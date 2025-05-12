<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Managers;

defined('ABSPATH') || exit();

class CartManager
{
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        add_action('wp_ajax_add_to_cart_ajax', [$this, 'handle_add_to_cart_ajax']);
        add_action('wp_ajax_nopriv_add_to_cart_ajax', [$this, 'handle_add_to_cart_ajax']);


        add_action('wp_ajax_add_to_cart_single_ajax', [$this, 'handle_add_to_cart_single_ajax']);
        add_action('wp_ajax_nopriv_add_to_cart_single_ajax', [$this, 'handle_add_to_cart_single_ajax']);
    }

    public function handle_add_to_cart_ajax()
    {
        $view = new \proteam\Cafedentist\View();
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        // چک کردن نانس
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_add_to_cart_' . $product_id)) {
            $data = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'product_id' => $product_id,
                'nonce' => wp_create_nonce('wc_add_to_cart_' . $product_id),
                'error' => 'خطای امنیتی: نانس نامعتبر'
            ];
            $view->display('@views/components/addToCartButton.twig', $data);
            wp_die();
        }

        if (!$product_id) {
            $data = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'product_id' => $product_id,
                'nonce' => wp_create_nonce('wc_add_to_cart_' . $product_id),
                'error' => 'شناسه محصول نامعتبر است'
            ];
            $view->display('@views/components/addToCartButton.twig', $data);
            wp_die();
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            $data = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'product_id' => $product_id,
                'nonce' => wp_create_nonce('wc_add_to_cart_' . $product_id),
                'error' => 'محصول یافت نشد'
            ];
            $view->display('@views/components/addToCartButton.twig', $data);
            wp_die();
        }

        // چک کردن نوع محصول
        if ($product->is_type('variable')) {
            // برای محصولات متغیر، دکمه به صفحه محصول لینک می‌شه
            $data = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'product_id' => $product_id,
                'nonce' => wp_create_nonce('wc_add_to_cart_' . $product_id),
                'permalink' => esc_url($product->get_permalink()),
                'is_variable' => true
            ];
            $view->display('@views/components/addToCartButton.twig', $data);
        } else {
            // برای محصولات ساده، عملیات Ajax انجام می‌شه
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
            $added = WC()->cart->add_to_cart($product_id, $quantity);

            if ($added) {
                $cart_count = WC()->cart->get_cart_contents_count();
                $data = [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'product_id' => $product_id,
                    'nonce' => wp_create_nonce('wc_add_to_cart_' . $product_id),
                    'cart_count' => $cart_count,
                    'message' => 'محصول با موفقیت به سبد اضافه شد!',
                    'is_variable' => false
                ];
                $view->display('@views/components/addToCartButton.twig', $data);
            } else {
                $data = [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'product_id' => $product_id,
                    'nonce' => wp_create_nonce('wc_add_to_cart_' . $product_id),
                    'error' => 'خطا در افزودن به سبد خرید',
                    'is_variable' => false
                ];
                $view->display('@views/components/addToCartButton.twig', $data);
            }
        }

        wp_die();
    }


    function handle_add_to_cart_single_ajax() {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
        $attributes = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'attribute_') === 0) {
                $attributes[$key] = sanitize_text_field($value);
            }
        }
    
        // بررسی اولیه
        if (!$product_id) {
            echo '<span class="text-red-500">خطا: شناسه محصول نامعتبر است.</span>';
            wp_die();
        }
    
        $product = wc_get_product($product_id);
        if (!$product) {
            echo '<span class="text-red-500">خطا: محصول یافت نشد.</span>';
            wp_die();
        }
    
        // اعتبارسنجی و افزودن به سبد
        $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $attributes);
    
        if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $attributes)) {
            $cart_url = wc_get_cart_url();
            $message = '<div class="text-green-500">محصول با موفقیت به سبد خرید اضافه شد!</div>';
            $message .= '<a href="' . esc_url($cart_url) . '" class="inline-block mt-2 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors">مشاهده سبد خرید</a>';
        } else {
            // دریافت خطاهای خاص از WooCommerce
            $notices = wc_get_notices('error');
            wc_clear_notices(); // پاک کردن اعلان‌ها بعد از دریافت
    
            if (empty($notices)) {
                $message = '<span class="text-red-500">خطا در افزودن به سبد خرید. لطفاً دوباره تلاش کنید.</span>';
            } else {
                $message = '';
                foreach ($notices as $notice) {
                    switch (true) {
                        case strpos($notice['notice'], 'required') !== false || strpos($notice['notice'], 'variation') !== false:
                            $message .= '<span class="text-red-500">لطفاً ویژگی‌های محصول را انتخاب کنید.</span>';
                            break;
                        case strpos($notice['notice'], 'stock') !== false:
                            $message .= '<span class="text-red-500">موجودی محصول در انبار کافی نیست.</span>';
                            break;
                        default:
                            $message .= '<span class="text-red-500">' . wp_kses_post($notice['notice']) . '</span>';
                            break;
                    }
                }
            }
        }
    
        echo $message;
        wp_die();
    }
}
