<?php

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key' => 'group_secretarias',
        'title' => __('Secretarias Municipais', 'prefeitura-sad-feminino'),
        'fields' => [
            [
                'key' => 'field_secretaria_nome',
                'label' => __('Nome da Secretaria', 'prefeitura-sad-feminino'),
                'name' => 'nome_secretaria',
                'type' => 'text',
            ],
            [
                'key' => 'field_secretaria_foto',
                'label' => __('Foto Representativa', 'prefeitura-sad-feminino'),
                'name' => 'foto_representativa',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
            [
                'key' => 'field_secretaria_secretario',
                'label' => __('Secretário Responsável', 'prefeitura-sad-feminino'),
                'name' => 'secretario_responsavel',
                'type' => 'text',
            ],
            [
                'key' => 'field_secretaria_descricao',
                'label' => __('Descrição', 'prefeitura-sad-feminino'),
                'name' => 'descricao',
                'type' => 'wysiwyg',
            ],
            [
                'key' => 'field_secretaria_telefone',
                'label' => __('Telefone', 'prefeitura-sad-feminino'),
                'name' => 'telefone',
                'type' => 'text',
            ],
            [
                'key' => 'field_secretaria_email',
                'label' => __('Email', 'prefeitura-sad-feminino'),
                'name' => 'email',
                'type' => 'email',
            ],
            [
                'key' => 'field_secretaria_horario',
                'label' => __('Horário de Funcionamento', 'prefeitura-sad-feminino'),
                'name' => 'horario_funcionamento',
                'type' => 'text',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'secretarias',
                ],
            ],
        ],
    ]);
}
