<?php
namespace jamal13647850\wphelpers\Utilities;

use jamal13647850\wphelpers\Cache\CacheManager;

defined('ABSPATH') || exit();

class Theme_Settings_Cache
{
    protected $driver; // Cache driver
    protected $group_keys = [];
    protected $prefix;
    protected $bypass = false;

    /**
     * Constructor
     * @param string $prefix یک پیشوند یکتا برای کلید‌های کش
     */
    public function __construct($prefix = 'theme_settings_')
    {
        $this->prefix = $prefix;
        $this->bypass = $this->isDebug();

        // Try Redis object cache first, fallback to transient 
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
     * صرفاً چک می‌کند Redis Object Cache نصب و فعال باشد (با wp_cache api کار می‌کند)
     */
    protected function isRedisAvailable()
    {
        global $wp_object_cache;
        // روش تست کلاس کلاسیک پلاگین redis-object-cache
        return is_object($wp_object_cache) && method_exists($wp_object_cache, 'redis');
    }

    /**
     * گروهی مقدار کش را بگیرد (گروه مثل general, header,...)
     */
    public function get($group)
    {
        if ($this->bypass) return false;
        return $this->driver->get('group_' . $group, false);
    }

    /**
     * کش‌گذاری گروهی
     */
    public function set($group, $data, $expiration = 604800)
    {
        if ($this->bypass) return false;
        return $this->driver->set('group_' . $group, $data, $expiration);
    }

    /**
     * حذف کش یک گروه خاص
     */
    public function flush_group($group)
    {
        if ($this->bypass) return true;
        return $this->driver->delete('group_' . $group);
    }

    /**
     * حذف کل کش اگر لازم شد
     */
    public function flush_all()
    {
        if ($this->bypass) return true;
        return $this->driver->flush();
    }
}
