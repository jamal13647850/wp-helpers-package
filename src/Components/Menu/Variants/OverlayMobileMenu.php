<?php
/**
 * Sayyed Jamal Ghasemi — Full-Stack Developer
 * 📧 info@jamalghasemi.com
 * 🔗 LinkedIn: https://www.linkedin.com/in/jamal1364/
 * 📸 Instagram: https://www.instagram.com/jamal13647850
 * 💬 Telegram: https://t.me/jamal13647850
 * 🌐 https://jamalghasemi.com
 */

declare(strict_types=1);

namespace jamal13647850\wphelpers\Components\Menu\Variants;

use jamal13647850\wphelpers\Components\Menu\AbstractMenu;
use jamal13647850\wphelpers\Navigation\OverlayMobileWalker;

// Ensure WordPress is loaded before executing this file
defined('ABSPATH') || exit();

/**
 * OverlayMobileMenu Class
 * 
 * Renders an overlay-style mobile navigation menu for WordPress themes.
 * This component creates a full-screen mobile menu with Alpine.js integration
 * for smooth accordion-style interactions and responsive behavior.
 * 
 * Features:
 * - Alpine.js powered accordion navigation
 * - Customizable container styling and attributes
 * - Support for menu icons and SVG carets
 * - Classic or independent accordion modes
 * - Configurable depth levels
 * - Accessibility features (ARIA labels, keyboard navigation)
 * 
 * Usage Example:
 * ```php
 * $mobileMenu = new OverlayMobileMenu();
 * echo $mobileMenu->render('primary-menu', [
 *     'container_class' => 'my-mobile-menu',
 *     'max_depth' => 3
 * ]);
 * ```
 * 
 * @package jamal13647850\wphelpers\Components\Menu\Variants
 * @since 1.0.0
 * @final This class should not be extended
 */
final class OverlayMobileMenu extends AbstractMenu
{
    /**
     * Get default configuration options for the overlay mobile menu
     * 
     * Defines the standard settings used when no custom options are provided.
     * These defaults ensure consistent behavior and styling across implementations.
     * 
     * Option Details:
     * - container_id: HTML ID attribute for the menu container
     * - container_class: CSS classes applied to the menu container
     * - aria_label: Accessibility label for screen readers (Persian)
     * - mobile_link_class: CSS class for top-level menu links
     * - mobile_submenu_class: CSS class for submenu/dropdown links
     * - enable_icons: Whether to display menu item icons
     * - caret_svg: Enable SVG caret icons for dropdowns
     * - accordion_mode: 'classic' (single open) or 'independent' (multiple open)
     * - max_depth: Maximum nesting levels for menu hierarchy (1-5 recommended)
     * - alpine_xdata: Alpine.js state initialization for accordion behavior
     * - alpine_bind: Alpine.js directives for show/hide and event handling
     * 
     * @return array<string, mixed> Default configuration array
     * @since 1.0.0
     */
    protected static function defaultOptions(): array
    {
        return [
            'container_id'          => 'mobile-menu',
            'container_class'       => 'mobile-menu',
            'aria_label'            => 'منوی موبایل',
            'mobile_link_class'     => 'mobile-menu-link',
            'mobile_submenu_class'  => 'mobile-submenu-link',
            'enable_icons'          => true,
            'caret_svg'             => true,
            'accordion_mode'        => 'classic', // 'classic' | 'independent'
            'max_depth'             => 5,
            'theme_location'        => '',
            'fallback_cb'           => false,
            'echo'                  => false,
            // Local state for classic accordion (opens) without affecting external mobileMenuOpen variable
            'alpine_xdata'          => 'x-data="{ opens: {} }"',
            // Show/hide entire panel with escape key and outside click handlers
            'alpine_bind'           => "x-cloak x-bind:class=\"{ 'active': mobileMenuOpen }\" @keyup.escape.window=\"mobileMenuOpen=false\" @click.outside=\"mobileMenuOpen=false\"",
        ];
    }

    /**
     * Render the overlay mobile menu HTML
     * 
     * Generates a complete mobile navigation menu with Alpine.js integration,
     * using WordPress's wp_nav_menu() function and a custom walker class.
     * The output includes accessibility features and responsive behavior.
     * 
     * Process Flow:
     * 1. Merge provided options with defaults
     * 2. Configure wp_nav_menu arguments with custom walker
     * 3. Generate menu HTML using WordPress navigation system
     * 4. Wrap in container div with Alpine.js attributes
     * 5. Return complete HTML string for output
     * 
     * @param string $themeLocation WordPress theme location identifier (registered with register_nav_menus)
     * @param array<string, mixed> $options Custom configuration options to override defaults
     * @param array<string, mixed> $walkerOptions Additional options passed to OverlayMobileWalker (currently unused)
     * 
     * @return string Complete HTML markup for the mobile menu
     * 
     * @throws \InvalidArgumentException If theme location is empty or invalid
     * @since 1.0.0
     * 
     * @example
     * ```php
     * // Basic usage with default options
     * $menu = new OverlayMobileMenu();
     * echo $menu->render('primary-menu');
     * 
     * // Advanced usage with custom options
     * echo $menu->render('primary-menu', [
     *     'container_class' => 'custom-mobile-menu',
     *     'accordion_mode' => 'independent',
     *     'max_depth' => 3,
     *     'enable_icons' => false
     * ]);
     * ```
     */
    public function render(string $themeLocation, array $options = [], array $walkerOptions = []): string
    {
        // Merge custom options with class defaults
        $opts = $this->makeOptions($options);

        // Configure WordPress navigation menu arguments
        $args = [
            'theme_location' => $themeLocation,
            'container'      => false,
            'items_wrap'     => '%3$s', // Output menu items without wrapping <ul>/<li>
            'walker'         => new OverlayMobileWalker([
                'mobile_link_class'     => (string) $opts->get('mobile_link_class'),
                'mobile_submenu_class'  => (string) $opts->get('mobile_submenu_class'),
                'enable_icons'          => (bool)   $opts->get('enable_icons'),
                'caret_svg'             => (bool)   $opts->get('caret_svg'),
                'accordion_mode'        => (string) $opts->get('accordion_mode'),
                'max_depth'             => (int)    $opts->get('max_depth'),
            ]),
            'fallback_cb'    => (bool) $opts->get('fallback_cb'),
            'echo'           => false, // Return HTML instead of echoing directly
        ];

        // Generate the menu HTML using WordPress navigation system
        $menuHtml = (string) wp_nav_menu($args);

        // Create container with internal state for classic accordion behavior
        // Alpine.js handles show/hide animations and keyboard/mouse interactions
        $container = sprintf(
            '<div id="%s" class="%s" role="dialog" aria-label="%s" %s %s>%s</div>',
            esc_attr((string) $opts->get('container_id')),
            esc_attr((string) $opts->get('container_class')),
            esc_attr((string) $opts->get('aria_label')),
            (string) $opts->get('alpine_xdata'),
            (string) $opts->get('alpine_bind'),
            $menuHtml
        );

        return $container;
    }
}