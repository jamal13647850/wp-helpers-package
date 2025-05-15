<?php
/**
 * Theme_Settings_ACF
 * Registers theme options pages and ACF field groups using a central config
 * @package YourTheme
 * @version 2.0
 */
namespace YourTheme\Utilities;

defined('ABSPATH') || exit;

class Theme_Settings_ACF
{
    /**
     * Prefixes array
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
     * Constructor: Load config, add hooks
     */
    public function __construct()
    {
        add_action('acf/init', [$this, 'check_acf_pro']);
        add_action('acf/init', [$this, 'load_config']);
        add_action('acf/init', [$this, 'register_options_pages'], 10);
        add_action('acf/init', [$this, 'register_field_groups'], 15);
    }

    /**
     * Check if ACF Pro exists and required functions available.
     * Admin notice if not.
     */
    public function check_acf_pro()
    {
        if (!function_exists('acf_add_options_page')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p><strong>ACF Pro plugin is required for Theme Settings!</strong></p></div>';
            });
        }
    }

    /**
     * Load config/definitions once and keep for later use
     */
    public function load_config()
    {
        if (!$this->groups) {
            $config_path = get_template_directory() . '/config/theme_settings_definitions.php';
            if (file_exists($config_path)) {
                $this->groups = require $config_path;
            }
        }
    }

    /**
     * Register main and sub options pages
     */
    public function register_options_pages()
    {
        if (!function_exists('acf_add_options_page') || !$this->groups) {
            return;
        }

        // Main options page (always exists)
        acf_add_options_page([
            'page_title'  => __('Theme Settings', 'your-textdomain'),
            'menu_title'  => __('Theme Settings', 'your-textdomain'),
            'menu_slug'   => 'theme-settings',
            'capability'  => 'manage_options',
            'redirect'    => false,
            'icon_url'    => 'dashicons-admin-customizer',
            'position'    => 60,
        ]);
        // Subpages per config
        foreach ($this->groups as $key => $group) {
            acf_add_options_sub_page([
                'page_title'  => $group['title'],
                'menu_title'  => $group['title'],
                'parent_slug' => 'theme-settings',
                'menu_slug'   => $group['menu_slug'],
                'capability'  => 'manage_options',
                'position'    => 60 + ($group['menu_order'] ?? 0),
            ]);
        }
    }

    /**
     * Register all settings field groups by config
     */
    public function register_field_groups()
    {
        if (!$this->groups) {
            $this->load_config();
        }
        foreach ($this->groups as $key => $group) {
            $this->register_settings_group($key, $group);
        }
    }

    /**
     * Register a single field group
     */
    private function register_settings_group(string $key, array $group)
    {
        $prefix = $this->prefixes[$key] ?? "{$key}_";
        $fields = $this->prepareFields($group['fields'], $prefix);

        acf_add_local_field_group([
            'key'                   => $prefix . 'group_settings',
            'title'                 => $group['title'],
            'fields'                => $fields,
            'location'              => [[[
                'param'     => 'options_page',
                'operator'  => '==',
                'value'     => $group['menu_slug'],
            ]]],
            'menu_order'            => $group['menu_order'] ?? 0,
            'position'              => 'acf_after_title',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
        ]);
    }


    /**
     * Prepare and standardize all fields with correct prefix, unique key, full name, and recursion for sub-fields.
     *
     * @param array $fields
     * @param string $prefix
     * @return array
     */
    private function prepareFields(array $fields, string $prefix): array
    {
        $out = [];
        foreach ($fields as $f) {
            $field = $f;

            // Fallback if 'type' missing
            if (!isset($field['type'])) {
                continue;
            }
            // Unique ACF key (must be unique globally!)
            $field['key'] = $prefix . ($field['name'] ?? $field['type'] . '_' . uniqid());

            // Always apply prefix to name, except for tab!
            if (isset($field['name']) && $field['type'] !== 'tab') {
                $field['name'] = $prefix . $field['name'];
            }

            // -- Recursively apply for repeater/flexible/sub_fields
            if (($field['type'] === 'repeater' || $field['type'] === 'group' || $field['type'] === 'flexible_content') && isset($field['sub_fields'])) {
                $field['sub_fields'] = $this->prepareFields($field['sub_fields'], $field['key'] . '_');
            }
            // -- Recursively apply for layouts (Flexible Content)
            if ($field['type'] === 'flexible_content' && isset($field['layouts'])) {
                foreach ($field['layouts'] as &$layout) {
                    if (isset($layout['sub_fields'])) {
                        $layout['sub_fields'] = $this->prepareFields($layout['sub_fields'], $field['key'] . '_' . ($layout['name'] ?? 'layout_') . '_');
                    }
                }
                $field['layouts'] = $layout ?? [];
            }
            $out[] = $field;
        }
        return $out;
    }

}