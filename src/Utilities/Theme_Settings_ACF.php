<?php

/**
 * Theme_Settings_ACF (Prefix Safe for Multi-theme Installations)
 * Registers theme options pages and ACF field groups using a central config
 *
 * @package jamal13647850\wphelper
 * @version 2.1
 */

namespace jamal13647850\wphelpers\Utilities;

defined('ABSPATH') || exit;

use jamal13647850\wphelpers\Utilities\Theme_Settings_Cache;

class Theme_Settings_ACF
{
    /**
     * Super prefix for all field names and keys (theme unique)
     * @var string
     */
    private $theme_prefix;

    /**
     * Prefixes array for each group
     * @var array
     */
    private $prefixes = [
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

    /**
     * Array holding all groups loaded from config
     * @var array
     */
    private $groups = [];

    /**
     * Path to configuration file
     * @var string|null
     */
    private $config_path = null;

    /**
     * Cache instance
     * @var Theme_Settings_Cache|null
     */
    private $cache = null;

    /**
     * Constructor: Initialize properties and hooks
     *
     * @param string|null $theme_prefix Prefix for theme fields, optional
     * @param string|null $config_path  Path to config file, optional
     */
    public function __construct($theme_prefix = null, $config_path = null)
    {
        // Determine theme prefix: given prefix or active stylesheet folder name normalized
        $stylesheet = function_exists('get_stylesheet') ? get_stylesheet() : 'defaulttheme';
        $this->theme_prefix = $theme_prefix ? $this->normalize_prefix($theme_prefix) : $this->normalize_prefix($stylesheet);

        // Initialize cache instance for theme prefix
        $this->cache = new Theme_Settings_Cache($this->theme_prefix);

        // Define config path or fallback to default
        $this->config_path = $config_path ?: get_template_directory() . '/config/theme_settings_definitions.php';

        // Show admin notices regarding ACF Pro plugin status
        add_action('admin_notices', [$this, 'check_acf_pro_admin_notices']);

        // Register ACF options once ACF is ready
        if (function_exists('acf_add_options_page')) {
            add_action('acf/init', [$this, 'load_config']);
            add_action('acf/init', [$this, 'register_options_pages'], 10);
            add_action('acf/init', [$this, 'register_field_groups'], 15);
        }

        // Invalidate cache on options save
        add_action('acf/save_post', function ($post_id) {
            if ($post_id !== 'options') {
                return;
            }

            // Normalize theme prefix same as constructor do
            $theme_prefix = function_exists('get_stylesheet') ? get_stylesheet() : 'defaulttheme';
            $theme_prefix = strtolower(preg_replace('/[^a-z0-9_]/', '_', $theme_prefix)) . '_';

            $cache = new Theme_Settings_Cache($theme_prefix);

            // Flush all group caches
            foreach ($this->groups as $key => $group) {
                $cache->flush_group($key);
            }
        });
    }

    /**
     * Normalize theme prefix: lowercase, allowed chars, ends with underscore
     *
     * @param string $prefix Input prefix string
     * @return string Normalized prefix with trailing underscore
     */
    private function normalize_prefix(string $prefix): string
    {
        $prefix = strtolower($prefix);
        $prefix = preg_replace('/[^a-z0-9_]/', '_', $prefix);
        return rtrim($prefix, '_') . '_';
    }

    /**
     * Check if ACF Pro plugin is active; show admin error notice if missing
     *
     * @return void
     */
    public function check_acf_pro_admin_notices(): void
    {
        if (
            !function_exists('acf_add_options_page') &&
            current_user_can('manage_options') &&
            is_admin()
        ) {
            echo '<div class="notice notice-error"><p><strong>ACF Pro plugin is required for Theme Settings management. لطفاً افزونه ACF Pro را نصب و فعال نمایید.</strong></p></div>';
        }
    }

    /**
     * Load configuration groups from the config file once
     *
     * @return void
     */
    public function load_config(): void
    {
        if ($this->groups) {
            // Already loaded; do nothing
            return;
        }

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
            // Optionally, you may provide an admin notice here.
        }
    }

    /**
     * Dynamically set config path and force reload on next load
     *
     * @param string $path New config file path
     */
    public function setConfigPath(string $path): void
    {
        $this->config_path = $path;
        $this->groups = []; // Reset groups to force reload
    }

    /**
     * Register main options page and subpages for each group using ACF
     *
     * @return void
     */
    public function register_options_pages(): void
    {
        try {
            if (!function_exists('acf_add_options_page') || empty($this->groups)) {
                return;
            }

            // Register main options page
            acf_add_options_page([
                'page_title' => __('Theme Settings', 'your-textdomain'),
                'menu_title' => __('Theme Settings', 'your-textdomain'),
                'menu_slug'  => $this->theme_prefix . 'theme-settings',
                'capability' => 'manage_options',
                'redirect'   => false,
                'icon_url'   => 'dashicons-admin-customizer',
                'position'   => 60,
                // Uncomment below if you want this under Appearance submenu
                // 'parent_slug' => 'themes.php',
            ]);

            // Register subpages from config groups
            foreach ($this->groups as $key => $group) {
                acf_add_options_sub_page([
                    'page_title'  => $group['title'],
                    'menu_title'  => $group['title'],
                    'parent_slug' => $this->theme_prefix . 'theme-settings',
                    'menu_slug'   => $this->theme_prefix . $group['menu_slug'],
                    'capability'  => $group['capability'] ?? 'manage_options',
                    'position'   => 60 + ($group['menu_order'] ?? 0),
                ]);
            }
        } catch (\Exception $e) {
            error_log("Error in register_options_pages in Theme_Settings_ACF: " . $e->getMessage());
        }
    }

    /**
     * Register all field groups defined in configuration
     *
     * @return void
     */
    public function register_field_groups(): void
    {
        try {
            if (empty($this->groups)) {
                $this->load_config();
            }

            foreach ($this->groups as $key => $group) {
                try {
                    $this->register_settings_group($key, $group);
                } catch (\Exception $e) {
                    // Log error but continue processing other groups
                    error_log("Error registering field group '{$key}': " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            error_log("Error in register_field_groups in Theme_Settings_ACF: " . $e->getMessage());
        }
    }

    /**
     * Register a single settings group (field group) with prefix for keys/names
     *
     * @param string $key   Group key identifier
     * @param array  $group Group config array
     *
     * @return void
     */
    private function register_settings_group(string $key, array $group): void
    {
        try {
            // Determine group prefix incorporating theme prefix and group prefix
            $prefix = $this->theme_prefix . ($this->prefixes[$key] ?? "{$key}_");

            // Prepare fields recursively with prefixes
            $fields = $this->prepareFields($group['fields'], $prefix);

            // Register group with ACF
            acf_add_local_field_group([
                'key'                   => $prefix . 'group_settings',
                'title'                 => $group['title'],
                'fields'                => $fields,
                'location'              => [[[
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => $this->theme_prefix . $group['menu_slug'],
                ]]],
                'menu_order'            => $group['menu_order'] ?? 0,
                'position'              => 'acf_after_title',
                'style'                 => 'default',
                'label_placement'       => 'top',
                'instruction_placement' => 'label',
            ]);
        } catch (\Exception $e) {
            error_log("Error in register_settings_group for key '{$key}': " . $e->getMessage());
        }
    }

    /**
     * Recursively prepare fields, adding super-prefix and respecting visibility
     *
     * @param array  $fields Array of fields config
     * @param string $prefix Prefix string to be prepended to keys and names
     *
     * @return array Prepared fields with updated keys and properly prefixed sub-fields
     */
    private function prepareFields(array $fields, string $prefix): array
    {
        $result = [];
        $current_user = function_exists('wp_get_current_user') ? wp_get_current_user() : false;

        foreach ($fields as $field) {
            // Skip fields if current user does not meet capability requirements
            if (!empty($field['capability']) && !current_user_can($field['capability'])) {
                continue;
            }

            // Skip if user's roles are not in visible_for_roles
            if (!empty($field['visible_for_roles']) && $current_user !== false) {
                $allowed_roles = (array) $field['visible_for_roles'];
                if (!array_intersect($allowed_roles, (array) $current_user->roles)) {
                    continue;
                }
            }

            // Clone original field to avoid side effects
            $field_copy = $field;

            // Assign unique global key with prefix
            $field_copy['key'] = $prefix . ($field_copy['name'] ?? $field_copy['type'] . '_' . uniqid());

            // Prefix the 'name' except for tabs which do not have names
            if (isset($field_copy['name']) && $field_copy['type'] !== 'tab') {
                $field_copy['name'] = $prefix . $field_copy['name'];
            }

            // Recursively process sub_fields for complex types
            if (in_array($field_copy['type'], ['repeater', 'group', 'flexible_content'], true) && isset($field_copy['sub_fields'])) {
                $field_copy['sub_fields'] = $this->prepareFields($field_copy['sub_fields'], $field_copy['key'] . '_');
            }

            // For flexible content, process layouts and their sub_fields recursively
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
     * Get an option value from the theme settings, considering caching.
     *
     * @param string $field Field name without prefix
     * @param string $group Group key; default 'general'
     *
     * @return mixed|null Field value or null on failure
     */
    public function getOption(string $field, string $group = 'general')
    {
        try {
            $prefix = $this->theme_prefix . ($this->prefixes[$group] ?? "{$group}_");
            $field_name = $prefix . $field;

            // Attempt to retrieve from cache
            $cache_key = $group;
            $cached = $this->cache ? $this->cache->get($cache_key) : false;

            if ($cached !== false && isset($cached[$field_name])) {
                return $cached[$field_name];
            }

            // Retrieve original value from DB (options)
            $value = function_exists('get_field') ? get_field($field_name, 'option') : null;

            // Populate cache for the group if empty cache and group fields loaded
            if ($this->cache && $cached === false && !empty($this->groups[$group]['fields'])) {
                $all = [];

                foreach ($this->groups[$group]['fields'] as $f) {
                    if (isset($f['name']) && $f['type'] !== 'tab') {
                        $all_prefix_name = $prefix . $f['name'];
                        $all[$all_prefix_name] = function_exists('get_field')
                            ? get_field($all_prefix_name, 'option')
                            : null;
                    }
                }

                $this->cache->set($cache_key, $all, 3600); // Cache for 1 hour

                if (isset($all[$field_name])) {
                    return $all[$field_name];
                }
            }

            return $value;
        } catch (\Exception $e) {
            error_log("Error in getOption for field '{$field}' in group '{$group}': " . $e->getMessage());
            return null;
        }
    }
}