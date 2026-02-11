<?php

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key' => 'group_programas_sociais',
        'title' => __('Programas Sociais', 'prefeitura-sad-feminino'),
        'fields' => [
            [
                'key' => 'field_programa_publico_alvo',
                'label' => __('Público Alvo', 'prefeitura-sad-feminino'),
                'name' => 'publico_alvo',
                'type' => 'text',
            ],
            [
                'key' => 'field_programa_descricao',
                'label' => __('Descrição do Programa', 'prefeitura-sad-feminino'),
                'name' => 'descricao_programa',
                'type' => 'wysiwyg',
            ],
            [
                'key' => 'field_programa_galeria',
                'label' => __('Galeria do Programa', 'prefeitura-sad-feminino'),
                'name' => 'galeria_programa',
                'type' => 'gallery',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
            [
                'key' => 'field_programa_secretaria',
                'label' => __('Secretaria Responsável', 'prefeitura-sad-feminino'),
                'name' => 'secretaria_responsavel',
                'type' => 'text',
            ],
            [
                'key' => 'field_programa_contato',
                'label' => __('Contato do Programa', 'prefeitura-sad-feminino'),
                'name' => 'contato_programa',
                'type' => 'text',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'programas_sociais',
                ],
            ],
        ],
    ]);
}
