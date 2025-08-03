<?php

/*
Sayyed Jamal Ghasemi — Full-Stack Developer  
📧 info@jamalghasemi.com  
🔗 LinkedIn: https://www.linkedin.com/in/jamal1364/  
📸 Instagram: https://www.instagram.com/jamal13647850  
💬 Telegram: https://t.me/jamal13647850  
🌐 https://jamalghasemi.com  
*/

declare(strict_types=1);

namespace jamal13647850\wphelpers\Components\Menu;

use InvalidArgumentException;

/**
 * MenuManager
 *
 * Central registry, factory, and rendering facade for all menu variants.
 * 
 * Hook points enable plugins/themes to modify input data or final HTML:
 * 
 * Hooks fired:
 *   - menu/before_render              (&$key, &$themeLocation, &$options, &$walkerOptions)
 *   - menu/before_render_{$key}       (&$themeLocation, &$options, &$walkerOptions)
 *   - menu/render_output              ($html, $key, $themeLocation, $options, $walkerOptions)
 *   - menu/render_output_{$key}       ($html, $themeLocation, $options, $walkerOptions)
 *   - menu/after_render               ($key, $html, $themeLocation, $options, $walkerOptions)
 *   - menu/after_render_{$key}        ($html, $themeLocation, $options, $walkerOptions)
 *
 * Usage:
 *   MenuManager::register('mega', MegaMenu::class);
 *   echo MenuManager::render('mega', 'main-menu');
 */
final class MenuManager
{
    /**
     * Map of menu keys to their variant class names.
     *
     * @var array<string, class-string<AbstractMenu>>
     */
    private static array $map = [];

    /**
     * Register a menu variant class with a unique key.
     *
     * @param string $key    Variant identifier (e.g., 'mega').
     * @param string $class  Fully qualified class name (must extend AbstractMenu).
     *
     * @throws InvalidArgumentException If $class does not extend AbstractMenu.
     *
     * @return void
     */
    public static function register(string $key, string $class): void
    {
        if (!is_subclass_of($class, AbstractMenu::class)) {
            // Exception message in Persian (fa-IR)
            throw new InvalidArgumentException("{$class} باید از AbstractMenu ارث‌بری کند.");
        }
        self::$map[$key] = $class;
    }

    /**
     * Instantiate a menu variant by key.
     *
     * @param string $key Menu variant identifier.
     *
     * @throws InvalidArgumentException If $key is not registered.
     *
     * @return AbstractMenu
     */
    public static function make(string $key): AbstractMenu
    {
        if (!isset(self::$map[$key])) {
            // Exception message in Persian (fa-IR)
            throw new InvalidArgumentException("منو با کلید [{$key}] ثبت نشده است.");
        }
        /** @var class-string<AbstractMenu> $class */
        $class = self::$map[$key];
        return new $class();
    }

    /**
     * Unified entry point for rendering a menu with extensibility hooks.
     *
     * Fires before/after hooks and allows filtering of the HTML output.
     *
     * @param string $key            Variant identifier.
     * @param string $themeLocation  WordPress menu theme location.
     * @param array  $options        Menu options for the variant (optional).
     * @param array  $walkerOptions  Options for the walker (optional).
     *
     * @return string                Rendered menu HTML.
     *
     * @example
     *   echo MenuManager::render('mega', 'main-menu', ['theme' => 'dark']);
     *
     * @side-effects
     *   Fires action/filter hooks as documented above for extensibility.
     */
    public static function render(
        string $key,
        string $themeLocation,
        array $options = [],
        array $walkerOptions = []
    ): string {
        // ---------- Pre-render hooks (parameters by reference) ----------
        do_action_ref_array('menu/before_render', [&$key, &$themeLocation, &$options, &$walkerOptions]);
        do_action_ref_array("menu/before_render_{$key}", [&$themeLocation, &$options, &$walkerOptions]);

        // ---------- Render menu variant ----------
        $html = self::make($key)->render($themeLocation, $options, $walkerOptions);

        // ---------- Filterable HTML output ----------
        $html = apply_filters('menu/render_output', $html, $key, $themeLocation, $options, $walkerOptions);
        $html = apply_filters("menu/render_output_{$key}", $html, $themeLocation, $options, $walkerOptions);

        // ---------- Post-render hooks (read-only) ----------
        do_action('menu/after_render', $key, $html, $themeLocation, $options, $walkerOptions);
        do_action("menu/after_render_{$key}", $html, $themeLocation, $options, $walkerOptions);

        return $html;
    }
}
