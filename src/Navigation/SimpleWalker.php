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

namespace jamal13647850\wphelpers\Navigation;

use Walker_Nav_Menu;

defined('ABSPATH') || exit();

/**
 * SimpleWalker
 *
 * A specialized WordPress walker for simple, horizontal menus with **no dropdowns**.
 * Separated from AlpineNavWalker to improve performance and readability.
 *
 * ## Features
 * - Renders only single-level menu items.
 * - Applies a configurable CSS class to links via `$options['simple_link_class']`.
 * - Skips submenu levels entirely (`start_lvl`/`end_lvl` are no-ops).
 *
 * ## Usage
 * ```php
 * wp_nav_menu([
 *     'theme_location' => 'primary',
 *     'walker'         => new \jamal13647850\wphelpers\Navigation\SimpleWalker([
 *         'simple_link_class' => 'text-secondary hover:text-primary',
 *     ]),
 * ]);
 * ```
 *
 * @since 1.0.0
 * @final
 */
final class SimpleWalker extends Walker_Nav_Menu
{
    /**
     * Walker options (CSS classes etc.).
     *
     * @var array{simple_link_class:string}
     */
    private array $options = [
        'simple_link_class' => 'text-secondary hover:text-primary transition-colors text-nowrap',
    ];

    /**
     * Constructor.
     *
     * @param array<string, mixed> $options Custom walker options to merge with defaults.
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Start rendering a single menu item (<li> with anchor).
     *
     * @param string       $output HTML output (by reference).
     * @param object       $item   Menu item object (WP_Post-like).
     * @param int          $depth  Menu level (0 = root). Submenus are ignored by this walker.
     * @param array|object $args   Arguments passed to `wp_nav_menu` (may be object or array).
     * @param int          $id     Item ID (unused; provided via $item->ID).
     * @return void
     */
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0): void
    {
        $indent  = ($depth) ? str_repeat("\t", $depth) : '';
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;

        /** @var string $class_names */
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        /** @var string|null $id_attr */
        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        $output .= $indent . '<li' . $id_attr . $class_names . '>';

        // Build anchor attributes.
        $atts = [
            'title'  => !empty($item->attr_title) ? $item->attr_title : '',
            'target' => !empty($item->target) ? $item->target : '',
            'rel'    => !empty($item->xfn) ? $item->xfn : '',
            'href'   => !empty($item->url) ? $item->url : '',
        ];

        if ($item->current || $item->current_item_ancestor || $item->current_item_parent) {
            $atts['aria-current'] = 'page';
        }

        /** @var array<string, string> $atts */
        $atts       = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);
        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value       = ($attr === 'href') ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        // Render simple link with configured class.
        $item_output  = $args->before ?? '';
        $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['simple_link_class']) . '">';
        $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
        $item_output .= '</a>';
        $item_output .= $args->after ?? '';

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Start a submenu level.
     *
     * No-op for SimpleWalker (submenus are not rendered).
     *
     * @param string       $output HTML output (by reference).
     * @param int          $depth  Current depth.
     * @param array|object $args   Rendering args (unused).
     * @return void
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void
    {
        return; // Intentionally not rendering submenus.
    }

    /**
     * End a submenu level.
     *
     * No-op for SimpleWalker (submenus are not rendered).
     *
     * @param string       $output HTML output (by reference).
     * @param int          $depth  Current depth.
     * @param array|object $args   Rendering args (unused).
     * @return void
     */
    public function end_lvl(&$output, $depth = 0, $args = []): void
    {
        return; // Intentionally not rendering submenus.
    }

    /**
     * End a single menu item.
     *
     * @param string       $output HTML output (by reference).
     * @param object       $item   Menu item.
     * @param int          $depth  Current depth.
     * @param array|object $args   Rendering args (unused).
     * @return void
     */
    public function end_el(&$output, $item, $depth = 0, $args = []): void
    {
        $output .= "</li>\n";
    }
}
