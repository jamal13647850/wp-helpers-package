<?php

/*
Sayyed Jamal Ghasemi — Full-Stack Developer  
📧 info@jamalghasemi.com  
🔗 LinkedIn: https://www.linkedin.com/in/jamal1364/  
📸 Instagram: https://www.instagram.com/jamal13647850  
💬 Telegram: https://t.me/jamal13647850  
🌐 https://jamalghasemi.com  
*/

declare(strict_types=1);

namespace jamal13647850\wphelpers\Components\Menu\Options;

use InvalidArgumentException;

/**
 * MenuOptions
 *
 * Immutable value object for menu options.
 * Stores, validates, and provides access to merged options.
 * All options are validated against allowed keys provided in defaults.
 *
 * Usage:
 *   $options = new MenuOptions(['theme' => 'dark'], ['theme' => 'light', 'size' => 'medium']);
 *   $theme = $options->get('theme');
 */
final class MenuOptions
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
     * MenuOptions constructor.
     *
     * Validates incoming options, merges them over defaults,
     * and throws if any unknown option keys are supplied.
     *
     * @param array $incoming  Options provided by the caller.
     * @param array $defaults  Allowed options and their default values.
     *
     * @throws InvalidArgumentException If any unknown option keys are detected.
     */
    public function __construct(array $incoming, array $defaults)
    {
        $this->defaults = $defaults;
        $unknown = array_diff(array_keys($incoming), array_keys($defaults));

        if ($unknown) {
            // Exception message already in Persian (fa-IR)
            throw new InvalidArgumentException(
                'گزینه(های) ناشناخته برای منو: ' . implode(', ', $unknown)
            );
        }

        // Merge user options over defaults (user overrides default)
        $this->resolved = array_merge($defaults, $incoming);
    }

    /**
     * Get a single option value by key.
     *
     * @param string $key  The option key to retrieve.
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
