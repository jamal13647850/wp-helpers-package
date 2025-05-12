<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

class SMSRegisterController
{
    private const OTP_EXPIRY = 180; // 3 minutes expiry
    private HTMX_Validator $validator;
    private \wpdb $wpdb;
    private string $table_name;
    private View $view;

    public function __construct(View $view)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'cafedentist_user_info';
        $this->validator = new HTMX_Validator('html');
        $this->view     = $view;

        // Register AJAX endpoints
        add_action('wp_ajax_nopriv_sms_register_request', [$this, 'handle_sms_register_request']);
        add_action('wp_ajax_nopriv_sms_register_verify',  [$this, 'handle_sms_register_verify']);

        // HTMX validations
        add_action('wp_ajax_htmx_validate_email',  [$this, 'htmx_validate_email']);
        add_action('wp_ajax_nopriv_htmx_validate_email',  [$this, 'htmx_validate_email']);
        add_action('wp_ajax_htmx_validate_mobile', [$this, 'htmx_validate_mobile']);
        add_action('wp_ajax_nopriv_htmx_validate_mobile', [$this, 'htmx_validate_mobile']);
        add_action('wp_ajax_htmx_validate_password', [$this, 'htmx_validate_password']);
        add_action('wp_ajax_nopriv_htmx_validate_password', [$this, 'htmx_validate_password']);
    }

    /**
     * Handle initial register request: validate, store OTP, send SMS, render verify form
     */
    public function handle_sms_register_request(): void
    {
        check_ajax_referer('sms_register_nonce', 'nonce');

        // Sanitize inputs
        $email    = sanitize_email($_POST['email'] ?? '');
        $mobile   = sanitize_text_field($_POST['mobile'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate fields
        $fields = [
            'ایمیل'       => ['value' => $email,    'rules' => ['required' => true, 'type' => 'email']],
            'شماره موبایل' => ['value' => $mobile,   'rules' => ['required' => true, 'type' => 'mobile']],
            'رمز عبور'     => ['value' => $password, 'rules' => ['required' => true]],
        ];

        $this->validator->validate_all_fields($fields);
        if (!empty($this->validator->get_errors())) {
            $this->validator->get_validation_response('html');
            wp_die();
        }

        // Generate OTP for mobile only
        $otp_mobile = $this->generate_otp();

        
        // Store mobile OTP in custom table with error handling

$this->wpdb->replace(                                                                                                                                                                 
    $this->table_name,                                                                                                                                                                
    [                                                                                                                                                                                 
        'user_id' => 0, // موقتاً 0 چون هنوز کاربر ثبت نشده                                                                                                                            
        'mobile_number' => $mobile,                                                                                                                                                                                                                                                                      
        'status' => 'pending',                                                                                                                                                        
        'verification_code' => $otp_mobile,                                                                                                                                           
    ]                                                                                                                                                                                 
); 



        // Send SMS via FarazSMS
        $sms = new \jamal13647850\smsapi\SMS(
            new \jamal13647850\smsapi\FarazSMS(
                FARAZSMS_USERNAME,
                FARAZSMS_PASSWORD,
                FARAZSMS_FROM_NUMBER,
                FARAZSMS_URL
            )
        );
        $sms->sendSMSByPattern($mobile, '', FARAZSMS_PATTERN, ['code' => $otp_mobile]);

        // Render verification form
        $this->create_otp_form($email, $mobile, $password);
        wp_die();
    }

    /**
     * Handle OTP verification: validate OTP for mobile, create WP user, update table
     */
    public function handle_sms_register_verify(): void
    {
        check_ajax_referer('sms_register_nonce', 'nonce');

        // Sanitize inputs
        $email      = sanitize_email($_POST['email'] ?? '');
        $mobile     = sanitize_text_field($_POST['mobile'] ?? '');
        $password   = $_POST['password'] ?? '';
        $otp_mobile = sanitize_text_field($_POST['otp_mobile'] ?? '');

        // Validate fields + OTP
        $fields = [
            'ایمیل'                => ['value' => $email,      'rules' => ['required' => true, 'type' => 'email']],
            'شماره موبایل'         => ['value' => $mobile,     'rules' => ['required' => true, 'type' => 'mobile']],
            'رمز عبور'             => ['value' => $password,   'rules' => ['required' => true]],
            'کد تأیید موبایل'      => ['value' => $otp_mobile, 'rules' => ['required' => true, 'type' => 'otp']],
        ];

        $this->validator->validate_all_fields($fields);
        if (!empty($this->validator->get_errors())) {
            $this->create_otp_form($email, $mobile, $password, $otp_mobile);
            $this->validator->get_validation_response('html');
            wp_die();
        }

        // Verify mobile OTP from DB
        $record = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE mobile_number = %s AND user_id = 0",
            $mobile
        ));
        if (!$record
            || $record->verification_code !== $otp_mobile
            || $record->status !== 'pending'
            || (time() - strtotime($record->updated_at)) > self::OTP_EXPIRY
        ) {
            $this->validator->add_error('کد تأیید موبایل نامعتبر یا منقضی شده است.');
            $this->create_otp_form($email, $mobile, $password, $otp_mobile);
            $this->validator->get_validation_response('html');
            wp_die();
        }

        // Create WP user
        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) {
            $this->validator->add_error('خطا در ثبت نام: ' . $user_id->get_error_message());
            $this->create_otp_form($email, $mobile, $password, $otp_mobile);
            $this->validator->get_validation_response('html');
            wp_die();
        }

        // Update custom table
        $this->wpdb->update(
            $this->table_name,
            [
                'user_id'          => $user_id,
                'status'           => 'verified',
                'verification_code'=> null,
            ],
            ['mobile_number' => $mobile, 'user_id' => 0]
        );

        // Sync mobile to billing_phone
        $migration = new UserMigration($this->view);
        $migration->sync_mobile_to_billing_phone($user_id);

        // Success message
        echo '<p class="text-primary">ثبت نام با موفقیت انجام شد. '
            . '<a href="' . esc_url(home_url('/login/')) . '" class="text-secondary hover:underline">ورود به حساب</a></p>';
        wp_die();
    }

    /**
     * Generate 6-digit OTP
     */
    private function generate_otp(): string
    {
        return sprintf('%06d', mt_rand(0, 999999));
    }

    /**
     * Render the OTP verification form via Twig
     *
     * @param string $email
     * @param string $mobile
     * @param string $password
     * @param string $otp_mobile
     */
    private function create_otp_form(
        string $email,
        string $mobile,
        string $password,
        string $otp_mobile = ''
    ): void {
        $ajax_url = admin_url('admin-ajax.php');
        $nonce    = wp_create_nonce('sms_register_nonce');

        $data = [
            'ajax_url'    => $ajax_url,
            'nonce'       => $nonce,
            'email'       => $email,
            'mobile'      => $mobile,
            'password'    => $password,
            'otp_mobile'  => $otp_mobile,
        ];

        $this->view->display('@views/components/register/verify.twig', $data);
    }

    // HTMX field validators

    public function htmx_validate_email(): void
    {
        check_ajax_referer('sms_register_nonce', 'nonce');
        $value = sanitize_email($_POST['email'] ?? '');
        $this->validator->validate_email($value);
        $this->validator->get_validation_response();
        wp_die();
    }

    public function htmx_validate_mobile(): void
    {
        check_ajax_referer('sms_register_nonce', 'nonce');
        $value = sanitize_text_field($_POST['mobile'] ?? '');
        $this->validator->validate_mobile($value);
        $this->validator->get_validation_response();
        wp_die();
    }

    public function htmx_validate_password(): void
    {
        check_ajax_referer('sms_register_nonce', 'nonce');
        $value = $_POST['password'] ?? '';
        $fields = [
            'رمز عبور' => ['value' => $value, 'rules' => ['required' => true]],
        ];
        $this->validator->validate_all_fields($fields);
        $this->validator->get_validation_response();
        wp_die();
    }
}