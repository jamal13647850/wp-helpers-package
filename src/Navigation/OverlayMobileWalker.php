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

defined('ABSPATH') || exit();

/**
 * OverlayMobileWalker - Advanced Mobile Navigation Walker
 *
 * A sophisticated WordPress navigation walker that creates mobile-optimized
 * navigation menus with multi-level accordion functionality and AlpineJS integration.
 * 
 * This walker generates HTML structure without traditional <ul>/<li> elements,
 * instead using semantic div/button/a elements for better mobile experience.
 *
 * ## HTML Structure Generated:
 * - Parent with children: <div class="mobile-menu-item"> + <button class="mobile-menu-title level-{depth}"> + <div class="mobile-submenu level-{depth}">children</div>
 * - Level 1 items without children: <a class="mobile-menu-link">
 * - Child items (any depth): <a class="mobile-submenu-link">
 *
 * ## Accordion Modes:
 * - **classic**: Only one submenu remains open per level (shared state opens[depth] in root)
 * - **independent**: Each parent has its own internal state (x-data in the same item)
 *
 * ## Icon Support:
 * Icons are extracted from attr_title or CSS classes starting with 'fa-' (Font Awesome)
 *
 * ## Usage Example:
 * ```php
 * $walker = new OverlayMobileWalker([
 *     'accordion_mode' => 'independent',
 *     'enable_icons' => true,
 *     'max_depth' => 3
 * ]);
 * 
 * wp_nav_menu([
 *     'theme_location' => 'mobile-menu',
 *     'walker' => $walker,
 *     'container' => false
 * ]);
 * ```
 *
 * ## AlpineJS Integration:
 * Requires AlpineJS for accordion functionality. The root element should have:
 * ```html
 * <div x-data="{ opens: {} }">
 *     <!-- Menu items generated here -->
 * </div>
 * ```
 *
 * @package    jamal13647850\wphelpers\Navigation
 * @author     Sayyed Jamal Ghasemi <info@jamalghasemi.com>
 * @license    GPL-2.0-or-later
 * @link       https://jamalghasemi.com
 * @since      1.0.0
 */
final class OverlayMobileWalker extends \Walker_Nav_Menu
{
    /**
     * Walker configuration options
     *
     * @var array<string,mixed> Configuration array containing:
     *                          - mobile_link_class: CSS class for top-level links
     *                          - mobile_submenu_class: CSS class for submenu links  
     *                          - enable_icons: Whether to parse and display icons
     *                          - caret_svg: Whether to show expand/collapse SVG icons
     *                          - accordion_mode: 'classic' or 'independent' accordion behavior
     *                          - max_depth: Maximum nesting depth for DOM safety
     */
    private array $options = [
        'mobile_link_class'     => 'mobile-menu-link',
        'mobile_submenu_class'  => 'mobile-submenu-link',
        'enable_icons'          => true,
        'caret_svg'             => true,
        'accordion_mode'        => 'classic',
        'max_depth'             => 5,
    ];

    /**
     * Stack tracking parent menu item IDs by depth level
     *
     * Used to maintain parent-child relationships during walker traversal.
     * Key = depth level, Value = parent menu item ID
     *
     * @var array<int,int> Parent ID per depth level
     */
    private array $parentStack = [];

    /**
     * Initialize the walker with custom options
     *
     * Merges provided options with defaults to configure walker behavior.
     * All options are optional and will fall back to sensible defaults.
     *
     * @param array<string,mixed> $options {
     *     Optional. Walker configuration options.
     *     
     *     @type string $mobile_link_class     CSS class for top-level menu links. Default 'mobile-menu-link'.
     *     @type string $mobile_submenu_class  CSS class for submenu links. Default 'mobile-submenu-link'.
     *     @type bool   $enable_icons          Whether to parse and display Font Awesome icons. Default true.
     *     @type bool   $caret_svg            Whether to show expand/collapse SVG arrows. Default true.
     *     @type string $accordion_mode        Accordion behavior: 'classic' or 'independent'. Default 'classic'.
     *     @type int    $max_depth            Maximum menu nesting depth allowed. Default 5.
     * }
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // Create walker with custom settings
     * $walker = new OverlayMobileWalker([
     *     'accordion_mode' => 'independent',
     *     'max_depth' => 3,
     *     'enable_icons' => false
     * ]);
     * ```
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Start the list before the elements are added (submenu container opening)
     *
     * Opens the submenu container for child elements at any depth level.
     * Uses AlpineJS for show/hide animation with conditional styling.
     *
     * The container behavior differs based on accordion mode:
     * - Classic mode: Uses shared state opens[depth] to control visibility
     * - Independent mode: Uses local 'open' state from parent item
     *
     * @param string $output Used to append additional content (passed by reference)
     * @param int    $depth  Depth of menu item. Used for max depth limit and level styling
     * @param mixed  $args   An object of wp_nav_menu() arguments (unused)
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @see   end_lvl() For the corresponding container closing
     *
     * @example
     * Generated HTML (classic mode, depth=1, parent ID=123):
     * ```html
     * <div id="submenu-123" class="mobile-submenu level-1" 
     *      x-bind:style="opens[1] === 123 ? 'max-height: 100vh; opacity: 1;' : 'max-height: 0; opacity: 0;'">
     * ```
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void
    {
        // Respect maximum depth limit for DOM performance and UX
        if ($depth >= (int) $this->options['max_depth']) {
            return;
        }

        // Get the parent ID for this depth level
        $parentId = $this->parentStack[$depth] ?? null;
        if (!$parentId) {
            return;
        }

        $submenu_id  = 'submenu-' . (int) $parentId;
        $level_class = 'level-' . (int) $depth;

        // Determine visibility condition based on accordion mode
        if ($this->isClassic()) {
            // Classic mode: Compare with shared opens[depth] state
            $condition = sprintf("opens[%d] === %d", (int) $depth, (int) $parentId);
        } else {
            // Independent mode: Use local state from parent item
            $condition = "open";
        }

        // Generate submenu container with AlpineJS conditional styling
        $output .= sprintf(
            '<div id="%s" class="mobile-submenu %s" x-bind:style="%s ? \'max-height: 100vh; opacity: 1;\' : \'max-height: 0; opacity: 0;\'">',
            esc_attr($submenu_id),
            esc_attr($level_class),
            esc_attr($condition)
        );
    }

    /**
     * End the list after the elements are added (submenu container closing)
     *
     * Closes both the submenu container and the parent wrapper div.
     * Cleans up the parent stack for this depth level.
     *
     * @param string $output Used to append additional content (passed by reference)
     * @param int    $depth  Depth of menu item. Used for parent stack cleanup
     * @param mixed  $args   An object of wp_nav_menu() arguments (unused)
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @see   start_lvl() For the corresponding container opening
     */
    public function end_lvl(&$output, $depth = 0, $args = []): void
    {
        $parentId = $this->parentStack[$depth] ?? null;
        if (!$parentId) {
            return;
        }

        // Close submenu container and parent wrapper
        $output .= '</div></div>';

        // Clean up parent stack for this depth level
        unset($this->parentStack[$depth]);
    }

    /**
     * Start the element output (individual menu item rendering)
     *
     * Generates HTML for individual menu items with different structures based on:
     * - Depth level (0 = top level, 1+ = submenu)
     * - Whether item has children (accordion trigger vs simple link)
     * - Current page status (active state styling)
     * - Icon presence (Font Awesome integration)
     *
     * ## Generated Structures:
     * 1. **Top-level without children**: Simple anchor link
     * 2. **Parent with children**: Button trigger + submenu container setup
     * 3. **Child without children**: Submenu-styled anchor link
     *
     * @param string $output Used to append additional content (passed by reference)
     * @param object $item   The menu item data object containing:
     *                       - ID: Menu item database ID
     *                       - title: Display text for the menu item  
     *                       - url: Target URL for the menu item
     *                       - classes: Array of CSS classes assigned to item
     *                       - attr_title: Optional icon class specification
     *                       - current: Boolean indicating if item represents current page
     * @param int    $depth  Depth of menu item (0 = top level, 1+ = nested)
     * @param mixed  $args   An object of wp_nav_menu() arguments (unused)
     * @param int    $id     Current menu item ID (unused, using $item->ID instead)
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @see   resolveIconClass() For icon parsing logic
     * @see   isClassic()       For accordion mode detection
     *
     * @example
     * Top-level link without children:
     * ```html
     * <a class="mobile-menu-link is-active" href="/page" aria-current="page">
     *     <i class="fa fa-home" aria-hidden="true"></i>
     *     <span>خانه</span>
     * </a>
     * ```
     *
     * Parent with children (classic mode):
     * ```html
     * <div class="mobile-menu-item">
     *     <button type="button" class="mobile-menu-title level-0" 
     *             @click="opens[0] = (opens[0] === 123 ? null : 123)"
     *             x-bind:aria-expanded="opens[0] === 123 ? 'true' : 'false'"
     *             aria-controls="submenu-123">
     *         <i class="fa fa-cog" aria-hidden="true"></i>
     *         <span>تنظیمات</span>
     *         <svg class="mobile-menu-caret">...</svg>
     *     </button>
     * ```
     */
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0): void
    {
        // Respect maximum depth limit
        if ($depth > (int) $this->options['max_depth']) {
            return;
        }

        // Normalize item data
        $item  = (object) $item;
        $title = esc_html(apply_filters('the_title', (string) ($item->title ?? ''), (int) ($item->ID ?? 0)));
        $url   = isset($item->url) ? esc_url((string) $item->url) : '#';

        // Parse item properties
        $classes   = empty($item->classes) ? [] : (array) $item->classes;
        $hasChild  = in_array('menu-item-has-children', $classes, true);
        $isCurrent = !empty($item->current);

        // Resolve icon from attr_title or CSS classes
        $icon_html = '';
        if (!empty($this->options['enable_icons'])) {
            $icon_class = $this->resolveIconClass(
                isset($item->attr_title) ? (string) $item->attr_title : null,
                $classes
            );
            if ($icon_class) {
                $icon_html = '<i class="' . esc_attr($icon_class) . '" aria-hidden="true"></i>';
            }
        }

        // Case 1: Top-level item without children - simple link
        if ($depth === 0 && !$hasChild) {
            $output .= sprintf(
                '<a class="%s%s" href="%s"%s>%s<span>%s</span></a>',
                esc_attr((string) $this->options['mobile_link_class']),
                $isCurrent ? ' is-active' : '',
                $url,
                $isCurrent ? ' aria-current="page"' : '',
                $icon_html,
                $title
            );
            return;
        }

        // Case 2: Parent with children (any depth) - accordion trigger
        if ($hasChild) {
            // Track this parent for submenu generation
            $this->parentStack[$depth] = (int) $item->ID;

            $submenu_id   = 'submenu-' . (int) $item->ID;
            $level_class  = 'level-' . (int) $depth;

            // Create parent wrapper with appropriate AlpineJS data
            if ($this->isClassic()) {
                // Classic mode: No local x-data, managed by root state opens[depth]
                $output .= '<div class="mobile-menu-item">';
            } else {
                // Independent mode: Local state for each item
                $output .= '<div class="mobile-menu-item" x-data="{ open: false }">';
            }

            // Generate accordion trigger button
            $output .= '<button type="button" class="mobile-menu-title ' . esc_attr($level_class) . '"';

            // Add AlpineJS click handlers based on accordion mode
            if ($this->isClassic()) {
                $output .= ' @click="opens[' . (int) $depth . '] = (opens[' . (int) $depth . '] === ' . (int) $item->ID . ' ? null : ' . (int) $item->ID . ')"';
                $output .= ' x-bind:aria-expanded="opens[' . (int) $depth . '] === ' . (int) $item->ID . ' ? \'true\' : \'false\'"';
            } else {
                $output .= ' @click="open = !open"';
                $output .= ' x-bind:aria-expanded="open ? \'true\' : \'false\'"';
            }

            $output .= ' aria-controls="' . esc_attr($submenu_id) . '">';

            // Add icon if available
            if ($icon_html) {
                $output .= $icon_html;
            }
            
            // Add title text
            $output .= '<span>' . $title . '</span>';

            // Add animated caret SVG if enabled
            if (!empty($this->options['caret_svg'])) {
                $caretTransform = $this->isClassic()
                    ? "opens[$depth] === " . (int) $item->ID
                    : "open";
                    
                $output .= '<svg class="mobile-menu-caret" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" ';
                $output .= 'x-bind:style="{ transform: (' . esc_attr($caretTransform) . ') ? \'rotate(180deg)\' : \'rotate(0deg)\' }">';
                $output .= '<path d="M7 10l5 5 5-5H7z"/></svg>';
            }

            $output .= '</button>';

            // Submenu container will be opened in start_lvl()
            return;
        }

        // Case 3: Child item (depth >= 1) without children - submenu link
        $output .= sprintf(
            '<a class="%s%s" href="%s"%s>%s<span>%s</span></a>',
            esc_attr((string) $this->options['mobile_submenu_class']),
            $isCurrent ? ' is-active' : '',
            $url,
            $isCurrent ? ' aria-current="page"' : '',
            $icon_html,
            $title
        );
    }

    /**
     * End the element output (individual menu item cleanup)
     *
     * Called after each menu item is processed. Parent wrapper closing
     * is handled in end_lvl(), so no specific output is needed here.
     *
     * @param string $output Used to append additional content (passed by reference, unused)
     * @param object $item   The menu item data object (unused)
     * @param int    $depth  Depth of menu item (unused)
     * @param mixed  $args   An object of wp_nav_menu() arguments (unused)
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function end_el(&$output, $item, $depth = 0, $args = []): void
    {
        // Parent wrapper is closed in end_lvl(), no specific output needed here
    }

    /**
     * Check if walker is in classic accordion mode
     *
     * Classic mode uses shared state management where only one submenu
     * can be open per depth level. Independent mode allows each parent
     * to manage its own open/closed state.
     *
     * @return bool True if using classic accordion mode, false for independent mode
     *
     * @since 1.0.0
     *
     * @see   __construct() For accordion_mode option configuration
     */
    private function isClassic(): bool
    {
        return ($this->options['accordion_mode'] ?? 'classic') === 'classic';
    }

    /**
     * Resolve Font Awesome icon class from menu item data
     *
     * Extracts and normalizes Font Awesome icon classes from either:
     * 1. Menu item's attr_title field (highest priority)
     * 2. CSS classes starting with 'fa-' prefix
     *
     * Supports multiple Font Awesome formats:
     * - "fa fa-car" (classic format)
     * - "fa-solid fa-car" (Font Awesome 5+ format)  
     * - "fa-car" (icon name only, will be prefixed with 'fa')
     *
     * @param string|null $attrTitle Menu item's attr_title value (may contain icon class)
     * @param array       $classes   Array of CSS classes assigned to menu item
     *
     * @return string|null Normalized Font Awesome class string, or null if no valid icon found
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // Input: attr_title = "fa-solid fa-home"
     * // Output: "fa-solid fa-home"
     *
     * // Input: classes = ["menu-item", "fa-car", "custom-class"]
     * // Output: "fa fa-car"
     *
     * // Input: attr_title = "fa-user"  
     * // Output: "fa fa-user"
     * ```
     */
    private function resolveIconClass(?string $attrTitle, array $classes): ?string
    {
        // Search for Font Awesome patterns: "fa fa-car", "fa-solid fa-car", "fa-car"
        $candidate = null;

        // Priority 1: Check attr_title field
        if (is_string($attrTitle) && strpos($attrTitle, 'fa-') !== false) {
            $candidate = trim($attrTitle);
        } else {
            // Priority 2: Check CSS classes for fa- prefix
            foreach ($classes as $c) {
                if (is_string($c) && strpos($c, 'fa-') === 0) {
                    $candidate = trim($c);
                    break;
                }
            }
        }

        if (!$candidate) {
            return null;
        }

        // Normalize: If only fa-car exists, add 'fa' base class
        $hasFaBase = (strpos($candidate, 'fa ') !== false) || 
                     (strpos($candidate, 'fa-') === 0 && strpos($candidate, ' ') !== false);
                     
        if (!$hasFaBase && strpos($candidate, 'fa-') === 0) {
            $candidate = 'fa ' . $candidate;
        }

        // Security filter: Only allow letters, numbers, hyphens, and spaces
        if (!preg_match('/^[a-z0-9\-\s]+$/i', $candidate)) {
            return null;
        }

        return $candidate;
    }
}