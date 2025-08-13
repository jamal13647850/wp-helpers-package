<?php
/**
 * File Name: AlpineMenuManager.php
 * Description: Helper for generating WordPress navigation menus. MODIFIED for accordion mobile menu.
 *
 * @package    wphelpers
 * @subpackage Navigation
 * @author     Sayyed Jamal Ghasemi
 * @link       https://jamalghasemi.com
 * @since      1.2.0
 * @version    1.3.0
 *
 * Developer Contact:
 * Email: jamal13647850@gmail.com
 * LinkedIn: https://www.linkedin.com/in/jamal1364/
 * Telegram: https://t.me/jamaldev
 *
 * Last Updated: <?php echo date('Y-m-d'); ?>
 */

declare(strict_types=1);

namespace jamal13647850\wphelpers\Navigation;

defined('ABSPATH') || exit();

/**
 * Class AlpineMenuManager
 *
 * Provides helper methods for generating different styles of WordPress menus
 * (desktop, mobile accordion, simple, dropdown) using AlpineNavWalker.
 *
 * - Standardizes menu markup and ARIA labels.
 * - Adds required Alpine.js state for mobile accordion menus.
 * - Supports override of default walker and menu arguments.
 *
 * Example usage:
 *   $manager = new AlpineMenuManager();
 *   echo $manager->get_mobile_menu('main_menu');
 *
 * @package    wphelpers\Navigation
 * @subpackage Navigation
 * @author     Sayyed Jamal Ghasemi
 * @version    1.3.0
 */
class AlpineMenuManager
{
    /**
     * Default arguments for wp_nav_menu.
     * These will be merged with menu-specific defaults and user overrides.
     *
     * @var array
     */
    private array $default_args = [
        'echo'        => false,
        'fallback_cb' => false,
    ];

    /**
     * Generate a desktop navigation menu (mega menu support).
     *
     * @param string $theme_location Theme location registered in WordPress.
     * @param array  $override_args  (Optional) Custom wp_nav_menu args to override defaults.
     * @param array  $walker_options (Optional) Options passed to AlpineNavWalker.
     * @return string HTML markup of the menu or empty string on failure.
     *
     * @example
     *   $manager = new AlpineMenuManager();
     *   echo $manager->get_desktop_menu('main_menu');
     */
    public function get_desktop_menu(string $theme_location, array $override_args = [], array $walker_options = []): string
    {
        $desktop_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'primary-menu-desktop',
            'menu_class'     => 'flex items-center space-x-2 relative',
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="' . esc_attr__('ناوبری اصلی', 'your-theme-textdomain') . '">%3$s</ul>',
            'walker'         => new AlpineNavWalker('desktop', $walker_options),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($desktop_defaults, $this->default_args));
        

        $menu_output = wp_nav_menu($args);

        return is_string($menu_output) ? $menu_output : '';
    }

    /**
     * Generate a mobile navigation menu with accordion behavior.
     *
     * This method adds Alpine.js state (`x-data="{ activeMenu: null }"`) to allow
     * only one submenu open at a time (accordion pattern).
     *
     * @param string $theme_location Theme location registered in WordPress.
     * @param array  $override_args  (Optional) Custom wp_nav_menu args to override defaults.
     * @param array  $walker_options (Optional) Options passed to AlpineNavWalker.
     * @return string HTML markup of the menu or empty string on failure.
     *
     * @example
     *   $manager = new AlpineMenuManager();
     *   echo $manager->get_mobile_menu('main_menu');
     */
    public function get_mobile_menu(string $theme_location, array $override_args = [], array $walker_options = []): string
    {
        $mobile_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'primary-menu-mobile',
            'menu_class'     => 'space-y-2',
            // ترجمه label به فارسی و اضافه‌کردن x-data برای اَکاردئون
            'items_wrap'     => '<ul id="%1$s" class="%2$s" x-data="{ activeMenu: null }" aria-label="' . esc_attr__('ناوبری موبایل', 'your-theme-textdomain') . '">%3$s</ul>',
            'walker'         => new AlpineNavWalker('mobile', $walker_options),
        ];

        $args           = wp_parse_args($override_args, wp_parse_args($mobile_defaults, $this->default_args));
        $args['walker'] = new AlpineNavWalker('mobile', $walker_options);
        $args['echo']   = false;

        $menu_output = wp_nav_menu($args);

        return is_string($menu_output) ? $menu_output : '';
    }

    /**
     * Generate a simple horizontal menu.
     *
     * @param string $theme_location Theme location registered in WordPress.
     * @param array  $override_args  (Optional) Custom wp_nav_menu args to override defaults.
     * @param array  $walker_options (Optional) Options passed to AlpineNavWalker.
     * @return string HTML markup of the menu or empty string on failure.
     *
     * @example
     *   $manager = new AlpineMenuManager();
     *   echo $manager->get_simple_menu('top_menu');
     */
    public function get_simple_menu(string $theme_location, array $override_args = [], array $walker_options = []): string
    {
        $simple_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'top-menu',
            'menu_class'     => 'flex items-center space-x-1 sm:space-x-2 lg:space-x-4',
            // ترجمه label به فارسی
            'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="' . esc_attr__('ناوبری بالایی', 'your-theme-textdomain') . '">%3$s</ul>',
            'walker'         => new AlpineNavWalker('simple', $walker_options),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($simple_defaults, $this->default_args));
        $args['walker'] = new AlpineNavWalker('simple', $walker_options);
        $args['echo']   = false;

        $menu_output = wp_nav_menu($args);

        return is_string($menu_output) ? $menu_output : '';
    }

    /**
     * Generate a dropdown menu.
     *
     * @param string $theme_location Theme location registered in WordPress.
     * @param array  $override_args  (Optional) Custom wp_nav_menu args to override defaults.
     * @param array  $walker_options (Optional) Options passed to AlpineNavWalker.
     * @return string HTML markup of the menu or empty string on failure.
     *
     * @example
     *   $manager = new AlpineMenuManager();
     *   echo $manager->get_dropdown_menu('dropdown_menu');
     */
    public function get_dropdown_menu(string $theme_location, array $override_args = [], array $walker_options = []): string
    {
        $dropdown_defaults = [
            'theme_location' => $theme_location,
            'menu_id'        => 'dropdown-menu',
            'menu_class'     => 'flex list-none m-0 p-0',
            // بدون label سفارشی
            'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
            'walker'         => new AlpineNavWalker('dropdown', $walker_options),
        ];

        $args = wp_parse_args($override_args, wp_parse_args($dropdown_defaults, $this->default_args));

        if (!isset($args['walker']) || !($args['walker'] instanceof AlpineNavWalker)) {
            $args['walker'] = new AlpineNavWalker('dropdown', $walker_options);
        }
        $args['echo'] = false;

        $menu_output = wp_nav_menu($args);

        return is_string($menu_output) ? $menu_output : '';
    }
}
