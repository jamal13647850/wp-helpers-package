<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

class QuickViewManager
{
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        add_action('wp_ajax_load_quick_view_content', [$this, 'handle_quick_view_content']);
        add_action('wp_ajax_nopriv_load_quick_view_content', [$this, 'handle_quick_view_content']);
    }

    public function handle_quick_view_content()
    {
        // چک کردن نانس
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_quick_view_' . $_POST['product_id'])) {
            status_header(403);
            echo '<p>خطای امنیتی: نانس نامعتبر است.</p>';
            wp_die();
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $product = wc_get_product($product_id);

        if (!$product) {
            echo '<p>محصول یافت نشد.</p>';
            wp_die();
        }

        // داده‌ها برای رندر مودال
        $data = [
            'product_id' => $product_id,
            'product_name' => esc_html($product->get_name()),
            'short_description' => wp_kses_post($product->get_short_description()),
            'price_html' => wp_kses_post($product->get_price_html()),
            'image_url' => esc_url(wp_get_attachment_url($product->get_image_id())),
            'permalink' => esc_url($product->get_permalink()),
            'quick_view_nonce' => wp_create_nonce('wc_quick_view_' . $product_id),
        ];

        $view = new \proteam\Cafedentist\View();
        $view->display("@views/components/quickview/quickViewModal.twig", $data);
      

        wp_die();
    }
}