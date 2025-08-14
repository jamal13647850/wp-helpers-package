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

namespace jamal13647850\wphelpers\Navigation;

use Walker_Nav_Menu;

defined('ABSPATH') || exit();

/**
 * MultiColumnDesktopWalker
 *
 * A specialized WordPress walker for rendering desktop navigation menus with
 * a two-level, multi-column dropdown (root level + one submenu level only).
 * It buffers first-level children and distributes them into N columns.
 *
 * ## Features
 * - Root items render as <li> with optional dropdown trigger arrow
 * - Only depth 0 (root) and depth 1 (direct children) are rendered
 * - Children are collected and laid out into configured columns
 * - Optional FontAwesome icons, resolved from `attr_title` or CSS classes
 *
 * ## Usage
 * Register this walker when rendering a menu:
 *
 * ```php
 * wp_nav_menu([
 *     'theme_location' => 'primary',
 *     'walker'         => new \jamal13647850\wphelpers\Navigation\MultiColumnDesktopWalker([
 *         'dropdown_columns'       => 3, // 1..6
 *         'enable_icons'           => true,
 *         'dropdown_trigger_class' => 'nav-link dropdown-trigger',
 *         'dropdown_link_class'    => 'dropdown-link',
 *         'dropdown_arrow_class'   => 'dropdown-arrow fas fa-chevron-down',
 *     ]),
 * ]);
 * ```
 *
 * ## Preconditions
 * - WordPress environment is loaded and `Walker_Nav_Menu` is available.
 * - Menu structure contains at most one submenu level for dropdowns.
 *
 * ## Side Effects
 * - Outputs HTML directly via `$output` argument references in walker methods.
 *
 * @since 1.0.0
 * @extends Walker_Nav_Menu
 * @final
 */
final class MultiColumnDesktopWalker extends Walker_Nav_Menu
{
    /**
     * Walker options.
     *
     * @var array{
     *     dropdown_columns:int,
     *     enable_icons:bool,
     *     dropdown_trigger_class:string,
     *     dropdown_link_class:string,
     *     dropdown_arrow_class:string
     * }
     */
    private array $options = [
        'dropdown_columns'       => 2,
        'enable_icons'           => true,
        'dropdown_trigger_class' => 'nav-link dropdown-trigger',
        'dropdown_link_class'    => 'dropdown-link',
        'dropdown_arrow_class'   => 'dropdown-arrow fas fa-chevron-down',
    ];

    /**
     * Buffer of first-level child items to be distributed into columns.
     * Structure: parentId => list<array{
     *   title:string, url:string, target?:string, rel?:string,
     *   attr_title?:string, classes?:array<int,string>
     * }>
     *
     * @var array<int, array<int, array<string, mixed>>>
     */
    private array $mc_buffer = [];

    /**
     * Current parent (root-level) menu item ID whose children are being collected.
     *
     * @var int|null
     */
    private ?int $mc_parent_id = null;

    /**
     * Number of dropdown columns to render (clamped 1..6).
     *
     * @var int
     */
    private int $dropdown_columns = 2;

    /**
     * Constructor.
     *
     * @param array{
     *     dropdown_columns?:int,
     *     enable_icons?:bool,
     *     dropdown_trigger_class?:string,
     *     dropdown_link_class?:string,
     *     dropdown_arrow_class?:string
     * } $options Custom walker options.
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
        $this->dropdown_columns = (int) max(1, min(6, $this->options['dropdown_columns']));
    }

    /**
     * Start rendering a menu item (<li>).
     *
     * Behavior:
     * - Depth 0: render root item and prepare for a potential dropdown.
     * - Depth 1: collect child items for later columnized output.
     * - >1    : ignored (this walker only supports 2 levels).
     *
     * @param string $output HTML output (by reference).
     * @param object $item   Menu item data (WP_Post-like).
     * @param int    $depth  Current depth (0-based).
     * @param array  $args   Rendering args (unused).
     * @param int    $id     Menu item ID (unused).
     * @return void
     */
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0): void
    {
        $item  = (object) $item;
        $title = apply_filters('the_title', $item->title ?? '');
        $url   = isset($item->url) ? esc_url((string) $item->url) : '#';

        // Only depth 0 (root) and depth 1 (direct children) are handled.
        if ($depth === 0) {
            $this->renderRootItem($output, $item, $title, $url);
            return;
        }

        if ($depth === 1) {
            $this->collectChildItem($item, $title, $url);
            return;
        }

        // Deeper levels are intentionally ignored (multi-column supports only 2 levels).
    }

    /**
     * Render a root-level item (<li> with link and optional dropdown trigger).
     *
     * @param string $output HTML output (by reference).
     * @param object $item   Menu item object (expects ->ID, ->classes).
     * @param string $title  Sanitized title (already filtered).
     * @param string $url    Escaped URL or '#'.
     * @return void
     */
    private function renderRootItem(string &$output, object $item, string $title, string $url): void
    {
        $classes_wp   = empty($item->classes) ? [] : (array) $item->classes;
        $has_children = in_array('menu-item-has-children', $classes_wp, true);

        // Compose <li> classes.
        $li_classes   = $classes_wp;
        $li_classes[] = 'menu-item-' . (int) ($item->ID ?? 0);
        $li_classes[] = 'relative';

        if ($has_children) {
            $li_classes[] = 'has-dropdown';
        }

        $output .= "\n<li class=\"" . esc_attr(implode(' ', array_filter($li_classes))) . "\">";

        // Determine link class based on presence of children.
        $link_class = $has_children
            ? esc_attr($this->options['dropdown_trigger_class'])
            : esc_attr($this->options['dropdown_trigger_class'] . ' no-children');

        // Cache current parent ID for collecting first-level children.
        $this->mc_parent_id = $has_children ? (int) ($item->ID ?? 0) : null;

        // Render the root link.
        $output .= '<a href="' . $url . '" class="' . $link_class . '">';
        $output .= '<span>' . esc_html($title) . '</span>';

        // Add dropdown arrow if the item has children.
        if ($has_children) {
            $arrow_class = esc_attr($this->options['dropdown_arrow_class']);
            $output .= '<i class="' . $arrow_class . '" aria-hidden="true"></i>';
        }

        $output .= '</a>';
    }

    /**
     * Collect a first-level child item for later rendering into dropdown columns.
     *
     * @param object $item  Menu child item.
     * @param string $title Child title (sanitized).
     * @param string $url   Escaped URL.
     * @return void
     */
    private function collectChildItem(object $item, string $title, string $url): void
    {
        if ($this->mc_parent_id === null) {
            return;
        }

        $this->mc_buffer[$this->mc_parent_id][] = [
            'title'      => $title,
            'url'        => $url,
            'target'     => $item->target ?? '',
            'rel'        => $item->xfn ?? '',
            'attr_title' => $item->attr_title ?? '',
            'classes'    => $item->classes ?? [],
        ];
    }

    /**
     * Start a submenu container for depth 0 root items.
     *
     * Outputs the wrapper elements that will contain the columnized child links.
     * Deeper levels are ignored to enforce a strict two-level structure.
     *
     * @param string $output HTML output (by reference).
     * @param int    $depth  Current depth.
     * @param array  $args   Rendering args (unused).
     * @return void
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void
    {
        if ($depth !== 0) {
            // Only handle the first submenu level.
            return;
        }

        $indent  = str_repeat("\t", $depth + 1);
        $output .= "\n{$indent}<div class=\"dropdown-menu\">\n";
        $output .= "{$indent}\t<div class=\"dropdown-content\">\n";
        $output .= "{$indent}\t\t<div class=\"dropdown-columns\">\n";
    }

    /**
     * End a submenu: distribute collected children into columns and render them.
     *
     * @param string $output HTML output (by reference).
     * @param int    $depth  Current depth.
     * @param array  $args   Rendering args (unused).
     * @return void
     */
    public function end_lvl(&$output, $depth = 0, $args = []): void
    {
        if ($depth !== 0) {
            return;
        }

        $indent = str_repeat("\t", $depth + 1);

        // Retrieve buffered children for the current parent (if any).
        $children = $this->mc_buffer[$this->mc_parent_id ?? 0] ?? [];
        $count    = count($children);
        $cols     = max(1, $this->dropdown_columns);
        $perCol   = $count > 0 ? (int) ceil($count / $cols) : 0;

        // Render columns and slice children across them.
        for ($col = 0; $col < $cols; $col++) {
            $start = $col * $perCol;
            $slice = ($perCol > 0) ? array_slice($children, $start, $perCol) : [];

            $output .= "{$indent}\t\t\t<div class=\"dropdown-column\">\n";

            foreach ($slice as $link) {
                $this->renderDropdownLink($output, $link, $indent);
            }

            $output .= "{$indent}\t\t\t</div>\n";
        }

        // Close wrappers.
        $output .= "{$indent}\t\t</div>\n"; // .dropdown-columns
        $output .= "{$indent}\t</div>\n";   // .dropdown-content
        $output .= "{$indent}</div>\n";     // .dropdown-menu

        // Clear buffer and reset current parent.
        if ($this->mc_parent_id !== null) {
            unset($this->mc_buffer[$this->mc_parent_id]);
            $this->mc_parent_id = null;
        }
    }

    /**
     * Render an individual link inside the dropdown.
     *
     * @param string               $output HTML output (by reference).
     * @param array<string, mixed> $link   Link payload (title, url, attr_title, classes, ...).
     * @param string               $indent Indentation prefix for pretty output.
     * @return void
     */
    private function renderDropdownLink(string &$output, array $link, string $indent): void
    {
        $iconClass = null;

        // Resolve optional FontAwesome icon if enabled.
        if (!empty($this->options['enable_icons'])) {
            $iconClass = $this->resolveIconClass(
                $link['attr_title'] ?? null,
                $link['classes'] ?? []
            );
        }

        $linkClass = esc_attr($this->options['dropdown_link_class']);
        $url       = esc_url($link['url']);
        $title     = esc_html($link['title']);

        $output .= "{$indent}\t\t\t\t<a href=\"{$url}\" class=\"{$linkClass}\">";

        // Render icon (if found).
        if ($iconClass) {
            $output .= '<i class="' . esc_attr($iconClass) . '" aria-hidden="true"></i>';
        }

        $output .= "<span>{$title}</span></a>\n";
    }

    /**
     * End a menu item.
     *
     * Only closes the <li> for root-level items; deeper levels are not rendered
     * as <li> by this walker.
     *
     * @param string $output HTML output (by reference).
     * @param object $item   Menu item object.
     * @param int    $depth  Current depth.
     * @param array  $args   Rendering args (unused).
     * @return void
     */
    public function end_el(&$output, $item, $depth = 0, $args = []): void
    {
        if ($depth === 0) {
            $output .= "</li>\n";
        }
        // Other depths are intentionally not closed here.
    }

    /**
     * Resolve a FontAwesome icon class from menu item attributes.
     *
     * Resolution rules:
     * - Prefer `attr_title` if it contains "fa-" token(s).
     * - Otherwise scan `classes` for a token starting with "fa-".
     * - Ensure a base "fa" prefix is present; normalize to "fa fa-xxx".
     * - Validate that only [a-zA-Z0-9- ] characters exist; otherwise return null.
     *
     * @param string|null $attrTitle Menu item's `attr_title`.
     * @param array<int, string>|null $classes Menu item classes.
     * @return string|null Normalized FA class string or null if none/invalid.
     */
    private function resolveIconClass(?string $attrTitle, ?array $classes): ?string
    {
        $candidate = null;

        // Check attr_title for an FA token.
        if (is_string($attrTitle) && strpos($attrTitle, 'fa-') !== false) {
            $candidate = trim($attrTitle);
        } else {
            // Scan class list for an FA token.
            if (is_array($classes)) {
                foreach ($classes as $c) {
                    if (is_string($c) && strpos($c, 'fa-') === 0) {
                        $candidate = trim($c);
                        break;
                    }
                }
            }
        }

        if (!$candidate) {
            return null;
        }

        // Ensure base "fa" is present if token starts with "fa-".
        $hasFaBase = (strpos($candidate, 'fa ') !== false)
            || (strpos($candidate, 'fa-') === 0 && strpos($candidate, ' ') !== false);

        if (!$hasFaBase && strpos($candidate, 'fa-') === 0) {
            $candidate = 'fa ' . $candidate;
        }

        // Validate class characters (letters, digits, hyphen, space only).
        if (!preg_match('/^[a-z0-9\-\s]+$/i', $candidate)) {
            return null;
        }

        return $candidate;
    }
}
