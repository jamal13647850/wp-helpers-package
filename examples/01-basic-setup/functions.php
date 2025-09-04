<?php
/**
 * مثال پایه راه‌اندازی پکیج WordPress Helpers
 * 
 * این فایل نمونه نشان می‌دهد که چگونه پکیج را در قالب خود راه‌اندازی کنید
 */

// بارگذاری Composer Autoloader
require_once get_template_directory() . '/vendor/autoload.php';

// راه‌اندازی ServiceProvider
use jamal13647850\wphelpers\ServiceProvider;
ServiceProvider::boot();

// راه‌اندازی View برای استفاده گسترده
use jamal13647850\wphelpers\Views\View;

function get_theme_view() {
    static $view = null;
    
    if ($view === null) {
        $view = new View();
        
        // افزودن مسیرهای template
        $view->addPath(get_template_directory() . '/templates', 'theme');
        $view->addPath(get_template_directory() . '/components', 'components');
        
        // ثبت توابع سفارشی
        $view->registerFunction('get_theme_option', function($key, $default = '') {
            return get_theme_mod($key, $default);
        });
        
        $view->registerFunction('format_price', function($price) {
            return number_format($price) . ' تومان';
        });
        
        $view->registerFunction('get_asset_url', function($path) {
            return get_template_directory_uri() . '/assets/' . ltrim($path, '/');
        });
    }
    
    return $view;
}

// ثبت منوها
add_action('after_setup_theme', function() {
    register_nav_menus([
        'primary' => 'منوی اصلی',
        'mobile' => 'منوی موبایل',
        'footer' => 'منوی فوتر'
    ]);
});

// پشتیبانی قالب
add_theme_support('post-thumbnails');
add_theme_support('title-tag');
add_theme_support('custom-logo');

// تنظیمات Customizer
add_action('customize_register', function($wp_customize) {
    $wp_customize->add_section('theme_options', [
        'title' => 'تنظیمات قالب',
        'priority' => 30
    ]);
    
    $wp_customize->add_setting('site_logo');
    $wp_customize->add_control(new WP_Customize_Image_Control(
        $wp_customize,
        'site_logo',
        [
            'label' => 'لوگوی سایت',
            'section' => 'theme_options'
        ]
    ));
});