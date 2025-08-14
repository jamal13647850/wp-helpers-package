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
 * DesktopMegaMenuWalker
 *
 * A specialized WordPress walker for desktop mega menus.
 * Separated from AlpineNavWalker to improve performance and readability.
 *
 * ## Features
 * - Renders a root-level item with hover-driven mega menu (Alpine.js).
 * - Buffers level-1 (section headers) and level-2 (children) items and renders them
 *   inside a responsive, multi-column mega menu panel.
 * - Adds SVG dropdown indicator on root items that have children.
 *
 * ## Usage
 * ```php
 * wp_nav_menu([
 *     'theme_location' => 'primary',
 *     'walker'         => new \jamal13647850\wphelpers\Navigation\DesktopMegaMenuWalker([
 *         // Optional overrides:
 *         'desktop_link_class'           => '...',
 *         'desktop_link_hover_color'     => '#F25A04',
 *         'desktop_svg_default_fill'     => '#79528A',
 *         'desktop_svg_hover_fill'       => '#F25A04',
 *         'mega_menu_parent_title_class' => '...',
 *         'mega_menu_child_link_class'   => '...',
 *     ]),
 * ]);
 * ```
 *
 * ## Preconditions
 * - WordPress environment loaded; `Walker_Nav_Menu` available.
 * - Menu structure: depth 0 (root), depth 1 (parent sections), depth 2 (children).
 *
 * ## Side Effects
 * - Emits HTML by appending to `$output` (by reference).
 *
 * @since 1.0.0
 * @final
 */
final class DesktopMegaMenuWalker extends Walker_Nav_Menu
{
    /**
     * Walker options.
     *
     * @var array{
     *   desktop_link_class:string,
     *   desktop_link_hover_color:string,
     *   desktop_svg_default_fill:string,
     *   desktop_svg_hover_fill:string,
     *   mega_menu_parent_title_class:string,
     *   mega_menu_child_link_class:string
     * }
     */
    private array $options = [
        'desktop_link_class'           => 'flex items-center gap-1 py-4 px-1 text-gray-800 hover:text-primary transition-colors text-nowrap text-sm lg:text-xs xl:text-sm',
        'desktop_link_hover_color'     => '#F25A04',
        'desktop_svg_default_fill'     => '#79528A',
        'desktop_svg_hover_fill'       => '#F25A04',
        'mega_menu_parent_title_class' => 'hover:text-primary transition-colors duration-300',
        'mega_menu_child_link_class'   => 'text-gray-700 hover:text-primary transition-colors duration-300 block py-1 text-sm hover:bg-gray-50 px-2 rounded',
    ];

    /**
     * Collected mega menu items.
     * Each entry:
     * - title, url, ID, target, attr_title, xfn
     * - children: list of { title, url, ID, target, attr_title, xfn }
     *
     * @var array<int, array<string, mixed>>
     */
    private array $mega_menu_items = [];

    /**
     * Reference to the current level-1 parent being collected.
     *
     * @var array<string, mixed>|null
     */
    private ?array $current_parent = null;

    /**
     * Images to render in the mega menu (if provided upstream).
     * Structure is assumed as a list of arrays with keys: image (url), alt (optional).
     *
     * @var array<int, array<string, string>>
     */
    private array $current_menu_images = [];

    /**
     * Constructor.
     *
     * @param array<string, mixed> $options Custom walker options.
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Start rendering a menu item (<li>).
     *
     * Behavior:
     * - Depth 0: render the root item and initialize Alpine.js hover state.
     * - Depth >=1: collect items for the mega menu panel (buffered for end of level).
     *
     * @param string       $output HTML output (by reference).
     * @param object       $item   Menu item object (WP_Post-like).
     * @param int          $depth  Current depth (0=root).
     * @param array|object $args   Menu args (from `wp_nav_menu`).
     * @param int          $id     Item ID (unused; WP supplies via $item->ID).
     * @return void
     */
    public function start_el(&$output, $item, $depth = 0, $args = [], $id = 0): void
    {
        if ($depth === 0) {
            $this->renderRootItem($output, $item, $depth, (array) $args, $id);
            return;
        }

        if ($depth >= 1) {
            $this->collectMegaMenuItem($item, $depth);
            return;
        }
    }

    /**
     * Render a root-level item (<li> with link and optional dropdown indicator).
     *
     * @param string $output HTML output (by reference).
     * @param object $item   Menu item.
     * @param int    $depth  Menu depth (0).
     * @param array  $args   Menu args.
     * @param int    $id     Item ID.
     * @return void
     */
    private function renderRootItem(string &$output, object $item, int $depth, array $args, int $id): void
    {
        $indent       = ($depth) ? str_repeat("\t", $depth) : '';
        $classes      = empty($item->classes) ? [] : (array) $item->classes;
        $classes[]    = 'menu-item-' . $item->ID;
        $has_children = in_array('menu-item-has-children', $classes);

        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        $id_attr     = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr     = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        // Alpine.js state (hover-driven mega menu visibility).
        $alpine_attrs = ' x-data="{ open0: false, isHovered: false }" x-on:mouseenter="open0 = true; isHovered = true" x-on:mouseleave="open0 = false; isHovered = false" class="menu-item-flex"';

        $output .= $indent . '<li' . $id_attr . $class_names . $alpine_attrs . '>';

        // Link attributes.
        $atts = [
            'title'  => !empty($item->attr_title) ? $item->attr_title : '',
            'target' => !empty($item->target) ? $item->target : '',
            'rel'    => !empty($item->xfn) ? $item->xfn : '',
            'href'   => !empty($item->url) ? $item->url : '',
        ];

        if ($item->current || $item->current_item_ancestor || $item->current_item_parent) {
            $atts['aria-current'] = 'page';
        }

        /** @var array<string, string> $atts */
        $atts       = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);
        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value       = ($attr === 'href') ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        // Render anchor with hover color toggle (Alpine.js).
        $item_output = $args['before'] ?? '';
        $svg_icon    = $has_children ? $this->generateDropdownIcon() : '';

        $item_output .= '<a x-bind:style="{ color: isHovered ? \'' . esc_attr($this->options['desktop_link_hover_color']) . '\' : \'\' }" ' . $attributes . ' class="' . esc_attr($this->options['desktop_link_class']) . '">';
        $item_output .= ($args['link_before'] ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args['link_after'] ?? '');

        if ($svg_icon) {
            $item_output .= $svg_icon;
        }

        $item_output .= '</a>';
        $item_output .= $args['after'] ?? '';

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, (object) $args);
    }

    /**
     * Collect mega menu items for rendering in end of the first submenu level.
     *
     * Depth 1 items become section headers; depth 2 items are their children.
     *
     * @param object $item  Menu item object.
     * @param int    $depth Item depth (1 or 2).
     * @return void
     */
    private function collectMegaMenuItem(object $item, int $depth): void
    {
        if ($depth === 1) {
            // Level-1 parent section.
            $this->mega_menu_items[] = [
                'title'      => apply_filters('the_title', $item->title, $item->ID),
                'url'        => $item->url,
                'ID'         => $item->ID,
                'target'     => $item->target,
                'attr_title' => $item->attr_title,
                'xfn'        => $item->xfn,
                'children'   => [],
            ];
            $this->current_parent = &$this->mega_menu_items[count($this->mega_menu_items) - 1];
        } elseif ($depth === 2 && $this->current_parent !== null) {
            // Level-2 child under current section.
            $this->current_parent['children'][] = [
                'title'      => apply_filters('the_title', $item->title, $item->ID),
                'url'        => $item->url,
                'ID'         => $item->ID,
                'target'     => $item->target,
                'attr_title' => $item->attr_title,
                'xfn'        => $item->xfn,
            ];
        }
    }

    /**
     * Start a submenu level.
     *
     * Only acts when `$depth === 0` (i.e., starting the mega menu container beneath
     * a root item). The actual section/child links are rendered in {@see end_lvl()}.
     *
     * @param string       $output HTML output (by reference).
     * @param int          $depth  Current depth.
     * @param array|object $args   Menu args (unused here).
     * @return void
     */
    public function start_lvl(&$output, $depth = 0, $args = []): void
    {
        if ($depth === 0) {
            $indent = str_repeat("\t", $depth + 1);

            // Mega menu container with Alpine.js transitions.
            $output .= "\n{$indent}<div class=\"mega-menu absolute min-w-[60vw] bg-white shadow-xl border-t border-gray-200 z-[112]\" style=\"margin-left: calc(-50vw + 50%);\" x-show=\"open0\" x-cloak ";
            $output .= 'x-transition:enter="transition ease-out duration-300" ';
            $output .= 'x-transition:enter-start="opacity-0 transform translate-y-[-10px]" ';
            $output .= 'x-transition:enter-end="opacity-100 transform translate-y-0" ';
            $output .= 'x-transition:leave="transition ease-in duration-200" ';
            $output .= 'x-transition:leave-start="opacity-100 transform translate-y-0" ';
            $output .= 'x-transition:leave-end="opacity-0 transform translate-y-[-10px]" ';
            $output .= '@click.outside="open0 = false">' . "\n";

            $output .= "{$indent}\t<div class=\"mega-menu-container max-w-7xl mx-auto px-6 py-8\">\n";
            $output .= "{$indent}\t\t<div class=\"flex flex-row-reverse gap-8\">\n";

            // Optional images block (if present).
            if (!empty($this->current_menu_images)) {
                $output .= "{$indent}\t\t\t<div class=\"mega-menu-images flex flex-col gap-4 w-1/3\">\n";
                foreach ($this->current_menu_images as $image_data) {
                    if (!empty($image_data['image'])) {
                        $image_url = esc_url($image_data['image']);
                        $image_alt = !empty($image_data['alt']) ? esc_attr($image_data['alt']) : esc_attr(basename($image_url));
                        $output   .= "{$indent}\t\t\t\t<div class=\"image-container flex-1\">\n";
                        $output   .= "{$indent}\t\t\t\t\t<img src=\"{$image_url}\" alt=\"{$image_alt}\" class=\"w-full h-[300px] rounded-lg shadow-md object-cover hover:shadow-lg transition-shadow duration-300\" loading=\"lazy\" />\n";
                        $output   .= "{$indent}\t\t\t\t</div>\n";
                    }
                }
                $output .= "{$indent}\t\t\t</div>\n";
            }

            $output .= "{$indent}\t\t\t<div class=\"mega-menu-content flex-1\">\n";
            $output .= "{$indent}\t\t\t\t<div class=\"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8\">\n";
        }
    }

    /**
     * End a submenu level and render the entire mega menu panel.
     *
     * Iterates buffered `$mega_menu_items` (level-1 parents and their children),
     * emits titles and lists, then closes all wrappers. Resets internal buffers.
     *
     * @param string       $output HTML output (by reference).
     * @param int          $depth  Current depth.
     * @param array|object $args   Menu args (unused).
     * @return void
     */
    public function end_lvl(&$output, $depth = 0, $args = []): void
    {
        if ($depth === 0) {
            $indent = str_repeat("\t", $depth + 1);

            // Render collected mega menu sections.
            foreach ($this->mega_menu_items as $mega_item) {
                $output .= "{$indent}\t\t\t\t\t<div class=\"mega-menu-section\">\n";

                // Parent (section) title/link.
                $parent_atts = [
                    'href' => !empty($mega_item['url']) ? esc_url($mega_item['url']) : '#',
                ];
                if (!empty($mega_item['target'])) {
                    $parent_atts['target'] = esc_attr($mega_item['target']);
                }
                if (!empty($mega_item['attr_title'])) {
                    $parent_atts['title'] = esc_attr($mega_item['attr_title']);
                }
                if (!empty($mega_item['xfn'])) {
                    $parent_atts['rel'] = esc_attr($mega_item['xfn']);
                }

                $parent_attributes_str = '';
                foreach ($parent_atts as $attr => $val) {
                    $parent_attributes_str .= " {$attr}=\"{$val}\"";
                }

                $output .= "{$indent}\t\t\t\t\t\t<h3 class=\"mega-menu-title text-xl font-bold text-secondary mb-4 pb-2 border-b border-gray-200\">\n";
                $output .= "{$indent}\t\t\t\t\t\t\t<a{$parent_attributes_str} class=\"" . esc_attr($this->options['mega_menu_parent_title_class']) . "\">" . esc_html($mega_item['title']) . "</a>\n";
                $output .= "{$indent}\t\t\t\t\t\t</h3>\n";

                // Children under the section.
                if (!empty($mega_item['children'])) {
                    $output .= "{$indent}\t\t\t\t\t\t<ul class=\"mega-menu-items list-none p-0 m-0 space-y-2\">\n";
                    foreach ($mega_item['children'] as $child) {
                        $child_atts = [
                            'href' => !empty($child['url']) ? esc_url($child['url']) : '#',
                        ];
                        if (!empty($child['target'])) {
                            $child_atts['target'] = esc_attr($child['target']);
                        }
                        if (!empty($child['attr_title'])) {
                            $child_atts['title'] = esc_attr($child['attr_title']);
                        }
                        if (!empty($child['xfn'])) {
                            $child_atts['rel'] = esc_attr($child['xfn']);
                        }

                        $child_attributes_str = '';
                        foreach ($child_atts as $attr => $val) {
                            $child_attributes_str .= " {$attr}=\"{$val}\"";
                        }

                        $output .= "{$indent}\t\t\t\t\t\t\t<li>\n";
                        $output .= "{$indent}\t\t\t\t\t\t\t\t<a{$child_attributes_str} class=\"" . esc_attr($this->options['mega_menu_child_link_class']) . "\">" . esc_html($child['title']) . "</a>\n";
                        $output .= "{$indent}\t\t\t\t\t\t\t</li>\n";
                    }
                    $output .= "{$indent}\t\t\t\t\t\t</ul>\n";
                }

                $output .= "{$indent}\t\t\t\t\t</div>\n";
            }

            // Close wrappers.
            $output .= "{$indent}\t\t\t\t</div>\n";
            $output .= "{$indent}\t\t\t</div>\n";
            $output .= "{$indent}\t\t</div>\n";
            $output .= "{$indent}\t</div>\n";
            $output .= "{$indent}</div>\n";

            // Reset internal state.
            $this->mega_menu_items    = [];
            $this->current_parent     = null;
            $this->current_menu_images = [];
        }
    }

    /**
     * End a single menu item.
     *
     * Only closes the <li> at root level (depth 0).
     *
     * @param string       $output HTML output (by reference).
     * @param object       $item   Menu item.
     * @param int          $depth  Current depth.
     * @param array|object $args   Menu args (unused).
     * @return void
     */
    public function end_el(&$output, $item, $depth = 0, $args = []): void
    {
        if ($depth === 0) {
            $output .= "</li>\n";
        }
    }

    /**
     * Generate the dropdown SVG icon for root items.
     *
     * The icon rotates on hover (via Alpine.js) and adapts its fill color.
     *
     * @return string SVG markup for the dropdown indicator.
     */
    private function generateDropdownIcon(): string
    {
        $transform_attr = "x-bind:style=\"{ transform: isHovered ? 'rotate(180deg)' : 'rotate(0deg)' }\"";
        $fill_attr      = "x-bind:fill=\"isHovered ? '" . esc_attr($this->options['desktop_svg_hover_fill']) . "' : '" . esc_attr($this->options['desktop_svg_default_fill']) . "'\"";

        return '<svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="h-6 transition-transform duration-300 ease-in-out" ' . $transform_attr . ' style="margin-top: 0 !important; margin-bottom: 0 !important;" aria-hidden="true">
        <g data-name="24x24/On Light/Arrow-Bottom">
        <path fill="none" d="M0 24V0h24v24z"/>
        <path id="svgPath" d="M7.53 9.47a.75.75 0 0 0-1.06 1.06l5 5a.75.75 0 0 0 1.061 0l5-5a.75.75 0 0 0-1.061-1.06L12 13.94Z" ' . $fill_attr . ' />
        </g></svg>';
    }
}
