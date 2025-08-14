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
use jamal13647850\wphelpers\Components\Menu\MenuManager;

defined('ABSPATH') || exit();

/**
 * OverlayMobileWithToggle - Complete mobile navigation solution with hamburger button
 * 
 * A composite menu component that combines a hamburger toggle button with an overlay mobile menu
 * in a single, cohesive unit. This component provides a complete mobile navigation solution
 * that handles both the trigger mechanism and the menu display.
 * 
 * Features:
 * - Hamburger-style toggle button with three-line icon
 * - Integrated Alpine.js state management for open/closed states
 * - Full accessibility support with ARIA attributes
 * - Seamless integration with OverlayMobileMenu component
 * - Single wrapper container with unified state management
 * - Persian accessibility labels for better UX in Persian-language sites
 * 
 * Architecture:
 * This component acts as a facade that orchestrates two main elements:
 * 1. Toggle button (hamburger icon) with click handlers
 * 2. OverlayMobileMenu component for the actual menu content
 * 
 * Both elements share a common Alpine.js state (`mobileMenuOpen`) for synchronized behavior.
 * 
 * Usage Example:
 * ```php
 * // Simple usage - button + menu with default options
 * echo MenuManager::render('overlay-mobile-with-toggle', 'primary');
 * 
 * // With custom button styling and menu options
 * echo MenuManager::render('overlay-mobile-with-toggle', 'primary', [
 *     'button_class' => 'custom-hamburger-btn',
 *     'menu_options' => [
 *         'max_depth' => 2,
 *         'accordion_mode' => 'independent'
 *     ]
 * ]);
 * ```
 * 
 * HTML Structure Generated:
 * ```html
 * <div class="wphelpers-overlay-mobile-nav" x-data="{ mobileMenuOpen: false }">
 *   <button id="mobile-menu-button" class="mobile-menu-btn" 
 *           aria-label="باز و بسته کردن منو" @click="mobileMenuOpen = !mobileMenuOpen">
 *     <span></span><span></span><span></span>
 *   </button>
 *   <!-- OverlayMobileMenu content -->
 *   <div id="mobile-menu" class="mobile-menu" role="dialog">...</div>
 * </div>
 * ```
 * 
 * @package jamal13647850\wphelpers\Components\Menu\Variants
 * @since 1.0.0
 * @author Sayyed Jamal Ghasemi
 * @see OverlayMobileMenu For the underlying menu component implementation
 * @see MenuManager For the component registration and rendering system
 */
final class OverlayMobileWithToggle extends AbstractMenu
{
    /**
     * Define default configuration options for the complete mobile navigation
     * 
     * Provides configuration for both the hamburger button and passes options
     * through to the underlying OverlayMobileMenu component.
     * 
     * @return array<string,mixed> Associative array of default option values
     * @since 1.0.0
     * 
     * Configuration Structure:
     * - Button-specific options for the hamburger toggle
     * - Pass-through options for the underlying menu component
     * - Persian accessibility labels for better localization
     * 
     * @example
     * ```php
     * $defaults = OverlayMobileWithToggle::defaultOptions();
     * // Customize button while keeping menu defaults
     * $custom = array_merge($defaults, [
     *     'button_class' => 'my-custom-hamburger',
     *     'menu_options' => ['max_depth' => 3]
     * ]);
     * ```
     */
    protected static function defaultOptions(): array
    {
        return [
            // Hamburger button configuration
            'button_id'          => 'mobile-menu-button',        // HTML ID attribute for the toggle button
            'button_class'       => 'mobile-menu-btn',           // CSS classes for button styling
            'button_label'       => 'باز و بسته کردن منو',        // Persian: "Open and close menu" - accessibility label
            
            // Pass-through options for OverlayMobileMenu component
            'menu_options'       => [],                          // Array of options forwarded to the underlying menu
        ];
    }

    /**
     * Render the complete mobile navigation HTML structure
     * 
     * Creates a unified mobile navigation solution by combining:
     * 1. Hamburger toggle button with Alpine.js click handlers
     * 2. OverlayMobileMenu component via MenuManager
     * 3. Shared Alpine.js state container for synchronized behavior
     * 
     * The component uses MenuManager to delegate the actual menu rendering
     * to the 'overlay-mobile' variant, ensuring consistency and avoiding
     * code duplication.
     * 
     * @param string $themeLocation WordPress theme location identifier for the menu
     * @param array  $options       Configuration options for both button and menu
     * @param array  $walkerOptions Optional walker-specific options (passed through to menu)
     * @return string Complete HTML markup for button + overlay menu
     * @since 1.0.0
     * 
     * State Management:
     * - Uses Alpine.js `x-data="{ mobileMenuOpen: false }"` for state
     * - Button toggles state with `@click="mobileMenuOpen = !mobileMenuOpen"`
     * - Menu responds to state changes for show/hide behavior
     * - ARIA attributes update reactively based on state
     * 
     * @example
     * ```php
     * // Basic usage
     * $component = new OverlayMobileWithToggle();
     * echo $component->render('primary');
     * 
     * // With custom button and menu options
     * echo $component->render('header_menu', [
     *     'button_class' => 'header-hamburger',
     *     'menu_options' => [
     *         'container_class' => 'header-mobile-menu',
     *         'max_depth' => 2
     *     ]
     * ]);
     * ```
     * 
     * Accessibility Features:
     * - `aria-label` with Persian description for screen readers
     * - `aria-controls` linking button to menu container
     * - `aria-expanded` state that updates reactively
     * - Semantic button element for proper keyboard navigation
     */
    public function render(string $themeLocation, array $options = [], array $walkerOptions = []): string
    {
        // Merge provided options with component defaults
        $opts = $this->makeOptions($options);
        
        // Generate hamburger toggle button with Alpine.js integration
        $button = sprintf(
            '<button id="%s" class="%s" aria-label="%s" aria-controls="mobile-menu" x-bind:aria-expanded="mobileMenuOpen ? \'true\' : \'false\'" @click="mobileMenuOpen = !mobileMenuOpen"><span></span><span></span><span></span></button>',
            esc_attr((string) $opts->get('button_id')),     // Unique button ID for styling and JavaScript targeting
            esc_attr((string) $opts->get('button_class')),  // CSS classes for hamburger icon styling
            esc_attr((string) $opts->get('button_label'))   // Persian accessibility label for screen readers
        );
        
        // Delegate menu rendering to the dedicated OverlayMobileMenu component
        // This ensures consistency and avoids duplicating menu logic
        $menuHtml = MenuManager::render(
            'overlay-mobile',                               // Component variant identifier
            $themeLocation,                                 // WordPress menu location
            (array) $opts->get('menu_options')              // Pass-through options for menu component
        );
        
        // Wrap both button and menu in unified Alpine.js state container
        // This creates a single source of truth for the open/closed state
        return '<div class="wphelpers-overlay-mobile-nav" x-data="{ mobileMenuOpen:false }">' . 
               $button . 
               $menuHtml . 
               '</div>';
    }
}