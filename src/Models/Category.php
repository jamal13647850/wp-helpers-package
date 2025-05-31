<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Models;

defined('ABSPATH') || exit();

use jamal13647850\wphelpers\Language\LanguageManager;

class Category
{
    /**
     * Get HTML link for the first post's core category.
     *
     * @param int $postID
     * @return string
     */
    public function getFirstCategory(int $postID): string
    {
        $cat = '';
        $categories = get_the_category($postID);

        if (!empty($categories)) {
            $lang = LanguageManager::getInstance();
            foreach ($categories as $category) {
                $cat = '<a href="' . esc_url(get_category_link($category->term_id)) . '" alt="' .
                    esc_attr(sprintf($lang->trans('view_all_posts_in'), $category->name)) . '">' .
                    esc_html($category->name) . '</a> ';
                break;
            }
        }
        return $cat;
    }

    /**
     * Get HTML link for the first custom taxonomy term.
     *
     * @param int $postID
     * @param string $customTaxonomy
     * @return string
     */
    public function getFirstCustomCategory(int $postID, string $customTaxonomy): string
    {
        $cat = '';
        $customCategories = get_the_terms($postID, $customTaxonomy);

        if (!empty($customCategories)) {
            $lang = LanguageManager::getInstance();
            foreach ($customCategories as $category) {
                $cat = '<a href="' . esc_url(get_term_link($category->term_id)) . '" alt="' .
                    esc_attr(sprintf($lang->trans('view_all_posts_in'), $category->name)) . '">' .
                    esc_html($category->name) . '</a>';
                break;
            }
        }
        return $cat;
    }

    /**
     * Get all terms for a custom taxonomy.
     *
     * @param string $customTaxonomy
     * @return array
     */
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
