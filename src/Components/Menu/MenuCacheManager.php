<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Components\Menu;

use jamal13647850\wphelpers\Cache\CacheManager;

if (!defined('ABSPATH')) {
    exit;
}

final class MenuCacheManager
{
    /** @var CacheManager */
    protected $driver;

    protected string $prefix = 'menu_cache_';
    protected bool $bypass  = false;
    protected int $ttl      = 604800; // 7 days

    private static ?self $instance = null;

    private function __construct()
    {
        // ✅ فیکس: قبلاً ![$this,'isDebug'] بود که غلط است
        $this->bypass = (bool) apply_filters('menu_cache/bypass', !$this->isDebug());
        $this->prefix = (string) apply_filters('menu_cache/prefix', $this->prefix);
        $this->ttl    = (int) apply_filters('menu_cache/ttl', $this->ttl);

        // انتخاب درایور کش
        if (!$this->bypass && function_exists('wp_cache_set') && $this->isRedisAvailable()) {
            $this->driver = new CacheManager('object', $this->prefix);
        } else {
            $this->driver = new CacheManager('transient', $this->prefix);
        }
    }

    private function isDebug(): bool
    {
        if (defined('CACHE_THEME') && CACHE_THEME) { return false; }
        if (defined('WP_DEBUG') && WP_DEBUG) { return true; }
        if (defined('WP_ENV') && WP_ENV === 'development') { return true; }
        return false;
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getMenu(string $key, string $themeLocation, array $options = [], array $walkerOptions = []): string
    {
        $cacheKey = $this->getCacheKey($key, $themeLocation);

        if (!$this->bypass) {
            $cached = $this->driver->get($cacheKey);
            if ($cached !== false && is_string($cached)) {
                return $cached;
            }
        }

        $html = MenuManager::render($key, $themeLocation, $options, $walkerOptions);

        if (!$this->bypass) {
            $this->driver->set($cacheKey, $html, $this->ttl);
        }

        return $html;
    }

    public function getCacheKey(string $key, string $themeLocation): string
    {
        return implode('_', [
            $this->prefix,
            $key,
            $themeLocation,
        ]);
    }

    public function purge(string $key, string $themeLocation): void
    {
        $this->driver->delete($this->getCacheKey($key, $themeLocation));
    }

    public function purgeAll(): void
    {
        $keys = ['desktop', 'mobile', 'dropdown', 'simple','multi-column-desktop', 'overlay-mobile'];
        $locations = array_keys(get_registered_nav_menus());
        foreach ($keys as $key) {
            foreach ($locations as $location) {
                $this->purge($key, $location);
            }
        }
    }

    private function isRedisAvailable(): bool
    {
        global $wp_object_cache;
        return is_object($wp_object_cache) && method_exists($wp_object_cache, 'redis');
    }
}


