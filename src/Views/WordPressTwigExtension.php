<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Views;

defined('ABSPATH') || exit();

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class WordPressTwigExtension
 * 
 * Adds WordPress specific filters and functions to Twig.
 */
class WordPressTwigExtension extends AbstractExtension
{
    /**
     * Get the name of the extension.
     *
     * @return string Extension name
     */
    public function getName(): string
    {
        return 'wordpress';
    }
    
    /**
     * Get the filters defined in this extension.
     *
     * @return TwigFilter[] Filters
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('wpautop', 'wpautop'),
            new TwigFilter('wp_trim_words', 'wp_trim_words'),
            new TwigFilter('sanitize', [$this, 'sanitize']),
            new TwigFilter('esc_html', 'esc_html'),
            new TwigFilter('esc_attr', 'esc_attr'),
            new TwigFilter('esc_url', 'esc_url'),
            new TwigFilter('esc_js', 'esc_js'),
            new TwigFilter('esc_textarea', 'esc_textarea'),
            new TwigFilter('wp_kses_post', 'wp_kses_post'),
            new TwigFilter('format_date', [$this, 'formatDate']),
            new TwigFilter('human_time_diff', 'human_time_diff'),
            new TwigFilter('apply_filters', 'apply_filters', ['is_variadic' => true]),
            new TwigFilter('shortcodes', 'do_shortcode'),
            new TwigFilter('slugify', 'sanitize_title'),
            new TwigFilter('translate', '__'),
        ];
    }
    
    /**
     * Get the functions defined in this extension.
     *
     * @return TwigFunction[] Functions
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('wp_query', [$this, 'wpQuery']),
            new TwigFunction('wp_menu', [$this, 'wpMenu']),
            new TwigFunction('wp_sidebar', [$this, 'wpSidebar']),
            new TwigFunction('wp_pagination', [$this, 'wpPagination']),
            new TwigFunction('wp_breadcrumbs', [$this, 'wpBreadcrumbs']),
            new TwigFunction('wp_image', [$this, 'wpImage']),
            new TwigFunction('wp_image_url', [$this, 'wpImageUrl']),
            new TwigFunction('wp_post_thumbnail', [$this, 'wpPostThumbnail']),
            new TwigFunction('wp_enqueue_asset', [$this, 'wpEnqueueAsset']),
            new TwigFunction('wp_localize_script', 'wp_localize_script'),
            new TwigFunction('wp_get_archives', 'wp_get_archives'),
            new TwigFunction('wp_meta', 'wp_meta'),
            new TwigFunction('wp_login_form', 'wp_login_form'),
            new TwigFunction('wp_nonce_field', 'wp_nonce_field'),
            new TwigFunction('wp_create_nonce', 'wp_create_nonce'),
            new TwigFunction('wp_get_attachment_image', 'wp_get_attachment_image'),
            new TwigFunction('wp_get_attachment_url', 'wp_get_attachment_url'),
            new TwigFunction('wp_get_attachment_image_src', 'wp_get_attachment_image_src'),
            new TwigFunction('wp_get_attachment_metadata', 'wp_get_attachment_metadata'),
            new TwigFunction('wp_get_attachment_caption', 'wp_get_attachment_caption'),
            new TwigFunction('wp_get_post_terms', 'wp_get_post_terms'),
            new TwigFunction('wp_get_object_terms', 'wp_get_object_terms'),
            new TwigFunction('wp_list_comments', 'wp_list_comments'),
            new TwigFunction('wp_link_pages', 'wp_link_pages'),
            new TwigFunction('wp_reset_postdata', 'wp_reset_postdata'),
            new TwigFunction('wp_reset_query', 'wp_reset_query'),
            new TwigFunction('wp_doing_ajax', 'wp_doing_ajax'),
            new TwigFunction('wp_doing_cron', 'wp_doing_cron'),
            new TwigFunction('wp_upload_dir', 'wp_upload_dir'),
            new TwigFunction('wp_get_theme', 'wp_get_theme'),
            new TwigFunction('wp_get_current_user', 'wp_get_current_user'),
            new TwigFunction('wp_get_current_user_id', 'get_current_user_id'),
            new TwigFunction('wp_get_post_type', 'get_post_type'),
            new TwigFunction('wp_get_post_types', 'get_post_types'),
            new TwigFunction('wp_get_post_type_object', 'get_post_type_object'),
            new TwigFunction('wp_get_post_type_archive_link', 'get_post_type_archive_link'),
            new TwigFunction('wp_get_post_format', 'get_post_format'),
            new TwigFunction('wp_get_the_author_posts_link', 'get_the_author_posts_link'),
            new TwigFunction('wp_get_the_author_meta', 'get_the_author_meta'),
            new TwigFunction('wp_get_the_category_list', 'get_the_category_list'),
            new TwigFunction('wp_get_the_tag_list', 'get_the_tag_list'),
            new TwigFunction('wp_get_the_term_list', 'get_the_term_list'),
            new TwigFunction('wp_get_the_terms', 'get_the_terms'),
            new TwigFunction('wp_get_the_time', 'get_the_time'),
            new TwigFunction('wp_get_the_modified_time', 'get_the_modified_time'),
            new TwigFunction('wp_get_the_date', 'get_the_date'),
            new TwigFunction('wp_get_the_modified_date', 'get_the_modified_date'),
            new TwigFunction('wp_get_the_excerpt', 'get_the_excerpt'),
            new TwigFunction('wp_has_excerpt', 'has_excerpt'),
            new TwigFunction('wp_get_the_ID', 'get_the_ID'),
            new TwigFunction('wp_get_the_title', 'get_the_title'),
            new TwigFunction('wp_get_the_content', 'get_the_content'),
            new TwigFunction('wp_get_the_permalink', 'get_the_permalink'),
            new TwigFunction('wp_get_permalink', 'get_permalink'),
            new TwigFunction('wp_get_post_permalink', 'get_post_permalink'),
            new TwigFunction('wp_get_edit_post_link', 'get_edit_post_link'),
            new TwigFunction('wp_get_delete_post_link', 'get_delete_post_link'),
            new TwigFunction('wp_get_comments_number', 'get_comments_number'),
            new TwigFunction('wp_get_comments_popup_link', 'comments_popup_link'),
            new TwigFunction('wp_get_avatar', 'get_avatar'),
            new TwigFunction('wp_get_avatar_url', 'get_avatar_url'),
            new TwigFunction('wp_get_search_form', 'get_search_form'),
            new TwigFunction('wp_get_calendar', 'get_calendar'),
            new TwigFunction('wp_get_template_part', 'get_template_part'),
            new TwigFunction('wp_get_locale', 'get_locale'),
            new TwigFunction('wp_get_language_attributes', 'get_language_attributes'),
            new TwigFunction('wp_get_bloginfo', 'get_bloginfo'),
            new TwigFunction('wp_get_option', 'get_option'),
            new TwigFunction('wp_get_theme_mod', 'get_theme_mod'),
            new TwigFunction('wp_get_theme_mods', 'get_theme_mods'),
            new TwigFunction('wp_get_custom_logo', 'get_custom_logo'),
            new TwigFunction('wp_get_site_url', 'get_site_url'),
            new TwigFunction('wp_get_home_url', 'get_home_url'),
            new TwigFunction('wp_get_admin_url', 'get_admin_url'),
            new TwigFunction('wp_get_rest_url', 'get_rest_url'),
            new TwigFunction('wp_get_privacy_policy_url', 'get_privacy_policy_url'),
            new TwigFunction('wp_get_post_meta', 'get_post_meta'),
            new TwigFunction('wp_get_term_meta', 'get_term_meta'),
            new TwigFunction('wp_get_user_meta', 'get_user_meta'),
            new TwigFunction('wp_get_metadata', 'get_metadata'),
            new TwigFunction('wp_get_post_custom', 'get_post_custom'),
            new TwigFunction('wp_get_post_custom_values', 'get_post_custom_values'),
            new TwigFunction('wp_get_post_custom_keys', 'get_post_custom_keys'),
            new TwigFunction('wp_get_post_status', 'get_post_status'),
            new TwigFunction('wp_get_post_stati', 'get_post_stati'),
            new TwigFunction('wp_get_post_statuses', 'get_post_statuses'),
            new TwigFunction('is_home', 'is_home'),
            new TwigFunction('is_front_page', 'is_front_page'),
            new TwigFunction('is_single', 'is_single'),
            new TwigFunction('is_page', 'is_page'),
            new TwigFunction('is_archive', 'is_archive'),
            new TwigFunction('is_category', 'is_category'),
            new TwigFunction('is_tag', 'is_tag'),
            new TwigFunction('is_tax', 'is_tax'),
            new TwigFunction('is_author', 'is_author'),
            new TwigFunction('is_search', 'is_search'),
            new TwigFunction('is_404', 'is_404'),
            new TwigFunction('is_user_logged_in', 'is_user_logged_in'),
            new TwigFunction('current_user_can', 'current_user_can'),
        ];
    }
    
    /**
     * Sanitize a value based on the type.
     *
     * @param mixed $value Value to sanitize
     * @param string $type Sanitization type
     * @return mixed Sanitized value
     */
    public function sanitize($value, string $type = 'text')
    {
        switch ($type) {
            case 'email':
                return sanitize_email($value);
            case 'url':
                return sanitize_url($value);
            case 'title':
                return sanitize_title($value);
            case 'key':
                return sanitize_key($value);
            case 'file_name':
                return sanitize_file_name($value);
            case 'html_class':
                return sanitize_html_class($value);
            case 'meta':
                return sanitize_meta($value, '', '');
            case 'sql':
                return esc_sql($value);
            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Format a date.
     *
     * @param mixed $date Date to format
     * @param string $format Date format
     * @return string Formatted date
     */
    public function formatDate($date, string $format = ''): string
    {
        if (empty($format)) {
            $format = get_option('date_format');
        }
        
        if (is_numeric($date)) {
            return date_i18n($format, (int)$date);
        }
        
        return date_i18n($format, strtotime($date));
    }
    
    /**
     * Execute a WordPress query.
     *
     * @param array $args Query arguments
     * @return array Query results
     */
    public function wpQuery(array $args = []): array
    {
        $query = new \WP_Query($args);
        $result = [
            'posts' => $query->posts,
            'post_count' => $query->post_count,
            'found_posts' => $query->found_posts,
            'max_num_pages' => $query->max_num_pages,
            'current_page' => max(1, get_query_var('paged')),
            'is_single' => $query->is_single(),
            'is_preview' => $query->is_preview(),
            'is_page' => $query->is_page(),
            'is_archive' => $query->is_archive(),
            'is_date' => $query->is_date(),
            'is_year' => $query->is_year(),
            'is_month' => $query->is_month(),
            'is_day' => $query->is_day(),
            'is_time' => $query->is_time(),
            'is_author' => $query->is_author(),
            'is_category' => $query->is_category(),
            'is_tag' => $query->is_tag(),
            'is_tax' => $query->is_tax(),
            'is_search' => $query->is_search(),
            'is_feed' => $query->is_feed(),
            'is_comment_feed' => $query->is_comment_feed(),
            'is_trackback' => $query->is_trackback(),
            'is_home' => $query->is_home(),
            'is_404' => $query->is_404(),
            'is_embed' => $query->is_embed(),
            'is_paged' => $query->is_paged(),
            'is_admin' => $query->is_admin(),
            'is_attachment' => $query->is_attachment(),
            'is_singular' => $query->is_singular(),
            'is_robots' => $query->is_robots(),
            'is_favicon' => $query->is_favicon(),
            'is_posts_page' => $query->is_posts_page(),
            'is_post_type_archive' => $query->is_post_type_archive(),
        ];
        
        wp_reset_postdata();
        
        return $result;
    }
    
    /**
     * Render a WordPress menu.
     *
     * @param string|array $args Menu arguments
     * @return string Menu HTML
     */
    public function wpMenu($args = []): string
    {
        if (is_string($args)) {
            $args = ['theme_location' => $args];
        }
        
        $defaults = [
            'echo' => false,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        return wp_nav_menu($args);
    }
    
    /**
     * Render a WordPress sidebar.
     *
     * @param string $id Sidebar ID
     * @param array $args Sidebar arguments
     * @return string|bool Sidebar HTML or false if not active
     */
    public function wpSidebar(string $id, array $args = [])
    {
        if (!is_active_sidebar($id)) {
            return false;
        }
        
        ob_start();
        dynamic_sidebar($id);
        return ob_get_clean();
    }
    
    /**
     * Render WordPress pagination.
     *
     * @param array $args Pagination arguments
     * @return string Pagination HTML
     */
    public function wpPagination(array $args = []): string
    {
        $defaults = [
            'mid_size' => 2,
            'prev_text' => __('&laquo; Previous', 'wphelpers'),
            'next_text' => __('Next &raquo;', 'wphelpers'),
            'screen_reader_text' => __('Posts navigation', 'wphelpers'),
            'type' => 'array',
            'total' => 0,
            'current' => 0,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // If total and current are set, use them
        if ($args['total'] > 0 && $args['current'] > 0) {
            $links = paginate_links($args);
        } else {
            global $wp_query;
            $args['total'] = $wp_query->max_num_pages;
            $args['current'] = max(1, get_query_var('paged'));
            $links = paginate_links($args);
        }
        
        if (is_array($links)) {
            $html = '<nav class="navigation pagination" role="navigation">';
            $html .= '<h2 class="screen-reader-text">' . $args['screen_reader_text'] . '</h2>';
            $html .= '<div class="nav-links">';
            $html .= implode('', $links);
            $html .= '</div>';
            $html .= '</nav>';
            
            return $html;
        }
        
        return '';
    }
    
    /**
     * Render WordPress breadcrumbs.
     *
     * @param array $args Breadcrumbs arguments
     * @return string Breadcrumbs HTML
     */
    public function wpBreadcrumbs(array $args = []): string
    {
        $defaults = [
            'delimiter' => '&raquo;',
            'home' => __('Home', 'wphelpers'),
            'show_home' => true,
            'show_current' => true,
            'before' => '<span class="current">',
            'after' => '</span>',
            'before_item' => '',
            'after_item' => '',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $html = '<nav class="breadcrumbs" aria-label="' . __('Breadcrumbs', 'wphelpers') . '">';
        $html .= '<ol class="breadcrumb">';
        
        // Home link
        if ($args['show_home']) {
            $html .= $args['before_item'] . '<li class="breadcrumb-item"><a href="' . esc_url(home_url('/')) . '">' . $args['home'] . '</a></li>' . $args['after_item'];
        }
        
        if (is_category() || is_single()) {
            $html .= $args['before_item'] . '<li class="breadcrumb-item">' . $args['delimiter'] . ' ';
            
            // Category
            if (is_category()) {
                $cat = get_category(get_query_var('cat'), false);
                
                if ($cat->parent != 0) {
                    $parent_categories = get_category_parents($cat->parent, true, ' ' . $args['delimiter'] . ' ');
                    $html .= substr($parent_categories, 0, -3);
                }
                
                $html .= $args['before'] . single_cat_title('', false) . $args['after'];
            }
            
            // Single post
            if (is_single()) {
                // Categories
                $categories = get_the_category();
                
                if (!empty($categories)) {
                    $category = $categories[0];
                    
                    if ($category->parent != 0) {
                        $parent_categories = get_category_parents($category->parent, true, ' ' . $args['delimiter'] . ' ');
                        $html .= substr($parent_categories, 0, -3);
                    }
                    
                    $html .= '<a href="' . esc_url(get_category_link($category->term_id)) . '">' . $category->name . '</a> ' . $args['delimiter'] . ' ';
                }
                
                // Post title
                if ($args['show_current']) {
                    $html .= $args['before'] . get_the_title() . $args['after'];
                }
            }
            
            $html .= '</li>' . $args['after_item'];
        } elseif (is_page()) {
            $html .= $args['before_item'] . '<li class="breadcrumb-item">' . $args['delimiter'] . ' ';
            
            // Parent pages
            if ($post->post_parent) {
                $parent_id = $post->post_parent;
                $breadcrumbs = [];
                
                while ($parent_id) {
                    $page = get_post($parent_id);
                    $breadcrumbs[] = '<a href="' . esc_url(get_permalink($page->ID)) . '">' . get_the_title($page->ID) . '</a>';
                    $parent_id = $page->post_parent;
                }
                
                $breadcrumbs = array_reverse($breadcrumbs);
                
                foreach ($breadcrumbs as $crumb) {
                    $html .= $crumb . ' ' . $args['delimiter'] . ' ';
                }
            }
            
            // Current page
            if ($args['show_current']) {
                $html .= $args['before'] . get_the_title() . $args['after'];
            }
            
            $html .= '</li>' . $args['after_item'];
        } elseif (is_tag()) {
            $html .= $args['before_item'] . '<li class="breadcrumb-item">' . $args['delimiter'] . ' ' . $args['before'] . single_tag_title('', false) . $args['after'] . '</li>' . $args['after_item'];
        } elseif (is_author()) {
            $html .= $args['before_item'] . '<li class="breadcrumb-item">' . $args['delimiter'] . ' ' . $args['before'] . get_the_author() . $args['after'] . '</li>' . $args['after_item'];
        } elseif (is_year()) {
            $html .= $args['before_item'] . '<li class="breadcrumb-item">' . $args['delimiter'] . ' ' . $args['before'] . get_the_date('Y') . $args['after'] . '</li>' . $args['after_item'];
        } elseif (is_month()) {
            $html .= $args['before_item'] . '<li class="breadcrumb-item">' . $args['delimiter'] . ' ' . $args['before'] . get_the_date('F Y') . $args['after'] . '</li>' . $args['after_item'];
        } elseif (is_day()) {
            $html .= $args['before_item'] . '<li class="breadcrumb-item">' . $args['delimiter'] . ' ' . $args['before'] . get_the_date() . $args['after'] . '</li>' . $args['after_item'];
        } elseif (is_tax()) {
            $html .= $args['before_item'] . '<li class="breadcrumb-item">' . $args['delimiter'] . ' ' . $args['before'] . single_term_title('', false) . $args['after'] . '</li>' . $args['after_item'];
        } elseif (is_post_type_archive()) {
            $html .= $args['before_item'] . '<li class="breadcrumb-item">' . $args['delimiter'] . ' ' . $args['before'] . post_type_archive_title('', false) . $args['after'] . '</li>' . $args['after_item'];
        } elseif (is_search()) {
            $html .= $args['before_item'] . '<li class="breadcrumb-item">' . $args['delimiter'] . ' ' . $args['before'] . __('Search results for', 'wphelpers') . ' "' . get_search_query() . '"' . $args['after'] . '</li>' . $args['after_item'];
        } elseif (is_404()) {
            $html .= $args['before_item'] . '<li class="breadcrumb-item">' . $args['delimiter'] . ' ' . $args['before'] . __('404 Not Found', 'wphelpers') . $args['after'] . '</li>' . $args['after_item'];
        }
        
        $html .= '</ol>';
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Get an image with attributes.
     *
     * @param int $attachment_id Attachment ID
     * @param string|array $size Image size
     * @param array $attr Image attributes
     * @return string Image HTML
     */
    public function wpImage(int $attachment_id, $size = 'thumbnail', array $attr = []): string
    {
        return wp_get_attachment_image($attachment_id, $size, false, $attr);
    }
    
    /**
     * Get an image URL.
     *
     * @param int $attachment_id Attachment ID
     * @param string|array $size Image size
     * @return string|false Image URL or false if not found
     */
    public function wpImageUrl(int $attachment_id, $size = 'thumbnail')
    {
        $image = wp_get_attachment_image_src($attachment_id, $size);
        return $image ? $image[0] : false;
    }
    
    /**
     * Get a post thumbnail.
     *
     * @param int|null $post_id Post ID
     * @param string|array $size Image size
     * @param array $attr Image attributes
     * @return string Post thumbnail HTML
     */
    public function wpPostThumbnail(?int $post_id = null, $size = 'thumbnail', array $attr = []): string
    {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        
        if (has_post_thumbnail($post_id)) {
            return get_the_post_thumbnail($post_id, $size, $attr);
        }
        
        return '';
    }
    
    /**
     * Enqueue an asset.
     *
     * @param string $handle Asset handle
     * @param string $src Asset source
     * @param array $deps Asset dependencies
     * @param string|bool $ver Asset version
     * @param bool $in_footer Whether to enqueue in footer
     * @param string $type Asset type (script or style)
     * @return bool True on success, false on failure
     */
    public function wpEnqueueAsset(string $handle, string $src, array $deps = [], $ver = false, bool $in_footer = true, string $type = 'script'): bool
    {
        if ($type === 'script') {
            wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
            return true;
        } elseif ($type === 'style') {
            wp_enqueue_style($handle, $src, $deps, $ver);
            return true;
        }
        
        return false;
    }
}
