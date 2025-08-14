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

namespace jamal13647850\wphelpers\Navigation\Base;

use Walker_Nav_Menu;
use jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem;
use jamal13647850\wphelpers\Navigation\ValueObjects\MenuOptions;
use jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext;

defined('ABSPATH') || exit();

/**
 * AbstractWalker - Base class for all navigation walkers
 *
 * Provides common functionality, security measures, and standardized structure
 * for all navigation walker implementations in the wp-helpers package.
 *
 * Features:
 * - Centralized security (sanitization, escaping, nonce validation)
 * - Common rendering patterns and utilities
 * - Standardized option management
 * - Performance optimizations (caching, lazy loading)
 * - Accessibility compliance (ARIA attributes, keyboard navigation)
 * - Alpine.js integration helpers
 *
 * Architecture Pattern:
 * - Template Method Pattern: Defines skeleton of walker algorithm
 * - Strategy Pattern: Delegates specific rendering to child classes
 * - Observer Pattern: Hooks for extensibility
 *
 * Child classes must implement:
 * - renderMenuItem(): Specific item rendering logic
 * - getDefaultOptions(): Walker-specific default configuration
 * - getWalkerType(): Unique identifier for the walker type
 *
 * @package jamal13647850\wphelpers\Navigation\Base
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
abstract class AbstractWalker extends Walker_Nav_Menu
{
    /**
     * Walker configuration options merged with defaults
     * @var MenuOptions
     * @since 2.0.0
     */
    protected MenuOptions $options;

    /**
     * Current rendering context and state
     * @var RenderContext
     * @since 2.0.0
     */
    protected RenderContext $context;

    /**
     * Cache for processed menu items to avoid redundant processing
     * @var array<int, MenuItem>
     * @since 2.0.0
     */
    protected array $itemCache = [];

    /**
     * Performance metrics for debugging and optimization
     * @var array<string, mixed>
     * @since 2.0.0
     */
    protected array $metrics = [
        'start_time' => null,
        'items_processed' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
    ];

    /**
     * AbstractWalker constructor
     *
     * Initializes the walker with provided options, sets up rendering context,
     * and starts performance monitoring for optimization purposes.
     *
     * @param array<string, mixed> $options Walker-specific configuration options
     * @since 2.0.0
     */
    public function __construct(array $options = [])
    {
        // Initialize performance monitoring
        $this->metrics['start_time'] = microtime(true);

        // Merge provided options with walker-specific defaults
        $this->options = new MenuOptions(
            $options,
            $this->getDefaultOptions()
        );

        // Initialize rendering context
        $this->context = new RenderContext(
            $this->getWalkerType(),
            $this->options
        );

        // Allow child classes to perform additional initialization
        $this->initializeWalker();
    }

    /**
     * Get walker-specific default options
     *
     * Child classes must implement this method to provide their default
     * configuration options that will be merged with user-provided options.
     *
     * @return array<string, mixed> Array of default options
     * @since 2.0.0
     */
    abstract protected function getDefaultOptions(): array;

    /**
     * Get unique walker type identifier
     *
     * Used for caching, hooks, and debugging. Should return a unique
     * string identifier for the specific walker implementation.
     *
     * @return string Unique walker type identifier
     * @since 2.0.0
     */
    abstract protected function getWalkerType(): string;

    /**
     * Render individual menu item
     *
     * Child classes must implement this method to define how individual
     * menu items should be rendered according to their specific needs.
     *
     * @param MenuItem $item Menu item to render
     * @param RenderContext $context Current rendering context
     * @return string Rendered HTML for the menu item
     * @since 2.0.0
     */
    abstract protected function renderMenuItem(MenuItem $item, RenderContext $context): string;

    /**
     * Additional walker initialization hook
     *
     * Override this method in child classes to perform any additional
     * initialization after the base constructor has completed.
     *
     * @return void
     * @since 2.0.0
     */
    protected function initializeWalker(): void
    {
        // Default implementation does nothing
        // Child classes can override for custom initialization
    }

    /**
     * Start outputting a menu element
     *
     * WordPress core method override that provides our standardized
     * processing pipeline with security, caching, and performance monitoring.
     *
     * @param string $output Menu HTML output (by reference)
     * @param object $item Menu item data object
     * @param int $depth Menu depth (0 = top level)
     * @param array<string, mixed> $args Menu rendering arguments
     * @param int $id Menu item ID
     * @return void
     * @since 2.0.0
     */
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0): void
    {
        // Performance monitoring
        $this->metrics['items_processed']++;

        // Convert WordPress menu item to our standardized format
        $menuItem = $this->createMenuItem($item, $depth, $args);

        // Update rendering context for current item
        $this->context->setCurrentItem($menuItem, $depth);

        // Check cache first for performance
        $cacheKey = $this->generateItemCacheKey($menuItem, $depth);
        if (isset($this->itemCache[$cacheKey])) {
            $output .= $this->itemCache[$cacheKey];
            $this->metrics['cache_hits']++;
            return;
        }

        // Apply pre-render filters for extensibility
        $menuItem = $this->applyPreRenderFilters($menuItem);

        // Delegate to child class for specific rendering
        $itemHtml = $this->renderMenuItem($menuItem, $this->context);

        // Apply post-render filters for extensibility
        $itemHtml = $this->applyPostRenderFilters($itemHtml, $menuItem);

        // Cache the result for future use
        $this->itemCache[$cacheKey] = $itemHtml;
        $this->metrics['cache_misses']++;

        // Add to output
        $output .= $itemHtml;
    }

    /**
     * Create standardized MenuItem from WordPress menu item object
     *
     * Converts WordPress's stdClass menu item to our type-safe MenuItem
     * value object with validation and security measures applied.
     *
     * @param object $wpItem WordPress menu item object
     * @param int $depth Current menu depth
     * @param array<string, mixed> $args Menu rendering arguments
     * @return MenuItem Standardized menu item object
     * @since 2.0.0
     */
    protected function createMenuItem($wpItem, int $depth, array $args): MenuItem
    {
        return MenuItem::fromWordPressItem($wpItem, $depth, $args);
    }

    /**
     * Generate cache key for menu item
     *
     * Creates a unique cache key based on item properties and rendering context
     * to enable efficient caching without conflicts.
     *
     * @param MenuItem $item Menu item object
     * @param int $depth Current menu depth
     * @return string Unique cache key
     * @since 2.0.0
     */
    protected function generateItemCacheKey(MenuItem $item, int $depth): string
    {
        return sprintf(
            '%s_%d_%d_%s',
            $this->getWalkerType(),
            $item->getId(),
            $depth,
            md5(serialize($this->options->toArray()))
        );
    }

    /**
     * Apply pre-render filters for extensibility
     *
     * Allows themes and plugins to modify menu items before rendering
     * through WordPress filter system.
     *
     * @param MenuItem $item Menu item to filter
     * @return MenuItem Filtered menu item
     * @since 2.0.0
     */
    protected function applyPreRenderFilters(MenuItem $item): MenuItem
    {
        $walkerType = $this->getWalkerType();
        
        // Generic pre-render filter
        $item = apply_filters('wphelpers/walker/pre_render', $item, $this->context);
        
        // Walker-specific pre-render filter
        $item = apply_filters("wphelpers/walker/{$walkerType}/pre_render", $item, $this->context);
        
        return $item;
    }

    /**
     * Apply post-render filters for extensibility
     *
     * Allows themes and plugins to modify rendered HTML after processing
     * through WordPress filter system.
     *
     * @param string $html Rendered HTML
     * @param MenuItem $item Menu item that was rendered
     * @return string Filtered HTML
     * @since 2.0.0
     */
    protected function applyPostRenderFilters(string $html, MenuItem $item): string
    {
        $walkerType = $this->getWalkerType();
        
        // Generic post-render filter
        $html = apply_filters('wphelpers/walker/post_render', $html, $item, $this->context);
        
        // Walker-specific post-render filter
        $html = apply_filters("wphelpers/walker/{$walkerType}/post_render", $html, $item, $this->context);
        
        return $html;
    }

    /**
     * Get current performance metrics
     *
     * Returns performance data for debugging and optimization purposes.
     * Useful for identifying bottlenecks and cache efficiency.
     *
     * @return array<string, mixed> Performance metrics
     * @since 2.0.0
     */
    public function getMetrics(): array
    {
        $currentTime = microtime(true);
        $executionTime = $currentTime - $this->metrics['start_time'];
        
        return array_merge($this->metrics, [
            'execution_time' => $executionTime,
            'cache_hit_ratio' => $this->calculateCacheHitRatio(),
            'items_per_second' => $executionTime > 0 ? $this->metrics['items_processed'] / $executionTime : 0,
        ]);
    }

    /**
     * Calculate cache hit ratio for performance analysis
     *
     * @return float Cache hit ratio (0.0 to 1.0)
     * @since 2.0.0
     */
    protected function calculateCacheHitRatio(): float
    {
        $totalAccess = $this->metrics['cache_hits'] + $this->metrics['cache_misses'];
        return $totalAccess > 0 ? $this->metrics['cache_hits'] / $totalAccess : 0.0;
    }

    /**
     * Reset walker state for reuse
     *
     * Clears caches and resets state to allow the same walker instance
     * to be used for multiple menu renderings.
     *
     * @return void
     * @since 2.0.0
     */
    public function reset(): void
    {
        $this->itemCache = [];
        $this->metrics = [
            'start_time' => microtime(true),
            'items_processed' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];
        $this->context->reset();
    }

    /**
     * Get walker configuration options
     *
     * @return MenuOptions Current walker options
     * @since 2.0.0
     */
    public function getOptions(): MenuOptions
    {
        return $this->options;
    }

    /**
     * Get current rendering context
     *
     * @return RenderContext Current rendering context
     * @since 2.0.0
     */
    public function getContext(): RenderContext
    {
        return $this->context;
    }
}