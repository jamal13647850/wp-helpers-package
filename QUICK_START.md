# شروع سریع با پکیج WordPress Helpers

راهنمای سریع برای شروع کار با پکیج wp-helpers در کمترین زمان ممکن.

## نصب سریع

```bash
composer require jamal13647850/wp-helpers
```

## راه‌اندازی در 3 مرحله

### مرحله 1: functions.php

```php
<?php
require_once get_template_directory() . '/vendor/autoload.php';

use jamal13647850\wphelpers\ServiceProvider;
ServiceProvider::boot();
```

### مرحله 2: ایجاد View Helper

```php
function get_theme_view() {
    static $view = null;
    if ($view === null) {
        $view = new \jamal13647850\wphelpers\Views\View();
        $view->addPath(get_template_directory() . '/templates', 'theme');
    }
    return $view;
}
```

### مرحله 3: استفاده در Template

```twig
{# templates/header.twig #}
<!DOCTYPE html>
<html {{ language_attributes() }} dir="rtl">
<head>{{ wp_head() }}</head>
<body {{ body_class() }}>

<header>
    {# منوی اصلی #}
    {{ menu('desktop', 'primary')|raw }}
    
    {# منوی موبایل #}
    {{ menu('overlay-mobile', 'primary', {'max_depth': 3})|raw }}
</header>
```

## ویژگی‌های اصلی

### 🎨 سیستم Template (Twig)
```php
$view = get_theme_view();
echo $view->render('page.twig', ['title' => 'عنوان صفحه']);
```

### ⚡ کش پیشرفته
```php
use jamal13647850\wphelpers\Cache\CacheManager;
$cache = new CacheManager('object', 'myprefix_', 3600);
$data = $cache->remember('expensive_query', fn() => $wpdb->get_results($sql), 1800);
```

### 🌐 چندزبانگی
```php
use jamal13647850\wphelpers\Language\LanguageManager;
$lang = LanguageManager::getInstance();
echo $lang->trans('welcome'); // خوش آمدید
```

### 📱 HTMX Controllers
```php
class MyController extends \jamal13647850\wphelpers\Controllers\HTMX_Controller {
    protected function registerRoutes(): void {
        $this->addRoute('submit_form', 'handleForm', ['methods' => ['POST']]);
    }
    
    protected function handleForm(): void {
        $this->render('success.twig', ['message' => 'موفق!'], '#form-result');
    }
}
new MyController();
```

### 🎯 Menu Components
```php
// 7 نوع منوی آماده
echo MenuManager::render('overlay-mobile', 'primary', [
    'accordion_mode' => 'independent',
    'enable_icons' => true
]);
```

### 🎪 Slider Components
```php
$slides = [
    ['image' => 'slide1.jpg', 'title' => 'عنوان', 'content' => 'محتوا']
];
echo SliderManager::render('wide-auto', $slides, ['interval' => 5000]);
```

## مثال کامل: فرم تماس HTMX

### Controller
```php
class ContactController extends \jamal13647850\wphelpers\Controllers\HTMX_Controller {
    protected function registerRoutes(): void {
        $this->addRoute('contact', 'handleContact', [
            'methods' => ['POST'],
            'middlewares' => ['throttle:5,300']
        ]);
    }
    
    protected function handleContact(): void {
        $validator = new \jamal13647850\wphelpers\Utilities\HTMX_Validator($this->view);
        
        $result = $validator->validate($_POST, [
            'name' => 'required|min:2',
            'email' => 'required|email',
            'message' => 'required|min:10'
        ]);
        
        if (!$result['isValid']) {
            $validator->renderErrors($result['errors'], '#errors');
            return;
        }
        
        // ارسال ایمیل
        wp_mail(get_option('admin_email'), 'تماس جدید', $_POST['message']);
        
        $this->render('success.twig', ['message' => 'پیام ارسال شد'], '#form');
    }
}
```

### Template
```html
<form hx-post="<?= wp_ajax_url() ?>?action=htmx_contact_contact" hx-target="#form">
    <div id="errors"></div>
    <input name="name" placeholder="نام" required>
    <input name="email" type="email" placeholder="ایمیل" required>
    <textarea name="message" placeholder="پیام" required></textarea>
    <button type="submit">ارسال</button>
    <?= wp_nonce_field('contact_nonce') ?>
</form>
```

## تنظیمات ACF

```php
use jamal13647850\wphelpers\Utilities\Theme_Settings_ACF;

$settings = new Theme_Settings_ACF();
$logo = $settings->getOption('logo', 'header');
$show_slider = (bool) $settings->getOption('slider_show', 'homepage');
```

## ابزارهای کاربردی

### کپچا
```php
use jamal13647850\wphelpers\Managers\CaptchaManager;
$captcha = new CaptchaManager();
$data = $captcha->generate_captcha('medium');
$is_valid = $captcha->verify_captcha($_POST['answer'], $_POST['token']);
```

### لیست علاقه‌مندی‌ها
```php
use jamal13647850\wphelpers\Managers\WishlistManager;
$wishlist = new WishlistManager();
$wishlist->toggle_wishlist($product_id, $user_id);
```

### پاکسازی کش
```php
use jamal13647850\wphelpers\Utilities\Clear_Theme_Cache;
new Clear_Theme_Cache(); // دکمه در admin bar
```

## نکات مهم

### امنیت ✅
- همیشه nonce استفاده کنید
- ورودی‌ها را sanitize کنید  
- خروجی‌ها را escape کنید

### عملکرد ⚡
- از کش استفاده کنید
- Asset ها را به‌موقع بارگذاری کنید
- Redis برای کش استفاده کنید

### مسیرها 📁
```
templates/
├── components/     # اجزای کوچک
├── layouts/        # قالب‌های اصلی  
├── pages/          # صفحات خاص
└── partials/       # بخش‌های مشترک
```

## عیب‌یابی سریع

```php
// Debug mode
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// چک کردن نصب
if (!class_exists('jamal13647850\wphelpers\ServiceProvider')) {
    wp_die('پکیج wp-helpers نصب نشده');
}

// چک کردن template
$view = get_theme_view();
if (!$view->templateExists('my-template.twig')) {
    wp_die('Template یافت نشد');
}
```

## لینک‌های مفید

- 📖 [راهنمای کامل](COMPREHENSIVE_GUIDE.md)
- 💻 [مثال‌های عملی](examples/)
- 🐛 [گزارش مشکل](https://github.com/jamal13647850/wp-helpers/issues)
- 💬 [تلگرام](https://t.me/jamal13647850)

---

**تبریک! 🎉** شما آماده استفاده از پکیج WordPress Helpers هستید!