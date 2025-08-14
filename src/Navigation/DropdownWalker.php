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
 * DropdownWalker
 *
 * A specialized WordPress walker for vertical dropdown menus with hover/click
 * interactions, separated from AlpineNavWalker for clarity and performance.
 *
 * Features:
 * - Renders arbitrary depths (root + nested submenus).
 * - Alpine.js state (`x-data`) controls open/close on hover for items that have children.
 * - Depth-aware link classes (root/child/subchild) driven by `$options`.
 *
 * Usage:
 * ```php
 * wp_nav_menu([
 *     'theme_location' => 'sidebar',
 *     'walker'         => new \jamal13647850\wphelpers\Navigation\DropdownWalker([
 *         'dropdown_root_link_class'     => '...',
 *         'dropdown_child_link_class'    => '...',
 *         'dropdown_subchild_link_class' => '...',
 *     ]),
 * ]);
 * ```
 *
 * Preconditions:
 * - WordPress environment is loaded; `Walker_Nav_Menu` is available.
 *
 * Side Effects:
 * - Emits HTML by appending to the `$output` (by reference) in walker methods.
 *
 * @since 1.0.0
 * @final
 */
final class DropdownWalker extends Walker_Nav_Menu
{
    /**
     * Walker options controlling link classes by depth.
     *
     * @var array{
     *   dropdown_root_link_class:string,
     *   dropdown_child_link_class:string,
     *   dropdown_subchild_link_class:string
     * }
     */
    private array $options = [
        'dropdown_root_link_class'     => 'block text-[#333] text-[16px] font-medium transition-colors duration-300 px-[22px] pt-[18px] pb-4 border-b-2 border-transparent hover:text-[#d32f2f] hover:border-[#d32f2f]',
        'dropdown_child_link_class'    => 'relative block pr-[36px] pl-6 py-3 text-[#333] text-[15px] whitespace-nowrap transition-all duration-300 hover:bg-[#f5f5f5] hover:text-[#d32f2f] font-normal',
        'dropdown_subchild_link_class' => 'block px-6 py-3 text-[15px] text-[#333] transition-colors duration-300 hover:text-[#d32f2f] whitespace-nowrap',
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
     * Start rendering a single menu item (<li>).
     *
     * Behavior:
     * - Adds `x-data` and hover handlers when the item has children (for Alpine.js).
     * - Applies depth-specific link classes from options.
     * - Appends directional SVG indicators when the item has children.
     *
     * @param string               $output HTML output (by reference).
     * @param object               $item   Menu item (WP_Post-like object).
     * @param int                  $depth  Depth level (0 = root).
     * @param array|object         $args   Menu rendering args (as passed by WP).
     * @param int                  $id     Item ID (unused; WP provides in $item->ID).
     * @return void
     */
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0): void
    {
        $indent       = ($depth) ? str_repeat("\t", $depth) : '';
        $classes      = empty($item->classes) ? [] : (array) $item->classes;
        $classes[]    = 'menu-item-' . $item->ID;
        $has_children = in_array('menu-item-has-children', $classes);

        // Build CSS classes for <li>.
        /** @var string $class_names */
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));

        // Alpine.js state for submenu toggle on hover.
        $li_attributes = '';
        if ($has_children) {
            $li_attributes = ' x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false"';
        }

        $final_classes = ' class="relative ' . esc_attr($class_names) . '"';
        /** @var string|null $id_attr */
        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        $output .= $indent . '<li' . $id_attr . $final_classes . $li_attributes . '>';

        // Build link attributes.
        $atts = [
            'href'   => !empty($item->url) ? $item->url : '#',
            'target' => !empty($item->target) ? $item->target : '',
            'rel'    => !empty($item->xfn) ? $item->xfn : '',
            'title'  => !empty($item->attr_title) ? $item->attr_title : '',
        ];

        // Choose depth-aware link class.
        if ($depth === 0) {
            $link_class = $this->options['dropdown_root_link_class'];
        } elseif ($depth === 1) {
            $link_class = $this->options['dropdown_child_link_class'];
        } else {
            $link_class = $this->options['dropdown_subchild_link_class'];
        }

        $atts['class'] = $link_class;

        // Attributes string.
        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value       = ($attr === 'href') ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        // Render the link.
        $item_output  = ($args->before ?? '') . '<a' . $attributes . '>';
        $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');

        // Add indicator icon for items with children.
        if ($has_children) {
            if ($depth === 0) {
                // Root-level indicator (chevron rotates).
                $item_output .= ' <span class="flex flex-col justify-center align-middle ml-1 text-[12px] transition-all duration-200 ease-out" :class="open ? \'-rotate-90 text-secondary-hover\' : \'text-dark\'" aria-hidden="true">';
                $item_output .= '<svg class="w-4 h-4" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">';
                $item_output .= '<path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" fill="currentColor"/>';
                $item_output .= '</svg></span>';
            } else {
                // Deeper levels indicator (left/right arrow).
                $item_output .= ' <span :class="open ? \'text-secondary-hover\' : \'text-dark\'" class="flex flex-col justify-center left-5 top-1/2 text-[16px] font-bold">';
                $item_output .= '<svg width="12px" height="12px" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">';
                $item_output .= '<path d="M17 9H5.414l3.293-3.293a.999.999 0 10-1.414-1.414l-5 5a.999.999 0 000 1.414l5 5a.997.997 0 001.414 0 .999.999 0 000-1.414L5.414 11H17a1 1 0 100-2z" fill="currentColor"/>';
                $item_output .= '</svg></span>';
            }
        }

        $item_output .= '</a>' . ($args->after ?? '');
        $output      .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Start a submenu level (<ul>).
     *
     * Depth 0 submenus open downward; deeper levels open sideways.
     * Uses Alpine.js transitions for smooth animations and sets `x-cloak`.
     *
     * @param string       $output HTML output (by reference).
     * @param int          $depth  Current depth (0-based).
     * @param array|object $args   Menu rendering args (unused here).
     * @return void
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void
    {
        $indent = str_repeat("\t", $depth + 1);

        // Depth-aware submenu classes.
        if ($depth === 0) {
            // First-level submenu opens downward.
            $ul_classes = 'absolute top-full right-0 min-w-[250px] bg-white shadow-[0_0_3px_rgba(0,0,0,0.15)] rounded-b-xl z-50 py-2 mt-1 list-none';
        } else {
            // Deeper submenus open to the side.
            $ul_classes = 'absolute top-0 right-full min-w-[250px] bg-white shadow-[3px_0_3px_rgba(0,0,0,0.15)] rounded-xl shadow-lg z-50 py-2 list-none';
        }

        // Alpine.js transitions for enter/leave.
        $output .= "\n{$indent}<ul class=\"{$ul_classes}\" x-show=\"open\" x-cloak ";
        $output .= 'x-transition:enter="transition ease-out duration-200" ';
        $output .= 'x-transition:enter-start="opacity-0 translate-y-2" ';
        $output .= 'x-transition:enter-end="opacity-100 translate-y-0" ';
        $output .= 'x-transition:leave="transition ease-in duration-150" ';
        $output .= 'x-transition:leave-start="opacity-100 translate-y-0" ';
        $output .= 'x-transition:leave-end="opacity-0 translate-y-2" ';
        $output .= 'style="display:none;">' . "\n";
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
        $indent  = str_repeat("\t", $depth + 1);
        $output .= "{$indent}</ul>\n";
    }

    /**
     * End a single menu item.
     *
     * @param string       $output HTML output (by reference).
     * @param object       $item   Menu item.
     * @param int          $depth  Current depth.
     * @param array|object $args   Menu rendering args (unused).
     * @return void
     */
    public function end_el(&$output, $item, $depth = 0, $args = []): void
    {
        $output .= "</li>\n";
    }
}
