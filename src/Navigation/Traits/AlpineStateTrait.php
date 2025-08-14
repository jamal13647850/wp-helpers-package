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
 * AlpineStateTrait - Alpine.js state management for navigation walkers
 *
 * Provides comprehensive Alpine.js integration including state management,
 * event binding, reactive data, and component communication. Handles different
 * Alpine.js patterns like accordion modes, dropdown states, and mobile menu toggling.
 *
 * Features:
 * - Accordion state management (classic vs independent modes)
 * - Mobile menu toggle integration
 * - Dropdown state coordination
 * - Event binding and delegation
 * - Reactive data generation
 * - Fallback handling when Alpine.js is unavailable
 * - Performance optimizations
 *
 * @package jamal13647850\wphelpers\Navigation\Traits
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
trait AlpineStateTrait
{
    /**
     * Alpine.js state configuration
     * @var array<string, mixed>
     */
    private array $alpineConfig = [
        'enable_alpine' => true,
        'accordion_mode' => 'classic', // 'classic', 'independent', 'exclusive'
        'enable_mobile_toggle' => true,
        'enable_dropdown_states' => true,
        'state_persistence' => false,
        'transition_duration' => 300,
        'debug_alpine' => false,
    ];

    /**
     * Cache for generated Alpine.js data
     * @var array<string, string>
     */
    private array $alpineDataCache = [];

    /**
     * Track registered Alpine.js components
     * @var array<string, bool>
     */
    private array $registeredComponents = [];

    /**
     * Generate Alpine.js x-data attribute for menu container
     *
     * Creates the initial state object that Alpine.js will use for reactivity.
     * Supports different accordion modes and state management patterns.
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $customData Additional state data
     * @return string Alpine.js x-data attribute value
     * @since 2.0.0
     */
    protected function generateAlpineData($context, array $customData = []): string
    {
        if (!$this->isAlpineEnabled()) {
            return '';
        }

        $cacheKey = $this->generateAlpineCacheKey($context, $customData);
        if (isset($this->alpineDataCache[$cacheKey])) {
            return $this->alpineDataCache[$cacheKey];
        }

        $stateData = $this->buildAlpineStateData($context, $customData);
        $dataString = $this->formatAlpineData($stateData);

        // Cache the result
        $this->alpineDataCache[$cacheKey] = $dataString;

        return $dataString;
    }

    /**
     * Build Alpine.js state data object
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $customData Additional state data
     * @return array<string, mixed> State data object
     * @since 2.0.0
     */
    private function buildAlpineStateData($context, array $customData): array
    {
        $mode = $this->alpineConfig['accordion_mode'];
        $maxDepth = $context->getOptions()->getInt('max_depth', 5);

        $stateData = [
            // Core navigation state
            'mobileMenuOpen' => false,
            'activeDepth' => 0,
            'isInitialized' => false,

            // Responsive state
            'isMobile' => false,
            'isTablet' => false,
            'isDesktop' => true,

            // Performance tracking
            'renderTime' => 0,
            'lastAction' => null,
        ];

        // Add accordion-specific state
        $stateData = array_merge($stateData, $this->buildAccordionState($mode, $maxDepth));

        // Add dropdown state if enabled
        if ($this->alpineConfig['enable_dropdown_states']) {
            $stateData = array_merge($stateData, $this->buildDropdownState());
        }

        // Add mobile toggle state if enabled
        if ($this->alpineConfig['enable_mobile_toggle']) {
            $stateData = array_merge($stateData, $this->buildMobileToggleState());
        }

        // Merge custom data
        $stateData = array_merge($stateData, $customData);

        // Add methods
        $stateData = array_merge($stateData, $this->buildAlpineMethods($context));

        return $stateData;
    }

    /**
     * Build accordion-specific state data
     *
     * @param string $mode Accordion mode
     * @param int $maxDepth Maximum menu depth
     * @return array<string, mixed> Accordion state data
     * @since 2.0.0
     */
    private function buildAccordionState(string $mode, int $maxDepth): array
    {
        switch ($mode) {
            case 'classic':
                // Classic mode: only one submenu open per depth level
                return [
                    'opens' => array_fill(0, $maxDepth + 1, null),
                    'accordionMode' => 'classic',
                ];

            case 'independent':
                // Independent mode: each item manages its own state
                return [
                    'itemStates' => [],
                    'accordionMode' => 'independent',
                ];

            case 'exclusive':
                // Exclusive mode: only one submenu open globally
                return [
                    'openItem' => null,
                    'accordionMode' => 'exclusive',
                ];

            default:
                return ['accordionMode' => 'classic'];
        }
    }

    /**
     * Build dropdown-specific state data
     *
     * @return array<string, mixed> Dropdown state data
     * @since 2.0.0
     */
    private function buildDropdownState(): array
    {
        return [
            'dropdownOpen' => [],
            'dropdownTimeout' => null,
            'dropdownDelay' => 300,
            'hoverIntent' => false,
        ];
    }

    /**
     * Build mobile toggle state data
     *
     * @return array<string, mixed> Mobile toggle state data
     * @since 2.0.0
     */
    private function buildMobileToggleState(): array
    {
        return [
            'mobileToggleEnabled' => true,
            'menuTransitioning' => false,
            'lastToggleTime' => 0,
            'preventBodyScroll' => true,
        ];
    }

    /**
     * Build Alpine.js methods
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @return array<string, mixed> Alpine.js methods
     * @since 2.0.0
     */
    private function buildAlpineMethods($context): array
    {
        $mode = $this->alpineConfig['accordion_mode'];

        $methods = [
            // Initialization
            'init()' => $this->generateInitMethod(),

            // Responsive detection
            'checkDevice()' => $this->generateDeviceCheckMethod(),

            // Generic toggle method
            'toggle(item, depth = 0)' => $this->generateToggleMethod($mode),

            // Mobile menu methods
            'toggleMobileMenu()' => $this->generateMobileToggleMethod(),
            'closeMobileMenu()' => $this->generateMobileCloseMethod(),

            // Utility methods
            'isOpen(item, depth = 0)' => $this->generateIsOpenMethod($mode),
            'closeAll()' => $this->generateCloseAllMethod($mode),
        ];

        // Add mode-specific methods
        switch ($mode) {
            case 'classic':
                $methods['toggleClassic(item, depth)'] = $this->generateClassicToggleMethod();
                break;

            case 'independent':
                $methods['toggleIndependent(item)'] = $this->generateIndependentToggleMethod();
                break;

            case 'exclusive':
                $methods['toggleExclusive(item)'] = $this->generateExclusiveToggleMethod();
                break;
        }

        // Add dropdown methods if enabled
        if ($this->alpineConfig['enable_dropdown_states']) {
            $methods = array_merge($methods, [
                'openDropdown(id)' => $this->generateDropdownOpenMethod(),
                'closeDropdown(id)' => $this->generateDropdownCloseMethod(),
                'handleDropdownHover(id, enter)' => $this->generateDropdownHoverMethod(),
            ]);
        }

        return $methods;
    }

    /**
     * Generate Alpine.js init method
     *
     * @return string JavaScript init method
     * @since 2.0.0
     */
    private function generateInitMethod(): string
    {
        return "
            this.checkDevice();
            this.isInitialized = true;
            this.renderTime = Date.now();
            
            // Listen for window resize
            window.addEventListener('resize', () => this.checkDevice());
            
            // Listen for escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeMobileMenu();
                    this.closeAll();
                }
            });
            
            // Prevent body scroll when mobile menu is open
            this.\$watch('mobileMenuOpen', (isOpen) => {
                if (this.preventBodyScroll) {
                    document.body.style.overflow = isOpen ? 'hidden' : '';
                }
            });
        ";
    }

    /**
     * Generate device check method
     *
     * @return string JavaScript device check method
     * @since 2.0.0
     */
    private function generateDeviceCheckMethod(): string
    {
        return "
            const width = window.innerWidth;
            this.isMobile = width < 768;
            this.isTablet = width >= 768 && width < 1024;
            this.isDesktop = width >= 1024;
            
            // Close mobile menu on desktop
            if (this.isDesktop && this.mobileMenuOpen) {
                this.closeMobileMenu();
            }
        ";
    }

    /**
     * Generate toggle method based on accordion mode
     *
     * @param string $mode Accordion mode
     * @return string JavaScript toggle method
     * @since 2.0.0
     */
    private function generateToggleMethod(string $mode): string
    {
        switch ($mode) {
            case 'classic':
                return "this.toggleClassic(item, depth);";
            case 'independent':
                return "this.toggleIndependent(item);";
            case 'exclusive':
                return "this.toggleExclusive(item);";
            default:
                return "console.warn('Unknown accordion mode: {$mode}');";
        }
    }

    /**
     * Generate classic accordion toggle method
     *
     * @return string JavaScript classic toggle method
     * @since 2.0.0
     */
    private function generateClassicToggleMethod(): string
    {
        return "
            this.lastAction = 'toggle_classic';
            this.activeDepth = depth;
            this.opens[depth] = (this.opens[depth] === item) ? null : item;
        ";
    }

    /**
     * Generate independent accordion toggle method
     *
     * @return string JavaScript independent toggle method
     * @since 2.0.0
     */
    private function generateIndependentToggleMethod(): string
    {
        return "
            this.lastAction = 'toggle_independent';
            this.itemStates[item] = !this.itemStates[item];
        ";
    }

    /**
     * Generate exclusive accordion toggle method
     *
     * @return string JavaScript exclusive toggle method
     * @since 2.0.0
     */
    private function generateExclusiveToggleMethod(): string
    {
        return "
            this.lastAction = 'toggle_exclusive';
            this.openItem = (this.openItem === item) ? null : item;
        ";
    }

    /**
     * Generate mobile menu toggle method
     *
     * @return string JavaScript mobile toggle method
     * @since 2.0.0
     */
    private function generateMobileToggleMethod(): string
    {
        return "
            this.lastAction = 'toggle_mobile';
            this.menuTransitioning = true;
            this.mobileMenuOpen = !this.mobileMenuOpen;
            this.lastToggleTime = Date.now();
            
            setTimeout(() => {
                this.menuTransitioning = false;
            }, {$this->alpineConfig['transition_duration']});
        ";
    }

    /**
     * Generate mobile menu close method
     *
     * @return string JavaScript mobile close method
     * @since 2.0.0
     */
    private function generateMobileCloseMethod(): string
    {
        return "
            if (this.mobileMenuOpen) {
                this.lastAction = 'close_mobile';
                this.mobileMenuOpen = false;
                this.menuTransitioning = true;
                
                setTimeout(() => {
                    this.menuTransitioning = false;
                }, {$this->alpineConfig['transition_duration']});
            }
        ";
    }

    /**
     * Generate isOpen check method
     *
     * @param string $mode Accordion mode
     * @return string JavaScript isOpen method
     * @since 2.0.0
     */
    private function generateIsOpenMethod(string $mode): string
    {
        switch ($mode) {
            case 'classic':
                return "return this.opens[depth] === item;";
            case 'independent':
                return "return !!this.itemStates[item];";
            case 'exclusive':
                return "return this.openItem === item;";
            default:
                return "return false;";
        }
    }

    /**
     * Generate close all method
     *
     * @param string $mode Accordion mode
     * @return string JavaScript close all method
     * @since 2.0.0
     */
    private function generateCloseAllMethod(string $mode): string
    {
        switch ($mode) {
            case 'classic':
                return "this.opens = this.opens.map(() => null);";
            case 'independent':
                return "this.itemStates = {};";
            case 'exclusive':
                return "this.openItem = null;";
            default:
                return "";
        }
    }

    /**
     * Generate dropdown open method
     *
     * @return string JavaScript dropdown open method
     * @since 2.0.0
     */
    private function generateDropdownOpenMethod(): string
    {
        return "
            if (this.dropdownTimeout) {
                clearTimeout(this.dropdownTimeout);
                this.dropdownTimeout = null;
            }
            this.dropdownOpen[id] = true;
        ";
    }

    /**
     * Generate dropdown close method
     *
     * @return string JavaScript dropdown close method
     * @since 2.0.0
     */
    private function generateDropdownCloseMethod(): string
    {
        return "
            this.dropdownTimeout = setTimeout(() => {
                this.dropdownOpen[id] = false;
                this.dropdownTimeout = null;
            }, this.dropdownDelay);
        ";
    }

    /**
     * Generate dropdown hover method
     *
     * @return string JavaScript dropdown hover method
     * @since 2.0.0
     */
    private function generateDropdownHoverMethod(): string
    {
        return "
            if (enter) {
                this.hoverIntent = true;
                this.openDropdown(id);
            } else {
                this.hoverIntent = false;
                this.closeDropdown(id);
            }
        ";
    }

    /**
     * Format Alpine.js data for HTML attribute
     *
     * @param array<string, mixed> $data State data
     * @return string Formatted Alpine.js data string
     * @since 2.0.0
     */
    private function formatAlpineData(array $data): string
    {
        $formattedData = [];

        foreach ($data as $key => $value) {
            if (strpos($key, '()') !== false) {
                // Method definition
                $formattedData[] = $key . ' { ' . trim($value) . ' }';
            } else {
                // Data property
                $formattedData[] = $key . ': ' . $this->formatAlpineValue($value);
            }
        }

        return '{ ' . implode(', ', $formattedData) . ' }';
    }

    /**
     * Format individual Alpine.js value
     *
     * @param mixed $value Value to format
     * @return string Formatted value
     * @since 2.0.0
     */
    private function formatAlpineValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                // Indexed array
                $items = array_map([$this, 'formatAlpineValue'], $value);
                return '[' . implode(', ', $items) . ']';
            } else {
                // Associative array
                $items = [];
                foreach ($value as $k => $v) {
                    $items[] = $this->escapeJavaScript($k) . ': ' . $this->formatAlpineValue($v);
                }
                return '{' . implode(', ', $items) . '}';
            }
        }

        return $this->escapeJavaScript((string) $value);
    }

    /**
     * Generate Alpine.js click handler
     *
     * @param string $action Action to perform
     * @param array<string, mixed> $params Action parameters
     * @return string Alpine.js click handler
     * @since 2.0.0
     */
    protected function generateClickHandler(string $action, array $params = []): string
    {
        if (!$this->isAlpineEnabled()) {
            return '';
        }

        $paramString = '';
        if (!empty($params)) {
            $paramValues = array_map([$this, 'formatAlpineValue'], $params);
            $paramString = ', ' . implode(', ', $paramValues);
        }

        return sprintf('@click="%s(%s)"', $action, ltrim($paramString, ', '));
    }

    /**
     * Generate Alpine.js binding attribute
     *
     * @param string $attribute HTML attribute to bind
     * @param string $expression Alpine.js expression
     * @return string Alpine.js binding attribute
     * @since 2.0.0
     */
    protected function generateBinding(string $attribute, string $expression): string
    {
        if (!$this->isAlpineEnabled()) {
            return '';
        }

        return sprintf('x-bind:%s="%s"', $attribute, $this->escapeAttribute($expression));
    }

    /**
     * Generate Alpine.js show directive
     *
     * @param string $condition Show condition
     * @return string Alpine.js show directive
     * @since 2.0.0
     */
    protected function generateShowDirective(string $condition): string
    {
        if (!$this->isAlpineEnabled()) {
            return 'style="display: none;"';
        }

        return sprintf('x-show="%s"', $this->escapeAttribute($condition));
    }

    /**
     * Generate Alpine.js transition
     *
     * @param array<string, mixed> $options Transition options
     * @return string Alpine.js transition directives
     * @since 2.0.0
     */
    protected function generateTransition(array $options = []): string
    {
        if (!$this->isAlpineEnabled()) {
            return '';
        }

        $duration = $options['duration'] ?? $this->alpineConfig['transition_duration'];
        $type = $options['type'] ?? 'slide';

        $directives = [
            'x-transition:enter="transition ease-out duration-' . $duration . '"',
            'x-transition:enter-start="opacity-0 transform scale-95"',
            'x-transition:enter-end="opacity-100 transform scale-100"',
            'x-transition:leave="transition ease-in duration-' . $duration . '"',
            'x-transition:leave-start="opacity-100 transform scale-100"',
            'x-transition:leave-end="opacity-0 transform scale-95"',
        ];

        return implode(' ', $directives);
    }

    /**
     * Check if Alpine.js is enabled
     *
     * @return bool True if Alpine.js is enabled
     * @since 2.0.0
     */
    private function isAlpineEnabled(): bool
    {
        return $this->alpineConfig['enable_alpine'] && !$this->isAlpineDisabledByUser();
    }

    /**
     * Check if Alpine.js is disabled by user preference
     *
     * @return bool True if disabled by user
     * @since 2.0.0
     */
    private function isAlpineDisabledByUser(): bool
    {
        return apply_filters('wphelpers/alpine/disable', false);
    }

    /**
     * Generate cache key for Alpine.js data
     *
     * @param \jamal13647850\wphelpers\Navigation\ValueObjects\RenderContext $context Rendering context
     * @param array<string, mixed> $customData Custom state data
     * @return string Cache key
     * @since 2.0.0
     */
    private function generateAlpineCacheKey($context, array $customData): string
    {
        return 'alpine_' . md5(serialize([
            $context->getWalkerType(),
            $this->alpineConfig,
            $customData,
        ]));
    }

    /**
     * Configure Alpine.js settings
     *
     * @param array<string, mixed> $config Alpine.js configuration
     * @return void
     * @since 2.0.0
     */
    protected function configureAlpine(array $config): void
    {
        $this->alpineConfig = array_merge($this->alpineConfig, $config);
    }

    /**
     * Clear Alpine.js cache
     *
     * @return void
     * @since 2.0.0
     */
    protected function clearAlpineCache(): void
    {
        $this->alpineDataCache = [];
    }

    /**
     * Register Alpine.js component
     *
     * @param string $name Component name
     * @return void
     * @since 2.0.0
     */
    protected function registerAlpineComponent(string $name): void
    {
        $this->registeredComponents[$name] = true;
    }

    /**
     * Check if Alpine.js component is registered
     *
     * @param string $name Component name
     * @return bool True if registered
     * @since 2.0.0
     */
    protected function isAlpineComponentRegistered(string $name): bool
    {
        return isset($this->registeredComponents[$name]);
    }
}