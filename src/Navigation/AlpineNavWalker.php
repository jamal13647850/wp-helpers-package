<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Navigation;

defined('ABSPATH') || exit();


/**
 * Alpine Nav Walker Class
 * 
 * Custom walker for WordPress navigation menus with Alpine.js integration
 * Supports both mobile and desktop menu rendering with mega menu functionality
 * 
 * @author Sayyed Jamal Ghasemi
 * @version 1.5.1
 */
class AlpineNavWalker extends \Walker_Nav_Menu {
    private $mega_menu_items = [];
    private $current_parent = null;
    private $menu_type = 'desktop';
    private $current_menu_images = [];

    /**
     * Constructor to set menu type
     * 
     * @param string $type Menu type: 'desktop', 'mobile', or 'simple'
     */
    public function __construct($type = 'desktop') {
        $this->menu_type = $type;
    }

    /**
     * Check if current menu item has children
     * 
     * @param array $elements Menu elements
     * @param int $id Current item ID
     * @return bool
     */
    public function has_children($elements, $id) {
        if (empty($elements[$id])) {
            return false;
        }
        return !empty($elements[$id]->children);
    }

    /**
     * Starts the list before the elements are added
     */
    function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
        // Simple menu type (for top menu)
        if ($this->menu_type === 'simple') {
            $this->render_simple_menu_item($output, $item, $depth, $args, $id);
            return;
        }

        // Mobile menu: limit depth and use accordion style
        if ($this->menu_type === 'mobile' && $depth > 1) {
            return;
        }

        // Desktop menu: collect mega menu items for levels 1 and 2
        if ($this->menu_type === 'desktop' && $depth >= 1) {
            $this->collect_mega_menu_items($item, $depth);
            return; // Skip rendering level 2 and 3 items in desktop
        }

        $this->render_menu_item($output, $item, $depth, $args, $id);
    }

    /**
     * Render simple menu item (for top menu)
     */
    private function render_simple_menu_item(&$output, $item, $depth, $args, $id) {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;

        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        $output .= $indent . '<li' . $id_attr . $class_names . '>';

        $atts = array();
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target)     ? $item->target     : '';
        $atts['rel']    = !empty($item->xfn)        ? $item->xfn        : '';
        $atts['href']   = !empty($item->url)        ? $item->url        : '';

        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $item_output = $args->before ?? '';
        $item_output .= '<a ' . $attributes . ' class="text-secondary hover:text-primary transition-colors text-nowrap">';
        $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
        $item_output .= '</a>';
        $item_output .= $args->after ?? '';

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Collect mega menu items for desktop menu
     */
    private function collect_mega_menu_items($item, $depth) {
        if ($depth === 1) {
            $this->mega_menu_items[] = [
                'title' => apply_filters('the_title', $item->title, $item->ID),
                'url' => $item->url,
                'children' => [],
            ];
            $this->current_parent = &$this->mega_menu_items[count($this->mega_menu_items) - 1];
        } elseif ($depth === 2 && $this->current_parent !== null) {
            $this->current_parent['children'][] = [
                'title' => apply_filters('the_title', $item->title, $item->ID),
                'url' => $item->url,
            ];
        }
    }

    /**
     * Render main menu item (desktop/mobile)
     */
    private function render_menu_item(&$output, $item, $depth, $args, $id) {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;

        // Check if item has children
        $has_children = in_array('menu-item-has-children', $classes);

        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        // Get mega menu images for top-level items in desktop
        if ($depth === 0 && $this->menu_type === 'desktop') {
            $menu_item_id = $item->ID;
            $mega_menu_images = get_field('mega_menu_images', $menu_item_id);
            $this->current_menu_images = $mega_menu_images ? $mega_menu_images : [];
        }

        // SVG icon for dropdown
        $svg = $has_children ? $this->generateDropdownIcon() : '';

        // Desktop menu: add hover behavior for top-level items
        if ($depth === 0 && $this->menu_type === 'desktop') {
            $output .= $indent . '<li' . $id_attr . $class_names . ' x-data="{ open0: false, isHovered: false }"   
                x-on:mouseenter="open0 = true; isHovered = true"   
                x-on:mouseleave="open0 = false; isHovered = false" class="menu-item-flex">';
        } elseif ($this->menu_type === 'mobile' && $depth === 0 && $has_children) {
            // Mobile menu: add accordion behavior
            $output .= $indent . '<li' . $id_attr . $class_names . ' x-data="{ open: false }">';
        } else {
            $output .= $indent . '<li' . $id_attr . $class_names . '>';
        }

        // Build link attributes
        $atts = array();
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target)     ? $item->target     : '';
        $atts['rel']    = !empty($item->xfn)        ? $item->xfn        : '';
        $atts['href']   = !empty($item->url)        ? $item->url        : '';

        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $item_output = $args->before ?? '';

        // Different link rendering for desktop vs mobile
        if ($depth === 0 && $this->menu_type === 'desktop') {
            // Desktop menu with flexible sizing
            $item_output .= '<a x-bind:style="{ color: isHovered ? \'#F25A04\' : \'\' }" ' . $attributes . ' class="flex items-center gap-1 py-4 px-1 text-gray-800 hover:text-primary transition-colors text-nowrap text-sm lg:text-xs xl:text-sm">';
            $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
            if ($svg) {
                $item_output .= $svg;
            }
            $item_output .= '</a>';
        } elseif ($this->menu_type === 'mobile' && $depth === 0) {
            if ($has_children) {
                $item_output .= '<div class="flex items-center justify-between w-full">';
                $item_output .= '<a ' . $attributes . ' class="flex-1 py-3 text-secondary hover:text-primary transition-colors  text-sm sm:text-base">';
                $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
                $item_output .= '</a>';
                $item_output .= '<button @click="open = !open" class="p-2 text-gray-600 hover:text-primary transition-colors" aria-label="Toggle submenu">' . $svg . '</button>';
                $item_output .= '</div>';
            } else {
                $item_output .= '<a ' . $attributes . ' class="block py-3 text-seconadary hover:text-primary transition-colors  text-sm sm:text-base">';
                $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
                $item_output .= '</a>';
            }
        } else {
            $item_output .= '<a ' . $attributes . ' class="block py-2 px-4 text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors">';
            $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
            $item_output .= '</a>';
        }

        $item_output .= $args->after ?? '';
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Starts the sub-menu list before the elements are added
     */
    function start_lvl(&$output, $depth = 0, $args = array()) {
        // Skip for simple menu type
        if ($this->menu_type === 'simple') {
            return;
        }

        $indent = str_repeat("\t", $depth);
    
        if ($depth === 0 && $this->menu_type === 'desktop') {
            // Full-width mega menu with absolute positioning
            $output .= "\n$indent<div class=\"mega-menu absolute left-1/2 w-screen bg-white shadow-xl border-t border-gray-200 z-[112]\" style=\"margin-left: calc(-50vw + 50%);\" x-show=\"open$depth\" x-transition:enter=\"transition ease-out duration-300\" x-transition:enter-start=\"opacity-0 transform translate-y-[-10px]\" x-transition:enter-end=\"opacity-100 transform translate-y-0\" x-transition:leave=\"transition ease-in duration-200\" x-transition:leave-start=\"opacity-100 transform translate-y-0\" x-transition:leave-end=\"opacity-0 transform translate-y-[-10px]\">\n";
            
            // Container with max-width for content
            $output .= "$indent<div class=\"mega-menu-container max-w-7xl mx-auto px-6 py-8\">\n";
            $output .= "$indent<div class=\"flex flex-row-reverse gap-8\">\n";
    
            // Render mega menu images if available
            if (!empty($this->current_menu_images)) {
                $output .= "$indent<div class=\"mega-menu-images flex flex-col gap-4 w-1/3\">\n";
                foreach ($this->current_menu_images as $image) {
                    $image_url = esc_url($image['image']);
                    $image_alt = esc_attr($image['alt'] ?? 'Mega Menu Image');
                    $output .= "$indent<div class=\"image-container flex-1\">\n";
                    $output .= "$indent<img src=\"$image_url\" alt=\"$image_alt\" class=\"w-full h-[300px] rounded-lg shadow-md object-cover hover:shadow-lg transition-shadow duration-300\" loading=\"lazy\" />\n";
                    $output .= "$indent</div>\n";
                }
                $output .= "$indent</div>\n";
            }
    
            // Content area
            $output .= "$indent<div class=\"mega-menu-content flex-1\">\n";
            $output .= "$indent<div class=\"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8\">\n";
        } elseif ($this->menu_type === 'mobile') {
            // Mobile submenu with accordion
            $output .= "\n$indent<ul class=\"submenu list-none p-0 m-0 pl-4 bg-gray-50 mt-2 rounded overflow-hidden\" x-show=\"open\" x-transition:enter=\"transition-all duration-300 ease-in-out\" x-transition:enter-start=\"opacity-0 max-h-0\" x-transition:enter-end=\"opacity-100 max-h-screen\" x-transition:leave=\"transition-all duration-300 ease-in-out\" x-transition:leave-start=\"opacity-100 max-h-screen\" x-transition:leave-end=\"opacity-0 max-h-0\">\n";
        }
    }

    /**
     * Ends the sub-menu list after the elements are added
     */
    function end_lvl(&$output, $depth = 0, $args = array()) {
        // Skip for simple menu type
        if ($this->menu_type === 'simple') {
            return;
        }

        $indent = str_repeat("\t", $depth);

        if ($depth === 0 && $this->menu_type === 'desktop') {
            // Render mega menu items
            foreach ($this->mega_menu_items as $mega_item) {
                $output .= "$indent<div class=\"mega-menu-section\">\n";
                $output .= "$indent<h3 class=\"mega-menu-title text-xl font-bold text-secondary mb-4 pb-2 border-b border-gray-200\">\n";
                $output .= "$indent<a href=\"" . esc_url($mega_item['url']) . "\" class=\"hover:text-primary transition-colors duration-300\">" . esc_html($mega_item['title']) . "</a>\n";
                $output .= "$indent</h3>\n";
                
                if (!empty($mega_item['children'])) {
                    $output .= "$indent<ul class=\"mega-menu-items list-none p-0 m-0 space-y-2\">\n";
                    foreach ($mega_item['children'] as $child) {
                        $output .= "$indent<li>\n";
                        $output .= "$indent<a href=\"" . esc_url($child['url']) . "\" class=\"text-gray-700 hover:text-primary transition-colors duration-300 block py-1 text-sm hover:bg-gray-50 px-2 rounded\">" . esc_html($child['title']) . "</a>\n";
                        $output .= "$indent</li>\n";
                    }
                    $output .= "$indent</ul>\n";
                }
                $output .= "$indent</div>\n";
            }

            $output .= "$indent</div>\n"; // Close grid
            $output .= "$indent</div>\n"; // Close mega-menu-content
            $output .= "$indent</div>\n"; // Close flex container
            $output .= "$indent</div>\n"; // Close mega-menu-container
            $output .= "$indent</div>\n"; // Close mega-menu

            // Reset mega menu data for next menu
            $this->mega_menu_items = [];
            $this->current_parent = null;
            $this->current_menu_images = [];
        } elseif ($this->menu_type === 'mobile') {
            $output .= "$indent</ul>\n";
        }
    }

    /**
     * Ends the list after the elements are added
     */
    function end_el(&$output, $item, $depth = 0, $args = array()) {
        if ($this->menu_type === 'simple' || $depth === 0 || $this->menu_type === 'mobile') {
            $output .= "</li>\n";
        }
    }

    /**
     * Generate dropdown icon SVG
     * 
     * @return string SVG icon HTML
     */
    private function generateDropdownIcon(): string {
        $transform_attr = $this->menu_type === 'desktop' ? 
            "x-bind:style=\"{ transform: isHovered ? 'rotate(180deg)' : 'rotate(0deg)' }\"" :
            "x-bind:style=\"{ transform: open ? 'rotate(180deg)' : 'rotate(0deg)' }\"";

        $fill_attr = $this->menu_type === 'desktop' ?
            "x-bind:fill=\"isHovered ? '#F25A04' : '#79528A'\"" :
            'fill="#79528A"';

        return '<svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="h-6 transition-transform duration-300 ease-in-out" ' . $transform_attr . ' style="margin-top: 0 !important; margin-bottom: 0 !important;">
        <g data-name="24x24/On Light/Arrow-Bottom">
        <path fill="none" d="M0 24V0h24v24z"/>
        <path id="svgPath" d="M7.53 9.47a.75.75 0 0 0-1.06 1.06l5 5a.75.75 0 0 0 1.061 0l5-5a.75.75 0 0 0-1.061-1.06L12 13.94Z" ' . $fill_attr . ' />
        </g></svg>';
    }
}
