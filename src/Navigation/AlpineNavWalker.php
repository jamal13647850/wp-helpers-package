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

defined('ABSPATH') || exit();

/**
 * Alpine Nav Walker Class
 *
 * Custom WordPress nav menu walker with Alpine.js integration.
 * - Supports mobile, desktop, simple, and dropdown menu types.
 * - Mega menu rendering for desktop, dropdown UI for mobile, ARIA/UX support.
 * - Fully customizable styling via $options.
 * - All UI-facing labels are translated to Persian (fa-IR).
 *
 * @author  Sayyed Jamal Ghasemi
 * @version 1.7.2
 */
class AlpineNavWalker extends \Walker_Nav_Menu
{
    /**
     * Holds mega menu columns for desktop rendering.
     * @var array
     */
    private array $mega_menu_items = [];

    /**
     * Reference to the current mega menu parent while building columns.
     * @var array|null
     */
    private ?array $current_parent = null;

    /**
     * Menu rendering mode: 'desktop', 'mobile', 'simple', or 'dropdown'.
     * @var string
     */
    private string $menu_type = 'desktop';

    /**
     * Images for the current mega menu (desktop mode).
     * @var array
     */
    private array $current_menu_images = [];

    /**
     * Merged styling/behavior options.
     * @var array
     */
    private array $options = [];

    /**
     * Used to track current menu item ID (for dropdown submenu logic).
     * @var int|null
     */
    private ?int $current_item_id = null;

    /**
     * Default styling/behavior options (can be overridden via $options).
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

    /**
     * Constructor: Sets menu rendering mode and merges custom options.
     *
     * @param string $type    Menu type: 'desktop', 'mobile', 'simple', or 'dropdown'
     * @param array  $options Override default options (class names, etc.)
     */
    public function __construct(string $type = 'desktop', array $options = [])
    {
        $this->menu_type = $type;
        $this->options = wp_parse_args($options, $this->default_options);
    }

    /**
     * Output the beginning of a menu item (<li> + <a> etc).
     * Calls specialized renderers depending on menu type.
     *
     * @param string $output Output HTML (by reference)
     * @param object $item   Menu item object
     * @param int    $depth  Menu depth (0 = root)
     * @param array  $args   Arguments from wp_nav_menu()
     * @param int    $id     Menu item ID
     */
    function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0): void
    {
        // "Simple" mode: minimal, used for e.g. footer menus
        if ($this->menu_type === 'simple') {
            $this->render_simple_menu_item($output, $item, $depth, $args, $id);
            return;
        }
        // "Dropdown" mode: classic multilevel dropdown menu
        if ($this->menu_type === 'dropdown') {
            $this->current_item_id = $item->ID;
            $this->render_dropdown_menu_item($output, $item, $depth, $args, $item->ID);
            return;
        }
        // Mobile mode: limit submenu to 2 levels for better UX
        if ($this->menu_type === 'mobile' && $depth > 1) {
            return;
        }
        // Desktop mode: Collects mega menu items at depth >= 1
        if ($this->menu_type === 'desktop' && $depth >= 1) {
            $this->collect_mega_menu_items($item, $depth);
            return;
        }
        // All other cases: render standard menu item
        $this->render_menu_item($output, $item, $depth, $args, $id);
    }

    /**
     * Render a dropdown menu item for "dropdown" mode.
     * Outputs <li> and <a> with proper Alpine.js states for interaction.
     *
     * @param string $output Output HTML (by reference)
     * @param object $item   Menu item object
     * @param int    $depth  Menu depth (0 = root)
     * @param array  $args   Arguments from wp_nav_menu()
     * @param int    $id     Menu item ID
     */
    private function render_dropdown_menu_item(&$output, $item, $depth, $args, $id): void
    {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        $has_children = in_array('menu-item-has-children', $classes);
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));

        $li_attributes = '';
        // Each parent menu item manages its own "open" state (isolated Alpine.js scope)
        if ($has_children) {
            $li_attributes = ' x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false"';
        }
        // "relative" is required for correct submenu positioning
        $final_classes = ' class="relative ' . esc_attr($class_names) . '"';

        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        $output .= $indent . '<li' . $id_attr . $final_classes . $li_attributes . '>';

        // Build anchor tag attributes
        $atts = [];
        $atts['href']   = !empty($item->url) ? $item->url : '#';
        $atts['target'] = !empty($item->target) ? $item->target : '';
        $atts['rel']    = !empty($item->xfn) ? $item->xfn : '';
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';

        // Assign proper link class for each menu depth
        if ($depth === 0) {
            $link_class = $this->options['dropdown_root_link_class'];
        } elseif ($depth === 1) {
            $link_class = $this->options['dropdown_child_link_class'];
        } else {
            $link_class = $this->options['dropdown_subchild_link_class'];
        }
        $atts['class'] = $link_class;

        // Output anchor attributes
        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $item_output = ($args->before ?? '') . '<a' . $attributes . '>';
        $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');

        // Add dropdown icon for items with children
        if ($has_children) {
            if ($depth === 0) {
                // Alpine.js: rotates icon on hover
                $item_output .= " <span
  class=\"flex flex-col justify-center align-middle ml-1 text-[12px] transition-all duration-200 ease-out\"
  :class=\"open ? '-rotate-90 text-secondary-hover' : 'text-dark'\"
  aria-hidden=\"true\"
>
  <svg
    class=\"w-4 h-4\"
    viewBox=\"0 0 20 20\"
    xmlns=\"http://www.w3.org/2000/svg\"
    fill=\"currentColor\"
    focusable=\"false\"
  >
    <path
      d=\"M17 9H5.414l3.293-3.293a.999.999 0 1 0-1.414-1.414l-5 5a1 1 0 0 0 0 1.414l5 5a.997.997 0 0 0 1.414 0 1 1 0 0 0 0-1.414L5.414 11H17a1 1 0 1 0 0-2\"
    />
  </svg>
</span>";
            } else {
                $item_output .= ' <span :class="open ? \'text-secondary-hover\' : \'text-dark\'" class="flex flex-col justify-center left-5 top-1/2 text-[16px] font-bold"><svg width="12px" height="12px" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17 9H5.414l3.293-3.293a.999.999 0 10-1.414-1.414l-5 5a.999.999 0 000 1.414l5 5a.997.997 0 001.414 0 .999.999 0 000-1.414L5.414 11H17a1 1 0 100-2z" fill="currentColor"/></svg></span>';
            }
        }
        $item_output .= '</a>' . ($args->after ?? '');

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Render a simple menu item (<li> + <a>) for "simple" mode.
     *
     * @param string $output Output HTML (by reference)
     * @param object $item   Menu item object
     * @param int    $depth  Menu depth
     * @param array  $args   Arguments from wp_nav_menu()
     * @param int    $id     Menu item ID
     */
    private function render_simple_menu_item(&$output, $item, $depth, $args, $id): void
    {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        $output .= $indent . '<li' . $id_attr . $class_names . '>';

        $atts = [];
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target) ? $item->target : '';
        $atts['rel']    = !empty($item->xfn) ? $item->xfn : '';
        $atts['href']   = !empty($item->url) ? $item->url : '';
        if ($item->current || $item->current_item_ancestor || $item->current_item_parent) {
            $atts['aria-current'] = 'page';
        }
        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $item_output = $args->before ?? '';
        $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['simple_link_class']) . '">';
        $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
        $item_output .= '</a>';
        $item_output .= $args->after ?? '';

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Collect mega menu columns for desktop mode.
     * At depth 1, create new column; at depth 2, add as a child link under the latest parent.
     *
     * @param object $item  Menu item object
     * @param int    $depth Menu depth
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
            // Assign current parent by reference for fast child linking
            $this->current_parent = &$this->mega_menu_items[count($this->mega_menu_items) - 1];
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
     * Render a menu item for desktop/mobile modes (including mega menu triggers).
     * Output varies by depth, presence of children, and mode.
     *
     * @param string $output Output HTML (by reference)
     * @param object $item   Menu item object
     * @param int    $depth  Menu depth
     * @param array  $args   Arguments from wp_nav_menu()
     * @param int    $id     Menu item ID
     */
    private function render_menu_item(&$output, $item, $depth, $args, $id): void
    {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        $has_children = in_array('menu-item-has-children', $classes);

        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        // Mega menu image handling: only for top-level desktop menu items
        if ($depth === 0 && $this->menu_type === 'desktop' && function_exists('get_field')) {
            $menu_item_id = $item->ID;
            $mega_menu_images_field = get_field('mega_menu_images', $menu_item_id);
            $this->current_menu_images = is_array($mega_menu_images_field) ? $mega_menu_images_field : [];
        }

        // SVG dropdown icon if menu item has children
        $svg_icon = $has_children ? $this->generateDropdownIcon() : '';

        // Desktop root item: Alpine.js for hover state/mega menu trigger
        if ($depth === 0 && $this->menu_type === 'desktop') {
            $output .= $indent . '<li' . $id_attr . $class_names . ' x-data="{ open0: false, isHovered: false }" x-on:mouseenter="open0 = true; isHovered = true" x-on:mouseleave="open0 = false; isHovered = false" class="menu-item-flex">';
        } elseif ($this->menu_type === 'mobile' && $depth === 0 && $has_children) {
            // Mobile root with children: Alpine.js "open" for dropdown
            $output .= $indent . '<li' . $id_attr . $class_names . ' x-data="{ open: false }">';
        } else {
            // All others
            $output .= $indent . '<li' . $id_attr . $class_names . '>';
        }

        // Build anchor/button attributes
        $atts = [];
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target) ? $item->target : '';
        $atts['rel']    = !empty($item->xfn) ? $item->xfn : '';
        $atts['href']   = !empty($item->url) ? $item->url : '';
        if ($item->current || $item->current_item_ancestor || $item->current_item_parent) {
            $atts['aria-current'] = 'page';
        }
        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $item_output = $args->before ?? '';

        if ($depth === 0 && $this->menu_type === 'desktop') {
            // Desktop root: hover color via Alpine.js binding
            $item_output .= '<a x-bind:style="{ color: isHovered ? \'' . esc_attr($this->options['desktop_link_hover_color']) . '\' : \'\' }" ' . $attributes . ' class="' . esc_attr($this->options['desktop_link_class']) . '">';
            $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
            if ($svg_icon) {
                $item_output .= $svg_icon;
            }
            $item_output .= '</a>';
        } elseif ($this->menu_type === 'mobile' && $depth === 0) {
            // Mobile: root item, may have children (show button)
            if ($has_children) {
                $item_output .= '<div class="flex items-center justify-between w-full">';
                $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['mobile_link_class']) . '">';
                $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
                $item_output .= '</a>';
                // Toggle button, with ARIA label translated to Persian
                $item_output .= '<button @click="open = !open" class="' . esc_attr($this->options['mobile_button_class']) . '" aria-label="' . esc_attr__('باز و بسته کردن زیرمنو', 'your-theme-textdomain') . '" aria-expanded="false" x-bind:aria-expanded="open.toString()">' . $svg_icon . '</button>';
                $item_output .= '</div>';
            } else {
                $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['mobile_link_no_children_class']) . '">';
                $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
                $item_output .= '</a>';
            }
        } else {
            // Submenu item (desktop/mobile)
            $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['submenu_link_class']) . '">';
            $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
            $item_output .= '</a>';
        }

        $item_output .= $args->after ?? '';
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Output the start of a submenu list (ul or mega menu container).
     *
     * @param string $output Output HTML (by reference)
     * @param int    $depth  Menu depth
     * @param array  $args   Arguments from wp_nav_menu()
     */
    function start_lvl(&$output, $depth = 0, $args = array()): void
    {
        if ($this->menu_type === 'simple') {
            return;
        }
        $indent = str_repeat("\t", $depth + 1);

        if ($this->menu_type === 'dropdown') {
            // Dropdown: right/left aligned submenu, transitions with Alpine.js
            $ul_classes = "absolute top-full right-0 min-w-[250px] bg-white shadow-[0_0_3px_rgba(0,0,0,0.15)] rounded-b-xl  z-50 py-2 mt-1 list-none";
            if ($depth > 0) {
                $ul_classes = "absolute top-0 right-full min-w-[250px] bg-white shadow-[3px_0_3px_rgba(0,0,0,0.15)] rounded-xl shadow-lg z-50 py-2 list-none";
            }
            $output .= "\n$indent<ul class=\"$ul_classes\" x-show=\"open\" x-cloak "
                . 'x-transition:enter="transition ease-out duration-200" '
                . 'x-transition:enter-start="opacity-0 translate-y-2" '
                . 'x-transition:enter-end="opacity-100 translate-y-0" '
                . 'x-transition:leave="transition ease-in duration-150" '
                . 'x-transition:leave-start="opacity-100 translate-y-0" '
                . 'x-transition:leave-end="opacity-0 translate-y-2" '
                . "style=\"display:none;\">\n";
            return;
        }

        // Desktop mode: output mega menu container on root submenu
        if ($depth === 0 && $this->menu_type === 'desktop') {
            $output .= "\n$indent<div class=\"mega-menu absolute min-w-[60vw] bg-white shadow-xl border-t border-gray-200 z-[112]\" style=\"margin-left: calc(-50vw + 50%);\" x-show=\"open0\" x-cloak x-transition:enter=\"transition ease-out duration-300\" x-transition:enter-start=\"opacity-0 transform translate-y-[-10px]\" x-transition:enter-end=\"opacity-100 transform translate-y-0\" x-transition:leave=\"transition ease-in duration-200\" x-transition:leave-start=\"opacity-100 transform translate-y-0\" x-transition:leave-end=\"opacity-0 transform translate-y-[-10px]\" @click.outside=\"open0 = false\">\n";
            $output .= "$indent\t<div class=\"mega-menu-container max-w-7xl mx-auto px-6 py-8\">\n";
            $output .= "$indent\t\t<div class=\"flex flex-row-reverse gap-8\">\n";
            // Output images (if any) on the right
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
        } elseif ($this->menu_type === 'mobile' && $depth === 0) {
            // Mobile mode: expand/collapse submenu with Alpine.js transitions
            $output .= "\n$indent<ul class=\"submenu list-none p-0 m-0 pl-4 bg-gray-50 mt-2 rounded overflow-hidden\" x-show=\"open\" x-cloak x-transition:enter=\"transition-all duration-300 ease-in-out\" x-transition:enter-start=\"opacity-0 max-h-0\" x-transition:enter-end=\"opacity-100 max-h-[100vh]\" x-transition:leave=\"transition-all duration-300 ease-in-out\" x-transition:leave-start=\"opacity-100 max-h-[100vh]\" x-transition:leave-end=\"opacity-0 max-h-0\">\n";
        }
    }

    /**
     * Output the end of a submenu list (ul or mega menu container).
     *
     * @param string $output Output HTML (by reference)
     * @param int    $depth  Menu depth
     * @param array  $args   Arguments from wp_nav_menu()
     */
    function end_lvl(&$output, $depth = 0, $args = array()): void
    {
        if ($this->menu_type === 'simple') {
            return;
        }
        $indent = str_repeat("\t", $depth + 1);

        if ($this->menu_type === 'dropdown') {
            $output .= "$indent</ul>\n";
            return;
        }

        if ($depth === 0 && $this->menu_type === 'desktop') {
            // Output mega menu columns (sections)
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

            // Close mega menu containers
            $output .= "$indent\t\t\t\t</div>\n";
            $output .= "$indent\t\t\t</div>\n";
            $output .= "$indent\t\t</div>\n";
            $output .= "$indent\t</div>\n";
            $output .= "$indent</div>\n";

            // Reset state for next mega menu
            $this->mega_menu_items = [];
            $this->current_parent = null;
            $this->current_menu_images = [];
        } elseif ($this->menu_type === 'mobile' && $depth === 0) {
            $output .= "$indent</ul>\n";
        }
    }

    /**
     * Output the end of a menu item (<li> closing tag).
     * Only outputs for modes where needed.
     *
     * @param string $output Output HTML (by reference)
     * @param object $item   Menu item object
     * @param int    $depth  Menu depth
     * @param array  $args   Arguments from wp_nav_menu()
     */
    function end_el(&$output, $item, $depth = 0, $args = array()): void
    {
        if ($this->menu_type === 'simple' || $this->menu_type === 'dropdown') {
            $output .= "</li>\n";
            return;
        }
        if ($this->menu_type === 'mobile') {
            if ($depth <= 1) {
                $output .= "</li>\n";
            }
            return;
        }
        if ($this->menu_type === 'desktop') {
            if ($depth === 0) {
                $output .= "</li>\n";
            }
            return;
        }
    }

    /**
     * Generate SVG icon for menu items with children (dropdown indicator).
     * Alpine.js manages color/rotation states.
     *
     * @return string SVG markup for dropdown indicator
     */
    private function generateDropdownIcon(): string
    {
        $transform_attr = '';
        $fill_attr = '';
        if ($this->menu_type === 'desktop') {
            $transform_attr = "x-bind:style=\"{ transform: isHovered ? 'rotate(180deg)' : 'rotate(0deg)' }\"";
            $fill_attr = "x-bind:fill=\"isHovered ? '" . esc_attr($this->options['desktop_svg_hover_fill']) . "' : '" . esc_attr($this->options['desktop_svg_default_fill']) . "'\"";
        } elseif ($this->menu_type === 'mobile') {
            $transform_attr = "x-bind:style=\"{ transform: open ? 'rotate(180deg)' : 'rotate(0deg)' }\"";
            $fill_attr = 'fill="' . esc_attr($this->options['mobile_svg_default_fill']) . '"';
        }
        return '<svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="h-6 transition-transform duration-300 ease-in-out" ' . $transform_attr . ' style="margin-top: 0 !important; margin-bottom: 0 !important;" aria-hidden="true">
        <g data-name="24x24/On Light/Arrow-Bottom">
        <path fill="none" d="M0 24V0h24v24z"/>
        <path id="svgPath" d="M7.53 9.47a.75.75 0 0 0-1.06 1.06l5 5a.75.75 0 0 0 1.061 0l5-5a.75.75 0 0 0-1.061-1.06L12 13.94Z" ' . $fill_attr . ' />
        </g></svg>';
    }
}
