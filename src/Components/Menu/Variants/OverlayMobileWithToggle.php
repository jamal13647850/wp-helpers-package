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
 * Overlay Mobile Menu with Hamburger Toggle Button
 *
 * A complete mobile navigation solution that combines a hamburger toggle button
 * with an overlay mobile menu in a unified component. This class orchestrates
 * the interaction between the toggle button and the menu overlay, providing
 * synchronized state management and styling through Alpine.js.
 *
 * Key Features:
 * - Hamburger button with animated states (active/inactive)
 * - Integrated overlay menu through MenuManager
 * - Unified Alpine.js state management scope
 * - Synchronized ARIA attributes for accessibility
 * - CSS class synchronization between button and menu states
 * - Automatic asset coordination
 *
 * Architecture:
 * This component acts as a coordinator that:
 * 1. Renders a hamburger toggle button with Alpine.js bindings
 * 2. Delegates menu rendering to OverlayMobileMenu via MenuManager
 * 3. Provides unified state management through a single x-data scope
 * 4. Ensures proper accessibility relationships between button and menu
 *
 * State Management:
 * - Uses Alpine.js x-data for reactive state
 * - mobileMenuOpen: Controls visibility and active states
 * - opens: Object for tracking submenu accordion states
 * - Prevents duplicate state by disabling menu's internal state provision
 *
 * Usage Example:
 * ```php
 * $navComponent = new OverlayMobileWithToggle();
 * echo $navComponent->render('primary', [
 *     'button_class' => 'custom-hamburger',
 *     'menu_options' => ['max_depth' => 3]
 * ]);
 * ```
 *
 * @package jamal13647850\wphelpers\Components\Menu\Variants
 * @since 1.0.0
 * @author Sayyed Jamal Ghasemi
 */
final class OverlayMobileWithToggle extends AbstractMenu
{
    /**
     * Defines default configuration options for the toggle button and menu integration
     *
     * Provides configuration options specifically for the hamburger button and
     * coordination with the overlay menu. The menu-specific options are delegated
     * to the OverlayMobileMenu component through the 'menu_options' parameter.
     *
     * Button Configuration:
     * - Unique identification and styling for the toggle button
     * - Accessibility labels in Persian for screen readers
     * - Pass-through options for the underlying menu component
     *
     * Integration Strategy:
     * The 'menu_options' array is passed directly to the OverlayMobileMenu
     * component, allowing full customization of menu behavior while maintaining
     * the integrated button functionality.
     *
     * @return array<string, mixed> Associative array of default configuration options
     *
     * @since 1.0.0
     */
    protected static function defaultOptions(): array
    {
        return [
            // Toggle button identification and styling
            'button_id'          => 'mobile-menu-button',
            'button_class'       => 'mobile-menu-btn',
            'button_label'       => 'باز و بسته کردن منو',

            // Options passed through to OverlayMobileMenu component
            // This allows full customization of the menu behavior
            'menu_options'       => [],
        ];
    }

    /**
     * Defines asset dependencies for the combined toggle and menu component
     *
     * This component delegates asset management to the underlying OverlayMobileMenu
     * component through the MenuManager. Since the menu component handles its own
     * CSS and JavaScript enqueueing, this method returns empty arrays to avoid
     * duplicate asset loading.
     *
     * Asset Management Strategy:
     * - No direct asset registration to prevent conflicts
     * - Relies on OverlayMobileMenu's asset management via MenuManager
     * - MenuManager automatically handles asset enqueueing when rendering
     * - CSS and JavaScript are loaded once regardless of component composition
     *
     * Benefits:
     * - Prevents duplicate asset loading
     * - Maintains clean separation of concerns
     * - Reduces HTTP requests and file size
     * - Ensures consistent styling across components
     *
     * @return array<string, array> Empty arrays for 'styles' and 'scripts'
     *                              since assets are managed by the menu component
     *
     * @since 1.0.0
     */
    protected static function assets(): array
    {
        // Asset management is delegated to OverlayMobileMenu via MenuManager
        // This prevents duplicate loading of CSS/JS resources
        return [
            'styles'  => [],
            'scripts' => [],
        ];
    }

    /**
     * Renders the complete hamburger button and overlay menu system
     *
     * Orchestrates the rendering of both the toggle button and the overlay menu
     * within a unified Alpine.js state management scope. This method ensures
     * proper coordination between the button and menu states, accessibility
     * relationships, and prevents duplicate state management.
     *
     * Rendering Process:
     * 1. Merge provided options with component defaults
     * 2. Generate hamburger button with Alpine.js bindings and accessibility attributes
     * 3. Render overlay menu via MenuManager with state provision disabled
     * 4. Wrap both components in unified container with shared state scope
     *
     * Button Features:
     * - Three-span hamburger icon structure for CSS animations
     * - Alpine.js bindings for aria-expanded and active class states
     * - Click handler for toggling menu visibility
     * - ARIA controls relationship with menu container
     *
     * State Management:
     * - Single x-data scope prevents conflicting state management
     * - mobileMenuOpen controls both button and menu states
     * - opens object manages submenu accordion states
     * - Disables menu's internal state provision to avoid duplication
     *
     * Accessibility Features:
     * - aria-label for screen reader button identification
     * - aria-controls linking button to menu container
     * - aria-expanded state reflecting menu visibility
     * - Keyboard navigation support through menu component
     *
     * @param string $themeLocation WordPress theme location identifier for the menu
     * @param array<string, mixed> $options Optional configuration overrides for button and menu
     * @param array<string, mixed> $walkerOptions Optional walker-specific configuration (passed to menu)
     *
     * @return string Complete HTML markup for the integrated button and menu system
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // Basic usage with default styling
     * $component = new OverlayMobileWithToggle();
     * echo $component->render('primary');
     *
     * // Advanced usage with custom button and menu options
     * echo $component->render('primary', [
     *     'button_class' => 'custom-hamburger-btn',
     *     'button_label' => 'تغییر وضعیت منو',
     *     'menu_options' => [
     *         'max_depth' => 2,
     *         'accordion_mode' => 'exclusive',
     *         'enable_icons' => true
     *     ]
     * ]);
     *
     * // Integration with custom CSS classes
     * echo $component->render('mobile-nav', [
     *     'button_id' => 'main-nav-toggle',
     *     'menu_options' => [
     *         'container_class' => 'main-mobile-overlay',
     *         'mobile_link_class' => 'nav-link-mobile'
     *     ]
     * ]);
     * ```
     */
    public function render(string $themeLocation, array $options = [], array $walkerOptions = []): string
    {
        // Merge provided options with component defaults
        $opts = $this->makeOptions($options);

        // Generate hamburger toggle button with Alpine.js state bindings
        // The button includes:
        // - Three spans for hamburger icon animation
        // - Alpine.js bindings for aria-expanded and active class states
        // - Click handler for menu toggle functionality
        // - ARIA attributes for accessibility compliance
        $button = sprintf(
            '<button id="%s" class="%s" aria-label="%s" aria-controls="mobile-menu" x-bind:aria-expanded="mobileMenuOpen ? \'true\' : \'false\'" x-bind:class="{ \'active\': mobileMenuOpen }" @click.stop.prevent="mobileMenuOpen = !mobileMenuOpen"><span></span><span></span><span></span></button>',
            esc_attr((string) $opts->get('button_id')),
            esc_attr((string) $opts->get('button_class')),
            esc_attr((string) $opts->get('button_label'))
        );

        // Render the overlay menu through MenuManager
        // Critical: Set 'provide_state' to false to prevent duplicate Alpine.js x-data
        // This ensures the menu uses the state scope provided by this component
        $menuHtml = MenuManager::render(
            'overlay-mobile',
            $themeLocation,
            array_merge(
                (array) $opts->get('menu_options'),
                ['provide_state' => false] // Prevents duplicate state management
            )
        );

        // Wrap button and menu in unified container with shared Alpine.js state
        // The container provides:
        // - Unified x-data scope for state management
        // - Data attribute for CSS targeting
        // - Semantic grouping of related components
        return sprintf(
            '<div class="wphelpers-overlay-mobile-nav" x-data="{ mobileMenuOpen:false, opens:{} }" data-overlay-nav>%s%s</div>',
            $button,
            $menuHtml
        );
    }
}