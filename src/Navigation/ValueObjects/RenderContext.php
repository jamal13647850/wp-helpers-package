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

namespace jamal13647850\wphelpers\Navigation\ValueObjects;

defined('ABSPATH') || exit();

/**
 * RenderContext Value Object
 *
 * Maintains rendering state and context information during menu walker execution.
 * Provides a centralized way to track rendering progress, depth, parent relationships,
 * and other contextual information needed for complex menu rendering.
 *
 * Features:
 * - Immutable state snapshots for consistency
 * - Parent-child relationship tracking
 * - Depth management and validation
 * - Alpine.js state coordination
 * - Performance monitoring integration
 * - Extensible context data storage
 *
 * @package jamal13647850\wphelpers\Navigation\ValueObjects
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
final class RenderContext
{
    /**
     * Walker type identifier
     * @var string
     */
    private string $walkerType;

    /**
     * Walker configuration options
     * @var MenuOptions
     */
    private MenuOptions $options;

    /**
     * Currently processing menu item
     * @var MenuItem|null
     */
    private ?MenuItem $currentItem = null;

    /**
     * Current rendering depth (0 = top level)
     * @var int
     */
    private int $currentDepth = 0;

    /**
     * Stack of parent menu items by depth level
     * @var array<int, MenuItem>
     */
    private array $parentStack = [];

    /**
     * Stack of open submenu IDs for Alpine.js state management
     * @var array<int, int>
     */
    private array $openSubmenus = [];

    /**
     * Total number of items processed
     * @var int
     */
    private int $itemsProcessed = 0;

    /**
     * Maximum depth encountered during rendering
     * @var int
     */
    private int $maxDepthReached = 0;

    /**
     * Custom context data for extensibility
     * @var array<string, mixed>
     */
    private array $customData = [];

    /**
     * Rendering timestamps for performance tracking
     * @var array<string, float>
     */
    private array $timestamps = [];

    /**
     * Whether the context is in debug mode
     * @var bool
     */
    private bool $debugMode;

    /**
     * RenderContext constructor
     *
     * @param string $walkerType Walker type identifier
     * @param MenuOptions $options Walker configuration options
     * @since 2.0.0
     */
    public function __construct(string $walkerType, MenuOptions $options)
    {
        $this->walkerType = $walkerType;
        $this->options = $options;
        $this->debugMode = $options->getBool('debug_mode', false);
        $this->timestamps['created'] = microtime(true);
    }

    /**
     * Set current menu item and update context
     *
     * Updates the rendering context with the new current item and manages
     * parent-child relationships, depth tracking, and state management.
     *
     * @param MenuItem $item Current menu item
     * @param int $depth Current rendering depth
     * @return void
     * @since 2.0.0
     */
    public function setCurrentItem(MenuItem $item, int $depth): void
    {
        $this->currentItem = $item;
        $this->currentDepth = $depth;
        $this->itemsProcessed++;

        // Update maximum depth reached
        $this->maxDepthReached = max($this->maxDepthReached, $depth);

        // Manage parent stack
        $this->updateParentStack($item, $depth);

        // Track timing for performance analysis
        if ($this->debugMode) {
            $this->timestamps["item_{$item->getId()}"] = microtime(true);
        }
    }

    /**
     * Update parent stack with current item
     *
     * Maintains the parent-child relationship stack, trimming deeper levels
     * when moving back up the hierarchy.
     *
     * @param MenuItem $item Current menu item
     * @param int $depth Current rendering depth
     * @return void
     * @since 2.0.0
     */
    private function updateParentStack(MenuItem $item, int $depth): void
    {
        // Trim parent stack if we've moved to a shallower depth
        $this->parentStack = array_slice($this->parentStack, 0, $depth, true);

        // Add current item as parent if it has children
        if ($item->hasChildren()) {
            $this->parentStack[$depth] = $item;
        }
    }

    /**
     * Get current menu item
     *
     * @return MenuItem|null Current menu item or null if none set
     * @since 2.0.0
     */
    public function getCurrentItem(): ?MenuItem
    {
        return $this->currentItem;
    }

    /**
     * Get current rendering depth
     *
     * @return int Current depth (0 = top level)
     * @since 2.0.0
     */
    public function getCurrentDepth(): int
    {
        return $this->currentDepth;
    }

    /**
     * Get parent item at specific depth
     *
     * @param int|null $depth Depth level (null for immediate parent)
     * @return MenuItem|null Parent item or null if none exists
     * @since 2.0.0
     */
    public function getParent(?int $depth = null): ?MenuItem
    {
        if ($depth === null) {
            $depth = $this->currentDepth - 1;
        }

        return $this->parentStack[$depth] ?? null;
    }

    /**
     * Get all parent items in the current hierarchy
     *
     * @return array<int, MenuItem> Array of parent items indexed by depth
     * @since 2.0.0
     */
    public function getParentStack(): array
    {
        return $this->parentStack;
    }

    /**
     * Check if currently at top level
     *
     * @return bool True if at depth 0
     * @since 2.0.0
     */
    public function isTopLevel(): bool
    {
        return $this->currentDepth === 0;
    }

    /**
     * Check if current depth exceeds maximum allowed
     *
     * @return bool True if depth exceeds max_depth option
     * @since 2.0.0
     */
    public function exceedsMaxDepth(): bool
    {
        $maxDepth = $this->options->getInt('max_depth', 0);
        return $maxDepth > 0 && $this->currentDepth >= $maxDepth;
    }

    /**
     * Get walker type identifier
     *
     * @return string Walker type
     * @since 2.0.0
     */
    public function getWalkerType(): string
    {
        return $this->walkerType;
    }

    /**
     * Get walker options
     *
     * @return MenuOptions Walker configuration
     * @since 2.0.0
     */
    public function getOptions(): MenuOptions
    {
        return $this->options;
    }

    /**
     * Add or update custom context data
     *
     * Allows storing arbitrary data in the context for use by child classes
     * or external code that needs to maintain state during rendering.
     *
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     * @since 2.0.0
     */
    public function setCustomData(string $key, $value): void
    {
        $this->customData[$key] = $value;
    }

    /**
     * Get custom context data
     *
     * @param string $key Data key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Custom data value or default
     * @since 2.0.0
     */
    public function getCustomData(string $key, $default = null)
    {
        return $this->customData[$key] ?? $default;
    }

    /**
     * Check if custom data exists
     *
     * @param string $key Data key to check
     * @return bool True if custom data exists
     * @since 2.0.0
     */
    public function hasCustomData(string $key): bool
    {
        return array_key_exists($key, $this->customData);
    }

    /**
     * Remove custom data
     *
     * @param string $key Data key to remove
     * @return void
     * @since 2.0.0
     */
    public function removeCustomData(string $key): void
    {
        unset($this->customData[$key]);
    }

    /**
     * Track open submenu for Alpine.js state management
     *
     * Maintains a record of which submenus are currently open for proper
     * Alpine.js state synchronization.
     *
     * @param int $depth Submenu depth
     * @param int $itemId Menu item ID
     * @return void
     * @since 2.0.0
     */
    public function openSubmenu(int $depth, int $itemId): void
    {
        $this->openSubmenus[$depth] = $itemId;
    }

    /**
     * Close submenu at specific depth
     *
     * @param int $depth Submenu depth to close
     * @return void
     * @since 2.0.0
     */
    public function closeSubmenu(int $depth): void
    {
        unset($this->openSubmenus[$depth]);
    }

    /**
     * Check if submenu is open at depth
     *
     * @param int $depth Depth to check
     * @param int|null $itemId Specific item ID to check (null for any)
     * @return bool True if submenu is open
     * @since 2.0.0
     */
    public function isSubmenuOpen(int $depth, ?int $itemId = null): bool
    {
        if (!isset($this->openSubmenus[$depth])) {
            return false;
        }

        return $itemId === null || $this->openSubmenus[$depth] === $itemId;
    }

    /**
     * Get all open submenus
     *
     * @return array<int, int> Array of open submenus (depth => itemId)
     * @since 2.0.0
     */
    public function getOpenSubmenus(): array
    {
        return $this->openSubmenus;
    }

    /**
     * Generate unique ID for current context
     *
     * Useful for creating unique HTML IDs, cache keys, etc.
     *
     * @param string $prefix Optional prefix for the ID
     * @return string Unique context ID
     * @since 2.0.0
     */
    public function generateId(string $prefix = ''): string
    {
        $parts = [
            $this->walkerType,
            $this->currentDepth,
            $this->currentItem ? $this->currentItem->getId() : 'none',
            substr(md5(serialize($this->parentStack)), 0, 8),
        ];

        $id = implode('-', $parts);
        
        return $prefix ? "{$prefix}-{$id}" : $id;
    }

    /**
     * Generate Alpine.js data context
     *
     * Creates the Alpine.js data object for state management based on
     * current context and walker options.
     *
     * @return array<string, mixed> Alpine.js data context
     * @since 2.0.0
     */
    public function generateAlpineContext(): array
    {
        $accordionMode = $this->options->getString('accordion_mode', 'classic');
        
        $context = [
            'mobileMenuOpen' => false,
            'activeDepth' => 0,
        ];

        if ($accordionMode === 'classic') {
            // Classic mode: only one submenu open per depth level
            $context['opens'] = array_fill(0, $this->maxDepthReached + 1, null);
        } else {
            // Independent mode: each item manages its own state
            $context['itemStates'] = [];
        }

        // Add custom Alpine data from options
        $customAlpineData = $this->options->getArray('alpine_data', []);
        $context = array_merge($context, $customAlpineData);

        return $context;
    }

    /**
     * Get performance statistics
     *
     * @return array<string, mixed> Performance statistics
     * @since 2.0.0
     */
    public function getPerformanceStats(): array
    {
        $currentTime = microtime(true);
        $totalTime = $currentTime - $this->timestamps['created'];

        return [
            'total_time' => $totalTime,
            'items_processed' => $this->itemsProcessed,
            'max_depth_reached' => $this->maxDepthReached,
            'items_per_second' => $totalTime > 0 ? $this->itemsProcessed / $totalTime : 0,
            'average_time_per_item' => $this->itemsProcessed > 0 ? $totalTime / $this->itemsProcessed : 0,
            'memory_usage' => memory_get_usage(true),
            'timestamps_count' => count($this->timestamps),
        ];
    }

    /**
     * Reset context for reuse
     *
     * Clears all state while preserving configuration, allowing the same
     * context to be reused for multiple menu renderings.
     *
     * @return void
     * @since 2.0.0
     */
    public function reset(): void
    {
        $this->currentItem = null;
        $this->currentDepth = 0;
        $this->parentStack = [];
        $this->openSubmenus = [];
        $this->itemsProcessed = 0;
        $this->maxDepthReached = 0;
        $this->customData = [];
        $this->timestamps = ['created' => microtime(true)];
    }

    /**
     * Create a snapshot of current context state
     *
     * Useful for debugging or storing context state at specific points
     * during rendering.
     *
     * @return array<string, mixed> Context state snapshot
     * @since 2.0.0
     */
    public function createSnapshot(): array
    {
        return [
            'walker_type' => $this->walkerType,
            'current_item_id' => $this->currentItem ? $this->currentItem->getId() : null,
            'current_depth' => $this->currentDepth,
            'parent_stack_size' => count($this->parentStack),
            'open_submenus' => $this->openSubmenus,
            'items_processed' => $this->itemsProcessed,
            'max_depth_reached' => $this->maxDepthReached,
            'custom_data_keys' => array_keys($this->customData),
            'performance_stats' => $this->getPerformanceStats(),
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Convert context to array for debugging
     *
     * @return array<string, mixed> Array representation of context
     * @since 2.0.0
     */
    public function toArray(): array
    {
        return [
            'walker_type' => $this->walkerType,
            'current_item' => $this->currentItem ? $this->currentItem->toArray() : null,
            'current_depth' => $this->currentDepth,
            'parent_stack' => array_map(fn($item) => $item->toArray(), $this->parentStack),
            'open_submenus' => $this->openSubmenus,
            'items_processed' => $this->itemsProcessed,
            'max_depth_reached' => $this->maxDepthReached,
            'custom_data' => $this->customData,
            'performance_stats' => $this->getPerformanceStats(),
            'alpine_context' => $this->generateAlpineContext(),
        ];
    }
}