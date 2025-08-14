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
 * IconHandlerTrait - Centralized icon management for navigation walkers
 *
 * Provides comprehensive icon handling capabilities including FontAwesome,
 * custom SVGs, image icons, and unicode symbols. Supports multiple icon
 * sources and provides consistent rendering across all walker types.
 *
 * Features:
 * - FontAwesome icon detection and rendering
 * - Custom SVG icon support
 * - Image icon handling
 * - Unicode symbol support
 * - Icon positioning and styling
 * - Accessibility compliance (aria-hidden, alt text)
 * - Performance optimizations (caching, lazy loading)
 *
 * @package jamal13647850\wphelpers\Navigation\Traits
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
trait IconHandlerTrait
{
    /**
     * Cache for processed icons to improve performance
     * @var array<string, string>
     */
    private array $iconCache = [];

    /**
     * Icon configuration settings
     * @var array<string, mixed>
     */
    private array $iconConfig = [
        'enable_cache' => true,
        'default_icon_size' => '16px',
        'fontawesome_prefix' => 'fa',
        'custom_svg_path' => '',
        'icon_position' => 'before', // 'before', 'after', 'replace'
        'fallback_icon' => null,
        'lazy_load_icons' => false,
    ];

    /**
     * Supported icon types
     * @var array<string>
     */
    private array $supportedIconTypes = [
        'fontawesome',
        'svg',
        'image',
        'unicode',
        'custom',
    ];

    /**
     * Render icon HTML based on menu item configuration
     *
     * Detects icon type and renders appropriate HTML with accessibility
     * attributes and proper styling.
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item object
     * @param array<string, mixed> $options Icon rendering options
     * @return string Rendered icon HTML or empty string
     * @since 2.0.0
     */
    protected function renderIcon($item, array $options = []): string
    {
        // Check if icons are enabled
        if (!$this->isIconsEnabled()) {
            return '';
        }

        // Get icon class from menu item
        $iconClass = $item->getIconClass();
        if (empty($iconClass)) {
            return $this->renderFallbackIcon($options);
        }

        // Use cache if available
        $cacheKey = $this->generateIconCacheKey($iconClass, $options);
        if ($this->iconConfig['enable_cache'] && isset($this->iconCache[$cacheKey])) {
            return $this->iconCache[$cacheKey];
        }

        // Detect icon type and render accordingly
        $iconType = $this->detectIconType($iconClass);
        $html = $this->renderIconByType($iconType, $iconClass, $options);

        // Cache the result
        if ($this->iconConfig['enable_cache']) {
            $this->iconCache[$cacheKey] = $html;
        }

        return $html;
    }

    /**
     * Detect icon type from class string
     *
     * Analyzes the icon class to determine the appropriate rendering method.
     *
     * @param string $iconClass Icon class string
     * @return string Icon type identifier
     * @since 2.0.0
     */
    private function detectIconType(string $iconClass): string
    {
        $iconClass = trim($iconClass);

        // FontAwesome detection
        if ($this->isFontAwesomeIcon($iconClass)) {
            return 'fontawesome';
        }

        // SVG detection
        if ($this->isSvgIcon($iconClass)) {
            return 'svg';
        }

        // Image detection
        if ($this->isImageIcon($iconClass)) {
            return 'image';
        }

        // Unicode detection
        if ($this->isUnicodeIcon($iconClass)) {
            return 'unicode';
        }

        // Default to custom
        return 'custom';
    }

    /**
     * Check if icon class represents a FontAwesome icon
     *
     * @param string $iconClass Icon class string
     * @return bool True if FontAwesome icon
     * @since 2.0.0
     */
    private function isFontAwesomeIcon(string $iconClass): bool
    {
        $faPattern = '/^(fa[srlt]?\s+)?fa-[\w-]+/i';
        return preg_match($faPattern, $iconClass) === 1;
    }

    /**
     * Check if icon class represents an SVG icon
     *
     * @param string $iconClass Icon class string
     * @return bool True if SVG icon
     * @since 2.0.0
     */
    private function isSvgIcon(string $iconClass): bool
    {
        return strpos($iconClass, 'svg-') === 0 || strpos($iconClass, '.svg') !== false;
    }

    /**
     * Check if icon class represents an image icon
     *
     * @param string $iconClass Icon class string
     * @return bool True if image icon
     * @since 2.0.0
     */
    private function isImageIcon(string $iconClass): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $extension = pathinfo($iconClass, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), $imageExtensions, true);
    }

    /**
     * Check if icon class represents a unicode symbol
     *
     * @param string $iconClass Icon class string
     * @return bool True if unicode icon
     * @since 2.0.0
     */
    private function isUnicodeIcon(string $iconClass): bool
    {
        return strpos($iconClass, '&#') === 0 || strpos($iconClass, '\u') === 0;
    }

    /**
     * Render icon based on detected type
     *
     * @param string $iconType Icon type identifier
     * @param string $iconClass Icon class string
     * @param array<string, mixed> $options Rendering options
     * @return string Rendered icon HTML
     * @since 2.0.0
     */
    private function renderIconByType(string $iconType, string $iconClass, array $options): string
    {
        switch ($iconType) {
            case 'fontawesome':
                return $this->renderFontAwesomeIcon($iconClass, $options);
                
            case 'svg':
                return $this->renderSvgIcon($iconClass, $options);
                
            case 'image':
                return $this->renderImageIcon($iconClass, $options);
                
            case 'unicode':
                return $this->renderUnicodeIcon($iconClass, $options);
                
            case 'custom':
            default:
                return $this->renderCustomIcon($iconClass, $options);
        }
    }

    /**
     * Render FontAwesome icon
     *
     * @param string $iconClass FontAwesome class string
     * @param array<string, mixed> $options Rendering options
     * @return string FontAwesome icon HTML
     * @since 2.0.0
     */
    private function renderFontAwesomeIcon(string $iconClass, array $options): string
    {
        // Ensure proper FontAwesome prefix
        if (!preg_match('/^fa[srlt]?\s/', $iconClass)) {
            $prefix = $this->iconConfig['fontawesome_prefix'];
            $iconClass = "{$prefix} {$iconClass}";
        }

        // Sanitize class
        $iconClass = $this->sanitizeCssClass($iconClass);
        
        // Add additional classes from options
        $additionalClasses = $options['additional_classes'] ?? '';
        if (!empty($additionalClasses)) {
            $iconClass .= ' ' . $this->sanitizeCssClass($additionalClasses);
        }

        // Build attributes
        $attributes = [
            'class' => $iconClass,
            'aria-hidden' => 'true',
        ];

        // Add custom attributes
        if (!empty($options['attributes'])) {
            $attributes = array_merge($attributes, $options['attributes']);
        }

        return $this->buildIconElement('i', '', $attributes);
    }

    /**
     * Render custom SVG icon
     *
     * @param string $iconClass SVG class or path
     * @param array<string, mixed> $options Rendering options
     * @return string SVG icon HTML
     * @since 2.0.0
     */
    private function renderSvgIcon(string $iconClass, array $options): string
    {
        // Check if it's a file path
        if (strpos($iconClass, '.svg') !== false) {
            return $this->renderSvgFromFile($iconClass, $options);
        }

        // Handle predefined SVG icons
        return $this->renderPredefinedSvg($iconClass, $options);
    }

    /**
     * Render SVG from file
     *
     * @param string $filePath SVG file path
     * @param array<string, mixed> $options Rendering options
     * @return string SVG HTML
     * @since 2.0.0
     */
    private function renderSvgFromFile(string $filePath, array $options): string
    {
        // Construct full path
        $svgPath = $this->iconConfig['custom_svg_path'];
        if (!empty($svgPath) && !path_is_absolute($filePath)) {
            $filePath = trailingslashit($svgPath) . $filePath;
        }

        // Security check - ensure file is within allowed directory
        if (!$this->isAllowedSvgPath($filePath)) {
            return $this->renderFallbackIcon($options);
        }

        // Read and sanitize SVG content
        if (file_exists($filePath)) {
            $svgContent = file_get_contents($filePath);
            if ($svgContent !== false) {
                return $this->sanitizeSvgContent($svgContent, $options);
            }
        }

        return $this->renderFallbackIcon($options);
    }

    /**
     * Render predefined SVG icon
     *
     * @param string $iconClass SVG icon class
     * @param array<string, mixed> $options Rendering options
     * @return string SVG HTML
     * @since 2.0.0
     */
    private function renderPredefinedSvg(string $iconClass, array $options): string
    {
        $predefinedSvgs = $this->getPredefinedSvgs();
        $iconKey = str_replace('svg-', '', $iconClass);

        if (isset($predefinedSvgs[$iconKey])) {
            return $this->sanitizeSvgContent($predefinedSvgs[$iconKey], $options);
        }

        return $this->renderFallbackIcon($options);
    }

    /**
     * Get predefined SVG icons
     *
     * @return array<string, string> Array of predefined SVG icons
     * @since 2.0.0
     */
    private function getPredefinedSvgs(): array
    {
        return [
            'chevron-down' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>',
            'chevron-up' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M7 14l5-5 5 5z"/></svg>',
            'chevron-right' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M10 7l5 5-5 5z"/></svg>',
            'chevron-left' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M15 7l-5 5 5 5z"/></svg>',
            'menu' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/></svg>',
            'close' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
        ];
    }

    /**
     * Render image icon
     *
     * @param string $imagePath Image file path or URL
     * @param array<string, mixed> $options Rendering options
     * @return string Image icon HTML
     * @since 2.0.0
     */
    private function renderImageIcon(string $imagePath, array $options): string
    {
        // Sanitize image URL
        $imageUrl = $this->sanitizeUrl($imagePath);
        
        if ($imageUrl === '#') {
            return $this->renderFallbackIcon($options);
        }

        // Build attributes
        $attributes = [
            'src' => $imageUrl,
            'alt' => $options['alt_text'] ?? '',
            'class' => $this->sanitizeCssClass($options['css_class'] ?? 'menu-icon-image'),
        ];

        // Add size attributes if specified
        if (!empty($options['width'])) {
            $attributes['width'] = absint($options['width']);
        }
        if (!empty($options['height'])) {
            $attributes['height'] = absint($options['height']);
        }

        // Add lazy loading if enabled
        if ($this->iconConfig['lazy_load_icons']) {
            $attributes['loading'] = 'lazy';
        }

        return $this->buildIconElement('img', '', $attributes);
    }

    /**
     * Render unicode icon
     *
     * @param string $unicode Unicode string
     * @param array<string, mixed> $options Rendering options
     * @return string Unicode icon HTML
     * @since 2.0.0
     */
    private function renderUnicodeIcon(string $unicode, array $options): string
    {
        // Convert unicode if needed
        if (strpos($unicode, '\u') === 0) {
            $unicode = json_decode('"' . $unicode . '"');
        }

        $attributes = [
            'class' => $this->sanitizeCssClass($options['css_class'] ?? 'menu-icon-unicode'),
            'aria-hidden' => 'true',
        ];

        return $this->buildIconElement('span', $this->escapeHtml($unicode), $attributes);
    }

    /**
     * Render custom icon
     *
     * @param string $iconClass Custom icon class
     * @param array<string, mixed> $options Rendering options
     * @return string Custom icon HTML
     * @since 2.0.0
     */
    private function renderCustomIcon(string $iconClass, array $options): string
    {
        // Apply custom icon filter for extensibility
        $iconHtml = apply_filters('wphelpers/icon/custom', '', $iconClass, $options);
        
        if (!empty($iconHtml)) {
            return $iconHtml;
        }

        // Default custom icon rendering
        $attributes = [
            'class' => $this->sanitizeCssClass($iconClass),
            'aria-hidden' => 'true',
        ];

        return $this->buildIconElement('i', '', $attributes);
    }

    /**
     * Render fallback icon when primary icon fails
     *
     * @param array<string, mixed> $options Rendering options
     * @return string Fallback icon HTML or empty string
     * @since 2.0.0
     */
    private function renderFallbackIcon(array $options): string
    {
        $fallbackIcon = $this->iconConfig['fallback_icon'] ?? $options['fallback_icon'] ?? null;
        
        if (empty($fallbackIcon)) {
            return '';
        }

        // Render fallback icon (prevent infinite recursion)
        $iconType = $this->detectIconType($fallbackIcon);
        return $this->renderIconByType($iconType, $fallbackIcon, $options);
    }

    /**
     * Build icon HTML element
     *
     * @param string $tag HTML tag name
     * @param string $content Element content
     * @param array<string, mixed> $attributes Element attributes
     * @return string HTML element
     * @since 2.0.0
     */
    private function buildIconElement(string $tag, string $content, array $attributes): string
    {
        $attributeString = '';
        foreach ($attributes as $name => $value) {
            if ($value !== null && $value !== '') {
                $attributeString .= sprintf(' %s="%s"', 
                    $this->escapeAttribute($name), 
                    $this->escapeAttribute((string) $value)
                );
            }
        }

        if (in_array($tag, ['img', 'br', 'hr'], true)) {
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
     * Sanitize SVG content for security
     *
     * @param string $svgContent Raw SVG content
     * @param array<string, mixed> $options Rendering options
     * @return string Sanitized SVG HTML
     * @since 2.0.0
     */
    private function sanitizeSvgContent(string $svgContent, array $options): string
    {
        // Add class and attributes to SVG
        $additionalClass = $options['css_class'] ?? '';
        if (!empty($additionalClass)) {
            $svgContent = preg_replace(
                '/(<svg[^>]*class=["\'])([^"\']*)/i',
                '$1$2 ' . $this->sanitizeCssClass($additionalClass),
                $svgContent
            );
        }

        // Use WordPress SVG sanitization if available
        if (function_exists('wp_kses')) {
            $allowedSvgTags = [
                'svg' => [
                    'class' => true, 'width' => true, 'height' => true, 
                    'viewbox' => true, 'xmlns' => true, 'fill' => true,
                    'stroke' => true, 'stroke-width' => true,
                ],
                'path' => ['d' => true, 'fill' => true, 'stroke' => true],
                'circle' => ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true],
                'rect' => ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'fill' => true],
                'line' => ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true],
                'g' => ['fill' => true, 'stroke' => true],
            ];
            
            $svgContent = wp_kses($svgContent, $allowedSvgTags);
        }

        return $svgContent;
    }

    /**
     * Check if SVG file path is allowed
     *
     * @param string $filePath SVG file path
     * @return bool True if path is allowed
     * @since 2.0.0
     */
    private function isAllowedSvgPath(string $filePath): bool
    {
        // Check if path is within allowed directory
        $allowedPath = $this->iconConfig['custom_svg_path'];
        if (!empty($allowedPath)) {
            $realPath = realpath($filePath);
            $realAllowedPath = realpath($allowedPath);
            
            if ($realPath && $realAllowedPath) {
                return strpos($realPath, $realAllowedPath) === 0;
            }
        }

        // Check file extension
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        return strtolower($extension) === 'svg';
    }

    /**
     * Generate cache key for icon
     *
     * @param string $iconClass Icon class
     * @param array<string, mixed> $options Rendering options
     * @return string Cache key
     * @since 2.0.0
     */
    private function generateIconCacheKey(string $iconClass, array $options): string
    {
        return 'icon_' . md5($iconClass . serialize($options));
    }

    /**
     * Check if icons are enabled
     *
     * @return bool True if icons are enabled
     * @since 2.0.0
     */
    private function isIconsEnabled(): bool
    {
        return $this->options->getBool('enable_icons', true);
    }

    /**
     * Configure icon settings
     *
     * @param array<string, mixed> $config Icon configuration
     * @return void
     * @since 2.0.0
     */
    protected function configureIcons(array $config): void
    {
        $this->iconConfig = array_merge($this->iconConfig, $config);
    }

    /**
     * Clear icon cache
     *
     * @return void
     * @since 2.0.0
     */
    protected function clearIconCache(): void
    {
        $this->iconCache = [];
    }
}