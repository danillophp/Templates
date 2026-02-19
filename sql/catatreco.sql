CREATE DATABASE IF NOT EXISTS santo821_treco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE santo821_treco;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'FUNCIONARIO') NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    address VARCHAR(255) NOT NULL,
    cep VARCHAR(20) NOT NULL,
    whatsapp VARCHAR(30) NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    pickup_datetime DATETIME NOT NULL,
    status ENUM('PENDENTE', 'APROVADO', 'RECUSADO', 'REAGENDADO', 'ENCAMINHADO', 'FINALIZADO') NOT NULL DEFAULT 'PENDENTE',
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    consent_given TINYINT(1) NOT NULL DEFAULT 1,
    request_ip VARCHAR(45) NOT NULL,
    assigned_user_id INT NULL,
    finalized_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_request_user FOREIGN KEY (assigned_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NULL,
    actor_user_id INT NULL,
    actor_role VARCHAR(30) NOT NULL,
    action VARCHAR(80) NOT NULL,
    details TEXT NULL,
    actor_ip VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_logs_request FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_logs_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO users (username, password_hash, role, full_name) VALUES
('admin', '$2y$12$3BlToSH5Hg3sSUFn09pEIuunitHsxErEb6VoWfgY.n0CB.EwCdHlm', 'ADMIN', 'Administrador'),
('funcionario1', '$2y$12$iqILOR1/hgWGWucm96QE.ecu2PPEYeyFtgPo23azJ5OgyXy.8UwPG', 'FUNCIONARIO', 'Equipe Operacional');
