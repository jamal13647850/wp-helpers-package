<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

/**
 * Class TransientCache
 * 
 * Handles caching using WordPress transients.
 */
class TransientCache
{
    /**
     * @var string
     */
    private string $prefix;
    
    /**
     * @var int
     */
    private int $default_expiration;
    
    /**
     * @var bool
     */
    private bool $use_object_cache;
    
    /**
     * TransientCache constructor.
     *
     * @param string|null $prefix Cache prefix
     * @param int|null $default_expiration Default expiration time in seconds
     * @param bool|null $use_object_cache Whether to use object cache
     */
    public function __construct(?string $prefix = null, ?int $default_expiration = null, ?bool $use_object_cache = null)
    {
        $this->prefix = $prefix ?? Config::get('cache.prefix', 'wphelpers_');
        $this->default_expiration = $default_expiration ?? Config::get('cache.expiration', 3600);
        $this->use_object_cache = $use_object_cache ?? Config::get('cache.use_object_cache', wp_using_ext_object_cache());
    }
    
    /**
     * Set a cache value.
     *
     * @param string $key Cache key
     * @param mixed $value Cache value
     * @param int|null $expiration Expiration time in seconds
     * @return bool True on success, false on failure
     */
    public function set(string $key, $value, ?int $expiration = null): bool
    {
        $key = $this->prefix . $key;
        $expiration = $expiration ?? $this->default_expiration;
        
        if ($this->use_object_cache) {
            return wp_cache_set($key, $value, 'wphelpers', $expiration);
        }
        
        return set_transient($key, $value, $expiration);
    }
    
    /**
     * Get a cache value.
     *
     * @param string $key Cache key
     * @param mixed $default Default value if cache is not found
     * @return mixed Cache value or default value
     */
    public function get(string $key, $default = null)
    {
        $key = $this->prefix . $key;
        
        if ($this->use_object_cache) {
            $value = wp_cache_get($key, 'wphelpers');
        } else {
            $value = get_transient($key);
        }
        
        return $value !== false ? $value : $default;
    }
    
    /**
     * Delete a cache value.
     *
     * @param string $key Cache key
     * @return bool True on success, false on failure
     */
    public function delete(string $key): bool
    {
        $key = $this->prefix . $key;
        
        if ($this->use_object_cache) {
            return wp_cache_delete($key, 'wphelpers');
        }
        
        return delete_transient($key);
    }
    
    /**
     * Check if a cache value exists.
     *
     * @param string $key Cache key
     * @return bool True if cache exists, false otherwise
     */
    public function exists(string $key): bool
    {
        return $this->get($key) !== null;
    }
    
    /**
     * Flush all cache values with the current prefix.
     *
     * @return bool True on success, false on failure
     */
    public function flush(): bool
    {
        global $wpdb;
        
        if ($this->use_object_cache) {
            wp_cache_flush();
            return true;
        }
        
        $prefix = $wpdb->esc_like('_transient_' . $this->prefix);
        $sql = "DELETE FROM $wpdb->options WHERE option_name LIKE %s";
        $result = $wpdb->query($wpdb->prepare($sql, $prefix . '%'));
        
        $prefix = $wpdb->esc_like('_transient_timeout_' . $this->prefix);
        $sql = "DELETE FROM $wpdb->options WHERE option_name LIKE %s";
        $wpdb->query($wpdb->prepare($sql, $prefix . '%'));
        
        return $result !== false;
    }
    
    /**
     * Remember a value in cache.
     *
     * @param string $key Cache key
     * @param callable $callback Callback to generate value if not in cache
     * @param int|null $expiration Expiration time in seconds
     * @return mixed Cache value or callback result
     */
    public function remember(string $key, callable $callback, ?int $expiration = null)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $expiration);
        
        return $value;
    }
    
    /**
     * Increment a numeric cache value.
     *
     * @param string $key Cache key
     * @param int $offset Increment offset
     * @param int|null $expiration Expiration time in seconds
     * @return int|false New value or false on failure
     */
    public function increment(string $key, int $offset = 1, ?int $expiration = null)
    {
        $value = (int)$this->get($key, 0);
        $value += $offset;
        
        if ($this->set($key, $value, $expiration)) {
            return $value;
        }
        
        return false;
    }
    
    /**
     * Decrement a numeric cache value.
     *
     * @param string $key Cache key
     * @param int $offset Decrement offset
     * @param int|null $expiration Expiration time in seconds
     * @return int|false New value or false on failure
     */
    public function decrement(string $key, int $offset = 1, ?int $expiration = null)
    {
        return $this->increment($key, -$offset, $expiration);
    }
    
    /**
     * Set multiple cache values.
     *
     * @param array $values Key-value pairs
     * @param int|null $expiration Expiration time in seconds
     * @return bool True on success, false on failure
     */
    public function setMultiple(array $values, ?int $expiration = null): bool
    {
        $success = true;
        
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $expiration)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get multiple cache values.
     *
     * @param array $keys Cache keys
     * @param mixed $default Default value if cache is not found
     * @return array Key-value pairs
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        
        return $result;
    }
    
    /**
     * Delete multiple cache values.
     *
     * @param array $keys Cache keys
     * @return bool True on success, false on failure
     */
    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get the cache prefix.
     *
     * @return string Cache prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
    
    /**
     * Set the cache prefix.
     *
     * @param string $prefix Cache prefix
     * @return self
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }
    
    /**
     * Get the default expiration time.
     *
     * @return int Default expiration time in seconds
     */
    public function getDefaultExpiration(): int
    {
        return $this->default_expiration;
    }
    
    /**
     * Set the default expiration time.
     *
     * @param int $default_expiration Default expiration time in seconds
     * @return self
     */
    public function setDefaultExpiration(int $default_expiration): self
    {
        $this->default_expiration = $default_expiration;
        return $this;
    }
    
    /**
     * Check if object cache is used.
     *
     * @return bool True if object cache is used, false otherwise
     */
    public function isUsingObjectCache(): bool
    {
        return $this->use_object_cache;
    }
    
    /**
     * Set whether to use object cache.
     *
     * @param bool $use_object_cache Whether to use object cache
     * @return self
     */
    public function setUseObjectCache(bool $use_object_cache): self
    {
        $this->use_object_cache = $use_object_cache;
        return $this;
    }
    
    /**
     * Get cache statistics.
     *
     * @return array Cache statistics
     */
    public function getStats(): array
    {
        global $wpdb;
        
        $stats = [
            'count' => 0,
            'size' => 0,
            'expired' => 0,
        ];
        
        if ($this->use_object_cache) {
            // Object cache stats are not available
            return $stats;
        }
        
        $prefix = $wpdb->esc_like('_transient_' . $this->prefix);
        $sql = "SELECT option_name, option_value, LENGTH(option_value) as size FROM $wpdb->options WHERE option_name LIKE %s";
        $results = $wpdb->get_results($wpdb->prepare($sql, $prefix . '%'));
        
        if (!empty($results)) {
            $stats['count'] = count($results);
            
            foreach ($results as $result) {
                $stats['size'] += $result->size;
                
                $key = str_replace('_transient_', '', $result->option_name);
                $timeout_key = '_transient_timeout_' . str_replace('_transient_', '', $key);
                $timeout = get_option($timeout_key);
                
                if ($timeout && $timeout < time()) {
                    $stats['expired']++;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Clear expired cache values.
     *
     * @return int Number of cleared values
     */
    public function clearExpired(): int
    {
        global $wpdb;
        
        if ($this->use_object_cache) {
            // Object cache expiration is handled automatically
            return 0;
        }
        
        $time = time();
        $prefix = $wpdb->esc_like('_transient_timeout_' . $this->prefix);
        $sql = "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s AND option_value < %d";
        $expired = $wpdb->get_results($wpdb->prepare($sql, $prefix . '%', $time));
        
        $count = 0;
        
        if (!empty($expired)) {
            foreach ($expired as $option) {
                $key = str_replace('_transient_timeout_', '', $option->option_name);
                delete_transient($key);
                $count++;
            }
        }
        
        return $count;
    }
}
