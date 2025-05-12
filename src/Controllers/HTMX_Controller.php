<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Controllers;

defined('ABSPATH') || exit();

/**
 * Class HTMX_Controller
 * 
 * Base controller for HTMX interactions.
 */
abstract class HTMX_Controller
{
    /**
     * @var View
     */
    protected View $view;
    
    /**
     * @var HTMX_Validator
     */
    protected HTMX_Validator $validator;
    
    /**
     * @var TransientCache
     */
    protected TransientCache $cache;
    
    /**
     * @var array
     */
    protected array $routes = [];
    
    /**
     * @var string
     */
    protected string $namespace;
    
    /**
     * HTMX_Controller constructor.
     *
     * @param View|null $view
     * @param HTMX_Validator|null $validator
     * @param TransientCache|null $cache
     */
    public function __construct(?View $view = null, ?HTMX_Validator $validator = null, ?TransientCache $cache = null)
    {
        $this->view = $view ?? new View();
        $this->validator = $validator ?? new HTMX_Validator($this->view);
        $this->cache = $cache ?? new TransientCache();
        $this->namespace = $this->getNamespace();
        
        $this->registerRoutes();
        
        // Register AJAX handlers for registered routes
        add_action('init', [$this, 'registerAjaxHandlers']);
    }
    
    /**
     * Get the controller namespace.
     *
     * @return string Controller namespace
     */
    abstract protected function getNamespace(): string;
    
    /**
     * Register controller routes.
     *
     * @return void
     */
    abstract protected function registerRoutes(): void;
    
    /**
     * Register AJAX handlers for the routes.
     *
     * @return void
     */
    public function registerAjaxHandlers(): void
    {
        foreach ($this->routes as $action => $config) {
            $handler = $config['handler'] ?? $action;
            $capability = $config['capability'] ?? null;
            $nonce_action = $config['nonce_action'] ?? $this->namespace . '_' . $action;
            $cache_enabled = $config['cache'] ?? Config::get('htmx.cache_enabled', false);
            $cache_time = $config['cache_time'] ?? Config::get('htmx.cache_time', 3600);
            
            // Register for logged in users
            add_action('wp_ajax_' . $this->namespace . '_' . $action, function() use ($handler, $capability, $nonce_action, $cache_enabled, $cache_time) {
                $this->handleRequest($handler, $capability, $nonce_action, $cache_enabled, $cache_time);
            });
            
            // Register for non-logged in users if public
            if (isset($config['public']) && $config['public']) {
                add_action('wp_ajax_nopriv_' . $this->namespace . '_' . $action, function() use ($handler, $capability, $nonce_action, $cache_enabled, $cache_time) {
                    $this->handleRequest($handler, $capability, $nonce_action, $cache_enabled, $cache_time);
                });
            }
        }
    }
    
    /**
     * Handle an AJAX request.
     *
     * @param string $handler Method to handle the request
     * @param string|null $capability Required capability
     * @param string $nonce_action Nonce action
     * @param bool $cache_enabled Whether to cache the response
     * @param int $cache_time Cache time in seconds
     * @return void
     */
    protected function handleRequest(string $handler, ?string $capability, string $nonce_action, bool $cache_enabled, int $cache_time): void
    {
        try {
            // Check if user has required capability
            if ($capability !== null && !current_user_can($capability)) {
                wp_send_json_error(['message' => __('شما مجوز لازم برای انجام این عملیات را ندارید.', 'wphelpers')], 403);
                return;
            }
            
            // Verify nonce if required
            if (Config::get('htmx.verify_nonce', true)) {
                $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
                if (!wp_verify_nonce($nonce, $nonce_action)) {
                    wp_send_json_error(['message' => __('خطای امنیتی: نانس نامعتبر است.', 'wphelpers')], 403);
                    return;
                }
            }
            
            // Check if this is an HTMX request
            $is_htmx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
            
            if (!$is_htmx && Config::get('htmx.require_htmx_header', true)) {
                wp_send_json_error(['message' => __('این درخواست باید از طریق HTMX ارسال شود.', 'wphelpers')], 400);
                return;
            }
            
            // Check if we can serve from cache
            $cache_key = null;
            
            if ($cache_enabled && isset($_SERVER['REQUEST_URI'])) {
                $cache_key = 'htmx_' . md5($_SERVER['REQUEST_URI'] . serialize($_REQUEST));
                $cached_response = $this->cache->get($cache_key);
                
                if ($cached_response !== null) {
                    echo $cached_response;
                    exit;
                }
            }
            
            // Start output buffering to capture the response
            ob_start();
            
            // Call the handler method
            if (method_exists($this, $handler)) {
                $this->$handler();
            } else {
                wp_send_json_error(['message' => __('متد مشخص شده یافت نشد.', 'wphelpers')], 404);
                return;
            }
            
            // Get the response and cache it if needed
            $response = ob_get_clean();
            
            if ($cache_enabled && $cache_key !== null) {
                $this->cache->set($cache_key, $response, $cache_time);
            }
            
            echo $response;
            exit;
            
        } catch (\Exception $e) {
            if (Config::get('htmx.debug', WP_DEBUG)) {
                wp_send_json_error([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ], 500);
            } else {
                wp_send_json_error(['message' => __('خطایی در پردازش درخواست رخ داد.', 'wphelpers')], 500);
            }
        }
    }
    
    /**
     * Add a route to the controller.
     *
     * @param string $action Action name
     * @param array $config Route configuration
     * @return self
     */
    protected function addRoute(string $action, array $config = []): self
    {
        $this->routes[$action] = $config;
        return $this;
    }
    
    /**
     * Get the AJAX URL for a route.
     *
     * @param string $action Action name
     * @param array $args Additional arguments
     * @param bool $with_nonce Whether to include a nonce
     * @return string AJAX URL
     */
    public function getRouteUrl(string $action, array $args = [], bool $with_nonce = true): string
    {
        $url_args = array_merge([
            'action' => $this->namespace . '_' . $action,
        ], $args);
        
        $url = admin_url('admin-ajax.php');
        $url = add_query_arg($url_args, $url);
        
        if ($with_nonce && isset($this->routes[$action])) {
            $nonce_action = $this->routes[$action]['nonce_action'] ?? $this->namespace . '_' . $action;
            $url = wp_nonce_url($url, $nonce_action);
        }
        
        return $url;
    }
    
    /**
     * Render a view.
     *
     * @param string $template Template name
     * @param array $data Template data
     * @return void
     */
    protected function render(string $template, array $data = []): void
    {
        echo $this->view->render($template, $data);
    }
    
    /**
     * Send a JSON response.
     *
     * @param mixed $data Response data
     * @param bool $success Whether the request was successful
     * @param int $status HTTP status code
     * @return void
     */
    protected function json($data, bool $success = true, int $status = 200): void
    {
        if ($success) {
            wp_send_json_success($data, $status);
        } else {
            wp_send_json_error($data, $status);
        }
    }
    
    /**
     * Set an HTMX response header.
     *
     * @param string $name Header name
     * @param string $value Header value
     * @return void
     */
    protected function setHtmxHeader(string $name, string $value): void
    {
        header('HX-' . $name . ': ' . $value);
    }
    
    /**
     * Trigger a client-side event.
     *
     * @param string $event Event name
     * @param array $detail Event detail
     * @return void
     */
    protected function triggerEvent(string $event, array $detail = []): void
    {
        $this->setHtmxHeader('Trigger', $event);
        
        if (!empty($detail)) {
            $this->setHtmxHeader('Trigger-Data', json_encode($detail));
        }
    }
    
    /**
     * Redirect to a URL.
     *
     * @param string $url URL to redirect to
     * @return void
     */
    protected function redirect(string $url): void
    {
        $this->setHtmxHeader('Redirect', $url);
        exit;
    }
    
    /**
     * Refresh the page.
     *
     * @return void
     */
    protected function refresh(): void
    {
        $this->setHtmxHeader('Refresh', 'true');
        exit;
    }
    
    /**
     * Reswap the element.
     *
     * @param string $method Swap method (innerHTML, outerHTML, beforebegin, afterbegin, beforeend, afterend, delete, none)
     * @return void
     */
    protected function reswap(string $method): void
    {
        $this->setHtmxHeader('Reswap', $method);
    }
    
    /**
     * Retarget the element.
     *
     * @param string $selector CSS selector
     * @return void
     */
    protected function retarget(string $selector): void
    {
        $this->setHtmxHeader('Retarget', $selector);
    }
    
    /**
     * Get a request parameter.
     *
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed Parameter value
     */
    protected function getParam(string $key, $default = null)
    {
        $value = $_REQUEST[$key] ?? $default;
        return $value;
    }
    
    /**
     * Get all request parameters.
     *
     * @return array Request parameters
     */
    protected function getAllParams(): array
    {
        return $_REQUEST;
    }
    
    /**
     * Get a sanitized request parameter.
     *
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @param string $filter Filter type (text, email, url, int, float)
     * @return mixed Sanitized parameter value
     */
    protected function getSanitizedParam(string $key, $default = null, string $filter = 'text')
    {
        $value = $this->getParam($key, $default);
        
        if ($value === null) {
            return $default;
        }
        
        switch ($filter) {
            case 'email':
                return sanitize_email($value);
            case 'url':
                return sanitize_url($value);
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Check if the request is an HTMX request.
     *
     * @return bool True if HTMX request, false otherwise
     */
    protected function isHtmxRequest(): bool
    {
        return isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
    }
    
    /**
     * Check if the request is a boosted HTMX request.
     *
     * @return bool True if boosted HTMX request, false otherwise
     */
    protected function isBoosted(): bool
    {
        return isset($_SERVER['HTTP_HX_BOOSTED']) && $_SERVER['HTTP_HX_BOOSTED'] === 'true';
    }
    
    /**
     * Get the target element ID.
     *
     * @return string|null Target element ID
     */
    protected function getTarget(): ?string
    {
        return $_SERVER['HTTP_HX_TARGET'] ?? null;
    }
    
    /**
     * Get the trigger element ID.
     *
     * @return string|null Trigger element ID
     */
    protected function getTrigger(): ?string
    {
        return $_SERVER['HTTP_HX_TRIGGER'] ?? null;
    }
    
    /**
     * Get the trigger name.
     *
     * @return string|null Trigger name
     */
    protected function getTriggerName(): ?string
    {
        return $_SERVER['HTTP_HX_TRIGGER_NAME'] ?? null;
    }
    
    /**
     * Get the current URL.
     *
     * @return string|null Current URL
     */
    protected function getCurrentUrl(): ?string
    {
        return $_SERVER['HTTP_HX_CURRENT_URL'] ?? null;
    }
}
