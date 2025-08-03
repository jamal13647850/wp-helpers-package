<?php

/*
Sayyed Jamal Ghasemi — Full-Stack Developer  
📧 info@jamalghasemi.com  
🔗 LinkedIn: https://www.linkedin.com/in/jamal1364/  
📸 Instagram: https://www.instagram.com/jamal13647850  
💬 Telegram: https://t.me/jamal13647850  
🌐 https://jamalghasemi.com  
*/

namespace jamal13647850\wphelpers\Components\Slider\Variants\WideAuto;

use jamal13647850\wphelpers\Components\Slider\AbstractSlider;
use jamal13647850\wphelpers\Components\Slider\Options\SliderOptions;

/**
 * WideAuto Slider Variant
 *
 * Implements a slider with a fixed wide layout and automatic transitions.
 *
 * Responsibilities:
 *   - Provides default options specific to the WideAuto variant.
 *   - Renders the slider using the appropriate Twig template and settings.
 *
 * Usage:
 *   $slider = new WideAuto();
 *   echo $slider->render($slidesArray, ['interval' => 3000, 'height' => 500]);
 */
final class WideAuto extends AbstractSlider
{
    /**
     * The Twig template namespace for this variant.
     *
     * @var string
     */
    protected const TEMPLATE_NAMESPACE = '@slider_wide_auto';

    /**
     * Get the absolute path to this variant's views directory.
     *
     * @return string
     */
    protected static function getViewsPath(): string
    {
        return __DIR__ . '/views';
    }

    /**
     * Return the default options for the WideAuto slider variant.
     *
     * @return array
     *
     *  - 'interval' (int): Milliseconds between slide transitions (default: 5000).
     *  - 'height'   (int): Slider max height in pixels for Tailwind CSS class (default: 530).
     */
    protected static function defaultOptions(): array
    {
        return [
            'interval' => 5000, // milliseconds
            'height'   => 530,  // pixels (used in Tailwind CSS class)
        ];
    }

    /**
     * Render the WideAuto slider as HTML.
     *
     * @param array $slides   Array of slides to be rendered.
     * @param array $options  Optional settings to override default options.
     *
     * @return string         The rendered HTML output.
     *
     * @example
     *   $slider = new WideAuto();
     *   echo $slider->render($slides, ['interval' => 4000]);
     */
    public function render(array $slides, array $options = []): string
    {
        // Create SliderOptions value-object for merged/validated options
        $optionsVO = $this->makeOptions($options);

        // Sanitize the slides array before rendering
        $slides = $this->sanitizeSlides($slides);

        // Compose Tailwind CSS class for max-height based on option value
        $heightClass = 'max-h-[' . (int) $optionsVO->get('height') . 'px]';

        // Render the template with context variables
        return $this->view->render(
            self::TEMPLATE_NAMESPACE . '/wide-auto.twig',
            [
                'slides'      => $slides,
                'interval'    => (int) $optionsVO->get('interval'),
                'heightClass' => $heightClass,
            ]
        );
    }
}
