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
 * MobileAccordionWalker
 *
 * A specialized WordPress walker for mobile accordion menus (Alpine.js-driven).
 * Separated from AlpineNavWalker to improve performance and readability.
 *
 * Hybrid Accordion Logic:
 * - Depth 0 (root): only one submenu can be open at a time (global `activeMenu` state).
 * - Deeper levels: each submenu toggles independently (local `open` state).
 *
 * ## Features
 * - Depth limiting via `max_depth` option (defaults to 3).
 * - Depth-aware Alpine.js bindings for smooth expand/collapse animations.
 * - Distinct link/button classes for items with/without children.
 *
 * ## Usage
 * ```php
 * wp_nav_menu([
 *     'theme_location' => 'mobile',
 *     'walker'         => new \jamal13647850\wphelpers\Navigation\MobileAccordionWalker([
 *         // optional overrides...
 *         'mobile_link_class' => '...',
 *     ]),
 * ]);
 * ```
 *
 * @since 1.0.0
 * @final
 */
final class MobileAccordionWalker extends Walker_Nav_Menu
{
    /**
     * Walker options.
     *
     * @var array{
     *   mobile_link_class:string,
     *   mobile_link_no_children_class:string,
     *   mobile_button_class:string,
     *   mobile_svg_default_fill:string,
     *   max_depth:int
     * }
     */
    private array $options = [
        'mobile_link_class'             => 'flex-1 py-3 text-secondary hover:text-primary transition-colors text-sm sm:text-base',
        'mobile_link_no_children_class' => 'block py-3 text-secondary hover:text-primary transition-colors text-sm sm:text-base',
        'mobile_button_class'           => 'p-2 text-dark hover:text-primary transition-colors',
        'mobile_svg_default_fill'       => '#79528A',
        'max_depth'                     => 3,
    ];

    /**
     * Current mobile item ID (used to bind depth-0 submenu visibility to global state).
     *
     * @var int|null
     */
    private ?int $current_mobile_item_id = null;

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
     * Start rendering a menu item (<li>).
     *
     * Behavior:
     * - Enforces a maximum depth via `max_depth`.
     * - Adds Alpine.js local state (`x-data`) for nested levels that have children.
     * - Renders link + toggle button for items with children, or a simple link otherwise.
     *
     * @param string       $output HTML output (by reference).
     * @param object       $item   Menu item data (WP_Post-like).
     * @param int          $depth  Menu depth (0 = root).
     * @param array|object $args   Menu rendering args (from WordPress).
     * @param int          $id     Menu item ID (unused; WP provides in $item->ID).
     * @return void
     */
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0): void
    {
        // Enforce maximum depth to avoid overly deep menus.
        if ($depth > (int) $this->options['max_depth']) {
            return;
        }

        $this->current_mobile_item_id = $item->ID;

        $indent       = ($depth) ? str_repeat("\t", $depth) : '';
        $classes      = empty($item->classes) ? [] : (array) $item->classes;
        $classes[]    = 'menu-item-' . $item->ID;
        $has_children = in_array('menu-item-has-children', $classes);

        /** @var string $class_names */
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        /** @var string|null $id_attr */
        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        // Hybrid state: local Alpine.js open state for nested levels (depth 1..2 by default).
        if ($has_children && $depth > 0 && $depth < 3) {
            $output .= $indent . '<li' . $id_attr . $class_names . ' x-data="{ open: false }">';
        } else {
            $output .= $indent . '<li' . $id_attr . $class_names . '>';
        }

        // Link attributes.
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

        $item_output = $args->before ?? '';

        // Hybrid accordion logic for mobile.
        if ($has_children && $depth < 3) {
            $item_output .= '<div class="flex items-center justify-between w-full">';
            $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['mobile_link_class']) . '">' . apply_filters('the_title', $item->title, $item->ID) . '</a>';

            // Toggle button for submenu.
            if ($depth === 0) {
                // Root level: bind to global accordion state (activeMenu).
                $click_action = sprintf("activeMenu = (activeMenu === %d ? null : %d)", $item->ID, $item->ID);
                $aria_binding = sprintf('x-bind:aria-expanded="(activeMenu === %d).toString()"', $item->ID);
                $svg_icon     = $this->generateDropdownIcon($item->ID);
            } else {
                // Nested levels: bind to local open state.
                $click_action = 'open = !open';
                $aria_binding = 'x-bind:aria-expanded="open.toString()"';
                $svg_icon     = $this->generateDropdownIcon();
            }

            // UI string intentionally in Persian (fa-IR) per preference.
            $item_output .= '<button @click="' . $click_action . '" class="' . esc_attr($this->options['mobile_button_class']) . '" aria-label="' . esc_attr__('باز و بسته کردن زیرمنو', 'your-theme-textdomain') . '" ' . $aria_binding . '>' . $svg_icon . '</button>';
            $item_output .= '</div>';
        } else {
            // Item without children or at maximum depth: render a simple link.
            $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['mobile_link_no_children_class']) . '">' . apply_filters('the_title', $item->title, $item->ID) . '</a>';
        }

        $item_output .= $args->after ?? '';
        $output      .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Start a submenu level (<ul>).
     *
     * Depth logic:
     * - Depth 0 submenus show when `activeMenu === current_mobile_item_id`.
     * - Deeper levels (>=1) show when local `open` is true.
     * - Only renders when depth is less than 3 (per `max_depth` default).
     *
     * @param string       $output HTML output (by reference).
     * @param int          $depth  Current depth.
     * @param array|object $args   Menu rendering args (unused).
     * @return void
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void
    {
        if ($depth < 3) {
            $indent        = str_repeat("\t", $depth + 1);
            $padding_class = 'pr-' . (2 * ($depth + 1));

            // Determine visibility condition by depth.
            if ($depth === 0) {
                $show_condition = sprintf('activeMenu === %d', $this->current_mobile_item_id);
            } else {
                $show_condition = 'open';
            }

            // Alpine.js transitions for smooth expand/collapse.
            $output .= "\n{$indent}<ul class=\"submenu list-none p-0 m-0 {$padding_class} bg-gray-50 mt-2 rounded overflow-hidden\" x-show=\"{$show_condition}\" x-cloak ";
            $output .= 'x-transition:enter="transition-all duration-300 ease-in-out" ';
            $output .= 'x-transition:enter-start="opacity-0 max-h-0" ';
            $output .= 'x-transition:enter-end="opacity-100 max-h-[100vh]" ';
            $output .= 'x-transition:leave="transition-all duration-300 ease-in-out" ';
            $output .= 'x-transition:leave-start="opacity-100 max-h-[100vh]" ';
            $output .= 'x-transition:leave-end="opacity-0 max-h-0">' . "\n";
        }
    }

    /**
     * End a submenu level.
     *
     * @param string       $output HTML output (by reference).
     * @param int          $depth  Current depth.
     * @param array|object $args   Menu rendering args (unused).
     * @return void
     */
    public function end_lvl(&$output, $depth = 0, $args = []): void
    {
        if ($depth < 3) {
            $indent  = str_repeat("\t", $depth + 1);
            $output .= "{$indent}</ul>\n";
        }
    }

    /**
     * End a single menu item.
     *
     * Closes the <li> wrapper for all depths up to the configured maximum.
     *
     * @param string       $output HTML output (by reference).
     * @param object       $item   Menu item.
     * @param int          $depth  Current depth.
     * @param array|object $args   Menu rendering args (unused).
     * @return void
     */
    public function end_el(&$output, $item, $depth = 0, $args = []): void
    {
        if ($depth <= 3) {
            $output .= "</li>\n";
        }
    }

    /**
     * Generate the dropdown SVG icon.
     *
     * - If `$item_id` is provided (root level), rotation is based on global `activeMenu`.
     * - Otherwise (nested levels), rotation is based on local `open` state.
     *
     * @param int|null $item_id Root item ID to bind against global state.
     * @return string SVG HTML string for the dropdown indicator.
     */
    private function generateDropdownIcon(?int $item_id = null): string
    {
        $transform_attr = '';
        $fill_attr      = 'fill="' . esc_attr($this->options['mobile_svg_default_fill']) . '"';

        if ($item_id !== null) {
            // Root: rotate based on activeMenu.
            $transform_attr = sprintf("x-bind:style=\"{ transform: activeMenu === %d ? 'rotate(180deg)' : 'rotate(0deg)' }\"", $item_id);
        } else {
            // Nested: rotate based on local open state.
            $transform_attr = "x-bind:style=\"{ transform: open ? 'rotate(180deg)' : 'rotate(0deg)' }\"";
        }

        return '<svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="h-6 transition-transform duration-300 ease-in-out" ' . $transform_attr . ' style="margin-top: 0 !important; margin-bottom: 0 !important;" aria-hidden="true">
        <g data-name="24x24/On Light/Arrow-Bottom">
        <path fill="none" d="M0 24V0h24v24z"/>
        <path id="svgPath" d="M7.53 9.47a.75.75 0 0 0-1.06 1.06l5 5a.75.75 0 0 0 1.061 0l5-5a.75.75 0 0 0-1.061-1.06L12 13.94Z" ' . $fill_attr . ' />
        </g></svg>';
    }
}
