<?php
/*
 * This utility adds a “Clear Theme Internal Cache” button to the WordPress admin bar
 * and provides an AJAX handler to remove all transients stored in the database.
 *
 * To use it, instantiate the class somewhere in your
 * plugin or theme (for example in your theme’s functions.php). Once
 * instantiated, administrators will see a broom icon in the admin bar that
 * triggers the cache flush via AJAX.
 *
 * Usage example:
 *
 *     use jamal13647850\wphelpers\Utilities\Clear_Theme_Cache;
 *     new Clear_Theme_Cache();
 *
 * @package    wp-helpers-package
 * @author     Sayyed Jamal Ghasemi
 * @link       https://jamalghasemi.com
 * @since      1.0.0
 */

namespace jamal13647850\wphelpers\Utilities;

// Bail out if WordPress core is not loaded
if (!defined('ABSPATH')) {
    return;
}

/**
 * Adds a “Clear Theme Internal Cache” admin‑bar button and handles cache clearing.
 *
 * When instantiated, this class registers callbacks to add a node to the admin bar for
 * users with the `manage_options` capability. Clicking the button triggers a
 * JavaScript function that makes an AJAX request to delete all transients from
 * the database and flush the object cache. A small JavaScript snippet is
 * printed in both the admin and front‑end footers to define the handler.
 *
 * All user‑facing text is in Persian to match the original implementation.
 */
class Clear_Theme_Cache
{
    /**
     * Constructor. Registers hooks for the admin bar, JavaScript, and AJAX handler.
     */
    public function __construct()
    {
        // Only register UI elements for users who can see the admin bar and have permission
        if (is_admin_bar_showing() && current_user_can('manage_options')) {
            add_action('admin_bar_menu', [$this, 'addAdminBarButton'], 100);
            add_action('admin_footer', [$this, 'printInlineScript']);
            add_action('wp_footer', [$this, 'printInlineScript']);
            add_action('wp_head', [$this, 'printAjaxUrl']);
        }

        // Register the AJAX handler for both logged‑in (privileged) users
        add_action('wp_ajax_jg_clear_theme_cache', [$this, 'handleAjax']);
    }

    /**
     * Adds the “Clear Theme Internal Cache” node to the admin bar.
     *
     * @param \WP_Admin_Bar $wp_admin_bar The admin bar object used to add nodes.
     */
    public function addAdminBarButton($wp_admin_bar): void
    {
        $args = [
            'id'    => 'jg-clear-theme-cache',
            'title' => '🧹 پاکسازی کش داخلی پوسته', // Persian: “Clear Theme Internal Cache”
            'href'  => '#',
            'meta'  => [
                'title'   => 'تمام کش‌های داخلی پوسته (Transient) حذف خواهد شد',
                'onclick' => 'jgClearThemeCache(); return false;',
            ],
        ];
        $wp_admin_bar->add_node($args);
    }

    /**
     * Outputs a small JavaScript function to handle the AJAX request.
     *
     * This script is printed in the admin and front‑end footers. It prompts the
     * user for confirmation, then posts to admin‑ajax.php with a nonce. The
     * response is displayed to the user via an alert.
     */
    public function printInlineScript(): void
    {
        // Use heredoc syntax for clarity; the nonce is generated on each output
        $nonce = wp_create_nonce('jg_clear_theme_cache');
        echo <<<JS
<script type="text/javascript">
function jgClearThemeCache() {
    if (!confirm('آیا مطمئن هستید که می‌خواهید تمام کش داخلی پوسته را پاک کنید؟')) return;
    var data = {
        action: 'jg_clear_theme_cache',
        _ajax_nonce: '{$nonce}'
    };
    jQuery.post(ajaxurl, data, function(response) {
        if (response.success) {
            alert('کش داخلی پوسته با موفقیت پاک شد!');
        } else {
            alert('پاکسازی کش با خطا مواجه شد: ' + (response.data || 'خطای ناشناخته'));
        }
    });
}
</script>
JS;
    }

    /**
     * Handles the AJAX request to clear all transients from the database.
     *
     * Verifies the nonce, checks the current user’s capability, loops through
     * all transient option names (including site‑wide transients), deletes
     * them, flushes the object cache, and returns a JSON response indicating
     * how many entries were removed.
     */
    public function handleAjax(): void
    {
        // Validate request
        check_ajax_referer('jg_clear_theme_cache');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز.');
        }

        global $wpdb;
        // Find all transient and site transient option names
        $transient_names = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%' OR option_name LIKE '\\_site\\_transient\\_%'"
        );

        $deleted_count = 0;
        foreach ($transient_names as $option) {
            if (delete_option($option)) {
                $deleted_count++;
            }
        }

        // Flush any persistent object caches
        wp_cache_flush();

        wp_send_json_success(sprintf('تعداد %d کش داخلی حذف شد.', $deleted_count));
    }

    /**
     * Prints the `ajaxurl` variable on the front‑end if necessary.
     *
     * Some themes may not define `ajaxurl` globally on the front‑end; this
     * function ensures it is available when the admin bar is visible. It is
     * wrapped in a check to avoid polluting pages for regular visitors.
     */
    public function printAjaxUrl(): void
    {
        if (is_admin_bar_showing() && current_user_can('manage_options')) {
            $ajax_url = admin_url('admin-ajax.php');
            echo "<script type=\"text/javascript\">\n";
            echo "if (typeof ajaxurl === 'undefined') { var ajaxurl = '{$ajax_url}'; }\n";
            echo "</script>";
        }
    }
}