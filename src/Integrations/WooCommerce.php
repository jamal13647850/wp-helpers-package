<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Integrations;

defined('ABSPATH') || exit();

class WooCommerce
{
    public function __construct() {}

    public function getTopLevelCategories(string $defaultImageURL): array
    {
        $args = array(
            'taxonomy'     => 'product_cat',
            'hide_empty'   => true, // if true, only categories with products will be displayed
            'parent'       => 0      // only top-level categories (without parent)
        );

        $top_categories = get_terms($args);

        if (!is_wp_error($top_categories)) {
            foreach ($top_categories as $category) {
                if ($category->name !== 'بدون دسته‌بندی') {
                    $categories[$category->term_id]['id'] = $category->term_id;
                    $categories[$category->term_id]['name'] = $category->name;
                    $categories[$category->term_id]['description'] = $category->description;
                    $categories[$category->term_id]['products_count'] = $category->count;
                    $categories[$category->term_id]['link'] = get_term_link($category);
                    $categories[$category->term_id]['image_url'] = '';

                    $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                    if ($thumbnail_id) {
                        $categories[$category->term_id]['image_url'] = wp_get_attachment_url($thumbnail_id);
                    } else {
                        $categories[$category->term_id]['image_url'] = $defaultImageURL;
                    }
                }
            }
            return $categories;
        }
        return [];
    }

    public function getTopLevelCategoriesWithImages(): array
    {
        $args = array(
            'taxonomy'     => 'product_cat',
            'hide_empty'   => true,
            'parent'       => 0
        );

        $top_categories = get_terms($args);
        $categories = array();

        if (!is_wp_error($top_categories)) {
            foreach ($top_categories as $category) {
                if ($category->name === 'بدون دسته‌بندی') {
                    continue;
                }

                $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                if (!$thumbnail_id) {
                    continue;
                }

                $image_url = wp_get_attachment_url($thumbnail_id);
                if (!$image_url) {
                    continue;
                }

                $categories[$category->term_id] = array(
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'products_count' => $category->count,
                    'link' => get_term_link($category),
                    'image_url' => $image_url
                );
            }
            return $categories;
        }
        return [];
    }


    public function getLatestWooProducts(int $count = 6): array
    {
        $products_array = array();

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => $count,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        );

        $products = new \WP_Query($args);

        if ($products->have_posts()) {
            while ($products->have_posts()) {
                $products->the_post();
                global $product;

                // Get thumbnail URL
                $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'woocommerce_thumbnail');
                if (!$thumbnail_url) {
                    $thumbnail_url = wc_placeholder_img_src('woocommerce_thumbnail');
                }

                // Get product categories
                $categories = get_the_terms($product->get_id(), 'product_cat');
                $category_names = [];
                if ($categories && !is_wp_error($categories)) {
                    foreach ($categories as $category) {
                        $category_names[] = esc_html($category->name);
                    }
                }

                // Product statuses
                $is_on_sale = $product->is_on_sale();
                $is_featured = $product->is_featured();
                $is_free = ($product->get_price() == 0);
                $is_in_stock = $product->is_in_stock();
                $is_variable = $product->is_type('variable');

                // Product static data
                $product_classes = esc_attr(implode(' ', wc_get_product_class('group relative flex flex-col overflow-hidden rounded-lg bg-white transition-all duration-300 hover:shadow-lg sm:min-h-[450px] product', $product)));
                $permalink = esc_url(get_permalink($product->get_id()));
                $product_name = esc_html($product->get_name());
                $price_html = wp_kses_post($product->get_price_html());
                $add_to_cart_text = esc_html($product->add_to_cart_text());

                // Build product data array
                $product_data = array(
                    'id'                => get_the_ID(),
                    'title'             => get_the_title(),
                    'name'              => $product_name,
                    'link'              => $permalink,
                    'image'             => $thumbnail_url,
                    'price'             => array(
                        'regular_price'     => $product->get_regular_price(),
                        'sale_price'        => $product->get_sale_price(),
                        'price'             => $product->get_price(),
                        'formatted_price'   => $price_html,
                    ),
                    'sku'               => $product->get_sku(),
                    'status'            => $product->get_stock_status(),
                    'in_stock'          => $is_in_stock,
                    'category_names'    => $category_names,
                    'is_on_sale'        => $is_on_sale,
                    'is_featured'       => $is_featured,
                    'is_free'           => $is_free,
                    'is_variable'       => $is_variable,
                    'product_classes'   => $product_classes,
                    'add_to_cart_text'  => $add_to_cart_text,
                );

                $products_array[] = $product_data;
            }
            wp_reset_postdata();
        }

        return $products_array;
    }
}
