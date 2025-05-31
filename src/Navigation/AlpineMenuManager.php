<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Navigation;

use jamal13647850\wphelpers\Navigation\AlpineNavWalker;

defined('ABSPATH') || exit();

/**
 * Alpine Menu Manager Class
 *
 * Simplifies the generation of WordPress navigation menus using AlpineNavWalker.
 * Provides dedicated methods for desktop, mobile, and simple menu types.
 *
 * @author Sayyed Jamal Ghasemi
 * @version 1.0.0
 */
class AlpineMenuManager
{
    /**
     * Default arguments for wp_nav_menu.
     * These can be overridden by passing an $args array to the public methods.
     * @var array
     */
    private array $default_args = [
        'echo' => false,
        'fallback_cb' => false, // Recommended to prevent WP from outputting a page list if menu not found
    ];

    /**
     * Generates a desktop navigation menu using AlpineNavWalker.
     *
     * @param string $theme_location The theme location for the menu.
     * @param array  $override_args  Optional. Arguments to override or supplement the defaults for wp_nav_menu.
     * @return string The HTML output for the desktop menu.
     */
    public function get_desktop_menu(string $theme_location, array $override_args = []): string
    {
        $desktop_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'primary-menu-desktop',
            'menu_class'     => 'flex items-center space-x-2 relative', 
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="Primary Navigation">%3$s</ul>', 
            'walker'         => new AlpineNavWalker('desktop'),
        ];
        
        $args = wp_parse_args($override_args, wp_parse_args($desktop_defaults, $this->default_args));
        // Ensure our specific walker and echo settings are not overridden by $override_args
        $args['walker'] = new AlpineNavWalker('desktop');
        $args['echo'] = false;

        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : '';
    }

    /**
     * Generates a mobile navigation menu using AlpineNavWalker.
     *
     * @param string $theme_location The theme location for the menu.
     * @param array  $override_args  Optional. Arguments to override or supplement the defaults for wp_nav_menu.
     * @return string The HTML output for the mobile menu.
     */
    public function get_mobile_menu(string $theme_location, array $override_args = []): string
    {
        $mobile_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'primary-menu-mobile',
            'menu_class'     => 'space-y-2', 
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="Primary Navigation Mobile">%3$s</ul>',
            'walker'         => new AlpineNavWalker('mobile'),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($mobile_defaults, $this->default_args));
        // Ensure our specific walker and echo settings are not overridden by $override_args
        $args['walker'] = new AlpineNavWalker('mobile');
        $args['echo'] = false;
        
        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : '';
    }

    /**
     * Generates a simple navigation menu (e.g., for top bar or footer) using AlpineNavWalker.
     *
     * @param string $theme_location The theme location for the menu.
     * @param array  $override_args  Optional. Arguments to override or supplement the defaults for wp_nav_menu.
     * @return string The HTML output for the simple menu.
     */
    public function get_simple_menu(string $theme_location, array $override_args = []): string
    {
        $simple_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'top-menu', 
            'menu_class'     => 'flex items-center space-x-1 sm:space-x-2 lg:space-x-4', 
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="Top Navigation">%3$s</ul>',
            'walker'         => new AlpineNavWalker('simple'),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($simple_defaults, $this->default_args));
        // Ensure our specific walker and echo settings are not overridden by $override_args
        $args['walker'] = new AlpineNavWalker('simple');
        $args['echo'] = false;

        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : '';
    }
}