<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

class Config
{
    /**
     * @var array<string, mixed>
     */
    private static array $config = [];
    
    /**
     * @var string|null
     */
    private static ?string $configPath = null;
    
    /**
     * Initialize configuration with default values and custom config file
     *
     * @param string|null $configPath Optional path to custom config file
     * @return void
     */
    public static function init(?string $configPath = null): void
    {
        // Set default configuration values
        self::$config = [
            'views' => [
                'path' => get_template_directory() . '/templates/',
                'directories' => ['views'],
                'cache' => get_template_directory() . '/templates/cache',
                'debug' => WP_DEBUG,
            ],
            'captcha' => [
                'enabled' => true,
                'difficulty' => 'medium',
                'session_key' => 'captcha_answer',
            ],
            'sms' => [
                'provider' => 'faraz',
                'username' => defined('FARAZSMS_USERNAME') ? FARAZSMS_USERNAME : '',
                'password' => defined('FARAZSMS_PASSWORD') ? FARAZSMS_PASSWORD : '',
                'from_number' => defined('FARAZSMS_FROM_NUMBER') ? FARAZSMS_FROM_NUMBER : '',
                'url' => defined('FARAZSMS_URL') ? FARAZSMS_URL : '',
                'pattern' => defined('FARAZSMS_PATTERN') ? FARAZSMS_PATTERN : '',
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
     * Get a configuration value
     *
     * @param string $key Dot notation key (e.g., 'views.path')
     * @param mixed $default Default value if key doesn't exist
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
     * Set a configuration value
     *
     * @param string $key Dot notation key
     * @param mixed $value Value to set
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
}
