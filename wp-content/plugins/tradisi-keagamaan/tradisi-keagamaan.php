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