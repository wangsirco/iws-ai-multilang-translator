<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * IWS_ML_Html_Translator
 *
 * 将带 HTML 的文章内容拆成多个块（p/h1-h6/li/blockquote），
 * 优先只处理正文容器（.entry-content）内部的节点，
 * 避免与主题封面标题区域冲突。
 */
class IWS_ML_Html_Translator {

    /**
     * @var IWS_ML_Service
     */
    private $service;

    /**
     * 每个块最大允许的纯文本长度（防止一次传太长）
     */
    private $max_chunk_length = 3000;

    public function __construct( IWS_ML_Service $service ) {
        $this->service = $service;
    }

    /**
     * 对整篇文章内容进行分块翻译并返回新的 HTML。
     *
     * @param string $html 原始 HTML 内容（the_content）
     * @param string $lang 目标语言
     * @return string 翻译后的 HTML
     */
    public function translate_post_content( $html, $lang ) {
        if ( trim( $html ) === '' ) {
            return $html;
        }

        $doc = new DOMDocument();
        $internalErrors = libxml_use_internal_errors( true );

        // 用一个根 div 包裹，避免 DOMDocument 乱加标签
        $wrapped_html = '<div id="iws-ml-root">' . $html . '</div>';

        $doc->loadHTML(
            '<?xml encoding="utf-8" ?>' . $wrapped_html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors( $internalErrors );

        $xpath = new DOMXPath( $doc );

        // 优先只处理正文容器 .entry-content 内的内容
        $content_nodes = $xpath->query( '//*[@class and contains(concat(" ", normalize-space(@class), " "), " entry-content ")]' );

        $targets = array();

        if ( $content_nodes && $content_nodes->length > 0 ) {
            // 找到正文容器，只在容器内部选取块级节点
            $tags = array( 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'blockquote' );
            foreach ( $content_nodes as $content_node ) {
                foreach ( $tags as $tag ) {
                    $node_list = $content_node->getElementsByTagName( $tag );
                    foreach ( $node_list as $n ) {
                        $targets[] = $n;
                    }
                }
            }
        } else {
            // 没有 .entry-content，退回到全局查询，保证兼容极简主题
            $tags  = array( 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'blockquote' );
            $query = array();
            foreach ( $tags as $tag ) {
                $query[] = '//' . $tag;
            }
            $query   = implode( ' | ', $query );
            $nodes   = $xpath->query( $query );
            if ( $nodes && $nodes->length > 0 ) {
                foreach ( $nodes as $n ) {
                    $targets[] = $n;
                }
            }
        }

        if ( empty( $targets ) ) {
            // 找不到可翻译块，就退回简单翻译（整篇纯文本）
            $plain      = wp_strip_all_tags( $html, true );
            $translated = $this->service->translate_text( $plain, $lang );
            if ( $translated && $translated !== $plain ) {
                return nl2br( esc_html( $translated ) );
            }
            return $html;
        }

        foreach ( $targets as $node ) {
            /** @var DOMElement $node */
            $plain_text = trim( $this->get_text_content( $node ) );
            if ( $plain_text === '' ) {
                continue;
            }

            // 简单长度保护：太长就截断一部分，避免单块超限
            $text_for_translate = mb_substr( $plain_text, 0, $this->max_chunk_length );

            // 先用当前 provider 翻译整段
            $translated = $this->service->translate_text( $text_for_translate, $lang );

            // 如果返回是典型 AI 拒绝，则对整段使用 Google 兜底
            if ( $this->looks_like_refusal( $translated ) ) {
                $translated = $this->service->translate_via_google_forced( $text_for_translate, $lang );
            }

            if ( ! $translated || $translated === $text_for_translate ) {
                // 翻译失败或未变化，保留原文
                continue;
            }

            // 清空原节点内容，只塞一个文本节点（保留块级结构，不保留行内标签）
            while ( $node->firstChild ) {
                $node->removeChild( $node->firstChild );
            }
            $node->appendChild( $doc->createTextNode( $translated ) );
        }

        $root = $doc->getElementById( 'iws-ml-root' );
        if ( ! $root ) {
            return $html;
        }

        $new_html = $this->get_inner_html( $root );
        return $new_html !== '' ? $new_html : $html;
    }

    /**
     * 检测一段译文是否看起来像 AI 拒绝/合规提示。
     *
     * @param string $translated
     * @return bool
     */
    private function looks_like_refusal( $translated ) {
        if ( ! is_string( $translated ) || $translated === '' ) {
            return false;
        }

        $lower = strtolower( $translated );

        $phrases = array(
            // 英文常见
            'i cannot provide a translation',
            'i cannot provide translation for that content',
            'i am an ai assistant',
            'as an ai assistant',
            'i cannot assist with that request',
            'this content appears to describe potentially harmful or unethical behavior',
            'i cannot fulfill this request',
            'i cannot help with that request',
            'i am not able to translate this content',
            'go against my guidelines',
            'violate my safety policies',
            'i must decline',

            // 西班牙语常见
            'no puedo proporcionar una traducción',
            'no puedo traducir ese contenido',
            'como asistente de ia',
            'como asistente de inteligencia artificial',
            'no puedo ayudar con esa solicitud',
            'no puedo cumplir con esta solicitud',
            'va en contra de mis políticas',

            // 法语常见
            'je ne peux pas fournir une traduction',
            'je ne peux pas traduire ce contenu',
            'en tant qu\'assistant ia',
            'je ne peux pas vous aider avec cette demande',
            'je ne peux pas répondre à cette demande',
            'cela va à l\'encontre de mes politiques',
        );

        foreach ( $phrases as $phrase ) {
            if ( strpos( $lower, $phrase ) !== false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取某个节点的纯文本内容（包含子节点）。
     *
     * @param DOMNode $node
     * @return string
     */
    private function get_text_content( DOMNode $node ) {
        return $node->textContent;
    }

    /**
     * 获取某个元素内部的 HTML。
     *
     * @param DOMNode $node
     * @return string
     */
    private function get_inner_html( DOMNode $node ) {
        $innerHTML = '';
        foreach ( $node->childNodes as $child ) {
            $innerHTML .= $node->ownerDocument->saveHTML( $child );
        }
        return $innerHTML;
    }
}
