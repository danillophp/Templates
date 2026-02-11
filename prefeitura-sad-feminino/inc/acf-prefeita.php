<?php

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key' => 'group_prefeita_perfil',
        'title' => __('Perfil Institucional – Prefeita', 'prefeitura-sad-feminino'),
        'fields' => [
            [
                'key' => 'field_prefeita_foto_oficial',
                'label' => __('Foto Oficial', 'prefeitura-sad-feminino'),
                'name' => 'foto_oficial',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
            [
                'key' => 'field_prefeita_nome_completo',
                'label' => __('Nome Completo', 'prefeitura-sad-feminino'),
                'name' => 'nome_completo',
                'type' => 'text',
            ],
            [
                'key' => 'field_prefeita_cargo',
                'label' => __('Cargo', 'prefeitura-sad-feminino'),
                'name' => 'cargo',
                'type' => 'text',
            ],
            [
                'key' => 'field_prefeita_mensagem',
                'label' => __('Mensagem à População', 'prefeitura-sad-feminino'),
                'name' => 'mensagem_a_populacao',
                'type' => 'wysiwyg',
            ],
            [
                'key' => 'field_prefeita_biografia',
                'label' => __('Biografia', 'prefeitura-sad-feminino'),
                'name' => 'biografia',
                'type' => 'wysiwyg',
            ],
            [
                'key' => 'field_prefeita_prioridades',
                'label' => __('Prioridades do Mandato', 'prefeitura-sad-feminino'),
                'name' => 'prioridades_do_mandato',
                'type' => 'repeater',
                'sub_fields' => [
                    [
                        'key' => 'field_prefeita_prioridade_titulo',
                        'label' => __('Título', 'prefeitura-sad-feminino'),
                        'name' => 'titulo',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_prefeita_prioridade_descricao',
                        'label' => __('Descrição', 'prefeitura-sad-feminino'),
                        'name' => 'descricao',
                        'type' => 'textarea',
                    ],
                ],
                'button_label' => __('Adicionar prioridade', 'prefeitura-sad-feminino'),
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-institucional.php',
                ],
            ],
        ],
    ]);
}
