<?php
/**
 * Plugin Name: Benjamin Marc SEO Checker
 * Description: A lightweight on-page SEO checklist for published posts and pages, including titles, meta descriptions, H1 headings, and image alt text.
 * Version: 1.0.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Benjamin Marc
 * Author URI: https://www.benjaminmarc.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: benjamin-marc-seo-checker
 */

if (!defined('ABSPATH')) { exit; }

add_action('admin_menu', function () {
    add_management_page(
        __('Benjamin Marc SEO Checker', 'benjamin-marc-seo-checker'),
        __('BM SEO Checker', 'benjamin-marc-seo-checker'),
        'edit_others_posts',
        'benjamin-marc-seo-checker',
        'bmseo_render_page'
    );
});

function bmseo_meta_description($post_id) {
    // Read-only compatibility with popular SEO plugins. No SEO metadata is changed.
    $keys = array('_yoast_wpseo_metadesc', 'rank_math_description', '_aioseo_description');
    foreach ($keys as $key) {
        $value = get_post_meta($post_id, $key, true);
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }
    return '';
}

function bmseo_analyze_post($post) {
    $content = (string) $post->post_content;
    $title = trim(wp_strip_all_tags(get_the_title($post)));
    $meta = bmseo_meta_description($post->ID);
    $h1_count = 0;
    $images = 0;
    $missing_alt = 0;

    // This inspects saved post content, not the final rendered front end.
    if (class_exists('DOMDocument')) {
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $content . '</div>');
        if ($loaded) {
            $h1_count = $dom->getElementsByTagName('h1')->length;
            foreach ($dom->getElementsByTagName('img') as $img) {
                $images++;
                if (trim($img->getAttribute('alt')) === '') { $missing_alt++; }
            }
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    } else {
        $h1_count = preg_match_all('/<h1\b[^>]*>/i', $content);
        if (preg_match_all('/<img\b[^>]*>/i', $content, $matches)) {
            foreach ($matches[0] as $tag) {
                $images++;
                if (!preg_match('/\balt\s*=\s*(["\'])(.*?)\1/is', $tag, $alt) || trim($alt[2]) === '') {
                    $missing_alt++;
                }
            }
        }
    }
    return array(
        'title' => $title !== '' ? 'pass' : 'warn',
        'meta' => $meta !== '' ? 'pass' : 'warn',
        'h1' => $h1_count === 1 ? 'pass' : 'warn',
        'alt' => $missing_alt === 0 ? 'pass' : 'warn',
        'h1_count' => $h1_count,
        'images' => $images,
        'missing_alt' => $missing_alt,
    );
}

function bmseo_render_page() {
    if (!current_user_can('edit_others_posts')) { wp_die(esc_html__('Insufficient permissions.', 'benjamin-marc-seo-checker')); }
    // Pagination is read-only, but validate the request nonce before reading its value.
    $page = 1;
    if (isset($_GET['paged'], $_GET['_wpnonce'])) {
        $pagination_nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
        if (wp_verify_nonce($pagination_nonce, 'bmseo_pagination')) {
            $page = max(1, absint(wp_unslash($_GET['paged'])));
        }
    }
    $query = new WP_Query(array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'posts_per_page' => 20,
        'paged' => $page,
        'orderby' => 'modified',
        'order' => 'DESC',
        'no_found_rows' => false,
    ));
    echo '<div class="wrap"><h1>' . esc_html__('Benjamin Marc SEO Checker', 'benjamin-marc-seo-checker') . '</h1>';
    echo '<p>' . esc_html__('Read-only checks of saved content for published posts and pages. Theme-rendered headings, dynamically generated images, and frontend SEO output may differ. Meta descriptions are detected from Yoast SEO, Rank Math, and legacy All in One SEO post metadata only.', 'benjamin-marc-seo-checker') . '</p>';
    echo '<table class="widefat striped"><thead><tr>';
    foreach (array('Page / Post', 'Title', 'Meta description', 'H1 in content', 'Image ALT in content') as $heading) {
        echo '<th scope="col">' . esc_html($heading) . '</th>';
    }
    echo '</tr></thead><tbody>';
    if (!$query->have_posts()) {
        echo '<tr><td colspan="5">' . esc_html__('No published posts or pages found.', 'benjamin-marc-seo-checker') . '</td></tr>';
    }
    foreach ($query->posts as $post) {
        $result = bmseo_analyze_post($post);
        $edit_url = get_edit_post_link($post->ID);
        echo '<tr><td><strong>';
        if ($edit_url) { echo '<a href="' . esc_url($edit_url) . '">' . esc_html(get_the_title($post)) . '</a>'; }
        else { echo esc_html(get_the_title($post)); }
        echo '</strong></td>';
        foreach (array('title', 'meta', 'h1', 'alt') as $check) {
            $ok = $result[$check] === 'pass';
            $message = $ok ? __('Pass', 'benjamin-marc-seo-checker') : __('Review', 'benjamin-marc-seo-checker');
            if ($check === 'h1') { $message .= ' (' . $result['h1_count'] . ')'; }
            if ($check === 'alt') { $message .= ' (' . $result['missing_alt'] . '/' . $result['images'] . ' missing)'; }
            echo '<td><span style="font-weight:600;color:' . ($ok ? '#176b35' : '#9a4d00') . '">' . esc_html($message) . '</span></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
    $total_pages = (int) $query->max_num_pages;
    if ($total_pages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';
        echo wp_kses_post(paginate_links(array(
            'base' => add_query_arg(array('paged' => '%#%', '_wpnonce' => wp_create_nonce('bmseo_pagination')), admin_url('tools.php?page=benjamin-marc-seo-checker')),
            'format' => '',
            'current' => $page,
            'total' => $total_pages,
        )));
        echo '</div></div>';
    }
    wp_reset_postdata();
    echo '<p>' . esc_html__('Tip: A missing meta description in this report may mean your SEO plugin stores it in a different format. This checker does not change your website.', 'benjamin-marc-seo-checker') . '</p>';
    echo '</div>';
}
