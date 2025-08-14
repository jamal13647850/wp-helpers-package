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

namespace jamal13647850\wphelpers\Navigation\Traits;

use jamal13647850\wphelpers\Components\Menu\MenuCacheManager;

defined('ABSPATH') || exit();

/**
 * CacheableTrait - Advanced caching integration for navigation walkers
 *
 * Provides comprehensive caching capabilities including fragment caching,
 * smart invalidation, cache warming, and integration with MenuCacheManager.
 * Optimizes menu rendering performance through intelligent caching strategies.
 *
 * Features:
 * - Fragment caching for menu components
 * - Smart cache invalidation based on context
 * - Cache warming and preloading
 * - Multi-level cache hierarchy
 * - Cache statistics and monitoring
 * - Integration with WordPress object cache
 * - Memory-aware cache management
 *
 * @package jamal13647850\wphelpers\Navigation\Traits
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
trait CacheableTrait
{
    /**
     * Cache configuration settings
     * @var array<string, mixed>
     */
    private array $cacheConfig = [
        'enable_caching' => true,
        'cache_ttl' => 3600, // 1 hour
        'fragment_cache_ttl' => 1800, // 30 minutes
        'enable_fragment_cache' => true,
        'enable_user_cache' => false,
        'enable_device_cache' => true,
        'enable_language_cache' => true,
        'cache_warming' => false,
        'max_cache_size' => 100, // Maximum cached items
        'enable_debug' => false,
    ];

    /**
     * Local cache storage for current request
     * @var array<string, mixed>
     */
    private array $localCache = [];

    /**
     * Cache statistics for monitoring
     * @var array<string, int>
     */
    private array $cacheStats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'invalidations' => 0,
        'warmups' => 0,
    ];

    /**
     * Cache key prefixes for different cache types
     * @var array<string, string>
     */
    private array $cacheKeyPrefixes = [
        'menu' => 'wphelpers_menu_',
        'fragment' => 'wphelpers_fragment_',
        'item' => 'wphelpers_item_',
        'submenu' => 'wphelpers_submenu_',
        'state' => 'wphelpers_state_',
    ];

    /**
     * Get cached menu content or generate if not cached
     *
     * Primary caching method that checks for cached content and generates
     * new content if cache miss occurs. Integrates with MenuCacheManager.
     *
     * @param string $cacheKey Unique cache key
     * @param callable $generator Function to generate content if cache miss
     * @param array<string, mixed> $context Caching context
     * @return mixed Cached or generated content
     * @since 2.0.0
     */
    protected function getCachedContent(string $cacheKey, callable $generator, array $context = [])
    {
        if (!$this->isCachingEnabled()) {
            return $generator();
        }

        // Check local cache first (request-level caching)
        if (isset($this->localCache[$cacheKey])) {
            $this->cacheStats['hits']++;
            return $this->localCache[$cacheKey];
        }

        // Check persistent cache
        $cacheManager = MenuCacheManager::getInstance();
        $cached = $this->getPersistentCache($cacheKey);

        if ($cached !== false) {
            $this->localCache[$cacheKey] = $cached;
            $this->cacheStats['hits']++;
            return $cached;
        }

        // Cache miss - generate content
        $this->cacheStats['misses']++;
        $content = $generator();

        // Store in caches
        $this->setCachedContent($cacheKey, $content, $context);

        return $content;
    }

    /**
     * Set content in cache with appropriate TTL and context
     *
     * @param string $cacheKey Unique cache key
     * @param mixed $content Content to cache
     * @param array<string, mixed> $context Caching context
     * @return bool True if successfully cached
     * @since 2.0.0
     */
    protected function setCachedContent(string $cacheKey, $content, array $context = []): bool
    {
        if (!$this->isCachingEnabled()) {
            return false;
        }

        $ttl = $this->calculateTtl($context);
        
        // Store in local cache
        $this->localCache[$cacheKey] = $content;

        // Store in persistent cache
        $success = $this->setPersistentCache($cacheKey, $content, $ttl);

        if ($success) {
            $this->cacheStats['writes']++;
            
            // Manage cache size
            $this->manageCacheSize();
        }

        return $success;
    }

    /**
     * Generate cache key for menu component
     *
     * Creates a unique cache key based on menu context, user state,
     * device type, language, and other relevant factors.
     *
     * @param string $type Cache type (menu, fragment, item, etc.)
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $additionalData Additional data for key generation
     * @return string Generated cache key
     * @since 2.0.0
     */
    protected function generateCacheKey(string $type, $context, array $additionalData = []): string
    {
        $keyComponents = [
            $this->cacheKeyPrefixes[$type] ?? 'wphelpers_',
            $context->getWalkerType(),
        ];

        // Add theme location if available
        $themeLocation = $context->getCustomData('theme_location');
        if ($themeLocation) {
            $keyComponents[] = $themeLocation;
        }

        // Add user-specific key if user caching is enabled
        if ($this->cacheConfig['enable_user_cache']) {
            $keyComponents[] = $this->getUserCacheKey();
        }

        // Add device-specific key if device caching is enabled
        if ($this->cacheConfig['enable_device_cache']) {
            $keyComponents[] = $this->getDeviceCacheKey();
        }

        // Add language-specific key if language caching is enabled
        if ($this->cacheConfig['enable_language_cache']) {
            $keyComponents[] = $this->getLanguageCacheKey();
        }

        // Add menu options hash
        $optionsHash = md5(serialize($context->getOptions()->toArray()));
        $keyComponents[] = substr($optionsHash, 0, 8);

        // Add additional data
        if (!empty($additionalData)) {
            $keyComponents[] = md5(serialize($additionalData));
        }

        // Add version for cache busting
        $keyComponents[] = $this->getCacheVersion();

        return implode('_', $keyComponents);
    }

    /**
     * Cache menu fragment (individual components)
     *
     * Caches smaller components like individual menu items, submenus,
     * or specific sections for more granular cache management.
     *
     * @param string $fragmentId Fragment identifier
     * @param callable $generator Function to generate fragment content
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @return mixed Fragment content
     * @since 2.0.0
     */
    protected function getCachedFragment(string $fragmentId, callable $generator, $context)
    {
        if (!$this->cacheConfig['enable_fragment_cache']) {
            return $generator();
        }

        $cacheKey = $this->generateCacheKey('fragment', $context, ['fragment_id' => $fragmentId]);
        
        return $this->getCachedContent($cacheKey, $generator, [
            'ttl' => $this->cacheConfig['fragment_cache_ttl'],
            'type' => 'fragment',
        ]);
    }

    /**
     * Invalidate cache based on specific criteria
     *
     * Provides smart cache invalidation that can target specific cache
     * types, user groups, devices, or other contextual factors.
     *
     * @param array<string, mixed> $criteria Invalidation criteria
     * @return int Number of cache entries invalidated
     * @since 2.0.0
     */
    protected function invalidateCache(array $criteria = []): int
    {
        $invalidated = 0;

        // Clear local cache if no specific criteria
        if (empty($criteria)) {
            $this->localCache = [];
            $invalidated += count($this->localCache);
        }

        // Invalidate persistent cache
        $invalidated += $this->invalidatePersistentCache($criteria);

        $this->cacheStats['invalidations'] += $invalidated;

        return $invalidated;
    }

    /**
     * Warm cache by pre-generating content
     *
     * Proactively generates and caches content for common scenarios
     * to improve response times for subsequent requests.
     *
     * @param array<string, mixed> $scenarios Scenarios to warm cache for
     * @return int Number of cache entries warmed
     * @since 2.0.0
     */
    protected function warmCache(array $scenarios = []): int
    {
        if (!$this->cacheConfig['cache_warming']) {
            return 0;
        }

        $warmed = 0;

        foreach ($scenarios as $scenario) {
            if ($this->warmCacheScenario($scenario)) {
                $warmed++;
            }
        }

        $this->cacheStats['warmups'] += $warmed;

        return $warmed;
    }

    /**
     * Get cache statistics for monitoring and debugging
     *
     * @return array<string, mixed> Cache statistics and metrics
     * @since 2.0.0
     */
    protected function getCacheStatistics(): array
    {
        $totalRequests = $this->cacheStats['hits'] + $this->cacheStats['misses'];
        $hitRatio = $totalRequests > 0 ? $this->cacheStats['hits'] / $totalRequests : 0;

        return [
            'enabled' => $this->isCachingEnabled(),
            'stats' => $this->cacheStats,
            'hit_ratio' => round($hitRatio, 3),
            'local_cache_size' => count($this->localCache),
            'cache_keys' => array_keys($this->localCache),
            'memory_usage' => $this->estimateMemoryUsage(),
            'configuration' => $this->cacheConfig,
        ];
    }

    /**
     * Clear all cache data
     *
     * @param bool $includePersistent Whether to clear persistent cache
     * @return bool True if cache was cleared successfully
     * @since 2.0.0
     */
    protected function clearAllCache(bool $includePersistent = false): bool
    {
        // Clear local cache
        $this->localCache = [];

        // Reset statistics
        $this->cacheStats = array_fill_keys(array_keys($this->cacheStats), 0);

        // Clear persistent cache if requested
        if ($includePersistent) {
            return $this->clearPersistentCache();
        }

        return true;
    }

    /**
     * Check if caching is enabled and available
     *
     * @return bool True if caching is enabled
     * @since 2.0.0
     */
    private function isCachingEnabled(): bool
    {
        // Check configuration
        if (!$this->cacheConfig['enable_caching']) {
            return false;
        }

        // Check if we're in admin or customizer (usually no caching)
        if (is_admin() || is_customize_preview()) {
            return false;
        }

        // Check debug mode
        if (defined('WP_DEBUG') && WP_DEBUG && !$this->cacheConfig['enable_debug']) {
            return false;
        }

        return true;
    }

    /**
     * Get content from persistent cache
     *
     * @param string $cacheKey Cache key
     * @return mixed Cached content or false if not found
     * @since 2.0.0
     */
    private function getPersistentCache(string $cacheKey)
    {
        // Try object cache first (Redis, Memcached)
        if (function_exists('wp_cache_get')) {
            $cached = wp_cache_get($cacheKey, 'wphelpers_menu');
            if ($cached !== false) {
                return $cached;
            }
        }

        // Fallback to transients
        return get_transient($cacheKey);
    }

    /**
     * Set content in persistent cache
     *
     * @param string $cacheKey Cache key
     * @param mixed $content Content to cache
     * @param int $ttl Time to live in seconds
     * @return bool True if successfully cached
     * @since 2.0.0
     */
    private function setPersistentCache(string $cacheKey, $content, int $ttl): bool
    {
        // Try object cache first
        if (function_exists('wp_cache_set')) {
            $success = wp_cache_set($cacheKey, $content, 'wphelpers_menu', $ttl);
            if ($success) {
                return true;
            }
        }

        // Fallback to transients
        return set_transient($cacheKey, $content, $ttl);
    }

    /**
     * Calculate TTL based on context
     *
     * @param array<string, mixed> $context Caching context
     * @return int TTL in seconds
     * @since 2.0.0
     */
    private function calculateTtl(array $context): int
    {
        // Use custom TTL if provided
        if (isset($context['ttl'])) {
            return (int) $context['ttl'];
        }

        // Use fragment TTL for fragments
        if (($context['type'] ?? '') === 'fragment') {
            return $this->cacheConfig['fragment_cache_ttl'];
        }

        // Default TTL
        return $this->cacheConfig['cache_ttl'];
    }

    /**
     * Generate user-specific cache key component
     *
     * @return string User cache key component
     * @since 2.0.0
     */
    private function getUserCacheKey(): string
    {
        $userId = get_current_user_id();
        $userRoles = wp_get_current_user()->roles ?? ['guest'];
        
        return 'user_' . $userId . '_' . md5(implode(',', $userRoles));
    }

    /**
     * Generate device-specific cache key component
     *
     * @return string Device cache key component
     * @since 2.0.0
     */
    private function getDeviceCacheKey(): string
    {
        if (wp_is_mobile()) {
            return 'mobile';
        } elseif ($this->isTablet()) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    /**
     * Generate language-specific cache key component
     *
     * @return string Language cache key component
     * @since 2.0.0
     */
    private function getLanguageCacheKey(): string
    {
        $locale = get_locale();
        
        // Check for WPML
        if (function_exists('icl_get_current_language')) {
            $locale = icl_get_current_language();
        }
        
        // Check for Polylang
        if (function_exists('pll_current_language')) {
            $locale = pll_current_language();
        }

        return 'lang_' . $locale;
    }

    /**
     * Get cache version for cache busting
     *
     * @return string Cache version
     * @since 2.0.0
     */
    private function getCacheVersion(): string
    {
        // Use theme version and menu version for cache busting
        $themeVersion = wp_get_theme()->get('Version') ?: '1.0';
        $menuVersion = get_option('wphelpers_menu_version', '1.0');
        
        return 'v' . md5($themeVersion . $menuVersion);
    }

    /**
     * Check if device is tablet
     *
     * @return bool True if tablet
     * @since 2.0.0
     */
    private function isTablet(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $tabletKeywords = ['tablet', 'ipad', 'playbook', 'silk'];
        
        foreach ($tabletKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Warm cache for specific scenario
     *
     * @param array<string, mixed> $scenario Scenario configuration
     * @return bool True if warmed successfully
     * @since 2.0.0
     */
    private function warmCacheScenario(array $scenario): bool
    {
        // Implementation would depend on specific scenario requirements
        // This is a placeholder for cache warming logic
        return false;
    }

    /**
     * Invalidate persistent cache based on criteria
     *
     * @param array<string, mixed> $criteria Invalidation criteria
     * @return int Number of entries invalidated
     * @since 2.0.0
     */
    private function invalidatePersistentCache(array $criteria): int
    {
        // For simplicity, this implementation clears all related cache
        // A more sophisticated implementation would target specific keys
        
        $patterns = [];
        
        if (isset($criteria['type'])) {
            $prefix = $this->cacheKeyPrefixes[$criteria['type']] ?? 'wphelpers_';
            $patterns[] = $prefix . '*';
        } else {
            // Clear all wphelpers cache
            $patterns[] = 'wphelpers_*';
        }

        $invalidated = 0;
        
        foreach ($patterns as $pattern) {
            // Delete transients matching pattern
            // This is a simplified implementation
            global $wpdb;
            
            $result = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . str_replace('*', '%', $pattern)
            ));
            
            if ($result !== false) {
                $invalidated += $result;
            }
        }

        return $invalidated;
    }

    /**
     * Clear all persistent cache
     *
     * @return bool True if cleared successfully
     * @since 2.0.0
     */
    private function clearPersistentCache(): bool
    {
        // Use MenuCacheManager for comprehensive cache clearing
        $cacheManager = MenuCacheManager::getInstance();
        $cacheManager->purgeAll();

        return true;
    }

    /**
     * Manage cache size to prevent memory issues
     *
     * @return void
     * @since 2.0.0
     */
    private function manageCacheSize(): void
    {
        $maxSize = $this->cacheConfig['max_cache_size'];
        
        if (count($this->localCache) > $maxSize) {
            // Remove oldest entries (simple LRU-like behavior)
            $toRemove = count($this->localCache) - $maxSize;
            $this->localCache = array_slice($this->localCache, $toRemove, null, true);
        }
    }

    /**
     * Estimate memory usage of local cache
     *
     * @return int Estimated memory usage in bytes
     * @since 2.0.0
     */
    private function estimateMemoryUsage(): int
    {
        return strlen(serialize($this->localCache));
    }

    /**
     * Configure cache settings
     *
     * @param array<string, mixed> $config Cache configuration
     * @return void
     * @since 2.0.0
     */
    protected function configureCache(array $config): void
    {
        $this->cacheConfig = array_merge($this->cacheConfig, $config);
    }

    /**
     * Get cache configuration
     *
     * @return array<string, mixed> Current cache configuration
     * @since 2.0.0
     */
    protected function getCacheConfig(): array
    {
        return $this->cacheConfig;
    }
}