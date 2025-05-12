<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

class AlpineNavWalker extends \Walker_Nav_Menu {
    private $mega_menu_items = [];
    private $current_parent = null;
    private $is_mobile = false;
    private $current_menu_images = []; // برای ذخیره تصاویر مگا منو

    public function __construct() {
        // بررسی اینکه آیا در موبایل هستیم یا خیر
        $this->is_mobile = wp_is_mobile();
    }

    function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
        // اگر در موبایل هستیم و عمق بیشتر از 1 است، مگا منو را غیرفعال می‌کنیم
        if ($this->is_mobile && $depth > 1) {
            return;
        }

        // اگر در دسکتاپ هستیم و عمق بیشتر از 0 است، آیتم‌ها را برای مگا منو جمع‌آوری می‌کنیم
        if (!$this->is_mobile && $depth >= 1) {
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
            return; // از رندر آیتم‌های سطح دوم و سوم در دسکتاپ جلوگیری می‌کنیم
        }

        $indent = ($depth) ? str_repeat("\t", $depth) : '';

        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;

        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';

        // دریافت تصاویر مگا منو برای آیتم سطح اول
        if ($depth === 0 && !$this->is_mobile) {
            $menu_item_id = $item->ID;
            $mega_menu_images = get_field('mega_menu_images', $menu_item_id); // دریافت فیلد Repeater از ACF
            $this->current_menu_images = $mega_menu_images ? $mega_menu_images : [];
        }

        $svg = '<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="h-6 transition-transform duration-300 ease-in-out" x-bind:style="{ transform: isHovered ? \'rotate(180deg)\' : \'rotate(0deg)\' }" style="margin-top: 0 !important; margin-bottom: 0 !important;">';
        $svg .= '<g data-name="24x24/On Light/Arrow-Bottom">';
        $svg .= '<path fill="none" d="M0 24V0h24v24z"/>';
        $svg .= '<path id="svgPath" d="M7.53 9.47a.75.75 0 0 0-1.06 1.06l5 5a.75.75 0 0 0 1.061 0l5-5a.75.75 0 0 0-1.061-1.06L12 13.94Z" fill="white" x-bind:fill="isHovered ? \'#ff0000\' : \'#000000\'" />';
        $svg .= '</g></svg>';

        $has_children = !empty($args->walker->has_children) ? $svg : '';

        // فقط برای منوی سطح اول در دسکتاپ، رفتار hover را اضافه می‌کنیم
        if ($depth === 0 && !$this->is_mobile) {
            $output .= $indent . '<li' . $id . $class_names . ' x-data="{ open0: false, isHovered: false }"   
                x-on:mouseenter="open0 = true; isHovered = true"   
                x-on:mouseleave="open0 = false; isHovered = false">';
        } else {
            $output .= $indent . '<li' . $id . $class_names . '>';
        }

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

        $item_output = $args->before;
        if ($depth === 0 && !$this->is_mobile) {
            $item_output .= '<a x-bind:style="{ color: isHovered ? \'#F25A04\' : \'\' }" ' . $attributes . '>';
        } else {
            $item_output .= '<a ' . $attributes . '>';
        }
        $item_output .= $args->link_before . apply_filters('the_title', $item->title, $item->ID) . ($depth === 0 && !$this->is_mobile ? $has_children : '') . $args->link_after;
        $item_output .= '</a>';
        $item_output .= $args->after;

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    function start_lvl(&$output, $depth = 0, $args = array()) {
        $indent = str_repeat("\t", $depth);
    
        // فقط برای سطح اول در دسکتاپ، مگا منو را رندر می‌کنیم
        if ($depth === 0 && !$this->is_mobile) {
            $output .= "\n$indent<div class=\"mega-menu z-[112] p-4 bg-white absolute left-0 w-full\" x-show=\"open$depth\" x-transition:enter=\"transition ease-out duration-200\" x-transition:enter-start=\"opacity-0 transform scale-95\" x-transition:enter-end=\"opacity-100 transform scale-100\" x-transition:leave=\"transition ease-in duration-150\" x-transition:leave-start=\"opacity-100 transform scale-100\" x-transition:leave-end=\"opacity-0 transform scale-95\">\n";
            $output .= "$indent<div class=\"mega-menu-container flex flex-row-reverse gap-8 max-w-screen-xl mx-auto py-4\">\n";
    
            // اگر تصاویر وجود داشته باشند، آن‌ها را نمایش می‌دهیم
            if (!empty($this->current_menu_images)) {
                $output .= "$indent<div class=\"mega-menu-images flex flex-row gap-4 h-[250px] self-center w-2/3\">\n";
                foreach ($this->current_menu_images as $image) {
                    $image_url = esc_url($image['image']);
                    $output .= "$indent<img src=\"$image_url\" alt=\"Mega Menu Image\" class=\"  rounded-lg shadow-md\" />\n";
                }
                $output .= "$indent</div>\n";
            }
    
            // محتوای اصلی مگا منو
            $output .= "$indent<div class=\"mega-menu-content flex flex-wrap gap-8 justify-start py-4\">\n";
        } elseif ($this->is_mobile) {
            $output .= "\n$indent<ul class=\"submenu list-none p-0 m-0 pl-4\">\n";
        }
    }

    function end_lvl(&$output, $depth = 0, $args = array()) {
        $indent = str_repeat("\t", $depth);

        if ($depth === 0 && !$this->is_mobile) {
            // رندر آیتم‌های مگا منو
            foreach ($this->mega_menu_items as $mega_item) {
                $output .= "$indent<div class=\"mega-menu-section flex flex-col min-w-[200px]\">\n";
                $output .= "$indent<h3 class=\"mega-menu-title text-lg font-bold text-secondary mb-2\"><a href=\"" . esc_url($mega_item['url']) . "\">" . esc_html($mega_item['title']) . "</a></h3>\n";
                if (!empty($mega_item['children'])) {
                    $output .= "$indent<ul class=\"mega-menu-items list-none p-0 m-0\">\n";
                    foreach ($mega_item['children'] as $child) {
                        $output .= "$indent<li class=\"py-1\"><a href=\"" . esc_url($child['url']) . "\" class=\"text-sm text-gray-700 hover:text-[#F25A04]\">" . esc_html($child['title']) . "</a></li>\n";
                    }
                    $output .= "$indent</ul>\n";
                }
                $output .= "$indent</div>\n";
            }
            $output .= "$indent</div>\n"; // بستن mega-menu-content
            $output .= "$indent</div>\n"; // بستن mega-menu-container
            $output .= "$indent</div>\n"; // بستن mega-menu
            // پاک کردن آیتم‌های مگا منو و تصاویر برای منوی بعدی
            $this->mega_menu_items = [];
            $this->current_parent = null;
            $this->current_menu_images = [];
        } elseif ($this->is_mobile) {
            $output .= "$indent</ul>\n";
        }
    }

    function end_el(&$output, $item, $depth = 0, $args = array()) {
        // فقط برای آیتم‌های سطح اول یا در موبایل تگ </li> را اضافه می‌کنیم
        if ($depth === 0 || $this->is_mobile) {
            $output .= "</li>\n";
        }
    }
}