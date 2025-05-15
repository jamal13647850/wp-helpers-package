<?php
/**
 * Theme_Settings_ACF (Prefix Safe for Multi-theme Installations)
 * Registers theme options pages and ACF field groups using a central config
 * @package jamal13647850\wphelper
 * @version 2.1
 */

namespace jamal13647850\wphelpers\Utilities;

defined('ABSPATH') || exit;

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
        'general'   => 'general_',
        'header'    => 'header_',
        'footer'    => 'footer_',
        'homepage'  => 'homepage_',
        'shop'      => 'shop_',
        'inner'     => 'inner_',
        'contact'   => 'contact_',
        'about'     => 'about_',
        'social'    => 'social_',
        'scripts'   => 'scripts_',
    ];

    /**
     * Array of groups loaded from config
     * @var array
     */
    private $groups = [];

    /**
     * Path to config file
     * @var string|null
     */
    private $config_path = null;

    /**
     * Constructor: Load config, add hooks
     * @param string|null $theme_prefix
     * @param string|null $config_path
     */
    public function __construct($theme_prefix = null, $config_path = null)
    {
        // تعیین prefix اصلی بر اساس ورودی یا نام قالب فعال
        $stylesheet = function_exists('get_stylesheet') ? get_stylesheet() : 'defaulttheme';
        $this->theme_prefix = $theme_prefix ? $this->normalize_prefix($theme_prefix) : $this->normalize_prefix($stylesheet);

        $this->config_path = $config_path
            ? $config_path
            : get_template_directory() . '/config/theme_settings_definitions.php';

        add_action('admin_notices', [$this, 'check_acf_pro_admin_notices']);

        if (function_exists('acf_add_options_page')) {
            add_action('acf/init', [$this, 'load_config']);
            add_action('acf/init', [$this, 'register_options_pages'], 10);
            add_action('acf/init', [$this, 'register_field_groups'], 15);
        }
    }

    /**
     * Normalize theme prefix (accept string, end with underscore, only chars/digits/_)
     * @param string $prefix
     * @return string
     */
    private function normalize_prefix($prefix) {
        $prefix = strtolower($prefix);
        $prefix = preg_replace('/[^a-z0-9_]/', '_', $prefix);
        return rtrim($prefix, '_') . '_';
    }

    /**
     * Check if ACF Pro exists and required functions available. Admin notice if not.
     */
    public function check_acf_pro_admin_notices()
    {
        if (
            !function_exists('acf_add_options_page')
            && current_user_can('manage_options')
            && is_admin()
        ) {
            echo '<div class="notice notice-error"><p><strong>ACF Pro plugin is required for Theme Settings management. لطفاً افزونه ACF Pro را نصب و فعال نمایید.</strong></p></div>';
        }
    }

    /**
     * Load config/definitions once and keep for later use
     */
    public function load_config()
    {
        if (!$this->groups) {
            try {
                if (file_exists($this->config_path)) {
                    // بارگذاری فایل تنظیمات درون try/catch برای جلوگیری از خطای احتمالی
                    $config = require $this->config_path;
                    if (!is_array($config)) {
                        throw new \Exception("Config file does not return an array.");
                    }
                    $this->groups = $config;
                } else {
                    throw new \Exception("Config file not found: " . $this->config_path);
                }
            } catch (\Exception $e) {
                error_log("Error loading config in Theme_Settings_ACF: " . $e->getMessage());
                // در صورت بروز خطا، به صورت دلخواه می‌توان یک پیام به admin نشان داد.
            }
        }
    }

    /**
     * For multi-projects: set config path dynamically
     */
    public function setConfigPath($path)
    {
        $this->config_path = $path;
        $this->groups = []; // force reload
    }

    /**
     * Register main and sub options pages
     */
    public function register_options_pages()
    {
        try {
            if (!function_exists('acf_add_options_page') || !$this->groups) {
                return;
            }

            // Main options page (always exists)
            acf_add_options_page([
                'page_title'  => __('Theme Settings', 'your-textdomain'),
                'menu_title'  => __('Theme Settings', 'your-textdomain'),
                'menu_slug'   => $this->theme_prefix . 'theme-settings',
                'capability'  => 'manage_options',
                'redirect'    => false,
                'icon_url'    => 'dashicons-admin-customizer',
                'position'    => 60,
                // 'parent_slug' => 'themes.php' // فعال کنید اگر در زیرمنوی Appearance بخواهید
            ]);

            // Subpages per config
            foreach ($this->groups as $key => $group) {
                acf_add_options_sub_page([
                    'page_title'  => $group['title'],
                    'menu_title'  => $group['title'],
                    'parent_slug' => $this->theme_prefix . 'theme-settings',
                    'menu_slug'   => $this->theme_prefix . $group['menu_slug'],
                    'capability'  => $group['capability'] ?? 'manage_options',
                    'position'    => 60 + ($group['menu_order'] ?? 0),
                ]);
            }
        } catch (\Exception $e) {
            error_log("Error in register_options_pages in Theme_Settings_ACF: " . $e->getMessage());
        }
    }

    /**
     * Register all settings field groups by config
     */
    public function register_field_groups()
    {
        try {
            if (!$this->groups) {
                $this->load_config();
            }
            foreach ($this->groups as $key => $group) {
                try {
                    $this->register_settings_group($key, $group);
                } catch (\Exception $e) {
                    // ادامه فرآیند ثبت گروه‌های بعدی حتی در صورت بروز خطا در یکی از گروهها
                    error_log("Error registering field group '{$key}': " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            error_log("Error in register_field_groups in Theme_Settings_ACF: " . $e->getMessage());
        }
    }

    /**
     * Register a single field group (with super-prefix)
     */
    private function register_settings_group(string $key, array $group)
    {
        try {
            $prefix = $this->theme_prefix . ($this->prefixes[$key] ?? "{$key}_");

            $fields = $this->prepareFields($group['fields'], $prefix);

            acf_add_local_field_group([
                'key'                   => $prefix . 'group_settings',
                'title'                 => $group['title'],
                'fields'                => $fields,
                'location'              => [[[
                    'param'     => 'options_page',
                    'operator'  => '==',
                    'value'     => $this->theme_prefix . $group['menu_slug'],
                ]]],
                'menu_order'            => $group['menu_order'] ?? 0,
                'position'              => 'acf_after_title',
                'style'                 => 'default',
                'label_placement'       => 'top',
                'instruction_placement' => 'label',
            ]);
        } catch (\Exception $e) {
            // ثبت خطا برای گروه فعلی
            error_log("Error in register_settings_group for key '{$key}': " . $e->getMessage());
            // در صورت نیاز می‌توان به صورت نمایش پیغام نیز عمل کرد.
        }
    }

    /**
     * Recursive add correct super-prefix and group prefix for all fields
     */
    private function prepareFields(array $fields, string $prefix): array
    {
        $out = [];
        $current_user = function_exists('wp_get_current_user') ? wp_get_current_user() : false;
        foreach ($fields as $f) {
            $field = $f;

            // کنترل نقش روی فیلد (در صورت set در config)
            if (!empty($field['capability']) && !current_user_can($field['capability'])) {
                continue;
            }
            if (!empty($field['visible_for_roles']) && $current_user
                && !array_intersect($field['visible_for_roles'], $current_user->roles)) {
                continue;
            }

            // Unique global key
            $field['key'] = $prefix . ($field['name'] ?? $field['type'] . '_' . uniqid());

            // Skip prefix for tab
            if (isset($field['name']) && $field['type'] !== 'tab') {
                $field['name'] = $prefix . $field['name'];
            }

            // recursion for repeater/group/flexible_content
            if (($field['type'] === 'repeater' || $field['type'] === 'group' || $field['type'] === 'flexible_content') && isset($field['sub_fields'])) {
                $field['sub_fields'] = $this->prepareFields($field['sub_fields'], $field['key'] . '_');
            }
            if ($field['type'] === 'flexible_content' && isset($field['layouts'])) {
                foreach ($field['layouts'] as &$layout) {
                    if (isset($layout['sub_fields'])) {
                        $layout['sub_fields'] = $this->prepareFields($layout['sub_fields'], $field['key'] . '_' . ($layout['name'] ?? 'layout_') . '_');
                    }
                }
            }
            $out[] = $field;
        }
        return $out;
    }

    /**
     * Public method for getting theme option by field & group (super-prefix ready)
     */
    public function getOption($field, $group = 'general')
    {
        try {
            $prefix = $this->theme_prefix . ($this->prefixes[ $group] ?? "{ $group}_");
            $field_name = $prefix . $field;
            return function_exists('get_field')
                ? get_field($field_name, 'option')
                : null;
        } catch (\Exception $e) {
            error_log("Error in getOption for field '{$field}' in group '{$group}': " . $e->getMessage());
            return null;
        }
    }
}