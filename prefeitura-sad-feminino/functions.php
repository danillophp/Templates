<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/cpt-register.php';
require_once get_template_directory() . '/inc/acf-prefeita.php';
require_once get_template_directory() . '/inc/acf-programas-sociais.php';
require_once get_template_directory() . '/inc/acf-secretarias.php';
require_once get_template_directory() . '/inc/acf-noticias.php';
require_once get_template_directory() . '/inc/acf-contato-cidadao.php';

function prefeitura_sad_feminino_setup() {
    load_theme_textdomain('prefeitura-sad-feminino', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);

    register_nav_menus([
        'menu_principal' => __('Menu Principal', 'prefeitura-sad-feminino'),
        'menu_rodape' => __('Menu Rodapé', 'prefeitura-sad-feminino'),
    ]);
}
add_action('after_setup_theme', 'prefeitura_sad_feminino_setup');

function prefeitura_sad_feminino_assets() {
    wp_enqueue_style('prefeitura-sad-feminino-style', get_stylesheet_uri(), [], '1.0.0');
    wp_enqueue_style('prefeitura-sad-feminino-main', get_template_directory_uri() . '/assets/css/main.css', [], '1.0.0');
    wp_enqueue_script('prefeitura-sad-feminino-main', get_template_directory_uri() . '/assets/js/main.js', [], '1.0.0', true);
    wp_enqueue_style('prefeitura-sad-feminino-google-fonts', 'https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&family=Playfair+Display:wght@500;600;700&display=swap', [], null);
}
add_action('wp_enqueue_scripts', 'prefeitura_sad_feminino_assets');

function prefeitura_sad_feminino_register_sidebars() {
    register_sidebar([
        'name' => __('Rodapé Coluna 1', 'prefeitura-sad-feminino'),
        'id' => 'rodape-coluna-1',
        'description' => __('Widgets para o rodapé.', 'prefeitura-sad-feminino'),
        'before_widget' => '<div class="widget">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget__title">',
        'after_title' => '</h3>',
    ]);

    register_sidebar([
        'name' => __('Rodapé Coluna 2', 'prefeitura-sad-feminino'),
        'id' => 'rodape-coluna-2',
        'description' => __('Widgets para o rodapé.', 'prefeitura-sad-feminino'),
        'before_widget' => '<div class="widget">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget__title">',
        'after_title' => '</h3>',
    ]);
}
add_action('widgets_init', 'prefeitura_sad_feminino_register_sidebars');
