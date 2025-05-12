<?php
declare(strict_types=1);

namespace jamal13647850\wphelpers\Cache;

/**
 * Interface CacheInterface
 * 
 * Common interface for all cache drivers.
 */
interface CacheInterface
{
    public function set(string $key, $value, ?int $expiration = null): bool;
    public function get(string $key, $default = null);
    public function delete(string $key): bool;
    public function exists(string $key): bool;
    public function flush(): bool;
    public function increment(string $key, int $offset = 1, ?int $expiration = null);
    public function decrement(string $key, int $offset = 1, ?int $expiration = null);
    public function setMultiple(array $values, ?int $expiration = null): bool;
    public function getMultiple(array $keys, $default = null): array;
    public function deleteMultiple(array $keys): bool;
}