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
 * and slider variants on WordPress startup.
 *
 * @author  Sayyed Jamal Ghasemi
 * @link    https://jamalghasemi.com
 */

declare(strict_types=1);

namespace jamal13647850\wphelpers;

use jamal13647850\wphelpers\Views\View;
use jamal13647850\wphelpers\Components\Slider\SliderManager;
use jamal13647850\wphelpers\Components\Slider\Variants\WideAuto\WideAuto;

defined('ABSPATH') || exit();

/**
 * ServiceProvider
 *
 * Handles the registration and bootstrapping of core services,
 * slider variants, Twig helpers, and shared front-end assets for wp-helpers.
 *
 * Usage:
 *   ServiceProvider::boot();
 */
final class ServiceProvider
{
    /**
     * Singleton boot flag to ensure bootstrapping occurs only once.
     *
     * @var bool
     */
    private static bool $booted = false;

    /**
     * Boot the package and register all core services and hooks.
     *
     * - Registers slider variants.
     * - Adds global Twig helpers after the theme setup.
     * - Enqueues shared JavaScript and CSS assets.
     *
     * @return void
     */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        /** -------------------------------------------------
         * 1) Register Slider Variants
         * ------------------------------------------------- */
        SliderManager::register('wide-auto', WideAuto::class);

        /** -------------------------------------------------
         * 2) Register global Twig helpers after theme setup
         * ------------------------------------------------- */
        add_action('after_setup_theme', [self::class, 'registerTwigHelpers']);

        /** -------------------------------------------------
         * 3) Enqueue shared front-end assets
         * ------------------------------------------------- */
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
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
        $view = new View(); // uses internal DI / singleton pattern
        $view->registerFunction('slider', static function (
            string $variantKey,
            array $slides = [],
            array $options = []
        ) {
            return SliderManager::make($variantKey)->render($slides, $options);
        });
    }

    /**
     * Register and enqueue shared JS & CSS assets.
     *
     * For example, registers Alpine.js (if not already registered),
     * and enqueues it for use in all sliders.
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
