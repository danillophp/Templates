CREATE DATABASE IF NOT EXISTS mapa_politico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mapa_politico;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address VARCHAR(255) NOT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    city_info TEXT,
    region_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE politicians (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(180) NOT NULL,
    position VARCHAR(120) NOT NULL,
    party VARCHAR(100) NOT NULL,
    age TINYINT UNSIGNED,
    biography TEXT,
    career_history TEXT,
    municipality_history TEXT,
    photo_path VARCHAR(255),
    phone VARCHAR(30),
    email VARCHAR(190),
    advisors VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_politician_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Senha inicial: admin123 (altere após o primeiro acesso)
INSERT INTO users (name, email, password_hash)
VALUES ('Administrador', 'admin@seudominio.com', '$2y$10$CbofV31RN4EhlnsL1H4wiu8fvQX9jHsVrCA6QtnivAS9jROFA4Kj.');
