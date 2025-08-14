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

namespace jamal13647850\wphelpers\Navigation;

use Walker_Nav_Menu;
use jamal13647850\wphelpers\Navigation\Base\WalkerInterface;
use jamal13647850\wphelpers\Navigation\Strategies\DesktopStrategy;
use jamal13647850\wphelpers\Navigation\Strategies\MobileStrategy;
use jamal13647850\wphelpers\Navigation\Strategies\DropdownStrategy;
use jamal13647850\wphelpers\Navigation\Strategies\MultiColumnStrategy;
use jamal13647850\wphelpers\Navigation\ValueObjects\MenuOptions;
use InvalidArgumentException;
use RuntimeException;

defined('ABSPATH') || exit();

/**
 * AlpineNavWalker - Unified navigation walker with strategy pattern
 *
 * A facade that provides a unified interface for different navigation walker
 * strategies. Automatically selects and delegates to the appropriate walker
 * strategy based on the requested menu type and configuration.
 *
 * Features:
 * - Strategy Pattern implementation for different menu types
 * - Automatic strategy selection based on menu type
 * - Backward compatibility with existing AlpineNavWalker usage
 * - Performance optimizations through strategy delegation
 * - Centralized configuration management
 * - Extensible architecture for custom strategies
 *
 * Supported Strategies:
 * - Desktop: Horizontal menus with hover dropdowns
 * - Mobile: Vertical accordion menus for mobile devices
 * - Dropdown: Simple dropdown menus
 * - Multi-column: Desktop menus with multi-column dropdowns
 * - Custom: Extensible for custom implementations
 *
 * @package jamal13647850\wphelpers\Navigation
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
final class AlpineNavWalker extends Walker_Nav_Menu
{
    /**
     * Current menu type identifier
     * @var string
     */
    private string $menuType;

    /**
     * Walker options configuration
     * @var MenuOptions
     */
    private MenuOptions $options;

    /**
     * Current strategy instance
     * @var WalkerInterface|null
     */
    private ?WalkerInterface $strategy = null;

    /**
     * Available walker strategies
     * @var array<string, class-string<WalkerInterface>>
     */
    private static array $strategies = [
        'desktop' => DesktopStrategy::class,
        'mobile' => MobileStrategy::class,
        'dropdown' => DropdownStrategy::class,
        'multi-column' => MultiColumnStrategy::class,
    ];

    /**
     * Strategy instances cache for reuse
     * @var array<string, WalkerInterface>
     */
    private static array $strategyCache = [];

    /**
     * Performance metrics
     * @var array<string, mixed>
     */
    private array $metrics = [
        'strategy_selection_time' => 0,
        'initialization_time' => 0,
        'total_render_time' => 0,
        'strategy_type' => null,
    ];

    /**
     * AlpineNavWalker constructor
     *
     * Initializes the walker with specified menu type and options, then
     * selects and configures the appropriate strategy for rendering.
     *
     * @param string $type Menu type identifier
     * @param array<string, mixed> $options Walker configuration options
     * @throws InvalidArgumentException If menu type is not supported
     * @since 2.0.0
     */
    public function __construct(string $type = 'desktop', array $options = [])
    {
        $startTime = microtime(true);

        $this->menuType = $this->normalizeMenuType($type);
        $this->options = new MenuOptions($options, $this->getDefaultOptionsForType($this->menuType));

        // Select and initialize strategy
        $this->selectStrategy();
        $this->initializeStrategy();

        $this->metrics['initialization_time'] = microtime(true) - $startTime;
        $this->metrics['strategy_type'] = $this->menuType;
    }

    /**
     * Start outputting submenu container
     *
     * Delegates to the current strategy for menu type-specific rendering.
     *
     * @param string $output Reference to the output string
     * @param int $depth Current menu depth
     * @param mixed $args Menu arguments
     * @return void
     * @since 2.0.0
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void
    {
        $this->ensureStrategyLoaded();
        $this->strategy->start_lvl($output, $depth, $args);
    }

    /**
     * End submenu container output
     *
     * Delegates to the current strategy for menu type-specific rendering.
     *
     * @param string $output Reference to the output string
     * @param int $depth Current menu depth
     * @param mixed $args Menu arguments
     * @return void
     * @since 2.0.0
     */
    public function end_lvl(&$output, $depth = 0, $args = []): void
    {
        $this->ensureStrategyLoaded();
        $this->strategy->end_lvl($output, $depth, $args);
    }

    /**
     * Start outputting a menu element
     *
     * Delegates to the current strategy for menu type-specific rendering.
     *
     * @param string $output Reference to the output string
     * @param object $item Menu item data object
     * @param int $depth Current menu depth
     * @param array<string, mixed> $args Menu rendering arguments
     * @param int $id Menu item ID
     * @return void
     * @since 2.0.0
     */
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0): void
    {
        $this->ensureStrategyLoaded();
        
        $startTime = microtime(true);
        $this->strategy->start_el($output, $item, $depth, $args, $id);
        $this->metrics['total_render_time'] += microtime(true) - $startTime;
    }

    /**
     * End menu element output
     *
     * Delegates to the current strategy for menu type-specific rendering.
     *
     * @param string $output Reference to the output string
     * @param object $item Menu item data object
     * @param int $depth Current menu depth
     * @param mixed $args Menu arguments
     * @return void
     * @since 2.0.0
     */
    public function end_el(&$output, $item, $depth = 0, $args = []): void
    {
        $this->ensureStrategyLoaded();
        $this->strategy->end_el($output, $item, $depth, $args);
    }

    /**
     * Get current menu type
     *
     * @return string Current menu type identifier
     * @since 2.0.0
     */
    public function getMenuType(): string
    {
        return $this->menuType;
    }

    /**
     * Get current walker options
     *
     * @return MenuOptions Walker configuration options
     * @since 2.0.0
     */
    public function getOptions(): MenuOptions
    {
        return $this->options;
    }

    /**
     * Get current strategy instance
     *
     * @return WalkerInterface|null Current strategy or null if not loaded
     * @since 2.0.0
     */
    public function getStrategy(): ?WalkerInterface
    {
        return $this->strategy;
    }

    /**
     * Switch to different menu type and strategy
     *
     * Allows dynamic switching between menu types during runtime.
     * Useful for responsive menus or context-specific rendering.
     *
     * @param string $type New menu type
     * @param array<string, mixed> $options New options (optional)
     * @return self Fluent interface
     * @throws InvalidArgumentException If menu type is not supported
     * @since 2.0.0
     */
    public function switchType(string $type, array $options = []): self
    {
        $normalizedType = $this->normalizeMenuType($type);
        
        // Only switch if type actually changed
        if ($normalizedType === $this->menuType && empty($options)) {
            return $this;
        }

        $this->menuType = $normalizedType;
        
        // Merge new options with existing ones
        if (!empty($options)) {
            $this->options = $this->options->with($options);
        }

        // Reset strategy to force reselection
        $this->strategy = null;
        $this->selectStrategy();
        $this->initializeStrategy();

        return $this;
    }

    /**
     * Register custom walker strategy
     *
     * Allows registration of custom walker strategies for extended functionality.
     *
     * @param string $type Strategy type identifier
     * @param class-string<WalkerInterface> $strategyClass Strategy class name
     * @return void
     * @throws InvalidArgumentException If strategy class is invalid
     * @since 2.0.0
     */
    public static function registerStrategy(string $type, string $strategyClass): void
    {
        if (!class_exists($strategyClass)) {
            throw new InvalidArgumentException("Strategy class {$strategyClass} does not exist.");
        }

        if (!is_subclass_of($strategyClass, WalkerInterface::class)) {
            throw new InvalidArgumentException("Strategy class {$strategyClass} must implement WalkerInterface.");
        }

        self::$strategies[$type] = $strategyClass;
    }

    /**
     * Get list of available strategies
     *
     * @return array<string> Array of available strategy type identifiers
     * @since 2.0.0
     */
    public static function getAvailableStrategies(): array
    {
        return array_keys(self::$strategies);
    }

    /**
     * Check if strategy type is supported
     *
     * @param string $type Strategy type to check
     * @return bool True if strategy is supported
     * @since 2.0.0
     */
    public static function supportsStrategy(string $type): bool
    {
        return isset(self::$strategies[$type]);
    }

    /**
     * Get performance metrics
     *
     * Returns performance data collected during walker operation.
     *
     * @return array<string, mixed> Performance metrics
     * @since 2.0.0
     */
    public function getMetrics(): array
    {
        $metrics = $this->metrics;
        
        // Add strategy metrics if available
        if ($this->strategy && method_exists($this->strategy, 'getMetrics')) {
            $metrics['strategy_metrics'] = $this->strategy->getMetrics();
        }

        return $metrics;
    }

    /**
     * Reset walker state for reuse
     *
     * Clears internal state to allow the same walker instance to be used
     * for multiple menu renderings.
     *
     * @return void
     * @since 2.0.0
     */
    public function reset(): void
    {
        if ($this->strategy && method_exists($this->strategy, 'reset')) {
            $this->strategy->reset();
        }

        // Reset metrics
        $this->metrics = [
            'strategy_selection_time' => 0,
            'initialization_time' => 0,
            'total_render_time' => 0,
            'strategy_type' => $this->menuType,
        ];
    }

    /**
     * Get walker capabilities
     *
     * Returns capabilities of the current strategy.
     *
     * @return array<string, mixed> Walker capabilities
     * @since 2.0.0
     */
    public function getCapabilities(): array
    {
        $this->ensureStrategyLoaded();
        
        if (method_exists($this->strategy, 'getCapabilities')) {
            return $this->strategy->getCapabilities();
        }

        return [];
    }

    /**
     * Export complete walker state for debugging
     *
     * @return array<string, mixed> Complete walker state
     * @since 2.0.0
     */
    public function exportState(): array
    {
        $state = [
            'menu_type' => $this->menuType,
            'options' => $this->options->toArray(),
            'metrics' => $this->getMetrics(),
            'strategy_class' => $this->strategy ? get_class($this->strategy) : null,
            'available_strategies' => self::getAvailableStrategies(),
        ];

        // Add strategy state if available
        if ($this->strategy && method_exists($this->strategy, 'exportState')) {
            $state['strategy_state'] = $this->strategy->exportState();
        }

        return $state;
    }

    /**
     * Select appropriate strategy based on menu type
     *
     * @return void
     * @throws InvalidArgumentException If strategy is not found
     * @since 2.0.0
     */
    private function selectStrategy(): void
    {
        $startTime = microtime(true);

        if (!isset(self::$strategies[$this->menuType])) {
            throw new InvalidArgumentException("Unsupported menu type: {$this->menuType}");
        }

        $strategyClass = self::$strategies[$this->menuType];
        $cacheKey = $strategyClass . '_' . md5(serialize($this->options->toArray()));

        // Use cached strategy if available
        if (isset(self::$strategyCache[$cacheKey])) {
            $this->strategy = self::$strategyCache[$cacheKey];
        } else {
            // Create new strategy instance
            $this->strategy = new $strategyClass($this->options->toArray());
            
            // Cache for reuse
            self::$strategyCache[$cacheKey] = $this->strategy;
        }

        $this->metrics['strategy_selection_time'] = microtime(true) - $startTime;
    }

    /**
     * Initialize the selected strategy
     *
     * @return void
     * @throws RuntimeException If strategy initialization fails
     * @since 2.0.0
     */
    private function initializeStrategy(): void
    {
        if (!$this->strategy) {
            throw new RuntimeException("No strategy selected for menu type: {$this->menuType}");
        }

        // Initialize strategy if method exists
        if (method_exists($this->strategy, 'initialize')) {
            $this->strategy->initialize($this->options->toArray());
        }

        // Validate strategy configuration
        if (method_exists($this->strategy, 'validateConfiguration')) {
            $this->strategy->validateConfiguration();
        }
    }

    /**
     * Ensure strategy is loaded and ready
     *
     * @return void
     * @throws RuntimeException If strategy is not loaded
     * @since 2.0.0
     */
    private function ensureStrategyLoaded(): void
    {
        if (!$this->strategy) {
            throw new RuntimeException("Strategy not loaded for menu type: {$this->menuType}");
        }
    }

    /**
     * Normalize menu type identifier
     *
     * Handles legacy type names and aliases for backward compatibility.
     *
     * @param string $type Raw menu type
     * @return string Normalized menu type
     * @since 2.0.0
     */
    private function normalizeMenuType(string $type): string
    {
        // Handle legacy aliases
        $aliases = [
            'multi-column-desktop' => 'multi-column',
            'overlay-mobile' => 'mobile',
            'simple' => 'dropdown',
        ];

        $normalizedType = strtolower(trim($type));
        
        return $aliases[$normalizedType] ?? $normalizedType;
    }

    /**
     * Get default options for specific menu type
     *
     * @param string $menuType Menu type identifier
     * @return array<string, mixed> Default options
     * @since 2.0.0
     */
    private function getDefaultOptionsForType(string $menuType): array
    {
        $commonDefaults = [
            'enable_icons' => true,
            'enable_alpine' => true,
            'enable_caching' => true,
            'enable_aria' => true,
            'enable_keyboard_nav' => true,
            'max_depth' => 3,
        ];

        $typeSpecificDefaults = [
            'desktop' => [
                'layout' => 'horizontal',
                'dropdown_behavior' => 'hover',
                'hover_delay' => 200,
                'enable_mega_menu' => false,
            ],
            'mobile' => [
                'layout' => 'vertical',
                'accordion_mode' => 'classic',
                'enable_touch' => true,
                'animation_duration' => 300,
            ],
            'dropdown' => [
                'layout' => 'vertical',
                'max_depth' => 2,
                'simple_mode' => true,
            ],
            'multi-column' => [
                'layout' => 'horizontal',
                'columns' => 3,
                'enable_mega_menu' => true,
                'dropdown_behavior' => 'hover',
            ],
        ];

        return array_merge($commonDefaults, $typeSpecificDefaults[$menuType] ?? []);
    }

    /**
     * Cleanup resources and cached strategies
     *
     * @return void
     * @since 2.0.0
     */
    public static function cleanup(): void
    {
        // Cleanup cached strategies
        foreach (self::$strategyCache as $strategy) {
            if (method_exists($strategy, 'cleanup')) {
                $strategy->cleanup();
            }
        }
        
        self::$strategyCache = [];
    }

    /**
     * Get walker metadata
     *
     * @return array<string, mixed> Walker metadata
     * @since 2.0.0
     */
    public function getMetadata(): array
    {
        return [
            'name' => 'AlpineNavWalker',
            'version' => '2.0.0',
            'author' => 'Sayyed Jamal Ghasemi',
            'description' => 'Unified navigation walker with strategy pattern support',
            'architecture' => 'Strategy Pattern with Facade',
            'supported_types' => self::getAvailableStrategies(),
            'current_type' => $this->menuType,
            'strategy_class' => $this->strategy ? get_class($this->strategy) : null,
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
        ];
    }

    /**
     * Destructor - ensure cleanup
     *
     * @since 2.0.0
     */
    public function __destruct()
    {
        if ($this->strategy && method_exists($this->strategy, 'cleanup')) {
            $this->strategy->cleanup();
        }
    }
}