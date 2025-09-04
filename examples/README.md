# مثال‌های عملی پکیج WordPress Helpers

این فولدر شامل مثال‌های کاربردی و عملی برای استفاده از قابلیت‌های مختلف پکیج WordPress Helpers است.

## فهرست مثال‌ها

### 1. راه‌اندازی پایه (01-basic-setup)
- **functions.php**: راه‌اندازی اولیه پکیج در قالب
- **templates/header.twig**: نمونه template با استفاده از Menu Components

**ویژگی‌های پوشش‌داده‌شده:**
- بارگذاری ServiceProvider
- پیکربندی View و Twig
- استفاده از Menu Components
- ثبت توابع سفارشی Twig

### 2. فرم تماس با HTMX (02-htmx-contact-form)
- **ContactController.php**: کنترلر کامل HTMX برای فرم تماس
- **contact-form.twig**: فرم پیشرفته با validation real-time

**ویژگی‌های پوشش‌داده‌شده:**
- HTMX Controller با middleware
- اعتبارسنجی پیشرفته
- Rate limiting و امنیت
- کپچا و Honeypot
- ارسال ایمیل
- JavaScript بهبود تجربه کاربری

### 3. اسلایدر سفارشی (03-custom-slider)
- **HeroSlider.php**: کلاس اسلایدر سفارشی با قابلیت‌های پیشرفته
- **hero.twig**: Template کامل اسلایدر با Swiper.js

**ویژگی‌های پوشش‌داده‌شده:**
- ایجاد Slider Component سفارشی
- مدیریت Asset ها
- Template پیشرفته با animation
- Responsive design
- Integration با Swiper.js
- Parallax effects

## نحوه استفاده

### نصب و راه‌اندازی

1. فایل‌های هر مثال را به قالب خود کپی کنید
2. مسیرهای فایل‌ها را متناسب با ساختار قالب خود تنظیم کنید
3. وابستگی‌های ضروری را نصب کنید

### مثال 1: راه‌اندازی پایه

```php
// functions.php
require_once get_template_directory() . '/vendor/autoload.php';
use jamal13647850\wphelpers\ServiceProvider;
ServiceProvider::boot();

$view = get_theme_view();
```

```twig
{# header.twig #}
{{ menu('desktop', 'primary', {
    'max_depth': 2,
    'enable_mega_menu': true
})|raw }}
```

### مثال 2: فرم HTMX

```php
// functions.php
require_once 'ContactController.php';
new ContactController();
```

```html
<!-- contact-page.php -->
<?php echo get_theme_view()->render('contact-form.twig', [
    'captcha_token' => wp_create_nonce('captcha_nonce')
]); ?>
```

### مثال 3: اسلایدر سفارشی

```php
// functions.php
require_once 'HeroSlider.php';

// استفاده در template
$slides = get_field('hero_slides');
echo render_hero_slider($slides, [
    'autoplay' => true,
    'interval' => 5000,
    'height' => 600
]);
```

## پیش‌نیازهای هر مثال

### مثال 1 (پایه)
- WordPress 5.0+
- PHP 7.4+
- Composer
- پکیج wp-helpers

### مثال 2 (HTMX)
- همه پیش‌نیازهای مثال 1
- HTMX.js (بارگذاری خودکار)
- پلاگین ACF Pro (برای CaptchaManager)

### مثال 3 (اسلایدر)
- همه پیش‌نیازهای مثال 1
- Swiper.js 8.x+
- Alpine.js (اختیاری)

## نکات مهم

### امنیت
- همیشه از `wp_nonce_field()` استفاده کنید
- ورودی‌ها را sanitize کنید: `sanitize_text_field()`, `sanitize_email()`
- خروجی‌ها را escape کنید: `esc_html()`, `esc_url()`

### عملکرد
- از کش استفاده کنید برای داده‌های پرهزینه
- Asset ها را فقط در صورت نیاز بارگذاری کنید
- تصاویر را lazy load کنید

### دسترسی‌پذیری
- ARIA attributes اضافه کنید
- Alt text برای تصاویر
- Keyboard navigation پشتیبانی کنید

## شخصی‌سازی

### تغییر مسیرها
```php
// برای تغییر مسیر templates
$view->addPath(get_template_directory() . '/custom-templates', 'custom');
echo $view->render('@custom/my-template.twig');
```

### اضافه کردن توابع Twig
```php
$view->registerFunction('my_function', function($param) {
    return 'Custom: ' . $param;
});
```

### Override کردن Assets
```php
// در functions.php
add_action('wp_enqueue_scripts', function() {
    wp_dequeue_script('hero-slider-js');
    wp_enqueue_script('my-custom-slider', get_template_directory_uri() . '/js/my-slider.js');
}, 20);
```

## عیب‌یابی

### مشکلات متداول

1. **خطای "Class not found"**
   - بررسی کنید Composer autoloader بارگذاری شده باشد
   - مسیر فایل‌ها را چک کنید

2. **Template یافت نشد**
   - مسیرهای template را بررسی کنید
   - نام namespace را چک کنید

3. **HTMX کار نمی‌کند**
   - JavaScript console را بررسی کنید
   - نmonce ها معتبر باشند
   - URL های AJAX صحیح باشند

### Debug Mode

```php
// فعال‌سازی debug در wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// در کد PHP
if (WP_DEBUG) {
    error_log('Debug: ' . print_r($data, true));
}
```

## مشارکت

اگر مثال جدیدی دارید یا بهبودی در مثال‌های موجود، لطفاً:

1. Fork کنید
2. Branch جدید ایجاد کنید
3. مثال را با مستندات کامل اضافه کنید
4. Pull Request ارسال کنید

## پشتیبانی

- **مستندات**: [GitHub Wiki](https://github.com/jamal13647850/wp-helpers/wiki)
- **Issues**: [GitHub Issues](https://github.com/jamal13647850/wp-helpers/issues)
- **تلگرام**: [@jamal13647850](https://t.me/jamal13647850)

---

**نکته**: این مثال‌ها برای آموزش و نمونه طراحی شده‌اند. قبل از استفاده در محیط تولید، لطفاً آن‌ها را متناسب با نیاز پروژه خود تنظیم کنید.