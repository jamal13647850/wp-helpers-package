<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Utilities;

defined('ABSPATH') || exit();

/**
 * Class HTMX_Handler
 * 
 * Handles HTMX requests.
 */
class HTMX_Handler
{
    /**
     * @var View
     */
    private View $view;
    
    /**
     * @var HTMX_Validator
     */
    private HTMX_Validator $validator;
    
    /**
     * @var TransientCache
     */
    private TransientCache $cache;
    
    /**
     * @var array
     */
    private array $endpoints = [];
    
    /**
     * @var array
     */
    private array $middleware = [];
    
    /**
     * @var string
     */
    private string $prefix;
    
    /**
     * HTMX_Handler constructor.
     *
     * @param View|null $view View instance
     * @param HTMX_Validator|null $validator Validator instance
     * @param TransientCache|null $cache Cache instance
     */
    public function __construct(?View $view = null, ?HTMX_Validator $validator = null, ?TransientCache $cache = null)
    {
        $this->view = $view ?? new View();
        $this->validator = $validator ?? new HTMX_Validator($this->view);
        $this->cache = $cache ?? new TransientCache();
        $this->prefix = Config::get('htmx.prefix', 'htmx_');
        
        // Register AJAX actions
        add_action('wp_ajax_' . $this->prefix . 'endpoint', [$this, 'handleEndpoint']);
        add_action('wp_ajax_nopriv_' . $this->prefix . 'endpoint', [$this, 'handleEndpoint']);
        
        // Register default middleware
        $this->registerMiddleware('auth', [$this, 'authMiddleware']);
        $this->registerMiddleware('nonce', [$this, 'nonceMiddleware']);
        $this->registerMiddleware('throttle', [$this, 'throttleMiddleware']);
        $this->registerMiddleware('cache', [$this, 'cacheMiddleware']);
        
        // Register custom middleware from config
        $custom_middleware = Config::get('htmx.middleware', []);
        
        foreach ($custom_middleware as $name => $callback) {
            if (is_callable($callback)) {
                $this->registerMiddleware($name, $callback);
            }
        }
    }
    
    /**
     * Register an endpoint.
     *
     * @param string $name Endpoint name
     * @param callable $callback Endpoint callback
     * @param array $middleware Middleware to apply
     * @return self
     */
    public function registerEndpoint(string $name, callable $callback, array $middleware = []): self
    {
        $this->endpoints[$name] = [
            'callback' => $callback,
            'middleware' => $middleware,
        ];
        
        return $this;
    }
    
    /**
     * Register middleware.
     *
     * @param string $name Middleware name
     * @param callable $callback Middleware callback
     * @return self
     */
    public function registerMiddleware(string $name, callable $callback): self
    {
        $this->middleware[$name] = $callback;
        
        return $this;
    }
    
    /**
     * Handle an endpoint request.
     *
     * @return void
     */
    public function handleEndpoint(): void
    {
        // Check if this is an HTMX request
        $is_htmx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
        
        if (!$is_htmx && !Config::get('htmx.allow_non_htmx', false)) {
            $this->sendError(__('Invalid request', 'wphelpers'), 400);
        }
        
        // Get endpoint name
        $endpoint = sanitize_text_field($_REQUEST['endpoint'] ?? '');
        
        if (empty($endpoint) || !isset($this->endpoints[$endpoint])) {
            $this->sendError(__('Invalid endpoint', 'wphelpers'), 404);
        }
        
        $endpoint_data = $this->endpoints[$endpoint];
        $callback = $endpoint_data['callback'];
        $middleware = $endpoint_data['middleware'];
        
        // Apply middleware
        foreach ($middleware as $middleware_name) {
            if (isset($this->middleware[$middleware_name])) {
                $middleware_callback = $this->middleware[$middleware_name];
                $result = $middleware_callback();
                
                if ($result === false) {
                    $this->sendError(__('Middleware check failed', 'wphelpers'), 403);
                }
            }
        }
        
        // Call the endpoint callback
        $result = $callback($this);
        
        if ($result === false) {
            $this->sendError(__('Endpoint execution failed', 'wphelpers'), 500);
        }
        
        exit;
    }
    
    /**
     * Auth middleware.
     *
     * @return bool True if authenticated, false otherwise
     */
    public function authMiddleware(): bool
    {
        return is_user_logged_in();
    }
    
    /**
     * Nonce middleware.
     *
     * @return bool True if nonce is valid, false otherwise
     */
    public function nonceMiddleware(): bool
    {
        $nonce = sanitize_text_field($_REQUEST['_wpnonce'] ?? '');
        $action = sanitize_text_field($_REQUEST['endpoint'] ?? '');
        
        return wp_verify_nonce($nonce, $this->prefix . $action);
    }
    
    /**
     * Throttle middleware.
     *
     * @return bool True if not throttled, false otherwise
     */
    public function throttleMiddleware(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $endpoint = sanitize_text_field($_REQUEST['endpoint'] ?? '');
        $key = 'throttle_' . md5($ip . '_' . $endpoint);
        $limit = Config::get('htmx.throttle.limit', 60);
        $period = Config::get('htmx.throttle.period', 60);
        
        $count = (int)$this->cache->get($key, 0);
        
        if ($count >= $limit) {
            header('Retry-After: ' . $period);
            return false;
        }
        
        $this->cache->increment($key, 1, $period);
        
        return true;
    }
    
    /**
     * Cache middleware.
     *
     * @return bool True if cache hit, false otherwise
     */
    public function cacheMiddleware(): bool
    {
        if (!Config::get('htmx.cache.enabled', false)) {
            return true;
        }
        
        $endpoint = sanitize_text_field($_REQUEST['endpoint'] ?? '');
        $key = 'cache_' . md5($endpoint . '_' . json_encode($_REQUEST));
        $ttl = Config::get('htmx.cache.ttl', 300);
        
        $cached = $this->cache->get($key);
        
        if ($cached !== null) {
            echo $cached;
            exit;
        }
        
        // Start output buffering
        ob_start();
        
        // Return true to continue processing
        return true;
    }
    
    /**
     * End cache middleware and store result.
     *
     * @return void
     */
    public function endCacheMiddleware(): void
    {
        if (!Config::get('htmx.cache.enabled', false)) {
            return;
        }
        
        $endpoint = sanitize_text_field($_REQUEST['endpoint'] ?? '');
        $key = 'cache_' . md5($endpoint . '_' . json_encode($_REQUEST));
        $ttl = Config::get('htmx.cache.ttl', 300);
        
        $content = ob_get_clean();
        $this->cache->set($key, $content, $ttl);
        
        echo $content;
    }
    
    /**
     * Validate request data.
     *
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @return bool True if validation passes, false otherwise
     */
    public function validate(array $rules, array $messages = []): bool
    {
        $data = $_REQUEST;
        return $this->validator->validate($data, $rules, $messages);
    }
    
    /**
     * Get validated data.
     *
     * @return array Validated data
     */
    public function getValidatedData(): array
    {
        return $this->validator->getValidatedData();
    }
    
    /**
     * Get validation errors.
     *
     * @return array Validation errors
     */
    public function getErrors(): array
    {
        return $this->validator->getErrors();
    }
    
    /**
     * Send a validation error response.
     *
     * @param string $target Target element
     * @param string $template Template name
     * @param array $data Template data
     * @return void
     */
    public function sendValidationErrors(string $target, string $template = 'validation/errors.twig', array $data = []): void
    {
        $this->validator->sendHtmxResponse($target, $template, $data);
    }
    
    /**
     * Send an error response.
     *
     * @param string $message Error message
     * @param int $status HTTP status code
     * @return void
     */
    public function sendError(string $message, int $status = 422): void
    {
        $this->validator->sendHtmxError($message, $status);
    }
    
    /**
     * Send a success response.
     *
     * @param string $message Success message
     * @param array $trigger Additional trigger data
     * @return void
     */
    public function sendSuccess(string $message, array $trigger = []): void
    {
        $this->validator->sendHtmxSuccess($message, $trigger);
    }
    
    /**
     * Render a template.
     *
     * @param string $template Template name
     * @param array $data Template data
     * @return string Rendered template
     */
    public function render(string $template, array $data = []): string
    {
        return $this->view->render($template, $data);
    }
    
    /**
     * Send a rendered template.
     *
     * @param string $template Template name
     * @param array $data Template data
     * @return void
     */
    public function sendTemplate(string $template, array $data = []): void
    {
        echo $this->render($template, $data);
    }
    
    /**
     * Get the AJAX URL for an endpoint.
     *
     * @param string $endpoint Endpoint name
     * @param array $params Additional parameters
     * @param bool $with_nonce Whether to include a nonce
     * @return string AJAX URL
     */
    public function getEndpointUrl(string $endpoint, array $params = [], bool $with_nonce = true): string
    {
        $url = admin_url('admin-ajax.php');
        
        $params = array_merge([
            'action' => $this->prefix . 'endpoint',
            'endpoint' => $endpoint,
        ], $params);
        
        if ($with_nonce) {
            $params['_wpnonce'] = wp_create_nonce($this->prefix . $endpoint);
        }
        
        return add_query_arg($params, $url);
    }
    
    /**
     * Get HTMX attributes for an endpoint.
     *
     * @param string $endpoint Endpoint name
     * @param array $params Additional parameters
     * @param array $attrs Additional HTMX attributes
     * @return string HTMX attributes
     */
    public function getHtmxAttrs(string $endpoint, array $params = [], array $attrs = []): string
    {
        $url = $this->getEndpointUrl($endpoint, $params);
        
        $default_attrs = [
            'hx-post' => $url,
            'hx-trigger' => 'click',
            'hx-swap' => 'outerHTML',
        ];
        
        $attrs = array_merge($default_attrs, $attrs);
        $html = '';
        
        foreach ($attrs as $name => $value) {
            $html .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
        }
        
        return $html;
    }
    
    /**
     * Register HTMX assets.
     *
     * @param bool $with_extensions Whether to include extensions
     * @return void
     */
    public function registerAssets(bool $with_extensions = false): void
    {
        $version = Config::get('htmx.version', '1.9.2');
        $min = Config::get('htmx.minified', true) ? '.min' : '';
        
        wp_register_script(
            'htmx',
            "https://unpkg.com/htmx.org@{$version}/dist/htmx{$min}.js",
            [],
            $version,
            true
        );
        
        wp_enqueue_script('htmx');
        
        // Register extensions
        if ($with_extensions) {
            $extensions = Config::get('htmx.extensions', []);
            
            foreach ($extensions as $name => $path) {
                wp_register_script(
                    "htmx-{$name}",
                    $path,
                    ['htmx'],
                    $version,
                    true
                );
                
                wp_enqueue_script("htmx-{$name}");
            }
        }
        
        // Add inline script for CSRF protection
        if (Config::get('htmx.csrf_protection', true)) {
            $script = "
                document.addEventListener('DOMContentLoaded', function() {
                    document.body.addEventListener('htmx:configRequest', function(event) {
                        event.detail.headers['X-WP-Nonce'] = '" . esc_js(wp_create_nonce('wp_rest')) . "';
                    });
                });
            ";
            
            wp_add_inline_script('htmx', $script);
        }
    }
    
    /**
     * Check if the current request is an HTMX request.
     *
     * @return bool True if HTMX request, false otherwise
     */
    public function isHtmxRequest(): bool
    {
        return isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
    }
    
    /**
     * Get the target element of an HTMX request.
     *
     * @return string|null Target element or null if not set
     */
    public function getHtmxTarget(): ?string
    {
        return $_SERVER['HTTP_HX_TARGET'] ?? null;
    }
    
    /**
     * Get the trigger element of an HTMX request.
     *
     * @return string|null Trigger element or null if not set
     */
    public function getHtmxTrigger(): ?string
    {
        return $_SERVER['HTTP_HX_TRIGGER'] ?? null;
    }
    
    /**
     * Get the trigger name of an HTMX request.
     *
     * @return string|null Trigger name or null if not set
     */
    public function getHtmxTriggerName(): ?string
    {
        return $_SERVER['HTTP_HX_TRIGGER_NAME'] ?? null;
    }
    
    /**
     * Get the current URL of an HTMX request.
     *
     * @return string|null Current URL or null if not set
     */
    public function getHtmxCurrentUrl(): ?string
    {
        return $_SERVER['HTTP_HX_CURRENT_URL'] ?? null;
    }
    
    /**
     * Get the prompt response of an HTMX request.
     *
     * @return string|null Prompt response or null if not set
     */
    public function getHtmxPrompt(): ?string
    {
        return $_SERVER['HTTP_HX_PROMPT'] ?? null;
    }
    
    /**
     * Set a response header for an HTMX request.
     *
     * @param string $name Header name
     * @param string $value Header value
     * @return void
     */
    public function setHtmxHeader(string $name, string $value): void
    {
        header("HX-{$name}: {$value}");
    }
    
    /**
     * Trigger an event on the client.
     *
     * @param string $event Event name
     * @param array $detail Event detail
     * @return void
     */
    public function triggerEvent(string $event, array $detail = []): void
    {
        $this->setHtmxHeader('Trigger', json_encode([
            $event => $detail ?: true,
        ]));
    }
    
    /**
     * Redirect to a URL.
     *
     * @param string $url URL to redirect to
     * @return void
     */
    public function redirect(string $url): void
    {
        $this->setHtmxHeader('Redirect', $url);
        exit;
    }
    
    /**
     * Refresh the page.
     *
     * @return void
     */
    public function refresh(): void
    {
        $this->setHtmxHeader('Refresh', 'true');
        exit;
    }
    
    /**
     * Set the target element for the response.
     *
     * @param string $target Target element
     * @return void
     */
    public function setTarget(string $target): void
    {
        $this->setHtmxHeader('Retarget', $target);
    }
    
    /**
     * Set the swap method for the response.
     *
     * @param string $method Swap method
     * @return void
     */
    public function setSwap(string $method): void
    {
        $this->setHtmxHeader('Reswap', $method);
    }
    
    /**
     * Push a URL to the browser history.
     *
     * @param string $url URL to push
     * @return void
     */
    public function pushUrl(string $url): void
    {
        $this->setHtmxHeader('Push-Url', $url);
    }
    
    /**
     * Replace the current URL in the browser history.
     *
     * @param string $url URL to replace with
     * @return void
     */
    public function replaceUrl(string $url): void
    {
        $this->setHtmxHeader('Replace-Url', $url);
    }
    
    /**
     * Set the response status code.
     *
     * @param int $code Status code
     * @return void
     */
    public function setStatus(int $code): void
    {
        http_response_code($code);
    }
    
    /**
     * Get the View instance.
     *
     * @return View View instance
     */
    public function getView(): View
    {
        return $this->view;
    }
    
    /**
     * Get the Validator instance.
     *
     * @return HTMX_Validator Validator instance
     */
    public function getValidator(): HTMX_Validator
    {
        return $this->validator;
    }
    
    /**
     * Get the Cache instance.
     *
     * @return TransientCache Cache instance
     */
    public function getCache(): TransientCache
    {
        return $this->cache;
    }
    
    /**
     * Get all registered endpoints.
     *
     * @return array Registered endpoints
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }
    
    /**
     * Get all registered middleware.
     *
     * @return array Registered middleware
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
    
    /**
     * Get the prefix.
     *
     * @return string Prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
