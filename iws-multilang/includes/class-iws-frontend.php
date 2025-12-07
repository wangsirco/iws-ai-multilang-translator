<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IWS_ML_Frontend {

    private $service;
    private $cache;
    private $html_translator;

    public function __construct( IWS_ML_Service $service, IWS_ML_Cache $cache ) {
        $this->service         = $service;
        $this->cache           = $cache;
        $this->html_translator = new IWS_ML_Html_Translator( $service );

        // 前台过滤标题、内容、摘要
        add_filter( 'the_title', array( $this, 'filter_title' ), 20, 2 );
        add_filter( 'the_content', array( $this, 'filter_content' ), 20 );
        add_filter( 'the_excerpt', array( $this, 'filter_excerpt' ), 20 );
    }

    private function get_current_lang() {
        if ( isset( $_GET['iws_lang'] ) ) {
            return sanitize_text_field( wp_unslash( $_GET['iws_lang'] ) );
        }
        return '';
    }

    private function should_translate() {
        if ( ! $this->service->is_enabled() ) {
            return false;
        }

        $lang = $this->get_current_lang();
        if ( '' === $lang ) {
            return false;
        }

        $langs = $this->service->get_languages();
        if ( ! in_array( $lang, $langs, true ) ) {
            return false;
        }

        return $lang;
    }

    /**
     * 翻译标题（列表 + 单文章）
     */
    public function filter_title( $title, $post_id ) {
        if ( is_admin() ) {
            return $title;
        }

        $lang = $this->should_translate();
        if ( ! $lang ) {
            return $title;
        }

        $post = get_post( $post_id );
        if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
            return $title;
        }

        if ( is_single( $post_id ) ) {
            $cached = $this->cache->get( $post_id, $lang, 'title' );
            if ( $cached ) {
                return $cached;
            }

            $plain_title = wp_strip_all_tags( $title, true );
            $translated  = $this->service->translate_text( $plain_title, $lang );

            if ( $translated && $translated !== $title ) {
                $this->cache->set( $post_id, $lang, 'title', $translated );
                return $translated;
            }

            return $title;
        }

        if ( in_the_loop() && is_main_query() && ( is_home() || is_archive() || is_search() ) ) {
            $cached = $this->cache->get( $post_id, $lang, 'title' );
            if ( $cached ) {
                return $cached;
            }

            $plain_title = wp_strip_all_tags( $title, true );
            $translated  = $this->service->translate_text( $plain_title, $lang );

            if ( $translated && $translated !== $title ) {
                $this->cache->set( $post_id, $lang, 'title', $translated );
                return $translated;
            }
        }

        return $title;
    }

    /**
     * 翻译正文内容（单篇文章，用 HTML 分块翻译）
     */
    public function filter_content( $content ) {
        if ( is_admin() ) {
            return $content;
        }

        if ( ! is_single() ) {
            return $content;
        }

        global $post;
        if ( ! $post instanceof WP_Post ) {
            return $content;
        }

        $lang = $this->should_translate();
        if ( ! $lang ) {
            return $content;
        }

        $cached = $this->cache->get( $post->ID, $lang, 'content' );
        if ( $cached ) {
            return $cached;
        }

        // 使用 HTML 分块翻译器处理正文
        $translated = $this->html_translator->translate_post_content( $content, $lang );

        if ( $translated && $translated !== $content ) {
            $this->cache->set( $post->ID, $lang, 'content', $translated );
            return $translated;
        }

        return $content;
    }

    /**
     * 翻译摘要（首页/列表），用纯文本
     */
    public function filter_excerpt( $excerpt ) {
        if ( is_admin() ) {
            return $excerpt;
        }

        if ( ! ( is_home() || is_front_page() || is_archive() || is_search() ) ) {
            return $excerpt;
        }

        global $post;
        if ( ! $post instanceof WP_Post ) {
            return $excerpt;
        }

        $lang = $this->should_translate();
        if ( ! $lang ) {
            return $excerpt;
        }

        $cached = $this->cache->get( $post->ID, $lang, 'excerpt' );
        if ( $cached ) {
            return $cached;
        }

        $plain_excerpt = wp_strip_all_tags( $excerpt, true );
        $translated    = $this->service->translate_text( $plain_excerpt, $lang );

        if ( $translated && $translated !== $plain_excerpt ) {
            $this->cache->set( $post->ID, $lang, 'excerpt', $translated );
            return esc_html( $translated );
        }

        return $excerpt;
    }
}
