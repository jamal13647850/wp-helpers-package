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
 * TwoColumnDesktopMenu
 *
 * Desktop menu variant with two-column dropdown mega-menu support.
 * Features icon + text layout with hover-based dropdown functionality.
 *
 * Features:
 * - Two-column dropdown layout
 * - Icon + text menu items support
 * - Hover-based mega menu
 * - RTL support
 * - Customizable column count
 *
 * Usage:
 *   $menu = new TwoColumnDesktopMenu();
 *   echo $menu->render('primary');
 */
final class TwoColumnDesktopMenu extends AbstractMenu
{
    /**
     * Get the default options for this menu variant.
     *
     * @return array
     *
     * Defaults:
     *   - 'menu_id'              (string)  Unique DOM id for the menu.
     *   - 'menu_class'           (string)  Classes applied to the root <ul>.
     *   - 'aria_label'           (string)  Accessible label for nav element (in fa-IR).
     *   - 'dropdown_columns'     (int)     Number of columns in dropdown.
     *   - 'enable_icons'         (bool)    Enable Font Awesome icons in dropdown.
     *   - 'dropdown_trigger_class' (string) Classes for dropdown trigger links.
     *   - 'echo'                 (bool)    Whether to echo or return HTML.
     *   - 'fallback_cb'          (bool)    Disable fallback if menu not assigned.
     */
    protected static function defaultOptions(): array
    {
        return [
            'menu_id'                => 'two-column-desktop-menu',
            'menu_class'             => 'nav-menu flex items-center gap-8',
            'aria_label'             => 'منوی اصلی', // Persian (fa-IR) for "Main Menu"
            'dropdown_columns'       => 2,
            'enable_icons'           => true,
            'dropdown_trigger_class' => 'nav-link dropdown-trigger flex items-center gap-2 py-6 text-foreground hover:text-primary transition-colors font-medium',
            'dropdown_link_class'    => 'dropdown-link flex items-center gap-3 px-6 py-3 text-foreground hover:text-primary transition-colors',
            'dropdown_arrow_class'   => 'dropdown-arrow fas fa-chevron-down text-xs text-primary transition-transform duration-300',
            'echo'                   => false,
            'fallback_cb'            => false,
        ];
    }

    /**
     * Render the two-column desktop menu.
     *
     * @param string $themeLocation   The WordPress theme menu location.
     * @param array  $options         Variant-specific menu options (optional).
     * @param array  $walkerOptions   Options for the walker (optional).
     *
     * @return string                 The rendered menu HTML.
     *
     * @example
     *   echo (new TwoColumnDesktopMenu())->render('primary');
     */
    public function render(
        string $themeLocation,
        array $options = [],
        array $walkerOptions = []
    ): string {
        $opts = $this->makeOptions($options);
        $args = $opts->toArray();
        $args['theme_location'] = $themeLocation;
        
        // Use custom walker for two-column design
        $args['walker'] = $this->makeWalker('two-column-desktop', array_merge([
            'dropdown_columns' => $opts->get('dropdown_columns'),
            'enable_icons' => $opts->get('enable_icons'),
            'dropdown_trigger_class' => $opts->get('dropdown_trigger_class'),
            'dropdown_link_class' => $opts->get('dropdown_link_class'),
            'dropdown_arrow_class' => $opts->get('dropdown_arrow_class'),
        ], $walkerOptions));

        // Compose the items_wrap with Persian aria-label
        $args['items_wrap'] = sprintf(
            '<ul id="%%1$s" class="%%2$s" aria-label="%s">%%3$s</ul>',
            esc_attr__($opts->get('aria_label'), 'wp-helpers')
        );

        /** @psalm-suppress UndefinedFunction */
        return (string) wp_nav_menu($args);
    }
}