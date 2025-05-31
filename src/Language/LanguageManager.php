<?php

namespace jamal13647850\wphelpers\Language;

use jamal13647850\wphelpers\Cache\CacheManager;

defined('ABSPATH') || exit;

/**
 * LanguageManager
 * Centralizes all multilingual functionalities for the package
 *
 * @package jamal13647850\wphelpers
 */
class LanguageManager
{
    /**
     * Singleton instance
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Supported languages (['fa_IR'=>'فارسی', ...])
     * @var array
     */
    private array $languages = [];

    /**
     * In-memory cache for loaded translations.
     * ['fa_IR' => ['filemtime' => int, 'translations' => array]]
     * @var array
     */
    private static array $translation_cache = [];

    /**
     * Cache manager instance (object-cache, transient, file, ...)
     * @var CacheManager
     */
    private CacheManager $cache;

    /**
     * LanguageManager constructor.
     * @param array|null $languages
     */
    private function __construct(?array $languages = null)
    {
        // Set supported languages; add more as needed
        $this->languages = $languages ?: [
            'fa_IR' => 'فارسی',
            'en_US' => 'English',
        ];

        // You may change 'transient' to your preferred persistent cache
        $this->cache = new CacheManager('transient', 'lang_', 24 * 3600); // 24 hours default
    }

    /**
     * Singleton getter
     * @param array|null $languages
     * @return self
     */
    public static function getInstance(?array $languages = null): self
    {
        if (!self::$instance) {
            self::$instance = new self($languages);
        }
        return self::$instance;
    }

    /**
     * Get current locale (e.g. 'fa_IR')
     * @return string
     */
    public function getCurrentLocale(): string
    {
        // Can be customized: user profile, cookie, GET etc.
        return get_locale();
    }

    /**
     * Get all supported languages
     * @return array
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * Translate a given key, using cache with auto-invalidate on file change
     * @param string $key
     * @param string|null $locale
     * @param string|null $default
     * @return string
     */
    public function trans(string $key, ?string $locale = null, ?string $default = null): string
    {
        $locale = $locale ?: $this->getCurrentLocale();

        $translations = $this->getTranslationsCached($locale);

        if (isset($translations[$key]) && $translations[$key] !== '') {
            return $translations[$key];
        }

        // Fallback: try 'en_US'
        if ($locale !== 'en_US') {
            $en_trans = $this->getTranslationsCached('en_US');
            if (isset($en_trans[$key]) && $en_trans[$key] !== '') {
                return $en_trans[$key];
            }
        }

        // Fallback: default
        return $default ?? $key;
    }

    /**
     * Generate a language-suffixed field key (e.g. main_phone_fa_IR)
     * @param string $key
     * @param string|null $locale
     * @return string
     */
    public function localizedKey(string $key, ?string $locale = null): string
    {
        $locale = $locale ?: $this->getCurrentLocale();
        return "{$key}_{$locale}";
    }

    /**
     * Returns cached translation array. Invalidates automatically when file changes.
     * @param string $locale
     * @return array
     */
    private function getTranslationsCached(string $locale): array
    {
        $file_path = __DIR__ . "/lang/{$locale}.php";
        $file_mtime = file_exists($file_path) ? filemtime($file_path) : 0;
        $cache_key = "translations_{$locale}_{$file_mtime}";

        // 1. Try in-memory cache (fastest)
        if (
            isset(self::$translation_cache[$locale]) && 
            self::$translation_cache[$locale]['filemtime'] === $file_mtime
        ) {
            return self::$translation_cache[$locale]['translations'];
        }

        // 2. Try external cache (e.g., persistent object cache)
        $array = $this->cache->get($cache_key);
        if (is_array($array)) {
            // Store in-memory for subsequent use in the same request
            self::$translation_cache[$locale] = [
                'filemtime'    => $file_mtime,
                'translations' => $array,
            ];
            return $array;
        }

        // 3. Not cached or cache invalidated (file changed): (re)load from file
        $array = $this->loadLangFile($locale);
        $this->cache->set($cache_key, $array, 24*3600);

        // Invalidate all previous cache keys for this locale (optional: best for very low memory leak risk)
        $this->invalidateOldCache($locale, $file_mtime);

        // Store in-memory as well
        self::$translation_cache[$locale] = [
            'filemtime'    => $file_mtime,
            'translations' => $array,
        ];
        return $array;
    }

    /**
     * Remove all old cache entries (by prefix) except current one (after file changed)
     * @param string $locale
     * @param int $current_mtime
     * @return void
     */
    private function invalidateOldCache(string $locale, int $current_mtime): void
    {
        // If your cache manager supports listing keys, you can delete previous cache keys.
        // For commonly used caches in WordPress (transient/object-cache), this feature is limited.
        // So this is mostly a placeholder for advanced cache drivers supporting key search/removal.
        // For most cases: don't worry; old cache entries will simply expire.

        // Examples for advanced cache:
        // $allKeys = $this->cache->searchKeys("translations_{$locale}_*");
        // foreach ($allKeys as $key) {
        //     if ($key !== "translations_{$locale}_{$current_mtime}") {
        //         $this->cache->delete($key);
        //     }
        // }
    }

    /**
     * Loads translation array from the language file
     * @param string $locale
     * @return array
     */
    private function loadLangFile(string $locale): array
    {
        $path = __DIR__ . "/lang/{$locale}.php";
        if (file_exists($path)) {
            $array = include $path;
            if (is_array($array)) return $array;
        }
        return [];
    }
}
