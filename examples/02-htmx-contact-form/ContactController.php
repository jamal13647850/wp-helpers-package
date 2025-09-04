<?php
/**
 * مثال کامل از کنترلر HTMX برای فرم تماس
 * 
 * این مثال نشان می‌دهد که چگونه:
 * - کنترلر HTMX ایجاد کنید
 * - اعتبارسنجی پیشرفته پیاده کنید
 * - Rate limiting اعمال کنید
 * - کپچا استفاده کنید
 * - ایمیل ارسال کنید
 */

use jamal13647850\wphelpers\Controllers\HTMX_Controller;
use jamal13647850\wphelpers\Utilities\HTMX_Validator;
use jamal13647850\wphelpers\Managers\CaptchaManager;

class ContactController extends HTMX_Controller 
{
    private CaptchaManager $captcha;
    
    public function __construct() 
    {
        $this->captcha = new CaptchaManager();
        parent::__construct();
    }
    
    /**
     * ثبت endpoint های مختلف
     */
    protected function registerRoutes(): void 
    {
        // ارسال فرم تماس
        $this->addRoute('submit_contact', 'handleContactSubmit', [
            'methods' => ['POST'],
            'capability' => false, // عمومی
            'middlewares' => ['throttle:5,300'] // 5 درخواست در 300 ثانیه
        ]);
        
        // اعتبارسنجی ایمیل real-time
        $this->addRoute('validate_email', 'validateEmail', [
            'methods' => ['POST'],
            'capability' => false,
            'middlewares' => ['throttle:10,60']
        ]);
        
        // اعتبارسنجی شماره تلفن real-time
        $this->addRoute('validate_phone', 'validatePhone', [
            'methods' => ['POST'],
            'capability' => false,
            'middlewares' => ['throttle:10,60']
        ]);
        
        // بازخوانی کپچا
        $this->addRoute('refresh_captcha', 'refreshCaptcha', [
            'methods' => ['POST'],
            'capability' => false,
            'middlewares' => ['throttle:20,60']
        ]);
    }
    
    /**
     * مدیریت ارسال فرم تماس
     */
    protected function handleContactSubmit(): void 
    {
        $validator = new HTMX_Validator($this->view);
        
        // اعتبارسنجی داده‌های ورودی
        $validation = $validator->validate($_POST, [
            'name' => 'required|min:2|max:50',
            'email' => 'required|email',
            'phone' => 'required|regex:/^09\d{9}$/',
            'subject' => 'required|min:5|max:100',
            'message' => 'required|min:10|max:1000',
            'captcha_answer' => 'required|numeric',
            'captcha_token' => 'required'
        ]);
        
        if (!$validation['isValid']) {
            $validator->renderErrors($validation['errors'], '#contact-errors');
            return;
        }
        
        // بررسی کپچا
        $captcha_valid = $this->captcha->verify_captcha(
            $_POST['captcha_answer'], 
            $_POST['captcha_token']
        );
        
        if (!$captcha_valid) {
            $validator->renderErrors([
                'captcha_answer' => 'کپچا اشتباه است، لطفاً مجدداً تلاش کنید'
            ], '#contact-errors');
            return;
        }
        
        // Honeypot بررسی (bot detection)
        if (!empty($_POST['website'])) {
            $this->setHtmxHeader('HX-Refresh', 'true');
            return;
        }
        
        // ارسال ایمیل
        $email_sent = $this->sendContactEmail($_POST);
        
        if ($email_sent) {
            // نمایش پیام موفقیت
            $this->render('contact/success.twig', [
                'name' => sanitize_text_field($_POST['name']),
                'message' => 'پیام شما با موفقیت ارسال شد. به زودی با شما تماس خواهیم گرفت.'
            ], '#contact-form');
            
            // بازنشانی فرم
            $this->setHtmxHeader('HX-Trigger', 'resetForm');
            
        } else {
            // خطا در ارسال ایمیل
            $validator->renderErrors([
                'general' => 'خطایی در ارسال پیام رخ داد. لطفاً مجدداً تلاش کنید.'
            ], '#contact-errors');
        }
    }
    
    /**
     * اعتبارسنجی ایمیل real-time
     */
    protected function validateEmail(): void 
    {
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (empty($email)) {
            echo '<span class="validation-message error">ایمیل الزامی است</span>';
            return;
        }
        
        if (!is_email($email)) {
            echo '<span class="validation-message error">فرمت ایمیل صحیح نیست</span>';
            return;
        }
        
        // بررسی دامنه‌های مسدود
        $blocked_domains = ['tempmail.com', '10minutemail.com', 'guerrillamail.com'];
        $domain = substr(strrchr($email, "@"), 1);
        
        if (in_array($domain, $blocked_domains)) {
            echo '<span class="validation-message warning">لطفاً از ایمیل دائمی استفاده کنید</span>';
            return;
        }
        
        echo '<span class="validation-message success">ایمیل معتبر است</span>';
    }
    
    /**
     * اعتبارسنجی شماره تلفن real-time
     */
    protected function validatePhone(): void 
    {
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        if (empty($phone)) {
            echo '<span class="validation-message error">شماره تلفن الزامی است</span>';
            return;
        }
        
        // بررسی فرمت شماره موبایل ایران
        if (!preg_match('/^09\d{9}$/', $phone)) {
            echo '<span class="validation-message error">فرمت صحیح: 09xxxxxxxxx</span>';
            return;
        }
        
        echo '<span class="validation-message success">شماره تلفن معتبر است</span>';
    }
    
    /**
     * بازخوانی کپچا
     */
    protected function refreshCaptcha(): void 
    {
        $captcha_data = $this->captcha->generate_captcha('medium');
        
        $this->render('components/captcha.twig', [
            'captcha' => $captcha_data
        ], '#captcha-container');
    }
    
    /**
     * ارسال ایمیل تماس
     */
    private function sendContactEmail(array $data): bool 
    {
        $name = sanitize_text_field($data['name']);
        $email = sanitize_email($data['email']);
        $phone = sanitize_text_field($data['phone']);
        $subject = sanitize_text_field($data['subject']);
        $message = sanitize_textarea_field($data['message']);
        
        // تنظیمات ایمیل
        $to = get_option('admin_email');
        $email_subject = "فرم تماس: {$subject}";
        
        // محتوای ایمیل
        $email_body = $this->view->render('emails/contact-form.twig', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
            'sent_at' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
        
        // هدرهای ایمیل
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . $name . ' <' . $email . '>'
        ];
        
        // ارسال ایمیل
        $sent = wp_mail($to, $email_subject, $email_body, $headers);
        
        // لاگ کردن برای debug
        if (!$sent && WP_DEBUG) {
            error_log("Contact form email failed for: {$email}");
        }
        
        return $sent;
    }
}

// فعال‌سازی کنترلر
if (class_exists('jamal13647850\wphelpers\Controllers\HTMX_Controller')) {
    new ContactController();
}