<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

interface ViewInterface
{
    /**
     * Render a template with the given data.
     *
     * @param string $template Template name
     * @param array $data Data to pass to the template
     * @return string Rendered template
     */
    public function render(string $template, array $data = []): string;
    
    /**
     * Display a template with the given data.
     *
     * @param string $template Template name
     * @param array $data Data to pass to the template
     * @return void
     */
    public function display(string $template, array $data = []): void;
    
    /**
     * Render a template with the given data and exit.
     *
     * @param string $template Template name
     * @param array $data Data to pass to the template
     * @param int $status HTTP status code
     * @return void
     */
    public function render_with_exit(string $template, array $data = [], int $status = 200): void;
    
    /**
     * Add a custom Twig extension.
     *
     * @param \Twig\Extension\ExtensionInterface $extension
     * @return void
     */
    public function addExtension(\Twig\Extension\ExtensionInterface $extension): void;
    
    /**
     * Add a custom Twig filter.
     *
     * @param string $name Filter name
     * @param callable $callback Filter callback
     * @param array $options Filter options
     * @return void
     */
    public function addFilter(string $name, callable $callback, array $options = []): void;
    
    /**
     * Add a custom Twig function.
     *
     * @param string $name Function name
     * @param callable $callback Function callback
     * @param array $options Function options
     * @return void
     */
    public function addFunction(string $name, callable $callback, array $options = []): void;
    
    /**
     * Add a custom template path.
     *
     * @param string $path Path to add
     * @param string $namespace Namespace for this path
     * @return void
     */
    public function addPath(string $path, string $namespace): void;
}
