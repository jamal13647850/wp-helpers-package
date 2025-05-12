<?php
declare(strict_types=1);

namespace jamal13647850\wphelpers\Cache;

/**
 * Object Cache Driver implementation (Relies on `wp_cache_*` functions)
 */
class ObjectCacheDriver implements CacheInterface
{
    protected string $prefix;
    protected int $default_expiration;
    protected string $group;

    public function __construct(string $prefix = '', int $default_expiration = 3600, string $group = 'wphelpers')
    {
        $this->prefix = $prefix;
        $this->default_expiration = $default_expiration;
        $this->group = $group;
    }

    public function set(string $key, $value, ?int $expiration = null): bool
    {
        $key = $this->prefix . $key;
        $expiration = $expiration ?? $this->default_expiration;
        return wp_cache_set($key, $value, $this->group, $expiration);
    }

    public function get(string $key, $default = null)
    {
        $key = $this->prefix . $key;
        $value = wp_cache_get($key, $this->group);
        return ($value !== false) ? $value : $default;
    }

    public function delete(string $key): bool
    {
        $key = $this->prefix . $key;
        return wp_cache_delete($key, $this->group);
    }

    public function exists(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function flush(): bool
    {
        // WARNING: Flushes all object cache, not only current prefix!
        return wp_cache_flush();
    }

    public function increment(string $key, int $offset = 1, ?int $expiration = null)
    {
        $value = (int)$this->get($key, 0) + $offset;
        return ($this->set($key, $value, $expiration)) ? $value : false;
    }

    public function decrement(string $key, int $offset = 1, ?int $expiration = null)
    {
        return $this->increment($key, -$offset, $expiration);
    }

    public function setMultiple(array $values, ?int $expiration = null): bool
    {
        $ok = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $expiration)) $ok = false;
        }
        return $ok;
    }

    public function getMultiple(array $keys, $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function deleteMultiple(array $keys): bool
    {
        $ok = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) $ok = false;
        }
        return $ok;
    }
}