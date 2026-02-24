<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_admin_menu_init(): void
{
    add_action('admin_menu', 'mpg_register_admin_menu');
}

function mpg_register_admin_menu(): void
{
    add_menu_page(
        'Mapa Político Goiás',
        'Mapa Político Goiás',
        'manage_options',
        'mpg-cadastro',
        'mpg_render_admin_cadastro_page',
        'dashicons-location-alt',
        26
    );

    add_submenu_page('mpg-cadastro', 'Cadastro Manual', 'Cadastro Manual', 'manage_options', 'mpg-cadastro', 'mpg_render_admin_cadastro_page');
    add_submenu_page('mpg-cadastro', 'Logs da IA', 'Logs da IA', 'manage_options', 'mpg-logs', 'mpg_render_admin_logs_page');
    add_submenu_page('mpg-cadastro', 'Excluir Cadastros', 'Excluir Cadastros', 'manage_options', 'mpg-delete', 'mpg_render_admin_delete_page');
}
