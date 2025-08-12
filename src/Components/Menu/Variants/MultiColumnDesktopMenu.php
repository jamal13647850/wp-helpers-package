<?php
declare(strict_types=1);

namespace jamal13647850\wphelpers\Components\Menu\Variants;

use jamal13647850\wphelpers\Components\Menu\AbstractMenu;
use jamal13647850\wphelpers\Components\Menu\Options\MenuOptions;

// ===== IMPLEMENTATION =====

/**
 * File: MultiColumnDesktopMenu.php
 * Variant: Multi-column desktop dropdown (2..3 columns)
 *
 * Renders desktop submenu as evenly distributed columns under:
 * .dropdown-menu > .dropdown-content > .dropdown-columns > .dropdown-column > a.dropdown-link
 */
final class MultiColumnDesktopMenu extends AbstractMenu
{
    /**
     * Default options for this variant.
     *
     * @return array<string,mixed>
     */
    protected static function defaultOptions(): array
    {
        return [
            'menu_id'                => 'multi-column-desktop-menu',
            'menu_class'             => 'nav-menu',
            'aria_label'             => 'منوی اصلی',
            'dropdown_columns'       => 2, // 2 or 3
            'enable_icons'           => true,
            'dropdown_trigger_class' => 'nav-link dropdown-trigger',
            'dropdown_link_class'    => 'dropdown-link',
            'dropdown_arrow_class'   => 'dropdown-arrow fas fa-chevron-down',
            'echo'                   => false,
            'fallback_cb'            => false,
        ];
    }

    /**
     * Render the multi-column desktop menu.
     *
     * @param string               $themeLocation
     * @param array<string,mixed>  $options
     * @param array<string,mixed>  $walkerOptions
     * @return string
     */
    public  function render(
        string $themeLocation,
        array $options = [],
        array $walkerOptions = []
    ): string {
        $opts = $this->makeOptions($options);

        $walkerVariantOptions = [
            'dropdown_columns'       => (int) $opts->get('dropdown_columns'),
            'enable_icons'           => (bool) $opts->get('enable_icons'),
            'dropdown_trigger_class' => (string) $opts->get('dropdown_trigger_class'),
            'dropdown_link_class'    => (string) $opts->get('dropdown_link_class'),
            'dropdown_arrow_class'   => (string) $opts->get('dropdown_arrow_class'),
        ];

        $args = [
            'theme_location' => $themeLocation,
            'menu_id'        => (string) $opts->get('menu_id'),
            'menu_class'     => (string) $opts->get('menu_class'),
            'container'      => false,
            'fallback_cb'    => (bool) $opts->get('fallback_cb'),
            'echo'           => (bool) $opts->get('echo'),
        ];
        $args['walker'] = $this->makeWalker('multi-column-desktop', $walkerOptions);
        $args['items_wrap'] = sprintf(
            '<ul id="%%1$s" class="%%2$s" aria-label="%s">%%3$s</ul>',
            esc_attr__($opts->get('aria_label'), 'wp-helpers')
        );

        /** @psalm-suppress UndefinedFunction */
        return (string) wp_nav_menu($args);
    }
}

/**
 * Backward compatibility: keep old class name working.
 */
final class TwoColumnDesktopMenu extends MultiColumnDesktopMenu
{
}
