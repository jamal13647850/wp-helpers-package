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

namespace jamal13647850\wphelpers\Components\Menu;

use jamal13647850\wphelpers\Components\Menu\Options\MenuOptions;
use jamal13647850\wphelpers\Navigation\AlpineNavWalker;
use jamal13647850\wphelpers\Assets\AssetManager;

/**
 * AbstractMenu
 *
 * Common base for all menu variants.
 * Provides standard factory methods for options and walker generation.
 * Child classes must implement HTML rendering and provide their own default options.
 */
abstract class AbstractMenu
{
    /**
     * Render the final menu HTML.
     *
     * @param string $themeLocation   The WordPress menu theme location.
     * @param array  $options         Variant-specific menu options (optional).
     * @param array  $walkerOptions   Options for the menu walker (optional).
     *
     * @return string                 The rendered menu HTML.
     */
    abstract public function render(
        string $themeLocation,
        array $options = [],
        array $walkerOptions = []
    ): string;

    /**
     * Return the default options for this menu variant.
     *
     * @return array  Associative array of default options.
     */
    abstract protected static function defaultOptions(): array;

    /**
     * Create a MenuOptions value object by merging $incoming with defaults.
     *
     * @param array $incoming   Options provided by the caller.
     * @return MenuOptions      The merged and validated menu options.
     */
    protected function makeOptions(array $incoming): MenuOptions
    {
        return new MenuOptions($incoming, static::defaultOptions());
    }

    /**
     * Create a ready-to-use AlpineNavWalker.
     * Child classes can override this if a custom walker is needed.
     *
     * @param string $mode            Mode string for walker configuration.
     * @param array  $walkerOptions   Additional options for the walker (optional).
     * @return AlpineNavWalker        Instantiated walker.
     */
    protected function makeWalker(string $mode, array $walkerOptions = []): AlpineNavWalker
    {
        return new AlpineNavWalker($mode, $walkerOptions);
    }


     /** Variant may override to declare its assets */
    protected static function assets(): array
    {
        return [
            'styles'  => [], // [['handle'=>..., 'src'=>..., 'deps'=>[], 'ver'=>null, 'media'=>'all']]
            'scripts' => [], // [['handle'=>..., 'src'=>..., 'deps'=>[], 'ver'=>null, 'footer'=>true]]
        ];
    }

    protected function enqueueAssets(): void
    {
        foreach (static::assets()['styles'] as $style) {
            AssetManager::style(
                $style['handle'],
                $style['src'],
                $style['deps']  ?? [],
                $style['ver']   ?? null,
                $style['media'] ?? 'all'
            );
        }
        foreach (static::assets()['scripts'] as $script) {
            AssetManager::script(
                $script['handle'],
                $script['src'],
                $script['deps']   ?? [],
                $script['ver']    ?? null,
                $script['footer'] ?? true
            );
        }
    }
}

