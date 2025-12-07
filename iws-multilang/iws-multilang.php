<?php
/**
 * Plugin Name: IWS Multilang Auto Translate
 * Description: 为 WordPress 提供多语言自动翻译（标题与内容），支持AI与Google机器翻译、缓存与小工具。
 * Version: 0.2.0
 * Author: IWSHENG BLOG
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'IWS_ML_VERSION', '0.2.0' );
define( 'IWS_ML_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IWS_ML_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// 自动加载包含文件
require_once IWS_ML_PLUGIN_DIR . 'includes/class-iws-activator.php';
require_once IWS_ML_PLUGIN_DIR . 'includes/class-iws-admin.php';
require_once IWS_ML_PLUGIN_DIR . 'includes/class-iws-service.php';
require_once IWS_ML_PLUGIN_DIR . 'includes/class-iws-cache.php';
require_once IWS_ML_PLUGIN_DIR . 'includes/class-iws-html-translator.php'; 
require_once IWS_ML_PLUGIN_DIR . 'includes/class-iws-topbar.php';
require_once IWS_ML_PLUGIN_DIR . 'includes/class-iws-frontend.php';
require_once IWS_ML_PLUGIN_DIR . 'includes/class-iws-widget.php';


// 激活钩子：建表等
register_activation_hook( __FILE__, 'iws_ml_on_activate' );

function iws_ml_on_activate() {
    // 原有激活逻辑
    IWS_ML_Activator::activate();

    // 标记需要在下次进入后台时跳转到安装向导
    add_option( 'iws_ml_do_activation_redirect', 1 );
}

// 处理激活后后台首访重定向
function iws_ml_activation_redirect() {
    // 只在后台执行
    if ( ! is_admin() ) {
        return;
    }

    // 如果没有这个 option，跳过
    if ( ! get_option( 'iws_ml_do_activation_redirect' ) ) {
        return;
    }

    // 删除 option，避免死循环
    delete_option( 'iws_ml_do_activation_redirect' );

    // 如果是批量激活或正在执行其他安装操作，可以根据需要添加更多判断
    // 这里简单跳到安装向导页面
    $url = admin_url( 'options-general.php?page=iws-ml-setup' );
    wp_safe_redirect( $url );
    exit;
}
add_action( 'admin_init', 'iws_ml_activation_redirect' );

// 插件初始化
function iws_ml_init() {
    // 后台设置 / 向导
    if ( is_admin() ) {
        new IWS_ML_Admin();
    }

    // 缓存与服务
    $cache   = new IWS_ML_Cache();
    $service = new IWS_ML_Service( $cache );

    // 前台翻译
    new IWS_ML_Frontend( $service, $cache );

    // 顶部语言条
    new IWS_ML_Topbar( $service );

    // 注册小工具
    add_action( 'widgets_init', function () use ( $service, $cache ) {
        register_widget( new IWS_ML_Widget( $service, $cache ) );
    } );
}
add_action( 'plugins_loaded', 'iws_ml_init' );
