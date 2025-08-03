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
 * DesktopMenu
 *
 * Desktop menu variant with mega-menu support.
 * Provides tailored markup, attributes, and walker for desktop navigation.
 *
 * Usage:
 *   $menu = new DesktopMenu();
 *   echo $menu->render('primary');
 */
final class DesktopMenu extends AbstractMenu
{
    /**
     * Get the default options for this menu variant.
     *
     * @return array
     *
     * Defaults:
     *   - 'menu_id'      (string)  Unique DOM id for the menu.
     *   - 'menu_class'   (string)  Classes applied to the root <ul>.
     *   - 'aria_label'   (string)  Accessible label for nav element (in fa-IR).
     *   - 'echo'         (bool)    Whether to echo or return HTML (always returns in this class).
     *   - 'fallback_cb'  (bool)    Disable fallback if menu not assigned.
     */
    protected static function defaultOptions(): array
    {
        return [
            'menu_id'        => 'primary-menu-desktop',
            'menu_class'     => 'flex items-center space-x-2 relative',
            'aria_label'     => 'ناوبری اصلی', // Persian (fa-IR) for "Main Navigation"
            'echo'           => false,
            'fallback_cb'    => false,
        ];
    }

    /**
     * Render the desktop menu.
     *
     * @param string $themeLocation   The WordPress theme menu location.
     * @param array  $options         Variant-specific menu options (optional).
     * @param array  $walkerOptions   Options for the walker (optional).
     *
     * @return string                 The rendered menu HTML.
     *
     * @example
     *   echo (new DesktopMenu())->render('main-menu');
     */
    public function render(
        string $themeLocation,
        array $options = [],
        array $walkerOptions = []
    ): string {
        $opts = $this->makeOptions($options);
        $args = $opts->toArray();
        $args['theme_location'] = $themeLocation;
        $args['walker'] = $this->makeWalker('desktop', $walkerOptions);

        // Compose the items_wrap with Persian aria-label (already translated)
        $args['items_wrap'] = sprintf(
            '<ul id="%%1$s" class="%%2$s" aria-label="%s">%%3$s</ul>',
            esc_attr__($opts->get('aria_label'), 'your-theme-textdomain')
        );

        /** @psalm-suppress UndefinedFunction */
        return (string) wp_nav_menu($args);
    }
}
