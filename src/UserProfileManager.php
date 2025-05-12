<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

class UserProfileManager
{
    private $wpdb;
    private string $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'cafedentist_user_info';

        add_action('show_user_profile', [$this, 'add_custom_fields']);
        add_action('edit_user_profile', [$this, 'add_custom_fields']);
        add_action('personal_options_update', [$this, 'save_custom_fields']);
        add_action('edit_user_profile_update', [$this, 'save_custom_fields']);
        // همگام‌سازی وقتی billing_phone توی WooCommerce تغییر می‌کنه
        add_action('woocommerce_customer_save_address', [$this, 'sync_from_billing_phone'], 10, 2);
    }

    public function add_custom_fields($user)
    {
        $user_id = $user->ID;
        $billing_phone = get_user_meta($user_id, 'billing_phone', true);
        $data = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT birth_date, medical_code FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));

        $birth_date = $data->birth_date ?? '';
        $medical_code = $data->medical_code ?? '';
        ?>
        <h3>اطلاعات اضافی کاربر</h3>
        <table class="form-table">
            <tr>
                <th><label for="birth_date">تاریخ تولد</label></th>
                <td>
                    <input type="text" 
                           name="birth_date" 
                           id="birth_date" 
                           value="<?php echo esc_attr($birth_date); ?>" 
                           class="regular-text">
                    <div id="birth_date_error" style="color: red;"></div>
                    <p class="description">اختیاری - فرمت: YYYY-MM-DD (مثال: 1364-08-12)</p>
                </td>
            </tr>
            <tr>
                <th><label for="medical_code">کد نظام پزشکی</label></th>
                <td>
                    <input type="text" 
                           name="medical_code" 
                           id="medical_code" 
                           value="<?php echo esc_attr($medical_code); ?>" 
                           class="regular-text">
                    <div id="medical_code_error" style="color: red;"></div>
                    <p class="description">اختیاری</p>
                </td>
            </tr>
        </table>
        <div id="profile_errors" style="color: red; margin-top: 10px;"></div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.querySelector('form#your-profile');
                if (form) {
                    form.addEventListener('submit', function(event) {
                        let hasErrors = false;
                        let firstErrorField = null;

                        document.getElementById('profile_errors').innerHTML = '';
                        document.getElementById('billing_phone_error').innerHTML = '';
                        document.getElementById('birth_date_error').innerHTML = '';
                        document.getElementById('medical_code_error').innerHTML = '';

                        const billingPhone = document.getElementById('billing_phone').value.trim();
                        const birthDate = document.getElementById('birth_date').value.trim();
                        const medicalCode = document.getElementById('medical_code').value.trim();

                        // اعتبارسنجی شماره موبایل (اجباری)
                        if (!billingPhone) {
                            document.getElementById('billing_phone_error').innerHTML = 'شماره موبایل اجباری است.';
                            hasErrors = true;
                            if (!firstErrorField) firstErrorField = document.getElementById('billing_phone');
                        } else if (!/^09[0-9]{9}$/.test(billingPhone)) {
                            document.getElementById('billing_phone_error').innerHTML = 'شماره موبایل باید 11 رقم و با 09 شروع شود.';
                            hasErrors = true;
                            if (!firstErrorField) firstErrorField = document.getElementById('billing_phone');
                        }

                        // اعتبارسنجی تاریخ تولد (اختیاری، اما اگه پر باشه باید درست باشه)
                        if (birthDate) {
                            if (!/^\d{4}-\d{2}-\d{2}$/.test(birthDate)) {
                                document.getElementById('birth_date_error').innerHTML = 'فرمت تاریخ تولد باید YYYY-MM-DD باشد.';
                                hasErrors = true;
                                if (!firstErrorField) firstErrorField = document.getElementById('birth_date');
                            } else {
                                const [year, month, day] = birthDate.split('-').map(Number);
                                if (year < 1300 || year > 1500 || month < 1 || month > 12 || day < 1 || day > 31 || 
                                    (month > 6 && day > 30) || (month === 12 && day > 29)) {
                                    document.getElementById('birth_date_error').innerHTML = 'تاریخ تولد نامعتبر است.';
                                    hasErrors = true;
                                    if (!firstErrorField) firstErrorField = document.getElementById('birth_date');
                                }
                            }
                        }

                        // اعتبارسنجی کد نظام پزشکی (اختیاری)
                        if (medicalCode && !/^[a-zA-Z0-9_]{4,32}$/.test(medicalCode)) {
                            document.getElementById('medical_code_error').innerHTML = 'کد نظام پزشکی باید 4-32 کاراکتر و شامل حروف، اعداد یا _ باشد.';
                            hasErrors = true;
                            if (!firstErrorField) firstErrorField = document.getElementById('medical_code');
                        }

                        if (hasErrors) {
                            event.preventDefault();
                            document.getElementById('profile_errors').innerHTML = 'لطفاً خطاهای بالا را برطرف کنید.';
                            if (firstErrorField) {
                                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                firstErrorField.focus();
                            }
                        }
                    });
                }
            });
        </script>
        <?php
    }

    public function save_custom_fields(int $user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        $billing_phone = sanitize_text_field($_POST['billing_phone'] ?? '');
        $birth_date = sanitize_text_field($_POST['birth_date'] ?? '');
        $medical_code = sanitize_text_field($_POST['medical_code'] ?? '');

        $normalized_billing = $this->normalize_phone_number($billing_phone);

        // ذخیره در جدول wp_cafedentist_user_info
        $this->wpdb->replace(
            $this->table_name,
            [
                'user_id' => $user_id,
                'mobile_number' => $normalized_billing,
                'birth_date' => $birth_date ?: null,
                'medical_code' => $medical_code,
                'status' => 'verified',
                'verification_code' => null,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );

        // به‌روزرسانی billing_phone توی wp_usermeta
        if ($normalized_billing) {
            update_user_meta($user_id, 'billing_phone', $normalized_billing);
            error_log("Updated billing_phone to $normalized_billing for user $user_id from profile");
        }
    }

    public function sync_from_billing_phone($user_id, $address_type)
    {
        if ($address_type !== 'billing') {
            return;
        }

        $billing_phone = get_user_meta($user_id, 'billing_phone', true);
        $normalized_billing = $this->normalize_phone_number($billing_phone);

        if ($normalized_billing) {
            $this->wpdb->replace(
                $this->table_name,
                [
                    'user_id' => $user_id,
                    'mobile_number' => $normalized_billing,
                    'birth_date' => null, // مقدار فعلی رو تغییر نمی‌ده مگه اینکه توی فرم باشه
                    'medical_code' => null,
                    'status' => 'verified',
                    'verification_code' => null,
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s']
            );
            error_log("Synced mobile_number to $normalized_billing from billing_phone for user $user_id");
        }
    }

    private function normalize_phone_number(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (substr($phone, 0, 3) === '+98') {
            $phone = '0' . substr($phone, 3);
        }
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '9') {
            $phone = '0' . $phone;
        }
        return $phone;
    }
}