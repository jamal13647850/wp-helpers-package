<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Managers;

defined('ABSPATH') || exit();

class WishlistManager
{
    private $wpdb;
    private $table_name;
    private static $table_version = '2.0';
    private $cache_group = 'wishlist_manager';
    private $cache_time = 3600;


    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'custom_wishlist';


        add_action('wp_ajax_toggle_wishlist', [$this, 'handle_toggle_wishlist']);
        add_action('wp_ajax_nopriv_toggle_wishlist', [$this, 'handle_toggle_wishlist']);
        add_action('wp_ajax_remove_from_wishlist', [$this, 'handle_remove_from_wishlist']);
        add_action('wp_ajax_nopriv_remove_from_wishlist', [$this, 'handle_remove_from_wishlist']);
        $this->maybe_create_table();
    }

    public function getWishlistData()
    {
        if (!is_user_logged_in()) {
            return [
                'is_logged_in' => false,
                'items' => []
            ];
        }

        $table_name = $this->wpdb->prefix . 'custom_wishlist';
        $user_id = get_current_user_id();

        $wishlist_items = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT product_id FROM $table_name WHERE user_id = %d",
            $user_id
        ));

        $products = [];
        foreach ($wishlist_items as $item) {
            $product = wc_get_product($item->product_id);
            if (!$product) continue;

            $products[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price_html' => $product->get_price_html(),
                'permalink' => $product->get_permalink(),
                'image' => $product->get_image('woocommerce_thumbnail'),
                'add_to_cart_url' => $product->add_to_cart_url(),
                'is_in_stock' => $product->is_in_stock(),
                'nonce' => wp_create_nonce('wishlist-nonce')
            ];
        }

        return [
            'is_logged_in' => true,
            'items' => $products,
            'ajax_url' => admin_url('admin-ajax.php')
        ];
    }


    public function check_if_product_is_wishlisted($product_id)
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();

        // $result = $this->wpdb->get_var($this->wpdb->prepare(
        //     "SELECT COUNT(*) FROM {$this->table_name} 
        //     WHERE user_id = %d AND product_id = %d",
        //     $user_id,
        //     $product_id
        // ));

        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT EXISTS(SELECT 1 FROM {$this->table_name} WHERE user_id = %d AND product_id = %d)",
            $user_id,
            $product_id
        ));

        return (bool) $result;
    }





    public function toggle_wishlist($product_id)
    {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'message' => 'لطفا ابتدا وارد شوید.'
            ];
        }

        $user_id = get_current_user_id();

        if (!wc_get_product($product_id)) {
            return [
                'success' => false,
                'message' => 'محصول یافت نشد.'
            ];
        }

        if ($this->check_if_product_is_wishlisted($product_id)) {
            $result = $this->wpdb->delete(
                $this->table_name,
                [
                    'user_id' => $user_id,
                    'product_id' => $product_id
                ],
                ['%d', '%d']
            );

            if ($result !== false) {
                return [
                    'success' => true,
                    'action' => 'removed',
                    'message' => 'محصول از لیست علاقه‌مندی‌ها حذف شد.'
                ];
            }
        } else {
            $result = $this->wpdb->insert(
                $this->table_name,
                [
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                    'date_added' => current_time('mysql')
                ],
                ['%d', '%d', '%s']
            );

            if ($result !== false) {
                return [
                    'success' => true,
                    'action' => 'added',
                    'message' => 'محصول به لیست علاقه‌مندی‌ها اضافه شد.'
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'خطایی رخ داد. لطفا دوباره تلاش کنید.'
        ];
    }


    private function maybe_create_table()
    {
        $version = get_option('wishlist_table_version');

        if ($version && version_compare($version, self::$table_version, '>=')) {
            return;
        }

        $this->create_table();

        update_option('wishlist_table_version', self::$table_version);
    }

    private static function create_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'custom_wishlist';

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id int UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            date_added timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            INDEX user_id_idx (user_id),
            UNIQUE KEY user_product (user_id,product_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX product_id_idx (product_id)");
    }


    public function get_wishlist_count()
    {
        if (!is_user_logged_in()) {
            return 0;
        }

        $user_id = get_current_user_id();

        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));
    }


    public function handle_toggle_wishlist()
    {
        $view = new \jamal13647850\wphelpers\Views\View();
        check_ajax_referer('wishlist-nonce', 'security');

        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

        if (!$product_id) {
            wp_send_json_error('شناسه محصول نامعتبر است.');
        }


        $result = $this->toggle_wishlist($product_id);

        if ($result['success']) {
            $data = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'product_id' => $product_id,
                'nonce' => wp_create_nonce('wishlist-nonce'),
                'is_wishlisted' => $result['action'] === 'added'
            ];



            $view->display("@views/components/wishlist/wishlistButton.twig", $data);
            die();
        } else {
            $data = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'product_id' => $product_id,
                'nonce' => wp_create_nonce('wishlist-nonce'),
                'is_wishlisted' => $result['action'] === 'added'
            ];



            $view->display("@views/components/wishlist/wishlistButton.twig", $data);
            die();
        }
    }




    public function handle_remove_from_wishlist()
    {
        $view = new \jamal13647850\wphelpers\Views\View();





        check_ajax_referer('wishlist-nonce', 'security');

        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

        if (!$product_id) {
            wp_send_json_error('شناسه محصول نامعتبر است.');
        }


        $result = $this->toggle_wishlist($product_id);

        if ($result['success']) {
            $data = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'product_id' => $product_id,
                'nonce' => wp_create_nonce('wishlist-nonce'),
                'is_wishlisted' => $result['action'] === 'added'
            ];



            $view->display("@views/components/wishlist/wishlist.twig", $this->getWishlistData());
            die();
        } else {
            $data = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'product_id' => $product_id,
                'nonce' => wp_create_nonce('wishlist-nonce'),
                'is_wishlisted' => $result['action'] === 'added'
            ];



            $view->display("@views/components/wishlist/wishlist.twig", $this->getWishlistData());
            die();
        }
    }
}
