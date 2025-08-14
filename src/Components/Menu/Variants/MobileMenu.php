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
use jamal13647850\wphelpers\Navigation\MobileAccordionWalker;

/**
 * MobileMenu
 *
 * Renders a mobile navigation menu using an accordion behavior powered by
 * {@see MobileAccordionWalker}. Provides sensible defaults, forwards walker
 * options, and injects a global Alpine.js accordion state on the items wrapper.
 *
 * ## Notes
 * - `aria_label` is kept in Persian (fa-IR) by default.
 * - Wrapper `<ul>` includes `x-data="{ activeMenu: null }"` to coordinate root-level toggling.
 *
 * @final
 */
final class MobileMenu extends AbstractMenu
{
    /**
     * Default configuration for this mobile menu variant.
     *
     * @return array{
     *   menu_id:string,
     *   menu_class:string,
     *   aria_label:string,
     *   items_wrap:string,
     *   echo:bool,
     *   fallback_cb:bool,
     *   mobile_link_class:string,
     *   mobile_link_no_children_class:string,
     *   mobile_button_class:string,
     *   mobile_svg_default_fill:string,
     *   max_depth:int
     * }
     */
    protected static function defaultOptions(): array
    {
        return [
            'menu_id'        => 'primary-menu-mobile',
            'menu_class'     => 'space-y-2',
            // UI label kept in Persian (fa-IR) per preference.
            'aria_label'     => 'ناوبری موبایل',
            // Placeholders; will be overridden in render() to include localized aria-label.
            'items_wrap'     => '<ul id="%1$s" class="%2$s" x-data="{ activeMenu: null }" aria-label="%s">%3$s</ul>',
            'echo'           => false,
            'fallback_cb'    => false,
            // MobileAccordionWalker-specific options:
            'mobile_link_class'             => 'flex-1 py-3 text-secondary hover:text-primary transition-colors text-sm sm:text-base',
            'mobile_link_no_children_class' => 'block py-3 text-secondary hover:text-primary transition-colors text-sm sm:text-base',
            'mobile_button_class'           => 'p-2 text-dark hover:text-primary transition-colors',
            'mobile_svg_default_fill'       => '#79528A',
            'max_depth'                     => 3,
        ];
    }

    /**
     * Render the mobile accordion menu.
     *
     * Prepares `wp_nav_menu` arguments, wires in {@see MobileAccordionWalker},
     * and returns the generated HTML. Injects the global Alpine.js state
     * (`{ activeMenu: null }`) on the items wrapper and localizes the `aria-label`.
     *
     * @param string               $themeLocation Menu theme location slug (e.g., "mobile").
     * @param array<string, mixed> $options       Optional menu options to merge with defaults.
     * @param array<string, mixed> $walkerOptions Optional walker overrides (merged with derived).
     * @return string The menu HTML returned by `wp_nav_menu`.
     *
     * @example
     * ```php
     * $menu = new MobileMenu();
     * echo $menu->render('mobile', [
     *     'menu_class' => 'divide-y divide-gray-100',
     * ]);
     * ```
     */
    public function render(
        string $themeLocation,
        array $options = [],
        array $walkerOptions = []
    ): string {
        $opts = $this->makeOptions($options);

        // Walker options tailored for MobileAccordionWalker.
        $walkerVariantOptions = [
            'mobile_link_class'             => (string) $opts->get('mobile_link_class'),
            'mobile_link_no_children_class' => (string) $opts->get('mobile_link_no_children_class'),
            'mobile_button_class'           => (string) $opts->get('mobile_button_class'),
            'mobile_svg_default_fill'       => (string) $opts->get('mobile_svg_default_fill'),
            'max_depth'                     => (int) $opts->get('max_depth'),
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

        // Use the specialized MobileAccordionWalker.
        $args['walker'] = new MobileAccordionWalker(
            array_merge($walkerVariantOptions, $walkerOptions)
        );

        // Localize aria-label and inject Alpine.js global state on items wrapper.
        $aria_label = esc_attr__(
            $opts->get('aria_label'),
            'your-theme-textdomain'
        );

        // Items wrapper with localized (fa-IR) aria-label and activeMenu state.
        $args['items_wrap'] = '<ul x-data="{ activeMenu: null }" aria-label="' . $aria_label . '" id="%1$s" class="%2$s">%3$s</ul>';

        return (string) wp_nav_menu($args);
    }
}
