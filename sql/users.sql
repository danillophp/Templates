-- SQL inicial para autenticação
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Senha do usuário abaixo: admin123
INSERT INTO users (name, email, password)
VALUES ('Administrador', 'admin@estudio.com', '$2y$10$9fYYf93S5Q1wG8iM8w2fTOxMpk8J0M6lJzi10BGSAdoo6gWQBaLKm');
