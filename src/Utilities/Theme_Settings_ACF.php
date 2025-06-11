<?php

namespace jamal13647850\wphelpers\Utilities;

use jamal13647850\wphelpers\Utilities\Theme_Settings_Cache;
use jamal13647850\wphelpers\Language\LanguageManager;

defined('ABSPATH') || exit;

/**
 * Theme_Settings_ACF
 * Full ACF option page/group handler with multilingual support using LanguageManager
 *
 * @package jamal13647850\wphelpers
 */
class Theme_Settings_ACF
{
    private string $theme_prefix;
    private array $prefixes = [
        'general'  => 'general_',
        'header'   => 'header_',
        'footer'   => 'footer_',
        'homepage' => 'homepage_',
        'shop'     => 'shop_',
        'inner'    => 'inner_',
        'contact'  => 'contact_',
        'about'    => 'about_',
        'social'   => 'social_',
        'scripts'  => 'scripts_',
    ];
    private array $groups = [];
    private ?string $config_path = null;
    private ?Theme_Settings_Cache $cache = null;
    private LanguageManager $lang;

    public function __construct(?string $theme_prefix = null, ?string $config_path = null)
    {
        $stylesheet = function_exists('get_stylesheet') ? get_stylesheet() : 'defaulttheme';
        $this->theme_prefix = $theme_prefix ? $this->normalize_prefix($theme_prefix) : $this->normalize_prefix($stylesheet);
        $this->cache = new Theme_Settings_Cache($this->theme_prefix);
        $this->config_path = $config_path ?: get_template_directory() . '/config/theme_settings_definitions.php';
        $this->lang = LanguageManager::getInstance();

        add_action('admin_notices', [$this, 'check_acf_pro_admin_notices']);
        if (function_exists('acf_add_options_page')) {
            add_action('acf/init', [$this, 'load_config']);
            add_action('acf/init', [$this, 'register_options_pages'], 10);
            add_action('acf/init', [$this, 'register_field_groups'], 15);
        }
        add_action('acf/save_post', [$this, 'clearCacheOnSave']);
    }

    private function normalize_prefix(string $prefix): string
    {
        $prefix = strtolower($prefix);
        $prefix = preg_replace('/[^a-z0-9_]/', '_', $prefix);
        return rtrim($prefix, '_') . '_';
    }

    /**
     * Shows admin notice if ACF Pro plugin not active
     */
    public function check_acf_pro_admin_notices(): void
    {
        if (
            !function_exists('acf_add_options_page') &&
            current_user_can('manage_options') &&
            is_admin()
        ) {
            echo '<div class="notice notice-error"><p><strong>' .
                $this->lang->trans(
                    'ACF Pro plugin is required for Theme Settings management.',
                    null,
                    'ACF Pro plugin is required for Theme Settings management.'
                )
                . '</strong></p></div>';
        }
    }

    /**
     * Loads config from file only once
     */
    public function load_config(): void
    {
        if ($this->groups) return;
        try {
            if (!file_exists($this->config_path)) {
                throw new \Exception("Config file not found: {$this->config_path}");
            }
            $config = require $this->config_path;
            if (!is_array($config)) {
                throw new \Exception('Config file must return an array.');
            }
            $this->groups = $config;
        } catch (\Exception $e) {
            error_log("Error loading config in Theme_Settings_ACF: " . $e->getMessage());
        }
    }

    /**
     * Option to change config path at runtime
     */
    public function setConfigPath(string $path): void
    {
        $this->config_path = $path;
        $this->groups = [];
    }

    /**
     * Register ACF options main page & sub pages, using ONLY LanguageManager for labels
     */
    public function register_options_pages(): void
    {
        if (!function_exists('acf_add_options_page') || empty($this->groups)) {
            return;
        }

        acf_add_options_page([
            'page_title' => $this->lang->trans('Theme Settings', null, 'Theme Settings'),
            'menu_title' => $this->lang->trans('Theme Settings', null, 'Theme Settings'),
            'menu_slug'  => $this->theme_prefix . 'theme-settings',
            'capability' => 'manage_options',
            'redirect'   => false,
            'icon_url'   => 'dashicons-admin-customizer',
            'position'   => 60,
        ]);

        foreach ($this->groups as $key => $group) {
            acf_add_options_sub_page([
                'page_title'  => $this->lang->trans($group['title'], null, $group['title']),
                'menu_title'  => $this->lang->trans($group['title'], null, $group['title']),
                'parent_slug' => $this->theme_prefix . 'theme-settings',
                'menu_slug'   => $this->theme_prefix . $group['menu_slug'],
                'capability'  => $group['capability'] ?? 'manage_options',
                'position'    => 60 + ($group['menu_order'] ?? 0),
            ]);
        }
    }

    /**
     * Register all ACF field groups/fields
     */
    public function register_field_groups(): void
    {
        if (empty($this->groups)) {
            $this->load_config();
        }
        foreach ($this->groups as $key => $group) {
            $this->register_settings_group($key, $group);
        }
    }

    private function register_settings_group(string $key, array $group): void
    {
        $prefix = $this->theme_prefix . ($this->prefixes[$key] ?? "{$key}_");
        $fields = $this->prepareFields($group['fields'], $prefix);

        acf_add_local_field_group([
            'key'      => $prefix . 'group_settings',
            'title'    => $this->lang->trans($group['title'], null, $group['title']),
            'fields'   => $fields,
            'location' => [[[
                'param'    => 'options_page',
                'operator' => '==',
                'value'    => $this->theme_prefix . $group['menu_slug'],
            ]]],
            'menu_order'           => $group['menu_order'] ?? 0,
            'position'             => 'acf_after_title',
            'style'                => 'default',
            'label_placement'      => 'top',
            'instruction_placement'=> 'label',
        ]);
    }

    /**
     * Fully multilingual: only uses LanguageManager to translate labels/instructions/choices
     */
    private function prepareFields(array $fields, string $prefix): array
    {
        $result = [];
        $current_user = function_exists('wp_get_current_user') ? wp_get_current_user() : false;

        foreach ($fields as $field) {
            if (!empty($field['capability']) && !current_user_can($field['capability'])) {
                continue;
            }
            if (!empty($field['visible_for_roles']) && $current_user !== false) {
                $allowed_roles = (array) $field['visible_for_roles'];
                if (!array_intersect($allowed_roles, (array) $current_user->roles)) {
                    continue;
                }
            }

            $field_copy = $field;
            $field_copy['key'] = $prefix . ($field_copy['name'] ?? $field_copy['type'] . '_' . uniqid());
            if (isset($field_copy['name']) && $field_copy['type'] !== 'tab') {
                $field_copy['name'] = $prefix . $field_copy['name'];
            }

            // Translate possible labels/instructions/choices only via LanguageManager
            if (isset($field_copy['label'])) {
                $field_copy['label'] = $this->lang->trans($field_copy['label'], null, $field_copy['label']);
            }
            if (isset($field_copy['instructions'])) {
                $field_copy['instructions'] = $this->lang->trans($field_copy['instructions'], null, $field_copy['instructions']);
            }
            if (isset($field_copy['choices']) && is_array($field_copy['choices'])) {
                foreach ($field_copy['choices'] as $choice_key => $choice_val) {
                    $field_copy['choices'][$choice_key] = $this->lang->trans($choice_val, null, $choice_val);
                }
            }

            // Recursively apply for sub_fields/layouts
            if (in_array($field_copy['type'], ['repeater', 'group', 'flexible_content'], true) && isset($field_copy['sub_fields'])) {
                $field_copy['sub_fields'] = $this->prepareFields($field_copy['sub_fields'], $field_copy['key'] . '_');
            }
            if ($field_copy['type'] === 'flexible_content' && isset($field_copy['layouts'])) {
                foreach ($field_copy['layouts'] as &$layout) {
                    if (isset($layout['sub_fields'])) {
                        $layout_prefix = $field_copy['key'] . '_' . ($layout['name'] ?? 'layout_') . '_';
                        $layout['sub_fields'] = $this->prepareFields($layout['sub_fields'], $layout_prefix);
                    }
                }
                unset($layout);
            }
            $result[] = $field_copy;
        }
        return $result;
    }

    /**
     * Clear cache on ACF save (per locale)
     */
    public function clearCacheOnSave($post_id): void
    {
        if ($post_id !== 'options' && substr($post_id, 0, 8) !== 'options_') {
            return;
        }

        $locale = $this->lang->getCurrentLocale();
        foreach ($this->groups as $key => $group) {
            $this->cache->flush_group($key . '_' . $locale);
        }
    }

    /**
     * Retrieve option value OR all options for a group - using localized keys
     */
    public function getOption($field = null, string $group = 'general')
    {
        $this->load_config();
        $prefix = $this->theme_prefix . ($this->prefixes[$group] ?? "{$group}_");
        $locale = $this->lang->getCurrentLocale();
        $option_lang = "option_" . $locale;

        if (empty($field)) {
            $cache_key = $group . '_' . $locale;
            $cached = $this->cache ? $this->cache->get($cache_key) : false;
            if ($cached !== false) return $cached;
            $result = [];
            if (!empty($this->groups[$group]['fields'])) {
                foreach ($this->groups[$group]['fields'] as $f) {
                    if (isset($f['name']) && $f['type'] !== 'tab') {
                        $fname = $prefix . $f['name'];
                        $result[$f['name']] = function_exists('get_field')
                            ? get_field($fname, 'option')
                            : null;
                    }
                }
                if ($this->cache) {
                    $this->cache->set($cache_key, $result, 3600);
                }
            }
            return $result;
        }
        $field_name = $prefix . $field;
        $cache_key = $group . '_' . $locale;
        $cached = $this->cache ? $this->cache->get($cache_key) : false;
        if ($cached !== false && isset($cached[$field])) {
            return $cached[$field];
        }
        $value = function_exists('get_field') ? get_field($field_name, $option_lang) : null;

        // Preload cache after first database fetch
        if ($this->cache && $cached === false && !empty($this->groups[$group]['fields'])) {
            $all = [];
            foreach ($this->groups[$group]['fields'] as $f) {
                if (isset($f['name']) && $f['type'] !== 'tab') {
                    $all_prefix_name = $prefix . $f['name'];
                    $all[$f['name']] = function_exists('get_field')
                        ? get_field($all_prefix_name, $option_lang)
                        : null;
                }
            }
            $this->cache->set($cache_key, $all, 3600);
            if (isset($all[$field])) {
                return $all[$field];
            }
        }
        return $value;
    }
}
