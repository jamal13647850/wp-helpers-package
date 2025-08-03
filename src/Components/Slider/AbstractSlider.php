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

use jamal13647850\wphelpers\Views\View;
use jamal13647850\wphelpers\Components\Slider\Options\SliderOptions;

/**
 * AbstractSlider
 *
 * Abstract base class for creating slider components in WordPress helper package.
 * 
 * Responsibilities:
 *   - Defines the basic structure for any slider component.
 *   - Manages view namespace registration for slider variants.
 *   - Provides an option factory for consistent SliderOptions instantiation.
 *
 * Usage:
 *   Extend this class to implement a custom slider variant.
 */
abstract class AbstractSlider
{
    /**
     * The Twig template namespace that must be defined by concrete child classes.
     * Each slider variant must provide its own namespace.
     *
     * @var string
     */
    protected const TEMPLATE_NAMESPACE = '';

    /**
     * View object used for rendering templates.
     *
     * @var View
     */
    protected View $view;

    /**
     * AbstractSlider constructor.
     * Initializes the view and registers the variant-specific namespace and template path.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->view = new View();
        // Each variant registers its own Twig template namespace and path.
        $this->view->addPath(
            static::TEMPLATE_NAMESPACE,
            static::getViewsPath()
        );
    }

    /**
     * Get the path to the variant's views folder.
     * 
     * Child classes must implement this to specify where their templates reside.
     *
     * @return string Absolute path to the views directory.
     */
    abstract protected static function getViewsPath(): string;

    /**
     * Render the final HTML for the slider.
     *
     * Child classes must implement this method to generate the HTML output.
     *
     * @param array $slides  The slide data to display in the slider.
     * @param array $options Additional options for the slider (optional).
     * @return string The rendered HTML markup.
     *
     * @example
     *   $slider = new MySliderVariant();
     *   echo $slider->render($slidesArray, ['autoplay' => true]);
     */
    abstract public function render(array $slides, array $options = []): string;

    /**
     * Provide the default options for this slider variant.
     *
     * Child classes must implement this to specify their default configuration.
     *
     * @return array The default options for the slider.
     */
    abstract protected static function defaultOptions(): array;

    /**
     * Create and return a SliderOptions value object, merging incoming options
     * with the variant's default options.
     * 
     * This enforces DRY principle for option handling.
     *
     * @param array $incoming User-supplied or custom options.
     * @return SliderOptions The value object representing merged slider options.
     */
    protected function makeOptions(array $incoming): SliderOptions
    {
        return new SliderOptions($incoming, static::defaultOptions());
    }
}

