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
 * SecurityTrait - Centralized security measures for navigation walkers
 *
 * Provides comprehensive security functions including input sanitization,
 * output escaping, nonce validation, and XSS prevention. This trait ensures
 * all walker implementations follow consistent security practices.
 *
 * Security Features:
 * - Input sanitization (URLs, CSS classes, HTML content)
 * - Output escaping (HTML attributes, JavaScript data)
 * - Nonce validation for AJAX requests
 * - XSS prevention through content filtering
 * - CSRF protection mechanisms
 * - Content Security Policy helpers
 *
 * @package jamal13647850\wphelpers\Navigation\Traits
 * @since 2.0.0
 * @author Sayyed Jamal Ghasemi
 */
trait SecurityTrait
{
    /**
     * Cache for sanitized values to improve performance
     * @var array<string, mixed>
     */
    private array $sanitizationCache = [];

    /**
     * Security configuration options
     * @var array<string, mixed>
     */
    private array $securityConfig = [
        'enable_cache' => true,
        'strict_mode' => false,
        'allowed_protocols' => ['http', 'https', 'mailto', 'tel'],
        'max_url_length' => 2000,
        'max_class_length' => 200,
    ];

    /**
     * Sanitize URL with enhanced validation
     *
     * Provides comprehensive URL sanitization with protocol validation,
     * length limits, and malicious pattern detection.
     *
     * @param string $url Raw URL to sanitize
     * @param array<string> $allowedProtocols Allowed URL protocols
     * @return string Sanitized URL or '#' for invalid URLs
     * @since 2.0.0
     */
    protected function sanitizeUrl(string $url, array $allowedProtocols = []): string
    {
        // Use cached result if available
        $cacheKey = 'url_' . md5($url . serialize($allowedProtocols));
        if ($this->securityConfig['enable_cache'] && isset($this->sanitizationCache[$cacheKey])) {
            return $this->sanitizationCache[$cacheKey];
        }

        // Basic sanitization
        $url = trim($url);
        
        // Return safe default for empty URLs
        if (empty($url)) {
            return $this->cacheResult($cacheKey, '#');
        }

        // Check URL length
        if (strlen($url) > $this->securityConfig['max_url_length']) {
            return $this->cacheResult($cacheKey, '#');
        }

        // Use provided protocols or default
        $protocols = !empty($allowedProtocols) ? $allowedProtocols : $this->securityConfig['allowed_protocols'];

        // WordPress core sanitization
        $sanitized = esc_url_raw($url, $protocols);

        // Additional validation in strict mode
        if ($this->securityConfig['strict_mode']) {
            $sanitized = $this->strictUrlValidation($sanitized);
        }

        // Validate the result
        if (empty($sanitized) || !filter_var($sanitized, FILTER_VALIDATE_URL) && !$this->isRelativeUrl($sanitized)) {
            $sanitized = '#';
        }

        return $this->cacheResult($cacheKey, $sanitized);
    }

    /**
     * Perform strict URL validation
     *
     * Additional security checks for strict mode operation.
     *
     * @param string $url URL to validate
     * @return string Validated URL or '#' if invalid
     * @since 2.0.0
     */
    private function strictUrlValidation(string $url): string
    {
        // Block suspicious patterns
        $suspiciousPatterns = [
            '/javascript:/i',
            '/data:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i', // Event handlers
            '/<script/i',
            '/&#/i', // HTML entities that might hide malicious code
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return '#';
            }
        }

        return $url;
    }

    /**
     * Check if URL is relative (internal)
     *
     * @param string $url URL to check
     * @return bool True if URL is relative
     * @since 2.0.0
     */
    private function isRelativeUrl(string $url): bool
    {
        return !preg_match('/^https?:\/\//', $url) && !preg_match('/^\/\//', $url);
    }

    /**
     * Sanitize CSS class with validation
     *
     * Ensures CSS classes are safe for HTML output and don't contain
     * malicious or invalid characters.
     *
     * @param string|array<string> $classes CSS classes to sanitize
     * @return string Sanitized CSS class string
     * @since 2.0.0
     */
    protected function sanitizeCssClass($classes): string
    {
        if (is_array($classes)) {
            $classes = implode(' ', $classes);
        }

        $cacheKey = 'css_' . md5((string) $classes);
        if ($this->securityConfig['enable_cache'] && isset($this->sanitizationCache[$cacheKey])) {
            return $this->sanitizationCache[$cacheKey];
        }

        $classString = trim((string) $classes);
        
        if (empty($classString)) {
            return $this->cacheResult($cacheKey, '');
        }

        // Check length limit
        if (strlen($classString) > $this->securityConfig['max_class_length']) {
            $classString = substr($classString, 0, $this->securityConfig['max_class_length']);
        }

        // Split into individual classes and sanitize each
        $classArray = preg_split('/\s+/', $classString);
        $sanitizedClasses = [];

        foreach ($classArray as $class) {
            $sanitized = sanitize_html_class($class);
            if (!empty($sanitized)) {
                $sanitizedClasses[] = $sanitized;
            }
        }

        $result = implode(' ', array_unique($sanitizedClasses));
        return $this->cacheResult($cacheKey, $result);
    }

    /**
     * Sanitize HTML content for menu items
     *
     * Allows safe HTML while removing potentially dangerous content.
     *
     * @param string $content HTML content to sanitize
     * @param bool $allowLinks Whether to allow link tags
     * @return string Sanitized HTML content
     * @since 2.0.0
     */
    protected function sanitizeHtmlContent(string $content, bool $allowLinks = true): string
    {
        if (empty($content)) {
            return '';
        }

        $cacheKey = 'html_' . md5($content . ($allowLinks ? '1' : '0'));
        if ($this->securityConfig['enable_cache'] && isset($this->sanitizationCache[$cacheKey])) {
            return $this->sanitizationCache[$cacheKey];
        }

        // Define allowed HTML tags and attributes
        $allowedTags = [
            'span' => ['class' => true, 'id' => true],
            'i' => ['class' => true, 'aria-hidden' => true],
            'em' => [],
            'strong' => [],
            'b' => [],
        ];

        if ($allowLinks) {
            $allowedTags['a'] = [
                'href' => true,
                'title' => true,
                'class' => true,
                'target' => true,
                'rel' => true,
                'aria-label' => true,
            ];
        }

        // Use WordPress KSES for sanitization
        $sanitized = wp_kses($content, $allowedTags);

        return $this->cacheResult($cacheKey, $sanitized);
    }

    /**
     * Escape HTML attributes safely
     *
     * Ensures HTML attributes are properly escaped to prevent XSS attacks.
     *
     * @param string $value Attribute value to escape
     * @return string Escaped attribute value
     * @since 2.0.0
     */
    protected function escapeAttribute(string $value): string
    {
        return esc_attr(trim($value));
    }

    /**
     * Escape HTML content safely
     *
     * @param string $content HTML content to escape
     * @return string Escaped HTML content
     * @since 2.0.0
     */
    protected function escapeHtml(string $content): string
    {
        return esc_html(trim($content));
    }

    /**
     * Escape JavaScript data safely
     *
     * Prepares data for safe inclusion in JavaScript code.
     *
     * @param mixed $data Data to escape for JavaScript
     * @return string JSON-encoded and escaped data
     * @since 2.0.0
     */
    protected function escapeJavaScript($data): string
    {
        return esc_js(wp_json_encode($data));
    }

    /**
     * Generate and validate nonce for AJAX requests
     *
     * Creates a nonce for protecting AJAX requests from CSRF attacks.
     *
     * @param string $action Nonce action identifier
     * @return string Generated nonce
     * @since 2.0.0
     */
    protected function generateNonce(string $action): string
    {
        $action = sanitize_key($action);
        return wp_create_nonce("wphelpers_menu_{$action}");
    }

    /**
     * Validate nonce for security
     *
     * @param string $nonce Nonce to validate
     * @param string $action Nonce action identifier
     * @return bool True if nonce is valid
     * @since 2.0.0
     */
    protected function validateNonce(string $nonce, string $action): bool
    {
        $action = sanitize_key($action);
        return wp_verify_nonce($nonce, "wphelpers_menu_{$action}") !== false;
    }

    /**
     * Sanitize menu item ID
     *
     * Ensures menu item IDs are safe integers.
     *
     * @param mixed $id Menu item ID to sanitize
     * @return int Sanitized menu item ID
     * @since 2.0.0
     */
    protected function sanitizeItemId($id): int
    {
        return absint($id);
    }

    /**
     * Sanitize menu depth value
     *
     * Ensures depth values are non-negative integers within reasonable limits.
     *
     * @param mixed $depth Depth value to sanitize
     * @param int $maxDepth Maximum allowed depth
     * @return int Sanitized depth value
     * @since 2.0.0
     */
    protected function sanitizeDepth($depth, int $maxDepth = 10): int
    {
        $depth = absint($depth);
        return min($depth, $maxDepth);
    }

    /**
     * Validate user capabilities for menu operations
     *
     * Checks if current user has permission to perform menu-related operations.
     *
     * @param string $capability Required capability
     * @return bool True if user has capability
     * @since 2.0.0
     */
    protected function validateUserCapability(string $capability = 'edit_theme_options'): bool
    {
        return current_user_can($capability);
    }

    /**
     * Log security event for monitoring
     *
     * Records security-related events for monitoring and debugging.
     *
     * @param string $event Event type
     * @param array<string, mixed> $data Event data
     * @param string $severity Event severity (info, warning, error)
     * @return void
     * @since 2.0.0
     */
    protected function logSecurityEvent(string $event, array $data = [], string $severity = 'info'): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $logData = [
            'timestamp' => current_time('mysql'),
            'event' => $event,
            'severity' => $severity,
            'user_id' => get_current_user_id(),
            'ip_address' => $this->getUserIpAddress(),
            'user_agent' => $this->getUserAgent(),
            'data' => $data,
        ];

        // Use WordPress debugging if available
        if (function_exists('error_log')) {
            error_log('WP-Helpers Security Event: ' . wp_json_encode($logData));
        }

        // Fire action for custom logging handlers
        do_action('wphelpers/security/event', $logData);
    }

    /**
     * Get user IP address safely
     *
     * @return string User IP address
     * @since 2.0.0
     */
    private function getUserIpAddress(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$key]));
                // Handle comma-separated IPs (for proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get user agent safely
     *
     * @return string Sanitized user agent
     * @since 2.0.0
     */
    private function getUserAgent(): string
    {
        return !empty($_SERVER['HTTP_USER_AGENT']) 
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) 
            : 'Unknown';
    }

    /**
     * Cache sanitization result for performance
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @return mixed Cached value
     * @since 2.0.0
     */
    private function cacheResult(string $key, $value)
    {
        if ($this->securityConfig['enable_cache']) {
            $this->sanitizationCache[$key] = $value;
        }
        return $value;
    }

    /**
     * Clear sanitization cache
     *
     * @return void
     * @since 2.0.0
     */
    protected function clearSanitizationCache(): void
    {
        $this->sanitizationCache = [];
    }

    /**
     * Configure security settings
     *
     * @param array<string, mixed> $config Security configuration
     * @return void
     * @since 2.0.0
     */
    protected function configureSecruitty(array $config): void
    {
        $this->securityConfig = array_merge($this->securityConfig, $config);
    }

    /**
     * Get security configuration
     *
     * @return array<string, mixed> Current security configuration
     * @since 2.0.0
     */
    protected function getSecurityConfig(): array
    {
        return $this->securityConfig;
    }
}