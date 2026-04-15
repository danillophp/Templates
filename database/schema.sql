-- Festival 14 de Maio | Garota SADE 2026
-- Schema MySQL 8+ / MariaDB 10.5+

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('SUPER_ADMIN', 'OPERADOR', 'AUDITOR') NOT NULL DEFAULT 'OPERADOR',
    ultimo_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admins_perfil (perfil)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS candidatas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero SMALLINT UNSIGNED NOT NULL,
    nome VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    idade TINYINT UNSIGNED NOT NULL,
    cidade VARCHAR(120) NOT NULL,
    descricao TEXT NOT NULL,
    foto VARCHAR(255) NOT NULL,
    status ENUM('ATIVA', 'INATIVA', 'EXCLUIDA') NOT NULL DEFAULT 'ATIVA',
    exibir_publicamente TINYINT(1) NOT NULL DEFAULT 1,
    votos_total BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_candidatas_numero (numero),
    INDEX idx_candidatas_status (status),
    INDEX idx_candidatas_votos_total (votos_total DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS votos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidata_id BIGINT UNSIGNED NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    user_agent_hash CHAR(64) NOT NULL,
    device_fingerprint CHAR(64) NOT NULL,
    cookie_token CHAR(64) NOT NULL,
    sessao_token VARCHAR(128) NOT NULL,
    origem VARCHAR(60) NOT NULL DEFAULT 'web',
    status ENUM('VALIDO', 'BLOQUEADO', 'SUSPEITO', 'INVALIDADO') NOT NULL DEFAULT 'VALIDO',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_votos_candidata FOREIGN KEY (candidata_id) REFERENCES candidatas(id),
    INDEX idx_votos_candidata (candidata_id),
    INDEX idx_votos_status (status),
    INDEX idx_votos_created_at (created_at),
    INDEX idx_votos_ip_hash (ip_hash),
    INDEX idx_votos_device_fingerprint (device_fingerprint),
    INDEX idx_votos_sessao_token (sessao_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tentativas_fraude (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_hash CHAR(64) NOT NULL,
    user_agent_hash CHAR(64) NOT NULL,
    fingerprint CHAR(64) NOT NULL,
    motivo VARCHAR(150) NOT NULL,
    payload_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tentativas_fraude_created_at (created_at),
    INDEX idx_tentativas_fraude_ip_hash (ip_hash),
    INDEX idx_tentativas_fraude_motivo (motivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_admin (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT UNSIGNED NOT NULL,
    acao VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    ip VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_admin_admin FOREIGN KEY (admin_id) REFERENCES admins(id),
    INDEX idx_logs_admin_admin_id (admin_id),
    INDEX idx_logs_admin_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS midias_evento (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    imagem VARCHAR(255) NOT NULL,
    tipo ENUM('BANNER', 'ATRAÇÃO', 'PATROCINADOR', 'GALERIA') NOT NULL DEFAULT 'BANNER',
    ordem INT NOT NULL DEFAULT 0,
    status ENUM('ATIVO', 'INATIVO') NOT NULL DEFAULT 'ATIVO',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_midias_evento_tipo (tipo),
    INDEX idx_midias_evento_status (status),
    INDEX idx_midias_evento_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracoes (chave, valor)
VALUES
('votacao_aberta', '1'),
('max_votos_ip_24h', '3'),
('max_votos_dispositivo_24h', '2'),
('tempo_minimo_interacao_segundos', '5')
ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW();
