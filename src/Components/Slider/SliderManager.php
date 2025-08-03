<?php

/*
Sayyed Jamal Ghasemi — Full-Stack Developer  
📧 info@jamalghasemi.com  
🔗 LinkedIn: https://www.linkedin.com/in/jamal1364/  
📸 Instagram: https://www.instagram.com/jamal13647850  
💬 Telegram: https://t.me/jamal13647850  
🌐 https://jamalghasemi.com  
*/

/**
 * SliderManager
 *
 * Central registry and factory for all slider variants.
 * Provides registration, instantiation, and a rendering facade
 * with extensibility hook points for other plugins/themes.
 *
 * Hooks fired (reference):
 *   - slider/before_render              (&$key, &$slides, &$options)
 *   - slider/before_render_{key}        (&$slides, &$options)
 *   - slider/after_render               ($key, $html, $slides, $options)
 *   - slider/after_render_{key}         ($html, $slides, $options)
 *
 * Usage:
 *   SliderManager::register('wide-auto', WideAuto::class);
 *   $slider = SliderManager::make('wide-auto');
 *   $html = SliderManager::render('wide-auto', $slides, $options);
 */

declare(strict_types=1);

namespace jamal13647850\wphelpers\Components\Slider;

use InvalidArgumentException;
use jamal13647850\wphelpers\Components\Slider\AbstractSlider;

/**
 * Class SliderManager
 *
 * Handles variant registration, creation, and rendering (with hooks).
 */
final class SliderManager
{
    /**
     * Map of slider keys to fully qualified class names.
     *
     * @var array<string, class-string<AbstractSlider>>
     */
    private static array $map = [];

    /**
     * Register a slider variant (e.g., 'wide-auto').
     *
     * @param string $key   Unique identifier for the slider variant.
     * @param string $class Fully qualified class name, must extend AbstractSlider.
     *
     * @throws InvalidArgumentException If $class is not a subclass of AbstractSlider.
     *
     * @return void
     *
     * @example
     *   SliderManager::register('wide-auto', WideAuto::class);
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
     * Instantiate a registered slider variant by key.
     *
     * @param string $key Slider variant identifier (must be registered).
     *
     * @throws InvalidArgumentException If $key is not registered.
     *
     * @return AbstractSlider
     *
     * @example
     *   $slider = SliderManager::make('wide-auto');
     */
    public static function make(string $key): AbstractSlider
    {
        if (!isset(self::$map[$key])) {
            // Exception message translated to Persian (fa-IR)
            throw new InvalidArgumentException("اسلایدر با کلید [{$key}] ثبت نشده است.");
        }
        /** @var class-string<AbstractSlider> $class */
        $class = self::$map[$key];
        return new $class();
    }

    /**
     * Render slider HTML with pre/post action hooks for extensibility.
     *
     * Hooks fired (all documented in class PHPDoc):
     *   - 'slider/before_render'              (&$key, &$slides, &$options)
     *   - "slider/before_render_{$key}"       (&$slides, &$options)
     *   - 'slider/after_render'               ($key, $html, $slides, $options)
     *   - "slider/after_render_{$key}"        ($html, $slides, $options)
     *
     * @param string $key     Variant identifier.
     * @param array  $slides  Slide data to be rendered.
     * @param array  $options Variant options (merged/validated downstream).
     *
     * @return string         The final rendered HTML output.
     *
     * @example
     *   echo SliderManager::render('wide-auto', $slides, ['interval' => 3000]);
     *
     * @side-effects
     *   Fires actions before and after rendering; enables third-party plugins/themes
     *   to modify input (by reference) or output (read-only).
     */
    public static function render(string $key, array $slides = [], array $options = []): string
    {
        // ----- Pre-render hooks (parameters by reference) -----
        do_action_ref_array('slider/before_render', [&$key, &$slides, &$options]);
        do_action_ref_array("slider/before_render_{$key}", [&$slides, &$options]);

        // Render the slider HTML using the specified variant
        $html = self::make($key)->render($slides, $options);

        // ----- Post-render hooks (read-only) -----
        do_action('slider/after_render', $key, $html, $slides, $options);
        do_action("slider/after_render_{$key}", $html, $slides, $options);

        return $html;
    }
}
