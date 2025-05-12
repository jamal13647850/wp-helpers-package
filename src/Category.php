<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();


class Category
{
    public function getFirstCategory(int $postID): string
    {
        $cat = '';
        $categories = get_the_category($postID);
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $cat = '<a href="' . esc_url(get_category_link($category->term_id)) . '" alt="' . esc_attr(sprintf(__('View all posts in %s', 'textdomain'), $category->name)) . '">' . esc_html($category->name) . '</a> ';
                break;
            }
        }
        return $cat;
    }

    public function getFirstCustomCategory(int $postID, string $customTaxonomy): string
    {
        $cat = '';
        $customCategories = get_the_terms($postID, $customTaxonomy);
        if (!empty($customCategories)) {
            foreach ($customCategories as $category) {
                $cat = '<a href="' . esc_url(get_term_link($category->term_id)) . '" alt="' . esc_attr(sprintf(__('View all posts in %s', 'textdomain'), $category->name)) . '">' . esc_html($category->name) . '</a>';
                break;
            }
        }
        return $cat;
    }




    public function getAllCPTTaxonomy(string $customTaxonomy): array
    {
        $args = array(
            'taxonomy' => $customTaxonomy,
            'hide_empty' => false,
        );

        $product_categories = get_terms($args);

        if (! empty($product_categories) && ! is_wp_error($product_categories)) {
            return $product_categories;
        } else {
            return [];
        }
    }



    
}
