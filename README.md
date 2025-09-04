# WordPress Helpers Package 🚀

یک کتابخانه قدرتمند و مدرن برای توسعه‌دهندگان WordPress که توسعه قالب‌ها و افزونه‌ها را سرعت می‌بخشد.

[![License](https://img.shields.io/github/license/jamal13647850/wp-helpers)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)](composer.json)
[![WordPress Version](https://img.shields.io/badge/WordPress-%3E%3D5.0-blue)](composer.json)
[![Latest Release](https://img.shields.io/github/v/release/jamal13647850/wp-helpers)](https://github.com/jamal13647850/wp-helpers/releases)

## 🌟 ویژگی‌ها

- 🎨 **سیستم Template پیشرفته** - موتور Twig برای قالب‌سازی مدرن
- ⚡ **سیستم کش چندلایه** - Redis، Object Cache، Transient
- 🌐 **پشتیبانی چندزبانه** - مدیریت زبان‌ها و ترجمه
- 📱 **HTMX Integration** - تعاملات AJAX مدرن و سریع
- 🎯 **Menu Components** - 7 نوع منوی آماده و قابل سفارشی‌سازی
- 🎪 **Slider Components** - اسلایدرهای پیشرفته با Swiper.js
- 🛡️ **امنیت پیشرفته** - CSRF، Rate Limiting، Validation
- 🔧 **ACF Integration** - مدیریت تنظیمات قالب
- 🎮 **Alpine.js Components** - رابط‌های کاربری تعاملی

## 🚀 شروع سریع

### نصب

```bash
composer require jamal13647850/wp-helpers
```

### راه‌اندازی پایه

```php
<?php
// functions.php
require_once get_template_directory() . '/vendor/autoload.php';

use jamal13647850\wphelpers\ServiceProvider;
ServiceProvider::boot();

// Helper function برای View
function get_theme_view() {
    static $view = null;
    if ($view === null) {
        $view = new \jamal13647850\wphelpers\Views\View();
        $view->addPath(get_template_directory() . '/templates', 'theme');
    }
    return $view;
}
```

### استفاده سریع

```twig
{# header.twig #}
<!DOCTYPE html>
<html {{ language_attributes() }} dir="rtl">
<head>{{ wp_head() }}</head>
<body {{ body_class() }}>
    <header>
        {{ menu('desktop', 'primary')|raw }}
    </header>
```

## 📚 مستندات

### راهنماهای کامل
- [📖 راهنمای کامل](COMPREHENSIVE_GUIDE.md) - مستندات جامع و تکمیلی
- [⚡ شروع سریع](QUICK_START.md) - راه‌اندازی در کمترین زمان ممکن  
- [💻 مثال‌های عملی](examples/) - کدهای آماده و قابل استفاده

### ویژگی‌های اصلی

#### 🎨 سیستم Template (Twig)
```php
$view = get_theme_view();
echo $view->render('page.twig', ['title' => 'عنوان صفحه']);
```

#### ⚡ کش پیشرفته
```php
use jamal13647850\wphelpers\Cache\CacheManager;
$cache = new CacheManager('object', 'myprefix_', 3600);
$data = $cache->remember('expensive_query', fn() => $wpdb->get_results($sql), 1800);
```

#### 🌐 چندزبانگی
```php
use jamal13647850\wphelpers\Language\LanguageManager;
$lang = LanguageManager::getInstance();
echo $lang->trans('welcome'); // خوش آمدید
```

#### 📱 HTMX Controllers
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

#### 🎯 Menu Components
```php
// 7 نوع منوی آماده
echo MenuManager::render('overlay-mobile', 'primary', [
    'accordion_mode' => 'independent',
    'enable_icons' => true
]);
```

#### 🎪 Slider Components
```php
$slides = [
    ['image' => 'slide1.jpg', 'title' => 'عنوان', 'content' => 'محتوا']
];
echo SliderManager::render('wide-auto', $slides, ['interval' => 5000]);
```

## 💡 مثال‌های کاربردی

### فرم تماس با HTMX و Validation

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
        
        wp_mail(get_option('admin_email'), 'تماس جدید', $_POST['message']);
        $this->render('success.twig', ['message' => 'پیام ارسال شد'], '#form');
    }
}
```

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

## 🎯 ویژگی‌های پیشرفته

### سیستم کش چندلایه
- **Redis Cache**: کارایی بالا برای سایت‌های پرترافیک
- **Object Cache**: کش داخلی WordPress
- **Transient Cache**: کش موقت با TTL
- **Auto-Fallback**: بازگشت خودکار به روش‌های جایگزین

### امنیت پیشرفته
- **CSRF Protection**: محافظت در برابر حملات CSRF
- **Rate Limiting**: محدودیت تعداد درخواست
- **Input Validation**: اعتبارسنجی کامل ورودی‌ها
- **XSS Prevention**: جلوگیری از حملات XSS
- **SQL Injection Protection**: محافظت در برابر SQL Injection

### بهینه‌سازی عملکرد
- **Lazy Loading**: بارگذاری به‌موقع اسکریپت‌ها
- **Asset Optimization**: بهینه‌سازی خودکار CSS/JS
- **Database Query Caching**: کش کوئری‌های پایگاه داده
- **Image Optimization**: بهینه‌سازی تصاویر

## 🛠️ ابزارهای توسعه

### ACF Pro Integration
```php
use jamal13647850\wphelpers\Utilities\Theme_Settings_ACF;

$settings = new Theme_Settings_ACF();
$logo = $settings->getOption('logo', 'header');
$show_slider = (bool) $settings->getOption('slider_show', 'homepage');
```

### کپچا و امنیت
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

## 📋 پیش‌نیازها

- PHP 7.4 یا بالاتر
- WordPress 5.0 یا بالاتر
- Composer
- ACF Pro (برای تنظیمات قالب)

## 🏗️ معماری

```
src/
├── Cache/                 # سیستم کش چندلایه
├── Components/           # کامپوننت‌های آماده
├── Controllers/          # کنترلرهای HTMX
├── Language/            # سیستم چندزبانه
├── Managers/            # مدیران مختلف (Menu، Slider، etc.)
├── Utilities/           # ابزارهای کمکی
└── Views/               # سیستم View و Twig
```

### الگوهای طراحی استفاده شده
- **Facade Pattern**: ساده‌سازی رابط‌های پیچیده
- **Singleton Pattern**: مدیریت instances منحصربه‌فرد
- **Factory Pattern**: ایجاد اشیاء بر اساس نوع
- **Observer Pattern**: مدیریت رویدادها
- **Strategy Pattern**: انتخاب الگوریتم در زمان اجرا

## 📱 سازگاری

### Frameworks و کتابخانه‌ها
- ✅ **Twig 3.x**: موتور template مدرن
- ✅ **HTMX 1.8+**: تعاملات AJAX بدون JavaScript
- ✅ **Alpine.js 3.x**: JavaScript framework سبک
- ✅ **Swiper.js 8.x**: اسلایدرهای پیشرفته
- ✅ **TailwindCSS**: framework CSS utility-first

### WordPress Plugins
- ✅ **ACF Pro**: تنظیمات پیشرفته قالب
- ✅ **WooCommerce**: فروشگاه آنلاین
- ✅ **WPML/Polylang**: چندزبانگی
- ✅ **Redis**: کش پیشرفته

## 🔧 پیکربندی

### تنظیمات Cache
```php
// wp-config.php
define('WP_CACHE', true);
define('WP_REDIS_HOST', '127.0.0.1');
define('WP_REDIS_PORT', 6379);
```

### تنظیمات Theme
```php
// در قالب
function theme_setup() {
    // پشتیبانی از قابلیت‌های مدرن
    add_theme_support('post-thumbnails');
    add_theme_support('menus');
    
    // ثبت منوها
    register_nav_menus([
        'primary' => 'منوی اصلی',
        'footer' => 'منوی فوتر'
    ]);
}
add_action('after_setup_theme', 'theme_setup');
```

## 🐛 عیب‌یابی

### فعال‌سازی Debug Mode
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### مشکلات رایج

#### 1. خطای "Class not found"
```bash
# بررسی autoloader
composer dump-autoload
```

#### 2. Template یافت نشد
```php
// بررسی مسیرها
$view = get_theme_view();
if (!$view->templateExists('my-template.twig')) {
    wp_die('Template یافت نشد');
}
```

#### 3. HTMX کار نمی‌کند
```javascript
// Console برای بررسی خطاها
console.log('HTMX loaded:', typeof htmx !== 'undefined');
```

## 🤝 مشارکت

ما از مشارکت شما استقبال می‌کنیم! لطفاً:

1. 🍴 **Fork** کنید
2. 🌿 **Branch** جدید ایجاد کنید (`feature/my-feature`)
3. 📝 تغییرات را **Commit** کنید
4. 📤 **Push** کنید به branch خود
5. 🔄 **Pull Request** ایجاد کنید

### استانداردهای کد
- PSR-12 coding standards
- PHPDoc برای همه متدها
- Unit tests برای ویژگی‌های جدید
- Semantic versioning

## 📄 مجوز

این پکیج تحت مجوز [MIT](LICENSE) منتشر شده است.

## 👨‍💻 سازنده

**Sayyed Jamal Ghasemi**
- 📧 Email: [info@jamalghasemi.com](mailto:info@jamalghasemi.com)
- 🔗 LinkedIn: [jamal1364](https://www.linkedin.com/in/jamal1364/)
- 📸 Instagram: [@jamal13647850](https://www.instagram.com/jamal13647850)
- 💬 Telegram: [@jamal13647850](https://t.me/jamal13647850)
- 🌐 Website: [jamalghasemi.com](https://jamalghasemi.com)

---

<p align="center">
  <strong>ساخته شده با ❤️ برای جامعه توسعه‌دهندگان WordPress</strong><br>
  اگر این پکیج برای شما مفید بود، لطفاً ⭐ بدهید!
</p>




