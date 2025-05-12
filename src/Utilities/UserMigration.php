<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Utilities;

defined('ABSPATH') || exit();

class UserMigration
{
    private $wpdb;
    private string $table_name;
    private View $view;

    public function __construct(View $view)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'cafedentist_user_info';
        $this->view = $view;

        add_action('user_register', [$this, 'sync_mobile_to_billing_phone']);
        add_action('profile_update', [$this, 'sync_mobile_to_billing_phone']);
        add_action('woocommerce_customer_save_address', [$this, 'sync_billing_to_mobile']);
        add_action('delete_user', [$this, 'delete_user_from_table']);
    }

    public function create_user_table(): bool
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            user_id BIGINT(20) UNSIGNED NOT NULL,
            mobile_number VARCHAR(11) NOT NULL,
            birth_date VARCHAR(10) DEFAULT NULL,
            medical_code  VARCHAR(50) DEFAULT NULL,
            status ENUM('pending', 'verified') DEFAULT 'pending',
            verification_code VARCHAR(6) DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id),
            UNIQUE KEY mobile_number (mobile_number)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        return $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
    }

    public function migrate_old_users(): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'duplicates' => 0,
            'invalid' => 0,
            'from_digits' => 0,
            'from_billing' => 0,
            'normalized' => 0, // تعداد شماره‌های استانداردشده
        ];

        $users = $this->wpdb->get_results("
            SELECT u.ID AS user_id, 
                   um1.meta_value AS digits_phone_no, 
                   um2.meta_value AS billing_phone
            FROM {$this->wpdb->users} u
            LEFT JOIN {$this->wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'digits_phone_no'
            LEFT JOIN {$this->wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'billing_phone'
        ");

        foreach ($users as $user) {
            $mobile = null;
            $source = null;

            // اولویت با digits_phone_no
            if (!empty($user->digits_phone_no)) {
                $mobile = $this->normalize_phone_number($user->digits_phone_no);
                $source = 'digits';
            } elseif (!empty($user->billing_phone)) {
                $mobile = $this->normalize_phone_number($user->billing_phone);
                $source = 'billing';
            }

            if ($mobile && preg_match('/^09[0-9]{9}$/', $mobile)) {
                $inserted = $this->wpdb->replace(
                    $this->table_name,
                    [
                        'user_id' => $user->user_id,
                        'mobile_number' => $mobile,
                        'birth_date' => null,
                        'medical_code' => null,
                        'status' => 'verified',
                        'verification_code' => null,
                    ]
                );

                if ($inserted === false) {
                    error_log("Failed to migrate user {$user->user_id}: " . $this->wpdb->last_error);
                    $results['failed']++;
                } elseif ($inserted === 0) {
                    $results['duplicates']++;
                } else {
                    $results['success']++;
                    $source === 'digits' ? $results['from_digits']++ : $results['from_billing']++;
                    if ($mobile !== ($user->digits_phone_no ?? $user->billing_phone)) {
                        $results['normalized']++;
                    }
                    $this->sync_mobile_to_billing_phone((int)$user->user_id);
                }
            } else {
                error_log("Invalid or missing mobile for user {$user->user_id}: digits={$user->digits_phone_no}, billing={$user->billing_phone}");
                $results['invalid']++;
            }
        }

        return $results;
    }

    public function delete_old_data(): int
    {
        $meta_keys = ['digits_phone', 'digits_phone_no', 'digits_form_data', 'digits_migrate_shown'];
        $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
        
        $deleted = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->wpdb->usermeta} WHERE meta_key IN ($placeholders)",
                $meta_keys
            )
        );

        if ($deleted === false) {
            error_log("Failed to delete old data: " . $this->wpdb->last_error);
            return 0;
        }

        return $deleted;
    }

    public function sync_mobile_to_billing_phone(int $user_id): void
    {
        $record = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT mobile_number, status FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));

        if ($record && $record->status === 'verified') {
            update_user_meta($user_id, 'billing_phone', $record->mobile_number);
        }
    }

    public function sync_billing_to_mobile(int $user_id): void
    {
        $billing_phone = get_user_meta($user_id, 'billing_phone', true);
        $current_record = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT mobile_number, status FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));

        if ($billing_phone && $billing_phone !== ($current_record->mobile_number ?? '')) {
            $normalized_phone = $this->normalize_phone_number($billing_phone);
            $validator = new HTMX_Validator();
            $validator->validate_mobile($normalized_phone);

            if (empty($validator->get_errors())) {
                $verification_code = sprintf("%06d", rand(0, 999999));
                $this->wpdb->replace(
                    $this->table_name,
                    [
                        'user_id' => $user_id,
                        'mobile_number' => $normalized_phone,
                        'status' => 'pending',
                        'verification_code' => $verification_code,
                    ]
                );
                $this->send_verification_sms($normalized_phone, $verification_code);
            }
        }
    }

    private function send_verification_sms(string $mobile, string $code): void
    {
        $sms = new \jamal13647850\smsapi\SMS(new \jamal13647850\smsapi\FarazSMS(
            FARAZSMS_USERNAME,
            FARAZSMS_PASSWORD,
            FARAZSMS_FROM_NUMBER,
            FARAZSMS_URL
        ));
        $sms->sendSMSByPattern($mobile, '', FARAZSMS_PATTERN, ['code' => $code]);
    }

    /**
     * استانداردسازی شماره موبایل
     * - شماره‌های بدون صفر (مثل 9366414750) به 09366414750 تبدیل می‌شن
     * - شماره‌های با +98 (مثل +989128112859) به 09128112859 تبدیل می‌شن
     */
    private function normalize_phone_number(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // حذف فاصله‌ها، خط تیره‌ها و کاراکترهای غیرعددی اضافی
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // اگر با +98 شروع می‌شه، تبدیل به 0
        if (substr($phone, 0, 3) === '+98') {
            $phone = '0' . substr($phone, 3);
        }

        // اگر 10 رقمیه و با 9 شروع می‌شه، صفر اضافه کن
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '9') {
            $phone = '0' . $phone;
        }

        return $phone;
    }

    /**
     * حذف کاربر از جدول cafedentist_user_info هنگام حذف از وردپرس
     *
     * @param int $user_id شناسه کاربر
     */
    public function delete_user_from_table(int $user_id): void
    {
        $deleted = $this->wpdb->delete(
            $this->table_name,
            ['user_id' => $user_id],
            ['%d']
        );

        if ($deleted === false) {
            error_log("Failed to delete user $user_id from {$this->table_name}: " . $this->wpdb->last_error);
        } else {
            error_log("Successfully deleted user $user_id from {$this->table_name}");
        }
    }
}