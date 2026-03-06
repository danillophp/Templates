-- Compatibilidade retroativa: mantenha este arquivo para importação rápida de usuários.
-- Para instalação completa, utilize: sql/schema.sql

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin','atendente') NOT NULL DEFAULT 'admin',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuarios (nome, email, senha, perfil)
VALUES ('Administrador', 'admin@studiobrunanayara.com', '$2y$10$9fYYf93S5Q1wG8iM8w2fTOxMpk8J0M6lJzi10BGSAdoo6gWQBaLKm', 'admin')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);
