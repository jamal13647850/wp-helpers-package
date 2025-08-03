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
 * SimpleMenu
 *
 * Simple horizontal menu variant (usually displayed at the top of the site).
 * Provides streamlined markup for a top navigation bar.
 *
 * Usage:
 *   $menu = new SimpleMenu();
 *   echo $menu->render('top');
 */
final class SimpleMenu extends AbstractMenu
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
     *   - 'items_wrap'   (string)  Markup template for the items container.
     *   - 'echo'         (bool)    Whether to echo or return HTML.
     *   - 'fallback_cb'  (bool)    Disable fallback if menu not assigned.
     */
    protected static function defaultOptions(): array
    {
        return [
            'menu_id'     => 'top-menu',
            'menu_class'  => 'flex items-center space-x-1 sm:space-x-2 lg:space-x-4',
            'aria_label'  => 'ناوبری بالایی', // Persian (fa-IR) for "Top Navigation"
            'items_wrap'  => '<ul id="%1$s" class="%2$s" aria-label="%s">%3$s</ul>',
            'echo'        => false,
            'fallback_cb' => false,
        ];
    }

    /**
     * Render the simple (horizontal) menu.
     *
     * @param string $themeLocation   The WordPress theme menu location.
     * @param array  $options         Variant-specific menu options (optional).
     * @param array  $walkerOptions   Options for the walker (optional).
     *
     * @return string                 The rendered menu HTML.
     *
     * @example
     *   echo (new SimpleMenu())->render('top-menu');
     */
    public function render(
        string $themeLocation,
        array $options = [],
        array $walkerOptions = []
    ): string {
        $opts = $this->makeOptions($options);
        $args = $opts->toArray();
        $args['theme_location'] = $themeLocation;
        $args['walker'] = $this->makeWalker('simple', $walkerOptions);

        // Compose the items_wrap with Persian aria-label (already translated)
        $args['items_wrap'] = sprintf(
            $opts->get('items_wrap'),
            esc_attr__($opts->get('aria_label'), 'your-theme-textdomain')
        );

        /** @psalm-suppress UndefinedFunction */
        return (string) wp_nav_menu($args);
    }
}
