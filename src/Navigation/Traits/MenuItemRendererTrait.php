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

namespace jamal13647850\wphelpers\Navigation\Traits;

defined('ABSPATH') || exit();

/**
 * MenuItemRendererTrait - Common menu item rendering functionality
 *
 * Provides standardized rendering patterns for menu items across different
 * walker implementations. Handles link generation, container wrapping,
 * styling application, and content formatting with consistent behavior.
 *
 * Features:
 * - Standardized link rendering with attributes
 * - Container and wrapper management
 * - CSS class generation and application
 * - Content formatting and escaping
 * - Template-based rendering support
 * - Performance optimizations
 * - Extensibility hooks
 *
 * @package jamal13647850\wphelpers\Navigation\Traits
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
trait MenuItemRendererTrait
{
    /**
     * Rendering configuration
     * @var array<string, mixed>
     */
    private array $renderConfig = [
        'default_tag' => 'li',
        'link_tag' => 'a',
        'enable_microdata' => false,
        'enable_json_ld' => false,
        'template_engine' => 'php', // 'php', 'twig'
        'cache_rendered_items' => true,
        'minify_output' => false,
    ];

    /**
     * Cache for rendered item HTML
     * @var array<string, string>
     */
    private array $renderedItemCache = [];

    /**
     * Template cache for performance
     * @var array<string, string>
     */
    private array $templateCache = [];

    /**
     * Render complete menu item with container and link
     *
     * Primary rendering method that orchestrates the complete item rendering
     * process including container, link, content, and any additional elements.
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item to render
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $options Rendering options
     * @return string Complete menu item HTML
     * @since 2.0.0
     */
    protected function renderCompleteItem($item, $context, array $options = []): string
    {
        // Check cache first
        if ($this->renderConfig['cache_rendered_items']) {
            $cacheKey = $this->generateRenderCacheKey($item, $context, $options);
            if (isset($this->renderedItemCache[$cacheKey])) {
                return $this->renderedItemCache[$cacheKey];
            }
        }

        // Apply pre-render filters
        $item = apply_filters('wphelpers/render/pre_item', $item, $context, $options);

        // Build item HTML structure
        $html = $this->buildItemStructure($item, $context, $options);

        // Apply post-render filters
        $html = apply_filters('wphelpers/render/post_item', $html, $item, $context, $options);

        // Minify if enabled
        if ($this->renderConfig['minify_output']) {
            $html = $this->minifyHtml($html);
        }

        // Cache the result
        if ($this->renderConfig['cache_rendered_items']) {
            $this->renderedItemCache[$cacheKey] = $html;
        }

        return $html;
    }

    /**
     * Build complete item HTML structure
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $options Rendering options
     * @return string Item HTML structure
     * @since 2.0.0
     */
    private function buildItemStructure($item, $context, array $options): string
    {
        $useContainer = $options['use_container'] ?? true;
        
        if (!$useContainer) {
            return $this->renderItemLink($item, $context, $options);
        }

        // Render container with link
        $containerTag = $options['container_tag'] ?? $this->renderConfig['default_tag'];
        $containerAttrs = $this->buildContainerAttributes($item, $context, $options);
        $linkHtml = $this->renderItemLink($item, $context, $options);

        return $this->buildHtmlElement($containerTag, $linkHtml, $containerAttrs);
    }

    /**
     * Render menu item link element
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $options Rendering options
     * @return string Link HTML
     * @since 2.0.0
     */
    protected function renderItemLink($item, $context, array $options = []): string
    {
        $linkTag = $options['link_tag'] ?? $this->renderConfig['link_tag'];
        $linkAttrs = $this->buildLinkAttributes($item, $context, $options);
        $linkContent = $this->buildLinkContent($item, $context, $options);

        return $this->buildHtmlElement($linkTag, $linkContent, $linkAttrs);
    }

    /**
     * Build container attributes for menu item
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $options Rendering options
     * @return array<string, string> Container attributes
     * @since 2.0.0
     */
    private function buildContainerAttributes($item, $context, array $options): array
    {
        $attributes = [];

        // Basic ID and classes
        $attributes['id'] = $this->generateItemId($item);
        $attributes['class'] = $this->buildContainerClasses($item, $context, $options);

        // ARIA attributes
        if (method_exists($this, 'generateItemAriaAttributes')) {
            $ariaAttrs = $this->generateItemAriaAttributes($item, $context);
            $attributes = array_merge($attributes, $ariaAttrs);
        }

        // Keyboard navigation attributes
        if (method_exists($this, 'generateKeyboardAttributes')) {
            $keyboardAttrs = $this->generateKeyboardAttributes($item, $context->getWalkerType());
            $attributes = array_merge($attributes, $keyboardAttrs);
        }

        // Alpine.js attributes
        if (method_exists($this, 'generateAlpineAttributes')) {
            $alpineAttrs = $this->generateAlpineAttributes($item, $context);
            $attributes = array_merge($attributes, $alpineAttrs);
        }

        // Custom data attributes
        $customAttrs = $options['container_attributes'] ?? [];
        $attributes = array_merge($attributes, $customAttrs);

        // Microdata if enabled
        if ($this->renderConfig['enable_microdata']) {
            $microdataAttrs = $this->buildMicrodataAttributes($item);
            $attributes = array_merge($attributes, $microdataAttrs);
        }

        return $attributes;
    }

    /**
     * Build link attributes for menu item
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $options Rendering options
     * @return array<string, string> Link attributes
     * @since 2.0.0
     */
    private function buildLinkAttributes($item, $context, array $options): array
    {
        // Start with item's native attributes
        $attributes = $item->getLinkAttributes();

        // Add CSS classes
        $attributes['class'] = $this->buildLinkClasses($item, $context, $options);

        // Add focus attributes
        if (method_exists($this, 'generateFocusAttributes')) {
            $focusAttrs = $this->generateFocusAttributes($item);
            $attributes = array_merge($attributes, $focusAttrs);
        }

        // Add tracking attributes if needed
        if ($options['enable_tracking'] ?? false) {
            $trackingAttrs = $this->buildTrackingAttributes($item);
            $attributes = array_merge($attributes, $trackingAttrs);
        }

        // Custom link attributes
        $customAttrs = $options['link_attributes'] ?? [];
        $attributes = array_merge($attributes, $customAttrs);

        return $attributes;
    }

    /**
     * Build link content including text, icons, and indicators
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $options Rendering options
     * @return string Link content HTML
     * @since 2.0.0
     */
    private function buildLinkContent($item, $context, array $options): string
    {
        $content = '';

        // Before content hook
        $content .= apply_filters('wphelpers/render/before_link_content', '', $item, $context);

        // Icon (before text)
        if (method_exists($this, 'renderIcon')) {
            $iconOptions = array_merge($options, ['position' => 'before']);
            $content .= $this->renderIcon($item, $iconOptions);
        }

        // Main text content
        $content .= $this->buildTextContent($item, $context, $options);

        // Children indicator
        if ($item->hasChildren()) {
            $content .= $this->renderChildrenIndicator($item, $context, $options);
        }

        // Badge or counter
        if ($options['show_badge'] ?? false) {
            $content .= $this->renderItemBadge($item, $options);
        }

        // After content hook
        $content .= apply_filters('wphelpers/render/after_link_content', '', $item, $context);

        return $content;
    }

    /**
     * Build text content for menu item
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $options Rendering options
     * @return string Text content HTML
     * @since 2.0.0
     */
    private function buildTextContent($item, $context, array $options): string
    {
        $title = $item->getTitle();
        $wrapText = $options['wrap_text'] ?? true;

        // Screen reader text for current items
        $srText = '';
        if ($item->isCurrent() && method_exists($this, 'generateScreenReaderText')) {
            $srText = $this->generateScreenReaderText(__('صفحه فعلی', 'wphelpers'), false);
        }

        // Main title
        $textContent = $this->escapeHtml($title) . $srText;

        // Description if enabled
        if ($options['show_description'] ?? false) {
            $description = $item->getDescription();
            if (!empty($description)) {
                $textContent .= sprintf(
                    '<span class="menu-item-description">%s</span>',
                    $this->escapeHtml($description)
                );
            }
        }

        // Wrap in span if requested
        if ($wrapText) {
            $textContent = sprintf(
                '<span class="menu-item-text">%s</span>',
                $textContent
            );
        }

        return $textContent;
    }

    /**
     * Render children indicator (arrow, caret, etc.)
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $options Rendering options
     * @return string Children indicator HTML
     * @since 2.0.0
     */
    private function renderChildrenIndicator($item, $context, array $options): string
    {
        $indicatorType = $options['children_indicator'] ?? 'caret';
        
        switch ($indicatorType) {
            case 'none':
                return '';
                
            case 'text':
                return $this->renderTextIndicator($options);
                
            case 'icon':
            case 'fontawesome':
                return $this->renderIconIndicator($options);
                
            case 'svg':
                return $this->renderSvgIndicator($options);
                
            case 'caret':
            default:
                return $this->renderCaretIndicator($options);
        }
    }

    /**
     * Render text-based children indicator
     *
     * @param array<string, mixed> $options Rendering options
     * @return string Text indicator HTML
     * @since 2.0.0
     */
    private function renderTextIndicator(array $options): string
    {
        $text = $options['indicator_text'] ?? '▼';
        return sprintf(
            '<span class="menu-item-indicator text-indicator" aria-hidden="true">%s</span>',
            $this->escapeHtml($text)
        );
    }

    /**
     * Render icon-based children indicator
     *
     * @param array<string, mixed> $options Rendering options
     * @return string Icon indicator HTML
     * @since 2.0.0
     */
    private function renderIconIndicator(array $options): string
    {
        $iconClass = $options['indicator_icon'] ?? 'fa fa-chevron-down';
        return sprintf(
            '<i class="menu-item-indicator icon-indicator %s" aria-hidden="true"></i>',
            $this->sanitizeCssClass($iconClass)
        );
    }

    /**
     * Render SVG-based children indicator
     *
     * @param array<string, mixed> $options Rendering options
     * @return string SVG indicator HTML
     * @since 2.0.0
     */
    private function renderSvgIndicator(array $options): string
    {
        $svgContent = $options['indicator_svg'] ?? $this->getDefaultCaretSvg();
        return sprintf(
            '<span class="menu-item-indicator svg-indicator" aria-hidden="true">%s</span>',
            $svgContent
        );
    }

    /**
     * Render caret-based children indicator
     *
     * @param array<string, mixed> $options Rendering options
     * @return string Caret indicator HTML
     * @since 2.0.0
     */
    private function renderCaretIndicator(array $options): string
    {
        $caretClass = $options['caret_class'] ?? 'menu-caret';
        
        // Add Alpine.js bindings if available
        $alpineBindings = '';
        if (method_exists($this, 'generateBinding')) {
            $alpineBindings = $this->generateBinding('class', 'isOpen ? "rotated" : ""');
        }

        return sprintf(
            '<span class="%s" aria-hidden="true" %s>%s</span>',
            $this->sanitizeCssClass($caretClass),
            $alpineBindings,
            $this->getDefaultCaretSvg()
        );
    }

    /**
     * Render item badge or counter
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param array<string, mixed> $options Rendering options
     * @return string Badge HTML
     * @since 2.0.0
     */
    private function renderItemBadge($item, array $options): string
    {
        $badgeValue = $options['badge_value'] ?? null;
        
        if (empty($badgeValue)) {
            return '';
        }

        $badgeClass = $options['badge_class'] ?? 'menu-item-badge';
        
        return sprintf(
            '<span class="%s" aria-label="%s">%s</span>',
            $this->sanitizeCssClass($badgeClass),
            $this->escapeAttribute(sprintf(__('تعداد: %s', 'wphelpers'), $badgeValue)),
            $this->escapeHtml($badgeValue)
        );
    }

    /**
     * Build container CSS classes
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $options Rendering options
     * @return string CSS classes
     * @since 2.0.0
     */
    private function buildContainerClasses($item, $context, array $options): string
    {
        $classes = [];

        // Base classes
        $classes[] = 'menu-item';
        $classes[] = 'menu-item-' . $item->getId();

        // WordPress classes
        $classes = array_merge($classes, $item->getClasses());

        // State classes
        if ($item->hasChildren()) {
            $classes[] = 'has-children';
            $classes[] = 'menu-item-has-children';
        }

        if ($item->isActive()) {
            $classes[] = 'active';
        }

        if ($item->isCurrent()) {
            $classes[] = 'current';
            $classes[] = 'current-menu-item';
        }

        // Depth-based classes
        $classes[] = 'menu-depth-' . $item->getDepth();

        if ($item->isTopLevel()) {
            $classes[] = 'top-level';
        } else {
            $classes[] = 'sub-level';
        }

        // Walker-specific classes
        $walkerType = $context->getWalkerType();
        $classes[] = $walkerType . '-menu-item';

        // Custom classes from options
        $customClasses = $options['container_class'] ?? '';
        if (!empty($customClasses)) {
            $classes[] = $customClasses;
        }

        // Clean and return
        $classes = array_filter(array_map('trim', $classes));
        return $this->sanitizeCssClass(implode(' ', $classes));
    }

    /**
     * Build link CSS classes
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $options Rendering options
     * @return string CSS classes
     * @since 2.0.0
     */
    private function buildLinkClasses($item, $context, array $options): string
    {
        $classes = [];

        // Base link class
        $classes[] = 'menu-link';

        // Walker-specific classes
        $walkerType = $context->getWalkerType();
        $depth = $item->getDepth();

        // Get appropriate class from options/context
        $linkClassKey = $depth === 0 ? "{$walkerType}_link_class" : "{$walkerType}_submenu_class";
        $baseClass = $context->getOptions()->getString($linkClassKey, 'menu-link');
        $classes[] = $baseClass;

        // State classes
        if ($item->isCurrent()) {
            $classes[] = 'current-link';
        }

        if ($item->hasChildren()) {
            $classes[] = 'has-dropdown';
        }

        // Custom classes from options
        $customClasses = $options['link_class'] ?? '';
        if (!empty($customClasses)) {
            $classes[] = $customClasses;
        }

        // Clean and return
        $classes = array_filter(array_map('trim', $classes));
        return $this->sanitizeCssClass(implode(' ', $classes));
    }

    /**
     * Build microdata attributes for structured data
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @return array<string, string> Microdata attributes
     * @since 2.0.0
     */
    private function buildMicrodataAttributes($item): array
    {
        return [
            'itemscope' => '',
            'itemtype' => 'https://schema.org/SiteNavigationElement',
            'itemprop' => 'name',
        ];
    }

    /**
     * Build tracking attributes for analytics
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @return array<string, string> Tracking attributes
     * @since 2.0.0
     */
    private function buildTrackingAttributes($item): array
    {
        return [
            'data-track-event' => 'menu_click',
            'data-track-label' => $item->getTitle(),
            'data-track-value' => (string) $item->getId(),
        ];
    }

    /**
     * Get default caret SVG
     *
     * @return string Default caret SVG
     * @since 2.0.0
     */
    private function getDefaultCaretSvg(): string
    {
        return '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 10l5 5 5-5z"/>
                </svg>';
    }

    /**
     * Build HTML element with attributes
     *
     * @param string $tag HTML tag name
     * @param string $content Element content
     * @param array<string, string> $attributes Element attributes
     * @return string Complete HTML element
     * @since 2.0.0
     */
    private function buildHtmlElement(string $tag, string $content, array $attributes): string
    {
        $attributeString = '';
        foreach ($attributes as $name => $value) {
            if ($value !== null && $value !== '') {
                $attributeString .= sprintf(' %s="%s"', 
                    $this->escapeAttribute($name), 
                    $this->escapeAttribute($value)
                );
            } elseif ($value === '') {
                // Boolean attributes (like 'itemscope')
                $attributeString .= sprintf(' %s', $this->escapeAttribute($name));
            }
        }

        // Self-closing tags
        if (in_array($tag, ['img', 'br', 'hr', 'input'], true)) {
            return sprintf('<%s%s />', $this->escapeAttribute($tag), $attributeString);
        }

        return sprintf('<%s%s>%s</%s>', 
            $this->escapeAttribute($tag), 
            $attributeString, 
            $content, 
            $this->escapeAttribute($tag)
        );
    }

    /**
     * Generate cache key for rendered item
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $options Rendering options
     * @return string Cache key
     * @since 2.0.0
     */
    private function generateRenderCacheKey($item, $context, array $options): string
    {
        return 'render_' . md5(serialize([
            $item->getId(),
            $item->getDepth(),
            $context->getWalkerType(),
            $options,
        ]));
    }

    /**
     * Minify HTML output
     *
     * @param string $html HTML to minify
     * @return string Minified HTML
     * @since 2.0.0
     */
    private function minifyHtml(string $html): string
    {
        // Remove extra whitespace and line breaks
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        
        return trim($html);
    }

    /**
     * Clear render cache
     *
     * @return void
     * @since 2.0.0
     */
    protected function clearRenderCache(): void
    {
        $this->renderedItemCache = [];
        $this->templateCache = [];
    }

    /**
     * Configure rendering settings
     *
     * @param array<string, mixed> $config Render configuration
     * @return void
     * @since 2.0.0
     */
    protected function configureRenderer(array $config): void
    {
        $this->renderConfig = array_merge($this->renderConfig, $config);
    }
}