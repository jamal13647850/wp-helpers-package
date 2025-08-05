<?php

/*
Sayyed Jamal Ghasemi — Full-Stack Developer
📧 info@jamalghasemi.com
🔗 LinkedIn: https://www.linkedin.com/in/jamal1364/
📸 Instagram: https://www.instagram.com/jamal13647850
💬 Telegram: https://t.me/jamal13647850
🌐 https://jamalghasemi.com
*/

namespace jamal13647850\wphelpers\Utilities;

use jamal13647850\wphelpers\Utilities\Theme_Settings_Cache;
use jamal13647850\wphelpers\Language\LanguageManager;

defined('ABSPATH') || exit;

/**
 * Class Theme_Settings_ACF
 *
 * Handles all ACF theme settings options with full multilingual support using LanguageManager.
 *
 * - Registers options pages and subpages
 * - Loads field group definitions from a config file
 * - Handles field translation via LanguageManager
 * - Provides settings retrieval and cache management
 *
 * @package jamal13647850\wphelpers
 * @author  Sayyed Jamal Ghasemi <info@jamalghasemi.com>
 * @link    https://jamalghasemi.com
 * @since   1.0.0
 */
class Theme_Settings_ACF
{
    /**
     * Theme prefix used for all options/groups.
     *
     * @var string
     */
    private string $theme_prefix;

    /**
     * Default field prefixes per group.
     *
     * @var array
     */
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

    /**
     * Loaded field group configurations.
     *
     * @var array
     */
    private array $groups = [];

    /**
     * Path to configuration file.
     *
     * @var string|null
     */
    private ?string $config_path = null;

    /**
     * Cache handler instance.
     *
     * @var Theme_Settings_Cache|null
     */
    private ?Theme_Settings_Cache $cache = null;

    /**
     * LanguageManager instance for translations.
     *
     * @var LanguageManager
     */
    private LanguageManager $lang;

    /**
     * Theme_Settings_ACF constructor.
     *
     * Initializes theme prefix, loads config, sets up cache, and registers hooks.
     *
     * @param string|null $theme_prefix Custom theme prefix (optional)
     * @param string|null $config_path  Path to config file (optional)
     *
     * @example
     *   $ts = new Theme_Settings_ACF('mytheme', '/my/path/to/config.php');
     */
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

    /**
     * Normalize a given prefix string.
     *
     * Ensures prefix is lowercase, only alphanumeric/underscores, and ends with "_".
     *
     * @param string $prefix
     * @return string
     */
    private function normalize_prefix(string $prefix): string
    {
        $prefix = strtolower($prefix);
        $prefix = preg_replace('/[^a-z0-9_]/', '_', $prefix);
        return rtrim($prefix, '_') . '_';
    }

    /**
     * Display admin notice if ACF Pro is not installed/active.
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
     * Loads field group configuration from file, if not already loaded.
     *
     * Will only attempt to load once per request.
     *
     * @return void
     */
    public function load_config(): void
    {
        if ($this->groups) {
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
        }
    }

    /**
     * Change the config path at runtime and clear cached groups.
     *
     * @param string $path Absolute path to new config file.
     * @return void
     *
     * @example
     *   $ts->setConfigPath('/custom/path/config.php');
     */
    public function setConfigPath(string $path): void
    {
        $this->config_path = $path;
        $this->groups = [];
    }

    /**
     * Register ACF options main page and sub pages.
     *
     * All labels and titles are translated via LanguageManager.
     *
     * @return void
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
     * Register all ACF field groups/fields using config.
     *
     * @return void
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

    /**
     * Register a single ACF settings group as a field group.
     *
     * @param string $key   Group identifier.
     * @param array  $group Group configuration.
     * @return void
     */
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
            'menu_order'            => $group['menu_order'] ?? 0,
            'position'              => 'acf_after_title',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
        ]);
    }

    /**
     * Prepare fields array for ACF, translating all labels/instructions/choices.
     *
     * Also applies prefixing and field capability/role checks.
     * Recursively handles sub_fields for repeater/group/flexible_content types.
     *
     * @param array  $fields Field definitions array.
     * @param string $prefix Field name prefix.
     * @return array Prepared/translated fields.
     */
    private function prepareFields(array $fields, string $prefix): array
    {
        $result = [];
        $current_user = function_exists('wp_get_current_user') ? wp_get_current_user() : false;

        foreach ($fields as $field) {
            // Capability restriction
            if (!empty($field['capability']) && !current_user_can($field['capability'])) {
                continue;
            }
            // Restrict by visible_for_roles, if set
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

            // Translate label/instructions/choices
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
            // Prefix conditional logic fields
            if (!empty($field_copy['conditional_logic'])) {
                $field_copy['conditional_logic'] = $this->prefixConditionalLogicFields($field_copy['conditional_logic'], $prefix);
            }

            // Recursively handle sub_fields and layouts
            if (
                in_array($field_copy['type'], ['repeater', 'group', 'flexible_content'], true)
                && isset($field_copy['sub_fields'])
            ) {
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
     * Prefixes only the 'field' keys inside conditional_logic arrays.
     *
     * @param array  $conditionalLogic Array of conditional logic rules (ACF format)
     * @param string $prefix           Prefix to apply to field keys
     * @return array Updated conditional logic with prefixed field keys
     *
     * @example
     *   $cond = [
     *      [
     *          ['field' => 'show_extra', 'operator' => '==', 'value' => '1'],
     *      ]
     *   ];
     *   $prefixed = $this->prefixConditionalLogicFields($cond, 'mytheme_');
     */
    protected function prefixConditionalLogicFields(array $conditionalLogic, string $prefix): array
    {
        foreach ($conditionalLogic as &$group) {
            if (is_array($group)) {
                foreach ($group as &$rule) {
                    if (
                        is_array($rule)
                        && isset($rule['field'])
                        && strpos($rule['field'], $prefix) !== 0 // Prevent double-prefix
                    ) {
                        $rule['field'] = $prefix . $rule['field'];
                    }
                }
            }
        }
        return $conditionalLogic;
    }

    /**
     * Clears cache for all groups in current locale when an options page is saved.
     *
     * Should be hooked to 'acf/save_post'.
     *
     * @param string|int $post_id Saved post identifier
     * @return void
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
     * Retrieve an option value or all option values for a group.
     *
     * Uses localized (per-locale) cache for optimal performance.
     *
     * @param string|null $field Field name, or null for all fields in the group.
     * @param string      $group Group key (default: 'general')
     * @return mixed Value of the requested field, or associative array of all fields if $field is null.
     *
     * @example
     *   $logo = $ts->getOption('logo', 'header');
     *   $all_general = $ts->getOption(null, 'general');
     */
    public function getOption($field = null, string $group = 'general')
    {
        $this->load_config();
        $prefix = $this->theme_prefix . ($this->prefixes[$group] ?? "{$group}_");
        $locale = $this->lang->getCurrentLocale();
        $option_lang = "option_" . $locale;

        if (empty($field)) {
            // Retrieve all fields for the group, using cache if available
            $cache_key = $group . '_' . $locale;
            $cached = $this->cache ? $this->cache->get($cache_key) : false;
            if ($cached !== false) {
                return $cached;
            }
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
                    $this->cache->set($cache_key, $result, 604800);
                }
            }
            return $result;
        }
        // Retrieve a single field
        $field_name = $prefix . $field;
        $cache_key = $group . '_' . $locale;
        $cached = $this->cache ? $this->cache->get($cache_key) : false;
        if ($cached !== false && isset($cached[$field])) {
            return $cached[$field];
        }
        $value = function_exists('get_field') ? get_field($field_name, $option_lang) : null;

        // Populate cache if not already cached
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
            $this->cache->set($cache_key, $all, 604800);
            if (isset($all[$field])) {
                return $all[$field];
            }
        }
        return $value;
    }
}
