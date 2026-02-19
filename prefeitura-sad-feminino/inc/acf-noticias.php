<?php

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key' => 'group_noticias',
        'title' => __('Notícias Institucionais', 'prefeitura-sad-feminino'),
        'fields' => [
            [
                'key' => 'field_noticia_destaque',
                'label' => __('Destaque', 'prefeitura-sad-feminino'),
                'name' => 'destaque',
                'type' => 'true_false',
                'ui' => 1,
                'message' => __('Marcar como notícia destaque', 'prefeitura-sad-feminino'),
            ],
            [
                'key' => 'field_noticia_galeria',
                'label' => __('Galeria da Notícia', 'prefeitura-sad-feminino'),
                'name' => 'galeria_noticia',
                'type' => 'gallery',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
            [
                'key' => 'field_noticia_fonte',
                'label' => __('Fonte Oficial', 'prefeitura-sad-feminino'),
                'name' => 'fonte_oficial',
                'type' => 'text',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
        ],
    ]);
}
