<?php
/**
 * Plugin Name: Database Tradisi Keagamaan
 * Description: Custom Post Type & Taxonomy untuk database tradisi keagamaan
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

// 1. Register Custom Post Type
function tk_register_post_type() {
    register_post_type('tradisi', [
        'labels' => [
            'name' => 'Tradisi',
            'singular_name' => 'Tradisi',
            'add_new_item' => 'Tambah Tradisi Baru',
            'edit_item' => 'Edit Tradisi',
            'all_items' => 'Semua Tradisi',
        ],
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-book-alt',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'tradisi'],
    ]);
}
add_action('init', 'tk_register_post_type');

// 2. Register Taxonomy: Agama
function tk_register_taxonomy_agama() {
    register_taxonomy('agama', 'tradisi', [
        'labels' => [
            'name' => 'Agama',
            'singular_name' => 'Agama',
        ],
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'agama'],
    ]);
}
add_action('init', 'tk_register_taxonomy_agama');

// 3. Register Taxonomy: Wilayah
function tk_register_taxonomy_wilayah() {
    register_taxonomy('wilayah', 'tradisi', [
        'labels' => [
            'name' => 'Wilayah',
            'singular_name' => 'Wilayah',
        ],
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'wilayah'],
    ]);
}
add_action('init', 'tk_register_taxonomy_wilayah');

// 4. Register Taxonomy: Kategori Tradisi
function tk_register_taxonomy_kategori() {
    register_taxonomy('kategori-tradisi', 'tradisi', [
        'labels' => [
            'name' => 'Kategori Tradisi',
            'singular_name' => 'Kategori',
        ],
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'kategori-tradisi'],
    ]);
}
add_action('init', 'tk_register_taxonomy_kategori');

// ============================================================
// 5. Tags Support + Template Loader untuk Single Tradisi
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

// ============================================================
// 6. ACF Field Group — TEMPEL HASIL EXPORT DI BAWAH INI
// ============================================================

add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
	'key' => 'group_6aa0f20d00bc9',
	'title' => 'Detail Tradisi',
	'fields' => array(
		array(
			'key' => 'field_6aa0f20e51a52',
			'label' => 'Asal Daerah',
			'name' => 'asal_daerah',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'maxlength' => '',
			'allow_in_bindings' => 0,
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
		),
		array(
			'key' => 'field_6aa0f24e51a54',
			'label' => 'Deskripsi Singkat',
			'name' => 'deskripsi_singkat',
			'aria-label' => '',
			'type' => 'textarea',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'maxlength' => '',
			'allow_in_bindings' => 0,
			'rows' => '',
			'placeholder' => '',
			'new_lines' => '',
		),
		array(
			'key' => 'field_6aa0f26151a55',
			'label' => 'Tanggal Perayaan',
			'name' => 'tanggal_perayaan',
			'aria-label' => '',
			'type' => 'date_picker',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'display_format' => 'F j, Y',
			'return_format' => 'd/m/Y',
			'first_day' => 1,
			'default_to_current_date' => 0,
			'allow_in_bindings' => 0,
		),
		array(
			'key' => 'field_6aa0f2c151a57',
			'label' => 'Sumber Referensi',
			'name' => 'sumber_referensi',
			'aria-label' => '',
			'type' => 'url',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'allow_in_bindings' => 0,
			'placeholder' => '',
		),
		array(
			'key' => 'field_6aa0f2fa51a58',
			'label' => 'Galeri Foto',
			'name' => 'galeri_foto',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
			'allow_in_bindings' => 0,
			'preview_size' => 'medium',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'tradisi',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => 'Berisi atribut terkait tradisi',
	'show_in_rest' => 0,
	'display_title' => '',
	'allow_ai_access' => false,
	'ai_description' => '',
) );
} );


// ============================================================
// 7. View Counter untuk Tradisi
// ============================================================

/**
 * Tambah 1 hitungan setiap kali halaman Single Tradisi dibuka.
 * Disimpan sebagai post meta 'tk_view_count'.
 */
function tk_track_view_count() {
    if (is_singular('tradisi') && !is_admin()) {
        $post_id = get_the_ID();
        $views = (int) get_post_meta($post_id, 'tk_view_count', true);
        update_post_meta($post_id, 'tk_view_count', $views + 1);
    }
}
add_action('wp_head', 'tk_track_view_count');

/**
 * Helper function untuk ambil jumlah views — dipakai di template.
 */
function tk_get_view_count($post_id) {
    $views = (int) get_post_meta($post_id, 'tk_view_count', true);
    return $views;
}

// ===== 8. Stats counter: [tk_stats] =====
add_shortcode( 'tk_stats', 'tk_stats_shortcode' );
function tk_stats_shortcode() {
    global $wpdb;

    // Jumlah tradisi yang sudah terbit
    $jml_tradisi = (int) wp_count_posts( 'tradisi' )->publish;

    // Jumlah provinsi = term 'wilayah' level teratas yang dipakai
    $jml_provinsi = wp_count_terms( array(
        'taxonomy'   => 'wilayah',
        'hide_empty' => true,
        'parent'     => 0,
    ) );
    $jml_provinsi = is_wp_error( $jml_provinsi ) ? 0 : (int) $jml_provinsi;

    // Jumlah kabupaten/kota = nilai unik field asal_daerah
    $jml_kab = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT TRIM(pm.meta_value))
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = %s
           AND pm.meta_value <> ''
           AND p.post_type = %s
           AND p.post_status = 'publish'",
        'asal_daerah',
        'tradisi'
    ) );

    $items = array(
        array( $jml_tradisi,  'Tradisi' ),
        array( $jml_provinsi, 'Provinsi' ),
        array( $jml_kab,      'Kabupaten/Kota' ),
    );

    $html = '<div class="tk-stats">';
    foreach ( $items as $item ) {
        $html .= sprintf(
            '<div class="tk-stat"><span class="tk-stat-angka">%s</span><span class="tk-stat-label">%s</span></div>',
            esc_html( number_format_i18n( $item[0] ) ),
            esc_html( $item[1] )
        );
    }
    $html .= '</div>';

    return $html;
}

// ===== Font Google: DM Serif Display (judul) + DM Sans (teks) =====
add_action( 'wp_enqueue_scripts', 'tk_enqueue_fonts' );
function tk_enqueue_fonts() {
    wp_enqueue_style(
        'tk-fonts',
        'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=DM+Serif+Display&display=swap',
        array(),
        null
    );
}