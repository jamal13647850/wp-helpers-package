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
 * DropdownMenu
 *
 * Dropdown menu variant (for site-wide or context menus).
 * Provides a simple unordered list structure for dropdown navigation.
 *
 * Usage:
 *   $menu = new DropdownMenu();
 *   echo $menu->render('dropdown');
 */
final class DropdownMenu extends AbstractMenu
{
    /**
     * Get the default options for this menu variant.
     *
     * @return array
     *
     * Defaults:
     *   - 'menu_id'      (string)  Unique DOM id for the menu.
     *   - 'menu_class'   (string)  Classes applied to the root <ul>.
     *   - 'items_wrap'   (string)  Markup template for the items container.
     *   - 'echo'         (bool)    Whether to echo or return HTML.
     *   - 'fallback_cb'  (bool)    Disable fallback if menu not assigned.
     */
    protected static function defaultOptions(): array
    {
        return [
            'menu_id'     => 'dropdown-menu',
            'menu_class'  => 'flex list-none m-0 p-0',
            'items_wrap'  => '<ul id="%1$s" class="%2$s">%3$s</ul>',
            'echo'        => false,
            'fallback_cb' => false,
        ];
    }

    /**
     * Render the dropdown menu.
     *
     * @param string $themeLocation   The WordPress theme menu location.
     * @param array  $options         Variant-specific menu options (optional).
     * @param array  $walkerOptions   Options for the walker (optional).
     *
     * @return string                 The rendered menu HTML.
     *
     * @example
     *   echo (new DropdownMenu())->render('dropdown-menu');
     */
    public function render(
        string $themeLocation,
        array $options = [],
        array $walkerOptions = []
    ): string {
        $opts = $this->makeOptions($options);
        $args = $opts->toArray();
        $args['theme_location'] = $themeLocation;
        $args['walker'] = $this->makeWalker('dropdown', $walkerOptions);

        /** @psalm-suppress UndefinedFunction */
        return (string) wp_nav_menu($args);
    }
}
