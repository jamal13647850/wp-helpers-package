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
use jamal13647850\wphelpers\Navigation\DesktopMegaMenuWalker;

/**
 * DesktopMenu
 *
 * Renders a desktop navigation menu featuring a mega menu, powered by
 * {@see DesktopMegaMenuWalker}. Exposes sensible defaults and forwards
 * walker-specific options safely.
 *
 * ## Notes
 * - `aria_label` is localized in Persian (fa-IR) by default.
 * - The walker expects depth 0 (root), depth 1 (section headers),
 *   and depth 2 (children) for mega menu rendering.
 *
 * @final
 */
final class DesktopMenu extends AbstractMenu
{
    /**
     * Default configuration for the desktop mega menu variant.
     *
     * @return array{
     *   menu_id:string,
     *   menu_class:string,
     *   aria_label:string,
     *   echo:bool,
     *   fallback_cb:bool,
     *   desktop_link_class:string,
     *   desktop_link_hover_color:string,
     *   desktop_svg_default_fill:string,
     *   desktop_svg_hover_fill:string,
     *   mega_menu_parent_title_class:string,
     *   mega_menu_child_link_class:string
     * }
     */
    protected static function defaultOptions(): array
    {
        return [
            'menu_id'        => 'primary-menu-desktop',
            'menu_class'     => 'flex items-center space-x-2 relative',
            // UI label kept in Persian (fa-IR) per preference.
            'aria_label'     => 'ناوبری اصلی',
            'echo'           => false,
            'fallback_cb'    => false,
            // DesktopMegaMenuWalker-specific options:
            'desktop_link_class'           => 'flex items-center gap-1 py-4 px-1 text-gray-800 hover:text-primary transition-colors text-nowrap text-sm lg:text-xs xl:text-sm',
            'desktop_link_hover_color'     => '#F25A04',
            'desktop_svg_default_fill'     => '#79528A',
            'desktop_svg_hover_fill'       => '#F25A04',
            'mega_menu_parent_title_class' => 'hover:text-primary transition-colors duration-300',
            'mega_menu_child_link_class'   => 'text-gray-700 hover:text-primary transition-colors duration-300 block py-1 text-sm hover:bg-gray-50 px-2 rounded',
        ];
    }

    /**
     * Render the desktop menu with mega menu support.
     *
     * Builds `wp_nav_menu` arguments, injects {@see DesktopMegaMenuWalker},
     * and returns the generated HTML string.
     *
     * @param string               $themeLocation  Menu theme location slug (e.g., "primary").
     * @param array<string, mixed> $options        Optional menu options to merge with defaults.
     * @param array<string, mixed> $walkerOptions  Optional walker overrides (merged with derived).
     * @return string The menu HTML returned by `wp_nav_menu`.
     *
     * @example
     * ```php
     * $menu = new DesktopMenu();
     * echo $menu->render('primary', [
     *     'menu_class' => 'flex items-center gap-4',
     * ]);
     * ```
     */
    public function render(
        string $themeLocation,
        array $options = [],
        array $walkerOptions = []
    ): string {
        $opts = $this->makeOptions($options);

        // Walker options tailored for DesktopMegaMenuWalker.
        $walkerVariantOptions = [
            'desktop_link_class'           => (string) $opts->get('desktop_link_class'),
            'desktop_link_hover_color'     => (string) $opts->get('desktop_link_hover_color'),
            'desktop_svg_default_fill'     => (string) $opts->get('desktop_svg_default_fill'),
            'desktop_svg_hover_fill'       => (string) $opts->get('desktop_svg_hover_fill'),
            'mega_menu_parent_title_class' => (string) $opts->get('mega_menu_parent_title_class'),
            'mega_menu_child_link_class'   => (string) $opts->get('mega_menu_child_link_class'),
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

        // Use the specialized DesktopMegaMenuWalker.
        $args['walker'] = new DesktopMegaMenuWalker(
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
