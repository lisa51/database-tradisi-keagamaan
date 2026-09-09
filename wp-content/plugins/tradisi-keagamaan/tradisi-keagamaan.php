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
        'show_in_rest' => true, // penting untuk Gutenberg & API
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
        'hierarchical' => true, // seperti kategori
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

// 4. Register Taxonomy: Kategori Tradisi (misal: ritual, perayaan, upacara adat)
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

