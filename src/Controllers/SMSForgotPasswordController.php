<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Controllers;

defined('ABSPATH') || exit();

/**
 * SMS Forgot Password Controller
 *
 * Handles the forgot password functionality via AJAX requests.
 */
class SMSForgotPasswordController {
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize hooks
     */
    public function init() {
        add_action('wp_ajax_lost_password', array($this, 'lost_password_callback'));
        add_action('wp_ajax_nopriv_lost_password', array($this, 'lost_password_callback')); // For non-logged-in users
    }

    /**
     * AJAX callback for lost password request
     */
    public function lost_password_callback() {
        global $wpdb;

        // Verify nonce for security
        check_ajax_referer('sms_login_nonce', 'nonce');

        // Get the user login (email or phone)
        $user_login = sanitize_text_field($_POST['user_login']);

        if (empty($user_login)) {
            echo '<p class="text-red-500 text-center">شماره موبایل یا ایمیل اجباری است.</p>';
            wp_die();
        }

        // Validate the input format
        if (!preg_match('/^[0-9a-zA-Z@.+-]+$/', $user_login)) {
            echo '<p class="text-red-500 text-center">فرمت شماره موبایل یا ایمیل نامعتبر است.</p>';
            wp_die();
        }

        // Check if the user exists by login or email
        $user = get_user_by('login', $user_login);
        if (!$user) {
            $user = get_user_by('email', $user_login);
        }

        // Check if the user exists by mobile number from custom table
        if (!$user) {
            $mobile_row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT user_id FROM {$wpdb->prefix}cafedentist_user_info WHERE mobile_number = %s LIMIT 1",
                    $user_login
                )
            );
            if ($mobile_row) {
                $user = get_user_by('ID', $mobile_row->user_id);
            }
        }

        if (!$user) {
            echo '<p class="text-red-500 text-center">هیچ کاربری با این شماره موبایل یا ایمیل یافت نشد.</p>';
            wp_die();
        }

        // Use WordPress default password reset process
        $success = $this->reset_password($user->ID);

        if ($success) {
            echo '<p class="text-green-500 text-center">ایمیل بازیابی رمز عبور با موفقیت ارسال شد. لطفاً ایمیل خود را چک کنید.</p>';
        } else {
            echo '<p class="text-red-500 text-center">خطایی در ارسال ایمیل بازیابی رخ داد. لطفاً دوباره تلاش کنید.</p>';
        }

        wp_die();
    }

    /**
     * Reset password using WordPress default function
     *
     * @param int $user_id User ID
     * @return bool
     */
    private function reset_password($user_id) {
        global $wp_hasher;

        // Generate a new reset key
        if (empty($wp_hasher)) {
            require_once ABSPATH . 'wp-includes/class-phpass.php';
            $wp_hasher = new \PasswordHash(8, true);
        }

        $key = wp_generate_password(20, false);
        $hashed_key = $wp_hasher->HashPassword($key);

        // Update user meta with reset key
        update_user_meta($user_id, 'reset_key', $key);
        update_user_meta($user_id, 'reset_key_expiry', time() + DAY_IN_SECONDS);

        // Send reset email using WordPress default function
        $result = retrieve_password($key, $user_id);

        return $result;
    }
}

// Instantiate the controller
if (!isset($sms_forgot_password_controller)) {
    $sms_forgot_password_controller = new SMSForgotPasswordController();
}