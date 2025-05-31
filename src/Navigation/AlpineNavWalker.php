<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Navigation;

defined('ABSPATH') || exit();

/**
 * Alpine Nav Walker Class
 *
 * Custom walker for WordPress navigation menus with Alpine.js integration
 * Supports both mobile and desktop menu rendering with mega menu functionality
 * Now with customizable options for styling.
 *
 * @author Sayyed Jamal Ghasemi
 * @version 1.6.1 // Incremented version due to fix
 */
class AlpineNavWalker extends \Walker_Nav_Menu {
    private array $mega_menu_items = [];
    // MODIFIED LINE: Changed type from ?object to ?array
    private ?array $current_parent = null; // Property to hold a reference to the current depth 1 mega menu item (which is an array)
    private string $menu_type = 'desktop';
    private array $current_menu_images = [];
    private array $options = [];

    /**
     * Default styling and behavior options.
     * These can be overridden via the $options parameter in the constructor.
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
        'mobile_button_class'           => 'p-2 text-gray-600 hover:text-primary transition-colors',
        'mobile_svg_default_fill'       => '#79528A',
        'submenu_link_class'            => 'block py-2 px-4 text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors',
        'mega_menu_parent_title_class'  => 'hover:text-primary transition-colors duration-300',
        'mega_menu_child_link_class'    => 'text-gray-700 hover:text-primary transition-colors duration-300 block py-1 text-sm hover:bg-gray-50 px-2 rounded',
    ];

    /**
     * Constructor to set menu type and custom options.
     *
     * @param string $type    Menu type: 'desktop', 'mobile', or 'simple'.
     * @param array  $options Optional. An array of options to override default styling and behavior.
     */
    public function __construct(string $type = 'desktop', array $options = []) {
        $this->menu_type = $type;
        $this->options = wp_parse_args($options, $this->default_options);
    }

    /**
     * Check if current menu item has children.
     *
     * @param array $elements Menu elements.
     * @param int   $id       Current item ID.
     * @return bool True if the item has children, false otherwise.
     */
    public function has_children($elements, $id): bool {
        if (empty($elements[$id])) {
            return false;
        }
        // Check if the 'children' property exists and is not empty.
        // WordPress populates this property during the walk_nav_menu_tree call.
        return !empty($elements[$id]->children);
    }

    /**
     * Starts the list before the elements are added.
     * @see Walker::start_el()
     *
     * @param string $output            Passed by reference. Used to append additional content.
     * @param object $item              Menu item data object.
     * @param int    $depth             Depth of menu item. Used for padding.
     * @param array  $args              An array of wp_nav_menu() arguments.
     * @param int    $id                Current item ID.
     */
    function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0): void {
        if ($this->menu_type === 'simple') {
            $this->render_simple_menu_item($output, $item, $depth, $args, $id);
            return;
        }

        if ($this->menu_type === 'mobile' && $depth > 1) {
            return;
        }

        if ($this->menu_type === 'desktop' && $depth >= 1) {
            $this->collect_mega_menu_items($item, $depth);
            return;
        }

        $this->render_menu_item($output, $item, $depth, $args, $id);
    }

    /**
     * Render simple menu item (e.g., for top bar or footer).
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param object $item   Menu item data object.
     * @param int    $depth  Depth of menu item.
     * @param array  $args   An array of wp_nav_menu() arguments.
     * @param int    $id     Current item ID.
     */
    private function render_simple_menu_item(&$output, $item, $depth, $args, $id): void {
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
     * Collect mega menu items for desktop menu.
     * Items at depth 1 become column titles, items at depth 2 become links under them.
     *
     * @param object $item  Menu item data object.
     * @param int    $depth Depth of menu item.
     */
    private function collect_mega_menu_items($item, $depth): void {
        if ($depth === 1) { // This is a top-level item in the mega menu (column header)
            $this->mega_menu_items[] = [
                'title'    => apply_filters('the_title', $item->title, $item->ID),
                'url'      => $item->url,
                'ID'       => $item->ID, // Store ID for potential use
                'target'   => $item->target,
                'attr_title' => $item->attr_title,
                'xfn'      => $item->xfn,
                'children' => [], // Initialize children array
            ];
            // Set current_parent to the last added mega_menu_item by reference
            // This now correctly assigns an array to a property typed as ?array
            $this->current_parent = &$this->mega_menu_items[count($this->mega_menu_items) - 1];
        } elseif ($depth === 2 && $this->current_parent !== null) { // This is a child of a mega menu column header
            // $this->current_parent is an array, so array access is correct.
            $this->current_parent['children'][] = [
                'title'    => apply_filters('the_title', $item->title, $item->ID),
                'url'      => $item->url,
                'ID'       => $item->ID,
                'target'   => $item->target,
                'attr_title' => $item->attr_title,
                'xfn'      => $item->xfn,
            ];
        }
    }

    /**
     * Render main menu item (desktop/mobile).
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param object $item   Menu item data object.
     * @param int    $depth  Depth of menu item.
     * @param array  $args   An array of wp_nav_menu() arguments.
     * @param int    $id     Current item ID.
     */
    private function render_menu_item(&$output, $item, $depth, $args, $id): void {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        $has_children = in_array('menu-item-has-children', $classes);

        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        if ($depth === 0 && $this->menu_type === 'desktop' && function_exists('get_field')) {
            $menu_item_id = $item->ID;
            $mega_menu_images_field = get_field('mega_menu_images', $menu_item_id);
            $this->current_menu_images = is_array($mega_menu_images_field) ? $mega_menu_images_field : [];
        }

        $svg_icon = $has_children ? $this->generateDropdownIcon() : '';

        if ($depth === 0 && $this->menu_type === 'desktop') {
            $output .= $indent . '<li' . $id_attr . $class_names . ' x-data="{ open0: false, isHovered: false }" x-on:mouseenter="open0 = true; isHovered = true" x-on:mouseleave="open0 = false; isHovered = false" class="menu-item-flex">';
        } elseif ($this->menu_type === 'mobile' && $depth === 0 && $has_children) {
            $output .= $indent . '<li' . $id_attr . $class_names . ' x-data="{ open: false }">';
        } else {
            $output .= $indent . '<li' . $id_attr . $class_names . '>';
        }

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
            $item_output .= '<a x-bind:style="{ color: isHovered ? \'' . esc_attr($this->options['desktop_link_hover_color']) . '\' : \'\' }" ' . $attributes . ' class="' . esc_attr($this->options['desktop_link_class']) . '">';
            $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
            if ($svg_icon) {
                $item_output .= $svg_icon;
            }
            $item_output .= '</a>';
        } elseif ($this->menu_type === 'mobile' && $depth === 0) {
            if ($has_children) {
                $item_output .= '<div class="flex items-center justify-between w-full">';
                $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['mobile_link_class']) . '">';
                $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
                $item_output .= '</a>';
                $item_output .= '<button @click="open = !open" class="' . esc_attr($this->options['mobile_button_class']) . '" aria-label="' . esc_attr__('Toggle submenu', 'your-theme-textdomain') . '" aria-expanded="false" x-bind:aria-expanded="open.toString()">' . $svg_icon . '</button>';
                $item_output .= '</div>';
            } else {
                $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['mobile_link_no_children_class']) . '">';
                $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
                $item_output .= '</a>';
            }
        } else {
            $item_output .= '<a ' . $attributes . ' class="' . esc_attr($this->options['submenu_link_class']) . '">';
            $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
            $item_output .= '</a>';
        }

        $item_output .= $args->after ?? '';
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Starts the sub-menu list (<ul> or <div> for mega menu).
     * @see Walker::start_lvl()
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param int    $depth  Depth of menu item. Used for padding.
     * @param array  $args   An array of wp_nav_menu() arguments.
     */
    function start_lvl(&$output, $depth = 0, $args = array()): void {
        if ($this->menu_type === 'simple') {
            return;
        }

        $indent = str_repeat("\t", $depth + 1);

        if ($depth === 0 && $this->menu_type === 'desktop') {
            $output .= "\n$indent<div class=\"mega-menu absolute left-1/2 w-screen bg-white shadow-xl border-t border-gray-200 z-[112]\" style=\"margin-left: calc(-50vw + 50%);\" x-show=\"open0\" x-cloak x-transition:enter=\"transition ease-out duration-300\" x-transition:enter-start=\"opacity-0 transform translate-y-[-10px]\" x-transition:enter-end=\"opacity-100 transform translate-y-0\" x-transition:leave=\"transition ease-in duration-200\" x-transition:leave-start=\"opacity-100 transform translate-y-0\" x-transition:leave-end=\"opacity-0 transform translate-y-[-10px]\" @click.outside=\"open0 = false\">\n";
            $output .= "$indent\t<div class=\"mega-menu-container max-w-7xl mx-auto px-6 py-8\">\n";
            $output .= "$indent\t\t<div class=\"flex flex-row-reverse gap-8\">\n";
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
            $output .= "\n$indent<ul class=\"submenu list-none p-0 m-0 pl-4 bg-gray-50 mt-2 rounded overflow-hidden\" x-show=\"open\" x-cloak x-transition:enter=\"transition-all duration-300 ease-in-out\" x-transition:enter-start=\"opacity-0 max-h-0\" x-transition:enter-end=\"opacity-100 max-h-[100vh]\" x-transition:leave=\"transition-all duration-300 ease-in-out\" x-transition:leave-start=\"opacity-100 max-h-[100vh]\" x-transition:leave-end=\"opacity-0 max-h-0\">\n";
        }
    }

    /**
     * Ends the sub-menu list.
     * @see Walker::end_lvl()
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param int    $depth  Depth of menu item. Used for padding.
     * @param array  $args   An array of wp_nav_menu() arguments.
     */
    function end_lvl(&$output, $depth = 0, $args = array()): void {
        if ($this->menu_type === 'simple') {
            return;
        }

        $indent = str_repeat("\t", $depth + 1);

        if ($depth === 0 && $this->menu_type === 'desktop') {
            foreach ($this->mega_menu_items as $mega_item) {
                $output .= "$indent\t\t\t\t\t<div class=\"mega-menu-section\">\n";
                $parent_atts = [];
                $parent_atts['href'] = !empty($mega_item['url']) ? esc_url($mega_item['url']) : '#';
                if (!empty($mega_item['target'])) $parent_atts['target'] = esc_attr($mega_item['target']);
                if (!empty($mega_item['attr_title'])) $parent_atts['title'] = esc_attr($mega_item['attr_title']);
                if (!empty($mega_item['xfn'])) $parent_atts['rel'] = esc_attr($mega_item['xfn']);

                $parent_attributes_str = '';
                foreach($parent_atts as $attr => $val) {
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
                        foreach($child_atts as $attr => $val) {
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

            $output .= "$indent\t\t\t\t</div>\n"; // Close grid
            $output .= "$indent\t\t\t</div>\n";   // Close mega-menu-content
            $output .= "$indent\t\t</div>\n";       // Close flex container (row-reverse)
            $output .= "$indent\t</div>\n";           // Close mega-menu-container
            $output .= "$indent</div>\n";               // Close mega-menu (main div)

            $this->mega_menu_items = [];
            $this->current_parent = null;
            $this->current_menu_images = [];
        } elseif ($this->menu_type === 'mobile' && $depth === 0) {
            $output .= "$indent</ul>\n";
        }
    }

    /**
     * Ends the element output, if needed.
     * @see Walker::end_el()
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param object $item   Page data object. Not used.
     * @param int    $depth  Depth of page. Not Used.
     * @param array  $args   An array of arguments. Not Used.
     */
    function end_el(&$output, $item, $depth = 0, $args = array()): void {
        if ($this->menu_type === 'simple') {
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
     * Generate dropdown icon SVG.
     * Color and transformation are now controlled by Alpine.js states and options.
     *
     * @return string SVG icon HTML.
     */
    private function generateDropdownIcon(): string {
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