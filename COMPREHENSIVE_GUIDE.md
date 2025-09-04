# راهنمای جامع پکیج WordPress Helpers (wp-helpers)

<div align="center">

![WordPress Helpers](https://img.shields.io/badge/WordPress-Helpers-blue)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-brightgreen)
![Twig Support](https://img.shields.io/badge/Twig-3.x-orange)
![License](https://img.shields.io/badge/License-GPL--2.0-red)

**مجموعه‌ای قدرتمند و جامع از کلاس‌های کمکی برای توسعه قالب‌ها و پلاگین‌های وردپرس**

[English Documentation](#english-documentation) | [مستندات فارسی](#persian-documentation)

</div>

---

## فهرست مطالب

- [معرفی](#معرفی)
- [ویژگی‌های کلیدی](#ویژگیهای-کلیدی)
- [نصب و راه‌اندازی](#نصب-و-راه‌اندازی)
- [معماری پکیج](#معماری-پکیج)
- [سیستم View و Twig](#سیستم-view-و-twig)
- [سیستم Cache](#سیستم-cache)
- [مدیریت زبان و ترجمه](#مدیریت-زبان-و-ترجمه)
- [Components (منو و اسلایدر)](#components)
- [Controllers و HTMX](#controllers-و-htmx)
- [Managers](#managers)
- [Utilities](#utilities)
- [Helpers](#helpers)
- [مثال‌های عملی](#مثالهای-عملی)
- [پیکربندی پیشرفته](#پیکربندی-پیشرفته)
- [بهترین روش‌ها](#بهترین-روشها)
- [عیب‌یابی](#عیب‌یابی)
- [مشارکت](#مشارکت)

---

## معرفی

پکیج **WordPress Helpers** یک مجموعه کامل و حرفه‌ای از ابزارها و کلاس‌های کمکی است که توسعه قالب‌ها و پلاگین‌های وردپرس را تسریع و تسهیل می‌کند. این پکیج با معماری مدرن PHP، استفاده از الگوهای طراحی بهینه و پشتیبانی کامل از تکنولوژی‌های روز طراحی شده است.

### چرا WordPress Helpers؟

- **معماری مدرن**: استفاده از PSR-4، Dependency Injection و Design Patterns
- **عملکرد بهینه**: سیستم Cache چندلایه با Redis و Fallback هوشمند
- **امنیت بالا**: پیاده‌سازی کامل CSRF Protection، Rate Limiting و Input Validation
- **توسعه‌پذیری**: Architecture قابل گسترش با Hook System و Plugin Architecture
- **چندزبانه**: پشتیبانی کامل از محلی‌سازی با مدیریت هوشمند کش
- **فارسی‌ساز**: طراحی شده با در نظر گیری نیازهای محتوای فارسی

---

## ویژگی‌های کلیدی

### 🎨 سیستم Template (Twig Integration)
- پیاده‌سازی کامل Twig برای WordPress
- namespace های قابل تنظیم
- توابع و فیلترهای از پیش تعریف شده WordPress
- مدیریت خطاهای هوشمند

### ⚡ سیستم Cache پیشرفته
- پشتیبانی Redis Object Cache با Auto-fallback
- سه نوع driver: Object, Transient, File
- عملیات Batch و Atomic
- مدیریت TTL هوشمند

### 🌐 مدیریت چندزبانه
- Singleton LanguageManager
- کش چندسطحه (Memory + Persistent)
- Auto-invalidation هنگام تغییر فایل ترجمه
- Fallback هوشمند

### 🧩 Components قدرتمند
- **Menu System**: 7 نوع منوی مختلف با Alpine.js
- **Slider System**: سیستم اسلایدر قابل گسترش
- **Walker Classes**: پیاده‌سازی پیشرفته برای Navigation

### 🔒 امنیت جامع
- HTMX Controller با امنیت چندلایه
- CSRF Protection و Nonce Verification
- Rate Limiting و Throttling
- Input Validation با 20+ قانون

### 📱 HTMX و Modern Frontend
- کنترلرهای تخصصی HTMX
- Validation real-time
- Progressive Enhancement
- Alpine.js Integration

---

## نصب و راه‌اندازی

### پیش‌نیازها

```json
{
  "php": ">=7.4",
  "wordpress": ">=5.0",
  "twig/twig": "^3.0",
  "jamal13647850/sms-api": "^2.4"
}
```

### نصب با Composer

```bash
composer require jamal13647850/wp-helpers
```

### راه‌اندازی در قالب

#### functions.php

```php
<?php
// بارگذاری Composer Autoloader
require_once get_template_directory() . '/vendor/autoload.php';

// راه‌اندازی ServiceProvider
use jamal13647850\wphelpers\ServiceProvider;
ServiceProvider::boot();
```

#### پیکربندی Config

```php
// config/config.php
<?php
return [
    'cache' => [
        'default_driver' => 'object', // object, transient, file
        'default_ttl' => 3600,
        'prefix' => 'mytheme_'
    ],
    'twig' => [
        'debug' => WP_DEBUG,
        'cache' => wp_upload_dir()['basedir'] . '/twig-cache',
        'auto_reload' => WP_DEBUG
    ],
    'language' => [
        'default_locale' => 'fa_IR',
        'fallback_locale' => 'en_US'
    ]
];
```

---

## معماری پکیج

### ساختار فولدرها

```
src/
├── Assets/              # مدیریت Asset ها
├── Cache/              # سیستم کش چندلایه
│   ├── CacheInterface.php
│   ├── CacheManager.php
│   ├── ObjectCacheDriver.php
│   ├── TransientCacheDriver.php
│   └── FileCacheDriver.php
├── Components/         # اجزای رابط کاربری
│   ├── Menu/          # سیستم منو
│   └── Slider/        # سیستم اسلایدر
├── Controllers/       # کنترلرهای HTMX
├── Helpers/           # توابع کمکی
├── Language/          # مدیریت چندزبانه
├── Managers/          # مدیریت‌کننده‌ها
├── Models/            # مدل‌های داده
├── Navigation/        # Walker های منو
├── Utilities/         # ابزارهای کاربردی
├── Views/             # سیستم Template
├── Traits/            # Trait های قابل استفاده مجدد
├── Integrations/      # ادغام با پلاگین‌ها
├── Config.php         # مدیریت تنظیمات
└── ServiceProvider.php # Bootstrap اصلی
```

### الگوهای طراحی استفاده شده

- **Factory Pattern**: در Manager کلاس‌ها
- **Singleton Pattern**: در LanguageManager و ServiceProvider
- **Strategy Pattern**: در Cache Drivers
- **Template Method Pattern**: در Abstract کلاس‌ها
- **Observer Pattern**: در Hook System
- **Value Object Pattern**: در Options کلاس‌ها

---

## سیستم View و Twig

### راه‌اندازی پایه

```php
use jamal13647850\wphelpers\Views\View;

$view = new View();

// رندر template ساده
echo $view->render('components/hero.twig', [
    'title' => 'عنوان اصلی',
    'content' => 'محتوای صفحه'
]);
```

### افزودن مسیرهای سفارشی

```php
$view->addPath(get_template_directory() . '/templates', 'theme');
$view->addPath(get_template_directory() . '/components', 'components');

// استفاده از namespace
echo $view->render('@theme/page.twig');
echo $view->render('@components/button.twig');
```

### ثبت توابع سفارشی

```php
$view->registerFunction('get_theme_option', function($key, $default = '') {
    return get_theme_mod($key, $default);
});

$view->registerFunction('format_price', function($price) {
    return number_format($price) . ' تومان';
});
```

### استفاده در Template

```twig
{# templates/single-post.twig #}
<!DOCTYPE html>
<html {{ language_attributes() }}>
<head>
    {{ wp_head() }}
</head>
<body {{ body_class() }}>
    
<header class="site-header">
    <h1>{{ get_theme_option('site_title', get_bloginfo('name')) }}</h1>
</header>

<main class="main-content">
    <article class="post">
        <h1 class="post-title">{{ post.post_title }}</h1>
        <div class="post-meta">
            نوشته شده در {{ post.post_date|date('Y/m/d') }}
        </div>
        <div class="post-content">
            {{ post.post_content|raw }}
        </div>
    </article>
</main>

{{ wp_footer() }}
</body>
</html>
```

---

## سیستم Cache

### استفاده پایه

```php
use jamal13647850\wphelpers\Cache\CacheManager;

$cache = new CacheManager('object', 'mytheme_', 3600);

// ذخیره و دریافت
$cache->set('user_data', $userData, 1800);
$userData = $cache->get('user_data');

// حذف
$cache->delete('user_data');
```

### Remember Pattern

```php
$expensiveData = $cache->remember('complex_query', function() {
    // عملیات پرهزینه
    return $wpdb->get_results("SELECT * FROM complex_table");
}, 3600);
```

### عملیات دسته‌ای

```php
// ذخیره چند آیتم
$cache->setMultiple([
    'key1' => 'value1',
    'key2' => 'value2',
    'key3' => 'value3'
], 1800);

// دریافت چند آیتم
$values = $cache->getMultiple(['key1', 'key2', 'key3']);
```

### کش با Redis

```php
// تنظیمات Redis در wp-config.php
define('WP_REDIS_HOST', 'localhost');
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_DATABASE', 0);

// استفاده از ObjectCacheDriver
$cache = new CacheManager('object', 'myprefix_');

// Auto-fallback به Transient در صورت عدم دسترسی Redis
```

---

## مدیریت زبان و ترجمه

### راه‌اندازی

```php
use jamal13647850\wphelpers\Language\LanguageManager;

$lang = LanguageManager::getInstance();
```

### ایجاد فایل ترجمه

```php
// src/Language/lang/fa_IR.php
<?php
return [
    'welcome' => 'خوش آمدید',
    'login' => 'ورود',
    'register' => 'ثبت‌نام',
    'search' => 'جستجو',
    'user_message' => 'سلام :name، شما :count پیام دارید'
];
```

```php
// src/Language/lang/en_US.php
<?php
return [
    'welcome' => 'Welcome',
    'login' => 'Login', 
    'register' => 'Register',
    'search' => 'Search',
    'user_message' => 'Hello :name, you have :count messages'
];
```

### استفاده در PHP

```php
// ترجمه ساده
echo $lang->trans('welcome');

// ترجمه با متغیرها
echo $lang->trans('user_message', 'fa_IR', 'پیام پیش‌فرض', [
    'name' => 'احمد',
    'count' => 5
]);
```

### استفاده در Twig

```twig
<h1>{{ trans('welcome') }}</h1>
<p>{{ trans('user_message', {'name': user.name, 'count': user.message_count}) }}</p>
```

---

## Components

### Menu System

#### انواع منوهای موجود

1. **SimpleMenu**: منوی ساده افقی
2. **DropdownMenu**: منو با dropdown
3. **MobileMenu**: منوی accordion موبایل
4. **DesktopMenu**: منوی دسکتاپ پیشرفته
5. **MultiColumnDesktopMenu**: منوی چند ستونی
6. **OverlayMobileMenu**: منوی overlay موبایل
7. **OverlayMobileWithToggle**: منوی overlay با toggle

#### استفاده پایه

```php
use jamal13647850\wphelpers\Components\Menu\MenuManager;

// رندر منوی ساده
echo MenuManager::render('simple', 'primary');

// رندر با گزینه‌های سفارشی
echo MenuManager::render('overlay-mobile', 'primary', [
    'max_depth' => 3,
    'accordion_mode' => 'classic',
    'enable_icons' => true,
    'custom_classes' => 'my-mobile-menu'
]);
```

#### استفاده در Twig

```twig
{# رندر منوی دسکتاپ #}
{{ menu('desktop', 'primary', {
    'max_depth': 2,
    'enable_mega_menu': true
})|raw }}

{# رندر منوی موبایل #}
{{ menu('overlay-mobile', 'primary', {
    'accordion_mode': 'independent',
    'enable_icons': true
})|raw }}
```

#### پیکربندی پیشرفته منو

```php
// ثبت منوی سفارشی
MenuManager::register('my-custom-menu', MyCustomMenu::class);

class MyCustomMenu extends AbstractMenu 
{
    protected function getViewsPath(): string 
    {
        return get_template_directory() . '/menu-templates/';
    }
    
    public function render(string $theme_location, array $options = []): string 
    {
        $options = $this->createOptions($options, [
            'container_class' => 'custom-menu-container',
            'menu_class' => 'custom-menu-list'
        ]);
        
        $walker = $this->createWalker($options);
        
        return wp_nav_menu([
            'theme_location' => $theme_location,
            'walker' => $walker,
            'echo' => false,
            'container_class' => $options['container_class']
        ]);
    }
}
```

### Slider System

#### استفاده پایه

```php
use jamal13647850\wphelpers\Components\Slider\SliderManager;

$slides = [
    [
        'image' => 'https://example.com/slide1.jpg',
        'title' => 'عنوان اسلاید اول',
        'content' => 'محتوای اسلاید',
        'button_text' => 'بیشتر بخوانید',
        'button_link' => '/about'
    ],
    [
        'image' => 'https://example.com/slide2.jpg', 
        'title' => 'عنوان اسلاید دوم'
    ]
];

echo SliderManager::render('wide-auto', $slides, [
    'interval' => 5000,
    'height' => 600
]);
```

#### ایجاد اسلایدر سفارشی

```php
use jamal13647850\wphelpers\Components\Slider\AbstractSlider;

class HeroSlider extends AbstractSlider 
{
    protected function getViewsPath(): string 
    {
        return get_template_directory() . '/slider-templates/';
    }
    
    public function render(array $slides, array $options = []): string 
    {
        $options = $this->createOptions($options, [
            'autoplay' => true,
            'dots' => true,
            'arrows' => false
        ]);
        
        return $this->view->render('@slider_hero/hero.twig', [
            'slides' => $this->sanitizeSlides($slides),
            'options' => $options
        ]);
    }
    
    protected function defaultOptions(): array 
    {
        return [
            'autoplay' => true,
            'interval' => 4000,
            'height' => 500
        ];
    }
}

// ثبت اسلایدر
SliderManager::register('hero', HeroSlider::class);
```

---

## Controllers و HTMX

### HTMX Controller پایه

```php
use jamal13647850\wphelpers\Controllers\HTMX_Controller;

class ContactController extends HTMX_Controller 
{
    protected function registerRoutes(): void 
    {
        $this->addRoute('submit_contact', 'handleContactForm', [
            'methods' => ['POST'],
            'capability' => 'read', // یا false برای عمومی
            'middlewares' => ['throttle:5,60'] // 5 درخواست در 60 ثانیه
        ]);
    }
    
    protected function handleContactForm(): void 
    {
        $validator = new HTMX_Validator($this->view);
        
        $validation = $validator->validate($_POST, [
            'name' => 'required|min:2',
            'email' => 'required|email',
            'message' => 'required|min:10'
        ]);
        
        if (!$validation['isValid']) {
            $validator->sendHtmxResponse([
                'template' => 'forms/contact-errors.twig',
                'data' => ['errors' => $validation['errors']],
                'target' => '#contact-form-errors'
            ]);
            return;
        }
        
        // پردازش فرم
        $this->processContactForm($_POST);
        
        // پاسخ موفقیت
        $this->render('forms/contact-success.twig', [
            'message' => 'پیام شما با موفقیت ارسال شد'
        ], '#contact-form');
    }
}

// فعال‌سازی کنترلر
new ContactController();
```

### استفاده در Frontend

```html
<form hx-post="<?php echo wp_ajax_url(); ?>?action=htmx_contact_submit_contact" 
      hx-target="#form-response">
    <input name="name" placeholder="نام" required>
    <input name="email" type="email" placeholder="ایمیل" required>
    <textarea name="message" placeholder="پیام" required></textarea>
    <button type="submit">ارسال</button>
</form>
<div id="form-response"></div>
```

### اعتبارسنجی Real-time

```php
protected function registerRoutes(): void 
{
    $this->addRoute('validate_email', 'validateEmailField', [
        'methods' => ['POST'],
        'capability' => false
    ]);
}

protected function validateEmailField(): void 
{
    $email = sanitize_email($_POST['email'] ?? '');
    
    if (!is_email($email)) {
        $this->setHtmxHeader('HX-Reswap', 'innerHTML');
        echo '<span class="error">ایمیل نامعتبر است</span>';
        return;
    }
    
    if (email_exists($email)) {
        echo '<span class="warning">این ایمیل قبلاً ثبت شده</span>';
        return;
    }
    
    echo '<span class="success">ایمیل معتبر است</span>';
}
```

```html
<input name="email" 
       hx-post="<?php echo wp_ajax_url(); ?>?action=htmx_contact_validate_email"
       hx-trigger="blur"
       hx-target="next .validation-message">
<div class="validation-message"></div>
```

---

## Managers

### WishlistManager

```php
use jamal13647850\wphelpers\Managers\WishlistManager;

$wishlist = new WishlistManager();

// اضافه/حذف محصول
$wishlist->toggle_wishlist(123, get_current_user_id());

// دریافت لیست علاقه‌مندی‌ها
$items = $wishlist->getWishlistData(get_current_user_id());

// بررسی وجود محصول در لیست
$is_wishlisted = $wishlist->check_if_product_is_wishlisted(123, get_current_user_id());
```

### CaptchaManager

```php
use jamal13647850\wphelpers\Managers\CaptchaManager;

$captcha = new CaptchaManager();

// تولید کپچا
$captcha_data = $captcha->generate_captcha('medium');

// تایید کپچا
$is_valid = $captcha->verify_captcha($_POST['captcha_answer'], $_POST['captcha_token']);
```

### UserProfileManager

```php
use jamal13647850\wphelpers\Managers\UserProfileManager;

// فعال‌سازی فیلدهای اضافی پروفایل
new UserProfileManager();
```

---

## Utilities

### Theme_Settings_ACF

```php
use jamal13647850\wphelpers\Utilities\Theme_Settings_ACF;

$settings = new Theme_Settings_ACF();

// دریافت تنظیم خاص
$logo = $settings->getOption('logo_main', 'header');

// دریافت همه تنظیمات یک گروه
$header_settings = $settings->getOption(null, 'header');

// استفاده با cast boolean
$show_slider = (bool) $settings->getOption('slider_show', 'homepage');
```

### Clear_Theme_Cache

```php
use jamal13647850\wphelpers\Utilities\Clear_Theme_Cache;

// فعال‌سازی دکمه پاکسازی کش در admin bar
new Clear_Theme_Cache();
```

---

## Helpers

### TwigHelper

```php
use jamal13647850\wphelpers\Helpers\TwigHelper;

$twig = TwigHelper::createInstance([
    'debug' => WP_DEBUG,
    'cache' => wp_upload_dir()['basedir'] . '/twig-cache'
]);

$twig->addFunction('custom_function', function($param) {
    return "Custom: " . $param;
});
```

### JalaliDate

```php
use jamal13647850\wphelpers\Helpers\JalaliDate;

$jalali = new JalaliDate();

// تبدیل تاریخ میلادی به شمسی
$persian_date = $jalali->gregorian_to_jalali('2023-12-25');

// فرمت کردن تاریخ
$formatted = $jalali->format_date('2023-12-25', 'Y/m/d');
```

---

## مثال‌های عملی

### 1. ایجاد صفحه تماس با HTMX

```php
// ContactController.php
class ContactController extends HTMX_Controller 
{
    protected function registerRoutes(): void 
    {
        $this->addRoute('submit', 'handleSubmit', [
            'methods' => ['POST'],
            'middlewares' => ['throttle:3,300']
        ]);
    }
    
    protected function handleSubmit(): void 
    {
        $validator = new HTMX_Validator($this->view);
        
        $result = $validator->validate($_POST, [
            'name' => 'required|min:2|max:50',
            'email' => 'required|email',
            'phone' => 'required|regex:/^09\d{9}$/',
            'message' => 'required|min:10',
            'captcha' => 'required'
        ]);
        
        if (!$result['isValid']) {
            $validator->renderErrors($result['errors'], '#contact-errors');
            return;
        }
        
        // بررسی کپچا
        $captcha = new CaptchaManager();
        if (!$captcha->verify_captcha($_POST['captcha'], $_POST['captcha_token'])) {
            $validator->renderErrors(['captcha' => 'کپچا اشتباه است'], '#contact-errors');
            return;
        }
        
        // ارسال ایمیل
        $this->sendContactEmail($_POST);
        
        $this->render('contact/success.twig', [
            'message' => 'پیام شما با موفقیت ارسال شد'
        ], '#contact-form');
    }
}
```

### 2. ایجاد اسلایدر سفارشی

```php
// HeroSlider.php
class HeroSlider extends AbstractSlider 
{
    protected function getViewsPath(): string 
    {
        return get_template_directory() . '/templates/sliders/';
    }
    
    public function render(array $slides, array $options = []): string 
    {
        $this->enqueueAssets();
        
        $options = $this->createOptions($options, [
            'autoplay' => true,
            'dots' => true,
            'arrows' => false,
            'transition' => 'fade'
        ]);
        
        return $this->view->render('@slider_hero/hero.twig', [
            'slides' => $this->processSlides($slides),
            'options' => $options,
            'unique_id' => 'hero-slider-' . uniqid()
        ]);
    }
    
    protected function enqueueAssets(): void 
    {
        wp_enqueue_script('swiper-js', 'https://unpkg.com/swiper/swiper-bundle.min.js');
        wp_enqueue_style('swiper-css', 'https://unpkg.com/swiper/swiper-bundle.min.css');
    }
}
```

### 3. منوی چند ستونی با آیکون

```php
// functions.php
add_action('after_setup_theme', function() {
    register_nav_menus([
        'primary' => 'منوی اصلی',
        'footer' => 'منوی فوتر'
    ]);
});

// در template
echo MenuManager::render('multi-column-desktop', 'primary', [
    'columns' => 3,
    'enable_icons' => true,
    'max_depth' => 2,
    'container_class' => 'main-navigation',
    'menu_class' => 'primary-menu'
]);
```

---

## پیکربندی پیشرفته

### تنظیمات کش

```php
// wp-config.php
define('WP_CACHE_TYPE', 'object'); // object, transient, file
define('WP_CACHE_PREFIX', 'mytheme_');
define('WP_CACHE_TTL', 3600);

// Redis تنظیمات
define('WP_REDIS_HOST', 'localhost');
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_DATABASE', 0);
```

### تنظیمات Twig

```php
$config = [
    'twig' => [
        'debug' => WP_DEBUG,
        'cache' => wp_upload_dir()['basedir'] . '/twig-cache',
        'auto_reload' => WP_DEBUG,
        'strict_variables' => false,
        'autoescape' => 'html'
    ]
];
```

### هوک‌های سفارشی

```php
// قبل از رندر منو
add_action('menu/before_render', function($key, $theme_location, $options) {
    // عملیات پیش از رندر
});

// بعد از رندر منو
add_action('menu/after_render', function($key, $output, $theme_location, $options) {
    // عملیات پس از رندر
});

// تغییر خروجی منو
add_filter('menu/render_output', function($output, $key, $theme_location, $options) {
    return $output . '<div class="menu-footer">پاورقی منو</div>';
});
```

---

## بهترین روش‌ها

### امنیت

1. **همیشه از nonce استفاده کنید**
2. **ورودی‌ها را Sanitize کنید**
3. **خروجی‌ها را Escape کنید**
4. **از Rate Limiting استفاده کنید**
5. **Capability ها را بررسی کنید**

### عملکرد

1. **از کش استفاده کنید**
2. **Asset ها را بهینه کنید**
3. **Database Query ها را کم کنید**
4. **Lazy Loading پیاده کنید**

### کیفیت کد

1. **از Type Declarations استفاده کنید**
2. **DocBlock های کامل بنویسید**
3. **خطاها را مدیریت کنید**
4. **Unit Test بنویسید**

---

## عیب‌یابی

### مشکلات متداول

#### 1. خطای "Class not found"

```php
// بررسی autoloader
if (!file_exists(get_template_directory() . '/vendor/autoload.php')) {
    wp_die('لطفا composer install را اجرا کنید');
}
```

#### 2. مشکل کش Redis

```php
// بررسی اتصال Redis
$cache = new CacheManager('object');
if (!$cache->isRedisAvailable()) {
    error_log('Redis در دسترس نیست، fallback به transient');
}
```

#### 3. خطای Twig Template

```php
// فعال‌سازی debug mode
$view = new View();
$view->addGlobal('WP_DEBUG', WP_DEBUG);

// بررسی وجود template
if (!$view->templateExists('my-template.twig')) {
    wp_die('Template یافت نشد');
}
```

### لاگ‌گیری

```php
// فعال‌سازی لاگ در wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// لاگ سفارشی
if (WP_DEBUG) {
    error_log('WP Helpers Debug: ' . print_r($data, true));
}
```

---

## مشارکت

### راهنمای مشارکت

1. Fork کردن repository
2. ایجاد branch جدید (`git checkout -b feature/my-feature`)
3. Commit تغییرات (`git commit -am 'Add new feature'`)
4. Push به branch (`git push origin feature/my-feature`)
5. ایجاد Pull Request

### استانداردهای کد

- PSR-4 Autoloading
- PSR-12 Coding Style
- DocBlock کامل
- Type Declarations
- Unit Testing

---

## لایسنس

این پروژه تحت لایسنس GPL-2.0-or-later منتشر شده است.

---

## حمایت و پشتیبانی

- **مستندات**: [GitHub Wiki](https://github.com/jamal13647850/wp-helpers/wiki)
- **Issues**: [GitHub Issues](https://github.com/jamal13647850/wp-helpers/issues)
- **Email**: info@jamalghasemi.com
- **Telegram**: [@jamal13647850](https://t.me/jamal13647850)

---

## ویرایش‌ها

- **v3.3.8**: رفع مشکل getOption در Theme_Settings_ACF
- **v3.3.7**: بهبود سیستم کش و fallback
- **v3.3.6**: افزودن HTMX Controllers
- **v3.3.5**: بهبود سیستم منو و اسلایدر

---

<div align="center">

**ساخته شده با ❤️ توسط [Sayyed Jamal Ghasemi](https://jamalghasemi.com)**

![GitHub stars](https://img.shields.io/github/stars/jamal13647850/wp-helpers)
![GitHub forks](https://img.shields.io/github/forks/jamal13647850/wp-helpers)
![GitHub issues](https://img.shields.io/github/issues/jamal13647850/wp-helpers)

</div>