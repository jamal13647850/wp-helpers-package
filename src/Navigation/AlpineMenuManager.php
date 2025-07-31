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

namespace jamal13647850\wphelpers\Navigation;

defined('ABSPATH') || exit();

/**
 * Alpine Menu Manager Class
 *
 * Helper for generating WordPress navigation menus using AlpineNavWalker.
 * Provides convenient methods for desktop, mobile, simple, and dropdown menus.
 * Ensures all ARIA/UI strings are in Persian (fa-IR).
 *
 * @author  Sayyed Jamal Ghasemi
 * @version 1.2.0
 */
class AlpineMenuManager
{
    /**
     * Default arguments for wp_nav_menu (non-visual output, no fallback).
     * @var array
     */
    private array $default_args = [
        'echo' => false,
        'fallback_cb' => false,
    ];

    /**
     * Generate a desktop navigation menu using AlpineNavWalker.
     * Styles: horizontal, flex, intended for desktop breakpoint.
     *
     * @param string $theme_location Theme location registered in WordPress.
     * @param array  $override_args  (Optional) Custom wp_nav_menu args to override defaults.
     * @param array  $walker_options (Optional) Customization options for AlpineNavWalker.
     * @return string HTML markup of the menu or empty string on failure.
     *
     * @example
     *   echo (new AlpineMenuManager())->get_desktop_menu('primary');
     */
    public function get_desktop_menu(string $theme_location, array $override_args = [], array $walker_options = []): string
    {
        // Define defaults for desktop menu appearance and ARIA label (Persian)
        $desktop_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'primary-menu-desktop',
            'menu_class'     => 'flex items-center space-x-2 relative',
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="' . esc_attr__('ناوبری اصلی', 'your-theme-textdomain') . '">%3$s</ul>',
            'walker'         => new AlpineNavWalker('desktop', $walker_options),
        ];

        // Merge argument priorities: override > desktop > class default
        $args = wp_parse_args($override_args, wp_parse_args($desktop_defaults, $this->default_args));
        // Ensure a fresh Walker instance and non-echo output
        $args['walker'] = new AlpineNavWalker('desktop', $walker_options);
        $args['echo'] = false;

        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : '';
    }

    /**
     * Generate a mobile navigation menu using AlpineNavWalker.
     * Styles: vertical, intended for mobile breakpoint, with expandable sections.
     *
     * @param string $theme_location Theme location registered in WordPress.
     * @param array  $override_args  (Optional) Custom wp_nav_menu args to override defaults.
     * @param array  $walker_options (Optional) Customization options for AlpineNavWalker.
     * @return string HTML markup of the menu or empty string on failure.
     */
    public function get_mobile_menu(string $theme_location, array $override_args = [], array $walker_options = []): string
    {
        $mobile_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'primary-menu-mobile',
            'menu_class'     => 'space-y-2',
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="' . esc_attr__('ناوبری موبایل', 'your-theme-textdomain') . '">%3$s</ul>',
            'walker'         => new AlpineNavWalker('mobile', $walker_options),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($mobile_defaults, $this->default_args));
        $args['walker'] = new AlpineNavWalker('mobile', $walker_options);
        $args['echo'] = false;

        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : '';
    }

    /**
     * Generate a simple (minimal) navigation menu (for top bar/footer).
     * Styles: horizontal, compact, no dropdowns, intended for simple display.
     *
     * @param string $theme_location Theme location registered in WordPress.
     * @param array  $override_args  (Optional) Custom wp_nav_menu args to override defaults.
     * @param array  $walker_options (Optional) Customization options for AlpineNavWalker.
     * @return string HTML markup of the menu or empty string on failure.
     */
    public function get_simple_menu(string $theme_location, array $override_args = [], array $walker_options = []): string
    {
        $simple_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'top-menu',
            'menu_class'     => 'flex items-center space-x-1 sm:space-x-2 lg:space-x-4',
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="' . esc_attr__('ناوبری بالایی', 'your-theme-textdomain') . '">%3$s</ul>',
            'walker'         => new AlpineNavWalker('simple', $walker_options),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($simple_defaults, $this->default_args));
        $args['walker'] = new AlpineNavWalker('simple', $walker_options);
        $args['echo'] = false;

        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : '';
    }

    /**
     * Generate a dropdown navigation menu using AlpineNavWalker.
     * Styles: horizontal, supports dropdowns/multilevel.
     *
     * @param string $theme_location Theme location registered in WordPress.
     * @param array  $override_args  (Optional) Custom wp_nav_menu args to override defaults.
     * @param array  $walker_options (Optional) Customization options for AlpineNavWalker.
     * @return string HTML markup of the menu or empty string on failure.
     */
    public function get_dropdown_menu(string $theme_location, array $override_args = [], array $walker_options = []): string
    {
        $dropdown_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'dropdown-menu',
            'menu_class'     => 'flex list-none m-0 p-0',
            // Note: Alpine.js state for submenu handled in Walker <li>, not here.
            'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
            'walker'         => new AlpineNavWalker('dropdown', $walker_options),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($dropdown_defaults, $this->default_args));
        // Ensure only one instance of AlpineNavWalker per call
        if (!isset($args['walker']) || !($args['walker'] instanceof AlpineNavWalker)) {
            $args['walker'] = new AlpineNavWalker('dropdown', $walker_options);
        }
        $args['echo'] = false;

        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : '';
    }
}
