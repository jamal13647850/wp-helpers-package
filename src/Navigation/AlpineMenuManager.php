<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Navigation;

defined('ABSPATH') || exit();

/**
 * Alpine Menu Manager Class
 *
 * Simplifies the generation of WordPress navigation menus using AlpineNavWalker.
 * Provides dedicated methods for desktop, mobile, simple, and dropdown menu types.
 *
 * @author Sayyed Jamal Ghasemi
 * @version 1.2.0
 */
class AlpineMenuManager
{
    /**
     * Default arguments for wp_nav_menu.
     * @var array
     */
    private array $default_args = [
        'echo' => false, 
        'fallback_cb' => false, 
    ];

    /**
     * Generates a desktop navigation menu using AlpineNavWalker.
     *
     * @param string $theme_location The theme location for the menu.
     * @param array  $override_args  Optional. Arguments to override or supplement the defaults for wp_nav_menu.
     * @param array  $walker_options Optional. Options to pass to the AlpineNavWalker constructor for styling.
     * @return string The HTML output for the desktop menu.
     */
    public function get_desktop_menu(string $theme_location, array $override_args = [], array $walker_options = []): string
    {
        $desktop_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'primary-menu-desktop', 
            'menu_class'     => 'flex items-center space-x-2 relative', 
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="' . esc_attr__('Primary Navigation', 'your-theme-textdomain') . '">%3$s</ul>',
            'walker'         => new AlpineNavWalker('desktop', $walker_options),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($desktop_defaults, $this->default_args));
        
        $args['walker'] = new AlpineNavWalker('desktop', $walker_options); 
        $args['echo'] = false; 

        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : ''; 
    }

    /**
     * Generates a mobile navigation menu using AlpineNavWalker.
     *
     * @param string $theme_location The theme location for the menu.
     * @param array  $override_args  Optional. Arguments to override or supplement the defaults for wp_nav_menu.
     * @param array  $walker_options Optional. Options to pass to the AlpineNavWalker constructor for styling.
     * @return string The HTML output for the mobile menu.
     */
    public function get_mobile_menu(string $theme_location, array $override_args = [], array $walker_options = []): string
    {
        $mobile_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'primary-menu-mobile',
            'menu_class'     => 'space-y-2', 
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="' . esc_attr__('Primary Mobile Navigation', 'your-theme-textdomain') . '">%3$s</ul>',
            'walker'         => new AlpineNavWalker('mobile', $walker_options),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($mobile_defaults, $this->default_args));
        $args['walker'] = new AlpineNavWalker('mobile', $walker_options);
        $args['echo'] = false;

        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : '';
    }

    /**
     * Generates a simple navigation menu (e.g., for top bar or footer) using AlpineNavWalker.
     *
     * @param string $theme_location The theme location for the menu.
     * @param array  $override_args  Optional. Arguments to override or supplement the defaults for wp_nav_menu.
     * @param array  $walker_options Optional. Options to pass to the AlpineNavWalker constructor for styling.
     * @return string The HTML output for the simple menu.
     */
    public function get_simple_menu(string $theme_location, array $override_args = [], array $walker_options = []): string
    {
        $simple_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'top-menu', 
            'menu_class'     => 'flex items-center space-x-1 sm:space-x-2 lg:space-x-4', 
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="' . esc_attr__('Top Navigation', 'your-theme-textdomain') . '">%3$s</ul>',
            'walker'         => new AlpineNavWalker('simple', $walker_options),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($simple_defaults, $this->default_args));
        $args['walker'] = new AlpineNavWalker('simple', $walker_options);
        $args['echo'] = false;

        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : '';
    }
    
    /**
     * Generates a dropdown navigation menu using AlpineNavWalker.
     *
     * @param string $theme_location The theme location for the menu.
     * @param array  $override_args  Optional. Arguments to override or supplement the defaults for wp_nav_menu.
     * @param array  $walker_options Optional. Options to pass to the AlpineNavWalker constructor for styling.
     * @return string The HTML output for the dropdown menu.
     */
    public function get_dropdown_menu(string $theme_location, array $override_args = [], array $walker_options = []): string
    {
        $dropdown_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'dropdown-menu',
            'menu_class'     => 'flex list-none m-0 p-0', 
            'items_wrap'     => '<ul id="%1$s" class="%2$s" x-data="{ openDropdown: null, openSubmenu: null }">%3$s</ul>',
            'walker'         => new AlpineNavWalker('dropdown', $walker_options),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($dropdown_defaults, $this->default_args));
        $args['walker'] = new AlpineNavWalker('dropdown', $walker_options);
        $args['echo'] = false;

        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : '';
    }
}