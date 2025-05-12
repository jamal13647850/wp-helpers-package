<?php
declare(strict_types=1);
namespace jamal13647850\wphelpers;

defined('ABSPATH') || exit();

interface TwigHelperInterface
{
    /**
     * Create and return an instance of Twig.
     *
     * @return \Twig\Environment
     */
    public function createInstance(): \Twig\Environment;
    
    /**
     * Add a custom path to Twig loader.
     *
     * @param string $path Path to add
     * @param string $namespace Namespace for this path
     * @return void
     */
    public function addPath(string $path, string $namespace): void;
    
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
}
