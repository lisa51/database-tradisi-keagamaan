// ============================================================
// TAMBAHAN: Template Loader untuk Single Tradisi + Tags Support
// ============================================================

function tk_add_tags_to_tradisi() {
    register_taxonomy_for_object_type('post_tag', 'tradisi');
}
add_action('init', 'tk_add_tags_to_tradisi');

function tk_single_tradisi_template($template) {
    if (is_singular('tradisi')) {
        $custom_template = plugin_dir_path(__FILE__) . 'templates/single-tradisi.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
}
add_filter('single_template', 'tk_single_tradisi_template');