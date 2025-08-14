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
 * MenuItem Value Object
 *
 * Immutable data container for menu item information with built-in validation
 * and security measures. Provides a type-safe interface for menu item data
 * while abstracting away WordPress's stdClass structure.
 *
 * Features:
 * - Immutable design prevents accidental data modification
 * - Built-in security (sanitization, validation, XSS prevention)
 * - Type safety with proper PHP typing
 * - WordPress integration helpers
 * - Icon detection and management
 * - State detection (current, active, etc.)
 *
 * @package jamal13647850\wphelpers\Navigation\ValueObjects
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
final class MenuItem
{
    /**
     * Unique menu item ID
     * @var int
     */
    private int $id;

    /**
     * Menu item title/label
     * @var string
     */
    private string $title;

    /**
     * Menu item URL
     * @var string
     */
    private string $url;

    /**
     * CSS classes assigned to the menu item
     * @var array<string>
     */
    private array $classes;

    /**
     * Target attribute for the link (_blank, _self, etc.)
     * @var string
     */
    private string $target;

    /**
     * Relationship attribute (rel) for the link
     * @var string
     */
    private string $relationship;

    /**
     * Title attribute for the link (tooltip)
     * @var string
     */
    private string $linkTitle;

    /**
     * Menu item description
     * @var string
     */
    private string $description;

    /**
     * Parent menu item ID (0 for top-level items)
     * @var int
     */
    private int $parentId;

    /**
     * Menu item depth level (0 = top level)
     * @var int
     */
    private int $depth;

    /**
     * Whether this item has child items
     * @var bool
     */
    private bool $hasChildren;

    /**
     * Whether this item is currently active/selected
     * @var bool
     */
    private bool $isCurrent;

    /**
     * Whether this item is an ancestor of the current page
     * @var bool
     */
    private bool $isCurrentAncestor;

    /**
     * Whether this item is a parent of the current page
     * @var bool
     */
    private bool $isCurrentParent;

    /**
     * FontAwesome icon class (if any)
     * @var string|null
     */
    private ?string $iconClass;

    /**
     * Original WordPress menu item object for advanced usage
     * @var object|null
     */
    private ?object $originalItem;

    /**
     * MenuItem constructor
     *
     * Creates a new MenuItem instance with validated and sanitized data.
     * Private constructor enforces use of factory methods for creation.
     *
     * @param array<string, mixed> $data Validated menu item data
     */
    private function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->title = (string) $data['title'];
        $this->url = (string) $data['url'];
        $this->classes = (array) $data['classes'];
        $this->target = (string) $data['target'];
        $this->relationship = (string) $data['relationship'];
        $this->linkTitle = (string) $data['link_title'];
        $this->description = (string) $data['description'];
        $this->parentId = (int) $data['parent_id'];
        $this->depth = (int) $data['depth'];
        $this->hasChildren = (bool) $data['has_children'];
        $this->isCurrent = (bool) $data['is_current'];
        $this->isCurrentAncestor = (bool) $data['is_current_ancestor'];
        $this->isCurrentParent = (bool) $data['is_current_parent'];
        $this->iconClass = $data['icon_class'] ?? null;
        $this->originalItem = $data['original_item'] ?? null;
    }

    /**
     * Create MenuItem from WordPress menu item object
     *
     * Factory method that converts WordPress's stdClass menu item object
     * into our type-safe MenuItem value object with security measures applied.
     *
     * @param object $wpItem WordPress menu item object
     * @param int $depth Current menu depth
     * @param array<string, mixed> $args Menu rendering arguments
     * @return self New MenuItem instance
     * @since 2.0.0
     */
    public static function fromWordPressItem($wpItem, int $depth, array $args): self
    {
        // Sanitize and validate all input data
        $title = apply_filters('the_title', $wpItem->title ?? '', $wpItem->ID ?? 0);
        $title = wp_strip_all_tags($title);
        $title = esc_html($title);

        $url = !empty($wpItem->url) ? esc_url($wpItem->url) : '#';
        
        $classes = !empty($wpItem->classes) ? (array) $wpItem->classes : [];
        $classes = array_map('sanitize_html_class', $classes);
        $classes = array_filter($classes); // Remove empty classes

        $target = !empty($wpItem->target) ? esc_attr($wpItem->target) : '';
        $relationship = !empty($wpItem->xfn) ? esc_attr($wpItem->xfn) : '';
        $linkTitle = !empty($wpItem->attr_title) ? esc_attr($wpItem->attr_title) : '';
        $description = !empty($wpItem->description) ? wp_kses_post($wpItem->description) : '';

        // Determine state flags
        $hasChildren = in_array('menu-item-has-children', $classes, true);
        $isCurrent = !empty($wpItem->current) || in_array('current-menu-item', $classes, true);
        $isCurrentAncestor = !empty($wpItem->current_item_ancestor) || in_array('current-menu-ancestor', $classes, true);
        $isCurrentParent = !empty($wpItem->current_item_parent) || in_array('current-menu-parent', $classes, true);

        // Extract icon class from title attribute or classes
        $iconClass = self::extractIconClass($linkTitle, $classes);

        return new self([
            'id' => (int) ($wpItem->ID ?? 0),
            'title' => $title,
            'url' => $url,
            'classes' => $classes,
            'target' => $target,
            'relationship' => $relationship,
            'link_title' => $linkTitle,
            'description' => $description,
            'parent_id' => (int) ($wpItem->menu_item_parent ?? 0),
            'depth' => $depth,
            'has_children' => $hasChildren,
            'is_current' => $isCurrent,
            'is_current_ancestor' => $isCurrentAncestor,
            'is_current_parent' => $isCurrentParent,
            'icon_class' => $iconClass,
            'original_item' => $wpItem,
        ]);
    }

    /**
     * Extract FontAwesome icon class from title attribute or CSS classes
     *
     * Searches for FontAwesome icon classes in the menu item's title attribute
     * or CSS classes and returns the properly formatted icon class.
     *
     * @param string $linkTitle Link title attribute
     * @param array<string> $classes CSS classes
     * @return string|null FontAwesome icon class or null if none found
     * @since 2.0.0
     */
    private static function extractIconClass(string $linkTitle, array $classes): ?string
    {
        // Check title attribute for icon class
        if (!empty($linkTitle) && strpos($linkTitle, 'fa-') !== false) {
            $iconClass = trim($linkTitle);
            // Ensure 'fa' prefix is present
            return strpos($iconClass, 'fa ') !== false ? $iconClass : 'fa ' . $iconClass;
        }

        // Check CSS classes for FontAwesome icons
        foreach ($classes as $class) {
            if (strpos($class, 'fa-') === 0) {
                return 'fa ' . trim($class);
            }
        }

        return null;
    }

    /**
     * Create MenuItem for testing purposes
     *
     * Factory method for creating MenuItem instances in tests with minimal data.
     *
     * @param array<string, mixed> $data Optional data to override defaults
     * @return self New MenuItem instance for testing
     * @since 2.0.0
     */
    public static function createForTesting(array $data = []): self
    {
        $defaults = [
            'id' => 1,
            'title' => 'Test Item',
            'url' => '#',
            'classes' => [],
            'target' => '',
            'relationship' => '',
            'link_title' => '',
            'description' => '',
            'parent_id' => 0,
            'depth' => 0,
            'has_children' => false,
            'is_current' => false,
            'is_current_ancestor' => false,
            'is_current_parent' => false,
            'icon_class' => null,
            'original_item' => null,
        ];

        return new self(array_merge($defaults, $data));
    }

    // ===== GETTERS =====

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getRelationship(): string
    {
        return $this->relationship;
    }

    public function getLinkTitle(): string
    {
        return $this->linkTitle;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function hasChildren(): bool
    {
        return $this->hasChildren;
    }

    public function isCurrent(): bool
    {
        return $this->isCurrent;
    }

    public function isCurrentAncestor(): bool
    {
        return $this->isCurrentAncestor;
    }

    public function isCurrentParent(): bool
    {
        return $this->isCurrentParent;
    }

    public function getIconClass(): ?string
    {
        return $this->iconClass;
    }

    public function getOriginalItem(): ?object
    {
        return $this->originalItem;
    }

    // ===== HELPER METHODS =====

    /**
     * Check if menu item has a specific CSS class
     *
     * @param string $className CSS class to check for
     * @return bool True if class exists
     * @since 2.0.0
     */
    public function hasClass(string $className): bool
    {
        return in_array($className, $this->classes, true);
    }

    /**
     * Get CSS classes as space-separated string
     *
     * @return string Space-separated CSS classes
     * @since 2.0.0
     */
    public function getClassString(): string
    {
        return implode(' ', $this->classes);
    }

    /**
     * Check if this is a top-level menu item
     *
     * @return bool True if top-level (depth 0)
     * @since 2.0.0
     */
    public function isTopLevel(): bool
    {
        return $this->depth === 0;
    }

    /**
     * Check if this item should open in a new window/tab
     *
     * @return bool True if target is _blank
     * @since 2.0.0
     */
    public function opensInNewWindow(): bool
    {
        return $this->target === '_blank';
    }

    /**
     * Check if this item has an icon
     *
     * @return bool True if icon class is set
     * @since 2.0.0
     */
    public function hasIcon(): bool
    {
        return $this->iconClass !== null;
    }

    /**
     * Check if this item is in an active state (current, ancestor, or parent)
     *
     * @return bool True if in any active state
     * @since 2.0.0
     */
    public function isActive(): bool
    {
        return $this->isCurrent || $this->isCurrentAncestor || $this->isCurrentParent;
    }

    /**
     * Get link attributes as associative array
     *
     * Returns properly escaped attributes ready for HTML output.
     *
     * @return array<string, string> Link attributes
     * @since 2.0.0
     */
    public function getLinkAttributes(): array
    {
        $attributes = [
            'href' => $this->url,
        ];

        if (!empty($this->target)) {
            $attributes['target'] = $this->target;
        }

        if (!empty($this->relationship)) {
            $attributes['rel'] = $this->relationship;
        }

        if (!empty($this->linkTitle)) {
            $attributes['title'] = $this->linkTitle;
        }

        if ($this->isCurrent) {
            $attributes['aria-current'] = 'page';
        }

        return $attributes;
    }

    /**
     * Get link attributes as HTML string
     *
     * @return string HTML attributes string
     * @since 2.0.0
     */
    public function getLinkAttributesString(): string
    {
        $attributes = $this->getLinkAttributes();
        $output = '';

        foreach ($attributes as $name => $value) {
            $output .= sprintf(' %s="%s"', esc_attr($name), esc_attr($value));
        }

        return $output;
    }

    /**
     * Convert to array representation
     *
     * Useful for debugging, serialization, or when working with templating engines.
     *
     * @return array<string, mixed> Array representation of the menu item
     * @since 2.0.0
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'classes' => $this->classes,
            'target' => $this->target,
            'relationship' => $this->relationship,
            'link_title' => $this->linkTitle,
            'description' => $this->description,
            'parent_id' => $this->parentId,
            'depth' => $this->depth,
            'has_children' => $this->hasChildren,
            'is_current' => $this->isCurrent,
            'is_current_ancestor' => $this->isCurrentAncestor,
            'is_current_parent' => $this->isCurrentParent,
            'icon_class' => $this->iconClass,
            'is_active' => $this->isActive(),
            'has_icon' => $this->hasIcon(),
            'is_top_level' => $this->isTopLevel(),
            'opens_in_new_window' => $this->opensInNewWindow(),
        ];
    }
}