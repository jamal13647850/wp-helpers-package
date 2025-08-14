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
 * AccessibilityTrait - WCAG compliance and accessibility features for navigation
 *
 * Provides comprehensive accessibility support including ARIA attributes,
 * keyboard navigation, screen reader compatibility, and WCAG 2.1 compliance.
 * Ensures navigation components are usable by all users including those
 * with disabilities.
 *
 * Features:
 * - ARIA attributes (roles, states, properties)
 * - Keyboard navigation support
 * - Screen reader compatibility
 * - Focus management and visual indicators
 * - High contrast mode support
 * - Semantic HTML structure
 * - WCAG 2.1 Level AA compliance
 *
 * @package jamal13647850\wphelpers\Navigation\Traits
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
trait AccessibilityTrait
{
    /**
     * Accessibility configuration settings
     * @var array<string, mixed>
     */
    private array $accessibilityConfig = [
        'wcag_level' => 'AA', // 'A', 'AA', 'AAA'
        'enable_aria' => true,
        'enable_keyboard_nav' => true,
        'enable_focus_indicators' => true,
        'enable_skip_links' => true,
        'enable_landmark_roles' => true,
        'high_contrast_mode' => false,
        'reduce_motion' => false,
        'screen_reader_only_text' => true,
    ];

    /**
     * ARIA landmarks and roles mapping
     * @var array<string, string>
     */
    private array $ariaLandmarks = [
        'main_nav' => 'navigation',
        'mobile_nav' => 'navigation',
        'dropdown' => 'menu',
        'submenu' => 'menu',
        'breadcrumb' => 'navigation',
        'pagination' => 'navigation',
    ];

    /**
     * Keyboard navigation key codes
     * @var array<string, int>
     */
    private array $keyboardKeys = [
        'ENTER' => 13,
        'SPACE' => 32,
        'ESCAPE' => 27,
        'ARROW_UP' => 38,
        'ARROW_DOWN' => 40,
        'ARROW_LEFT' => 37,
        'ARROW_RIGHT' => 39,
        'HOME' => 36,
        'END' => 35,
        'TAB' => 9,
    ];

    /**
     * Generate ARIA attributes for menu container
     *
     * Creates appropriate ARIA attributes based on menu type and context
     * to ensure proper accessibility semantics.
     *
     * @param string $menuType Menu type identifier
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @return array<string, string> ARIA attributes
     * @since 2.0.0
     */
    protected function generateMenuAriaAttributes(string $menuType, $context): array
    {
        if (!$this->accessibilityConfig['enable_aria']) {
            return [];
        }

        $attributes = [];

        // Set appropriate role
        $role = $this->getAriaRole($menuType);
        if ($role) {
            $attributes['role'] = $role;
        }

        // Set aria-label for identification
        $label = $this->generateAriaLabel($menuType, $context);
        if ($label) {
            $attributes['aria-label'] = $label;
        }

        // Add orientation for certain menu types
        if (in_array($menuType, ['desktop', 'horizontal'], true)) {
            $attributes['aria-orientation'] = 'horizontal';
        } elseif (in_array($menuType, ['mobile', 'vertical'], true)) {
            $attributes['aria-orientation'] = 'vertical';
        }

        // Add landmark role if enabled
        if ($this->accessibilityConfig['enable_landmark_roles']) {
            $landmark = $this->ariaLandmarks[$menuType] ?? null;
            if ($landmark) {
                $attributes['role'] = $landmark;
            }
        }

        return $attributes;
    }

    /**
     * Generate ARIA attributes for menu item
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item object
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @return array<string, string> ARIA attributes
     * @since 2.0.0
     */
    protected function generateItemAriaAttributes($item, $context): array
    {
        if (!$this->accessibilityConfig['enable_aria']) {
            return [];
        }

        $attributes = [];

        // Current page indicator
        if ($item->isCurrent()) {
            $attributes['aria-current'] = 'page';
        }

        // Expanded state for items with children
        if ($item->hasChildren()) {
            $isOpen = $this->isItemExpanded($item, $context);
            $attributes['aria-expanded'] = $isOpen ? 'true' : 'false';
            
            // Controls relationship
            $submenuId = $this->generateSubmenuId($item);
            $attributes['aria-controls'] = $submenuId;
            
            // Has popup indicator
            $attributes['aria-haspopup'] = 'true';
        }

        // Disabled state (if applicable)
        if ($this->isItemDisabled($item)) {
            $attributes['aria-disabled'] = 'true';
        }

        // Position in set for screen readers
        $position = $this->getItemPosition($item, $context);
        if ($position) {
            $attributes['aria-posinset'] = (string) $position['current'];
            $attributes['aria-setsize'] = (string) $position['total'];
        }

        // Description for complex items
        $description = $this->generateItemDescription($item);
        if ($description) {
            $descriptionId = $this->generateDescriptionId($item);
            $attributes['aria-describedby'] = $descriptionId;
        }

        return $attributes;
    }

    /**
     * Generate ARIA attributes for submenu
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $parentItem Parent menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @return array<string, string> ARIA attributes
     * @since 2.0.0
     */
    protected function generateSubmenuAriaAttributes($parentItem, $context): array
    {
        if (!$this->accessibilityConfig['enable_aria']) {
            return [];
        }

        $attributes = [
            'role' => 'menu',
            'aria-labelledby' => $this->generateItemId($parentItem),
            'id' => $this->generateSubmenuId($parentItem),
        ];

        // Hidden state management
        $isOpen = $this->isItemExpanded($parentItem, $context);
        if (!$isOpen) {
            $attributes['aria-hidden'] = 'true';
        }

        return $attributes;
    }

    /**
     * Generate keyboard navigation attributes
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item object
     * @param string $menuType Menu type
     * @return array<string, string> Keyboard navigation attributes
     * @since 2.0.0
     */
    protected function generateKeyboardAttributes($item, string $menuType): array
    {
        if (!$this->accessibilityConfig['enable_keyboard_nav']) {
            return [];
        }

        $attributes = [];

        // Tab index management
        if ($item->isTopLevel()) {
            $attributes['tabindex'] = $item->isCurrent() ? '0' : '-1';
        } else {
            $attributes['tabindex'] = '-1';
        }

        // Keyboard event handlers
        if ($item->hasChildren()) {
            $keyboardHandlers = $this->generateKeyboardHandlers($menuType);
            $attributes = array_merge($attributes, $keyboardHandlers);
        }

        return $attributes;
    }

    /**
     * Generate keyboard event handlers
     *
     * @param string $menuType Menu type
     * @return array<string, string> Keyboard event handlers
     * @since 2.0.0
     */
    private function generateKeyboardHandlers(string $menuType): array
    {
        $handlers = [];

        // Base keyboard navigation
        $handlers['onkeydown'] = $this->generateKeydownHandler($menuType);

        // Alpine.js integration if available
        if ($this->isAlpineEnabled()) {
            $handlers['@keydown'] = $this->generateAlpineKeydownHandler($menuType);
        }

        return $handlers;
    }

    /**
     * Generate keydown event handler
     *
     * @param string $menuType Menu type
     * @return string JavaScript keydown handler
     * @since 2.0.0
     */
    private function generateKeydownHandler(string $menuType): string
    {
        return sprintf(
            'wpHelpersNavigation.handleKeydown(event, "%s")',
            $this->escapeJavaScript($menuType)
        );
    }

    /**
     * Generate Alpine.js keydown handler
     *
     * @param string $menuType Menu type
     * @return string Alpine.js keydown handler
     * @since 2.0.0
     */
    private function generateAlpineKeydownHandler(string $menuType): string
    {
        $handlers = [
            'arrow-down' => 'focusNext($event)',
            'arrow-up' => 'focusPrevious($event)',
            'escape' => 'closeAndFocus($event)',
            'enter' => 'activateItem($event)',
            'space' => 'activateItem($event)',
        ];

        if ($menuType === 'desktop') {
            $handlers['arrow-right'] = 'openSubmenu($event)';
            $handlers['arrow-left'] = 'closeSubmenu($event)';
        }

        $conditions = [];
        foreach ($handlers as $key => $action) {
            $conditions[] = sprintf('$event.key === "%s" && %s', $key, $action);
        }

        return implode(' || ', $conditions);
    }

    /**
     * Generate focus management attributes
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item object
     * @return array<string, string> Focus management attributes
     * @since 2.0.0
     */
    protected function generateFocusAttributes($item): array
    {
        if (!$this->accessibilityConfig['enable_focus_indicators']) {
            return [];
        }

        $attributes = [];

        // Focus classes for styling
        $focusClasses = $this->generateFocusClasses();
        if ($focusClasses) {
            $attributes['class'] = $focusClasses;
        }

        // Focus trap for mobile menus
        if ($item->isTopLevel() && $this->isMobileMenu()) {
            $attributes['data-focus-trap'] = 'true';
        }

        return $attributes;
    }

    /**
     * Generate screen reader text
     *
     * @param string $text Text for screen readers
     * @param bool $includeWrapper Whether to include wrapper element
     * @return string Screen reader text HTML
     * @since 2.0.0
     */
    protected function generateScreenReaderText(string $text, bool $includeWrapper = true): string
    {
        if (!$this->accessibilityConfig['screen_reader_only_text']) {
            return '';
        }

        $escapedText = $this->escapeHtml($text);

        if (!$includeWrapper) {
            return $escapedText;
        }

        return sprintf(
            '<span class="sr-only screen-reader-text">%s</span>',
            $escapedText
        );
    }

    /**
     * Generate skip link
     *
     * @param string $target Target element ID
     * @param string $text Link text
     * @return string Skip link HTML
     * @since 2.0.0
     */
    protected function generateSkipLink(string $target, string $text): string
    {
        if (!$this->accessibilityConfig['enable_skip_links']) {
            return '';
        }

        return sprintf(
            '<a href="#%s" class="skip-link screen-reader-text">%s</a>',
            $this->escapeAttribute($target),
            $this->escapeHtml($text)
        );
    }

    /**
     * Generate live region for dynamic updates
     *
     * @param string $id Region ID
     * @param string $politeness Politeness level ('polite', 'assertive')
     * @return string Live region HTML
     * @since 2.0.0
     */
    protected function generateLiveRegion(string $id, string $politeness = 'polite'): string
    {
        return sprintf(
            '<div id="%s" aria-live="%s" aria-atomic="true" class="sr-only"></div>',
            $this->escapeAttribute($id),
            $this->escapeAttribute($politeness)
        );
    }

    /**
     * Get ARIA role for menu type
     *
     * @param string $menuType Menu type
     * @return string|null ARIA role
     * @since 2.0.0
     */
    private function getAriaRole(string $menuType): ?string
    {
        $roles = [
            'desktop' => 'menubar',
            'mobile' => 'menu',
            'dropdown' => 'menu',
            'submenu' => 'menu',
            'breadcrumb' => 'navigation',
            'pagination' => 'navigation',
        ];

        return $roles[$menuType] ?? 'menu';
    }

    /**
     * Generate ARIA label for menu
     *
     * @param string $menuType Menu type
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @return string ARIA label
     * @since 2.0.0
     */
    private function generateAriaLabel(string $menuType, $context): string
    {
        $labels = [
            'desktop' => __('منوی اصلی', 'wphelpers'),
            'mobile' => __('منوی موبایل', 'wphelpers'),
            'dropdown' => __('منوی کشویی', 'wphelpers'),
            'submenu' => __('زیرمنو', 'wphelpers'),
            'breadcrumb' => __('مسیر صفحه', 'wphelpers'),
            'pagination' => __('صفحه‌بندی', 'wphelpers'),
        ];

        $baseLabel = $labels[$menuType] ?? __('منوی ناوبری', 'wphelpers');

        // Add context if available
        $themeLocation = $context->getCustomData('theme_location');
        if ($themeLocation) {
            $baseLabel .= sprintf(' (%s)', $themeLocation);
        }

        return $baseLabel;
    }

    /**
     * Check if item is expanded
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @return bool True if item is expanded
     * @since 2.0.0
     */
    private function isItemExpanded($item, $context): bool
    {
        // Check Alpine.js state if available
        if ($this->isAlpineEnabled()) {
            return $context->isSubmenuOpen($item->getDepth(), $item->getId());
        }

        // Fallback to CSS-based detection
        return $item->hasClass('expanded') || $item->hasClass('open');
    }

    /**
     * Check if item is disabled
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @return bool True if item is disabled
     * @since 2.0.0
     */
    private function isItemDisabled($item): bool
    {
        return $item->hasClass('disabled') || $item->getUrl() === '#';
    }

    /**
     * Get item position in menu
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @return array<string, int>|null Position information
     * @since 2.0.0
     */
    private function getItemPosition($item, $context): ?array
    {
        $customData = $context->getCustomData('menu_positions');
        if (!is_array($customData)) {
            return null;
        }

        $itemId = $item->getId();
        return $customData[$itemId] ?? null;
    }

    /**
     * Generate item description
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @return string Item description
     * @since 2.0.0
     */
    private function generateItemDescription($item): string
    {
        $description = $item->getDescription();
        
        if (empty($description) && $item->hasChildren()) {
            $childCount = $this->getChildrenCount($item);
            $description = sprintf(
                _n('دارای %d زیرمنو', 'دارای %d زیرمنو', $childCount, 'wphelpers'),
                $childCount
            );
        }

        return $description;
    }

    /**
     * Get children count for menu item
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @return int Children count
     * @since 2.0.0
     */
    private function getChildrenCount($item): int
    {
        // This would need to be implemented based on your menu structure
        // For now, return a default value
        return $item->hasChildren() ? 1 : 0;
    }

    /**
     * Generate unique ID for menu item
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @return string Unique ID
     * @since 2.0.0
     */
    private function generateItemId($item): string
    {
        return sprintf('menu-item-%d', $item->getId());
    }

    /**
     * Generate unique ID for submenu
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Parent menu item
     * @return string Unique submenu ID
     * @since 2.0.0
     */
    private function generateSubmenuId($item): string
    {
        return sprintf('submenu-%d', $item->getId());
    }

    /**
     * Generate unique ID for description
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\MenuItem $item Menu item
     * @return string Unique description ID
     * @since 2.0.0
     */
    private function generateDescriptionId($item): string
    {
        return sprintf('menu-desc-%d', $item->getId());
    }

    /**
     * Generate focus indicator CSS classes
     *
     * @return string Focus CSS classes
     * @since 2.0.0
     */
    private function generateFocusClasses(): string
    {
        $classes = [
            'focus:outline-none',
            'focus:ring-2',
            'focus:ring-primary',
            'focus:ring-offset-2',
        ];

        // High contrast mode
        if ($this->accessibilityConfig['high_contrast_mode']) {
            $classes[] = 'focus:ring-black';
            $classes[] = 'focus:bg-yellow-200';
        }

        return implode(' ', $classes);
    }

    /**
     * Check if current context is mobile menu
     *
     * @return bool True if mobile menu
     * @since 2.0.0
     */
    private function isMobileMenu(): bool
    {
        return isset($this->context) && 
               $this->context->getWalkerType() === 'mobile';
    }

    /**
     * Check if Alpine.js is enabled
     *
     * @return bool True if Alpine.js is enabled
     * @since 2.0.0
     */
    private function isAlpineEnabled(): bool
    {
        return method_exists($this, 'isAlpineEnabled') && 
               parent::isAlpineEnabled();
    }

    /**
     * Configure accessibility settings
     *
     * @param array<string, mixed> $config Accessibility configuration
     * @return void
     * @since 2.0.0
     */
    protected function configureAccessibility(array $config): void
    {
        $this->accessibilityConfig = array_merge($this->accessibilityConfig, $config);
    }

    /**
     * Get accessibility configuration
     *
     * @return array<string, mixed> Current accessibility configuration
     * @since 2.0.0
     */
    protected function getAccessibilityConfig(): array
    {
        return $this->accessibilityConfig;
    }

    /**
     * Validate WCAG compliance level
     *
     * @param string $level WCAG level to validate against
     * @return bool True if compliant
     * @since 2.0.0
     */
    protected function validateWcagCompliance(string $level = 'AA'): bool
    {
        $requiredFeatures = [
            'A' => ['enable_aria', 'enable_keyboard_nav'],
            'AA' => ['enable_aria', 'enable_keyboard_nav', 'enable_focus_indicators'],
            'AAA' => ['enable_aria', 'enable_keyboard_nav', 'enable_focus_indicators', 'enable_skip_links'],
        ];

        $required = $requiredFeatures[$level] ?? [];
        
        foreach ($required as $feature) {
            if (!$this->accessibilityConfig[$feature]) {
                return false;
            }
        }

        return true;
    }
}