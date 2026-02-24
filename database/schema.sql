CREATE DATABASE IF NOT EXISTS santo821_treco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE santo821_treco;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('ADMINISTRADOR','FUNCIONARIO') NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pickup_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    citizen_name VARCHAR(190) NOT NULL,
    address VARCHAR(255) NOT NULL,
    cep VARCHAR(20) NULL,
    whatsapp VARCHAR(30) NOT NULL,
    photo_path VARCHAR(255) NULL,
    scheduled_at DATETIME NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    status ENUM('PENDENTE','APROVADA','EM_ANDAMENTO','FINALIZADA','RECUSADA') NOT NULL DEFAULT 'PENDENTE',
    assigned_user_id INT UNSIGNED NULL,
    admin_notes TEXT NULL,
    consent_lgpd TINYINT(1) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_pickup_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_at)
) ENGINE=InnoDB;

CREATE TABLE request_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL,
    actor_user_id INT UNSIGNED NULL,
    note TEXT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_hist_request FOREIGN KEY (request_id) REFERENCES pickup_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_hist_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    metadata_json JSON NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Senhas iniciais: admin123 / funcionario123
INSERT INTO users (name, username, password_hash, role) VALUES
('Administrador', 'admin', '$2y$10$CbofV31RN4EhlnsL1H4wiu8fvQX9jHsVrCA6QtnivAS9jROFA4Kj.', 'ADMINISTRADOR'),
('Equipe Cata Treco', 'funcionario1', '$2y$12$h8gNX6OnWzXuo.S1dNuMwez7DTdrcYpSIjFYiME1vFpEDNN7dDbe6', 'FUNCIONARIO');
