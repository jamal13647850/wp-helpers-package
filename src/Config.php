<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

/**
 * Static Configuration Handler Class
 *
 * Handles get/set/unset of dot-notation keys,
 * supports save/load to wp_options and fetches ACF options seamlessly.
 */
class Config
{
    /**
     * @var array<string, mixed> Stores all configuration data
     */
    private static array $config = [];

    /**
     * @var string|null Absolute path to custom config file loaded (if any)
     */
    private static ?string $configPath = null;

    /**
     * Initialize configuration with defaults & optionally a custom config file
     *
     * @param string|null $configPath Optional custom config path (absolute)
     * @return void
     */
    public static function init(?string $configPath = null): void
    {
        // Set default configuration values
        self::$config = [
            'twig' => [
                'paths' =>[
                    'views' => get_template_directory() . '/templates',
                ],
                'cache'=>[
                    'enabled'=> defined('WP_ENV') && (WP_ENV==="development") ? false:true,
                    'path'=> WP_CONTENT_DIR . '/cache/twig',
                ],
                'debug' => defined('WP_DEBUG') ? WP_DEBUG : false,
                'auto_reload' => defined('WP_DEBUG') && (WP_DEBUG) ? true:false,
                'strict_variables' => (defined('WP_ENV') && (WP_ENV==="development"))  ? true:false,
            ],
            'captcha' => [
                'enabled' => true,
                'difficulty' => 'medium',
                'session_key' => 'captcha_answer',
            ],
            'sms' => [
                'providers' => [
                    'faraz' => [
                        'username'    => defined('FARAZSMS_USERNAME') ? FARAZSMS_USERNAME : '',
                        'password'    => defined('FARAZSMS_PASSWORD') ? FARAZSMS_PASSWORD : '',
                        'from_number' => defined('FARAZSMS_FROM_NUMBER') ? FARAZSMS_FROM_NUMBER : '',
                        'url'         => defined('FARAZSMS_URL') ? FARAZSMS_URL : '',
                        'patterns'    => [
                            'login'   => defined('FARAZSMS_PATTERN') ? FARAZSMS_PATTERN : '',
                        ],
                    ],
                    // نمونه برای افزودن Provider دیگر:
                    // 'kavenegar' => [
                    //     'apikey'   => '',
                    //     'patterns' => [
                    //         'login'   => '',
                    //         'register'=> '',
                    //     ],
                    // ],
                ],
            ],
            'cache' => [
                'enabled' => true,
                'prefix' => 'wphelpers_',
                'version' => '1.0',
                'default_expiration' => 3600,
            ],
            'comments' => [
                'rate_limit' => 300, // 5 minutes
                'max_attempts' => 5,
            ],
            'htmx' => [
                'validation_response_type' => 'html',
            ],
        ];

        // Set config path
        self::$configPath = $configPath;

        // Load custom configuration if provided
        if ($configPath && file_exists($configPath)) {
            $customConfig = require $configPath;
            if (is_array($customConfig)) {
                self::$config = array_replace_recursive(self::$config, $customConfig);
            }
        }
    }

    /**
     * Retrieve a configuration value (supports dot notation for depth)
     *
     * @param string $key     Dot notation key (e.g., 'views.path')
     * @param mixed  $default Default value if key does not exist
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (empty(self::$config)) {
            self::init();
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set a configuration value (supports dot notation for depth)
     *
     * @param string $key   Dot notation key (e.g., 'sms.providers.faraz.username')
     * @param mixed  $value Value to set
     * @return void
     */
    public static function set(string $key, $value): void
    {
        if (empty(self::$config)) {
            self::init();
        }

        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &self::$config;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current[$lastKey] = $value;
    }

    /**
     * Unset/remove a configuration value by dot notation key
     *
     * @param string $key Dot notation key (e.g., 'views.path')
     * @return bool True if unset, false if key did not exist
     */
    public static function unset(string $key): bool
    {
        if (empty(self::$config)) {
            self::init();
        }

        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &self::$config;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                // Key path does not exist
                return false;
            }
            $current = &$current[$k];
        }

        if (isset($current[$lastKey])) {
            unset($current[$lastKey]);
            return true;
        }

        return false;
    }

    /**
     * Stores configuration (full or a certain key) into a wp_option (serialized)
     *
     * @param string      $optionName wp_option key
     * @param string|null $key        Optional dot notation subkey to store
     * @return bool True on success
     */
    public static function saveToOption(string $optionName, ?string $key = null): bool
    {
        if (empty(self::$config)) {
            self::init();
        }

        $value = self::$config;
        if ($key !== null) {
            // Only save specific config key/path
            $value = self::get($key);
        }

        return update_option($optionName, $value, false);
    }

    /**
     * Loads configuration from a wp_option (option should be an array)
     *
     * @param string      $optionName wp_option key
     * @param string|null $key        Optional dot notation subkey to load into that path
     * @return bool True on success, false if value is not array
     */
    public static function loadFromOption(string $optionName, ?string $key = null): bool
    {
        $value = get_option($optionName);

        if ($value === false) {
            return false;
        }
        if ($key !== null) {
            self::set($key, $value);
            return true;
        } elseif (is_array($value)) {
            self::$config = array_replace_recursive(self::$config, $value);
            return true;
        }

        return false;
    }

    /**
     * Get (ACF) option page value by key automatically using get_field()
     * Falls back to WP options if not present in ACF.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function getACFOption(string $key, $default = null)
    {
        // ACF option (for both regular ACF field or options page)
        if (function_exists('get_field')) {
            $result = get_field($key, 'option');
            if ($result !== null && $result !== false) {
                return $result;
            }
        }

        // fallback to standard WP options (flat, not dottable)
        $result = get_option($key, $default);
        return $result !== false ? $result : $default;
    }
}