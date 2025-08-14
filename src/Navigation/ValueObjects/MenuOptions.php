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

namespace jamal13647850\wphelpers\Navigation\ValueObjects;

defined('ABSPATH') || exit();

/**
 * MenuOptions Value Object
 *
 * Immutable configuration container for menu walker options with validation,
 * type safety, and WordPress integration. Provides a standardized way to
 * handle menu configuration across different walker implementations.
 *
 * Features:
 * - Immutable design prevents configuration drift
 * - Type validation and sanitization
 * - Default value management
 * - WordPress filter integration
 * - Debugging and introspection helpers
 * - Performance optimizations (lazy loading, caching)
 *
 * @package jamal13647850\wphelpers\Navigation\ValueObjects
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
final class MenuOptions
{
    /**
     * Merged and validated options
     * @var array<string, mixed>
     */
    private array $options;

    /**
     * Default options used for fallback
     * @var array<string, mixed>
     */
    private array $defaults;

    /**
     * Cache for computed values to improve performance
     * @var array<string, mixed>
     */
    private array $computedCache = [];

    /**
     * MenuOptions constructor
     *
     * Creates a new MenuOptions instance by merging user-provided options
     * with defaults and applying validation and sanitization.
     *
     * @param array<string, mixed> $userOptions User-provided options
     * @param array<string, mixed> $defaults Default options
     * @since 2.0.0
     */
    public function __construct(array $userOptions = [], array $defaults = [])
    {
        $this->defaults = $defaults;
        
        // Apply WordPress filters to allow theme/plugin customization
        $userOptions = apply_filters('wphelpers/menu_options/user', $userOptions);
        $defaults = apply_filters('wphelpers/menu_options/defaults', $defaults);

        // Merge options with defaults taking precedence for structure
        $this->options = $this->mergeOptions($userOptions, $defaults);

        // Validate and sanitize all options
        $this->options = $this->validateOptions($this->options);

        // Apply final filter for complete customization
        $this->options = apply_filters('wphelpers/menu_options/final', $this->options);
    }

    /**
     * Merge user options with defaults intelligently
     *
     * Handles nested arrays properly and maintains type consistency.
     *
     * @param array<string, mixed> $userOptions User-provided options
     * @param array<string, mixed> $defaults Default options
     * @return array<string, mixed> Merged options
     * @since 2.0.0
     */
    private function mergeOptions(array $userOptions, array $defaults): array
    {
        $merged = $defaults;

        foreach ($userOptions as $key => $value) {
            if (isset($defaults[$key]) && is_array($defaults[$key]) && is_array($value)) {
                // Recursively merge nested arrays
                $merged[$key] = $this->mergeOptions($value, $defaults[$key]);
            } else {
                // Direct assignment for non-array values or new keys
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Validate and sanitize options
     *
     * Applies appropriate validation and sanitization based on option types
     * and known WordPress patterns.
     *
     * @param array<string, mixed> $options Options to validate
     * @return array<string, mixed> Validated options
     * @since 2.0.0
     */
    private function validateOptions(array $options): array
    {
        $validated = [];

        foreach ($options as $key => $value) {
            $validated[$key] = $this->validateSingleOption($key, $value);
        }

        return $validated;
    }

    /**
     * Validate a single option value
     *
     * Applies specific validation rules based on the option key and value type.
     *
     * @param string $key Option key
     * @param mixed $value Option value
     * @return mixed Validated and sanitized value
     * @since 2.0.0
     */
    private function validateSingleOption(string $key, $value)
    {
        // Boolean options
        if (in_array($key, [
            'enable_icons', 'caret_svg', 'enable_accordion', 'provide_state',
            'fallback_cb', 'echo', 'cache_enabled', 'debug_mode'
        ], true)) {
            return (bool) $value;
        }

        // Integer options
        if (in_array($key, [
            'max_depth', 'dropdown_columns', 'cache_ttl', 'item_limit'
        ], true)) {
            return (int) $value;
        }

        // String options that should be sanitized as CSS classes
        if (strpos($key, '_class') !== false || strpos($key, '_classes') !== false) {
            if (is_array($value)) {
                return array_map('sanitize_html_class', $value);
            }
            return sanitize_text_field((string) $value);
        }

        // URL options
        if (strpos($key, '_url') !== false || $key === 'base_url') {
            return esc_url_raw((string) $value);
        }

        // HTML content (with limited tags)
        if (in_array($key, ['items_wrap', 'before', 'after'], true)) {
            return wp_kses_post((string) $value);
        }

        // Accordion mode validation
        if ($key === 'accordion_mode') {
            $validModes = ['classic', 'independent', 'exclusive'];
            return in_array($value, $validModes, true) ? $value : 'classic';
        }

        // Menu type validation
        if ($key === 'menu_type') {
            $validTypes = ['desktop', 'mobile', 'dropdown', 'simple', 'multi-column', 'overlay'];
            return in_array($value, $validTypes, true) ? $value : 'desktop';
        }

        // Array options
        if (is_array($value)) {
            return array_map([$this, 'sanitizeArrayValue'], $value);
        }

        // Default: sanitize as text
        return sanitize_text_field((string) $value);
    }

    /**
     * Sanitize array values recursively
     *
     * @param mixed $value Array value to sanitize
     * @return mixed Sanitized value
     * @since 2.0.0
     */
    private function sanitizeArrayValue($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitizeArrayValue'], $value);
        }
        
        return sanitize_text_field((string) $value);
    }

    /**
     * Get option value with fallback
     *
     * @param string $key Option key
     * @param mixed $default Default value if option doesn't exist
     * @return mixed Option value or default
     * @since 2.0.0
     */
    public function get(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Check if option exists
     *
     * @param string $key Option key to check
     * @return bool True if option exists
     * @since 2.0.0
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }

    /**
     * Get boolean option with proper type casting
     *
     * @param string $key Option key
     * @param bool $default Default value
     * @return bool Boolean option value
     * @since 2.0.0
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);
        return (bool) $value;
    }

    /**
     * Get integer option with proper type casting
     *
     * @param string $key Option key
     * @param int $default Default value
     * @return int Integer option value
     * @since 2.0.0
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);
        return (int) $value;
    }

    /**
     * Get string option with proper type casting
     *
     * @param string $key Option key
     * @param string $default Default value
     * @return string String option value
     * @since 2.0.0
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);
        return (string) $value;
    }

    /**
     * Get array option with proper type casting
     *
     * @param string $key Option key
     * @param array<mixed> $default Default value
     * @return array<mixed> Array option value
     * @since 2.0.0
     */
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);
        return is_array($value) ? $value : $default;
    }

    /**
     * Get CSS class string from option
     *
     * Handles both string and array formats, returning a clean CSS class string.
     *
     * @param string $key Option key (should end with '_class' or '_classes')
     * @param string $default Default CSS classes
     * @return string Clean CSS class string
     * @since 2.0.0
     */
    public function getCssClass(string $key, string $default = ''): string
    {
        // Use cache for expensive operations
        $cacheKey = "css_class_{$key}";
        if (isset($this->computedCache[$cacheKey])) {
            return $this->computedCache[$cacheKey];
        }

        $value = $this->get($key, $default);
        
        if (is_array($value)) {
            $classes = array_filter(array_map('trim', $value));
            $result = implode(' ', $classes);
        } else {
            $result = trim((string) $value);
        }

        // Cache the result
        $this->computedCache[$cacheKey] = $result;
        
        return $result;
    }

    /**
     * Get computed option value
     *
     * Some options may need computation based on other options or WordPress state.
     * This method handles such dynamic calculations with caching.
     *
     * @param string $key Option key
     * @return mixed Computed option value
     * @since 2.0.0
     */
    public function getComputed(string $key)
    {
        if (isset($this->computedCache[$key])) {
            return $this->computedCache[$key];
        }

        $value = $this->computeOptionValue($key);
        $this->computedCache[$key] = $value;
        
        return $value;
    }

    /**
     * Compute dynamic option values
     *
     * @param string $key Option key to compute
     * @return mixed Computed value
     * @since 2.0.0
     */
    private function computeOptionValue(string $key)
    {
        switch ($key) {
            case 'is_mobile':
                return wp_is_mobile();
                
            case 'current_user_can_edit':
                return current_user_can('edit_theme_options');
                
            case 'is_admin':
                return is_admin();
                
            case 'theme_supports_menus':
                return current_theme_supports('menus');
                
            case 'alpine_enabled':
                // Check if Alpine.js is available
                global $wp_scripts;
                return isset($wp_scripts->registered['alpine']) || 
                       wp_script_is('alpine', 'enqueued');
                
            default:
                return $this->get($key);
        }
    }

    /**
     * Create a modified copy with new options
     *
     * Since MenuOptions is immutable, this method creates a new instance
     * with modified options while preserving the original.
     *
     * @param array<string, mixed> $newOptions Options to add or override
     * @return self New MenuOptions instance
     * @since 2.0.0
     */
    public function with(array $newOptions): self
    {
        $mergedOptions = array_merge($this->options, $newOptions);
        
        // Create new instance with merged options and same defaults
        $newInstance = new self([], $this->defaults);
        $newInstance->options = $this->validateOptions($mergedOptions);
        
        return $newInstance;
    }

    /**
     * Get all options as array
     *
     * @return array<string, mixed> All options
     * @since 2.0.0
     */
    public function toArray(): array
    {
        return $this->options;
    }

    /**
     * Get default options
     *
     * @return array<string, mixed> Default options
     * @since 2.0.0
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Get options that differ from defaults
     *
     * Useful for debugging and understanding what has been customized.
     *
     * @return array<string, mixed> Options that differ from defaults
     * @since 2.0.0
     */
    public function getCustomizations(): array
    {
        $customizations = [];
        
        foreach ($this->options as $key => $value) {
            $defaultValue = $this->defaults[$key] ?? null;
            
            if ($value !== $defaultValue) {
                $customizations[$key] = [
                    'current' => $value,
                    'default' => $defaultValue,
                ];
            }
        }
        
        return $customizations;
    }

    /**
     * Validate that required options are present
     *
     * @param array<string> $requiredKeys Required option keys
     * @return bool True if all required options are present
     * @since 2.0.0
     */
    public function hasRequired(array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get debug information for troubleshooting
     *
     * @return array<string, mixed> Debug information
     * @since 2.0.0
     */
    public function getDebugInfo(): array
    {
        return [
            'total_options' => count($this->options),
            'total_defaults' => count($this->defaults),
            'customizations' => count($this->getCustomizations()),
            'cache_entries' => count($this->computedCache),
            'memory_usage' => memory_get_usage(true),
            'options_hash' => md5(serialize($this->options)),
        ];
    }
}