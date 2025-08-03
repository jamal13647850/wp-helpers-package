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
 * Immutable Value-Object for slider options.
 *
 * Provides safe, validated, and immutable storage of options for a slider variant.
 *
 * @author  Sayyed Jamal Ghasemi
 * @link    https://jamalghasemi.com
 */

declare(strict_types=1);

namespace jamal13647850\wphelpers\Components\Slider\Options;

use InvalidArgumentException;

/**
 * Class SliderOptions
 *
 * An immutable value object that stores, validates, and provides access
 * to slider options, merging user-provided values with variant-specific defaults.
 */
final class SliderOptions
{
    /**
     * Allowed option keys mapped to their default values.
     *
     * @var array
     */
    private array $defaults;

    /**
     * The final, resolved options array after merging incoming and defaults.
     *
     * @var array
     */
    private array $resolved;

    /**
     * SliderOptions constructor.
     *
     * - Validates all incoming keys against allowed defaults.
     * - Merges user options over default values.
     * - Throws if unknown option keys are found.
     *
     * @param array $incoming  Options passed by the caller.
     * @param array $defaults  Variant-specific allowed options and their defaults.
     *
     * @throws InvalidArgumentException If any unknown keys are supplied.
     */
    public function __construct(array $incoming, array $defaults)
    {
        $this->defaults = $defaults;

        // Validate: only allow keys defined in $defaults
        $unknown = array_diff(array_keys($incoming), array_keys($defaults));
        if ($unknown) {
            // Exception message translated to Persian (fa-IR)
            throw new InvalidArgumentException(
                'گزینه(های) ناشناخته برای اسلایدر: ' . implode(', ', $unknown)
            );
        }

        // Merge user options over defaults (user overrides default)
        $this->resolved = array_merge($defaults, $incoming);
    }

    /**
     * Get a single option value by key.
     *
     * @param string $key  Option key.
     * @return mixed|null  Option value or null if not set.
     */
    public function get(string $key)
    {
        return $this->resolved[$key] ?? null;
    }

    /**
     * Get all resolved options as an associative array.
     *
     * @return array The merged options array.
     */
    public function toArray(): array
    {
        return $this->resolved;
    }
}

