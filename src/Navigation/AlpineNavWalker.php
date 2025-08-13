<?php

/**
 * File Name: AlpineNavWalker.php
 * Description: Custom WordPress nav menu walker. Implements a hybrid accordion for mobile menus.
 *
 * @package    wphelpers
 * @subpackage Navigation
 * @author     Sayyed Jamal Ghasemi
 * @link       https://jamalghasemi.com
 * @since      1.7.2
 * @version    2.0.0
 *
 * Developer Contact:
 * Email: jamal13647850@gmail.com
 * LinkedIn: https://www.linkedin.com/in/jamal1364/
 * Telegram: https://t.me/jamaldev
 * 
 * Last Updated: <?php echo date('Y-m-d'); ?>
 */

declare(strict_types=1);

namespace jamal13647850\wphelpers\Navigation;

defined('ABSPATH') || exit();

/**
 * Class AlpineNavWalker
 *
 * Implements a custom nav menu walker supporting a hybrid accordion navigation pattern.
 * - For mobile: Top-level menu items behave as an accordion (only one open at a time), 
 *   while sub-menus (depth > 0) toggle independently within their branches.
 * - For desktop: Top-level items can act as a mega menu on hover.
 * - For dropdown or simple modes: Renders as requested.
 *
 * Usage:
 *   wp_nav_menu([
 *     'theme_location' => 'main_menu',
 *     'walker' => new AlpineNavWalker('mobile'|'desktop'|'dropdown'|'simple', $options)
 *   ]);
 *
 * @package wphelpers\Navigation
 * @author  Sayyed Jamal Ghasemi
 * @version 2.0.0
 */
class AlpineNavWalker extends \Walker_Nav_Menu
{
    /**
     * Holds mega menu parent and child items during render (desktop mode).
     * @var array
     */
    private array $mega_menu_items = [];

    /**
     * Reference to current parent item in mega menu collection.
     * @var array|null
     */
    private ?array $current_parent = null;

    /**
     * Menu type: 'desktop', 'mobile', 'dropdown', or 'simple'.
     * @var string
     */
    private string $menu_type = 'desktop';

    /**
     * Array of images to be shown in mega menu (desktop mode).
     * @var array
     */
    private array $current_menu_images = [];

    /**
     * User-supplied or merged menu options.
     * @var array
     */
    private array $options = [];

    /**
     * Stores current item id (used in dropdown/desktop logic).
     * @var int|null
     */
    private ?int $current_item_id = null;

    /**
     * Stores current item id for mobile (used in hybrid accordion state).
     * @var int|null
     */
    private ?int $current_mobile_item_id = null;

    /**
     * Default menu options (merged with supplied options).
     * @var array
     */
    private array $default_options = [
        'simple_link_class'             => 'text-secondary hover:text-primary transition-colors text-nowrap',
        'desktop_link_class'            => 'flex items-center gap-1 py-4 px-1 text-gray-800 hover:text-primary transition-colors text-nowrap text-sm lg:text-xs xl:text-sm',
        'desktop_link_hover_color'      => '#F25A04',
        'desktop_svg_default_fill'      => '#79528A',
        'desktop_svg_hover_fill'        => '#F25A04',
        'mobile_link_class'             => 'flex-1 py-3 text-secondary hover:text-primary transition-colors text-sm sm:text-base',
        'mobile_link_no_children_class' => 'block py-3 text-secondary hover:text-primary transition-colors text-sm sm:text-base',
        'mobile_button_class'           => 'p-2 text-dark hover:text-primary transition-colors',
        'mobile_svg_default_fill'       => '#79528A',
        'submenu_link_class'            => 'block py-2 px-4 text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors',
        'mega_menu_parent_title_class'  => 'hover:text-primary transition-colors duration-300',
        'mega_menu_child_link_class'    => 'text-gray-700 hover:text-primary transition-colors duration-300 block py-1 text-sm hover:bg-gray-50 px-2 rounded',
        'dropdown_root_link_class'      => 'block text-[#333] text-[16px] font-medium transition-colors duration-300 px-[22px] pt-[18px] pb-4 border-b-2 border-transparent hover:text-[#d32f2f] hover:border-[#d32f2f]',
        'dropdown_child_link_class'     => 'relative block pr-[36px] pl-6 py-3 text-[#333] text-[15px] whitespace-nowrap transition-all duration-300 hover:bg-[#f5f5f5] hover:text-[#d32f2f] font-normal',
        'dropdown_subchild_link_class'  => 'block px-6 py-3 text-[15px] text-[#333] transition-colors duration-300 hover:text-[#d32f2f] whitespace-nowrap',
    ];

    private bool $is_multi_column = false;       // render desktop submenu as N columns
    private int $multi_columns = 2;              // N = 2..3 (قابل افزایش تا 6)
    private array $mc_buffer = [];               // parentId => list of children (depth=1)
    private ?int $mc_parent_id = null;

    /**
     * AlpineNavWalker constructor.
     *
     * @param string $type   Menu rendering type. Valid: 'desktop', 'mobile', 'dropdown', 'simple'.
     * @param array  $options Associative array for customizing CSS classes or SVG fill colors.
     */
    public function __construct(string $type = 'desktop', array $options = [])
    {
        $this->menu_type = $type;
        $this->options   = wp_parse_args($options, $this->default_options);

        if (in_array($type, ['multi-column-desktop', 'overlay-mobile'])) {
            $this->setupCustomVariantOptions($type, $options);
        }
    }

    /**
     * Setup custom variant walker options
     */
    private function setupCustomVariantOptions(string $type, array $options): void
    {

        if ($type === 'multi-column-desktop' ) {
            $this->is_multi_column = true;
            $this->multi_columns   = (int) max(1, min(6, (int)($options['dropdown_columns'] ?? 2)));
            $this->menu_type       = 'desktop';

            $this->options = array_merge($this->options, [
                'dropdown_trigger_class' => $options['dropdown_trigger_class'] ?? 'nav-link dropdown-trigger',
                'dropdown_link_class'    => $options['dropdown_link_class'] ?? 'dropdown-link',
                'dropdown_arrow_class'   => $options['dropdown_arrow_class'] ?? 'dropdown-arrow fas fa-chevron-down',
                'enable_icons'           => $options['enable_icons'] ?? true,
            ]);
        }
     

        if ($type === 'overlay-mobile') {
            $this->options = array_merge($this->options, [
                'mobile_link_class' => $options['mobile_link_class'] ?? 'mobile-menu-link block py-3 px-5 text-foreground hover:text-primary transition-colors font-medium',
                'mobile_submenu_class' => $options['mobile_submenu_class'] ?? 'mobile-submenu-link block py-2 px-8 text-text-muted hover:text-primary transition-colors',
                'enable_accordion' => $options['enable_accordion'] ?? true,
            ]);
            // Set menu_type to mobile for processing
            $this->menu_type = 'mobile';
        }
    }

    private function mcResolveIconClass(?string $attrTitle, ?array $classes): ?string
    {
        if (is_string($attrTitle) && strpos($attrTitle, 'fa-') !== false) {
            return strpos($attrTitle, 'fa ') !== false ? trim($attrTitle) : 'fa ' . trim($attrTitle);
        }
        if (is_array($classes)) {
            foreach ($classes as $c) {
                if (is_string($c) && strpos($c, 'fa-') === 0) {
                    return 'fa ' . trim($c);
                }
            }
        }
        return null;
    }

    /**
     * Start rendering a menu element (li).
     *
     * @param string $output Menu HTML output (by reference).
     * @param object $item   Menu item data.
     * @param int    $depth  Menu depth (0 = root).
     * @param array  $args   Menu rendering arguments.
     * @param int    $id     Menu item ID.
     */
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0): void
    {
        if ($this->menu_type === 'desktop' && $this->is_multi_column) {
            $item  = (object) $item;
            $title = apply_filters('the_title', $item->title ?? '');
            $url   = isset($item->url) ? esc_url($item->url) : '#';

              if ($depth === 0) {
            // ⬇️ تشخیص اینکه این آیتم واقعاً فرزند دارد یا نه
            $classes_wp   = empty($item->classes) ? [] : (array) $item->classes;
            $has_children = in_array('menu-item-has-children', $classes_wp, true);

            // کلاس‌های <li>
            $li_classes   = $classes_wp;
            $li_classes[] = 'menu-item-' . (int) ($item->ID ?? 0);
            $li_classes[] = 'relative';
            if ($has_children) {
                $li_classes[] = 'has-dropdown';
            }
            $output .= "\n<li class=\"" . esc_attr(implode(' ', array_filter($li_classes))) . "\">";

            // کلاس لینک: اگر فرزند دارد همان trigger؛ اگر ندارد فقط کلاس معمولی
            $link_class_children    = esc_attr($this->options['dropdown_trigger_class'] ?? 'nav-link dropdown-trigger');
            $link_class_no_children = esc_attr($this->options['desktop_link_class'] ?? 'nav-link');
            $link_class             = $has_children ? $link_class_children : $link_class_no_children;

            // نگهداری آی‌دی والد برای رندر پنل فقط وقتی واقعاً فرزند دارد
            $this->mc_parent_id = $has_children ? (int) ($item->ID ?? 0) : null;

            // لینک + فلش فقط برای والد
            $output .= '<a href="' . $url . '" class="' . $link_class . '"><span>' . esc_html($title) . '</span>';
            if ($has_children) {
                $arrow_cls = esc_attr($this->options['dropdown_arrow_class'] ?? 'dropdown-arrow fas fa-chevron-down');
                $output   .= '<i class="' . $arrow_cls . '" aria-hidden="true"></i>';
            }
            $output .= '</a>';

            return;
        }

            if ($depth === 1) {
                $this->mc_buffer[$this->mc_parent_id ?? 0][] = [
                    'title'      => $title,
                    'url'        => $url,
                    'target'     => $item->target ?? '',
                    'rel'        => $item->xfn ?? '',
                    'attr_title' => $item->attr_title ?? '',
                    'classes'    => $item->classes ?? [],
                ];
                return;
            }

            // depth >= 2 ignored per requirement
            return;
        }
        if ($this->menu_type === 'mobile') {
            $this->current_mobile_item_id = $item->ID;
        }

        // Render for "simple" menu type (just anchor tags).
        if ($this->menu_type === 'simple') {
            $this->render_simple_menu_item($output, $item, $depth, $args, $id);
            return;
        }
        // Render for "dropdown" menu type (vertical dropdown menu).
        if ($this->menu_type === 'dropdown') {
            $this->current_item_id = $item->ID;
            $this->render_dropdown_menu_item($output, $item, $depth, $args, $item->ID);
            return;
        }
        // For mobile menus, only render up to depth 3.
        if ($this->menu_type === 'mobile' && $depth > 3) {
            return;
        }
        // Collect mega menu items for desktop (for later mega menu rendering).
        if ($this->menu_type === 'desktop' && $depth >= 1) {
            $this->collect_mega_menu_items($item, $depth);
            return;
        }
        // Default: render normal menu item.
        $this->render_menu_item($output, $item, $depth, $args, $id);
    }

    /**
     * Render a dropdown menu item (li + a).
     * Used for 'dropdown' menu type (vertical dropdown menus).
     *
     * @param string $output Menu HTML output (by reference).
     * @param object $item   Menu item object.
     * @param int    $depth  Menu depth.
     * @param array  $args   Render arguments.
     * @param int    $id     Item ID.
     */
    private function render_dropdown_menu_item(&$output, $item, $depth, $args, $id): void
    {
        $indent      = ($depth) ? str_repeat("\t", $depth) : '';
        $classes     = empty($item->classes) ? [] : (array) $item->classes;
        $classes[]   = 'menu-item-' . $item->ID;
        $has_children = in_array('menu-item-has-children', $classes);
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $li_attributes = '';
        if ($has_children) {
            // Alpine.js state for open/close submenu (on hover)
            $li_attributes = ' x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false"';
        }
        $final_classes = ' class="relative ' . esc_attr($class_names) . '"';
        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';
        $output .= $indent . '<li' . $id_attr . $final_classes . $li_attributes . '>';

        // Prepare link attributes.
        $atts = [
            'href'   => !empty($item->url) ? $item->url : '#',
            'target' => !empty($item->target) ? $item->target : '',
            'rel'    => !empty($item->xfn) ? $item->xfn : '',
            'title'  => !empty($item->attr_title) ? $item->attr_title : '',
        ];
        if ($depth === 0) {
            $link_class = $this->options['dropdown_root_link_class'];
        } elseif ($depth === 1) {
            $link_class = $this->options['dropdown_child_link_class'];
        } else {
            $link_class = $this->options['dropdown_subchild_link_class'];
        }
        $atts['class'] = $link_class;

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = ($attr === 'href') ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $item_output = ($args->before ?? '') . '<a' . $attributes . '>';
        $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');

        // Add indicator icon for items with children
        if ($has_children) {
            if ($depth === 0) {
                $item_output .= " <span class=\"flex flex-col justify-center align-middle ml-1 text-[12px] transition-all duration-200 ease-out\" :class=\"open ? '-rotate-90 text-secondary-hover' : 'text-dark'\" aria-hidden=\"true\"><svg class=\"w-4 h-4\" viewBox=\"0 0 20 20\" xmlns=\"http://www.w3.org/2000/svg\" fill=\"currentColor\" focusable=\"false\"><path d=\"M17 9H5.414l3.293-3.293a.999.999 0 1 0-1.414-1.414l-5 5a1 1 0 0 0 0 1.414l5 5a.997.997 0 0 0 1.414 0 1 1 0 0 0 0-1.414L5.414 11H17a1 1 0 1 0 0-2\"/></svg></span>";
            } else {
                $item_output .= ' <span :class="open ? \'text-secondary-hover\' : \'text-dark\'" class="flex flex-col justify-center left-5 top-1/2 text-[16px] font-bold"><svg width="12px" height="12px" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17 9H5.414l3.293-3.293a.999.999 0 10-1.414-1.414l-5 5a.999.999 0 000 1.414l5 5a.997.997 0 001.414 0 .999.999 0 000-1.414L5.414 11H17a1 1 0 100-2z\" fill=\"currentColor\"/></svg></span>';
            }
        }
        $item_output .= '</a>' . ($args->after ?? '');

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Render a simple menu item (li + a).
     * Used for 'simple' menu type.
     *
     * @param string $output Output buffer (by reference).
     * @param object $item   Menu item object.
     * @param int    $depth  Menu depth.
     * @param array  $args   Render arguments.
     * @param int    $id     Item ID.
     */
    private function render_simple_menu_item(&$output, $item, $depth, $args, $id): void
    {
        $indent      = ($depth) ? str_repeat("\t", $depth) : '';
        $classes     = empty($item->classes) ? [] : (array) $item->classes;
        $classes[]   = 'menu-item-' . $item->ID;
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        $id_attr     = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr     = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';
        $output     .= $indent . '<li' . $id_attr . $class_names . '>';

        $atts         = [
            'title'  => !empty($item->attr_title) ? $item->attr_title : '',
            'target' => !empty($item->target) ? $item->target : '',
            'rel'    => !empty($item->xfn) ? $item->xfn : '',
            'href'   => !empty($item->url) ? $item->url : '',
        ];
        if ($item->current || $item->current_item_ancestor || $item->current_item_parent) {
            $atts['aria-current'] = 'page';
        }
        $atts        = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);
        $attributes  = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value      = ($attr === 'href') ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }
        $item_output = $args->before ?? '';
        $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['simple_link_class']) . '">';
        $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
        $item_output .= '</a>';
        $item_output .= $args->after ?? '';
        $output      .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Collects mega menu items (for desktop mode).
     * At depth 1, a new parent is added. At depth 2, children are appended to the current parent.
     *
     * @param object $item  Menu item object.
     * @param int    $depth Menu item depth.
     */
    private function collect_mega_menu_items($item, $depth): void
    {
        if ($depth === 1) {
            $this->mega_menu_items[] = [
                'title'      => apply_filters('the_title', $item->title, $item->ID),
                'url'        => $item->url,
                'ID'         => $item->ID,
                'target'     => $item->target,
                'attr_title' => $item->attr_title,
                'xfn'        => $item->xfn,
                'children'   => [],
            ];
            $this->current_parent    = &$this->mega_menu_items[count($this->mega_menu_items) - 1];
        } elseif ($depth === 2 && $this->current_parent !== null) {
            $this->current_parent['children'][] = [
                'title'      => apply_filters('the_title', $item->title, $item->ID),
                'url'        => $item->url,
                'ID'         => $item->ID,
                'target'     => $item->target,
                'attr_title' => $item->attr_title,
                'xfn'        => $item->xfn,
            ];
        }
    }

    /**
     * Renders a menu item (li + a).
     * Handles logic for hybrid accordion (mobile), mega menu (desktop), and default item rendering.
     *
     * @param string $output Output buffer (by reference).
     * @param object $item   Menu item object.
     * @param int    $depth  Menu depth.
     * @param array  $args   Render arguments.
     * @param int    $id     Item ID.
     */
    private function render_menu_item(&$output, $item, $depth, $args, $id): void
    {
        $indent       = ($depth) ? str_repeat("\t", $depth) : '';
        $classes      = empty($item->classes) ? [] : (array) $item->classes;
        $classes[]    = 'menu-item-' . $item->ID;
        $has_children = in_array('menu-item-has-children', $classes);
        $class_names  = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names  = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        $id_attr      = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr      = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        // Hybrid State Logic: Apply Alpine.js state only to appropriate menu types and depths.
        if ($this->menu_type === 'mobile' && $has_children && $depth > 0 && $depth < 3) {
            // Nested items: Local state for toggle.
            $output .= $indent . '<li' . $id_attr . $class_names . ' x-data="{ open: false }">';
        } elseif ($depth === 0 && $this->menu_type === 'desktop') {
            // Desktop: Top-level items with hover-based state for mega menu.
            $output .= $indent . '<li' . $id_attr . $class_names . ' x-data="{ open0: false, isHovered: false }" x-on:mouseenter="open0 = true; isHovered = true" x-on:mouseleave="open0 = false; isHovered = false" class="menu-item-flex">';
        } else {
            // Other items: No local Alpine state needed.
            $output .= $indent . '<li' . $id_attr . $class_names . '>';
        }

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
        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value      = ($attr === 'href') ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $item_output = $args->before ?? '';

        if ($this->menu_type === 'mobile') {
            // Hybrid accordion logic for mobile.
            if ($has_children && $depth < 3) {
                $item_output .= '<div class="flex items-center justify-between w-full">';
                $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['mobile_link_class']) . '">' . apply_filters('the_title', $item->title, $item->ID) . '</a>';

                // Button for toggling submenu.
                if ($depth === 0) {
                    // Top-level: Bind to global accordion state.
                    $click_action = sprintf("activeMenu = (activeMenu === %d ? null : %d)", $item->ID, $item->ID);
                    $aria_binding = sprintf('x-bind:aria-expanded="(activeMenu === %d).toString()"', $item->ID);
                    $svg_icon     = $this->generateDropdownIcon($item->ID);
                } else {
                    // Nested: Bind to local open state.
                    $click_action = "open = !open";
                    $aria_binding = 'x-bind:aria-expanded="open.toString()"';
                    $svg_icon     = $this->generateDropdownIcon();
                }
                // فارسی: ترجمه label دکمه باز/بسته
                $item_output .= '<button @click="' . $click_action . '" class="' . esc_attr($this->options['mobile_button_class']) . '" aria-label="' . esc_attr__('باز و بسته کردن زیرمنو', 'your-theme-textdomain') . '" ' . $aria_binding . '>' . $svg_icon . '</button>';
                $item_output .= '</div>';
            } else {
                // Item with no children or max depth reached.
                $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['mobile_link_no_children_class']) . '">' . apply_filters('the_title', $item->title, $item->ID) . '</a>';
            }
        } else {
            // Desktop or others
            $svg_icon = $has_children ? $this->generateDropdownIcon() : '';
            $item_output .= '<a x-bind:style="{ color: isHovered ? \'' . esc_attr($this->options['desktop_link_hover_color']) . '\' : \'\' }" ' . $attributes . ' class="' . esc_attr($this->options['desktop_link_class']) . '">';
            $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
            if ($svg_icon) {
                $item_output .= $svg_icon;
            }
            $item_output .= '</a>';
        }

        $item_output .= $args->after ?? '';
        $output      .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Start the submenu level.
     *
     * Handles Alpine.js visibility conditions for hybrid accordion (mobile) and mega menu (desktop).
     *
     * @param string $output Output buffer (by reference).
     * @param int    $depth  Current menu depth.
     * @param array  $args   Render arguments.
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void
    {
        if ($this->menu_type === 'desktop' && $this->is_multi_column && $depth === 0) {
            $indent  = str_repeat("\t", $depth + 1);
            $output .= "\n$indent<div class=\"dropdown-menu\" >\n";
            $output .= "$indent\t<div class=\"dropdown-content\">\n";
            $output .= "$indent\t\t<div class=\"dropdown-columns\">\n";
            return;
        }
        if ($this->menu_type === 'simple') {
            return;
        }
        $indent = str_repeat("\t", $depth + 1);

        // Hybrid accordion (mobile): Show submenu based on Alpine.js state.
        if ($this->menu_type === 'mobile' && $depth < 3) {
            $padding_class = 'pr-' . (2 * ($depth + 1));
            if ($depth === 0) {
                $show_condition = sprintf("activeMenu === %d", $this->current_mobile_item_id);
            } else {
                $show_condition = "open";
            }
            $output .= "\n$indent<ul class=\"submenu list-none p-0 m-0 {$padding_class} bg-gray-50 mt-2 rounded overflow-hidden\" x-show=\"{$show_condition}\" x-cloak x-transition:enter=\"transition-all duration-300 ease-in-out\" x-transition:enter-start=\"opacity-0 max-h-0\" x-transition:enter-end=\"opacity-100 max-h-[100vh]\" x-transition:leave=\"transition-all duration-300 ease-in-out\" x-transition:leave-start=\"opacity-100 max-h-[100vh]\" x-transition:leave-end=\"opacity-0 max-h-0\">\n";
        } elseif ($this->menu_type === 'desktop' && $depth === 0) {
            // Mega menu container for desktop top-level submenu.
            $output .= "\n$indent<div class=\"mega-menu absolute min-w-[60vw] bg-white shadow-xl border-t border-gray-200 z-[112]\" style=\"margin-left: calc(-50vw + 50%);\" x-show=\"open0\" x-cloak x-transition:enter=\"transition ease-out duration-300\" x-transition:enter-start=\"opacity-0 transform translate-y-[-10px]\" x-transition:enter-end=\"opacity-100 transform translate-y-0\" x-transition:leave=\"transition ease-in duration-200\" x-transition:leave-start=\"opacity-100 transform translate-y-0\" x-transition:leave-end=\"opacity-0 transform translate-y-[-10px]\" @click.outside=\"open0 = false\">\n";
            $output .= "$indent\t<div class=\"mega-menu-container max-w-7xl mx-auto px-6 py-8\">\n";
            $output .= "$indent\t\t<div class=\"flex flex-row-reverse gap-8\">\n";
            // Render mega menu images if available.
            if (!empty($this->current_menu_images)) {
                $output .= "$indent\t\t\t<div class=\"mega-menu-images flex flex-col gap-4 w-1/3\">\n";
                foreach ($this->current_menu_images as $image_data) {
                    if (!empty($image_data['image'])) {
                        $image_url = esc_url($image_data['image']);
                        $image_alt = !empty($image_data['alt']) ? esc_attr($image_data['alt']) : esc_attr(basename($image_url));
                        $output .= "$indent\t\t\t\t<div class=\"image-container flex-1\">\n";
                        $output .= "$indent\t\t\t\t\t<img src=\"$image_url\" alt=\"$image_alt\" class=\"w-full h-[300px] rounded-lg shadow-md object-cover hover:shadow-lg transition-shadow duration-300\" loading=\"lazy\" />\n";
                        $output .= "$indent\t\t\t\t</div>\n";
                    }
                }
                $output .= "$indent\t\t\t</div>\n";
            }
            $output .= "$indent\t\t\t<div class=\"mega-menu-content flex-1\">\n";
            $output .= "$indent\t\t\t\t<div class=\"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8\">\n";
        } elseif ($this->menu_type === 'dropdown') {
            // Vertical dropdown submenu (Alpine.js show on hover/click)
            $ul_classes = "absolute top-full right-0 min-w-[250px] bg-white shadow-[0_0_3px_rgba(0,0,0,0.15)] rounded-b-xl z-50 py-2 mt-1 list-none";
            if ($depth > 0) {
                $ul_classes = "absolute top-0 right-full min-w-[250px] bg-white shadow-[3px_0_3px_rgba(0,0,0,0.15)] rounded-xl shadow-lg z-50 py-2 list-none";
            }
            $output .= "\n$indent<ul class=\"$ul_classes\" x-show=\"open\" x-cloak " . 'x-transition:enter="transition ease-out duration-200" ' . 'x-transition:enter-start="opacity-0 translate-y-2" ' . 'x-transition:enter-end="opacity-100 translate-y-0" ' . 'x-transition:leave="transition ease-in duration-150" ' . 'x-transition:leave-start="opacity-100 translate-y-0" ' . 'x-transition:leave-end="opacity-0 translate-y-2" ' . "style=\"display:none;\">\n";
        }
    }

    /**
     * End the submenu level.
     * For desktop: closes mega menu markup. For mobile: closes submenu ul.
     *
     * @param string $output Output buffer (by reference).
     * @param int    $depth  Menu depth.
     * @param array  $args   Render arguments.
     */
    public function end_lvl(&$output, $depth = 0, $args = []): void
    {
        $indent = str_repeat("\t", $depth + 1);

        if ($this->menu_type === 'desktop' && $this->is_multi_column && $depth === 0) {
            $indent   = str_repeat("\t", $depth + 1);
            $children = $this->mc_buffer[$this->mc_parent_id ?? 0] ?? [];
            $count    = count($children);
            $cols     = max(1, $this->multi_columns);
            $perCol   = $count > 0 ? (int) ceil($count / $cols) : 0;

            for ($col = 0; $col < $cols; $col++) {
                $start = $col * $perCol;
                $slice = ($perCol > 0) ? array_slice($children, $start, $perCol) : [];

                $output .= "$indent\t\t\t<div class=\"dropdown-column\">\n";
                foreach ($slice as $link) {
                    $iconClass = null;
                    if (!empty($this->options['enable_icons'])) {
                        $iconClass = $this->mcResolveIconClass($link['attr_title'] ?? null, $link['classes'] ?? []);
                    }
                    $linkClass = esc_attr($this->options['dropdown_link_class'] ?? 'dropdown-link');
                    $output .= "$indent\t\t\t\t<a href=\"" . esc_url($link['url']) . "\" class=\"$linkClass\">";
                    if ($iconClass) {
                        $output .= '<i class="' . esc_attr($iconClass) . '" aria-hidden="true"></i>';
                    }
                    $output .= '<span>' . esc_html($link['title']) . "</span></a>\n";
                }
                $output .= "$indent\t\t\t</div>\n";
            }

            $output .= "$indent\t\t</div>\n"; // .dropdown-columns
            $output .= "$indent\t</div>\n";   // .dropdown-content
            $output .= "$indent</div>\n";     // .dropdown-menu

            if ($this->mc_parent_id !== null) {
                unset($this->mc_buffer[$this->mc_parent_id]);
            }
            $this->mc_parent_id = null;
            return;
        }

        if ($this->menu_type === 'dropdown') {
            $output .= "$indent</ul>\n";
            return;
        }

        // Desktop: close mega menu structure and reset state
        if ($depth === 0 && $this->menu_type === 'desktop') {
            foreach ($this->mega_menu_items as $mega_item) {
                $output .= "$indent\t\t\t\t\t<div class=\"mega-menu-section\">\n";
                $parent_atts = [];
                $parent_atts['href'] = !empty($mega_item['url']) ? esc_url($mega_item['url']) : '#';
                if (!empty($mega_item['target'])) $parent_atts['target'] = esc_attr($mega_item['target']);
                if (!empty($mega_item['attr_title'])) $parent_atts['title'] = esc_attr($mega_item['attr_title']);
                if (!empty($mega_item['xfn'])) $parent_atts['rel'] = esc_attr($mega_item['xfn']);

                $parent_attributes_str = '';
                foreach ($parent_atts as $attr => $val) {
                    $parent_attributes_str .= " {$attr}=\"{$val}\"";
                }

                $output .= "$indent\t\t\t\t\t\t<h3 class=\"mega-menu-title text-xl font-bold text-secondary mb-4 pb-2 border-b border-gray-200\">\n";
                $output .= "$indent\t\t\t\t\t\t\t<a" . $parent_attributes_str . " class=\"" . esc_attr($this->options['mega_menu_parent_title_class']) . "\">" . esc_html($mega_item['title']) . "</a>\n";
                $output .= "$indent\t\t\t\t\t\t</h3>\n";
                if (!empty($mega_item['children'])) {
                    $output .= "$indent\t\t\t\t\t\t<ul class=\"mega-menu-items list-none p-0 m-0 space-y-2\">\n";
                    foreach ($mega_item['children'] as $child) {
                        $child_atts = [];
                        $child_atts['href'] = !empty($child['url']) ? esc_url($child['url']) : '#';
                        if (!empty($child['target'])) $child_atts['target'] = esc_attr($child['target']);
                        if (!empty($child['attr_title'])) $child_atts['title'] = esc_attr($child['attr_title']);
                        if (!empty($child['xfn'])) $child_atts['rel'] = esc_attr($child['xfn']);

                        $child_attributes_str = '';
                        foreach ($child_atts as $attr => $val) {
                            $child_attributes_str .= " {$attr}=\"{$val}\"";
                        }
                        $output .= "$indent\t\t\t\t\t\t\t<li>\n";
                        $output .= "$indent\t\t\t\t\t\t\t\t<a" . $child_attributes_str . " class=\"" . esc_attr($this->options['mega_menu_child_link_class']) . "\">" . esc_html($child['title']) . "</a>\n";
                        $output .= "$indent\t\t\t\t\t\t\t</li>\n";
                    }
                    $output .= "$indent\t\t\t\t\t\t</ul>\n";
                }
                $output .= "$indent\t\t\t\t\t</div>\n";
            }
            $output .= "$indent\t\t\t\t</div>\n";
            $output .= "$indent\t\t\t</div>\n";
            $output .= "$indent\t\t</div>\n";
            $output .= "$indent\t</div>\n";
            $output .= "$indent</div>\n";
            // Reset collected mega menu items
            $this->mega_menu_items     = [];
            $this->current_parent      = null;
            $this->current_menu_images = [];
        } elseif ($this->menu_type === 'mobile' && $depth < 3) {
            $output .= "$indent</ul>\n";
        }
    }

    /**
     * End a menu element (close li).
     *
     * @param string $output Output buffer (by reference).
     * @param object $item   Menu item object.
     * @param int    $depth  Menu depth.
     * @param array  $args   Render arguments.
     */
    public function end_el(&$output, $item, $depth = 0, $args = []): void
    {
        if ($this->menu_type === 'desktop' && $this->is_multi_column) {
            if ($depth === 0) {
                $output .= "</li>\n";
            }
            return;
        }
        if ($this->menu_type === 'simple' || $this->menu_type === 'dropdown') {
            $output .= "</li>\n";
            return;
        }
        if ($this->menu_type === 'mobile') {
            if ($depth <= 3) {
                $output .= "</li>\n";
            }
            return;
        }
        if ($this->menu_type === 'desktop') {
            if ($depth === 0) {
                $output .= "</li>\n";
            }
        }
    }

    /**
     * Generates the SVG dropdown icon.
     * For mobile, supports global (top-level) and local (nested) state via Alpine.js bindings.
     * For desktop, supports hover color/fill/rotate via Alpine.js.
     *
     * @param int|null $item_id (Optional) Top-level menu item ID for global accordion state (mobile only).
     * @return string SVG markup for dropdown indicator icon.
     */
    private function generateDropdownIcon(int $item_id = null): string
    {
        $transform_attr = '';
        $fill_attr      = '';

        if ($this->menu_type === 'desktop') {
            $transform_attr = "x-bind:style=\"{ transform: isHovered ? 'rotate(180deg)' : 'rotate(0deg)' }\"";
            $fill_attr      = "x-bind:fill=\"isHovered ? '" . esc_attr($this->options['desktop_svg_hover_fill']) . "' : '" . esc_attr($this->options['desktop_svg_default_fill']) . "'\"";
        } elseif ($this->menu_type === 'mobile') {
            if ($item_id !== null) {
                $transform_attr = sprintf("x-bind:style=\"{ transform: activeMenu === %d ? 'rotate(180deg)' : 'rotate(0deg)' }\"", $item_id);
            } else {
                $transform_attr = "x-bind:style=\"{ transform: open ? 'rotate(180deg)' : 'rotate(0deg)' }\"";
            }
            $fill_attr = 'fill="' . esc_attr($this->options['mobile_svg_default_fill']) . '"';
        }

        return '<svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="h-6 transition-transform duration-300 ease-in-out" ' . $transform_attr . ' style="margin-top: 0 !important; margin-bottom: 0 !important;" aria-hidden="true">
        <g data-name="24x24/On Light/Arrow-Bottom">
        <path fill="none" d="M0 24V0h24v24z"/>
        <path id="svgPath" d="M7.53 9.47a.75.75 0 0 0-1.06 1.06l5 5a.75.75 0 0 0 1.061 0l5-5a.75.75 0 0 0-1.061-1.06L12 13.94Z" ' . $fill_attr . ' />
        </g></svg>';
    }
}
