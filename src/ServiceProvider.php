<?php

/*
Sayyed Jamal Ghasemi — Full-Stack Developer  
📧 info@jamalghasemi.com  
🔗 LinkedIn: https://www.linkedin.com/in/jamal1364/  
📸 Instagram: https://www.instagram.com/jamal13647850  
💬 Telegram: https://t.me/jamal13647850  
🌐 https://jamalghasemi.com  
*/

/**
 * Package Bootstrap – wp-helpers
 * -------------------------------------------------
 * Registers all core services, Twig helpers, assets,
 * and slider/menu variants on WordPress startup.
 *
 * @author  Sayyed Jamal Ghasemi
 * @link    https://jamalghasemi.com
 */

declare(strict_types=1);

namespace jamal13647850\wphelpers;

use jamal13647850\wphelpers\Views\View;
use jamal13647850\wphelpers\Components\Slider\SliderManager;
use jamal13647850\wphelpers\Components\Slider\Variants\WideAuto\WideAuto;
use jamal13647850\wphelpers\Components\Menu\MenuManager;
use jamal13647850\wphelpers\Components\Menu\Variants\MobileMenu;
use jamal13647850\wphelpers\Components\Menu\Variants\SimpleMenu;
use jamal13647850\wphelpers\Components\Menu\Variants\DropdownMenu;
use jamal13647850\wphelpers\Components\Menu\Variants\DesktopMenu;

use jamal13647850\wphelpers\Components\Menu\Variants\MultiColumnDesktopMenu;
use jamal13647850\wphelpers\Components\Menu\Variants\OverlayMobileMenu;
use jamal13647850\wphelpers\Components\Menu\MenuCacheManager;
use jamal13647850\wphelpers\Utilities\Clear_Theme_Cache;

defined('ABSPATH') || exit();

/**
 * ServiceProvider
 *
 * Handles registration and bootstrapping of all wp-helpers services:
 * - Registers all slider and menu variants
 * - Adds global Twig helpers
 * - Enqueues shared assets (JS/CSS)
 *
 * Usage:
 *   ServiceProvider::boot();
 */
final class ServiceProvider
{
    /**
     * Singleton boot flag to ensure bootstrapping occurs only once per request.
     *
     * @var bool
     */
    private static bool $booted = false;

    /**
     * Boot the package and register all core services and hooks.
     *
     * - Registers slider and menu variants
     * - Adds global Twig helpers after the theme setup
     * - Enqueues shared JavaScript and CSS assets
     *
     * @return void
     */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        $theme_settings_acf = new \jamal13647850\wphelpers\Utilities\Theme_Settings_ACF();

        new Clear_Theme_Cache();

        // 1) Register Slider Variants
        SliderManager::register('wide-auto', WideAuto::class);

        // 2) Register global Twig helpers after theme setup
        add_action('after_setup_theme', [self::class, 'registerTwigHelpers']);

        // 3) Enqueue shared front-end assets (JS/CSS)
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);

        // 4) Register all Menu Variants
        MenuManager::register('mobile',   MobileMenu::class);
        MenuManager::register('simple',   SimpleMenu::class);
        MenuManager::register('dropdown', DropdownMenu::class);
        MenuManager::register('desktop',  DesktopMenu::class);
       
        MenuManager::register('multi-column-desktop', MultiColumnDesktopMenu::class);
        MenuManager::register('overlay-mobile',     OverlayMobileMenu::class);

        add_action('wp_update_nav_menu',         [MenuCacheManager::getInstance(), 'purgeAll'], 99);
        add_action('wp_delete_nav_menu',         [MenuCacheManager::getInstance(), 'purgeAll'], 99);
        add_action('wp_nav_menu_item_updated',   [MenuCacheManager::getInstance(), 'purgeAll'], 99, 3);
        add_action('customize_save_after',       [MenuCacheManager::getInstance(), 'purgeAll'], 99);
        add_action('acf/save_post', function ($post_id) {
            if ($post_id === 'options') {
                MenuCacheManager::getInstance()->purgeAll();
            }
        }, 99);
    }

    /**
     * Register Twig helpers globally.
     *
     * Adds the `slider()` Twig function, allowing developers to write:
     *     {{ slider('wide-auto', slides, { interval: 7000 })|raw }}
     *
     * The registered function will:
     *   - Locate the slider variant by key
     *   - Render the slider with provided slides and options
     *
     * @return void
     */
    public static function registerTwigHelpers(): void
    {
        $view = new View(); // Uses internal DI / singleton
        $view->registerFunction('slider', static function (
            string $variantKey,
            array $slides = [],
            array $options = []
        ) {
            return \jamal13647850\wphelpers\Components\Slider\SliderManager::render(
                $variantKey,
                $slides,
                $options
            );
        });
    }

    /**
     * Register and enqueue shared JS & CSS assets.
     *
     * For example, registers Alpine.js (if not already registered),
     * and enqueues it for use in all sliders and menus.
     *
     * @return void
     */
    public static function enqueueAssets(): void
    {
        // Register Alpine.js (only if not already registered elsewhere)
        if (!wp_script_is('alpinejs', 'registered')) {
            wp_register_script(
                'alpinejs',
                'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
                [],
                null,
                true
            );
        }
        wp_enqueue_script('alpinejs');
    }
}

/* -----------------------------------------------------
 * Boot the package (can be called with require_once + autoload)
 * ---------------------------------------------------- */
ServiceProvider::boot();
