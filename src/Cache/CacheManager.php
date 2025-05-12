<?php
declare(strict_types=1);

namespace jamal13647850\wphelpers\Cache;

defined('ABSPATH') || exit();

use jamal13647850\wphelpers\Config;

/**
 * CacheManager
 * 
 * Facade for switching cache drivers.
 */
class CacheManager
{
    protected CacheInterface $driver;

    /**
     * CacheManager constructor.
     * 
     * @param string $driver_type 'transient' | 'object' | 'file'
     * @param string|null $prefix
     * @param int|null $default_expiration
     */
    public function __construct(
        string $driver_type = 'transient',
        ?string $prefix = null,
        ?int $default_expiration = null
    ) {
        $prefix = $prefix ?? Config::get('cache.prefix', 'wphelpers_');
        $default_expiration = $default_expiration ?? Config::get('cache.expiration', 3600);

        switch ($driver_type) {
            case 'object':
                $this->driver = new ObjectCacheDriver($prefix, $default_expiration);
                break;
            case 'file':
                $this->driver = new FileCacheDriver($prefix, $default_expiration);
                break;
            case 'transient':
            default:
                $this->driver = new TransientCacheDriver($prefix, $default_expiration);
        }
    }
    
    // Delegate all proxy methods to the driver

    public function set(string $key, $value, ?int $expiration = null): bool
    {
        return $this->driver->set($key, $value, $expiration);
    }

    public function get(string $key, $default = null)
    {
        return $this->driver->get($key, $default);
    }

    public function delete(string $key): bool
    {
        return $this->driver->delete($key);
    }

    public function exists(string $key): bool
    {
        return $this->driver->exists($key);
    }

    public function flush(): bool
    {
        return $this->driver->flush();
    }

    public function remember(string $key, callable $callback, ?int $expiration = null)
    {
        $value = $this->driver->get($key);
        if ($value !== null) return $value;
        $value = $callback();
        $this->driver->set($key, $value, $expiration);
        return $value;
    }

    public function increment(string $key, int $offset = 1, ?int $expiration = null)
    {
        return $this->driver->increment($key, $offset, $expiration);
    }

    public function decrement(string $key, int $offset = 1, ?int $expiration = null)
    {
        return $this->driver->decrement($key, $offset, $expiration);
    }

    public function setMultiple(array $values, ?int $expiration = null): bool
    {
        return $this->driver->setMultiple($values, $expiration);
    }

    public function getMultiple(array $keys, $default = null): array
    {
        return $this->driver->getMultiple($keys, $default);
    }

    public function deleteMultiple(array $keys): bool
    {
        return $this->driver->deleteMultiple($keys);
    }

    public function setDriver(CacheInterface $driver): void
    {
        $this->driver = $driver;
    }

    public function getDriver(): CacheInterface
    {
        return $this->driver;
    }
}