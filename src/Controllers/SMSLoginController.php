<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Controllers;

defined('ABSPATH') || exit();

class SMSLoginController
{
    private $wpdb;
    private string $table_name;
    private View $view;
    private const OTP_EXPIRY = 180; // 3 دقیقه

    public function __construct(View $view)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'cafedentist_user_info';
        $this->view = $view;

        add_action('wp_ajax_sms_login', [$this, 'handle_sms_login']);
        add_action('wp_ajax_nopriv_sms_login', [$this, 'handle_sms_login']);
        add_action('wp_ajax_resend_login_otp', [$this, 'handle_resend_login_otp']);
        add_action('wp_ajax_nopriv_resend_login_otp', [$this, 'handle_resend_login_otp']);
    }

    public function handle_sms_login()
    {
        check_ajax_referer('sms_login_nonce', 'nonce');

        $login_identifier = sanitize_text_field($_POST['login_identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $otp = $_POST['otp'] ?? '';
        $otp_login = isset($_POST['otp_login']) && $_POST['otp_login'] === '1';

        // دیباگ
        error_log("Login attempt - Identifier: $login_identifier, OTP Login: $otp_login, OTP: $otp");

        if ($otp_login) {
            error_log("Entering OTP login logic");
            if (empty($login_identifier)) {
                $validator = new HTMX_Validator('html');
                $validator->add_error('شماره موبایل اجباری است.');
                $validator->get_validation_response('html');
                wp_die();
            }

            $record = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT user_id, mobile_number, verification_code, status, updated_at FROM {$this->table_name} WHERE mobile_number = %s",
                $login_identifier
            ));

            error_log("Record found: " . print_r($record, true));
            if (!$record) {
                $validator = new HTMX_Validator('html');
                $validator->add_error('شماره شما ثبت‌نام نشده است. لطفاً ابتدا <a href="/register/">ثبت‌نام</a> کنید.');
                $validator->get_validation_response('html');
                wp_die();
            }

            // اگه هنوز کد نگرفته، پیامک بفرست
            if (empty($otp)) {
                error_log("No OTP provided, sending new code");
                $new_otp = sprintf("%06d", rand(0, 999999));
                $this->wpdb->update(
                    $this->table_name,
                    ['verification_code' => $new_otp, 'updated_at' => current_time('mysql')],
                    ['mobile_number' => $login_identifier],
                    ['%s'],
                    ['%s']
                );
                $this->send_verification_sms($login_identifier, $new_otp);
                echo '<p class="text-blue-600 text-center">کد تأیید به شماره شما ارسال شد. لطفاً کد را وارد کنید.</p>';
                wp_die();
            }

            // اعتبارسنجی کد
            if ($record->status !== 'verified' || $record->verification_code !== $otp || (time() - strtotime($record->updated_at)) > self::OTP_EXPIRY) {
                $validator = new HTMX_Validator('html');
                $validator->add_error('کد تأیید نامعتبر یا منقضی شده است.');
                $validator->get_validation_response('html');
                wp_die();
            }

            error_log("OTP validated, logging in user: " . $record->user_id);
            wp_set_current_user($record->user_id);
            wp_set_auth_cookie($record->user_id);
            echo '<p class="text-green-600">ورود با موفقیت انجام شد. در حال انتقال...</p><script>setTimeout(() => window.location.href = "/", 1000);</script>';
        } else {
            // ورود با رمز
            $is_email = filter_var($login_identifier, FILTER_VALIDATE_EMAIL);
            $user = null;

            if ($is_email) {
                $user = get_user_by('email', $login_identifier);
            } else {
                // چک کردن شماره موبایل توی متادیتا یا جدول سفارشی
                $user_id = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT user_id FROM {$this->table_name} WHERE mobile_number = %s",
                    $login_identifier
                ));
                if ($user_id) {
                    $user = get_user_by('ID', $user_id);
                } else {
                    $user = get_user_by('login', $login_identifier); // به‌عنوان fallback
                }
            }

            if ($user && wp_check_password($password, $user->user_pass, $user->ID)) {
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);
                echo '<p class="text-green-600">ورود با موفقیت انجام شد. در حال انتقال...</p><script>setTimeout(() => window.location.href = "/", 1000);</script>';
            } else {
                $validator = new HTMX_Validator('html');
                $validator->add_error('ایمیل/موبایل یا رمز عبور اشتباه است.');
                $validator->get_validation_response('html');
                wp_die();
            }
        }

        wp_die();
    }

    public function handle_resend_login_otp()
    {
        check_ajax_referer('sms_login_nonce', 'nonce');

        $login_identifier = sanitize_text_field($_POST['login_identifier'] ?? '');
        $validator = new HTMX_Validator('html');

        if (empty($login_identifier)) {
            $validator->add_error('شماره موبایل اجباری است.');
        } else {
            $validator->validate_mobile($login_identifier);
        }

        if (!empty($validator->get_errors())) {
            $validator->get_validation_response('html');
            wp_die();
        }

        $record = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE mobile_number = %s",
            $login_identifier
        ));

        if (!$record || $record->status !== 'verified') {
            $validator->add_error('شماره شما ثبت‌نام نشده است. لطفاً ابتدا <a href="/register/">ثبت‌نام</a> کنید.');
            $validator->get_validation_response('html');
            wp_die();
        }

        $otp = sprintf("%06d", rand(0, 999999));
        $this->wpdb->update(
            $this->table_name,
            ['verification_code' => $otp, 'updated_at' => current_time('mysql')],
            ['mobile_number' => $login_identifier],
            ['%s'],
            ['%s']
        );

        error_log("Sending SMS to $login_identifier with OTP: $otp");
        $this->send_verification_sms($login_identifier, $otp);
        echo '<p class="text-green-600">کد تأیید جدید ارسال شد.</p>';
        wp_die();
    }

    private function send_verification_sms(string $mobile, string $code): void
    {
        error_log("Attempting to send SMS to $mobile with code $code");
        $sms = new \jamal13647850\smsapi\SMS(new \jamal13647850\smsapi\FarazSMS(
            FARAZSMS_USERNAME,
            FARAZSMS_PASSWORD,
            FARAZSMS_FROM_NUMBER,
            FARAZSMS_URL
        ));
        try {
            $sms->sendSMSByPattern($mobile, '', FARAZSMS_PATTERN, ['code' => $code]);
            error_log("SMS sent successfully to $mobile");
        } catch (Exception $e) {
            error_log("SMS sending failed: " . $e->getMessage());
            throw $e;
        }
    }
}