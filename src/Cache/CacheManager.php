<?php
/*
Sayyed Jamal Ghasemi — Full-Stack Developer
📧 info@jamalghasemi.com
🔗 LinkedIn: https://www.linkedin.com/in/jamal1364/
📸 Instagram: https://www.instagram.com/jamal13647850
💬 Telegram: https://t.me/jamal13647850
🌐 https://jamalghasemi.com
*/

declare(strict_types=1);

namespace jamal13647850\wphelpers\Cache;

defined('ABSPATH') || exit();

use jamal13647850\wphelpers\Config;

/**
 * Class CacheManager
 *
 * Facade for selecting and proxying cache drivers with an automatic
 * fallback from "object" (Redis-backed external object cache) to "transient"
 * when Redis is not actually usable at runtime.
 *
 * Behavior:
 * - If `$driver_type === 'object'`, the constructor verifies effective Redis support:
 *   (1) external object cache enabled; (2) Redis not disabled; (3) a Redis client exists.
 *   If any check fails, the manager falls back to the transient driver, records the reason,
 *   emits a `do_action('wphelpers/cache/fallback', ...)`, and logs to error_log when WP_DEBUG is true.
 *
 * Usage example:
 * ```php
 * // Prefer object cache; transparently fall back to transients if Redis is unavailable.
 * $cache = new CacheManager('object', 'wphelpers_', 3600);
 * $value = $cache->remember('key', static fn () => expensiveComputation(), 600);
 * ```
 *
 * Preconditions:
 * - WordPress context is expected (uses `wp_using_ext_object_cache`, `do_action`).
 * - Driver classes implementing `CacheInterface` must be autoloadable.
 *
 * Side-effects:
 * - May trigger a WordPress action and write to error_log when falling back.
 *
 * @author
 */
class CacheManager
{
    /**
     * The concrete cache driver selected for this instance.
     *
     * @var CacheInterface
     */
    protected CacheInterface $driver;

    /**
     * The resolved driver type after construction.
     * One of: "object" | "transient" | "file".
     *
     * @var string
     */
    protected string $resolvedDriverType;

    /**
     * Indicates whether an auto-fallback from "object" to "transient" occurred.
     *
     * @var bool
     */
    protected bool $usingFallback = false;

    /**
     * Reason for fallback, or null if no fallback occurred.
     *
     * @var string|null
     */
    protected ?string $fallbackReason = null;

    /**
     * CacheManager constructor.
     *
     * Attempts to instantiate the requested driver. If "object" is requested,
     * Redis availability is validated and the manager falls back to "transient"
     * when requirements are not met.
     *
     * @param string      $driver_type         Requested driver: 'transient' | 'object' | 'file'.
     * @param string|null $prefix              Optional cache key prefix; defaults to config 'cache.prefix' or 'wphelpers_'.
     * @param int|null    $default_expiration  Default TTL (seconds); defaults to config 'cache.expiration' or 3600.
     *
     * @example
     *  $cache = new CacheManager('file', 'wphelpers_', 300);
     *  $cache->set('greeting', 'hello', 60);
     *  echo $cache->get('greeting'); // 'hello'
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
                [$available, $reason] = $this->checkRedisSupport();

                if ($available) {
                    $this->driver = new ObjectCacheDriver($prefix, $default_expiration);
                    $this->resolvedDriverType = 'object';
                } else {
                    $this->driver = new TransientCacheDriver($prefix, $default_expiration);
                    $this->resolvedDriverType = 'transient';
                    $this->usingFallback = true;
                    $this->fallbackReason = $reason;

                    /**
                     * Action: fires when CacheManager falls back from "object" to "transient".
                     *
                     * @param string $fromDriver Original driver name.
                     * @param string $toDriver   Fallback driver name.
                     * @param string $reason     Human-readable fallback reason.
                     */
                    if (function_exists('do_action')) {
                        do_action('wphelpers/cache/fallback', 'object', 'transient', (string) $reason);
                    }

                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log(sprintf(
                            '[wphelpers] Cache fallback: %s -> %s (%s)',
                            'object',
                            'transient',
                            (string) $reason
                        ));
                    }
                }
                break;

            case 'file':
                $this->driver = new FileCacheDriver($prefix, $default_expiration);
                $this->resolvedDriverType = 'file';
                break;

            case 'transient':
            default:
                $this->driver = new TransientCacheDriver($prefix, $default_expiration);
                $this->resolvedDriverType = 'transient';
        }
    }

    // ===== INTERNALS =====

    /**
     * Check if a Redis-backed external object cache is usable.
     *
     * Conditions for "available = true":
     *  1) External object cache is enabled: `wp_using_ext_object_cache() === true`.
     *  2) Redis is not explicitly disabled: `WP_REDIS_DISABLED !== true`.
     *  3) A Redis client is available: PhpRedis (`\Redis`) or Predis (`\Predis\Client`).
     *
     * Notes:
     * - This method does not probe `wp_cache_*` calls to distinguish providers like Memcached.
     *   It validates preconditions sufficient to consider Redis viable.
     *
     * @return array{0: bool, 1: ?string} A tuple: [available, reason_if_unavailable].
     */
    protected function checkRedisSupport(): array
    {
        // 1) External object cache must be enabled.
        if (!function_exists('wp_using_ext_object_cache') || !wp_using_ext_object_cache()) {
            return [false, 'External object cache is not enabled (object-cache.php drop-in is missing).'];
        }

        // 2) Respect common toggle used by Redis Object Cache plugin.
        if (defined('WP_REDIS_DISABLED') && WP_REDIS_DISABLED) {
            return [false, 'WP_REDIS_DISABLED is true.'];
        }

        // 3) A Redis client must be available: PhpRedis or Predis.
        $hasPhpRedis = class_exists('\\Redis');
        $hasPredis = class_exists('\\Predis\\Client');

        if (!$hasPhpRedis && !$hasPredis) {
            return [false, 'No Redis client found (ext-redis or predis).'];
        }

        // Preconditions satisfied.
        return [true, null];
    }

    // ===== PUBLIC API: proxy methods to the selected driver =====

    /**
     * Store a value under the given key.
     *
     * @param string   $key         Cache key (will be prefixed by the driver).
     * @param mixed    $value       Serializable value to store.
     * @param int|null $expiration  Optional TTL in seconds; uses driver default if null.
     *
     * @return bool True on success; false otherwise.
     *
     * @example
     *  $cache->set('count', 1, 120);
     */
    public function set(string $key, $value, ?int $expiration = null): bool
    {
        return $this->driver->set($key, $value, $expiration);
    }

    /**
     * Retrieve a value by key.
     *
     * @param string $key      Cache key.
     * @param mixed  $default  Value to return if the key does not exist.
     *
     * @return mixed The cached value or $default when missing.
     *
     * @example
     *  $value = $cache->get('count', 0); // 0 if not found
     */
    public function get(string $key, $default = null)
    {
        return $this->driver->get($key, $default);
    }

    /**
     * Delete a key from cache.
     *
     * @param string $key Cache key to delete.
     *
     * @return bool True if key was deleted; false otherwise.
     */
    public function delete(string $key): bool
    {
        return $this->driver->delete($key);
    }

    /**
     * Check if a key exists (and is not expired).
     *
     * @param string $key Cache key to check.
     *
     * @return bool True if the key exists; false if not.
     */
    public function exists(string $key): bool
    {
        return $this->driver->exists($key);
    }

    /**
     * Flush the entire cache for the current driver scope/prefix.
     *
     * @return bool True on success; false otherwise.
     *
     * @warning This may remove a large number of entries; use cautiously.
     */
    public function flush(): bool
    {
        return $this->driver->flush();
    }

    /**
     * Return cached value or compute and store it when missing.
     *
     * @param string   $key         Cache key.
     * @param callable $callback    Producer invoked when cache miss occurs.
     * @param int|null $expiration  Optional TTL (seconds) for the computed value.
     *
     * @return mixed The cached or newly computed value.
     *
     * @example
     *  $user = $cache->remember("user_{$id}", fn () => loadUser($id), 300);
     */
    public function remember(string $key, callable $callback, ?int $expiration = null)
    {
        $value = $this->driver->get($key);
        if ($value !== null) {
            return $value;
        }
        $value = $callback();
        $this->driver->set($key, $value, $expiration);
        return $value;
    }

    /**
     * Atomically increment a numeric value.
     *
     * @param string   $key         Cache key.
     * @param int      $offset      Increment step (default 1).
     * @param int|null $expiration  Optional TTL for newly created keys.
     *
     * @return mixed New value after increment, or driver-dependent result.
     *
     * @precondition Existing value should be numeric for predictable results.
     */
    public function increment(string $key, int $offset = 1, ?int $expiration = null)
    {
        return $this->driver->increment($key, $offset, $expiration);
    }

    /**
     * Atomically decrement a numeric value.
     *
     * @param string   $key         Cache key.
     * @param int      $offset      Decrement step (default 1).
     * @param int|null $expiration  Optional TTL for newly created keys.
     *
     * @return mixed New value after decrement, or driver-dependent result.
     *
     * @precondition Existing value should be numeric for predictable results.
     */
    public function decrement(string $key, int $offset = 1, ?int $expiration = null)
    {
        return $this->driver->decrement($key, $offset, $expiration);
    }

    /**
     * Set multiple key/value pairs at once.
     *
     * @param array<string, mixed> $values     Map of key => value.
     * @param int|null             $expiration Optional TTL in seconds.
     *
     * @return bool True on success; false otherwise.
     */
    public function setMultiple(array $values, ?int $expiration = null): bool
    {
        return $this->driver->setMultiple($values, $expiration);
    }

    /**
     * Retrieve multiple keys at once.
     *
     * @param array<int, string> $keys  List of keys to fetch.
     * @param mixed              $default Default value if a key is missing.
     *
     * @return array<string, mixed> Map of key => value (or default).
     */
    public function getMultiple(array $keys, $default = null): array
    {
        return $this->driver->getMultiple($keys, $default);
    }

    /**
     * Delete multiple keys at once.
     *
     * @param array<int, string> $keys Keys to delete.
     *
     * @return bool True on success; false otherwise.
     */
    public function deleteMultiple(array $keys): bool
    {
        return $this->driver->deleteMultiple($keys);
    }

    /**
     * Swap the underlying driver at runtime.
     *
     * Resets fallback state and infers the resolved driver type from the instance.
     *
     * @param CacheInterface $driver New driver instance to use.
     *
     * @return void
     */
    public function setDriver(CacheInterface $driver): void
    {
        $this->driver = $driver;
        $this->resolvedDriverType = $driver instanceof FileCacheDriver ? 'file'
            : ($driver instanceof ObjectCacheDriver ? 'object' : 'transient');
        $this->usingFallback = false;
        $this->fallbackReason = null;
    }

    /**
     * Get the current driver instance.
     *
     * @return CacheInterface The driver in use.
     */
    public function getDriver(): CacheInterface
    {
        return $this->driver;
    }

    /**
     * Get the resolved driver type that is actually in use.
     *
     * @return string One of: "object" | "transient" | "file".
     */
    public function getResolvedDriverType(): string
    {
        return $this->resolvedDriverType;
    }

    /**
     * Whether the manager fell back from 'object' to 'transient'.
     *
     * @return bool True if a fallback occurred; false otherwise.
     */
    public function isUsingFallback(): bool
    {
        return $this->usingFallback;
    }

    /**
     * If fallback happened, returns the reason; otherwise null.
     *
     * @return string|null Human-readable reason for fallback, or null.
     */
    public function getFallbackReason(): ?string
    {
        return $this->fallbackReason;
    }
}
