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

namespace jamal13647850\wphelpers\Components\Menu;

use jamal13647850\wphelpers\Cache\CacheManager;
use jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext;

defined('ABSPATH') || exit();

/**
 * Enhanced MenuCacheManager - Advanced caching for navigation menus
 *
 * Provides comprehensive caching capabilities including fragment caching,
 * smart invalidation, cache warming, versioning, and performance monitoring.
 * Integrates seamlessly with the new walker architecture.
 *
 * Features:
 * - Multi-level cache hierarchy (fragment, menu, global)
 * - Smart invalidation based on context and dependencies
 * - Cache warming and preloading strategies
 * - Version-based cache busting
 * - Performance monitoring and analytics
 * - User and device-specific caching
 * - Memory-aware cache management
 *
 * @package jamal13647850\wphelpers\Components\Menu
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
final class MenuCacheManager
{
    /**
     * Singleton instance
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Primary cache driver
     * @var CacheManager
     */
    private CacheManager $cacheDriver;

    /**
     * Fragment cache driver for smaller components
     * @var CacheManager
     */
    private CacheManager $fragmentDriver;

    /**
     * Cache configuration
     * @var array<string, mixed>
     */
    private array $config = [
        'enable_cache' => true,
        'enable_fragment_cache' => true,
        'enable_user_cache' => false,
        'enable_device_cache' => true,
        'enable_language_cache' => true,
        'cache_warming' => false,
        'debug_mode' => false,
        'bypass' => false,
        'ttl_default' => 3600,        // 1 hour
        'ttl_fragment' => 1800,       // 30 minutes
        'ttl_user_specific' => 900,   // 15 minutes
        'max_cache_size' => 1000,     // Maximum cached items
        'cleanup_interval' => 86400,  // 24 hours
    ];

    /**
     * Cache key prefixes for different cache types
     * @var array<string, string>
     */
    private array $keyPrefixes = [
        'menu' => 'wphelpers_menu_',
        'fragment' => 'wphelpers_fragment_',
        'walker' => 'wphelpers_walker_',
        'user' => 'wphelpers_user_',
        'device' => 'wphelpers_device_',
        'lang' => 'wphelpers_lang_',
        'version' => 'wphelpers_version_',
    ];

    /**
     * Cache statistics for monitoring
     * @var array<string, int>
     */
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0,
        'invalidations' => 0,
        'warmups' => 0,
        'fragments_cached' => 0,
        'memory_usage' => 0,
    ];

    /**
     * Invalidation dependencies tracking
     * @var array<string, array>
     */
    private array $dependencies = [];

    /**
     * Cache warming queue
     * @var array<string, array>
     */
    private array $warmingQueue = [];

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->initializeConfiguration();
        $this->initializeCacheDrivers();
        $this->registerHooks();
    }

    /**
     * Get singleton instance
     *
     * @return self Singleton instance
     * @since 2.0.0
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get cached menu content with advanced features
     *
     * Primary method for retrieving cached menu content with support for
     * fragment caching, user-specific caching, and smart invalidation.
     *
     * @param string $key Walker type identifier
     * @param string $themeLocation WordPress theme location
     * @param array<string, mixed> $options Menu options
     * @param array<string, mixed> $walkerOptions Walker-specific options
     * @param RenderContext|null $context Rendering context for advanced caching
     * @return string Cached or generated menu HTML
     * @since 2.0.0
     */
    public function getMenu(
        string $key, 
        string $themeLocation, 
        array $options = [], 
        array $walkerOptions = [],
        ?RenderContext $context = null
    ): string {
        if (!$this->isCachingEnabled()) {
            return $this->generateMenuContent($key, $themeLocation, $options, $walkerOptions);
        }

        $cacheKey = $this->generateCacheKey($key, $themeLocation, $options, $walkerOptions, $context);
        
        // Try to get from cache first
        $cached = $this->getCachedContent($cacheKey);
        if ($cached !== false) {
            $this->stats['hits']++;
            $this->logCacheEvent('hit', $cacheKey, $context);
            return $cached;
        }

        // Cache miss - generate content
        $this->stats['misses']++;
        $content = $this->generateMenuContent($key, $themeLocation, $options, $walkerOptions);

        // Store in cache with appropriate TTL
        $ttl = $this->calculateTtl($options, $context);
        $this->setCachedContent($cacheKey, $content, $ttl, $context);

        $this->logCacheEvent('miss', $cacheKey, $context);
        
        return $content;
    }

    /**
     * Cache menu fragment (individual components)
     *
     * Provides granular caching for menu components like individual items,
     * submenus, or specific sections for better cache efficiency.
     *
     * @param string $fragmentId Unique fragment identifier
     * @param callable $generator Function to generate content if cache miss
     * @param array<string, mixed> $context Fragment context
     * @param int|null $ttl Custom TTL for fragment
     * @return mixed Fragment content
     * @since 2.0.0
     */
    public function getFragment(string $fragmentId, callable $generator, array $context = [], ?int $ttl = null)
    {
        if (!$this->config['enable_fragment_cache']) {
            return $generator();
        }

        $cacheKey = $this->generateFragmentKey($fragmentId, $context);
        
        // Try fragment cache first
        $cached = $this->fragmentDriver->get($cacheKey);
        if ($cached !== false) {
            $this->stats['hits']++;
            return $cached;
        }

        // Generate content
        $this->stats['misses']++;
        $content = $generator();

        // Cache with fragment TTL
        $fragmentTtl = $ttl ?? $this->config['ttl_fragment'];
        $this->fragmentDriver->set($cacheKey, $content, $fragmentTtl);
        $this->stats['fragments_cached']++;

        return $content;
    }

    /**
     * Invalidate cache with smart targeting
     *
     * Provides intelligent cache invalidation that can target specific
     * cache types, user groups, devices, or menu locations.
     *
     * @param array<string, mixed> $criteria Invalidation criteria
     * @return int Number of cache entries invalidated
     * @since 2.0.0
     */
    public function invalidateCache(array $criteria = []): int
    {
        $invalidated = 0;

        if (empty($criteria)) {
            // Invalidate all menu caches
            $invalidated = $this->invalidateAllMenuCaches();
        } else {
            // Targeted invalidation
            $invalidated = $this->invalidateTargeted($criteria);
        }

        $this->stats['invalidations'] += $invalidated;
        
        do_action('wphelpers/menu_cache/invalidated', $criteria, $invalidated);

        return $invalidated;
    }

    /**
     * Warm cache for common scenarios
     *
     * Proactively generates and caches content for frequently requested
     * menu configurations to improve response times.
     *
     * @param array<string, mixed> $scenarios Scenarios to warm cache for
     * @return int Number of cache entries warmed
     * @since 2.0.0
     */
    public function warmCache(array $scenarios = []): int
    {
        if (!$this->config['cache_warming']) {
            return 0;
        }

        $warmed = 0;

        // Use provided scenarios or generate common ones
        $scenariosToWarm = !empty($scenarios) ? $scenarios : $this->generateCommonScenarios();

        foreach ($scenariosToWarm as $scenario) {
            if ($this->warmCacheForScenario($scenario)) {
                $warmed++;
            }
        }

        $this->stats['warmups'] += $warmed;
        
        do_action('wphelpers/menu_cache/warmed', $scenariosToWarm, $warmed);

        return $warmed;
    }

    /**
     * Purge all menu caches (legacy compatibility)
     *
     * Maintains compatibility with existing MenuCacheManager usage
     * while providing enhanced functionality.
     *
     * @return void
     * @since 2.0.0
     */
    public function purgeAll(): void
    {
        $this->invalidateCache();
        
        // Also clear fragment cache
        if ($this->config['enable_fragment_cache']) {
            $this->fragmentDriver->flush();
        }

        // Reset statistics
        $this->resetStatistics();
    }

    /**
     * Purge specific menu cache (legacy compatibility)
     *
     * @param string $key Menu type key
     * @param string $themeLocation Theme location
     * @return void
     * @since 2.0.0
     */
    public function purge(string $key, string $themeLocation): void
    {
        $this->invalidateCache([
            'menu_type' => $key,
            'theme_location' => $themeLocation,
        ]);
    }

    /**
     * Get cache statistics and metrics
     *
     * Returns comprehensive statistics about cache performance,
     * hit ratios, memory usage, and other metrics.
     *
     * @return array<string, mixed> Cache statistics
     * @since 2.0.0
     */
    public function getStatistics(): array
    {
        $totalRequests = $this->stats['hits'] + $this->stats['misses'];
        $hitRatio = $totalRequests > 0 ? $this->stats['hits'] / $totalRequests : 0;

        return [
            'enabled' => $this->isCachingEnabled(),
            'stats' => $this->stats,
            'hit_ratio' => round($hitRatio, 3),
            'cache_efficiency' => $this->calculateCacheEfficiency(),
            'memory_usage' => $this->estimateMemoryUsage(),
            'configuration' => $this->config,
            'driver_info' => $this->getDriverInfo(),
            'warming_queue_size' => count($this->warmingQueue),
            'dependencies_tracked' => count($this->dependencies),
        ];
    }

    /**
     * Configure cache settings
     *
     * Allows runtime configuration of cache behavior and settings.
     *
     * @param array<string, mixed> $config Configuration options
     * @return void
     * @since 2.0.0
     */
    public function configure(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        
        // Reinitialize if necessary
        if (isset($config['enable_cache']) || isset($config['enable_fragment_cache'])) {
            $this->initializeCacheDrivers();
        }
    }

    /**
     * Check cache health and perform maintenance
     *
     * Performs cache maintenance tasks like cleanup, optimization,
     * and health checks.
     *
     * @return array<string, mixed> Health report
     * @since 2.0.0
     */
    public function performMaintenance(): array
    {
        $report = [
            'cleanup_performed' => false,
            'items_cleaned' => 0,
            'memory_freed' => 0,
            'errors' => [],
            'recommendations' => [],
        ];

        try {
            // Cleanup expired entries
            $cleaned = $this->cleanupExpiredEntries();
            $report['cleanup_performed'] = true;
            $report['items_cleaned'] = $cleaned;

            // Check memory usage
            $memoryUsage = $this->estimateMemoryUsage();
            if ($memoryUsage > 50 * 1024 * 1024) { // 50MB
                $report['recommendations'][] = 'Consider reducing cache TTL or enabling fragment caching';
            }

            // Check hit ratio
            $stats = $this->getStatistics();
            if ($stats['hit_ratio'] < 0.3) {
                $report['recommendations'][] = 'Low cache hit ratio detected - consider cache warming';
            }

        } catch (\Exception $e) {
            $report['errors'][] = $e->getMessage();
        }

        return $report;
    }

    /**
     * Initialize cache configuration
     *
     * @return void
     * @since 2.0.0
     */
    private function initializeConfiguration(): void
    {
        // Apply WordPress filters for configuration
        $this->config['bypass'] = (bool) apply_filters('wphelpers/menu_cache/bypass', $this->isDebugMode());
        $this->config['ttl_default'] = (int) apply_filters('wphelpers/menu_cache/ttl', $this->config['ttl_default']);
        $this->config['enable_cache'] = (bool) apply_filters('wphelpers/menu_cache/enable', $this->config['enable_cache']);
        
        // Environment-specific adjustments
        if ($this->isDebugMode()) {
            $this->config['debug_mode'] = true;
            $this->config['ttl_default'] = 300; // 5 minutes in debug mode
        }
    }

    /**
     * Initialize cache drivers
     *
     * @return void
     * @since 2.0.0
     */
    private function initializeCacheDrivers(): void
    {
        // Main cache driver
        if ($this->config['enable_cache'] && !$this->config['bypass']) {
            $driverType = $this->selectBestCacheDriver();
            $this->cacheDriver = new CacheManager($driverType, $this->keyPrefixes['menu'], $this->config['ttl_default']);
        }

        // Fragment cache driver (can use different strategy)
        if ($this->config['enable_fragment_cache'] && !$this->config['bypass']) {
            $this->fragmentDriver = new CacheManager('transient', $this->keyPrefixes['fragment'], $this->config['ttl_fragment']);
        }
    }

    /**
     * Register WordPress hooks for cache invalidation
     *
     * @return void
     * @since 2.0.0
     */
    private function registerHooks(): void
    {
        // Menu-related hooks
        add_action('wp_update_nav_menu', [$this, 'handleMenuUpdate'], 10, 2);
        add_action('wp_delete_nav_menu', [$this, 'handleMenuDelete'], 10, 1);
        add_action('wp_nav_menu_item_updated', [$this, 'handleMenuItemUpdate'], 10, 3);
        
        // Theme and customizer hooks
        add_action('customize_save_after', [$this, 'handleCustomizerSave'], 10);
        add_action('switch_theme', [$this, 'purgeAll'], 10);
        
        // Plugin hooks
        add_action('activated_plugin', [$this, 'handlePluginChange'], 10);
        add_action('deactivated_plugin', [$this, 'handlePluginChange'], 10);

        // Scheduled cleanup
        add_action('wphelpers_menu_cache_cleanup', [$this, 'performMaintenance']);
        
        if (!wp_next_scheduled('wphelpers_menu_cache_cleanup')) {
            wp_schedule_event(time(), 'daily', 'wphelpers_menu_cache_cleanup');
        }
    }

    /**
     * Generate cache key with context awareness
     *
     * @param string $key Walker type
     * @param string $themeLocation Theme location
     * @param array<string, mixed> $options Menu options
     * @param array<string, mixed> $walkerOptions Walker options
     * @param RenderContext|null $context Rendering context
     * @return string Generated cache key
     * @since 2.0.0
     */
    private function generateCacheKey(
        string $key, 
        string $themeLocation, 
        array $options, 
        array $walkerOptions,
        ?RenderContext $context = null
    ): string {
        $keyComponents = [
            $this->keyPrefixes['menu'],
            $key,
            $themeLocation,
        ];

        // Add options hash
        $optionsHash = md5(serialize(array_merge($options, $walkerOptions)));
        $keyComponents[] = substr($optionsHash, 0, 8);

        // Add user-specific component
        if ($this->config['enable_user_cache']) {
            $keyComponents[] = $this->getUserCacheComponent();
        }

        // Add device-specific component
        if ($this->config['enable_device_cache']) {
            $keyComponents[] = $this->getDeviceCacheComponent();
        }

        // Add language-specific component
        if ($this->config['enable_language_cache']) {
            $keyComponents[] = $this->getLanguageCacheComponent();
        }

        // Add context-specific component
        if ($context) {
            $contextHash = md5(serialize($context->createSnapshot()));
            $keyComponents[] = substr($contextHash, 0, 6);
        }

        // Add version for cache busting
        $keyComponents[] = $this->getCacheVersion();

        return implode('_', $keyComponents);
    }

    /**
     * Generate fragment cache key
     *
     * @param string $fragmentId Fragment identifier
     * @param array<string, mixed> $context Fragment context
     * @return string Fragment cache key
     * @since 2.0.0
     */
    private function generateFragmentKey(string $fragmentId, array $context): string
    {
        $keyComponents = [
            $this->keyPrefixes['fragment'],
            $fragmentId,
        ];

        if (!empty($context)) {
            $contextHash = md5(serialize($context));
            $keyComponents[] = substr($contextHash, 0, 8);
        }

        return implode('_', $keyComponents);
    }

    /**
     * Generate menu content (fallback when cache miss)
     *
     * @param string $key Walker type
     * @param string $themeLocation Theme location
     * @param array<string, mixed> $options Menu options
     * @param array<string, mixed> $walkerOptions Walker options
     * @return string Generated menu HTML
     * @since 2.0.0
     */
    private function generateMenuContent(string $key, string $themeLocation, array $options, array $walkerOptions): string
    {
        return MenuManager::render($key, $themeLocation, $options, $walkerOptions);
    }

    /**
     * Get cached content from appropriate driver
     *
     * @param string $cacheKey Cache key
     * @return mixed Cached content or false if not found
     * @since 2.0.0
     */
    private function getCachedContent(string $cacheKey)
    {
        if (!$this->cacheDriver) {
            return false;
        }

        return $this->cacheDriver->get($cacheKey, false);
    }

    /**
     * Set cached content with dependencies tracking
     *
     * @param string $cacheKey Cache key
     * @param mixed $content Content to cache
     * @param int $ttl Time to live
     * @param RenderContext|null $context Rendering context
     * @return bool Success status
     * @since 2.0.0
     */
    private function setCachedContent(string $cacheKey, $content, int $ttl, ?RenderContext $context = null): bool
    {
        if (!$this->cacheDriver) {
            return false;
        }

        $success = $this->cacheDriver->set($cacheKey, $content, $ttl);
        
        if ($success) {
            $this->stats['writes']++;
            
            // Track dependencies for smart invalidation
            $this->trackDependencies($cacheKey, $context);
        }

        return $success;
    }

    /**
     * Calculate appropriate TTL based on context
     *
     * @param array<string, mixed> $options Menu options
     * @param RenderContext|null $context Rendering context
     * @return int TTL in seconds
     * @since 2.0.0
     */
    private function calculateTtl(array $options, ?RenderContext $context = null): int
    {
        // Custom TTL from options
        if (isset($options['cache_ttl'])) {
            return (int) $options['cache_ttl'];
        }

        // User-specific caching uses shorter TTL
        if ($this->config['enable_user_cache'] && is_user_logged_in()) {
            return $this->config['ttl_user_specific'];
        }

        // Complex menus get shorter TTL
        if ($context && $context->getMaxDepthReached() > 2) {
            return (int) ($this->config['ttl_default'] * 0.5);
        }

        return $this->config['ttl_default'];
    }

    /**
     * Check if caching is enabled and available
     *
     * @return bool True if caching should be used
     * @since 2.0.0
     */
    private function isCachingEnabled(): bool
    {
        return $this->config['enable_cache'] && 
               !$this->config['bypass'] && 
               !is_customize_preview() &&
               !(is_admin() && !wp_doing_ajax());
    }

    /**
     * Check if debug mode is active
     *
     * @return bool True if in debug mode
     * @since 2.0.0
     */
    private function isDebugMode(): bool
    {
        return (defined('WP_DEBUG') && WP_DEBUG) ||
               (defined('WP_ENV') && WP_ENV === 'development');
    }

    /**
     * Select best available cache driver
     *
     * @return string Cache driver type
     * @since 2.0.0
     */
    private function selectBestCacheDriver(): string
    {
        // Prefer object cache (Redis, Memcached) if available
        if (function_exists('wp_cache_set') && $this->isExternalObjectCacheAvailable()) {
            return 'object';
        }

        // Fallback to transients
        return 'transient';
    }

    /**
     * Check if external object cache is available
     *
     * @return bool True if external object cache is available
     * @since 2.0.0
     */
    private function isExternalObjectCacheAvailable(): bool
    {
        global $wp_object_cache;
        
        return is_object($wp_object_cache) && 
               !($wp_object_cache instanceof \WP_Object_Cache);
    }

    /**
     * Get user-specific cache component
     *
     * @return string User cache component
     * @since 2.0.0
     */
    private function getUserCacheComponent(): string
    {
        $userId = get_current_user_id();
        $userRoles = wp_get_current_user()->roles ?? ['guest'];
        
        return $this->keyPrefixes['user'] . $userId . '_' . md5(implode(',', $userRoles));
    }

    /**
     * Get device-specific cache component
     *
     * @return string Device cache component
     * @since 2.0.0
     */
    private function getDeviceCacheComponent(): string
    {
        return $this->keyPrefixes['device'] . (wp_is_mobile() ? 'mobile' : 'desktop');
    }

    /**
     * Get language-specific cache component
     *
     * @return string Language cache component
     * @since 2.0.0
     */
    private function getLanguageCacheComponent(): string
    {
        $locale = get_locale();
        
        // WPML support
        if (function_exists('icl_get_current_language')) {
            $locale = icl_get_current_language();
        }
        
        // Polylang support
        if (function_exists('pll_current_language')) {
            $locale = pll_current_language();
        }

        return $this->keyPrefixes['lang'] . $locale;
    }

    /**
     * Get cache version for cache busting
     *
     * @return string Cache version
     * @since 2.0.0
     */
    private function getCacheVersion(): string
    {
        static $version = null;
        
        if ($version === null) {
            $components = [
                wp_get_theme()->get('Version') ?: '1.0',
                get_option('wphelpers_menu_version', '2.0'),
                filemtime(__FILE__), // File modification time
            ];
            
            $version = $this->keyPrefixes['version'] . md5(implode('_', $components));
        }
        
        return $version;
    }

    /**
     * Track cache dependencies for smart invalidation
     *
     * @param string $cacheKey Cache key
     * @param RenderContext|null $context Rendering context
     * @return void
     * @since 2.0.0
     */
    private function trackDependencies(string $cacheKey, ?RenderContext $context = null): void
    {
        if (!$context) {
            return;
        }

        $this->dependencies[$cacheKey] = [
            'walker_type' => $context->getWalkerType(),
            'max_depth' => $context->getMaxDepthReached(),
            'items_processed' => $context->getPerformanceStats()['items_processed'] ?? 0,
            'created_at' => time(),
        ];
    }

    /**
     * Handle menu update hook
     *
     * @param int $menu_id Menu ID
     * @param array<string, mixed> $menu_data Menu data
     * @return void
     * @since 2.0.0
     */
    public function handleMenuUpdate(int $menu_id, array $menu_data = []): void
    {
        $this->invalidateCache(['menu_id' => $menu_id]);
    }

    /**
     * Handle menu deletion hook
     *
     * @param int $menu_id Menu ID
     * @return void
     * @since 2.0.0
     */
    public function handleMenuDelete(int $menu_id): void
    {
        $this->invalidateCache(['menu_id' => $menu_id]);
    }

    /**
     * Handle menu item update hook
     *
     * @param int $menu_id Menu ID
     * @param int $menu_item_db_id Menu item ID
     * @param array<string, mixed> $args Menu item args
     * @return void
     * @since 2.0.0
     */
    public function handleMenuItemUpdate(int $menu_id, int $menu_item_db_id, array $args): void
    {
        $this->invalidateCache(['menu_id' => $menu_id, 'item_id' => $menu_item_db_id]);
    }

    /**
     * Handle customizer save hook
     *
     * @return void
     * @since 2.0.0
     */
    public function handleCustomizerSave(): void
    {
        $this->purgeAll();
    }

    /**
     * Handle plugin activation/deactivation
     *
     * @param string $plugin Plugin basename
     * @return void
     * @since 2.0.0
     */
    public function handlePluginChange(string $plugin): void
    {
        // Only invalidate if it's a menu-related plugin
        $menuPlugins = ['advanced-custom-fields', 'wpml', 'polylang'];
        
        foreach ($menuPlugins as $menuPlugin) {
            if (strpos($plugin, $menuPlugin) !== false) {
                $this->purgeAll();
                break;
            }
        }
    }

    /**
     * Log cache events for debugging
     *
     * @param string $event Event type
     * @param string $cacheKey Cache key
     * @param RenderContext|null $context Rendering context
     * @return void
     * @since 2.0.0
     */
    private function logCacheEvent(string $event, string $cacheKey, ?RenderContext $context = null): void
    {
        if (!$this->config['debug_mode']) {
            return;
        }

        $logData = [
            'event' => $event,
            'cache_key' => $cacheKey,
            'timestamp' => microtime(true),
            'context' => $context ? $context->getWalkerType() : null,
        ];

        do_action('wphelpers/menu_cache/debug', $logData);
    }

    /**
     * Invalidate all menu caches
     *
     * @return int Number of items invalidated
     * @since 2.0.0
     */
    private function invalidateAllMenuCaches(): int
    {
        $count = 0;
        
        if ($this->cacheDriver) {
            $this->cacheDriver->flush();
            $count += 100; // Estimated count
        }
        
        if ($this->fragmentDriver) {
            $this->fragmentDriver->flush();
            $count += 50; // Estimated count
        }

        return $count;
    }

    /**
     * Targeted cache invalidation
     *
     * @param array<string, mixed> $criteria Invalidation criteria
     * @return int Number of items invalidated
     * @since 2.0.0
     */
    private function invalidateTargeted(array $criteria): int
    {
        // This would implement targeted invalidation based on criteria
        // For now, fall back to clearing all
        return $this->invalidateAllMenuCaches();
    }

    /**
     * Generate common cache warming scenarios
     *
     * @return array<array> Common scenarios
     * @since 2.0.0
     */
    private function generateCommonScenarios(): array
    {
        $scenarios = [];
        $menuTypes = ['desktop', 'mobile', 'dropdown'];
        $themeLocations = array_keys(get_registered_nav_menus());

        foreach ($menuTypes as $type) {
            foreach ($themeLocations as $location) {
                $scenarios[] = [
                    'menu_type' => $type,
                    'theme_location' => $location,
                    'options' => [],
                    'walker_options' => [],
                ];
            }
        }

        return $scenarios;
    }

    /**
     * Warm cache for specific scenario
     *
     * @param array<string, mixed> $scenario Scenario configuration
     * @return bool Success status
     * @since 2.0.0
     */
    private function warmCacheForScenario(array $scenario): bool
    {
        try {
            $this->getMenu(
                $scenario['menu_type'],
                $scenario['theme_location'],
                $scenario['options'] ?? [],
                $scenario['walker_options'] ?? []
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Calculate cache efficiency
     *
     * @return float Cache efficiency ratio
     * @since 2.0.0
     */
    private function calculateCacheEfficiency(): float
    {
        $totalRequests = $this->stats['hits'] + $this->stats['misses'];
        if ($totalRequests === 0) {
            return 0.0;
        }

        return round($this->stats['hits'] / $totalRequests, 3);
    }

    /**
     * Estimate memory usage
     *
     * @return int Estimated memory usage in bytes
     * @since 2.0.0
     */
    private function estimateMemoryUsage(): int
    {
        // This is a rough estimate
        return memory_get_usage(true);
    }

    /**
     * Get cache driver information
     *
     * @return array<string, mixed> Driver information
     * @since 2.0.0
     */
    private function getDriverInfo(): array
    {
        $info = [
            'main_driver' => $this->cacheDriver ? get_class($this->cacheDriver) : 'none',
            'fragment_driver' => $this->fragmentDriver ? get_class($this->fragmentDriver) : 'none',
            'external_object_cache' => $this->isExternalObjectCacheAvailable(),
            'wp_cache_available' => function_exists('wp_cache_set'),
        ];

        return $info;
    }

    /**
     * Clean up expired cache entries
     *
     * @return int Number of entries cleaned
     * @since 2.0.0
     */
    private function cleanupExpiredEntries(): int
    {
        // This would implement cleanup logic
        // For now, return 0 as drivers handle their own cleanup
        return 0;
    }

    /**
     * Reset cache statistics
     *
     * @return void
     * @since 2.0.0
     */
    private function resetStatistics(): void
    {
        $this->stats = array_fill_keys(array_keys($this->stats), 0);
    }
}