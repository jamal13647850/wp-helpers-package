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

/**
 * OverlayMobileMenu
 *
 * Mobile menu variant with full-screen overlay and accordion support.
 * Features smooth slide-in animations and hierarchical navigation.
 *
 * Features:
 * - Full-screen overlay
 * - Accordion sub-menus
 * - Alpine.js state management
 * - RTL support with smooth animations
 * - Customizable animation duration
 *
 * Usage:
 *   $menu = new OverlayMobileMenu();
 *   echo $menu->render('primary');
 */
final class OverlayMobileMenu extends AbstractMenu
{
    /**
     * Get the default options for this menu variant.
     *
     * @return array
     *
     * Defaults:
     *   - 'menu_id'              (string)  Unique DOM id for the menu.
     *   - 'menu_class'           (string)  Classes for the root <ul>.
     *   - 'aria_label'           (string)  Accessible label for nav element (in fa-IR).
     *   - 'overlay_class'        (string)  Classes for overlay container.
     *   - 'enable_accordion'     (bool)    Enable accordion functionality.
     *   - 'animation_duration'   (int)     Animation duration in milliseconds.
     *   - 'echo'                 (bool)    Whether to echo or return HTML.
     *   - 'fallback_cb'          (bool)    Disable fallback if menu not assigned.
     */
    protected static function defaultOptions(): array
    {
        return [
            'menu_id'              => 'overlay-mobile-menu',
            'menu_class'           => 'mobile-nav-menu space-y-2 p-6',
            'aria_label'           => 'منوی موبایل', // Persian (fa-IR) for "Mobile Menu"
            'overlay_class'        => 'mobile-menu-overlay fixed inset-0 bg-card-background z-50 transform translate-x-full transition-transform duration-300 ease-in-out',
            'header_class'         => 'flex justify-between items-center p-6 border-b border-border',
            'close_button_class'   => 'text-foreground hover:text-primary transition-colors',
            'enable_accordion'     => true,
            'animation_duration'   => 300,
            'mobile_link_class'    => 'mobile-menu-link block py-3 px-5 text-foreground hover:text-primary transition-colors font-medium',
            'mobile_submenu_class' => 'mobile-submenu-link block py-2 px-8 text-text-muted hover:text-primary transition-colors',
            'echo'                 => false,
            'fallback_cb'          => false,
        ];
    }

    /**
     * Render the overlay mobile menu.
     *
     * @param string $themeLocation   The WordPress theme menu location.
     * @param array  $options         Variant-specific menu options (optional).
     * @param array  $walkerOptions   Options for the walker (optional).
     *
     * @return string                 The rendered menu HTML.
     *
     * @example
     *   echo (new OverlayMobileMenu())->render('primary');
     */
    public function render(
        string $themeLocation,
        array $options = [],
        array $walkerOptions = []
    ): string {
        $opts = $this->makeOptions($options);
        $args = $opts->toArray();
        $args['theme_location'] = $themeLocation;
        
        // Use custom walker for overlay mobile design
        $args['walker'] = $this->makeWalker('overlay-mobile', array_merge([
            'enable_accordion' => $opts->get('enable_accordion'),
            'animation_duration' => $opts->get('animation_duration'),
            'mobile_link_class' => $opts->get('mobile_link_class'),
            'mobile_submenu_class' => $opts->get('mobile_submenu_class'),
        ], $walkerOptions));

        // Create overlay wrapper with menu
        $aria_label = esc_attr__($opts->get('aria_label'), 'wp-helpers');
        $overlay_class = $opts->get('overlay_class');
        $header_class = $opts->get('header_class');
        $close_button_class = $opts->get('close_button_class');
        
        $menu_html = wp_nav_menu(array_merge($args, [
            'items_wrap' => '<ul id="%1$s" class="%2$s" x-data="{ activeMenu: null }" aria-label="' . $aria_label . '">%3$s</ul>',
            'echo' => false
        ]));

        // Wrap in mobile overlay container
        return sprintf(
            '<div class="%s" x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" @click.outside="mobileMenuOpen = false">
                <div class="%s">
                    <h2 class="text-xl font-bold text-foreground">منو</h2>
                    <button @click="mobileMenuOpen = false" class="%s" aria-label="بستن منو">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                %s
            </div>',
            $overlay_class,
            $header_class,
            $close_button_class,
            $menu_html
        );
    }
}