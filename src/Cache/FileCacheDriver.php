<?php
declare(strict_types=1);

namespace jamal13647850\wphelpers\Cache;

/**
 * File Cache Driver (For demonstration; not recommended for production)
 */
class FileCacheDriver implements CacheInterface
{
    protected string $prefix;
    protected int $default_expiration;
    protected string $cache_dir;

    public function __construct(string $prefix = '', int $default_expiration = 3600, ?string $cache_dir = null)
    {
        $this->prefix = $prefix;
        $this->default_expiration = $default_expiration;
        $this->cache_dir = $cache_dir ?? (WP_CONTENT_DIR . '/cache/wphelpers/');
        if (!is_dir($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
        }
    }

    protected function getCacheFile(string $key): string
    {
        $safe = preg_replace('~[^a-zA-Z0-9_]~', '_', $this->prefix . $key);
        return $this->cache_dir . $safe . '.cache.php';
    }

    public function set(string $key, $value, ?int $expiration = null): bool
    {
        $expiration = $expiration ?? $this->default_expiration;
        $data = [
            'expire' => time() + $expiration,
            'value'  => serialize($value)
        ];
        return (bool) file_put_contents($this->getCacheFile($key), "<?php return " . var_export($data, true) . ";");
    }

    public function get(string $key, $default = null)
    {
        $file = $this->getCacheFile($key);
        if (!file_exists($file)) return $default;
        $data = include $file;
        if (!is_array($data) || ($data['expire'] < time())) {
            $this->delete($key);
            return $default;
        }
        return unserialize($data['value']);
    }

    public function delete(string $key): bool
    {
        $file = $this->getCacheFile($key);
        return (!file_exists($file) || unlink($file));
    }

    public function exists(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function flush(): bool
    {
        $files = glob($this->cache_dir . '*.cache.php');
        if (!$files) return true;
        foreach ($files as $file) unlink($file);
        return true;
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