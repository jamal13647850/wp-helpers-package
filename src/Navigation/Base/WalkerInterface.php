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

defined('ABSPATH') || exit();

/**
 * WalkerInterface - Contract for navigation walker implementations
 *
 * Defines the standard interface that all navigation walkers must implement
 * to ensure consistency across different walker types and rendering strategies.
 * Supports the Strategy Pattern for flexible walker selection and swapping.
 *
 * Interface Methods:
 * - Menu structure methods (start_lvl, end_lvl, start_el, end_el)
 * - Configuration and initialization
 * - Type identification and capabilities
 * - Performance monitoring
 *
 * @package jamal13647850\wphelpers\Navigation\Base
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
interface WalkerInterface
{
    /**
     * Start outputting submenu container
     *
     * Called when WordPress encounters a submenu level. Implementations
     * should handle appropriate container opening based on walker type.
     *
     * @param string $output Reference to the output string being built
     * @param int $depth Current menu depth (0 = top level)
     * @param mixed $args Additional arguments passed from wp_nav_menu()
     * @return void
     * @since 2.0.0
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void;

    /**
     * End submenu container output
     *
     * Called when WordPress finishes outputting a submenu level.
     * Should close containers opened in start_lvl().
     *
     * @param string $output Reference to the output string being built
     * @param int $depth Current menu depth (0 = top level)
     * @param mixed $args Additional arguments passed from wp_nav_menu()
     * @return void
     * @since 2.0.0
     */
    public function end_lvl(&$output, $depth = 0, $args = []): void;

    /**
     * Start outputting a menu element
     *
     * Called for each menu item. Should handle the complete rendering
     * of individual menu items including links and content.
     *
     * @param string $output Reference to the output string being built
     * @param object $item Menu item data object
     * @param int $depth Current menu depth (0 = top level)
     * @param array<string, mixed> $args Menu rendering arguments
     * @param int $id Menu item ID
     * @return void
     * @since 2.0.0
     */
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0): void;

    /**
     * End menu element output
     *
     * Called when WordPress finishes outputting a menu item.
     * Should close any containers opened in start_el().
     *
     * @param string $output Reference to the output string being built
     * @param object $item Menu item data object
     * @param int $depth Current menu depth (0 = top level)
     * @param mixed $args Additional arguments passed from wp_nav_menu()
     * @return void
     * @since 2.0.0
     */
    public function end_el(&$output, $item, $depth = 0, $args = []): void;

    /**
     * Get unique walker type identifier
     *
     * Should return a unique string identifier that describes this
     * walker type. Used for caching, hooks, and debugging.
     *
     * @return string Unique walker type identifier
     * @since 2.0.0
     */
    public function getWalkerType(): string;

    /**
     * Get walker capabilities
     *
     * Returns an array describing what features this walker supports.
     * Used for compatibility checking and feature detection.
     *
     * @return array<string, bool> Capability flags
     * @since 2.0.0
     *
     * @example
     * [
     *     'supports_icons' => true,
     *     'supports_alpine' => true,
     *     'supports_caching' => true,
     *     'supports_accessibility' => true,
     *     'supports_multi_level' => true,
     *     'max_depth' => 5
     * ]
     */
    public function getCapabilities(): array;

    /**
     * Check if walker supports specific feature
     *
     * Convenience method for checking individual capabilities
     * without needing to inspect the full capabilities array.
     *
     * @param string $feature Feature name to check
     * @return bool True if feature is supported
     * @since 2.0.0
     */
    public function supportsFeature(string $feature): bool;

    /**
     * Get walker configuration options
     *
     * Returns the current configuration options for this walker instance.
     * Should include all customizable settings and their current values.
     *
     * @return \jamal13647850\wphelpers\Navigation\ValueObjects\MenuOptions Walker options
     * @since 2.0.0
     */
    public function getOptions(): \jamal13647850\wphelpers\Navigation\ValueObjects\MenuOptions;

    /**
     * Get current rendering context
     *
     * Returns the current rendering context with state information,
     * parent tracking, and other contextual data.
     *
     * @return \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext Rendering context
     * @since 2.0.0
     */
    public function getContext(): \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext;

    /**
     * Initialize walker with configuration
     *
     * Performs any necessary setup or initialization for the walker.
     * Called once before menu rendering begins.
     *
     * @param array<string, mixed> $config Walker configuration
     * @return void
     * @since 2.0.0
     */
    public function initialize(array $config = []): void;

    /**
     * Validate configuration and dependencies
     *
     * Checks that the walker is properly configured and all dependencies
     * are available. Should throw exceptions for critical issues.
     *
     * @return bool True if validation passes
     * @throws \InvalidArgumentException If configuration is invalid
     * @throws \RuntimeException If dependencies are missing
     * @since 2.0.0
     */
    public function validateConfiguration(): bool;

    /**
     * Reset walker state for reuse
     *
     * Clears any internal state to allow the same walker instance
     * to be used for multiple menu renderings without conflicts.
     *
     * @return void
     * @since 2.0.0
     */
    public function reset(): void;

    /**
     * Get performance metrics
     *
     * Returns performance data collected during rendering for
     * optimization and debugging purposes.
     *
     * @return array<string, mixed> Performance metrics
     * @since 2.0.0
     *
     * @example
     * [
     *     'execution_time' => 0.025,
     *     'memory_usage' => 1048576,
     *     'items_processed' => 15,
     *     'cache_hits' => 8,
     *     'cache_misses' => 7
     * ]
     */
    public function getMetrics(): array;

    /**
     * Handle walker-specific hooks and filters
     *
     * Allows walkers to register their own hooks and filters for
     * extended functionality and customization points.
     *
     * @return void
     * @since 2.0.0
     */
    public function registerHooks(): void;

    /**
     * Clean up resources and unregister hooks
     *
     * Performs cleanup when the walker is no longer needed.
     * Should unregister any hooks and free resources.
     *
     * @return void
     * @since 2.0.0
     */
    public function cleanup(): void;

    /**
     * Get walker metadata for introspection
     *
     * Returns metadata about the walker including version, author,
     * description, and other identifying information.
     *
     * @return array<string, mixed> Walker metadata
     * @since 2.0.0
     *
     * @example
     * [
     *     'name' => 'Desktop Navigation Walker',
     *     'version' => '2.0.0',
     *     'author' => 'Sayyed Jamal Ghasemi',
     *     'description' => 'Desktop menu walker with hover dropdowns',
     *     'supports' => ['icons', 'mega-menu', 'alpine-js'],
     *     'requires' => ['php' => '8.4', 'wordpress' => '6.6']
     * ]
     */
    public function getMetadata(): array;

    /**
     * Export walker configuration for debugging
     *
     * Returns a complete dump of the walker's current state and
     * configuration for debugging and troubleshooting.
     *
     * @return array<string, mixed> Complete walker state
     * @since 2.0.0
     */
    public function exportState(): array;

    /**
     * Import walker configuration from state
     *
     * Restores walker state from a previously exported configuration.
     * Useful for testing and state management.
     *
     * @param array<string, mixed> $state State data to import
     * @return bool True if import was successful
     * @since 2.0.0
     */
    public function importState(array $state): bool;

    /**
     * Check compatibility with WordPress version
     *
     * Verifies that the walker is compatible with the current
     * WordPress version and environment.
     *
     * @return bool True if compatible
     * @since 2.0.0
     */
    public function checkCompatibility(): bool;

    /**
     * Get recommended settings for optimal performance
     *
     * Returns configuration recommendations based on the current
     * environment and usage patterns.
     *
     * @return array<string, mixed> Recommended settings
     * @since 2.0.0
     */
    public function getRecommendedSettings(): array;

    /**
     * Benchmark walker performance
     *
     * Runs performance tests on the walker to measure rendering
     * speed and resource usage under different conditions.
     *
     * @param array<string, mixed> $testData Test scenarios
     * @return array<string, mixed> Benchmark results
     * @since 2.0.0
     */
    public function benchmark(array $testData = []): array;

    /**
     * Generate walker documentation
     *
     * Returns formatted documentation about the walker's features,
     * usage, and configuration options.
     *
     * @param string $format Documentation format ('html', 'markdown', 'json')
     * @return string Generated documentation
     * @since 2.0.0
     */
    public function generateDocumentation(string $format = 'html'): string;
}