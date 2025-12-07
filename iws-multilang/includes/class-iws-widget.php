<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IWS_ML_Widget extends WP_Widget {

    private $service;
    private $cache;

    public function __construct( IWS_ML_Service $service, IWS_ML_Cache $cache ) {
        $this->service = $service;
        $this->cache   = $cache;

        parent::__construct(
            'iws_ml_widget',
            'IWS 语言切换',
            array(
                'description' => '显示 IWS 多语言自动翻译的语言切换按钮',
            )
        );

        // 简单前端样式（只加载一次）
        add_action( 'wp_head', array( $this, 'output_widget_styles' ) );
    }

    /**
     * 输出小工具按钮样式
     */
    public function output_widget_styles() {
        ?>
        <style>
            .iws-ml-lang-widget {
                margin-top: 10px;
            }
            .iws-ml-lang-widget .iws-ml-lang-title {
                font-weight: 600;
                font-size: 15px;
                margin-bottom: 8px;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .iws-ml-lang-widget .iws-ml-lang-title span.en {
                font-size: 11px;
                opacity: 0.7;
            }
            .iws-ml-lang-widget .iws-ml-lang-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }
            .iws-ml-lang-widget .iws-ml-lang-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 4px 10px;
                border-radius: 999px;
                border: 1px solid rgba(255,255,255,0.12);
                font-size: 13px;
                line-height: 1.4;
                cursor: pointer;
                text-decoration: none;
                color: inherit;
                background: rgba(255,255,255,0.03);
                transition: all 0.15s ease-out;
            }
            .iws-ml-lang-widget .iws-ml-lang-btn:hover {
                background: rgba(255,255,255,0.08);
                border-color: rgba(255,255,255,0.25);
                transform: translateY(-1px);
            }
            .iws-ml-lang-widget .iws-ml-lang-btn.active {
                background: #ff4b8b;
                border-color: #ff4b8b;
                color: #ffffff;
                box-shadow: 0 0 0 1px rgba(255,75,139,0.35);
            }
        </style>
        <?php
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '语言 Language';

        $current_lang = isset( $_GET['iws_lang'] ) ? sanitize_text_field( wp_unslash( $_GET['iws_lang'] ) ) : '';

        if ( ! $this->service->is_enabled() ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
            echo '<p>自动翻译未启用。</p>';
            echo $args['after_widget'];
            return;
        }

        $langs = $this->service->get_languages();
        if ( empty( $langs ) ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
            echo '<p>未配置可用语言。</p>';
            echo $args['after_widget'];
            return;
        }

        $current_url = $this->get_current_url_without_lang();

        echo '<div class="iws-ml-lang-widget">';

        echo $args['before_title'];
        echo '<div class="iws-ml-lang-title">';
        echo '<span>语言</span><span class="en">Language</span>';
        echo '</div>';
        echo $args['after_title'];

        echo '<div class="iws-ml-lang-buttons">';

        foreach ( $langs as $code ) {
            $label = $code;
            switch ( $code ) {
                case 'en':
                    $label = 'English';
                    break;
                case 'zh-TW':
                    $label = '繁體中文';
                    break;
                case 'es':
                    $label = 'Español';
                    break;
                case 'fr':
                    $label = 'Français';
                    break;
            }

            $url    = add_query_arg( 'iws_lang', $code, $current_url );
            $class  = 'iws-ml-lang-btn';
            if ( $current_lang === $code ) {
                $class .= ' active';
            }

            echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">';
            echo esc_html( $label );
            echo '</a>';
        }

        echo '</div>'; // .iws-ml-lang-buttons
        echo '</div>'; // .iws-ml-lang-widget

        echo $args['after_widget'];
    }

    private function get_current_url_without_lang() {
        $url = home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) );
        $url = remove_query_arg( 'iws_lang', $url );
        return $url;
    }

    public function form( $instance ) {
        $title = isset( $instance['title'] ) ? $instance['title'] : '语言 Language';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">标题：</label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance          = array();
        $instance['title'] = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
        return $instance;
    }
}
