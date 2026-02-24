<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_db_activate(): void
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset = $wpdb->get_charset_collate();
    $fila = $wpdb->prefix . 'mpg_fila_sync';
    $prefeitos = $wpdb->prefix . 'mpg_prefeitos';
    $manualQueue = $wpdb->prefix . 'mpg_manual_search_queue';

    $sqlFila = "CREATE TABLE {$fila} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        municipio_nome VARCHAR(120) NOT NULL,
        municipio_codigo VARCHAR(12) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pendente',
        tentativas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        ultima_fonte TEXT NULL,
        ultimo_erro TEXT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        iniciado_em DATETIME NULL,
        finalizado_em DATETIME NULL,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_municipio_codigo (municipio_codigo),
        KEY idx_status (status)
    ) {$charset};";

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
        numero_votos VARCHAR(30) NULL,
        telefone VARCHAR(30) NULL,
        email VARCHAR(190) NULL,
        endereco_prefeitura VARCHAR(255) NULL,
        cep VARCHAR(20) NULL,
        latitude DECIMAL(10,7) NOT NULL,
        longitude DECIMAL(10,7) NOT NULL,
        site_oficial TEXT NULL,
        fonte_primaria TEXT NOT NULL,
        fontes_json LONGTEXT NULL,
        foto_url TEXT NULL,
        biografia_resumida TEXT NULL,
        historico_politico TEXT NULL,
        mandato VARCHAR(50) NULL,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_municipio_prefeito (municipio_codigo),
        KEY idx_municipio_nome (municipio_nome),
        KEY idx_prefeito_nome (prefeito_nome),
        KEY idx_partido (partido),
        KEY idx_nome_cidade (prefeito_nome, municipio_nome)
    ) {$charset};";

    $sqlManualQueue = "CREATE TABLE {$manualQueue} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NULL,
        query_text TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pendente',
        tentativas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        ultimo_erro TEXT NULL,
        resultado_id BIGINT UNSIGNED NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        iniciado_em DATETIME NULL,
        finalizado_em DATETIME NULL,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_status (status)
    ) {$charset};";

    dbDelta($sqlFila);
    dbDelta($sqlPrefeitos);
    dbDelta($sqlManualQueue);
}
