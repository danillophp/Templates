<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_db_activate(): void
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset = $wpdb->get_charset_collate();
    $prefeitos = $wpdb->prefix . 'mpg_prefeitos';

    $sqlPrefeitos = "CREATE TABLE {$prefeitos} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        municipio_nome VARCHAR(120) NOT NULL,
        municipio_codigo VARCHAR(12) NOT NULL,
        prefeito_nome VARCHAR(190) NOT NULL,
        vice_nome VARCHAR(190) NOT NULL,
        cargo VARCHAR(120) NULL,
        estado VARCHAR(80) NULL,
        partido VARCHAR(50) NULL,
        idade VARCHAR(10) NULL,
        telefone VARCHAR(30) NULL,
        email VARCHAR(190) NULL,
        endereco_prefeitura VARCHAR(255) NULL,
        cep VARCHAR(20) NULL,
        latitude DECIMAL(10,7) NOT NULL,
        longitude DECIMAL(10,7) NOT NULL,
        site_oficial TEXT NULL,
        fonte_primaria TEXT NULL,
        fontes_json LONGTEXT NULL,
        foto_attachment_id BIGINT UNSIGNED NULL,
        biografia_resumida TEXT NULL,
        historico_politico TEXT NULL,
        mandato VARCHAR(50) NULL,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_municipio_nome (municipio_nome),
        KEY idx_prefeito_nome (prefeito_nome),
        KEY idx_partido (partido),
        KEY idx_nome_cidade (prefeito_nome, municipio_nome)
    ) {$charset};";

    dbDelta($sqlPrefeitos);
}
