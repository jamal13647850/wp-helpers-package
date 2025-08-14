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

namespace jamal13647850\wphelpers\Components\Menu\Variants;

use jamal13647850\wphelpers\Components\Menu\AbstractMenu;
use jamal13647850\wphelpers\Navigation\OverlayMobileWalker;

defined('ABSPATH') || exit();

/**
 * Overlay Mobile Menu Component
 *
 * A specialized mobile menu implementation that creates an overlay-style navigation
 * with Alpine.js state management and WordPress walker integration. This component
 * extends the AbstractMenu base class to provide mobile-optimized navigation with
 * accordion functionality, icons, and responsive behavior.
 *
 * Features:
 * - Overlay presentation with backdrop
 * - Alpine.js state management for open/close states
 * - Configurable accordion modes
 * - Icon support with SVG carets
 * - Multi-level navigation support
 * - Accessibility attributes and keyboard navigation
 * - Asset management (CSS/JS)
 *
 * Usage Example:
 * ```php
 * $menu = new OverlayMobileMenu();
 * echo $menu->render('primary', ['max_depth' => 3]);
 * ```
 *
 * @package jamal13647850\wphelpers\Components\Menu\Variants
 * @since 1.0.0
 * @author Sayyed Jamal Ghasemi
 */
final class OverlayMobileMenu extends AbstractMenu
{
    /**
     * Defines default configuration options for the overlay mobile menu
     *
     * Provides a comprehensive set of default options that control the menu's
     * appearance, behavior, and functionality. These options can be overridden
     * when rendering the menu to customize its behavior for specific use cases.
     *
     * Key Configuration Areas:
     * - Container styling and identification
     * - Mobile-specific CSS classes
     * - Icon and accordion behavior
     * - Navigation depth and fallback handling
     * - State management settings
     *
     * @return array<string, mixed> Associative array of default configuration options
     *
     * @since 1.0.0
     */
    protected static function defaultOptions(): array
    {
        return [
            // Container identification and styling
            'container_id'          => 'mobile-menu',
            'container_class'       => 'mobile-menu',
            'aria_label'            => 'منوی موبایل',

            // Mobile-specific styling classes
            'mobile_link_class'     => 'mobile-menu-link',
            'mobile_submenu_class'  => 'mobile-submenu-link',

            // Feature toggles
            'enable_icons'          => true,
            'caret_svg'             => true,

            // Accordion behavior configuration
            'accordion_mode'        => 'classic',
            'max_depth'             => 5,

            // WordPress menu integration
            'theme_location'        => '',
            'fallback_cb'           => false,
            'echo'                  => false,

            // State management configuration
            // When true, this component provides its own Alpine.js x-data state
            // Set to false when used with OverlayMobileWithToggle to avoid duplicate state
            'provide_state'         => true,
        ];
    }

    /**
     * Defines CSS and JavaScript assets required for the overlay mobile menu
     *
     * Manages the registration and enqueueing of necessary stylesheets and scripts
     * for the mobile menu functionality. Automatically calculates file versions
     * based on modification times for cache busting.
     *
     * Asset Structure:
     * - CSS: Theme-based styling from active theme directory
     * - JS: Plugin-based JavaScript for fallback functionality
     *
     * File Path Resolution:
     * - CSS files are loaded from the active theme's assets directory
     * - JavaScript files are loaded from the plugin's assets directory
     * - Version numbers are automatically generated from file modification times
     *
     * @return array<string, array> Multi-dimensional array containing 'styles' and 'scripts'
     *                              Each sub-array contains WordPress enqueue parameters
     *
     * @since 1.0.0
     */
    
    /**
     * Renders the complete overlay mobile menu HTML structure
     *
     * Orchestrates the complete rendering process for the mobile menu including:
     * - Asset enqueueing (CSS/JS)
     * - Option merging and validation
     * - WordPress menu generation with custom walker
     * - Alpine.js state management integration
     * - Accessibility attribute configuration
     *
     * Rendering Process:
     * 1. Enqueue required CSS and JavaScript assets
     * 2. Merge provided options with defaults
     * 3. Configure WordPress wp_nav_menu arguments
     * 4. Generate menu HTML using OverlayMobileWalker
     * 5. Wrap in container with Alpine.js bindings and accessibility features
     *
     * Alpine.js Integration:
     * - Conditional x-data attribute for state management
     * - x-bind:class for active state styling
     * - Event listeners for keyboard (Escape) and click-outside behavior
     *
     * Accessibility Features:
     * - role="dialog" for screen readers
     * - aria-label for menu identification
     * - Keyboard navigation support (Escape key)
     * - Focus management
     *
     * @param string $themeLocation WordPress theme location identifier for the menu
     * @param array<string, mixed> $options Optional configuration overrides
     * @param array<string, mixed> $walkerOptions Optional walker-specific configuration (currently unused)
     *
     * @return string Complete HTML markup for the overlay mobile menu
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // Basic usage with default options
     * $menu = new OverlayMobileMenu();
     * echo $menu->render('primary');
     *
     * // Advanced usage with custom options
     * echo $menu->render('primary', [
     *     'max_depth' => 3,
     *     'accordion_mode' => 'exclusive',
     *     'enable_icons' => false
     * ]);
     * ```
     */
    public function render(string $themeLocation, array $options = [], array $walkerOptions = []): string
    {
        // Ensure required CSS and JavaScript assets are loaded
        $this->enqueueAssets();

        // Merge provided options with component defaults
        $opts = $this->makeOptions($options);

        // Configure WordPress menu arguments with custom walker
        $args = [
            'theme_location' => $themeLocation,
            'container'      => false,
            'items_wrap'     => '%3$s', // Remove default <ul> wrapper for custom structure
            'walker'         => new OverlayMobileWalker([
                'mobile_link_class'     => (string) $opts->get('mobile_link_class'),
                'mobile_submenu_class'  => (string) $opts->get('mobile_submenu_class'),
                'enable_icons'          => (bool)   $opts->get('enable_icons'),
                'caret_svg'             => (bool)   $opts->get('caret_svg'),
                'accordion_mode'        => (string) $opts->get('accordion_mode'),
                'max_depth'             => (int)    $opts->get('max_depth'),
            ]),
            'fallback_cb'    => (bool) $opts->get('fallback_cb'),
            'echo'           => false,
        ];

        // Generate the menu HTML using WordPress navigation system
        $menuHtml = (string) wp_nav_menu($args);

        // Configure Alpine.js state management attribute
        // Only provide x-data when this component manages its own state
        // When used with OverlayMobileWithToggle, state is managed externally
        $stateAttr = (bool) $opts->get('provide_state')
            ? ' x-data="{ mobileMenuOpen:false, opens:{} }"'
            : '';

        // Assemble complete menu container with Alpine.js bindings and accessibility features
        return sprintf(
            '<div id="%s" class="%s" role="dialog" aria-label="%s"%s x-bind:class="{ \'active\': mobileMenuOpen }" @keyup.escape.window="mobileMenuOpen=false" @click.outside="mobileMenuOpen=false">%s</div>',
            esc_attr((string) $opts->get('container_id')),
            esc_attr((string) $opts->get('container_class')),
            esc_attr((string) $opts->get('aria_label')),
            $stateAttr,
            $menuHtml
        );
    }
}