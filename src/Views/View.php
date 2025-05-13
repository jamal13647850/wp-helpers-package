<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Views;

defined('ABSPATH') || exit();

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use jamal13647850\wphelpers\Config;
/**
 * Class View
 * 
 * Handles view rendering using Twig.
 */
class View
{
    /**
     * @var Environment|null
     */
    private ?Environment $twig = null;
    
    /**
     * @var array
     */
    private array $globals = [];
    
    /**
     * @var array
     */
    private array $functions = [];
    
    /**
     * @var array
     */
    private array $paths = [];
    
    /**
     * View constructor.
     */
    public function __construct()
    {
        // Initialize view paths from config
        $this->paths = [
            '@views' => Config::get('view.paths.views', dirname(__DIR__) . '/views'),
            '@templates' => Config::get('view.paths.templates', get_template_directory()),
            '@partials' => Config::get('view.paths.partials', get_template_directory() . '/partials'),
            '@components' => Config::get('view.paths.components', get_template_directory() . '/components'),
        ];
        
        // Add custom paths from config
        $custom_paths = Config::get('view.paths.custom', []);
        foreach ($custom_paths as $namespace => $path) {
            $this->paths[$namespace] = $path;
        }
        
        // Register default WordPress functions
        $this->registerDefaultFunctions();
        
        // Register custom functions from config
        $custom_functions = Config::get('view.functions', []);
        foreach ($custom_functions as $name => $callback) {
            $this->registerFunction($name, $callback);
        }
        
        // Add global variables from config
        $globals = Config::get('view.globals', []);
        foreach ($globals as $name => $value) {
            $this->addGlobal($name, $value);
        }
    }
    
    /**
     * Initialize Twig environment.
     *
     * @return Environment Twig environment
     */
    private function initTwig(): Environment
    {
        if ($this->twig === null) {
            // Create loader with namespaced paths
            $loader = new FilesystemLoader();
            
            foreach ($this->paths as $namespace => $path) {
                if (is_dir($path)) {
                    $loader->addPath($path, substr($namespace, 1));
                }
            }
            
            // Create Twig environment
            $debug = Config::get('view.debug', WP_DEBUG);
            $cache = Config::get('view.cache.enabled', false) ? Config::get('view.cache.path', WP_CONTENT_DIR . '/cache/twig') : false;
            
            $options = [
                'debug' => $debug,
                'cache' => $cache,
                'auto_reload' => Config::get('view.auto_reload', true),
                'strict_variables' => Config::get('view.strict_variables', false),
            ];
            
            $this->twig = new Environment($loader, $options);
            
            // Add globals
            foreach ($this->globals as $name => $value) {
                $this->twig->addGlobal($name, $value);
            }
            
            // Add functions
            foreach ($this->functions as $name => $callback) {
                $this->twig->addFunction(new TwigFunction($name, $callback));
            }
            
            // Add debug extension if in debug mode
            if ($debug) {
                $this->twig->addExtension(new \Twig\Extension\DebugExtension());
            }
            
            // Add WordPress extension
            $this->twig->addExtension(new WordPressTwigExtension());
        }
        
        return $this->twig;
    }
    
    /**
     * Register default WordPress functions.
     *
     * @return void
     */
    private function registerDefaultFunctions(): void
    {
        // WordPress core functions
        $wp_functions = [
            'get_header', 'get_footer', 'get_sidebar', 'get_template_part',
            'wp_head', 'wp_footer', 'body_class', 'post_class',
            'wp_nav_menu', 'wp_title', 'bloginfo', 'get_bloginfo',
            'get_the_title', 'the_title', 'the_content', 'the_excerpt',
            'the_permalink', 'get_the_permalink', 'get_the_ID', 'get_the_date',
            'get_the_author', 'get_the_author_meta', 'get_post_meta', 'get_post_thumbnail_id',
            'get_post_thumbnail', 'get_the_post_thumbnail', 'wp_get_attachment_image',
            'wp_get_attachment_url', 'wp_get_attachment_image_src', 'wp_enqueue_script',
            'wp_enqueue_style', 'wp_localize_script', 'get_search_form', 'comments_template',
            'comment_form', 'previous_posts_link', 'next_posts_link', 'paginate_links',
            'get_posts', 'get_terms', 'get_term_by', 'get_term_link', 'get_categories',
            'get_tags', 'get_archives_link', 'wp_list_categories', 'wp_list_pages',
            'wp_login_url', 'wp_logout_url', 'wp_registration_url', 'wp_lostpassword_url',
            'is_home', 'is_front_page', 'is_single', 'is_page', 'is_archive', 'is_category',
            'is_tag', 'is_tax', 'is_author', 'is_search', 'is_404', 'is_user_logged_in',
            'current_user_can', 'wp_nonce_field', 'wp_create_nonce', 'wp_verify_nonce',
            'esc_html', 'esc_attr', 'esc_url', 'esc_js', 'esc_textarea', 'wp_kses_post',
            'sanitize_text_field', 'sanitize_email', 'sanitize_title', 'apply_filters',
            'do_action', 'do_shortcode', 'shortcode_exists', 'wp_reset_postdata',
            'get_option', 'update_option', 'delete_option', 'get_theme_mod', 'set_theme_mod',
            'remove_theme_mod', 'get_theme_mods', 'remove_theme_mods', 'get_theme_support',
            'add_theme_support', 'remove_theme_support', 'current_theme_supports',
            '__', '_e', '_n', '_x', 'esc_html__', 'esc_html_e', 'esc_attr__', 'esc_attr_e',
        ];
        
        foreach ($wp_functions as $function) {
            if (function_exists($function)) {
                $this->registerFunction($function, $function);
            }
        }
    }
    
    /**
     * Register a function with Twig.
     *
     * @param string $name Function name
     * @param callable $callback Function callback
     * @return self
     */
    public function registerFunction(string $name, callable $callback): self
    {
        $this->functions[$name] = $callback;
        
        // If Twig is already initialized, add the function directly
        if ($this->twig !== null) {
            $this->twig->addFunction(new TwigFunction($name, $callback));
        }
        
        return $this;
    }
    
    /**
     * Add a global variable to Twig.
     *
     * @param string $name Variable name
     * @param mixed $value Variable value
     * @return self
     */
    public function addGlobal(string $name, $value): self
    {
        $this->globals[$name] = $value;
        
        // If Twig is already initialized, add the global directly
        if ($this->twig !== null) {
            $this->twig->addGlobal($name, $value);
        }
        
        return $this;
    }
    
    /**
     * Add a path to Twig.
     *
     * @param string $namespace Namespace
     * @param string $path Path
     * @return self
     */
    public function addPath(string $namespace, string $path): self
    {
        $this->paths[$namespace] = $path;
        
        // If Twig is already initialized, add the path directly
        if ($this->twig !== null && is_dir($path)) {
            $this->twig->getLoader()->addPath($path, substr($namespace, 1));
        }
        
        return $this;
    }
    
    /**
     * Render a template.
     *
     * @param string $template Template name
     * @param array $data Template data
     * @return string Rendered template
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $template, array $data = []): string
    {
        try {
            $twig = $this->initTwig();
            
            // Add current user to data if not already set
            if (!isset($data['current_user']) && is_user_logged_in()) {
                $data['current_user'] = wp_get_current_user();
            }
            
            // Add post to data if not already set and in the loop
            if (!isset($data['post']) && in_the_loop()) {
                global $post;
                $data['post'] = $post;
            }
            
            // Add query to data if not already set
            if (!isset($data['wp_query'])) {
                global $wp_query;
                $data['wp_query'] = $wp_query;
            }
            
            // Apply filter to template data
            $data = apply_filters('wphelpers_view_data', $data, $template);
            
            // Render template
            return $twig->render($template, $data);
            
        } catch (LoaderError $e) {
            if (Config::get('view.debug', WP_DEBUG)) {
                throw $e;
            }
            
            return $this->renderError('Template not found: ' . $template);
        } catch (RuntimeError | SyntaxError $e) {
            if (Config::get('view.debug', WP_DEBUG)) {
                throw $e;
            }
            
            return $this->renderError('Error rendering template: ' . $e->getMessage());
        }
    }
    
    /**
     * Render an error message.
     *
     * @param string $message Error message
     * @return string Rendered error message
     */
    private function renderError(string $message): string
    {
        if (Config::get('view.show_errors', WP_DEBUG)) {
            return '<div class="wphelpers-view-error">' . esc_html($message) . '</div>';
        }
        
        return '<!-- Template Error: ' . esc_html($message) . ' -->';
    }
    
    /**
     * Get the Twig environment.
     *
     * @return Environment Twig environment
     */
    public function getTwig(): Environment
    {
        return $this->initTwig();
    }
    
    /**
     * Check if a template exists.
     *
     * @param string $template Template name
     * @return bool True if template exists, false otherwise
     */
    public function templateExists(string $template): bool
    {
        try {
            $twig = $this->initTwig();
            return $twig->getLoader()->exists($template);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get a list of available templates.
     *
     * @param string $namespace Namespace
     * @return array List of templates
     */
    public function getTemplates(string $namespace = '@views'): array
    {
        $templates = [];
        
        if (isset($this->paths[$namespace])) {
            $path = $this->paths[$namespace];
            
            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && in_array($file->getExtension(), ['twig', 'html'])) {
                        $relativePath = str_replace($path . '/', '', $file->getPathname());
                        $templates[] = $relativePath;
                    }
                }
            }
        }
        
        return $templates;
    }
    
    /**
     * Clear the Twig cache.
     *
     * @return bool True if cache was cleared, false otherwise
     */
    public function clearCache(): bool
    {
        $cache_path = Config::get('view.cache.path', WP_CONTENT_DIR . '/cache/twig');
        
        if (is_dir($cache_path)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cache_path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            
            return true;
        }
        
        return false;
    }
}
