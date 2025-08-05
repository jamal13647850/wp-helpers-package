<?php

/**
 * Menu Cache Manager
 * Caching layer for menu rendering with dynamic cache backend selection.
 *
 * @package   jamal13647850/wphelpers
 * @author    Sayyed Jamal Ghasemi <info@jamalghasemi.com>
 * @version   1.0.0
 */

namespace jamal13647850\wphelpers\Components\Menu;

if (!defined('ABSPATH')) {
    exit;
}



use jamal13647850\wphelpers\Components\Menu\MenuManager;
use jamal13647850\wphelpers\Cache\CacheManager;

class MenuCacheManager
{

    /**
     * CacheManager instance (object or transient)
     * @var CacheManager
     */
    protected $driver;

    /**
     * Cache prefix
     * @var string
     */
    protected $prefix = 'menu_cache_';

    /**
     * Bypass flag
     * @var bool
     */
    protected $bypass = false;

    /**
     * TTL for cache (seconds)
     * @var int
     */
    protected $ttl = 604800;

    /**
     * Singleton instance
     * @var self
     */
    protected static $instance = null;

    /**
     * Constructor (private)
     */
    protected function __construct()
    {
        // Allow bypass via filter for debugging
        $this->bypass = (bool) apply_filters('menu_cache/bypass', ![$this, 'isDebug']);

        // Unique prefix for cache keys
        $this->prefix = apply_filters('menu_cache/prefix', $this->prefix);

        // TTL: allow override
        $this->ttl = (int) apply_filters('menu_cache/ttl', $this->ttl);

        // Dynamic driver selection (object cache/redis, fallback to transient)
        if (!$this->bypass && function_exists('wp_cache_set') && $this->isRedisAvailable()) {
            $this->driver = new CacheManager('object', $this->prefix);
        } else {
            $this->driver = new CacheManager('transient', $this->prefix);
        }
    }

    /**
     * Check if should bypass for development
     */
    protected function isDebug()
    {
        if (defined('CACHE_THEME') && CACHE_THEME)      return false;
        if (defined('WP_DEBUG') && WP_DEBUG)      return true;
        if (defined('WP_ENV') && WP_ENV === 'development') return true;
        return false;
    }

    /**
     * Get singleton instance.
     * @return self
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get cached menu HTML or regenerate and set.
     *
     * @param string $key          Menu type (desktop/mobile/...)
     * @param string $themeLocation
     * @param array  $options
     * @param array  $walkerOptions
     * @return string
     */
    public function getMenu($key, $themeLocation, $options = [], $walkerOptions = [])
    {
        $cacheKey = $this->getCacheKey($key, $themeLocation);

        
        if (!$this->bypass) {
           
            // Try to get cached HTML
            $cached = $this->driver->get($cacheKey);
            if ($cached !== false && is_string($cached)) {
                return $cached;
            }
        }

        // Render menu (call MenuManager directly)
        $html = MenuManager::render($key, $themeLocation, $options, $walkerOptions);

        if (!$this->bypass) {
            $this->driver->set($cacheKey, $html, $this->ttl);
        }

        return $html;
    }

    /**
     * Cache key generator.
     */
    public function getCacheKey($key, $themeLocation)
    {
        $parts = [
            $this->prefix,
            $key,
            $themeLocation
        ];
        return implode('_', $parts);
    }

    /**
     * Purge all menu caches.
     * Use this on menu or settings update.
     */
    public function purge($key, $themeLocation)
    {
        $this->driver->delete($this->getCacheKey($key, $themeLocation));
    }

    public function purgeAll()
    {
        $keys = ['desktop', 'mobile', 'dropdown', 'simple'];
        $locations = array_keys(get_registered_nav_menus());

        foreach ($keys as $key) {
            foreach ($locations as $location) {
                $this->purge($key, $location);
            }
        }
    }

    /**
     * Check if Redis object cache is available.
     * @return bool
     */
    protected function isRedisAvailable()
    {
        // Basic check: can be improved depending on stack
        global $wp_object_cache;
        return is_object($wp_object_cache) && method_exists($wp_object_cache, 'redis');
    }


}


/*
=========================
= Developer Signature =
=========================
File: Menu_Cache_Manager.php
By: Sayyed Jamal Ghasemi – jamal13647850/wphelpers
https://github.com/jamal13647850
*/
