<?php
/**
 * مثال ایجاد اسلایدر سفارشی با WordPress Helpers
 * 
 * این مثال نشان می‌دهد که چگونه:
 * - اسلایدر سفارشی ایجاد کنید
 * - Asset ها را مدیریت کنید
 * - گزینه‌های پیش‌فرض تنظیم کنید
 * - Template سفارشی استفاده کنید
 */

use jamal13647850\wphelpers\Components\Slider\AbstractSlider;
use jamal13647850\wphelpers\Components\Slider\SliderManager;

class HeroSlider extends AbstractSlider 
{
    /**
     * مسیر template های اسلایدر
     */
    protected function getViewsPath(): string 
    {
        return get_template_directory() . '/templates/sliders/';
    }
    
    /**
     * رندر اسلایدر
     */
    public function render(array $slides, array $options = []): string 
    {
        // بارگذاری Asset های ضروری
        $this->enqueueAssets();
        
        // ایجاد گزینه‌ها با مقادیر پیش‌فرض
        $options = $this->createOptions($options, [
            'autoplay' => true,
            'dots' => true,
            'arrows' => false,
            'transition' => 'fade',
            'height' => 500,
            'responsive' => true
        ]);
        
        // پردازش اسلایدها
        $processed_slides = $this->processSlides($slides);
        
        // رندر template
        return $this->view->render('@slider_hero/hero.twig', [
            'slides' => $processed_slides,
            'options' => $options,
            'unique_id' => 'hero-slider-' . uniqid(),
            'slider_class' => $this->getSliderClass($options)
        ]);
    }
    
    /**
     * گزینه‌های پیش‌فرض اسلایدر
     */
    protected function defaultOptions(): array 
    {
        return [
            'autoplay' => true,
            'interval' => 4000,
            'height' => 500,
            'transition' => 'slide',
            'dots' => true,
            'arrows' => false,
            'responsive' => true,
            'pause_on_hover' => true,
            'infinite' => true,
            'speed' => 500
        ];
    }
    
    /**
     * بارگذاری Asset های ضروری
     */
    protected function enqueueAssets(): void 
    {
        // Swiper.js برای اسلایدر
        wp_enqueue_script(
            'swiper-js', 
            'https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js',
            [],
            '8.4.5',
            true
        );
        
        wp_enqueue_style(
            'swiper-css',
            'https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css',
            [],
            '8.4.5'
        );
        
        // اسکریپت سفارشی اسلایدر
        wp_enqueue_script(
            'hero-slider-js',
            get_template_directory_uri() . '/assets/js/hero-slider.js',
            ['swiper-js'],
            '1.0.0',
            true
        );
        
        // CSS سفارشی
        wp_enqueue_style(
            'hero-slider-css',
            get_template_directory_uri() . '/assets/css/hero-slider.css',
            ['swiper-css'],
            '1.0.0'
        );
    }
    
    /**
     * پردازش داده‌های اسلایدها
     */
    private function processSlides(array $slides): array 
    {
        $processed = [];
        
        foreach ($slides as $slide) {
            $processed_slide = [
                'image' => $this->processImage($slide['image'] ?? ''),
                'mobile_image' => $this->processImage($slide['mobile_image'] ?? ''),
                'title' => sanitize_text_field($slide['title'] ?? ''),
                'subtitle' => sanitize_text_field($slide['subtitle'] ?? ''),
                'description' => wp_kses_post($slide['description'] ?? ''),
                'button_text' => sanitize_text_field($slide['button_text'] ?? ''),
                'button_link' => esc_url($slide['button_link'] ?? ''),
                'text_position' => in_array($slide['text_position'] ?? 'right', ['left', 'center', 'right']) 
                    ? $slide['text_position'] : 'right',
                'overlay_opacity' => intval($slide['overlay_opacity'] ?? 50),
                'text_color' => sanitize_hex_color($slide['text_color'] ?? '#ffffff')
            ];
            
            $processed[] = $processed_slide;
        }
        
        return $processed;
    }
    
    /**
     * پردازش تصاویر اسلایدر
     */
    private function processImage($image): array 
    {
        if (empty($image)) {
            return [];
        }
        
        // اگر ACF array است
        if (is_array($image)) {
            return [
                'url' => $image['url'] ?? '',
                'alt' => $image['alt'] ?? '',
                'width' => $image['width'] ?? 0,
                'height' => $image['height'] ?? 0,
                'sizes' => $image['sizes'] ?? []
            ];
        }
        
        // اگر URL ساده است
        if (is_string($image)) {
            return [
                'url' => $image,
                'alt' => '',
                'width' => 0,
                'height' => 0,
                'sizes' => []
            ];
        }
        
        return [];
    }
    
    /**
     * تعیین کلاس‌های CSS اسلایدر
     */
    private function getSliderClass(array $options): string 
    {
        $classes = ['hero-slider'];
        
        if ($options['responsive']) {
            $classes[] = 'hero-slider--responsive';
        }
        
        if ($options['dots']) {
            $classes[] = 'hero-slider--with-dots';
        }
        
        if ($options['arrows']) {
            $classes[] = 'hero-slider--with-arrows';
        }
        
        $classes[] = 'hero-slider--transition-' . $options['transition'];
        
        return implode(' ', $classes);
    }
}

// ثبت اسلایدر در منیجر
add_action('init', function() {
    if (class_exists('jamal13647850\wphelpers\Components\Slider\SliderManager')) {
        SliderManager::register('hero', HeroSlider::class);
    }
});

// تابع کمکی برای استفاده آسان
function render_hero_slider($slides, $options = []) {
    if (class_exists('jamal13647850\wphelpers\Components\Slider\SliderManager')) {
        return SliderManager::render('hero', $slides, $options);
    }
    return '';
}

// Hook برای بهبود اسلایدر
add_action('slider/before_render_hero', function($slides, $options) {
    // اضافه کردن structured data
    if (!empty($slides)) {
        add_action('wp_head', function() use ($slides) {
            echo '<script type="application/ld+json">';
            echo json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'ImageGallery',
                'name' => get_bloginfo('name') . ' - اسلایدر اصلی',
                'image' => array_map(function($slide) {
                    return $slide['image']['url'] ?? '';
                }, $slides)
            ]);
            echo '</script>';
        });
    }
});