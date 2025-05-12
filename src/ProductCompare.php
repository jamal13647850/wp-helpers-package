<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

class ProductCompare
{
    private $products = [];

    public function __construct()
    {
        add_action('wp_ajax_add_to_compare', [$this, 'add_to_compare']);
        add_action('wp_ajax_nopriv_add_to_compare', [$this, 'add_to_compare']);
        add_action('wp_ajax_remove_from_compare', [$this, 'remove_from_compare']);
        add_action('wp_ajax_nopriv_remove_from_compare', [$this, 'remove_from_compare']);
    }



    public function get_compare_products($compare_ids = [])
    {
        if(empty($compare_ids)){
            $compare_ids = isset($_COOKIE['compare_products']) ? json_decode(stripslashes($_COOKIE['compare_products']), true) : [];
        }
        
        $products = [];



        foreach ($compare_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $products[] = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price_html(),
                    'image' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
                    'description' => $product->get_short_description(),
                    'attributes' => $this->get_product_attributes($product),
                    'stock_status' => $product->get_stock_status(),
                    'add_to_cart_url' => $product->add_to_cart_url()
                ];
            }
        }

        return $products;
    }

    private function get_product_attributes($product)
    {
        $attributes = [];
        $product_attributes = $product->get_attributes();

        foreach ($product_attributes as $attribute) {
            if ($attribute->get_visible()) {
                $name = wc_attribute_label($attribute->get_name());
                $values = [];

                if ($attribute->is_taxonomy()) {
                    $terms = wp_get_post_terms($product->get_id(), $attribute->get_name(), 'all');
                    foreach ($terms as $term) {
                        $values[] = $term->name;
                    }
                } else {
                    $values = $attribute->get_options();
                }

                $attributes[$name] = implode(', ', $values);
            }
        }

        return $attributes;
    }

    public function add_to_compare()
    {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $compare_ids = isset($_COOKIE['compare_products']) ? json_decode(stripslashes($_COOKIE['compare_products']), true) : [];

        if (!in_array($product_id, $compare_ids)) {
            $compare_ids[] = $product_id;
            setcookie('compare_products', json_encode($compare_ids), time() + (86400 * 30), '/');
            $compare_ids = isset($_COOKIE['compare_products']) ? json_decode(stripslashes($_COOKIE['compare_products']), true) : [];
        }
        $view = new \proteam\Cafedentist\View();
        $view->display("@views/components/compare/compare-button.twig", [
            'ajax_url' => admin_url('admin-ajax.php'),
            'product_id' => $product_id,
            'compare_list' => $compare_ids,
        ]);
        die();
    }

    public function remove_from_compare()
    {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $compare_ids = isset($_COOKIE['compare_products']) ? json_decode(stripslashes($_COOKIE['compare_products']), true) : [];

        $compare_ids = array_diff($compare_ids, [$product_id]);
        setcookie('compare_products', json_encode($compare_ids), time() + (86400 * 30), '/');


        $view = new \proteam\Cafedentist\View();
        $products = $this->get_compare_products($compare_ids);
        $view->display("@views/components/compare/compare.twig", [
            'products' => $products,
            'ajax_url' => admin_url('admin-ajax.php'),
            'shop_url' => get_permalink(wc_get_page_id('shop'))
        ]);
        die();
    }
}
