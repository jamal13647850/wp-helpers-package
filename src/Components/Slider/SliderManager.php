<?php

/*
Sayyed Jamal Ghasemi — Full-Stack Developer  
📧 info@jamalghasemi.com  
🔗 LinkedIn: https://www.linkedin.com/in/jamal1364/  
📸 Instagram: https://www.instagram.com/jamal13647850  
💬 Telegram: https://t.me/jamal13647850  
🌐 https://jamalghasemi.com  
*/

namespace jamal13647850\wphelpers\Components\Slider;

use InvalidArgumentException;

/**
 * SliderManager
 *
 * Manages the registration and instantiation of slider variants.
 *
 * Usage:
 *   - Register slider variants with unique keys during package bootstrapping.
 *   - Instantiate a registered slider by key using the factory method.
 */
final class SliderManager
{
    /**
     * Map of slider keys to their corresponding class names.
     *
     * @var array<string, class-string<AbstractSlider>>
     */
    private static array $map = [];

    /**
     * Register a new slider variant with a unique key.
     *
     * Only call this method once during the package boot sequence.
     *
     * @param string $key   Unique identifier for the slider variant.
     * @param string $class Fully qualified class name of the slider (must extend AbstractSlider).
     * 
     * @throws InvalidArgumentException If the class does not extend AbstractSlider.
     * 
     * @return void
     *
     * @example
     *   SliderManager::register('carousel', CarouselSlider::class);
     */
    public static function register(string $key, string $class): void
    {
        if (!is_subclass_of($class, AbstractSlider::class)) {
            // Exception message translated to Persian (fa-IR)
            throw new InvalidArgumentException("{$class} باید از AbstractSlider ارث‌بری کند.");
        }
        self::$map[$key] = $class;
    }

    /**
     * Create a new instance of a registered slider variant by key.
     *
     * @param string $key The key used during registration.
     * 
     * @throws InvalidArgumentException If the key is not registered.
     * 
     * @return AbstractSlider
     *
     * @example
     *   $slider = SliderManager::make('carousel');
     */
    public static function make(string $key): AbstractSlider
    {
        if (!isset(self::$map[$key])) {
            // Exception message translated to Persian (fa-IR)
            throw new InvalidArgumentException("اسلایدر با کلید [{$key}] ثبت نشده است.");
        }
        return new self::$map[$key]();
    }
}
