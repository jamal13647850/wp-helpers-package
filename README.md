# مستندات فنی کتابخانه WP Helpers

## 1. نگاه کلی به سیستم

### هدف
کتابخانه WP Helpers یک مجموعه ابزار و کلاس‌های کمکی برای توسعه‌دهندگان وردپرس است که امکانات گسترده‌ای را برای پیاده‌سازی سریع‌تر و کارآمدتر قابلیت‌های مختلف در پروژه‌های وردپرسی فراهم می‌کند. این کتابخانه با هدف ساده‌سازی توسعه افزونه‌ها و قالب‌های وردپرس طراحی شده است.

### معماری
کتابخانه WP Helpers با معماری شیءگرا (OOP) طراحی شده و از الگوهای طراحی مدرن مانند Facade، Singleton، و Strategy بهره می‌برد. این کتابخانه در فضای نام `jamal13647850\wphelpers` قرار دارد و شامل چندین زیرسیستم اصلی است:

1. **سیستم کش (Cache)**: مدیریت کش با پشتیبانی از چندین درایور مختلف
2. **سیستم نمایش (View)**: موتور قالب‌سازی مبتنی بر Twig
3. **سیستم اعتبارسنجی (Validation)**: اعتبارسنجی فرم‌ها و درخواست‌ها
4. **مدیریت HTMX**: پشتیبانی از تعاملات AJAX مدرن با استفاده از HTMX
5. **ابزارهای کاربردی**: مجموعه‌ای از کلاس‌های کمکی برای کار با ووکامرس، نظرات، امتیازدهی و غیره

### جایگاه کد در سیستم
این کتابخانه به عنوان یک لایه میانی بین هسته وردپرس و کد اختصاصی پروژه عمل می‌کند. توسعه‌دهندگان می‌توانند با استفاده از این کتابخانه، قابلیت‌های پیشرفته را بدون نیاز به نوشتن کد تکراری پیاده‌سازی کنند.

## 2. جزئیات فنی

### سیستم کش (Cache)

#### CacheInterface
رابط اصلی برای تمام درایورهای کش که متدهای استاندارد برای ذخیره، بازیابی و مدیریت داده‌های کش را تعریف می‌کند.

```php
interface CacheInterface {
    public function set(string $key, $value, ?int $expiration = null): bool;
    public function get(string $key, $default = null);
    public function delete(string $key): bool;
    public function exists(string $key): bool;
    public function flush(): bool;
    // و سایر متدها...
}
```

#### CacheManager
کلاس Facade برای سیستم کش که امکان استفاده از درایورهای مختلف را فراهم می‌کند.

**پارامترها**:
- `$driver_type`: نوع درایور کش ('transient', 'object', 'file')
- `$prefix`: پیشوند کلیدهای کش
- `$default_expiration`: زمان انقضای پیش‌فرض به ثانیه

**متدهای اصلی**:
- `set()`: ذخیره داده در کش
- `get()`: بازیابی داده از کش
- `delete()`: حذف داده از کش
- `remember()`: بازیابی داده از کش یا ذخیره نتیجه تابع callback

#### درایورهای کش
سه درایور اصلی برای سیستم کش وجود دارد:

1. **TransientCacheDriver**: استفاده از سیستم transient وردپرس
2. **ObjectCacheDriver**: استفاده از سیستم object cache وردپرس
3. **FileCacheDriver**: ذخیره کش در فایل (برای نمایش؛ برای محیط تولید توصیه نمی‌شود)

### سیستم نمایش (View)

#### ViewInterface
رابط اصلی برای سیستم نمایش که متدهای لازم برای رندر قالب‌ها را تعریف می‌کند.

#### View
پیاده‌سازی سیستم نمایش با استفاده از موتور قالب‌سازی Twig.

**متدهای اصلی**:
- `render()`: رندر یک قالب و برگرداندن نتیجه به صورت رشته
- `display()`: رندر یک قالب و نمایش مستقیم آن
- `render_with_exit()`: رندر یک قالب و خروج از اجرا

### سیستم اعتبارسنجی (Validation)

#### HTMX_Validator
کلاس اعتبارسنجی داده‌ها با پشتیبانی از قوانین مختلف اعتبارسنجی.

**متدهای اصلی**:
- `validate()`: اعتبارسنجی داده‌ها بر اساس قوانین تعریف شده
- `getErrors()`: دریافت خطاهای اعتبارسنجی
- `renderErrors()`: رندر خطاهای اعتبارسنجی

**قوانین اعتبارسنجی**:
- required: فیلد اجباری
- email: آدرس ایمیل معتبر
- url: آدرس URL معتبر
- numeric: مقدار عددی
- min/max: حداقل/حداکثر طول
- و بسیاری دیگر...

#### CommentValidationTrait
Trait برای اعتبارسنجی نظرات که شامل متدهای مشترک برای اعتبارسنجی نظرات است.

**متدهای اصلی**:
- `verifyNonce()`: بررسی اعتبار nonce
- `verifyCaptcha()`: بررسی اعتبار کپچا
- `verifyHoneypot()`: بررسی فیلد honeypot
- `applyRateLimiting()`: اعمال محدودیت تعداد درخواست

### مدیریت HTMX

#### HTMX_Controller
کلاس پایه برای کنترلرهای HTMX که امکان ایجاد کنترلرهای سفارشی را فراهم می‌کند.

**متدهای اصلی**:
- `registerRoutes()`: ثبت مسیرهای HTMX
- `handleRequest()`: پردازش درخواست‌های HTMX
- `render()`: رندر قالب‌ها
- `triggerEvent()`: ارسال رویداد به سمت کلاینت

#### HTMX_Handler
کلاس مدیریت درخواست‌های HTMX با پشتیبانی از middleware.

**متدهای اصلی**:
- `registerEndpoint()`: ثبت یک نقطه پایانی HTMX
- `registerMiddleware()`: ثبت یک middleware
- `handleEndpoint()`: پردازش درخواست‌های HTMX
- `registerAssets()`: ثبت فایل‌های JavaScript HTMX

### ابزارهای کاربردی

#### CaptchaManager
مدیریت کپچا برای فرم‌ها.

**متدهای اصلی**:
- `generate_captcha()`: تولید کپچا جدید
- `verify_captcha()`: بررسی اعتبار پاسخ کپچا
- `render_captcha()`: رندر HTML کپچا

#### BlogCommentsController
مدیریت نظرات وبلاگ.

**متدهای اصلی**:
- `prepareCommentsData()`: آماده‌سازی داده‌های نظرات
- `submitComment()`: ثبت نظر جدید
- `filterComments()`: فیلتر کردن نظرات

#### BlogRatingController
مدیریت امتیازدهی به مطالب وبلاگ.

**متدهای اصلی**:
- `handle_submit_rating()`: پردازش ثبت امتیاز
- `calculateRating()`: محاسبه میانگین امتیازات
- `hasUserRated()`: بررسی اینکه آیا کاربر قبلاً امتیاز داده است یا خیر

#### CartManager
مدیریت سبد خرید ووکامرس.

**متدهای اصلی**:
- `handle_add_to_cart_ajax()`: پردازش افزودن محصول به سبد خرید با AJAX
- `handle_add_to_cart_single_ajax()`: پردازش افزودن محصول تکی به سبد خرید با AJAX

#### AlpineNavWalker
کلاس سفارشی برای ایجاد منوهای پیشرفته با Alpine.js.

**متدهای اصلی**:
- `start_el()`: شروع یک آیتم منو
- `end_el()`: پایان یک آیتم منو
- `start_lvl()`: شروع یک سطح منو
- `end_lvl()`: پایان یک سطح منو

## 3. رابط برنامه‌نویسی (API)

### سیستم کش

```php
// ایجاد نمونه از CacheManager
$cache = new CacheManager('transient', 'my_prefix_', 3600);

// ذخیره داده در کش
$cache->set('key', $value, 3600); // ذخیره با زمان انقضای 1 ساعت

// بازیابی داده از کش
$value = $cache->get('key', $default);

// حذف داده از کش
$cache->delete('key');

// بازیابی یا ذخیره با استفاده از تابع callback
$value = $cache->remember('key', function() {
    return expensive_operation();
}, 3600);
```

### سیستم نمایش

```php
// ایجاد نمونه از View
$view = new View();

// رندر یک قالب
$html = $view->render('template.twig', ['key' => 'value']);

// نمایش مستقیم یک قالب
$view->display('template.twig', ['key' => 'value']);

// رندر و خروج
$view->render_with_exit('template.twig', ['key' => 'value'], 200);
```

### سیستم اعتبارسنجی

```php
// ایجاد نمونه از HTMX_Validator
$validator = new HTMX_Validator();

// تعریف قوانین اعتبارسنجی
$rules = [
    'name' => 'required|min:3',
    'email' => 'required|email',
    'age' => 'numeric|min:18'
];

// اعتبارسنجی داده‌ها
if ($validator->validate($_POST, $rules)) {
    // داده‌ها معتبر هستند
    $validated_data = $validator->getValidatedData();
} else {
    // داده‌ها نامعتبر هستند
    $errors = $validator->getErrors();
}
```

### مدیریت HTMX

```php
// ایجاد کنترلر سفارشی
class MyController extends HTMX_Controller {
    protected function getNamespace(): string {
        return 'my_controller';
    }
    
    protected function registerRoutes(): void {
        $this->addRoute('load_items', [
            'handler' => 'loadItems',
            'public' => true,
            'cache' => true
        ]);
    }
    
    public function loadItems() {
        $items = get_posts(['post_type' => 'item', 'posts_per_page' => 10]);
        $this->render('items/list.twig', ['items' => $items]);
    }
}

// استفاده از کنترلر
$controller = new MyController();
$url = $controller->getRouteUrl('load_items');
```

### ابزارهای کاربردی

```php
// استفاده از CaptchaManager
$captcha = new CaptchaManager();
$captcha_html = $captcha->render_captcha();

// بررسی اعتبار کپچا
$is_valid = $captcha->verify_captcha($_POST['captcha_answer'], $_POST['captcha_nonce'], $_POST['captcha_transient_key']);

// استفاده از BlogCommentsController
$comments_controller = new BlogCommentsController($view, 'comments/form.twig', $post_id, $captcha);
$comments_data = $comments_controller->prepareCommentsData();
$form_html = $comments_controller->render_comment_form();

// استفاده از CartManager
$cart_manager = new CartManager();
add_action('wp_ajax_add_to_cart_ajax', [$cart_manager, 'handle_add_to_cart_ajax']);
add_action('wp_ajax_nopriv_add_to_cart_ajax', [$cart_manager, 'handle_add_to_cart_ajax']);
```

## 4. مثال‌های کاربردی

### مثال 1: ایجاد سیستم کش برای نتایج پرس‌وجوهای وردپرس

```php
<?php
use jamal13647850\wphelpers\CacheManager;

// ایجاد نمونه از CacheManager
$cache = new CacheManager('transient', 'query_cache_', 3600);

/**
 * دریافت پست‌های اخیر با استفاده از کش
 */
function get_cached_recent_posts($count = 5, $category = null) {
    global $cache;
    
    // ایجاد کلید کش بر اساس پارامترها
    $cache_key = "recent_posts_{$count}_{$category}";
    
    // بازیابی از کش یا اجرای کوئری
    return $cache->remember($cache_key, function() use ($count, $category) {
        $args = [
            'posts_per_page' => $count,
            'post_status' => 'publish'
        ];
        
        if ($category) {
            $args['category_name'] = $category;
        }
        
        $query = new WP_Query($args);
        $posts = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $posts[] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'link' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url(),
                    'date' => get_the_date()
                ];
            }
            wp_reset_postdata();
        }
        
        return $posts;
    }, 3600); // کش برای 1 ساعت
}

// استفاده از تابع
$recent_posts = get_cached_recent_posts(5, 'news');
```

### مثال 2: ایجاد فرم نظرات با اعتبارسنجی و کپچا

```php
<?php
use jamal13647850\wphelpers\View;
use jamal13647850\wphelpers\CaptchaManager;
use jamal13647850\wphelpers\BlogCommentsController;

// ایجاد نمونه‌های مورد نیاز
$view = new View();
$captcha = new CaptchaManager($view);
$post_id = get_the_ID();

// ایجاد کنترلر نظرات
$comments_controller = new BlogCommentsController(
    $view,
    'components/blog/comment-form.twig',
    $post_id,
    $captcha
);

// ثبت اکشن‌های AJAX
add_action('wp_ajax_submit_blog_comment', [$comments_controller, 'handle_submit_comment']);
add_action('wp_ajax_nopriv_submit_blog_comment', [$comments_controller, 'handle_submit_comment']);

// در قالب وردپرس
function render_comments_section() {
    global $comments_controller;
    
    // دریافت داده‌های نظرات
    $comments_data = $comments_controller->prepareCommentsData();
    
    // رندر فرم نظرات
    $form_html = $comments_controller->render_comment_form();
    
    // رندر بخش نظرات
    echo $view->render('components/blog/comments-section.twig', [
        'comments' => $comments_data,
        'form' => $form_html,
        'post_id' => get_the_ID()
    ]);
}
```

### مثال 3: ایجاد یک کنترلر HTMX برای بارگذاری محصولات ووکامرس

```php
<?php
use jamal13647850\wphelpers\HTMX_Controller;
use jamal13647850\wphelpers\View;

/**
 * کنترلر محصولات ووکامرس
 */
class ProductsController extends HTMX_Controller {
    protected function getNamespace(): string {
        return 'products';
    }
    
    protected function registerRoutes(): void {
        $this->addRoute('load_products', [
            'handler' => 'loadProducts',
            'public' => true,
            'cache' => true,
            'cache_time' => 1800 // 30 دقیقه
        ]);
        
        $this->addRoute('filter_products', [
            'handler' => 'filterProducts',
            'public' => true
        ]);
        
        $this->addRoute('quick_view', [
            'handler' => 'quickView',
            'public' => true
        ]);
    }
    
    /**
     * بارگذاری محصولات
     */
    public function loadProducts() {
        $page = $this->getSanitizedParam('page', 1, 'int');
        $per_page = $this->getSanitizedParam('per_page', 12, 'int');
        
        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish'
        ];
        
        $products = wc_get_products($args);
        
        $this->render('products/list.twig', [
            'products' => $products,
            'page' => $page,
            'per_page' => $per_page
        ]);
    }
    
    /**
     * فیلتر محصولات
     */
    public function filterProducts() {
        $category = $this->getSanitizedParam('category');
        $min_price = $this->getSanitizedParam('min_price', 0, 'int');
        $max_price = $this->getSanitizedParam('max_price', 0, 'int');
        $orderby = $this->getSanitizedParam('orderby', 'date');
        
        $args = [
            'post_type' => 'product',
            'posts_per_page' => 12,
            'post_status' => 'publish'
        ];
        
        // اضافه کردن فیلترها
        if ($category) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $category
            ];
        }
        
        if ($min_price > 0 || $max_price > 0) {
            $args['meta_query'][] = [
                'key' => '_price',
                'value' => [$min_price, $max_price > 0 ? $max_price : 999999],
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC'
            ];
        }
        
        // مرتب‌سازی
        switch ($orderby) {
            case 'price-asc':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_price';
                $args['order'] = 'ASC';
                break;
            case 'price-desc':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_price';
                $args['order'] = 'DESC';
                break;
            case 'popularity':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = 'total_sales';
                $args['order'] = 'DESC';
                break;
            case 'date':
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }
        
        $products = wc_get_products($args);
        
        $this->render('products/filtered-list.twig', [
            'products' => $products,
            'filters' => [
                'category' => $category,
                'min_price' => $min_price,
                'max_price' => $max_price,
                'orderby' => $orderby
            ]
        ]);
    }
    
    /**
     * نمایش سریع محصول
     */
    public function quickView() {
        $product_id = $this->getSanitizedParam('product_id', 0, 'int');
        
        if (!$product_id) {
            $this->sendError('شناسه محصول نامعتبر است.');
            return;
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            $this->sendError('محصول یافت نشد.');
            return;
        }
        
        $this->render('products/quick-view.twig', [
            'product' => $product
        ]);
    }
}

// ایجاد نمونه از کنترلر
$products_controller = new ProductsController();

// در قالب HTML
// <button hx-get="<?php echo $products_controller->getRouteUrl('load_products'); ?>" hx-target="#products-container">
//     بارگذاری محصولات
// </button>
```

## 5. وابستگی‌ها و ارتباطات

### وابستگی‌های خارجی
- **وردپرس**: نسخه 5.0 یا بالاتر
- **PHP**: نسخه 7.4 یا بالاتر
- **Twig**: برای موتور قالب‌سازی
- **HTMX**: برای تعاملات AJAX مدرن
- **Alpine.js**: برای منوهای پیشرفته و تعاملات سمت کاربر

### ارتباطات داخلی

1. **CacheManager** از **CacheInterface** و درایورهای مختلف کش استفاده می‌کند.
2. **View** از **ViewInterface** پیروی می‌کند و برای رندر قالب‌ها استفاده می‌شود.
3. **BlogCommentsController** از **CommentValidationTrait** و **CaptchaManager** استفاده می‌کند.
4. **HTMX_Controller** و **HTMX_Handler** از **View** و **HTMX_Validator** استفاده می‌کنند.
5. **Config** برای مدیریت تنظیمات سراسری استفاده می‌شود.

### ساختار فایل‌ها
```
src/
  Cache/                    # سیستم کش
    CacheInterface.php      # رابط کش
    CacheManager.php        # مدیریت کش
    FileCacheDriver.php     # درایور کش فایل
    ObjectCacheDriver.php   # درایور کش شیء
    TransientCache.php      # درایور کش transient
  AlpineNavWalker.php       # منوهای Alpine.js
  BlogCommentsController.php # کنترلر نظرات وبلاگ
  BlogRatingController.php  # کنترلر امتیازدهی وبلاگ
  CaptchaManager.php        # مدیریت کپچا
  CartManager.php           # مدیریت سبد خرید
  Category.php              # کلاس کمکی دسته‌بندی
  CommentValidationTrait.php # Trait اعتبارسنجی نظرات
  Config.php                # مدیریت تنظیمات
  CPTCategory.php           # دسته‌بندی نوع پست سفارشی
  Helper.php                # توابع کمکی
  HTMX_Controller.php       # کنترلر HTMX
  HTMX_Handler.php          # پردازشگر HTMX
  HTMX_Validator.php        # اعتبارسنجی HTMX
  jdf.php                   # توابع تاریخ جلالی
  ProductCompare.php        # مقایسه محصولات
  ProductReviewsController.php # کنترلر نظرات محصول
  QuickViewManager.php      # مدیریت نمایش سریع
  SMSForgotPasswordController.php # بازیابی رمز عبور با SMS
  SMSLoginController.php    # ورود با SMS
  SMSRegisterController.php # ثبت‌نام با SMS
  TwigHelper.php            # کمک‌کننده Twig
  TwigHelperInterface.php   # رابط کمک‌کننده Twig
  UserMigration.php         # مهاجرت کاربران
  UserProfileManager.php    # مدیریت پروفایل کاربر
  View.php                  # سیستم نمایش
  ViewInterface.php         # رابط سیستم نمایش
  WishlistManager.php       # مدیریت لیست علاقه‌مندی‌ها
  WooCommerce.php           # کلاس کمکی ووکامرس
  WordPressTwigExtension.php # افزونه Twig برای وردپرس
```

## 6. محدودیت‌ها و نکات مهم

### محدودیت‌ها
1. **درایور FileCacheDriver**: برای محیط توسعه مناسب است و برای محیط تولید توصیه نمی‌شود.
2. **وابستگی به وردپرس**: این کتابخانه به طور خاص برای وردپرس طراحی شده و خارج از آن قابل استفاده نیست.
3. **سازگاری با PHP**: نیاز به PHP 7.4 یا بالاتر دارد.
4. **عملکرد کش**: در صورت استفاده نادرست از سیستم کش، ممکن است عملکرد سایت کاهش یابد.

### نکات مهم
1. **امنیت**: همیشه از توابع اعتبارسنجی و sanitize برای داده‌های ورودی استفاده کنید.
2. **عملکرد**: برای بهبود عملکرد، از سیستم کش به درستی استفاده کنید.
3. **سازگاری**: قبل از به‌روزرسانی کتابخانه، تغییرات را در محیط توسعه آزمایش کنید.
4. **تنظیمات**: برای تنظیم پیکربندی کتابخانه، از کلاس Config استفاده کنید.
5. **دیباگ**: در محیط توسعه، تنظیم `debug` را فعال کنید تا پیام‌های خطا را مشاهده کنید.

### بهترین شیوه‌های استفاده
1. **ایجاد کلاس‌های سفارشی**: به جای تغییر مستقیم کد کتابخانه، کلاس‌های سفارشی ایجاد کنید که از کلاس‌های کتابخانه ارث‌بری می‌کنند.
2. **استفاده از سیستم کش**: برای بهبود عملکرد، از سیستم کش برای ذخیره نتایج عملیات پرهزینه استفاده کنید.
3. **پیکربندی مناسب**: تنظیمات کتابخانه را متناسب با نیازهای پروژه خود پیکربندی کنید.
4. **استفاده از اعتبارسنجی**: همیشه از سیستم اعتبارسنجی برای بررسی داده‌های ورودی استفاده کنید.
5. **مستندسازی**: کد سفارشی خود را به خوبی مستند کنید و از نام‌گذاری معنادار استفاده کنید.
6. **آزمایش**: قبل از استفاده در محیط تولید، کد خود را در محیط توسعه آزمایش کنید.
7. **به‌روزرسانی**: کتابخانه را به‌روز نگه دارید تا از آخرین ویژگی‌ها و رفع اشکالات بهره‌مند شوید.
8. **استفاده از HTMX**: برای ایجاد رابط کاربری پویا، از قابلیت‌های HTMX استفاده کنید.
9. **مدیریت خطا**: همیشه خطاها را مدیریت کنید و پیام‌های خطای مناسب به کاربر نمایش دهید.
10. **استفاده از View**: برای جداسازی منطق از نمایش، از سیستم View استفاده کنید.

### نتیجه‌گیری

کتابخانه WP Helpers یک مجموعه قدرتمند از ابزارها و کلاس‌های کمکی برای توسعه‌دهندگان وردپرس است که می‌تواند فرآیند توسعه را سرعت بخشیده و کیفیت کد را بهبود دهد. با استفاده از این کتابخانه، می‌توانید از نوشتن کد تکراری جلوگیری کرده و از الگوهای طراحی مدرن بهره ببرید.

با درک عمیق از قابلیت‌های این کتابخانه و رعایت بهترین شیوه‌های استفاده، می‌توانید پروژه‌های وردپرسی حرفه‌ای و مقیاس‌پذیر ایجاد کنید که به راحتی قابل نگهداری و توسعه هستند.