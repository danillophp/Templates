<?php

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key' => 'group_contato_cidadao',
        'title' => __('Contato com a Cidadã', 'prefeitura-sad-feminino'),
        'fields' => [
            [
                'key' => 'field_contato_nome',
                'label' => __('Nome', 'prefeitura-sad-feminino'),
                'name' => 'nome',
                'type' => 'text',
            ],
            [
                'key' => 'field_contato_email',
                'label' => __('Email', 'prefeitura-sad-feminino'),
                'name' => 'email',
                'type' => 'email',
            ],
            [
                'key' => 'field_contato_telefone',
                'label' => __('Telefone', 'prefeitura-sad-feminino'),
                'name' => 'telefone',
                'type' => 'text',
            ],
            [
                'key' => 'field_contato_bairro',
                'label' => __('Bairro', 'prefeitura-sad-feminino'),
                'name' => 'bairro',
                'type' => 'text',
            ],
            [
                'key' => 'field_contato_assunto',
                'label' => __('Assunto', 'prefeitura-sad-feminino'),
                'name' => 'assunto',
                'type' => 'text',
            ],
            [
                'key' => 'field_contato_mensagem',
                'label' => __('Mensagem', 'prefeitura-sad-feminino'),
                'name' => 'mensagem',
                'type' => 'textarea',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ],
            ],
        ],
    ]);
}
