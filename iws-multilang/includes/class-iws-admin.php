<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IWS_ML_Admin {

    private $option_name = 'iws_ml_options';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_iws_ml_clear_cache', array( $this, 'handle_clear_cache' ) );
    }

    public function add_menu() {
        // 常规设置页
        add_options_page(
            'IWS 翻译设置',
            'IWS 翻译',
            'manage_options',
            'iws-ml-settings',
            array( $this, 'render_settings_page' )
        );

        // 安装向导页（隐藏子菜单）
        add_submenu_page(
            null,
            'IWS 翻译安装向导',
            'IWS 翻译安装向导',
            'manage_options',
            'iws-ml-setup',
            array( $this, 'render_setup_wizard' )
        );
    }

    public function register_settings() {
        register_setting( 'iws_ml_settings_group', $this->option_name );

        add_settings_section(
            'iws_ml_main_section',
            '基本设置',
            '__return_false',
            'iws-ml-settings'
        );

        add_settings_field(
            'enabled',
            '启用自动翻译',
            array( $this, 'field_enabled' ),
            'iws-ml-settings',
            'iws_ml_main_section'
        );

        add_settings_field(
            'provider',
            '翻译服务提供商',
            array( $this, 'field_provider' ),
            'iws-ml-settings',
            'iws_ml_main_section'
        );

        add_settings_field(
            'google_api_key',
            'Google API Key',
            array( $this, 'field_google_api_key' ),
            'iws-ml-settings',
            'iws_ml_main_section'
        );

        add_settings_field(
            'glm_api_key',
            'GLM API Key',
            array( $this, 'field_glm_api_key' ),
            'iws-ml-settings',
            'iws_ml_main_section'
        );

        add_settings_field(
            'glm_base_url',
            'GLM 接口地址',
            array( $this, 'field_glm_base_url' ),
            'iws-ml-settings',
            'iws_ml_main_section'
        );

        add_settings_field(
            'claude_api_key',
            'Claude API Key',
            array( $this, 'field_claude_api_key' ),
            'iws-ml-settings',
            'iws_ml_main_section'
        );

        add_settings_field(
            'claude_model',
            'Claude 模型名',
            array( $this, 'field_claude_model' ),
            'iws-ml-settings',
            'iws_ml_main_section'
        );

        add_settings_field(
            'languages',
            '可用语言',
            array( $this, 'field_languages' ),
            'iws-ml-settings',
            'iws_ml_main_section'
        );

        add_settings_section(
            'iws_ml_cache_section',
            '缓存设置',
            '__return_false',
            'iws-ml-settings'
        );

        add_settings_field(
            'cache_enabled',
            '启用缓存',
            array( $this, 'field_cache_enabled' ),
            'iws-ml-settings',
            'iws_ml_cache_section'
        );

        add_settings_field(
            'cache_days',
            '缓存天数',
            array( $this, 'field_cache_days' ),
            'iws-ml-settings',
            'iws_ml_cache_section'
        );
    }

    private function get_options() {
        $opts = get_option( $this->option_name );
        if ( ! is_array( $opts ) ) {
            $opts = array();
        }
        return $opts;
    }

    /* ====== 设置页字段 ====== */

    public function field_enabled() {
        $opts    = $this->get_options();
        $enabled = isset( $opts['enabled'] ) ? (int) $opts['enabled'] : 0;
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enabled]" value="1" <?php checked( 1, $enabled ); ?> />
            启用首页和文章页的自动翻译
        </label>
        <?php
    }

    public function field_provider() {
        $opts     = $this->get_options();
        $provider = isset( $opts['provider'] ) ? $opts['provider'] : 'google';
        ?>
        <select name="<?php echo esc_attr( $this->option_name ); ?>[provider]">
            <option value="google" <?php selected( $provider, 'google' ); ?>>Google Cloud Translation</option>
            <option value="glm4flash" <?php selected( $provider, 'glm4flash' ); ?>>GLM-4-Flash（智谱免费）</option>
            <option value="claude" <?php selected( $provider, 'claude' ); ?>>Claude-3-Haiku（Anthropic）</option>
            <option value="microsoft" <?php selected( $provider, 'microsoft' ); ?>>Microsoft（预留）</option>
        </select>
        <p class="description">
            建议：长期免费可用优先 GLM-4-Flash 或 Claude-3-Haiku；Google 适合稳定传统机器翻译。
        </p>
        <?php
    }

    public function field_google_api_key() {
        $opts = $this->get_options();
        $val  = isset( $opts['google_api_key'] ) ? $opts['google_api_key'] : '';
        ?>
        <input type="password" style="width: 400px;" name="<?php echo esc_attr( $this->option_name ); ?>[google_api_key]" value="<?php echo esc_attr( $val ); ?>" />
        <p class="description">在 Google Cloud Translation 控制台获取 API Key 并填入（可选）。</p>
        <?php
    }

    public function field_glm_api_key() {
        $opts = $this->get_options();
        $val  = isset( $opts['glm_api_key'] ) ? $opts['glm_api_key'] : '';
        ?>
        <input type="password" style="width: 400px;" name="<?php echo esc_attr( $this->option_name ); ?>[glm_api_key]" value="<?php echo esc_attr( $val ); ?>" />
        <p class="description">在智谱 AI 平台创建 GLM-4-Flash API Key，并粘贴到这里。</p>
        <?php
    }

    public function field_glm_base_url() {
        $opts      = $this->get_options();
        $val       = isset( $opts['glm_base_url'] ) ? $opts['glm_base_url'] : '';
        $default   = 'https://open.bigmodel.cn/api/paas/v4/chat/completions';
        $display_v = $val ? $val : $default;
        ?>
        <input type="text" style="width: 400px;" name="<?php echo esc_attr( $this->option_name ); ?>[glm_base_url]" value="<?php echo esc_attr( $display_v ); ?>" />
        <p class="description">GLM-4-Flash 对话接口地址，默认：<?php echo esc_html( $default ); ?>。</p>
        <?php
    }

    public function field_claude_api_key() {
        $opts = $this->get_options();
        $val  = isset( $opts['claude_api_key'] ) ? $opts['claude_api_key'] : '';
        ?>
        <input type="password" style="width: 400px;" name="<?php echo esc_attr( $this->option_name ); ?>[claude_api_key]" value="<?php echo esc_attr( $val ); ?>" />
        <p class="description">在 Anthropic Claude 开发者平台创建 API Key，并粘贴到这里。</p>
        <?php
    }

    public function field_claude_model() {
        $opts    = $this->get_options();
        $model   = isset( $opts['claude_model'] ) ? $opts['claude_model'] : 'claude-3-haiku-20240307';
        ?>
        <input type="text" style="width: 300px;" name="<?php echo esc_attr( $this->option_name ); ?>[claude_model]" value="<?php echo esc_attr( $model ); ?>" />
        <p class="description">Claude 模型名，默认为 de>claude-3-haiku-20240307</code>，可按官方文档更换。</p>
        <?php
    }

    public function field_languages() {
        $opts      = $this->get_options();
        $languages = isset( $opts['languages'] ) && is_array( $opts['languages'] )
            ? $opts['languages']
            : array( 'en', 'zh-TW', 'es', 'fr' );

        $all_langs = array(
            'en'    => 'English',
            'zh-TW' => '繁體中文',
            'es'    => 'Español',
            'fr'    => 'Français',
        );
        foreach ( $all_langs as $code => $label ) : ?>
            <label style="margin-right: 15px;">
                <input type="checkbox"
                       name="<?php echo esc_attr( $this->option_name ); ?>[languages][]"
                       value="<?php echo esc_attr( $code ); ?>"
                    <?php checked( in_array( $code, $languages, true ) ); ?> />
                <?php echo esc_html( $label . ' (' . $code . ')' ); ?>
            </label>
        <?php endforeach; ?>
        <p class="description">选择前台可切换的目标语言。</p>
        <?php
    }

    public function field_cache_enabled() {
        $opts          = $this->get_options();
        $cache_enabled = isset( $opts['cache_enabled'] ) ? (int) $opts['cache_enabled'] : 1;
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[cache_enabled]" value="1" <?php checked( 1, $cache_enabled ); ?> />
            启用数据库缓存翻译结果
        </label>
        <?php
    }

    public function field_cache_days() {
        $opts       = $this->get_options();
        $cache_days = isset( $opts['cache_days'] ) ? (int) $opts['cache_days'] : 30;
        ?>
        <input type="number" min="1" name="<?php echo esc_attr( $this->option_name ); ?>[cache_days]" value="<?php echo esc_attr( $cache_days ); ?>" />
        <span>天</span>
        <?php
    }

    /* ====== 清空缓存处理 ====== */

    public function handle_clear_cache() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( '权限不足。' );
        }

        check_admin_referer( 'iws_ml_clear_cache_action', 'iws_ml_clear_cache_nonce' );

        global $wpdb;
        $table = $wpdb->prefix . 'iws_translations';

        $wpdb->query( "TRUNCATE TABLE {$table}" );

        $redirect = add_query_arg(
            array(
                'page'          => 'iws-ml-settings',
                'iws_ml_cleared'=> 1,
            ),
            admin_url( 'options-general.php' )
        );
        wp_safe_redirect( $redirect );
        exit;
    }

    /* ====== 设置页渲染 ====== */

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>IWS Multilang 自动翻译设置</h1>

            <?php if ( isset( $_GET['iws_ml_cleared'] ) && (int) $_GET['iws_ml_cleared'] === 1 ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>已成功清空所有翻译缓存，下次访问页面将重新调用翻译接口。</p>
                </div>
            <?php endif; ?>

            <p>如果需要重新运行安装向导，可以点击：
                <a href="<?php echo esc_url( admin_url( 'options-general.php?page=iws-ml-setup' ) ); ?>">重新运行安装向导</a>
            </p>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'iws_ml_settings_group' );
                do_settings_sections( 'iws-ml-settings' );
                submit_button();
                ?>
            </form>

            <hr />

            <h2>缓存管理</h2>
            <p>如果你修改了翻译逻辑或测试过错误结果，可以在这里一键清空缓存。</p>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'iws_ml_clear_cache_action', 'iws_ml_clear_cache_nonce' ); ?>
                <input type="hidden" name="action" value="iws_ml_clear_cache" />
                <?php submit_button( '清空所有翻译缓存', 'delete' ); ?>
            </form>
        </div>
        <?php
    }

    /* ====== 安装向导（带 Claude / GLM / Google 配置） ====== */

    public function render_setup_wizard() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $step = isset( $_GET['step'] ) ? (int) $_GET['step'] : 1;
        if ( $step < 1 || $step > 3 ) {
            $step = 1;
        }

        $options   = $this->get_options();
        $test_info = array(
            'message' => '',
            'success' => false,
        );

        if ( isset( $_POST['iws_ml_setup_nonce'] ) && wp_verify_nonce( $_POST['iws_ml_setup_nonce'], 'iws_ml_setup' ) ) {
            $current_step = isset( $_POST['iws_ml_step'] ) ? (int) $_POST['iws_ml_step'] : 1;
            $is_test      = isset( $_POST['iws_ml_test'] ) && '1' === $_POST['iws_ml_test'];

            if ( 1 === $current_step ) {
                $provider       = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : 'google';
                $google_api_key = isset( $_POST['google_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['google_api_key'] ) ) : '';
                                $glm_api_key    = isset( $_POST['glm_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['glm_api_key'] ) ) : '';
                $glm_base_url   = isset( $_POST['glm_base_url'] ) ? sanitize_text_field( wp_unslash( $_POST['glm_base_url'] ) ) : '';
                $claude_api_key = isset( $_POST['claude_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['claude_api_key'] ) ) : '';
                $claude_model   = isset( $_POST['claude_model'] ) ? sanitize_text_field( wp_unslash( $_POST['claude_model'] ) ) : '';

                $options['provider']       = $provider;
                $options['google_api_key'] = $google_api_key;
                $options['glm_api_key']    = $glm_api_key;
                if ( $glm_base_url ) {
                    $options['glm_base_url'] = $glm_base_url;
                }
                $options['claude_api_key'] = $claude_api_key;
                if ( $claude_model ) {
                    $options['claude_model'] = $claude_model;
                }

                update_option( $this->option_name, $options );

                if ( $is_test ) {
                    // 仍然只测试 Google，避免误用付费大模型
                    $sample_text = '你好，世界';
                    $result      = $this->test_google_translation( $google_api_key, $sample_text, 'en' );
                    if ( is_wp_error( $result ) ) {
                        $test_info['message'] = '测试失败：' . $result->get_error_message();
                        $test_info['success'] = false;
                    } else {
                        $test_info['message'] = '测试成功："' . esc_html( $sample_text ) . '" → "' . esc_html( $result ) . '"';
                        $test_info['success'] = true;
                    }
                    $step = 1;
                } else {
                    wp_safe_redirect( add_query_arg( 'step', 2, admin_url( 'options-general.php?page=iws-ml-setup' ) ) );
                    exit;
                }
            } elseif ( 2 === $current_step ) {
                $langs = isset( $_POST['languages'] ) && is_array( $_POST['languages'] )
                    ? array_map( 'sanitize_text_field', wp_unslash( $_POST['languages'] ) )
                    : array( 'en' );

                $cache_days = isset( $_POST['cache_days'] ) ? (int) $_POST['cache_days'] : 30;

                $options['languages']      = $langs;
                $options['cache_days']     = $cache_days;
                $options['cache_enabled']  = 1;
                update_option( $this->option_name, $options );

                wp_safe_redirect( add_query_arg( 'step', 3, admin_url( 'options-general.php?page=iws-ml-setup' ) ) );
                exit;
            } elseif ( 3 === $current_step ) {
                $options['enabled'] = 1;
                update_option( $this->option_name, $options );

                wp_safe_redirect( admin_url( 'options-general.php?page=iws-ml-settings' ) );
                exit;
            }
        }

        ?>
        <div class="wrap">
            <h1>IWS Multilang 安装向导</h1>
            <ol class="iws-ml-steps" style="margin-bottom: 20px;">
                <li><strong>步骤 1：</strong>选择服务商并配置 API Key（支持测试 Google）</li>
                <li><strong>步骤 2：</strong>选择语言与缓存策略</li>
                <li><strong>步骤 3：</strong>启用并完成</li>
            </ol>

            <?php if ( 1 === $step ) : ?>
                <h2>步骤 1：选择服务商与 API Key</h2>

                <?php if ( $test_info['message'] ) : ?>
                    <div class="<?php echo $test_info['success'] ? 'notice notice-success' : 'notice notice-error'; ?> is-dismissible">
                        <p><?php echo esc_html( $test_info['message'] ); ?></p>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?php wp_nonce_field( 'iws_ml_setup', 'iws_ml_setup_nonce' ); ?>
                    <input type="hidden" name="iws_ml_step" value="1" />

                    <table class="form-table">
                        <tr>
                            <th scope="row">翻译服务提供商</th>
                            <td>
                                <select name="provider">
                                    <option value="google" <?php selected( isset( $options['provider'] ) ? $options['provider'] : 'google', 'google' ); ?>>
                                        Google Cloud Translation
                                    </option>
                                    <option value="glm4flash" <?php selected( isset( $options['provider'] ) ? $options['provider'] : 'google', 'glm4flash' ); ?>>
                                        GLM-4-Flash（智谱免费大模型）
                                    </option>
                                    <option value="claude" <?php selected( isset( $options['provider'] ) ? $options['provider'] : 'google', 'claude' ); ?>>
                                        Claude-3-Haiku（Anthropic）
                                    </option>
                                    <option value="microsoft" disabled>Microsoft（当前未实现）</option>
                                </select>
                                <p class="description">可以先用 Google 测试接口连通，再切换到 GLM 或 Claude 做主力翻译。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Google API Key</th>
                            <td>
                                <input type="password" style="width: 400px;" name="google_api_key"
                                       value="<?php echo isset( $options['google_api_key'] ) ? esc_attr( $options['google_api_key'] ) : ''; ?>" />
                                <p class="description">在 Google Cloud 控制台创建 Translation API 密钥，并粘贴到这里（可选）。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">GLM API Key</th>
                            <td>
                                <input type="password" style="width: 400px;" name="glm_api_key"
                                       value="<?php echo isset( $options['glm_api_key'] ) ? esc_attr( $options['glm_api_key'] ) : ''; ?>" />
                                <p class="description">在智谱 AI 平台创建 GLM-4-Flash API Key，并粘贴到这里（可选）。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">GLM 接口地址</th>
                            <td>
                                <?php
                                $default_glm = 'https://open.bigmodel.cn/api/paas/v4/chat/completions';
                                $glm_url     = isset( $options['glm_base_url'] ) && $options['glm_base_url']
                                    ? $options['glm_base_url']
                                    : $default_glm;
                                ?>
                                <input type="text" style="width: 400px;" name="glm_base_url"
                                       value="<?php echo esc_attr( $glm_url ); ?>" />
                                <p class="description">如无特殊需求，保持默认即可：<?php echo esc_html( $default_glm ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Claude API Key</th>
                            <td>
                                <input type="password" style="width: 400px;" name="claude_api_key"
                                       value="<?php echo isset( $options['claude_api_key'] ) ? esc_attr( $options['claude_api_key'] ) : ''; ?>" />
                                <p class="description">在 Anthropic Claude 开发者平台创建 API Key，并粘贴到这里（可选）。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Claude 模型名</th>
                            <td>
                                <?php
                                $claude_model = isset( $options['claude_model'] ) && $options['claude_model']
                                    ? $options['claude_model']
                                    : 'claude-3-haiku-20240307';
                                ?>
                                <input type="text" style="width: 300px;" name="claude_model"
                                       value="<?php echo esc_attr( $claude_model ); ?>" />
                                <p class="description">默认 de>claude-3-haiku-20240307</code>，如有更新可在此修改。</p>
                            </td>
                        </tr>
                    </table>

                    <p>
                        <button type="submit" class="button button-secondary" name="iws_ml_test" value="1">
                            测试 Google 翻译（将“你好，世界”翻译为英文）
                        </button>
                        &nbsp;
                        <button type="submit" class="button button-primary">
                            保存并进入步骤 2
                        </button>
                    </p>
                </form>

            <?php elseif ( 2 === $step ) : ?>
                <h2>步骤 2：选择语言与缓存策略</h2>
                <form method="post">
                    <?php wp_nonce_field( 'iws_ml_setup', 'iws_ml_setup_nonce' ); ?>
                    <input type="hidden" name="iws_ml_step" value="2" />

                    <table class="form-table">
                        <tr>
                            <th scope="row">可用语言</th>
                            <td>
                                <?php
                                $langs = isset( $options['languages'] ) && is_array( $options['languages'] )
                                    ? $options['languages']
                                    : array( 'en', 'zh-TW', 'es', 'fr' );

                                $all_langs = array(
                                    'en'    => 'English',
                                    'zh-TW' => '繁體中文',
                                    'es'    => 'Español',
                                    'fr'    => 'Français',
                                );
                                foreach ( $all_langs as $code => $label ) : ?>
                                    <label style="margin-right: 15px;">
                                        <input type="checkbox" name="languages[]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $langs, true ) ); ?> />
                                        <?php echo esc_html( $label . ' (' . $code . ')' ); ?>
                                    </label>
                                <?php endforeach; ?>
                                <p class="description">建议保留 2–4 个常用语言，过多语言会增加调用次数与费用。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">缓存天数</th>
                            <td>
                                <?php $cache_days = isset( $options['cache_days'] ) ? (int) $options['cache_days'] : 30; ?>
                                <input type="number" min="1" name="cache_days" value="<?php echo esc_attr( $cache_days ); ?>" />
                                <span>天</span>
                                <p class="description">建议 30 天以上，避免重复调用翻译 API。</p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button( '保存并进入步骤 3' ); ?>
                </form>

            <?php elseif ( 3 === $step ) : ?>
                <h2>步骤 3：启用并完成</h2>
                <p>即将启用 IWS Multilang 自动翻译功能：</p>
                <ul>
                    <li>首页文章标题和摘要将根据选定语言自动翻译。</li>
                    <li>文章详情页标题和内容将自动翻译并缓存。</li>
                    <li>侧栏语言按钮会在 URL 添加 de>?iws_lang=xx</code> 参数切换语言。</li>
                </ul>

                <form method="post">
                    <?php wp_nonce_field( 'iws_ml_setup', 'iws_ml_setup_nonce' ); ?>
                    <input type="hidden" name="iws_ml_step" value="3" />
                    <?php submit_button( '启用并前往设置页' ); ?>
                </form>

            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * 在安装向导中测试 Google 翻译是否可用。
     */
    private function test_google_translation( $api_key, $text, $target_lang ) {
        if ( '' === trim( $api_key ) ) {
            return new WP_Error( 'iws_ml_no_key', '未填写 Google API Key。' );
        }

        $endpoint = 'https://translation.googleapis.com/language/translate/v2?key=' . rawurlencode( $api_key );

        $body = array(
            'q'      => $text,
            'target' => $target_lang,
        );

        $response = wp_remote_post(
            $endpoint,
            array(
                'timeout'   => 15,
                'headers'   => array(
                    'Content-Type' => 'application/json',
                ),
                'body'      => wp_json_encode( $body ),
                'sslverify' => true,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return new WP_Error( 'iws_ml_bad_code', 'HTTP 状态码：' . $code );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! isset( $data['data']['translations'][0]['translatedText'] ) ) {
            return new WP_Error( 'iws_ml_no_data', '返回数据中没有译文字段。' );
        }

        return $data['data']['translations'][0]['translatedText'];
    }
}

