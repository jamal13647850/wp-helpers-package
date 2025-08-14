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
 * OverlayMobileWalker — Multi-level accordion navigation with icons
 * 
 * A custom WordPress navigation walker that creates mobile-friendly accordion menus
 * without traditional <ul>/<li> structure. Uses div/a elements optimized for mobile devices
 * with Alpine.js integration for dynamic behavior.
 * 
 * Features:
 * - Accordion modes: 'classic' (siblings close when one opens) or 'independent' (each item toggles independently)
 * - FontAwesome icon support via menu item attributes or CSS classes
 * - Configurable depth limits and styling classes
 * - ARIA accessibility attributes for screen readers
 * - Mobile-optimized markup structure
 * 
 * Usage Example:
 * ```php
 * $walker = new OverlayMobileWalker([
 *     'accordion_mode' => 'independent',
 *     'max_depth' => 3,
 *     'enable_icons' => true
 * ]);
 * 
 * wp_nav_menu([
 *     'theme_location' => 'mobile_menu',
 *     'walker' => $walker,
 *     'container' => false
 * ]);
 * ```
 * 
 * @package jamal13647850\wphelpers\Navigation
 * @since 1.0.0
 * @author Sayyed Jamal Ghasemi
 */
final class OverlayMobileWalker extends \Walker_Nav_Menu
{
    /**
     * Configuration options for the walker
     * 
     * @var array<string,mixed> Array of configuration settings
     * @since 1.0.0
     */
    private array $options = [
        'mobile_link_class'     => 'mobile-menu-link',      // CSS class for top-level links
        'mobile_submenu_class'  => 'mobile-submenu-link',   // CSS class for child menu links
        'enable_icons'          => true,                    // Whether to display FontAwesome icons
        'caret_svg'             => true,                    // Whether to show dropdown caret icons
        'accordion_mode'        => 'classic',               // 'classic' | 'independent' accordion behavior
        'max_depth'             => 5,                       // Maximum menu depth to render
    ];

    /**
     * Stack tracking parent menu item IDs by depth level
     * 
     * Used to maintain parent-child relationships and generate proper
     * submenu container IDs for accordion functionality.
     * 
     * @var array<int,int> Maps depth level to parent menu item ID
     * @since 1.0.0
     */
    private array $parentStack = [];

    /**
     * Constructor - Initialize walker with custom options
     * 
     * @param array<string,mixed> $options Optional configuration array to override defaults
     * @since 1.0.0
     * 
     * @example
     * ```php
     * $walker = new OverlayMobileWalker([
     *     'accordion_mode' => 'independent',
     *     'enable_icons' => false,
     *     'max_depth' => 2
     * ]);
     * ```
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Start outputting submenu container
     * 
     * Called when WordPress encounters a submenu level. Creates a div container
     * with Alpine.js bindings for accordion animation and proper ARIA attributes.
     * 
     * @param string $output Reference to the output string being built
     * @param int    $depth  Current menu depth (0 = top level)
     * @param mixed  $args   Additional arguments passed from wp_nav_menu()
     * @return void
     * @since 1.0.0
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void
    {
        // Respect maximum depth setting
        if ($depth >= (int) $this->options['max_depth']) {
            return;
        }

        // Get parent ID from stack for this depth level
        $parentId = $this->parentStack[$depth] ?? null;
        if (!$parentId) {
            return;
        }

        // Generate unique submenu container ID and CSS class
        $submenu_id  = 'submenu-' . (int) $parentId;
        $level_class = 'level-' . (int) $depth;

        // Set Alpine.js condition based on accordion mode
        $condition = $this->isClassic()
            ? sprintf("opens[%d] === %d", (int) $depth, (int) $parentId)
            : "open";

        // Output submenu container with Alpine.js styling bindings
        $output .= sprintf(
            '<div id="%s" class="mobile-submenu %s" x-bind:style="%s ? \'max-height: 100vh; opacity: 1;\' : \'max-height: 0; opacity: 0;\'">',
            esc_attr($submenu_id),
            esc_attr($level_class),
            esc_attr($condition)
        );
    }

    /**
     * End submenu container output
     * 
     * Closes the submenu div container and cleans up the parent stack.
     * 
     * @param string $output Reference to the output string being built
     * @param int    $depth  Current menu depth (0 = top level)
     * @param mixed  $args   Additional arguments passed from wp_nav_menu()
     * @return void
     * @since 1.0.0
     */
    public function end_lvl(&$output, $depth = 0, $args = []): void
    {
        $parentId = $this->parentStack[$depth] ?? null;
        if (!$parentId) {
            return;
        }

        // Close both submenu and menu item containers
        $output .= '</div></div>';
        
        // Clean up parent stack for this depth level
        unset($this->parentStack[$depth]);
    }

    /**
     * Start outputting individual menu item
     * 
     * Handles rendering of menu items based on their type:
     * - Top-level links without children (direct anchor tags)
     * - Parent items with children (accordion trigger buttons)
     * - Child items within submenus (submenu links)
     * 
     * @param string $output Reference to the output string being built
     * @param object $item   WordPress menu item object
     * @param int    $depth  Current menu depth (0 = top level)
     * @param mixed  $args   Additional arguments passed from wp_nav_menu()
     * @param int    $id     Current menu item ID
     * @return void
     * @since 1.0.0
     */
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0): void
    {
        // Respect maximum depth setting
        if ($depth > (int) $this->options['max_depth']) {
            return;
        }

        // Ensure item is an object and extract properties
        $item  = (object) $item;
        $title = esc_html(apply_filters('the_title', (string) ($item->title ?? ''), (int) ($item->ID ?? 0)));
        $url   = isset($item->url) ? esc_url((string) $item->url) : '#';

        // Analyze menu item properties
        $classes   = empty($item->classes) ? [] : (array) $item->classes;
        $hasChild  = in_array('menu-item-has-children', $classes, true);
        $isCurrent = !empty($item->current);

        // Generate icon HTML if enabled
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

        // Handle top-level items without children (simple links)
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

        // Handle parent items with children (accordion triggers)
        if ($hasChild) {
            // Track this parent in the stack for submenu generation
            $this->parentStack[$depth] = (int) $item->ID;

            $submenu_id   = 'submenu-' . (int) $item->ID;
            $level_class  = 'level-' . (int) $depth;

            // Initialize Alpine.js data context based on accordion mode
            if ($this->isClassic()) {
                $output .= '<div class="mobile-menu-item">';
            } else {
                $output .= '<div class="mobile-menu-item" x-data="{ open: false }">';
            }

            // Create accordion trigger button
            $output .= '<button type="button" class="mobile-menu-title ' . esc_attr($level_class) . '"';

            // Add Alpine.js click handlers and ARIA attributes
            if ($this->isClassic()) {
                $output .= ' @click="opens[' . (int) $depth . '] = (opens[' . (int) $depth . '] === ' . (int) $item->ID . ' ? null : ' . (int) $item->ID . ')"';
                $output .= ' x-bind:aria-expanded="opens[' . (int) $depth . '] === ' . (int) $item->ID . ' ? \'true\' : \'false\'"';
            } else {
                $output .= ' @click="open = !open"';
                $output .= ' x-bind:aria-expanded="open ? \'true\' : \'false\'"';
            }

            $output .= ' aria-controls="' . esc_attr($submenu_id) . '">';

            // Add icon, title, and caret
            if ($icon_html) {
                $output .= $icon_html;
            }
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
            return;
        }

        // Handle child items within submenus (depth >= 1)
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
     * Check if accordion is in classic mode
     * 
     * Classic mode: Only one submenu can be open at each depth level (siblings close when one opens)
     * Independent mode: Each submenu toggles independently
     * 
     * @return bool True if using classic accordion mode, false for independent mode
     * @since 1.0.0
     */
    private function isClassic(): bool
    {
        return ($this->options['accordion_mode'] ?? 'classic') === 'classic';
    }

    /**
     * Resolve FontAwesome icon class from menu item attributes
     * 
     * Searches for FontAwesome icon classes in two locations:
     * 1. Menu item's attr_title field (takes priority)
     * 2. Menu item's CSS classes array
     * 
     * Automatically adds 'fa' base class if missing and validates the result.
     * 
     * @param string|null $attrTitle Menu item's attr_title field content
     * @param array       $classes   Array of CSS classes assigned to menu item
     * @return string|null Valid FontAwesome class string or null if none found
     * @since 1.0.0
     * 
     * @example
     * ```php
     * // From attr_title: "fa-home" -> "fa fa-home"
     * // From classes: ["menu-item", "fa-user"] -> "fa fa-user"
     * // Invalid input: "javascript:alert()" -> null
     * ```
     */
    private function resolveIconClass(?string $attrTitle, array $classes): ?string
    {
        $candidate = null;

        // First priority: Check attr_title field for FontAwesome classes
        if (is_string($attrTitle) && strpos($attrTitle, 'fa-') !== false) {
            $candidate = trim($attrTitle);
        } else {
            // Second priority: Search CSS classes array for FontAwesome classes
            foreach ($classes as $c) {
                if (is_string($c) && strpos($c, 'fa-') === 0) {
                    $candidate = trim($c);
                    break;
                }
            }
        }

        // Return null if no FontAwesome class found
        if (!$candidate) {
            return null;
        }

        // Check if 'fa' base class is already present
        $hasFaBase = (strpos($candidate, 'fa ') !== false) ||
                     (strpos($candidate, 'fa-') === 0 && strpos($candidate, ' ') !== false);

        // Add 'fa' base class if missing
        if (!$hasFaBase && strpos($candidate, 'fa-') === 0) {
            $candidate = 'fa ' . $candidate;
        }

        // Security validation: Only allow alphanumeric, hyphens, and spaces
        if (!preg_match('/^[a-z0-9\-\s]+$/i', $candidate)) {
            return null;
        }

        return $candidate;
    }
}