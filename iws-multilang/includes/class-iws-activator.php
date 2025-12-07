<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IWS_ML_Activator {

    public static function activate() {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'iws_translations';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            lang VARCHAR(20) NOT NULL,
            field VARCHAR(20) NOT NULL,
            translated_text LONGTEXT NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY post_lang_field (post_id, lang, field)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql ); // 依据 SQL 创建或更新表结构 [web:54]

        // 默认配置
        $default_options = array(
            'enabled'        => 0,
            'provider'       => 'google',
            'google_api_key' => '',
            'languages'      => array( 'en', 'zh-TW', 'es', 'fr' ),
            'cache_enabled'  => 1,
            'cache_days'     => 30,
        );

        add_option( 'iws_ml_options', $default_options );
    }
}
