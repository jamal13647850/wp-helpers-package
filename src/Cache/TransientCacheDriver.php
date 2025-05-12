<?php
declare(strict_types=1);

namespace jamal13647850\wphelpers\Cache;

/**
 * Transient Cache Driver (using WordPress transients API)
 */
class TransientCacheDriver implements CacheInterface
{
    protected string $prefix;
    protected int $default_expiration; // in seconds

    /**
     * Constructor.
     *
     * @param string $prefix
     * @param int $default_expiration
     */
    public function __construct(string $prefix = '', int $default_expiration = 3600)
    {
        $this->prefix = $prefix;
        $this->default_expiration = $default_expiration;
    }

    /**
     * Build the prefixed transient name, respecting length restriction.
     */
    protected function buildKey(string $key): string
    {
        // WP's max transient key length = 45 chars (option name size 64 minus prefix ...)
        $transient = $this->prefix . $key;
        if (strlen($transient) > 45) {
            // fallback: hash long keys
            $transient = $this->prefix . substr(md5($key), 0, 16);
        }
        return $transient;
    }

    public function set(string $key, $value, ?int $expiration = null): bool
    {
        $expiration = $expiration ?? $this->default_expiration;
        return set_transient($this->buildKey($key), $value, $expiration);
    }

    public function get(string $key, $default = null)
    {
        $value = get_transient($this->buildKey($key));
        return ($value !== false) ? $value : $default;
    }

    public function delete(string $key): bool
    {
        return delete_transient($this->buildKey($key));
    }

    public function exists(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function flush(): bool
    {
        // WARNING: Only clears all transients if called in WP-CLI.
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::runcommand('transient delete --all');
            return true;
        }
        // Otherwise, DELETE via direct DB query (dangerous!).
        global $wpdb;
        // Safeguarded: only removes _transient_{$this->prefix}*
        $like = $wpdb->esc_like('_transient_' . $this->prefix);
        $options = $wpdb->get_results(
            $wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like . '%')
        );
        foreach ($options as $row) {
            delete_option($row->option_name);
        }
        // Expiration entries
        $like = $wpdb->esc_like('_transient_timeout_' . $this->prefix);
        $timeouts = $wpdb->get_results(
            $wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like . '%')
        );
        foreach ($timeouts as $row) {
            delete_option($row->option_name);
        }
        return true;
    }

    public function increment(string $key, int $offset = 1, ?int $expiration = null)
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $offset;
        return ($this->set($key, $new, $expiration)) ? $new : false;
    }

    public function decrement(string $key, int $offset = 1, ?int $expiration = null)
    {
        return $this->increment($key, -$offset, $expiration);
    }

    public function setMultiple(array $values, ?int $expiration = null): bool
    {
        $ok = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $expiration)) {
                $ok = false;
            }
        }
        return $ok;
    }

    public function getMultiple(array $keys, $default = null): array
    {
        $res = [];
        foreach ($keys as $key) {
            $res[$key] = $this->get($key, $default);
        }
        return $res;
    }

    public function deleteMultiple(array $keys): bool
    {
        $ok = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $ok = false;
            }
        }
        return $ok;
    }
}