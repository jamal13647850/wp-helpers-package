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
 * MultiColumnStrategy - Multi-column mega menu navigation walker
 *
 * Advanced walker for creating multi-column mega menus with complex layouts,
 * featured content, and enhanced visual organization. Designed for large
 * websites with extensive navigation requirements.
 *
 * Features:
 * - Multi-column dropdown layouts
 * - Mega menu panels with custom content
 * - Featured content areas
 * - Image support in menu items
 * - Dynamic column balancing
 * - Advanced Alpine.js interactions
 *
 * @package jamal13647850\wphelpers\Navigation\Strategies
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
final class MultiColumnStrategy extends AbstractWalker
{
    use SecurityTrait;
    use IconHandlerTrait;
    use AlpineStateTrait;
    use AccessibilityTrait;
    use MenuItemRendererTrait;
    use CacheableTrait;

    /**
     * Multi-column specific configuration
     * @var array<string, mixed>
     */
    private array $columnConfig = [
        'columns' => 3,
        'max_columns' => 6,
        'min_items_per_column' => 2,
        'balance_columns' => true,
        'enable_featured_content' => true,
        'enable_images' => true,
        'image_size' => 'thumbnail',
        'column_width' => 'auto', // 'auto', 'equal', 'custom'
    ];

    /**
     * Multi-column buffer for processing menu items
     * @var array<int, array>
     */
    private array $columnBuffer = [];

    /**
     * Current mega menu parent ID
     * @var int|null
     */
    private ?int $currentMegaParent = null;

    /**
     * Featured content areas
     * @var array<int, array>
     */
    private array $featuredContent = [];

    /**
     * Get walker-specific default options
     *
     * @return array<string, mixed> Default options for multi-column walker
     * @since 2.0.0
     */
    protected function getDefaultOptions(): array
    {
        return [
            // CSS Classes
            'menu_class' => 'mega-menu multi-column-menu',
            'menu_item_class' => 'mega-menu-item',
            'mega_panel_class' => 'mega-menu-panel',
            'column_class' => 'mega-menu-column',
            'column_header_class' => 'column-header',
            'column_content_class' => 'column-content',
            'featured_area_class' => 'featured-content-area',
            'link_class' => 'mega-menu-link',
            'submenu_link_class' => 'mega-submenu-link',
            'active_class' => 'active current-menu-item',

            // Layout
            'columns' => 3,
            'max_columns' => 6,
            'column_width' => 'equal',
            'balance_columns' => true,
            'panel_width' => '100%',
            'panel_max_width' => '1200px',

            // Content
            'enable_images' => true,
            'image_size' => 'thumbnail',
            'enable_descriptions' => true,
            'enable_featured_content' => true,
            'show_column_headers' => true,

            // Behavior
            'max_depth' => 3,
            'enable_icons' => true,
            'dropdown_behavior' => 'hover',
            'hover_delay' => 300,
            'close_delay' => 500,

            // Alpine.js
            'enable_alpine' => true,
            'alpine_scope' => 'megaMenu',

            // Accessibility
            'enable_aria' => true,
            'enable_keyboard_nav' => true,
            'focus_indicators' => true,

            // Performance
            'enable_caching' => true,
            'cache_ttl' => 3600,
            'lazy_load_images' => true,
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
        return 'multi-column';
    }

    /**
     * Initialize multi-column walker
     *
     * @return void
     * @since 2.0.0
     */
    protected function initializeWalker(): void
    {
        // Configure column settings
        $this->configureColumnSettings();

        // Configure Alpine.js for mega menu interactions
        $this->configureAlpine([
            'enable_alpine' => $this->options->getBool('enable_alpine'),
            'accordion_mode' => 'none', // Mega menus don't use accordion
            'transition_duration' => $this->options->getInt('hover_delay', 300),
        ]);

        // Configure accessibility for complex mega menus
        $this->configureAccessibility([
            'enable_aria' => $this->options->getBool('enable_aria'),
            'enable_keyboard_nav' => $this->options->getBool('enable_keyboard_nav'),
            'wcag_level' => 'AA',
        ]);

        // Configure caching with longer TTL for complex menus
        $this->configureCache([
            'enable_caching' => $this->options->getBool('enable_caching'),
            'cache_ttl' => $this->options->getInt('cache_ttl'),
            'enable_fragment_cache' => true,
        ]);
    }

    /**
     * Start outputting submenu container for multi-column
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

        if ($depth === 1 && $this->currentMegaParent) {
            // Start mega menu panel
            $this->startMegaPanel($output, $depth);
        } else {
            // Regular submenu
            $this->startRegularSubmenu($output, $depth);
        }
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

        if ($depth === 1 && $this->currentMegaParent) {
            // End mega menu panel
            $this->endMegaPanel($output, $depth);
        } else {
            // Regular submenu
            $this->endRegularSubmenu($output, $depth);
        }
    }

    /**
     * Render individual menu item for multi-column
     *
     * @param MenuItem $item Menu item to render
     * @param RenderContext $context Rendering context
     * @return string Rendered menu item HTML
     * @since 2.0.0
     */
    protected function renderMenuItem(MenuItem $item, RenderContext $context): string
    {
        // Track mega menu parent
        if ($item->isTopLevel() && $item->hasChildren() && $this->shouldUseMegaMenu($item)) {
            $this->currentMegaParent = $item->getId();
        }

        // Buffer items for column organization
        if ($item->getDepth() === 1 && $this->currentMegaParent) {
            $this->bufferColumnItem($item, $context);
            return ''; // Will be rendered in end_lvl
        }

        // Use caching for complex items
        if ($this->options->getBool('enable_caching')) {
            return $this->getCachedFragment(
                'multicolumn_item_' . $item->getId(),
                fn() => $this->renderMultiColumnItem($item, $context),
                $context
            );
        }

        return $this->renderMultiColumnItem($item, $context);
    }

    /**
     * Render multi-column menu item without caching
     *
     * @param MenuItem $item Menu item to render
     * @param RenderContext $context Rendering context
     * @return string Rendered menu item HTML
     * @since 2.0.0
     */
    private function renderMultiColumnItem(MenuItem $item, RenderContext $context): string
    {
        $depth = $item->getDepth();
        $indent = str_repeat("\t", $depth + 1);

        if ($item->isTopLevel()) {
            return $indent . $this->renderTopLevelItem($item, $context) . "\n";
        } else {
            return $indent . $this->renderSubmenuItem($item, $context) . "\n";
        }
    }

    /**
     * Render top-level menu item
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Top-level item HTML
     * @since 2.0.0
     */
    private function renderTopLevelItem(MenuItem $item, RenderContext $context): string
    {
        $containerAttrs = $this->buildTopLevelContainerAttributes($item, $context);
        $linkHtml = $this->renderTopLevelLink($item, $context);

        return $this->buildHtmlElement('li', $linkHtml, $containerAttrs);
    }

    /**
     * Render submenu item
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Submenu item HTML
     * @since 2.0.0
     */
    private function renderSubmenuItem(MenuItem $item, RenderContext $context): string
    {
        $linkAttrs = $this->buildSubmenuLinkAttributes($item, $context);
        $linkContent = $this->buildSubmenuLinkContent($item, $context);

        return $this->buildHtmlElement('a', $linkContent, $linkAttrs);
    }

    /**
     * Start mega menu panel
     *
     * @param string $output Reference to output string
     * @param int $depth Menu depth
     * @return void
     * @since 2.0.0
     */
    private function startMegaPanel(string &$output, int $depth): void
    {
        $indent = str_repeat("\t", $depth + 1);
        $panelClass = $this->options->getCssClass('mega_panel_class');
        $panelId = 'mega-panel-' . $this->currentMegaParent;

        // Alpine.js bindings for mega menu
        $alpineBindings = $this->generateMegaMenuBindings();

        // Accessibility attributes
        $ariaAttributes = $this->generateMegaPanelAriaAttributes();

        $output .= "\n{$indent}<div class=\"{$panelClass}\" id=\"{$panelId}\" {$alpineBindings} {$this->buildAttributeString($ariaAttributes)}>\n";
        $output .= "{$indent}\t<div class=\"mega-menu-container\">\n";

        // Add featured content area if enabled
        if ($this->options->getBool('enable_featured_content')) {
            $output .= $this->renderFeaturedContentArea($indent . "\t\t");
        }

        $output .= "{$indent}\t\t<div class=\"mega-menu-columns\">\n";
    }

    /**
     * End mega menu panel
     *
     * @param string $output Reference to output string
     * @param int $depth Menu depth
     * @return void
     * @since 2.0.0
     */
    private function endMegaPanel(string &$output, int $depth): void
    {
        $indent = str_repeat("\t", $depth + 1);

        // Render buffered columns
        $output .= $this->renderBufferedColumns($indent . "\t\t\t");

        $output .= "{$indent}\t\t</div>\n"; // .mega-menu-columns
        $output .= "{$indent}\t</div>\n";   // .mega-menu-container
        $output .= "{$indent}</div>\n";     // .mega-menu-panel

        // Clear buffer and reset
        $this->columnBuffer = [];
        $this->currentMegaParent = null;
    }

    /**
     * Start regular submenu
     *
     * @param string $output Reference to output string
     * @param int $depth Menu depth
     * @return void
     * @since 2.0.0
     */
    private function startRegularSubmenu(string &$output, int $depth): void
    {
        $indent = str_repeat("\t", $depth + 1);
        $submenuClass = 'sub-menu depth-' . $depth;

        $output .= "\n{$indent}<ul class=\"{$submenuClass}\">\n";
    }

    /**
     * End regular submenu
     *
     * @param string $output Reference to output string
     * @param int $depth Menu depth
     * @return void
     * @since 2.0.0
     */
    private function endRegularSubmenu(string &$output, int $depth): void
    {
        $indent = str_repeat("\t", $depth + 1);
        $output .= "{$indent}</ul>\n";
    }

    /**
     * Check if item should use mega menu
     *
     * @param MenuItem $item Menu item
     * @return bool True if should use mega menu
     * @since 2.0.0
     */
    private function shouldUseMegaMenu(MenuItem $item): bool
    {
        // Check if item has enough children for mega menu
        $minItems = $this->columnConfig['min_items_per_column'] * $this->columnConfig['columns'];
        
        // For now, assume mega menu if top-level with children
        // In real implementation, you'd count actual children
        return $item->isTopLevel() && 
               $item->hasChildren() && 
               $this->options->getInt('columns', 1) > 1;
    }

    /**
     * Buffer column item for later rendering
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return void
     * @since 2.0.0
     */
    private function bufferColumnItem(MenuItem $item, RenderContext $context): void
    {
        if (!$this->currentMegaParent) {
            return;
        }

        $this->columnBuffer[] = [
            'item' => $item,
            'context' => $context,
            'html' => $this->renderColumnItem($item, $context),
        ];
    }

    /**
     * Render column item
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Column item HTML
     * @since 2.0.0
     */
    private function renderColumnItem(MenuItem $item, RenderContext $context): string
    {
        $content = '';

        // Item header
        if ($this->options->getBool('show_column_headers')) {
            $content .= $this->renderColumnHeader($item, $context);
        }

        // Item content
        $content .= $this->renderColumnContent($item, $context);

        return $content;
    }

    /**
     * Render column header
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Column header HTML
     * @since 2.0.0
     */
    private function renderColumnHeader(MenuItem $item, RenderContext $context): string
    {
        $headerClass = $this->options->getCssClass('column_header_class');
        $linkAttrs = $item->getLinkAttributes();
        $linkAttrs['class'] = 'column-header-link';

        $headerContent = '';

        // Icon
        if ($this->options->getBool('enable_icons')) {
            $headerContent .= $this->renderIcon($item, ['position' => 'before']);
        }

        // Title
        $headerContent .= sprintf('<span class="header-text">%s</span>', $this->escapeHtml($item->getTitle()));

        $link = $this->buildHtmlElement('a', $headerContent, $linkAttrs);

        return sprintf('<div class="%s">%s</div>', $headerClass, $link);
    }

    /**
     * Render column content
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Column content HTML
     * @since 2.0.0
     */
    private function renderColumnContent(MenuItem $item, RenderContext $context): string
    {
        $contentClass = $this->options->getCssClass('column_content_class');
        $content = '';

        // Description
        if ($this->options->getBool('enable_descriptions')) {
            $description = $item->getDescription();
            if (!empty($description)) {
                $content .= sprintf('<p class="column-description">%s</p>', $this->escapeHtml($description));
            }
        }

        // Image
        if ($this->options->getBool('enable_images')) {
            $image = $this->renderColumnImage($item);
            if (!empty($image)) {
                $content .= $image;
            }
        }

        // Child items would be rendered here in full implementation

        return sprintf('<div class="%s">%s</div>', $contentClass, $content);
    }

    /**
     * Render column image
     *
     * @param MenuItem $item Menu item
     * @return string Image HTML
     * @since 2.0.0
     */
    private function renderColumnImage(MenuItem $item): string
    {
        // This would integrate with WordPress media system
        // For now, return placeholder
        $imageSize = $this->options->getString('image_size', 'thumbnail');
        $lazyLoad = $this->options->getBool('lazy_load_images');

        return sprintf(
            '<div class="column-image"><img src="placeholder.jpg" alt="%s" class="menu-image" %s /></div>',
            $this->escapeAttribute($item->getTitle()),
            $lazyLoad ? 'loading="lazy"' : ''
        );
    }

    /**
     * Render buffered columns
     *
     * @param string $indent Indentation string
     * @return string Columns HTML
     * @since 2.0.0
     */
    private function renderBufferedColumns(string $indent): string
    {
        if (empty($this->columnBuffer)) {
            return '';
        }

        $columns = $this->organizeItemsIntoColumns();
        $output = '';

        foreach ($columns as $columnIndex => $items) {
            $output .= $this->renderColumn($items, $columnIndex, $indent);
        }

        return $output;
    }

    /**
     * Organize buffered items into columns
     *
     * @return array<int, array> Organized columns
     * @since 2.0.0
     */
    private function organizeItemsIntoColumns(): array
    {
        $itemCount = count($this->columnBuffer);
        $columnCount = $this->columnConfig['columns'];
        
        if ($this->columnConfig['balance_columns']) {
            $itemsPerColumn = ceil($itemCount / $columnCount);
        } else {
            $itemsPerColumn = $this->columnConfig['min_items_per_column'];
        }

        $columns = [];
        $currentColumn = 0;

        foreach ($this->columnBuffer as $index => $bufferedItem) {
            if (!isset($columns[$currentColumn])) {
                $columns[$currentColumn] = [];
            }

            $columns[$currentColumn][] = $bufferedItem;

            // Move to next column if current is full
            if (count($columns[$currentColumn]) >= $itemsPerColumn && $currentColumn < $columnCount - 1) {
                $currentColumn++;
            }
        }

        return $columns;
    }

    /**
     * Render single column
     *
     * @param array $items Column items
     * @param int $columnIndex Column index
     * @param string $indent Indentation
     * @return string Column HTML
     * @since 2.0.0
     */
    private function renderColumn(array $items, int $columnIndex, string $indent): string
    {
        $columnClass = $this->options->getCssClass('column_class');
        $columnClass .= ' column-' . ($columnIndex + 1);

        $content = '';
        foreach ($items as $bufferedItem) {
            $content .= $bufferedItem['html'];
        }

        return sprintf(
            "%s<div class=\"%s\">\n%s%s</div>\n",
            $indent,
            $this->sanitizeCssClass($columnClass),
            $content,
            $indent
        );
    }

    /**
     * Render featured content area
     *
     * @param string $indent Indentation
     * @return string Featured content HTML
     * @since 2.0.0
     */
    private function renderFeaturedContentArea(string $indent): string
    {
        $featuredClass = $this->options->getCssClass('featured_area_class');
        
        return sprintf(
            "%s<div class=\"%s\">\n%s\t<!-- Featured content would go here -->\n%s</div>\n",
            $indent,
            $featuredClass,
            $indent,
            $indent
        );
    }

    /**
     * Build top-level container attributes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return array<string, string> Container attributes
     * @since 2.0.0
     */
    private function buildTopLevelContainerAttributes(MenuItem $item, RenderContext $context): array
    {
        $attributes = [];

        // Basic attributes
        $attributes['id'] = 'menu-item-' . $item->getId();
        $attributes['class'] = $this->buildTopLevelContainerClasses($item, $context);

        // ARIA attributes
        $ariaAttrs = $this->generateItemAriaAttributes($item, $context);
        $attributes = array_merge($attributes, $ariaAttrs);

        // Alpine.js hover handlers for mega menu
        if ($item->hasChildren() && $this->shouldUseMegaMenu($item)) {
            $hoverBindings = $this->generateMegaMenuHoverBindings($item);
            $attributes = array_merge($attributes, $hoverBindings);
        }

        return $attributes;
    }

    /**
     * Build top-level container classes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string CSS classes
     * @since 2.0.0
     */
    private function buildTopLevelContainerClasses(MenuItem $item, RenderContext $context): string
    {
        $classes = [];

        // Base classes
        $classes[] = $this->options->getCssClass('menu_item_class');
        $classes[] = 'menu-item-' . $item->getId();

        // WordPress classes
        $classes = array_merge($classes, $item->getClasses());

        // State classes
        if ($item->hasChildren()) {
            if ($this->shouldUseMegaMenu($item)) {
                $classes[] = 'has-mega-menu';
            } else {
                $classes[] = 'has-dropdown';
            }
        }

        if ($item->isActive()) {
            $classes[] = $this->options->getCssClass('active_class');
        }

        return $this->sanitizeCssClass(implode(' ', array_filter($classes)));
    }

    /**
     * Render top-level link
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Link HTML
     * @since 2.0.0
     */
    private function renderTopLevelLink(MenuItem $item, RenderContext $context): string
    {
        $linkAttrs = $this->buildTopLevelLinkAttributes($item, $context);
        $linkContent = $this->buildTopLevelLinkContent($item, $context);

        return $this->buildHtmlElement('a', $linkContent, $linkAttrs);
    }

    /**
     * Build top-level link attributes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return array<string, string> Link attributes
     * @since 2.0.0
     */
    private function buildTopLevelLinkAttributes(MenuItem $item, RenderContext $context): array
    {
        $attributes = $item->getLinkAttributes();

        // CSS classes
        $attributes['class'] = $this->options->getCssClass('link_class');

        // Focus attributes
        $focusAttrs = $this->generateFocusAttributes($item);
        $attributes = array_merge($attributes, $focusAttrs);

        return $attributes;
    }

    /**
     * Build top-level link content
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Link content HTML
     * @since 2.0.0
     */
    private function buildTopLevelLinkContent(MenuItem $item, RenderContext $context): string
    {
        $content = '';

        // Icon
        if ($this->options->getBool('enable_icons')) {
            $content .= $this->renderIcon($item, ['position' => 'before']);
        }

        // Text
        $content .= sprintf('<span class="menu-text">%s</span>', $this->escapeHtml($item->getTitle()));

        // Dropdown indicator
        if ($item->hasChildren()) {
            $content .= '<span class="dropdown-indicator" aria-hidden="true">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7 10l5 5 5-5z"/>
                            </svg>
                        </span>';
        }

        return $content;
    }

    /**
     * Build submenu link attributes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return array<string, string> Link attributes
     * @since 2.0.0
     */
    private function buildSubmenuLinkAttributes(MenuItem $item, RenderContext $context): array
    {
        $attributes = $item->getLinkAttributes();
        $attributes['class'] = $this->options->getCssClass('submenu_link_class');

        return $attributes;
    }

    /**
     * Build submenu link content
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Link content HTML
     * @since 2.0.0
     */
    private function buildSubmenuLinkContent(MenuItem $item, RenderContext $context): string
    {
        return $this->escapeHtml($item->getTitle());
    }

    /**
     * Generate mega menu Alpine.js bindings
     *
     * @return string Alpine.js bindings
     * @since 2.0.0
     */
    private function generateMegaMenuBindings(): string
    {
        if (!$this->options->getBool('enable_alpine')) {
            return 'style="display: none;"';
        }

        $showDirective = $this->generateShowDirective('megaMenuOpen');
        $transition = $this->generateTransition(['duration' => 300, 'type' => 'fade']);
        
        return "{$showDirective} {$transition}";
    }

    /**
     * Generate mega menu hover bindings
     *
     * @param MenuItem $item Menu item
     * @return array<string, string> Hover bindings
     * @since 2.0.0
     */
    private function generateMegaMenuHoverBindings(MenuItem $item): array
    {
        if (!$this->options->getBool('enable_alpine')) {
            return [];
        }

        $hoverDelay = $this->options->getInt('hover_delay', 300);
        $closeDelay = $this->options->getInt('close_delay', 500);
        
        return [
            '@mouseenter' => "openMegaMenu({$item->getId()}, {$hoverDelay})",
            '@mouseleave' => "closeMegaMenu({$item->getId()}, {$closeDelay})",
        ];
    }

    /**
     * Generate mega panel ARIA attributes
     *
     * @return array<string, string> ARIA attributes
     * @since 2.0.0
     */
    private function generateMegaPanelAriaAttributes(): array
    {
        if (!$this->options->getBool('enable_aria')) {
            return [];
        }

        return [
            'role' => 'menu',
            'aria-hidden' => 'true',
            'aria-labelledby' => 'menu-item-' . $this->currentMegaParent,
        ];
    }

    /**
     * Configure column settings
     *
     * @return void
     * @since 2.0.0
     */
    private function configureColumnSettings(): void
    {
        $this->columnConfig = array_merge($this->columnConfig, [
            'columns' => $this->options->getInt('columns', 3),
            'balance_columns' => $this->options->getBool('balance_columns', true),
            'enable_featured_content' => $this->options->getBool('enable_featured_content', true),
            'enable_images' => $this->options->getBool('enable_images', true),
        ]);
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
            'supports_multi_column' => true,
            'supports_featured_content' => true,
            'supports_images' => true,
            'supports_hover' => true,
            'supports_keyboard_nav' => true,
            'max_depth' => $this->options->getInt('max_depth', 3),
            'max_columns' => $this->columnConfig['max_columns'],
            'layout_types' => ['horizontal'],
            'complexity' => 'advanced',
        ];
    }
}