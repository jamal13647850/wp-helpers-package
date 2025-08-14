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
 * OverlayMobileMenu - Mobile overlay menu component implementation
 * 
 * A concrete implementation of AbstractMenu that creates mobile-optimized overlay menus
 * with Alpine.js integration for smooth animations and accessibility features.
 * 
 * This component automatically handles:
 * - CSS asset loading with cache-busting
 * - Alpine.js data binding for mobile menu states
 * - ARIA accessibility attributes
 * - Custom walker integration for accordion functionality
 * - Responsive design considerations
 * 
 * Features:
 * - Overlay-style mobile menu with backdrop
 * - Accordion submenu navigation
 * - FontAwesome icon support
 * - Keyboard navigation (ESC to close)
 * - Click-outside-to-close functionality
 * - Automatic CSS enqueuing with file modification timestamps
 * 
 * Usage Example:
 * ```php
 * $mobileMenu = new OverlayMobileMenu();
 * echo $mobileMenu->render('primary', [
 *     'accordion_mode' => 'independent',
 *     'max_depth' => 3
 * ]);
 * ```
 * 
 * Required CSS Structure:
 * The component expects CSS at: `/assets/menu/overlay-mobile.css` in the active theme
 * 
 * @package jamal13647850\wphelpers\Components\Menu\Variants
 * @since 1.0.0
 * @author Sayyed Jamal Ghasemi
 * @see OverlayMobileWalker For the custom navigation walker implementation
 * @see AbstractMenu For the base menu component architecture
 */
final class OverlayMobileMenu extends AbstractMenu
{
    /**
     * Define default configuration options for the mobile overlay menu
     * 
     * Provides sensible defaults for all configurable aspects of the mobile menu,
     * including styling classes, Alpine.js bindings, accessibility labels, and
     * walker-specific options.
     * 
     * @return array<string,mixed> Associative array of default option values
     * @since 1.0.0
     * 
     * @example
     * ```php
     * $defaults = OverlayMobileMenu::defaultOptions();
     * // Override specific options while keeping defaults
     * $customOptions = array_merge($defaults, [
     *     'max_depth' => 2,
     *     'accordion_mode' => 'independent'
     * ]);
     * ```
     */
    protected static function defaultOptions(): array
    {
        return [
            // Container HTML attributes
            'container_id'          => 'mobile-menu',           // Main container element ID
            'container_class'       => 'mobile-menu',           // Main container CSS classes
            'aria_label'            => 'منوی موبایل',            // Persian: "Mobile Menu" for screen readers
            
            // Walker configuration options
            'mobile_link_class'     => 'mobile-menu-link',      // CSS class for top-level menu links
            'mobile_submenu_class'  => 'mobile-submenu-link',   // CSS class for submenu links
            'enable_icons'          => true,                    // Enable FontAwesome icon rendering
            'caret_svg'             => true,                    // Show dropdown caret indicators
            'accordion_mode'        => 'classic',               // 'classic' | 'independent' accordion behavior
            'max_depth'             => 5,                       // Maximum menu nesting depth
            
            // WordPress menu configuration
            'theme_location'        => '',                      // WordPress menu location identifier
            'fallback_cb'           => false,                   // Callback when no menu is assigned
            'echo'                  => false,                   // Return output instead of echoing
            
            // Alpine.js data and behavior bindings
            'alpine_xdata'          => 'x-data="{ opens: {} }"',                    // Initialize Alpine.js state tracking
            'alpine_bind'           => "x-cloak x-bind:class=\"{ 'active': mobileMenuOpen }\" @keyup.escape.window=\"mobileMenuOpen=false\" @click.outside=\"mobileMenuOpen=false\"", // Reactive behaviors
        ];
    }

    /**
     * Define required CSS and JavaScript assets for the mobile menu
     * 
     * Automatically generates asset URLs with cache-busting based on file modification times.
     * Follows WordPress theme structure conventions for asset organization.
     * 
     * Expected file location: `/assets/menu/overlay-mobile.css` within the active theme directory
     * 
     * @return array<string,array> Asset configuration arrays for styles and scripts
     * @since 1.0.0
     * 
     * Cache-busting strategy:
     * - Uses filemtime() to generate version numbers
     * - Ensures browsers reload CSS when files are updated
     * - Falls back to null version if file doesn't exist
     * 
     * @example
     * ```php
     * $assets = OverlayMobileMenu::assets();
     * // Returns:
     * // [
     * //     'styles' => [['handle' => 'overlay-mobile-menu', ...]],
     * //     'scripts' => []
     * // ]
     * ```
     */
    protected static function assets(): array
    {
        // Define relative path to CSS file within theme structure
        $rel  = '/assets/menu/overlay-mobile.css';
        $src  = get_stylesheet_directory_uri() . $rel;  // Public URL for browser loading
        $file = get_stylesheet_directory() . $rel;      // Server file path for existence check
        
        // Generate cache-busting version number from file modification time
        $ver  = file_exists($file) ? (string) filemtime($file) : null;

        return [
            'styles' => [
                [
                    'handle' => 'overlay-mobile-menu',   // WordPress style handle for dependency management
                    'src'    => $src,                    // Public CSS file URL
                    'deps'   => [],                      // No CSS dependencies
                    'ver'    => $ver,                    // Cache-busting version
                    'media'  => 'all',                   // Apply to all media types
                ],
            ],
            'scripts' => [], // No JavaScript files required (using Alpine.js from theme)
        ];
    }

    /**
     * Render the complete mobile overlay menu HTML structure
     * 
     * Orchestrates the entire menu rendering process including:
     * 1. Asset enqueuing with cache-busting
     * 2. Option merging and validation
     * 3. WordPress menu generation with custom walker
     * 4. Alpine.js integration and accessibility attributes
     * 5. Final HTML container assembly
     * 
     * @param string $themeLocation WordPress theme location identifier for the menu
     * @param array  $options       Optional configuration overrides for this render call
     * @param array  $walkerOptions Optional configuration overrides passed directly to OverlayMobileWalker
     * @return string Complete HTML markup for the mobile overlay menu
     * @since 1.0.0
     * 
     * @example
     * ```php
     * // Basic usage with theme location
     * echo $menu->render('primary');
     * 
     * // With custom options
     * echo $menu->render('primary', [
     *     'max_depth' => 2,
     *     'container_class' => 'custom-mobile-menu'
     * ]);
     * 
     * // With walker-specific options
     * echo $menu->render('primary', [], [
     *     'accordion_mode' => 'independent'
     * ]);
     * ```
     * 
     * HTML Structure:
     * ```html
     * <div id="mobile-menu" class="mobile-menu" role="dialog" 
     *      aria-label="منوی موبایل" x-data="{opens:{}}" x-cloak ...>
     *   <!-- Generated menu items via OverlayMobileWalker -->
     * </div>
     * ```
     */
    public function render(string $themeLocation, array $options = [], array $walkerOptions = []): string
    {
        // Ensure CSS assets are loaded before rendering
        $this->enqueueAssets();
        
        // Merge provided options with defaults
        $opts = $this->makeOptions($options);
        
        // Configure WordPress wp_nav_menu arguments
        $args = [
            'theme_location' => $themeLocation,                 // WordPress menu location to render
            'container'      => false,                          // Disable default <div> wrapper
            'items_wrap'     => '%3$s',                         // Output only menu items without <ul>/<li>
            'walker'         => new OverlayMobileWalker([       // Custom walker for mobile-optimized markup
                'mobile_link_class'     => (string) $opts->get('mobile_link_class'),
                'mobile_submenu_class'  => (string) $opts->get('mobile_submenu_class'),
                'enable_icons'          => (bool)   $opts->get('enable_icons'),
                'caret_svg'             => (bool)   $opts->get('caret_svg'),
                'accordion_mode'        => (string) $opts->get('accordion_mode'),
                'max_depth'             => (int)    $opts->get('max_depth'),
            ]),
            'fallback_cb'    => (bool) $opts->get('fallback_cb'), // Disable fallback when no menu assigned
            'echo'           => false,                             // Return HTML instead of echoing
        ];
        
        // Generate the menu HTML using WordPress core functionality
        $menuHtml = (string) wp_nav_menu($args);
        
        // Assemble final container with Alpine.js bindings and accessibility attributes
        return sprintf(
            '<div id="%s" class="%s" role="dialog" aria-label="%s" %s %s>%s</div>',
            esc_attr((string) $opts->get('container_id')),    // Unique container ID
            esc_attr((string) $opts->get('container_class')), // Container CSS classes
            esc_attr((string) $opts->get('aria_label')),      // Accessibility label (Persian)
            (string) $opts->get('alpine_xdata'),              // Alpine.js data initialization
            (string) $opts->get('alpine_bind'),               // Alpine.js reactive behaviors
            $menuHtml                                         // Generated menu content
        );
    }
}