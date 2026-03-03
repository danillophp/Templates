CREATE DATABASE IF NOT EXISTS educasade CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE educasade;

CREATE TABLE roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id BIGINT UNSIGNED NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
  INDEX idx_users_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE schools (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  type ENUM('escola','creche') NOT NULL,
  region VARCHAR(100) NULL,
  address VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NULL,
  director_name VARCHAR(150) NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  rooms_count INT NOT NULL DEFAULT 0,
  total_students INT NOT NULL DEFAULT 0,
  available_slots INT NOT NULL DEFAULT 0,
  show_teachers_public TINYINT(1) NOT NULL DEFAULT 0,
  enrollment_link VARCHAR(255) NULL,
  app_link VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_school_type_region (type, region),
  INDEX idx_school_slots (available_slots)
) ENGINE=InnoDB;

CREATE TABLE school_photos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(80) NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_school_photos_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  INDEX idx_school_photos_school (school_id)
) ENGINE=InnoDB;

CREATE TABLE news (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  category VARCHAR(80) NOT NULL,
  summary TEXT NOT NULL,
  body MEDIUMTEXT NOT NULL,
  image_path VARCHAR(255) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_news_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_news_status_pub (status, published_at)
) ENGINE=InnoDB;

CREATE TABLE documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  category VARCHAR(100) NOT NULL,
  tags VARCHAR(255) NULL,
  year_ref SMALLINT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_documents_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_documents_filters (category, year_ref)
) ENGINE=InnoDB;

CREATE TABLE stock_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  unit ENUM('kg','un','lt') NOT NULL,
  lot_code VARCHAR(60) NOT NULL,
  expiry_date DATE NOT NULL,
  quantity_current DECIMAL(10,2) NOT NULL DEFAULT 0,
  minimum_quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_stock_expiry (expiry_date),
  INDEX idx_stock_qty (quantity_current)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  stock_item_id BIGINT UNSIGNED NOT NULL,
  school_id BIGINT UNSIGNED NOT NULL,
  movement_type ENUM('entrada','saida') NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  notes VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_stock_mov_item FOREIGN KEY (stock_item_id) REFERENCES stock_items(id),
  CONSTRAINT fk_stock_mov_school FOREIGN KEY (school_id) REFERENCES schools(id),
  CONSTRAINT fk_stock_mov_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_mov_created (created_at),
  INDEX idx_mov_school (school_id)
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  subject VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_contact_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE sic_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  protocol VARCHAR(20) NOT NULL UNIQUE,
  citizen_name VARCHAR(150) NOT NULL,
  citizen_email VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('aberto','em_analise','respondido','encerrado') NOT NULL DEFAULT 'aberto',
  response_text TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_sic_status (status)
) ENGINE=InnoDB;

CREATE TABLE integrations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider ENUM('gier','centi') NOT NULL,
  base_url VARCHAR(255) NOT NULL,
  encrypted_credentials TEXT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uk_provider (provider)
) ENGINE=InnoDB;

CREATE TABLE sync_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider ENUM('gier','centi') NOT NULL,
  sync_type ENUM('escolas','turmas','matriculas','vagas') NOT NULL,
  result ENUM('sucesso','erro') NOT NULL,
  message TEXT NULL,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  CONSTRAINT fk_sync_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_sync_provider_time (provider, started_at)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(80) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(60) NOT NULL,
  actor_user_id BIGINT UNSIGNED NULL,
  payload_json JSON NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_audit_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_entity (entity_type, entity_id),
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

CREATE VIEW vw_stock_alerts AS
SELECT id, name, quantity_current, minimum_quantity, expiry_date,
       (quantity_current <= minimum_quantity) AS is_low_stock,
       (expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS near_expiry
FROM stock_items;

INSERT INTO roles (name) VALUES
('super_admin'),('secretaria'),('gestor'),('diretor'),('estoque'),('conteudo');

INSERT INTO users (role_id, username, email, password_hash, full_name, created_at, updated_at)
VALUES
(1, 'superadmin', 'admin@educasade.com.br', '$2y$10$2Ug0Qn9QSYjLVH9qgxQqkOaYfMNLxV7L4nVcn4sFd4gfdw4Ni2xRe', 'Administrador Geral', NOW(), NOW());
