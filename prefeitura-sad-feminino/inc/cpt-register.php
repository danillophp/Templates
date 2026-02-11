<?php

if (!defined('ABSPATH')) {
    exit;
}

function prefeitura_sad_feminino_register_cpts() {
    register_post_type('programas_sociais', [
        'labels' => [
            'name' => __('Programas Sociais', 'prefeitura-sad-feminino'),
            'singular_name' => __('Programa Social', 'prefeitura-sad-feminino'),
            'add_new_item' => __('Adicionar novo programa social', 'prefeitura-sad-feminino'),
            'edit_item' => __('Editar programa social', 'prefeitura-sad-feminino'),
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-heart',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'programas_sociais'],
    ]);

    register_post_type('secretarias', [
        'labels' => [
            'name' => __('Secretarias Municipais', 'prefeitura-sad-feminino'),
            'singular_name' => __('Secretaria Municipal', 'prefeitura-sad-feminino'),
            'add_new_item' => __('Adicionar nova secretaria', 'prefeitura-sad-feminino'),
            'edit_item' => __('Editar secretaria', 'prefeitura-sad-feminino'),
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-building',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'secretarias'],
    ]);
}
add_action('init', 'prefeitura_sad_feminino_register_cpts');
