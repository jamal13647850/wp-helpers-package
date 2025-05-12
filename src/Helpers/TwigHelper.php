<?php
declare(strict_types=1);
namespace jamal13647850\wphelpers\Helpers;

defined('ABSPATH') || exit();

class TwigHelper implements TwigHelperInterface
{
    /**
     * @var \Twig\Environment|null
     */
    private ?\Twig\Environment $twig = null;
    
    /**
     * @var \Twig\Loader\FilesystemLoader|null
     */
    private ?\Twig\Loader\FilesystemLoader $loader = null;
    
    /**
     * TwigHelper constructor.
     */
    public function __construct()
    {
        $this->initTwig();
    }
    
    /**
     * Initialize Twig with the configured directories and options.
     *
     * @return void
     */
    private function initTwig(): void
    {
        $this->loader = new \Twig\Loader\FilesystemLoader();
        
        $basePath = Config::get('views.path', get_template_directory() . '/templates/');
        $directories = Config::get('views.directories', ['views']);
        
        foreach ($directories as $dir) {
            $path = rtrim($basePath, '/') . '/' . $dir;
            if (is_dir($path)) {
                $this->loader->addPath($path, $dir);
                // Also add as a default path
                $this->loader->addPath($path);
            }
        }
        
        $this->twig = new \Twig\Environment($this->loader, [
            'debug' => Config::get('views.debug', WP_DEBUG),
            'cache' => Config::get('views.cache', false),
            'auto_reload' => Config::get('views.auto_reload', true),
        ]);
        
        // Add debug extension if debug is enabled
        if (Config::get('views.debug', WP_DEBUG)) {
            $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        }
        
        // Add WordPress specific functions and filters
        $this->addWordPressFunctions();
    }
    
    /**
     * Create and return the Twig instance.
     *
     * @return \Twig\Environment
     */
    public function createInstance(): \Twig\Environment
    {
        if ($this->twig === null) {
            $this->initTwig();
        }
        
        return $this->twig;
    }
    
    /**
     * Add a custom path to Twig loader.
     *
     * @param string $path Path to add
     * @param string $namespace Namespace for this path
     * @return void
     */
    public function addPath(string $path, string $namespace): void
    {
        if ($this->loader === null) {
            $this->initTwig();
        }
        
        if (is_dir($path)) {
            $this->loader->addPath($path, $namespace);
        }
    }
    
    /**
     * Add a custom Twig extension.
     *
     * @param \Twig\Extension\ExtensionInterface $extension
     * @return void
     */
    public function addExtension(\Twig\Extension\ExtensionInterface $extension): void
    {
        if ($this->twig === null) {
            $this->initTwig();
        }
        
        $this->twig->addExtension($extension);
    }
    
    /**
     * Add a custom Twig filter.
     *
     * @param string $name Filter name
     * @param callable $callback Filter callback
     * @param array $options Filter options
     * @return void
     */
    public function addFilter(string $name, callable $callback, array $options = []): void
    {
        if ($this->twig === null) {
            $this->initTwig();
        }
        
        $this->twig->addFilter(new \Twig\TwigFilter($name, $callback, $options));
    }
    
    /**
     * Add a custom Twig function.
     *
     * @param string $name Function name
     * @param callable $callback Function callback
     * @param array $options Function options
     * @return void
     */
    public function addFunction(string $name, callable $callback, array $options = []): void
    {
        if ($this->twig === null) {
            $this->initTwig();
        }
        
        $this->twig->addFunction(new \Twig\TwigFunction($name, $callback, $options));
    }
    
    /**
     * Add WordPress specific functions and filters to Twig.
     *
     * @return void
     */
    private function addWordPressFunctions(): void
    {
        // Common WordPress functions
        $wpFunctions = [
            'wp_head', 'wp_footer', 'body_class', 'get_header', 'get_footer', 'get_sidebar',
            'get_template_part', 'get_permalink', 'get_the_title', 'get_the_content',
            'get_the_excerpt', 'get_the_post_thumbnail', 'wp_get_attachment_image',
            'get_post_type', 'get_post_meta', 'get_term_meta', 'get_option', 'get_theme_mod',
            'wp_nav_menu', 'wp_list_categories', 'paginate_links', 'home_url', 'admin_url',
            'get_template_directory_uri', 'get_stylesheet_directory_uri', 'get_search_form',
            'wp_enqueue_script', 'wp_enqueue_style', 'wp_login_url', 'wp_logout_url',
            'wp_register_url', 'is_user_logged_in', 'current_user_can', 'wp_get_current_user',
            'is_front_page', 'is_home', 'is_single', 'is_page', 'is_archive', 'is_category',
            'is_tag', 'is_tax', 'is_author', 'is_search', 'is_404'
        ];
        
        // Add WordPress functions to Twig
        foreach ($wpFunctions as $function) {
            if (function_exists($function)) {
                $this->addFunction($function, $function, ['is_safe' => ['html']]);
            }
        }
        
        // Add translation functions
        $this->addFunction('__', function($text, $domain = 'default') {
            return __($text, $domain);
        }, ['is_safe' => ['html']]);
        
        $this->addFunction('_e', function($text, $domain = 'default') {
            return _e($text, $domain);
        }, ['is_safe' => ['html']]);
        
        $this->addFunction('_n', function($single, $plural, $number, $domain = 'default') {
            return _n($single, $plural, $number, $domain);
        }, ['is_safe' => ['html']]);
        
        // Add escaping filters
        $this->addFilter('esc_html', 'esc_html');
        $this->addFilter('esc_attr', 'esc_attr');
        $this->addFilter('esc_url', 'esc_url');
        $this->addFilter('esc_js', 'esc_js');
        
        // Add wp_kses filter
        $this->addFilter('wp_kses_post', 'wp_kses_post', ['is_safe' => ['html']]);
    }
}
