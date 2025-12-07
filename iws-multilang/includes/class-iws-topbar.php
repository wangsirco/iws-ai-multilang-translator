<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * IWS_ML_Topbar
 *
 * 在站点最顶部输出一个语言切换条：EN / 繁體中文 / ES / FR。
 * 使用 ?iws_lang=xx 参数切换语言，不依赖主题菜单。
 */
class IWS_ML_Topbar {

    /**
     * @var IWS_ML_Service
     */
    private $service;

    public function __construct( IWS_ML_Service $service ) {
        $this->service = $service;

        // 在 <body> 打开后插入顶栏（要求主题支持 wp_body_open）
        add_action( 'wp_body_open', array( $this, 'render_topbar' ) );
    }

    /**
     * 渲染顶部语言切换条
     */
    public function render_topbar() {
        if ( is_admin() ) {
            return;
        }

        if ( ! $this->service->is_enabled() ) {
            return;
        }

        // 当前允许的语言列表（和后台设置保持一致）
        $available = $this->service->get_languages();

        // 固定显示顺序
        $order = array( 'en', 'zh-TW', 'es', 'fr' );

        // 计算当前语言
        $current = isset( $_GET['iws_lang'] ) ? sanitize_text_field( wp_unslash( $_GET['iws_lang'] ) ) : '';

        // 生成基础 URL（剥离原有 iws_lang 参数）
        $url    = home_url( add_query_arg( array() ) );
        $parsed = wp_parse_url( $url );
        $query  = array();
        if ( ! empty( $parsed['query'] ) ) {
            parse_str( $parsed['query'], $query );
            unset( $query['iws_lang'] );
        }

        // 顶栏简单样式（不依赖主题 CSS）
        ?>
        <style>
            .iws-ml-topbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 9999;
                background: rgba(0, 0, 0, 0.82);
                color: #fff;
                font-size: 13px;
                line-height: 1;
                padding: 8px 16px;
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                box-sizing: border-box;
                backdrop-filter: blur(6px);
            }
            .iws-ml-topbar a {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 999px;
                border: 1px solid rgba(255,255,255,0.3);
                color: #fff;
                text-decoration: none;
                opacity: 0.8;
                transition: all 0.15s ease-in-out;
                background: transparent;
            }
            .iws-ml-topbar a:hover {
                opacity: 1;
                background: rgba(255,255,255,0.12);
            }
            .iws-ml-topbar a.iws-ml-active {
                opacity: 1;
                background: #ff4b8a;
                border-color: #ff4b8a;
            }
            body {
                /* 为避免遮挡内容，顶部预留一点空间（可按主题调整） */
                padding-top: 40px;
            }
            @media (max-width: 600px) {
                .iws-ml-topbar {
                    justify-content: center;
                    padding: 6px 8px;
                }
            }
        </style>
        <div class="iws-ml-topbar">
            <?php
            foreach ( $order as $code ) {
                if ( ! in_array( $code, $available, true ) ) {
                    continue;
                }

                switch ( $code ) {
                    case 'en':
                        $label = 'EN';
                        break;
                    case 'zh-TW':
                        $label = '繁中';
                        break;
                    case 'es':
                        $label = 'ES';
                        break;
                    case 'fr':
                        $label = 'FR';
                        break;
                    default:
                        $label = strtoupper( $code );
                }

                $query_with_lang = array_merge( $query, array( 'iws_lang' => $code ) );
                $link            = add_query_arg( $query_with_lang, home_url( $parsed['path'] ?? '/' ) );

                $active_class = ( $code === $current ) ? ' iws-ml-active' : '';
                echo '<a class="iws-ml-btn' . esc_attr( $active_class ) . '" href="' . esc_url( $link ) . '">' . esc_html( $label ) . '</a>';
            }
            ?>
        </div>
        <?php
    }
}

