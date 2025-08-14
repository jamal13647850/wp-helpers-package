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

namespace jamal13647850\wphelpers\Navigation\Strategies;

use jamal13647850\wphelpers\Navigation\Base\AbstractWalker;
use jamal13647850\wphelpers\Navigation\Traits\SecurityTrait;
use jamal13647850\wphelpers\Navigation\Traits\IconHandlerTrait;
use jamal13647850\wphelpers\Navigation\Traits\AccessibilityTrait;
use jamal13647850\wphelpers\Navigation\Traits\MenuItemRendererTrait;
use jamal13647850\wphelpers\Navigation\Traits\CacheableTrait;
use jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem;
use jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext;

defined('ABSPATH') || exit();

/**
 * DropdownStrategy - Simple dropdown navigation walker
 *
 * Simplified walker for basic dropdown navigation with minimal Alpine.js
 * dependencies. Ideal for simple menus, admin interfaces, or legacy compatibility.
 *
 * Features:
 * - Traditional ul/li structure
 * - Simple dropdown functionality
 * - Minimal JavaScript dependencies
 * - Lightweight and fast
 * - Easy customization
 *
 * @package jamal13647850\wphelpers\Navigation\Strategies
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
final class DropdownStrategy extends AbstractWalker
{
    use SecurityTrait;
    use IconHandlerTrait;
    use AccessibilityTrait;
    use MenuItemRendererTrait;
    use CacheableTrait;

    /**
     * Get walker-specific default options
     *
     * @return array<string, mixed> Default options for dropdown walker
     * @since 2.0.0
     */
    protected function getDefaultOptions(): array
    {
        return [
            // CSS Classes
            'menu_class' => 'dropdown-menu',
            'menu_item_class' => 'menu-item',
            'submenu_class' => 'sub-menu',
            'link_class' => 'menu-link',
            'submenu_link_class' => 'submenu-link',
            'active_class' => 'current-menu-item',
            'has_children_class' => 'menu-item-has-children',

            // Behavior
            'max_depth' => 2,
            'enable_icons' => false,
            'simple_mode' => true,
            'show_indicators' => false,

            // Alpine.js (minimal usage)
            'enable_alpine' => false,

            // Accessibility
            'enable_aria' => true,
            'enable_keyboard_nav' => false,

            // Performance
            'enable_caching' => true,
            'cache_ttl' => 7200, // 2 hours
        ];
    }

    /**
     * Get unique walker type identifier
     *
     * @return string Walker type identifier
     * @since 2.0.0
     */
    protected function getWalkerType(): string
    {
        return 'dropdown';
    }

    /**
     * Initialize dropdown walker
     *
     * @return void
     * @since 2.0.0
     */
    protected function initializeWalker(): void
    {
        // Minimal configuration for dropdown
        $this->configureAccessibility([
            'enable_aria' => $this->options->getBool('enable_aria'),
            'wcag_level' => 'A', // Basic level for simple dropdowns
        ]);

        $this->configureCache([
            'enable_caching' => $this->options->getBool('enable_caching'),
            'cache_ttl' => $this->options->getInt('cache_ttl'),
        ]);
    }

    /**
     * Start outputting submenu container
     *
     * @param string $output Reference to the output string
     * @param int $depth Current menu depth
     * @param mixed $args Menu arguments
     * @return void
     * @since 2.0.0
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void
    {
        if ($depth >= $this->options->getInt('max_depth', 2)) {
            return;
        }

        $indent = str_repeat("\t", $depth + 1);
        $submenuClass = $this->getSubmenuClass($depth);

        $output .= "\n{$indent}<ul class=\"{$submenuClass}\">\n";
    }

    /**
     * End submenu container output
     *
     * @param string $output Reference to the output string
     * @param int $depth Current menu depth
     * @param mixed $args Menu arguments
     * @return void
     * @since 2.0.0
     */
    public function end_lvl(&$output, $depth = 0, $args = []): void
    {
        if ($depth >= $this->options->getInt('max_depth', 2)) {
            return;
        }

        $indent = str_repeat("\t", $depth + 1);
        $output .= "{$indent}</ul>\n";
    }

    /**
     * Render individual menu item for dropdown
     *
     * @param MenuItem $item Menu item to render
     * @param RenderContext $context Rendering context
     * @return string Rendered menu item HTML
     * @since 2.0.0
     */
    protected function renderMenuItem(MenuItem $item, RenderContext $context): string
    {
        // Use caching for better performance
        if ($this->options->getBool('enable_caching')) {
            return $this->getCachedFragment(
                'dropdown_item_' . $item->getId(),
                fn() => $this->renderDropdownItem($item, $context),
                $context
            );
        }

        return $this->renderDropdownItem($item, $context);
    }

    /**
     * Render dropdown menu item without caching
     *
     * @param MenuItem $item Menu item to render
     * @param RenderContext $context Rendering context
     * @return string Rendered menu item HTML
     * @since 2.0.0
     */
    private function renderDropdownItem(MenuItem $item, RenderContext $context): string
    {
        $depth = $item->getDepth();
        $indent = str_repeat("\t", $depth + 1);

        // Build container
        $containerTag = 'li';
        $containerAttrs = $this->buildDropdownContainerAttributes($item, $context);
        $linkHtml = $this->renderDropdownLink($item, $context);

        return $indent . $this->buildHtmlElement($containerTag, $linkHtml, $containerAttrs) . "\n";
    }

    /**
     * Render dropdown menu link
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Link HTML
     * @since 2.0.0
     */
    private function renderDropdownLink(MenuItem $item, RenderContext $context): string
    {
        $linkAttrs = $this->buildDropdownLinkAttributes($item, $context);
        $linkContent = $this->buildDropdownLinkContent($item, $context);

        return $this->buildHtmlElement('a', $linkContent, $linkAttrs);
    }

    /**
     * Build dropdown container attributes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return array<string, string> Container attributes
     * @since 2.0.0
     */
    private function buildDropdownContainerAttributes(MenuItem $item, RenderContext $context): array
    {
        $attributes = [];

        // Basic attributes
        $attributes['id'] = 'menu-item-' . $item->getId();
        $attributes['class'] = $this->buildDropdownContainerClasses($item, $context);

        // ARIA attributes (if enabled)
        if ($this->options->getBool('enable_aria')) {
            $ariaAttrs = $this->generateItemAriaAttributes($item, $context);
            $attributes = array_merge($attributes, $ariaAttrs);
        }

        return $attributes;
    }

    /**
     * Build dropdown link attributes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return array<string, string> Link attributes
     * @since 2.0.0
     */
    private function buildDropdownLinkAttributes(MenuItem $item, RenderContext $context): array
    {
        $attributes = $item->getLinkAttributes();

        // CSS classes
        $attributes['class'] = $this->buildDropdownLinkClasses($item, $context);

        return $attributes;
    }

    /**
     * Build dropdown link content
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Link content HTML
     * @since 2.0.0
     */
    private function buildDropdownLinkContent(MenuItem $item, RenderContext $context): string
    {
        $content = '';

        // Icon (if enabled)
        if ($this->options->getBool('enable_icons')) {
            $content .= $this->renderIcon($item, ['position' => 'before']);
        }

        // Text content
        $content .= sprintf('<span class="menu-text">%s</span>', $this->escapeHtml($item->getTitle()));

        // Indicator (if enabled and has children)
        if ($item->hasChildren() && $this->options->getBool('show_indicators')) {
            $content .= $this->renderSimpleIndicator();
        }

        return $content;
    }

    /**
     * Build dropdown container CSS classes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string CSS classes
     * @since 2.0.0
     */
    private function buildDropdownContainerClasses(MenuItem $item, RenderContext $context): string
    {
        $classes = [];

        // Base classes
        $classes[] = $this->options->getCssClass('menu_item_class');
        $classes[] = 'menu-item-' . $item->getId();

        // WordPress classes
        $classes = array_merge($classes, $item->getClasses());

        // State classes
        if ($item->hasChildren()) {
            $classes[] = $this->options->getCssClass('has_children_class');
        }

        if ($item->isActive()) {
            $classes[] = $this->options->getCssClass('active_class');
        }

        // Depth classes
        $classes[] = 'menu-depth-' . $item->getDepth();

        return $this->sanitizeCssClass(implode(' ', array_filter($classes)));
    }

    /**
     * Build dropdown link CSS classes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string CSS classes
     * @since 2.0.0
     */
    private function buildDropdownLinkClasses(MenuItem $item, RenderContext $context): string
    {
        $classes = [];

        // Base link class
        if ($item->isTopLevel()) {
            $classes[] = $this->options->getCssClass('link_class');
        } else {
            $classes[] = $this->options->getCssClass('submenu_link_class');
        }

        // State classes
        if ($item->isCurrent()) {
            $classes[] = 'current-link';
        }

        return $this->sanitizeCssClass(implode(' ', array_filter($classes)));
    }

    /**
     * Render simple dropdown indicator
     *
     * @return string Indicator HTML
     * @since 2.0.0
     */
    private function renderSimpleIndicator(): string
    {
        return '<span class="dropdown-indicator" aria-hidden="true"> ▼</span>';
    }

    /**
     * Get submenu CSS class based on depth
     *
     * @param int $depth Menu depth
     * @return string Submenu CSS class
     * @since 2.0.0
     */
    private function getSubmenuClass(int $depth): string
    {
        $baseClass = $this->options->getCssClass('submenu_class');
        return $baseClass . ' depth-' . $depth;
    }

    /**
     * Get walker capabilities
     *
     * @return array<string, mixed> Capabilities
     * @since 2.0.0
     */
    public function getCapabilities(): array
    {
        return [
            'supports_icons' => true,
            'supports_alpine' => false,
            'supports_caching' => true,
            'supports_accessibility' => true,
            'supports_multi_level' => true,
            'supports_mega_menu' => false,
            'supports_hover' => false,
            'supports_keyboard_nav' => false,
            'max_depth' => $this->options->getInt('max_depth', 2),
            'layout_types' => ['vertical'],
            'complexity' => 'simple',
        ];
    }
}