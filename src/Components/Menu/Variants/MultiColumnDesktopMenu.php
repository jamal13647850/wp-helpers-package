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
use jamal13647850\wphelpers\Navigation\MultiColumnDesktopWalker;

/**
 * MultiColumnDesktopMenu
 *
 * Renders a desktop navigation menu with a two-level, multi-column dropdown,
 * powered by {@see MultiColumnDesktopWalker}. This variant exposes sensible
 * defaults and forwards walker-specific options in a safe, typed manner.
 *
 * ## Usage
 * ```php
 * echo (new MultiColumnDesktopMenu())->render('primary', [
 *     'dropdown_columns' => 3,
 * ]);
 * ```
 *
 * ## Notes
 * - Only depth 0 and 1 are used by the underlying walker.
 * - UI string `aria_label` is Persian (fa-IR) by default.
 *
 * @final
 */
final class MultiColumnDesktopMenu extends AbstractMenu
{
    /**
     * Default configuration for this menu variant.
     *
     * @return array{
     *   menu_id:string,
     *   menu_class:string,
     *   aria_label:string,
     *   dropdown_columns:int,
     *   enable_icons:bool,
     *   dropdown_trigger_class:string,
     *   dropdown_link_class:string,
     *   dropdown_arrow_class:string,
     *   echo:bool,
     *   fallback_cb:bool
     * }
     */
    protected static function defaultOptions(): array
    {
        return [
            'menu_id'                => 'multi-column-desktop-menu',
            'menu_class'             => 'nav-menu',
            // UI label kept in Persian (fa-IR) per preference.
            'aria_label'             => 'منوی اصلی',
            'dropdown_columns'       => 2,
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
     * Builds `wp_nav_menu` arguments, wires in {@see MultiColumnDesktopWalker},
     * and returns the generated HTML string.
     *
     * @param string $themeLocation Menu theme location slug (e.g., "primary").
     * @param array<string, mixed> $options       Optional menu options to merge with defaults.
     * @param array<string, mixed> $walkerOptions Optional walker overrides (merged with derived).
     * @return string The menu HTML returned by `wp_nav_menu`.
     *
     * @example
     * ```php
     * $menu = new MultiColumnDesktopMenu();
     * echo $menu->render('primary', [
     *     'dropdown_columns' => 3,
     *     'enable_icons'     => false,
     * ]);
     * ```
     */
    public function render(
        string $themeLocation,
        array $options = [],
        array $walkerOptions = []
    ): string {
        $opts = $this->makeOptions($options);

        // Walker options tailored for MultiColumnDesktopWalker.
        $walkerVariantOptions = [
            'dropdown_columns'       => (int) $opts->get('dropdown_columns'),
            'enable_icons'           => (bool) $opts->get('enable_icons'),
            'dropdown_trigger_class' => (string) $opts->get('dropdown_trigger_class'),
            'dropdown_link_class'    => (string) $opts->get('dropdown_link_class'),
            'dropdown_arrow_class'   => (string) $opts->get('dropdown_arrow_class'),
        ];

        // Base wp_nav_menu args.
        $args = [
            'theme_location' => $themeLocation,
            'menu_id'        => (string) $opts->get('menu_id'),
            'menu_class'     => (string) $opts->get('menu_class'),
            'container'      => false,
            'fallback_cb'    => (bool) $opts->get('fallback_cb'),
            'echo'           => (bool) $opts->get('echo'),
        ];

        // Use the specialized MultiColumnDesktopWalker.
        $args['walker'] = new MultiColumnDesktopWalker(
            array_merge($walkerVariantOptions, $walkerOptions)
        );

        // Items wrapper with localized (fa-IR) aria-label.
        $args['items_wrap'] = sprintf(
            '<ul id="%%1$s" class="%%2$s" aria-label="%s">%%3$s</ul>',
            esc_attr__($opts->get('aria_label'), 'your-theme-textdomain')
        );

        return (string) wp_nav_menu($args);
    }

    // Note: makeWalker method intentionally omitted; using MultiColumnDesktopWalker directly
    // avoids return type conflicts with the parent class.
}
