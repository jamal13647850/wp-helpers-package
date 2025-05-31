<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Navigation;

// It's good practice to ensure the dependent class is loaded or use autoloading.
// Assuming AlpineNavWalker.php is in the same directory or autoloaded.
// require_once __DIR__ . '/AlpineNavWalker.php'; 

defined('ABSPATH') || exit();

/**
 * Alpine Menu Manager Class
 *
 * Simplifies the generation of WordPress navigation menus using AlpineNavWalker.
 * Provides dedicated methods for desktop, mobile, and simple menu types.
 * Now allows passing walker options to AlpineNavWalker.
 *
 * @author Sayyed Jamal Ghasemi
 * @version 1.1.0
 */
class AlpineMenuManager
{
    /**
     * Default arguments for wp_nav_menu.
     * These can be overridden by passing an $args array to the public methods.
     * @var array
     */
    private array $default_args = [
        'echo' => false, // Critical: ensures wp_nav_menu returns the string
        'fallback_cb' => false, // Recommended to prevent WP from outputting a page list if menu not found
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
            'menu_id'        => 'primary-menu-desktop', // Suggest making this configurable via $override_args
            'menu_class'     => 'flex items-center space-x-2 relative', // CSS classes for the <ul> element
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="' . esc_attr__('Primary Navigation', 'your-theme-textdomain') . '">%3$s</ul>',
            // Walker is now instantiated with $walker_options
            'walker'         => new AlpineNavWalker('desktop', $walker_options),
        ];

        // Merge override_args first, then with our specific desktop_defaults, then with class-wide default_args
        $args = wp_parse_args($override_args, wp_parse_args($desktop_defaults, $this->default_args));
        
        // Ensure our critical settings (echo and walker instance with options) are preserved if $override_args tries to change them in a basic way.
        // If $override_args provides its own 'walker' instance, that will be used. If it's a string or null, our new instance below takes precedence.
        // If $override_args provides 'echo' => true, it will be overridden by false below.
        $args['walker'] = new AlpineNavWalker('desktop', $walker_options); // Ensure our walker with options is used
        $args['echo'] = false; // Must be false to return the menu as a string

        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : ''; // Ensure a string is always returned
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
            'menu_id'        => 'primary-menu-mobile', // Suggest making this configurable
            'menu_class'     => 'space-y-2', // CSS classes for the <ul> element
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="' . esc_attr__('Primary Mobile Navigation', 'your-theme-textdomain') . '">%3$s</ul>',
            'walker'         => new AlpineNavWalker('mobile', $walker_options),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($mobile_defaults, $this->default_args));
        $args['walker'] = new AlpineNavWalker('mobile', $walker_options); // Ensure our walker with options is used
        $args['echo'] = false; // Must be false

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
            'menu_id'        => 'top-menu', // Suggest making this configurable
            'menu_class'     => 'flex items-center space-x-1 sm:space-x-2 lg:space-x-4', // CSS classes for the <ul> element
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="' . esc_attr__('Top Navigation', 'your-theme-textdomain') . '">%3$s</ul>',
            'walker'         => new AlpineNavWalker('simple', $walker_options),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($simple_defaults, $this->default_args));
        $args['walker'] = new AlpineNavWalker('simple', $walker_options); // Ensure our walker with options is used
        $args['echo'] = false; // Must be false

        $menu_output = wp_nav_menu($args);
        return is_string($menu_output) ? $menu_output : '';
    }
}