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
 * AssetManager
 * 
 * Registers and enqueues front-end CSS/JS assets only once per request,
 * preventing duplicate asset loading.
 */

declare(strict_types=1);

namespace jamal13647850\wphelpers\Assets;

/**
 * Class AssetManager
 *
 * Handles idempotent registration and enqueuing of WordPress CSS and JS assets.
 * Ensures that each asset handle is only enqueued once during a request.
 *
 * Usage:
 *   AssetManager::style('theme-css', '/css/theme.css');
 *   AssetManager::script('theme-js', '/js/theme.js');
 */
final class AssetManager
{
    /**
     * Tracks which asset handles have been registered/enqueued.
     *
     * @var bool[]
     */
    private static array $done = [];

    /**
     * Register and enqueue a CSS stylesheet by handle.
     * Does nothing if already called with this handle in the current request.
     *
     * @param string      $handle   Unique style handle.
     * @param string      $src      Stylesheet URL.
     * @param array       $deps     Array of dependency handles.
     * @param string|null $ver      Stylesheet version.
     * @param string      $media    Media type (default 'all').
     *
     * @return void
     */
    public static function style(
        string $handle,
        string $src,
        array $deps = [],
        string $ver = null,
        string $media = 'all'
    ): void {
        if (isset(self::$done[$handle])) {
            return;
        }
        wp_enqueue_style($handle, $src, $deps, $ver, $media);
        self::$done[$handle] = true;
    }

    /**
     * Register and enqueue a JavaScript file by handle.
     * Does nothing if already called with this handle in the current request.
     *
     * @param string      $handle   Unique script handle.
     * @param string      $src      Script URL.
     * @param array       $deps     Array of dependency handles.
     * @param string|null $ver      Script version.
     * @param bool        $footer   Whether to enqueue in footer (default true).
     *
     * @return void
     */
    public static function script(
        string $handle,
        string $src,
        array $deps = [],
        string $ver = null,
        bool $footer = true
    ): void {
        if (isset(self::$done[$handle])) {
            return;
        }
        wp_enqueue_script($handle, $src, $deps, $ver, $footer);
        self::$done[$handle] = true;
    }
}

