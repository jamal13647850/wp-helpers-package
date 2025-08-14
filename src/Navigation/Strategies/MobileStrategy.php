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
 * MobileStrategy - Mobile navigation walker with accordion functionality
 *
 * Specialized walker for mobile navigation featuring vertical accordion layout,
 * touch-friendly interactions, and optimized mobile user experience.
 *
 * Features:
 * - Vertical accordion layout
 * - Touch-friendly interaction
 * - Accordion state management (classic/independent modes)
 * - Swipe gesture support
 * - Optimized for small screens
 * - Alpine.js state management
 *
 * @package jamal13647850\wphelpers\Navigation\Strategies
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
final class MobileStrategy extends AbstractWalker
{
    use SecurityTrait;
    use IconHandlerTrait;
    use AlpineStateTrait;
    use AccessibilityTrait;
    use MenuItemRendererTrait;
    use CacheableTrait;

    /**
     * Mobile-specific configuration
     * @var array<string, mixed>
     */
    private array $mobileConfig = [
        'layout' => 'vertical',
        'accordion_mode' => 'classic', // 'classic', 'independent', 'exclusive'
        'touch_enabled' => true,
        'swipe_enabled' => false,
        'animation_duration' => 300,
        'collapse_on_click' => true,
        'show_parent_links' => true,
        'max_touch_targets' => 44, // minimum touch target size in pixels
    ];

    /**
     * Parent stack for accordion state management
     * @var array<int, int>
     */
    private array $parentStack = [];

    /**
     * Get walker-specific default options
     *
     * @return array<string, mixed> Default options for mobile walker
     * @since 2.0.0
     */
    protected function getDefaultOptions(): array
    {
        return [
            // CSS Classes
            'menu_class' => 'mobile-menu vertical-menu',
            'menu_item_class' => 'mobile-menu-item',
            'submenu_class' => 'mobile-submenu',
            'link_class' => 'mobile-menu-link',
            'submenu_link_class' => 'mobile-submenu-link',
            'toggle_class' => 'mobile-menu-toggle',
            'active_class' => 'active current-menu-item',
            'expanded_class' => 'expanded',

            // Behavior
            'max_depth' => 5,
            'enable_icons' => true,
            'enable_accordion' => true,
            'accordion_mode' => 'classic',
            'show_indicators' => true,
            'enable_touch' => true,
            'close_siblings' => true,

            // Alpine.js
            'enable_alpine' => true,
            'alpine_scope' => 'mobileMenu',

            // Accessibility
            'enable_aria' => true,
            'enable_keyboard_nav' => true,
            'focus_indicators' => true,

            // Performance
            'enable_caching' => true,
            'cache_ttl' => 1800, // 30 minutes for mobile (changes more frequently)
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
        return 'mobile';
    }

    /**
     * Initialize mobile walker
     *
     * @return void
     * @since 2.0.0
     */
    protected function initializeWalker(): void
    {
        // Configure mobile-specific settings
        $this->configureMobileSettings();

        // Configure Alpine.js for accordion behavior
        $accordionMode = $this->options->getString('accordion_mode', 'classic');
        $this->configureAlpine([
            'enable_alpine' => $this->options->getBool('enable_alpine'),
            'accordion_mode' => $accordionMode,
            'transition_duration' => $this->mobileConfig['animation_duration'],
        ]);

        // Configure accessibility for mobile navigation
        $this->configureAccessibility([
            'enable_aria' => $this->options->getBool('enable_aria'),
            'enable_keyboard_nav' => $this->options->getBool('enable_keyboard_nav'),
            'wcag_level' => 'AA',
        ]);

        // Configure caching with shorter TTL for mobile
        $this->configureCache([
            'enable_caching' => $this->options->getBool('enable_caching'),
            'cache_ttl' => $this->options->getInt('cache_ttl'),
            'enable_fragment_cache' => true,
        ]);
    }

    /**
     * Start outputting submenu container for mobile
     *
     * @param string $output Reference to the output string
     * @param int $depth Current menu depth
     * @param mixed $args Menu arguments
     * @return void
     * @since 2.0.0
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void
    {
        if ($depth >= $this->options->getInt('max_depth', 5)) {
            return;
        }

        $indent = str_repeat("\t", $depth + 1);
        $parentId = $this->parentStack[$depth] ?? null;
        
        if (!$parentId) {
            return;
        }

        // Generate submenu container
        $submenuId = 'mobile-submenu-' . $parentId;
        $submenuClass = $this->getSubmenuClass($depth);
        
        // Alpine.js bindings for accordion behavior
        $accordionBindings = $this->generateAccordionBindings($parentId, $depth);
        
        // Accessibility attributes
        $ariaAttributes = $this->generateSubmenuAriaAttributes(
            $this->context->getParent($depth - 1),
            $this->context
        );

        $output .= "\n{$indent}<div class=\"{$submenuClass}\" id=\"{$submenuId}\" {$accordionBindings} {$this->buildAttributeString($ariaAttributes)}>\n";
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
        if ($depth >= $this->options->getInt('max_depth', 5)) {
            return;
        }

        $indent = str_repeat("\t", $depth + 1);
        $output .= "{$indent}</div>\n";
        
        // Clean up parent stack
        unset($this->parentStack[$depth]);
    }

    /**
     * Render individual menu item for mobile
     *
     * @param MenuItem $item Menu item to render
     * @param RenderContext $context Rendering context
     * @return string Rendered menu item HTML
     * @since 2.0.0
     */
    protected function renderMenuItem(MenuItem $item, RenderContext $context): string
    {
        // Track parent for submenu generation
        if ($item->hasChildren()) {
            $this->parentStack[$item->getDepth()] = $item->getId();
        }

        // Use caching for complex items
        if ($this->options->getBool('enable_caching')) {
            return $this->getCachedFragment(
                'mobile_item_' . $item->getId(),
                fn() => $this->renderMobileItem($item, $context),
                $context
            );
        }

        return $this->renderMobileItem($item, $context);
    }

    /**
     * Render mobile menu item without caching
     *
     * @param MenuItem $item Menu item to render
     * @param RenderContext $context Rendering context
     * @return string Rendered menu item HTML
     * @since 2.0.0
     */
    private function renderMobileItem(MenuItem $item, RenderContext $context): string
    {
        $depth = $item->getDepth();
        $indent = str_repeat("\t", $depth + 1);

        if ($item->hasChildren()) {
            // Render parent item with accordion functionality
            return $indent . $this->renderMobileParentItem($item, $context) . "\n";
        } else {
            // Render simple link item
            return $indent . $this->renderMobileSimpleItem($item, $context) . "\n";
        }
    }

    /**
     * Render mobile parent item with children
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Parent item HTML
     * @since 2.0.0
     */
    private function renderMobileParentItem(MenuItem $item, RenderContext $context): string
    {
        $containerAttrs = $this->buildMobileContainerAttributes($item, $context);
        $accordionContent = $this->buildAccordionContent($item, $context);

        return $this->buildHtmlElement('div', $accordionContent, $containerAttrs);
    }

    /**
     * Render mobile simple item (no children)
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Simple item HTML
     * @since 2.0.0
     */
    private function renderMobileSimpleItem(MenuItem $item, RenderContext $context): string
    {
        $linkAttrs = $this->buildMobileLinkAttributes($item, $context);
        $linkContent = $this->buildMobileLinkContent($item, $context);

        return $this->buildHtmlElement('a', $linkContent, $linkAttrs);
    }

    /**
     * Build accordion content for parent items
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Accordion content HTML
     * @since 2.0.0
     */
    private function buildAccordionContent(MenuItem $item, RenderContext $context): string
    {
        $content = '';

        // Accordion toggle button
        $content .= $this->renderAccordionToggle($item, $context);

        // Parent link (if enabled)
        if ($this->mobileConfig['show_parent_links']) {
            $content .= $this->renderParentLink($item, $context);
        }

        return $content;
    }

    /**
     * Render accordion toggle button
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Toggle button HTML
     * @since 2.0.0
     */
    private function renderAccordionToggle(MenuItem $item, RenderContext $context): string
    {
        $toggleAttrs = $this->buildToggleAttributes($item, $context);
        $toggleContent = $this->buildToggleContent($item, $context);

        return $this->buildHtmlElement('button', $toggleContent, $toggleAttrs);
    }

    /**
     * Render parent link for accordion items
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Parent link HTML
     * @since 2.0.0
     */
    private function renderParentLink(MenuItem $item, RenderContext $context): string
    {
        $linkAttrs = $this->buildMobileLinkAttributes($item, $context, true);
        $linkContent = $this->buildMobileLinkContent($item, $context, false); // No indicator for parent links

        return $this->buildHtmlElement('a', $linkContent, $linkAttrs);
    }

    /**
     * Build mobile container attributes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return array<string, string> Container attributes
     * @since 2.0.0
     */
    private function buildMobileContainerAttributes(MenuItem $item, RenderContext $context): array
    {
        $attributes = [];

        // Basic attributes
        $attributes['class'] = $this->buildMobileContainerClasses($item, $context);
        
        // Alpine.js data for accordion items
        if ($item->hasChildren() && $this->options->getBool('enable_alpine')) {
            $accordionMode = $this->options->getString('accordion_mode');
            if ($accordionMode === 'independent') {
                $attributes['x-data'] = '{ open: false }';
            }
        }

        return $attributes;
    }

    /**
     * Build toggle button attributes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return array<string, string> Toggle attributes
     * @since 2.0.0
     */
    private function buildToggleAttributes(MenuItem $item, RenderContext $context): array
    {
        $attributes = [
            'type' => 'button',
            'class' => $this->buildToggleClasses($item, $context),
            'aria-controls' => 'mobile-submenu-' . $item->getId(),
        ];

        // Alpine.js click handler
        if ($this->options->getBool('enable_alpine')) {
            $accordionMode = $this->options->getString('accordion_mode');
            $clickHandler = $this->generateAccordionClickHandler($item, $accordionMode);
            $attributes['@click'] = $clickHandler;
            
            // Expanded state binding
            $expandedBinding = $this->generateExpandedBinding($item, $accordionMode);
            $attributes['x-bind:aria-expanded'] = $expandedBinding;
        } else {
            // Fallback JavaScript
            $attributes['onclick'] = "wpHelpersMobile.toggle({$item->getId()})";
        }

        // Accessibility attributes
        $ariaAttrs = $this->generateItemAriaAttributes($item, $context);
        $attributes = array_merge($attributes, $ariaAttrs);

        return $attributes;
    }

    /**
     * Build toggle button content
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Toggle content HTML
     * @since 2.0.0
     */
    private function buildToggleContent(MenuItem $item, RenderContext $context): string
    {
        $content = '';

        // Icon (if enabled)
        if ($this->options->getBool('enable_icons')) {
            $content .= $this->renderIcon($item, ['position' => 'before']);
        }

        // Text
        $content .= sprintf('<span class="toggle-text">%s</span>', $this->escapeHtml($item->getTitle()));

        // Accordion indicator
        if ($this->options->getBool('show_indicators')) {
            $content .= $this->renderAccordionIndicator($item, $context);
        }

        return $content;
    }

    /**
     * Build mobile link attributes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @param bool $isParentLink Whether this is a parent link
     * @return array<string, string> Link attributes
     * @since 2.0.0
     */
    private function buildMobileLinkAttributes(MenuItem $item, RenderContext $context, bool $isParentLink = false): array
    {
        $attributes = $item->getLinkAttributes();

        // CSS classes
        $attributes['class'] = $this->buildMobileLinkClasses($item, $context, $isParentLink);

        // Touch-friendly attributes
        if ($this->mobileConfig['touch_enabled']) {
            $attributes['data-touch-target'] = 'true';
        }

        // Accessibility attributes
        if (!$isParentLink) {
            $ariaAttrs = $this->generateItemAriaAttributes($item, $context);
            $attributes = array_merge($attributes, $ariaAttrs);
        }

        return $attributes;
    }

    /**
     * Build mobile link content
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @param bool $showIndicator Whether to show indicator
     * @return string Link content HTML
     * @since 2.0.0
     */
    private function buildMobileLinkContent(MenuItem $item, RenderContext $context, bool $showIndicator = true): string
    {
        $content = '';

        // Icon (if enabled)
        if ($this->options->getBool('enable_icons')) {
            $content .= $this->renderIcon($item, ['position' => 'before']);
        }

        // Text content
        $content .= sprintf('<span class="link-text">%s</span>', $this->escapeHtml($item->getTitle()));

        return $content;
    }

    /**
     * Build mobile container CSS classes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string CSS classes
     * @since 2.0.0
     */
    private function buildMobileContainerClasses(MenuItem $item, RenderContext $context): string
    {
        $classes = [];

        // Base classes
        $classes[] = $this->options->getCssClass('menu_item_class');
        $classes[] = 'menu-item-' . $item->getId();

        // WordPress classes
        $classes = array_merge($classes, $item->getClasses());

        // State classes
        if ($item->hasChildren()) {
            $classes[] = 'has-children';
            $classes[] = 'accordion-item';
        }

        if ($item->isActive()) {
            $classes[] = $this->options->getCssClass('active_class');
        }

        // Depth classes
        $classes[] = 'depth-' . $item->getDepth();

        return $this->sanitizeCssClass(implode(' ', array_filter($classes)));
    }

    /**
     * Build toggle button CSS classes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string CSS classes
     * @since 2.0.0
     */
    private function buildToggleClasses(MenuItem $item, RenderContext $context): string
    {
        $classes = [];

        // Base toggle class
        $classes[] = $this->options->getCssClass('toggle_class');

        // Depth classes
        $classes[] = 'toggle-depth-' . $item->getDepth();

        // State classes
        if ($item->isActive()) {
            $classes[] = 'active-toggle';
        }

        return $this->sanitizeCssClass(implode(' ', array_filter($classes)));
    }

    /**
     * Build mobile link CSS classes
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @param bool $isParentLink Whether this is a parent link
     * @return string CSS classes
     * @since 2.0.0
     */
    private function buildMobileLinkClasses(MenuItem $item, RenderContext $context, bool $isParentLink = false): string
    {
        $classes = [];

        // Base link class
        if ($item->getDepth() === 0 || $isParentLink) {
            $classes[] = $this->options->getCssClass('link_class');
        } else {
            $classes[] = $this->options->getCssClass('submenu_link_class');
        }

        // State classes
        if ($item->isCurrent()) {
            $classes[] = 'current-link';
        }

        // Parent link class
        if ($isParentLink) {
            $classes[] = 'parent-link';
        }

        return $this->sanitizeCssClass(implode(' ', array_filter($classes)));
    }

    /**
     * Render accordion indicator
     *
     * @param MenuItem $item Menu item
     * @param RenderContext $context Rendering context
     * @return string Accordion indicator HTML
     * @since 2.0.0
     */
    private function renderAccordionIndicator(MenuItem $item, RenderContext $context): string
    {
        $accordionMode = $this->options->getString('accordion_mode');
        $condition = $this->getAccordionCondition($item, $accordionMode);

        if ($this->options->getBool('enable_alpine')) {
            // Alpine.js reactive indicator
            return sprintf(
                '<span class="accordion-indicator" aria-hidden="true" x-bind:class="%s ? \'expanded\' : \'collapsed\'">
                    <svg class="indicator-icon" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7 10l5 5 5-5z"/>
                    </svg>
                </span>',
                $condition
            );
        } else {
            // Static indicator
            return '<span class="accordion-indicator" aria-hidden="true">
                        <svg class="indicator-icon" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 10l5 5 5-5z"/>
                        </svg>
                    </span>';
        }
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
        return $baseClass . ' submenu-depth-' . $depth;
    }

    /**
     * Generate Alpine.js accordion bindings
     *
     * @param int $parentId Parent item ID
     * @param int $depth Menu depth
     * @return string Alpine.js bindings
     * @since 2.0.0
     */
    private function generateAccordionBindings(int $parentId, int $depth): string
    {
        if (!$this->options->getBool('enable_alpine')) {
            return 'style="display: none;"';
        }

        $accordionMode = $this->options->getString('accordion_mode');
        $condition = $this->getAccordionConditionForSubmenu($parentId, $depth, $accordionMode);
        
        $showDirective = $this->generateShowDirective($condition);
        $transition = $this->generateTransition(['duration' => $this->mobileConfig['animation_duration']]);
        
        return "{$showDirective} {$transition}";
    }

    /**
     * Generate Alpine.js click handler for accordion
     *
     * @param MenuItem $item Menu item
     * @param string $accordionMode Accordion mode
     * @return string Click handler
     * @since 2.0.0
     */
    private function generateAccordionClickHandler(MenuItem $item, string $accordionMode): string
    {
        switch ($accordionMode) {
            case 'classic':
                return "toggle({$item->getId()}, {$item->getDepth()})";
            case 'independent':
                return "open = !open";
            case 'exclusive':
                return "toggleExclusive({$item->getId()})";
            default:
                return "toggle({$item->getId()}, {$item->getDepth()})";
        }
    }

    /**
     * Generate expanded state binding
     *
     * @param MenuItem $item Menu item
     * @param string $accordionMode Accordion mode
     * @return string Expanded binding
     * @since 2.0.0
     */
    private function generateExpandedBinding(MenuItem $item, string $accordionMode): string
    {
        return $this->getAccordionCondition($item, $accordionMode) . ' ? \'true\' : \'false\'';
    }

    /**
     * Get accordion condition for item
     *
     * @param MenuItem $item Menu item
     * @param string $accordionMode Accordion mode
     * @return string Condition expression
     * @since 2.0.0
     */
    private function getAccordionCondition(MenuItem $item, string $accordionMode): string
    {
        switch ($accordionMode) {
            case 'classic':
                return "opens[{$item->getDepth()}] === {$item->getId()}";
            case 'independent':
                return "open";
            case 'exclusive':
                return "openItem === {$item->getId()}";
            default:
                return "opens[{$item->getDepth()}] === {$item->getId()}";
        }
    }

    /**
     * Get accordion condition for submenu
     *
     * @param int $parentId Parent item ID
     * @param int $depth Menu depth
     * @param string $accordionMode Accordion mode
     * @return string Condition expression
     * @since 2.0.0
     */
    private function getAccordionConditionForSubmenu(int $parentId, int $depth, string $accordionMode): string
    {
        switch ($accordionMode) {
            case 'classic':
                return "opens[" . ($depth - 1) . "] === {$parentId}";
            case 'independent':
                return "open";
            case 'exclusive':
                return "openItem === {$parentId}";
            default:
                return "opens[" . ($depth - 1) . "] === {$parentId}";
        }
    }

    /**
     * Configure mobile-specific settings
     *
     * @return void
     * @since 2.0.0
     */
    private function configureMobileSettings(): void
    {
        // Merge mobile config with options
        $mobileOptions = [
            'accordion_mode' => $this->options->getString('accordion_mode', 'classic'),
            'touch_enabled' => $this->options->getBool('enable_touch', true),
            'animation_duration' => $this->options->getInt('animation_duration', 300),
            'show_parent_links' => $this->options->getBool('show_parent_links', true),
        ];

        $this->mobileConfig = array_merge($this->mobileConfig, $mobileOptions);
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
            'supports_accordion' => true,
            'supports_touch' => true,
            'supports_keyboard_nav' => true,
            'max_depth' => $this->options->getInt('max_depth', 5),
            'layout_types' => ['vertical'],
            'accordion_modes' => ['classic', 'independent', 'exclusive'],
        ];
    }
}