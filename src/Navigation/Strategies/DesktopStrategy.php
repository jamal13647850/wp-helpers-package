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
use jamal13647850\wphelpers\Navigation\Traits\AlpineStateTrait;
use jamal13647850\wphelpers\Navigation\Traits\AccessibilityTrait;
use jamal13647850\wphelpers\Navigation\Traits\MenuItemRendererTrait;
use jamal13647850\wphelpers\Navigation\Traits\CacheableTrait;
use jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem;
use jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext;

defined('ABSPATH') || exit();

/**
 * DesktopStrategy - Desktop navigation walker with hover dropdowns
 *
 * Specialized walker for desktop navigation featuring horizontal layout,
 * hover-based dropdown menus, and mega menu support. Optimized for mouse
 * interaction and larger screen layouts.
 *
 * Features:
 * - Horizontal menu bar layout
 * - Hover-based dropdown activation
 * - Mega menu support with columns
 * - Keyboard navigation accessibility
 * - Alpine.js state management
 * - Performance optimizations
 *
 * @package jamal13647850\wphelpers\Navigation\Strategies
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
final class DesktopStrategy extends AbstractWalker
{
    use SecurityTrait;
    use IconHandlerTrait;
    use AlpineStateTrait;
    use AccessibilityTrait;
    use MenuItemRendererTrait;
    use CacheableTrait;

    /**
     * Desktop-specific configuration
     * @var array<string, mixed>
     */
    private array $desktopConfig = [
        'layout' => 'horizontal', // 'horizontal', 'vertical'
        'dropdown_behavior' => 'hover', // 'hover', 'click'
        'mega_menu_enabled' => false,
        'mega_menu_columns' => 3,
        'dropdown_animation' => 'fade', // 'fade', 'slide', 'none'
        'hover_delay' => 200, // milliseconds
        'dropdown_width' => 'auto', // 'auto', 'full', 'custom'
        'sticky_enabled' => false,
    ];

    /**
     * Mega menu items buffer for complex rendering
     * @var array<int, array>
     */
    private array $megaMenuBuffer = [];

    /**
     * Current mega menu parent ID
     * @var int|null
     */
    private ?int $currentMegaParent = null;

    /**
     * Get walker-specific default options
     *
     * @return array<string, mixed> Default options for desktop walker
     * @since 2.0.0
     */
    protected function getDefaultOptions(): array
    {
        return [
            // CSS Classes
            'menu_class' => 'desktop-menu horizontal-menu',
            'menu_item_class' => 'menu-item',
            'top_level_class' => 'top-level-item',
            'dropdown_class' => 'dropdown-menu',
            'mega_menu_class' => 'mega-menu',
            'link_class' => 'menu-link',
            'submenu_link_class' => 'submenu-link',
            'active_class' => 'active current-menu-item',
            'has_children_class' => 'has-dropdown',

            // Behavior
            'max_depth' => 3,
            'enable_icons' => true,
            'enable_mega_menu' => false,
            'mega_menu_columns' => 3,
            'dropdown_indicator' => true,
            'enable_hover' => true,
            'hover_delay' => 200,

            // Alpine.js
            'enable_alpine' => true,
            'alpine_scope' => 'desktopMenu',

            // Accessibility
            'enable_aria' => true,
            'enable_keyboard_nav' => true,
            'focus_indicators' => true,

            // Performance
            'enable_caching' => true,
            'cache_ttl' => 3600,
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
        return 'desktop';
    }

    /**
     * Initialize desktop walker
     *
     * @return void
     * @since 2.0.0
     */
    protected function initializeWalker(): void
    {
        // Configure desktop-specific settings
        $this->configureDesktopSettings();

        // Configure Alpine.js for desktop interaction
        $this->configureAlpine([
            'enable_alpine' => $this->options->getBool('enable_alpine'),
            'accordion_mode' => 'none', // Desktop doesn't use accordion
        ]);

        // Configure accessibility for desktop navigation
        $this->configureAccessibility([
            'enable_aria' => $this->options->getBool('enable_aria'),
            'enable_keyboard_nav' => $this->options->getBool('enable_keyboard_nav'),
        ]);

        // Configure caching
        $this->configureCache([
            'enable_caching' => $this->options->getBool('enable_caching'),
            'cache_ttl' => $this->options->getInt('cache_ttl'),
        ]);
    }

    /**
     * Start outputting submenu container for desktop
     *
     * @param string $output Reference to the output string
     * @param int $depth Current menu depth
     * @param mixed $args Menu arguments
     * @return void
     * @since 2.0.0
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void
    {
        if ($depth >= $this->options->getInt('max_depth', 3)) {
            return;
        }

        $indent = str_repeat("\t", $depth + 1);

        // Determine submenu type
        $submenuClass = $this->getSubmenuClass($depth);
        $submenuId = $this->generateSubmenuId($depth);

        // Alpine.js bindings for dropdown behavior
        $alpineBindings = $this->generateDropdownBindings();

        // Accessibility attributes
        $ariaAttributes = $this->generateSubmenuAriaAttributes(
            $this->context->getParent($depth - 1),
            $this->context
        );

        $output .= "\n{$indent}<ul class=\"{$submenuClass}\" id=\"{$submenuId}\" {$alpineBindings} {$this->buildAttributeString($ariaAttributes)}>\n";
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
        if ($depth >= $this->options->getInt('max_depth', 3)) {
            return;
        }

        $indent = str_repeat("\t", $depth + 1);
        $output .= "{$indent}</ul>\n";
    }

    /**
     * Render individual menu item for desktop
     *
     * @param MenuItem $item Menu item to render
     * @param RenderContext $context Rendering context
     * @return string Rendered menu item HTML
     * @since 2.0.0
     */
    protected function renderMenuItem(MenuItem $item, RenderContext $context): string
    {
        // Use caching for complex items
        if ($this->options->getBool('enable_caching')) {
            return $this->getCachedFragment(
                'desktop_item_' . $item->getId(),
                fn() => $this->renderDesktopItem($item, $context),
                $context
            );
        }

        return $this->renderDesktopItem($item, $context);
    }

    /**
     * Render desktop menu item without caching
     *
     * @param MenuItem $item Menu item to render
     * @param RenderContext $context Rendering context
     * @return string Rendered menu item HTML
     * @since 2.0.0
     */
    private function renderDesktopItem(MenuItem $item, RenderContext $context): string
    {
        $depth = $item->getDepth();
        $indent = str_repeat("\t", $depth + 1);

        // Handle mega menu items
        if ($this->shouldUseMegaMenu($item, $context)) {
            return $this->renderMegaMenuItem($item, $context);
        }

        // Build container
        $containerTag = 'li';
        $containerAttrs = $this->buildDesktopContainerAttributes($item, $context);
        $linkHtml = $this->renderDesktopLink($item, $context);

        return $indent . $this->buildHtmlElement($containerTag, $linkHtml, $containerAttrs) . "\n";
    }

    /**
     * Render desktop menu link
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Link HTML
     * @since 2.0.0
     */
    private function renderDesktopLink(MenuItem $item, RenderContext $context): string
    {
        $linkAttrs = $this->buildDesktopLinkAttributes($item, $context);
        $linkContent = $this->buildDesktopLinkContent($item, $context);

        return $this->buildHtmlElement('a', $linkContent, $linkAttrs);
    }

    /**
     * Build desktop-specific container attributes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return array<string, string> Container attributes
     * @since 2.0.0
     */
    private function buildDesktopContainerAttributes(MenuItem $item, RenderContext $context): array
    {
        $attributes = [];

        // Basic attributes
        $attributes['id'] = 'menu-item-' . $item->getId();
        $attributes['class'] = $this->buildDesktopContainerClasses($item, $context);

        // ARIA attributes
        $ariaAttrs = $this->generateItemAriaAttributes($item, $context);
        $attributes = array_merge($attributes, $ariaAttrs);

        // Alpine.js hover handlers for dropdowns
        if ($item->hasChildren() && $this->options->getBool('enable_hover')) {
            $hoverBindings = $this->generateHoverBindings($item);
            $attributes = array_merge($attributes, $hoverBindings);
        }

        // Keyboard navigation
        $keyboardAttrs = $this->generateKeyboardAttributes($item, 'desktop');
        $attributes = array_merge($attributes, $keyboardAttrs);

        return $attributes;
    }

    /**
     * Build desktop-specific link attributes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return array<string, string> Link attributes
     * @since 2.0.0
     */
    private function buildDesktopLinkAttributes(MenuItem $item, RenderContext $context): array
    {
        $attributes = $item->getLinkAttributes();

        // CSS classes
        $attributes['class'] = $this->buildDesktopLinkClasses($item, $context);

        // Focus attributes
        $focusAttrs = $this->generateFocusAttributes($item);
        $attributes = array_merge($attributes, $focusAttrs);

        // Click handlers for dropdowns
        if ($item->hasChildren() && $this->desktopConfig['dropdown_behavior'] === 'click') {
            $clickHandler = $this->generateClickHandler('toggleDropdown', [$item->getId()]);
            $attributes['@click'] = $clickHandler;
        }

        return $attributes;
    }

    /**
     * Build desktop link content
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Link content HTML
     * @since 2.0.0
     */
    private function buildDesktopLinkContent(MenuItem $item, RenderContext $context): string
    {
        $content = '';

        // Icon (if enabled)
        if ($this->options->getBool('enable_icons')) {
            $content .= $this->renderIcon($item, ['position' => 'before']);
        }

        // Text content
        $content .= sprintf('<span class="menu-text">%s</span>', $this->escapeHtml($item->getTitle()));

        // Dropdown indicator
        if ($item->hasChildren() && $this->options->getBool('dropdown_indicator')) {
            $content .= $this->renderDropdownIndicator($item, $context);
        }

        return $content;
    }

    /**
     * Build desktop container CSS classes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string CSS classes
     * @since 2.0.0
     */
    private function buildDesktopContainerClasses(MenuItem $item, RenderContext $context): string
    {
        $classes = [];

        // Base classes
        $classes[] = $this->options->getCssClass('menu_item_class');
        $classes[] = 'menu-item-' . $item->getId();

        // WordPress classes
        $classes = array_merge($classes, $item->getClasses());

        // Depth classes
        if ($item->isTopLevel()) {
            $classes[] = $this->options->getCssClass('top_level_class');
        }

        // State classes
        if ($item->hasChildren()) {
            $classes[] = $this->options->getCssClass('has_children_class');
        }

        if ($item->isActive()) {
            $classes[] = $this->options->getCssClass('active_class');
        }

        // Mega menu class
        if ($this->shouldUseMegaMenu($item, $context)) {
            $classes[] = 'mega-menu-item';
        }

        return $this->sanitizeCssClass(implode(' ', array_filter($classes)));
    }

    /**
     * Build desktop link CSS classes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string CSS classes
     * @since 2.0.0
     */
    private function buildDesktopLinkClasses(MenuItem $item, RenderContext $context): string
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

        if ($item->hasChildren()) {
            $classes[] = 'has-dropdown-link';
        }

        return $this->sanitizeCssClass(implode(' ', array_filter($classes)));
    }

    /**
     * Render dropdown indicator
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Dropdown indicator HTML
     * @since 2.0.0
     */
    private function renderDropdownIndicator(MenuItem $item, RenderContext $context): string
    {
        if ($item->getDepth() === 0) {
            // Top level - down arrow
            return '<span class="dropdown-indicator" aria-hidden="true">
                        <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 10l5 5 5-5z"/>
                        </svg>
                    </span>';
        } else {
            // Submenu level - right arrow
            return '<span class="dropdown-indicator" aria-hidden="true">
                        <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                        </svg>
                    </span>';
        }
    }

    /**
     * Check if item should use mega menu
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return bool True if should use mega menu
     * @since 2.0.0
     */
    private function shouldUseMegaMenu(MenuItem $item, RenderContext $context): bool
    {
        return $item->isTopLevel() &&
               $item->hasChildren() &&
               $this->options->getBool('enable_mega_menu') &&
               $this->options->getInt('mega_menu_columns', 1) > 1;
    }

    /**
     * Render mega menu item
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Mega menu item HTML
     * @since 2.0.0
     */
    private function renderMegaMenuItem(MenuItem $item, RenderContext $context): string
    {
        // This would implement mega menu rendering logic
        // For now, return standard item
        return $this->renderDesktopItem($item, $context);
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
        $baseClass = $this->options->getCssClass('dropdown_class');
        
        if ($depth === 1) {
            return $baseClass . ' first-level-dropdown';
        } elseif ($depth === 2) {
            return $baseClass . ' second-level-dropdown';
        } else {
            return $baseClass . ' deep-level-dropdown';
        }
    }

    /**
     * Generate unique submenu ID
     *
     * @param int $depth Menu depth
     * @return string Submenu ID
     * @since 2.0.0
     */
    private function generateSubmenuId(int $depth): string
    {
        $parentItem = $this->context->getParent($depth - 1);
        
        if ($parentItem) {
            return 'submenu-' . $parentItem->getId();
        }
        
        return 'submenu-depth-' . $depth;
    }

    /**
     * Generate Alpine.js dropdown bindings
     *
     * @return string Alpine.js dropdown bindings
     * @since 2.0.0
     */
    private function generateDropdownBindings(): string
    {
        if (!$this->options->getBool('enable_alpine')) {
            return '';
        }

        // Show/hide based on parent state
        $showDirective = $this->generateShowDirective('dropdownOpen');
        $transition = $this->generateTransition(['duration' => 200, 'type' => 'fade']);
        
        return "{$showDirective} {$transition}";
    }

    /**
     * Generate hover bindings for dropdown activation
     *
     * @param MenuItem $item Menu item
     * @return array<string, string> Hover bindings
     * @since 2.0.0
     */
    private function generateHoverBindings(MenuItem $item): array
    {
        if (!$this->options->getBool('enable_alpine') || !$this->options->getBool('enable_hover')) {
            return [];
        }

        $delay = $this->options->getInt('hover_delay', 200);
        
        return [
            '@mouseenter' => "hoverEnter({$item->getId()}, {$delay})",
            '@mouseleave' => "hoverLeave({$item->getId()}, {$delay})",
        ];
    }

    /**
     * Configure desktop-specific settings
     *
     * @return void
     * @since 2.0.0
     */
    private function configureDesktopSettings(): void
    {
        // Merge desktop config with options
        $desktopOptions = [
            'layout' => $this->options->getString('layout', 'horizontal'),
            'dropdown_behavior' => $this->options->getString('dropdown_behavior', 'hover'),
            'mega_menu_enabled' => $this->options->getBool('enable_mega_menu', false),
            'hover_delay' => $this->options->getInt('hover_delay', 200),
        ];

        $this->desktopConfig = array_merge($this->desktopConfig, $desktopOptions);
    }

    /**
     * Build attribute string from array
     *
     * @param array<string, string> $attributes Attributes array
     * @return string Attribute string
     * @since 2.0.0
     */
    private function buildAttributeString(array $attributes): string
    {
        $attributeString = '';
        
        foreach ($attributes as $name => $value) {
            if ($value !== null && $value !== '') {
                $attributeString .= sprintf(' %s="%s"', 
                    $this->escapeAttribute($name), 
                    $this->escapeAttribute($value)
                );
            }
        }
        
        return $attributeString;
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
            'supports_alpine' => true,
            'supports_caching' => true,
            'supports_accessibility' => true,
            'supports_multi_level' => true,
            'supports_mega_menu' => true,
            'supports_hover' => true,
            'supports_keyboard_nav' => true,
            'max_depth' => $this->options->getInt('max_depth', 3),
            'layout_types' => ['horizontal', 'vertical'],
            'dropdown_behaviors' => ['hover', 'click'],
        ];
    }
}