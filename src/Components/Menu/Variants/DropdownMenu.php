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
use jamal13647850\wphelpers\Navigation\DropdownWalker;

/**
 * DropdownMenu
 *
 * Renders a vertical dropdown menu powered by {@see DropdownWalker}.
 * Provides sensible defaults and forwards depth-aware link classes to the walker.
 *
 * ## Usage
 * ```php
 * echo (new DropdownMenu())->render('primary', [
 *     'menu_class' => 'flex gap-6',
 * ]);
 * ```
 *
 * ## Notes
 * - UI string `aria_label` is Persian (fa-IR) by default.
 * - `items_wrap` is overridden to include the localized `aria-label`.
 *
 * @final
 */
final class DropdownMenu extends AbstractMenu
{
    /**
     * Default configuration for this dropdown menu variant.
     *
     * @return array{
     *   menu_id:string,
     *   menu_class:string,
     *   aria_label:string,
     *   items_wrap:string,
     *   echo:bool,
     *   fallback_cb:bool,
     *   dropdown_root_link_class:string,
     *   dropdown_child_link_class:string,
     *   dropdown_subchild_link_class:string
     * }
     */
    protected static function defaultOptions(): array
    {
        return [
            'menu_id'     => 'dropdown-menu',
            'menu_class'  => 'flex list-none m-0 p-0',
            // UI label kept in Persian (fa-IR) per preference.
            'aria_label'  => 'منوی کشویی',
            'items_wrap'  => '<ul id="%1$s" class="%2$s">%3$s</ul>',
            'echo'        => false,
            'fallback_cb' => false,
            // DropdownWalker-specific classes (depth-aware):
            'dropdown_root_link_class'     => 'block text-[#333] text-[16px] font-medium transition-colors duration-300 px-[22px] pt-[18px] pb-4 border-b-2 border-transparent hover:text-[#d32f2f] hover:border-[#d32f2f]',
            'dropdown_child_link_class'    => 'relative block pr-[36px] pl-6 py-3 text-[#333] text-[15px] whitespace-nowrap transition-all duration-300 hover:bg-[#f5f5f5] hover:text-[#d32f2f] font-normal',
            'dropdown_subchild_link_class' => 'block px-6 py-3 text-[15px] text-[#333] transition-colors duration-300 hover:text-[#d32f2f] whitespace-nowrap',
        ];
    }

    /**
     * Render the dropdown menu.
     *
     * Prepares `wp_nav_menu` arguments, wires in {@see DropdownWalker}, and returns
     * the generated HTML. The `aria-label` is localized (fa-IR) using `esc_attr__`.
     *
     * @param string               $themeLocation  Menu theme location slug (e.g., "primary").
     * @param array<string, mixed> $options        Optional menu options to merge with defaults.
     * @param array<string, mixed> $walkerOptions  Optional walker overrides (merged with derived).
     * @return string The menu HTML returned by `wp_nav_menu`.
     *
     * @example
     * ```php
     * $menu = new DropdownMenu();
     * echo $menu->render('sidebar', [
     *     'menu_class' => 'flex flex-col',
     * ]);
     * ```
     */
    public function render(
        string $themeLocation,
        array $options = [],
        array $walkerOptions = []
    ): string {
        $opts = $this->makeOptions($options);

        // Walker options tailored for DropdownWalker (depth-aware link classes).
        $walkerVariantOptions = [
            'dropdown_root_link_class'     => (string) $opts->get('dropdown_root_link_class'),
            'dropdown_child_link_class'    => (string) $opts->get('dropdown_child_link_class'),
            'dropdown_subchild_link_class' => (string) $opts->get('dropdown_subchild_link_class'),
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

        // Use the specialized DropdownWalker.
        $args['walker'] = new DropdownWalker(
            array_merge($walkerVariantOptions, $walkerOptions)
        );

        // Items wrapper with localized (fa-IR) aria-label.
        $args['items_wrap'] = sprintf(
            '<ul id="%%1$s" class="%%2$s" aria-label="%s">%%3$s</ul>',
            esc_attr__($opts->get('aria_label'), 'your-theme-textdomain')
        );

        return (string) wp_nav_menu($args);
    }
}
