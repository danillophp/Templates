CREATE DATABASE IF NOT EXISTS educasade CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE educasade;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_id BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  totp_secret VARCHAR(64) NULL,
  totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  failed_login_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  password_changed_at DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_active_locked (is_active, locked_until)
) ENGINE=InnoDB;

CREATE TABLE user_roles (
  user_id BIGINT UNSIGNED NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, role_id),
  CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  was_successful TINYINT(1) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_attempts_lookup (username, ip_address, attempted_at),
  INDEX idx_login_attempts_time (attempted_at)
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
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_schools_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_schools_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_schools_filter (type, region, is_active),
  INDEX idx_schools_location (latitude, longitude)
) ENGINE=InnoDB;

CREATE TABLE school_photos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(80) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_school_photos_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  CONSTRAINT fk_school_photos_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_school_photos_school (school_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE school_staff (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NOT NULL,
  role_type ENUM('diretor','professor','coordenador','outro') NOT NULL,
  full_name VARCHAR(150) NULL,
  area VARCHAR(100) NULL,
  shift ENUM('matutino','vespertino','noturno','integral') NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_school_staff_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  INDEX idx_school_staff_lookup (school_id, role_type, shift)
) ENGINE=InnoDB;

CREATE TABLE school_stats (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NOT NULL,
  reference_date DATE NOT NULL,
  students_total INT UNSIGNED NOT NULL DEFAULT 0,
  available_slots INT UNSIGNED NOT NULL DEFAULT 0,
  rooms_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  grades_json JSON NULL,
  shifts_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_school_stats_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  UNIQUE KEY uk_school_stats_daily (school_id, reference_date),
  INDEX idx_school_stats_slot (available_slots)
) ENGINE=InnoDB;

CREATE TABLE menus (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NOT NULL,
  menu_date DATE NOT NULL,
  meal_type ENUM('cafe','almoco','lanche','jantar') NOT NULL,
  description TEXT NOT NULL,
  version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_menus_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  CONSTRAINT fk_menus_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uk_menus_version (school_id, menu_date, meal_type, version),
  INDEX idx_menus_lookup (school_id, menu_date)
) ENGINE=InnoDB;

CREATE TABLE calendars (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NOT NULL,
  year_ref SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  content_text MEDIUMTEXT NULL,
  file_path VARCHAR(255) NULL,
  version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_calendars_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  CONSTRAINT fk_calendars_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_calendars_school_year (school_id, year_ref, version)
) ENGINE=InnoDB;

CREATE TABLE inventory_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  unit ENUM('kg','un','lt') NOT NULL,
  minimum_quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_inventory_item_name (name)
) ENGINE=InnoDB;

CREATE TABLE inventory_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inventory_item_id BIGINT UNSIGNED NOT NULL,
  school_id BIGINT UNSIGNED NOT NULL,
  lot_code VARCHAR(60) NOT NULL,
  expiry_date DATE NOT NULL,
  quantity_current DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_inventory_batches_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_inventory_batches_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  UNIQUE KEY uk_inventory_batch (inventory_item_id, school_id, lot_code),
  INDEX idx_inventory_batches_alert (expiry_date, quantity_current)
) ENGINE=InnoDB;

CREATE TABLE inventory_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inventory_batch_id BIGINT UNSIGNED NOT NULL,
  school_id BIGINT UNSIGNED NOT NULL,
  movement_type ENUM('entrada','saida','baixa') NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  reason VARCHAR(200) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_inventory_movements_batch FOREIGN KEY (inventory_batch_id) REFERENCES inventory_batches(id),
  CONSTRAINT fk_inventory_movements_school FOREIGN KEY (school_id) REFERENCES schools(id),
  CONSTRAINT fk_inventory_movements_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_inventory_movements_report (school_id, created_at),
  INDEX idx_inventory_movements_batch (inventory_batch_id, created_at)
) ENGINE=InnoDB;



CREATE TABLE stock_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  unit ENUM('kg','un','lt') NOT NULL,
  lot_code VARCHAR(60) NOT NULL,
  expiry_date DATE NOT NULL,
  quantity_current DECIMAL(10,2) NOT NULL DEFAULT 0,
  minimum_quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_stock_expiry (expiry_date),
  INDEX idx_stock_qty (quantity_current)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  stock_item_id BIGINT UNSIGNED NOT NULL,
  school_id BIGINT UNSIGNED NOT NULL,
  movement_type ENUM('entrada','saida','baixa') NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  notes VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_stock_mov_item FOREIGN KEY (stock_item_id) REFERENCES stock_items(id),
  CONSTRAINT fk_stock_mov_school FOREIGN KEY (school_id) REFERENCES schools(id),
  CONSTRAINT fk_stock_mov_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_mov_created (created_at),
  INDEX idx_mov_school (school_id)
) ENGINE=InnoDB;

CREATE TABLE alerts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NULL,
  alert_type ENUM('estoque_baixo','validade_proxima','integracao_falha','seguranca') NOT NULL,
  severity ENUM('info','warning','critical') NOT NULL DEFAULT 'warning',
  title VARCHAR(180) NOT NULL,
  details TEXT NULL,
  resolved_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_alerts_open (alert_type, severity, resolved_at),
  CONSTRAINT fk_alerts_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL
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
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_news_user_created FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_news_user_updated FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
  FULLTEXT KEY ftx_news_content (title, summary, body),
  INDEX idx_news_status_pub (status, published_at)
) ENGINE=InnoDB;

CREATE TABLE events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  location VARCHAR(200) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_events_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_events_calendar (status, starts_at)
) ENGINE=InnoDB;

CREATE TABLE documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  category VARCHAR(100) NOT NULL,
  tags VARCHAR(255) NULL,
  year_ref SMALLINT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(80) NOT NULL DEFAULT 'application/pdf',
  file_size INT UNSIGNED NOT NULL DEFAULT 0,
  is_public TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_documents_user_created FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_documents_user_updated FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
  FULLTEXT KEY ftx_documents_title (title),
  INDEX idx_documents_filters (category, year_ref, is_public)
) ENGINE=InnoDB;

CREATE TABLE document_tags (
  document_id BIGINT UNSIGNED NOT NULL,
  tag VARCHAR(80) NOT NULL,
  PRIMARY KEY (document_id, tag),
  CONSTRAINT fk_document_tags_document FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
  INDEX idx_document_tags_tag (tag)
) ENGINE=InnoDB;

CREATE TABLE transparency_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category ENUM('receitas','despesas','licitacoes','contratos','folha','outros') NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  year_ref SMALLINT UNSIGNED NOT NULL,
  source_type ENUM('arquivo','link') NOT NULL,
  file_path VARCHAR(255) NULL,
  source_url VARCHAR(255) NULL,
  published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  CONSTRAINT fk_transparency_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_transparency_filters (category, year_ref, published_at)
) ENGINE=InnoDB;

CREATE TABLE sic_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  protocol VARCHAR(24) NOT NULL UNIQUE,
  citizen_name VARCHAR(150) NOT NULL,
  citizen_email VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('aberto','em_analise','respondido','encerrado') NOT NULL DEFAULT 'aberto',
  response_text TEXT NULL,
  retention_until DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sic_status_time (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE integrations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider ENUM('gier','centi') NOT NULL,
  base_url VARCHAR(255) NOT NULL,
  encrypted_credentials TEXT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_provider (provider),
  CONSTRAINT fk_integrations_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE integration_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  integration_id BIGINT UNSIGNED NOT NULL,
  run_type ENUM('manual','upload_csv','upload_json') NOT NULL,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  status ENUM('running','success','error') NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  CONSTRAINT fk_integration_runs_integration FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE CASCADE,
  CONSTRAINT fk_integration_runs_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_integration_runs_status (integration_id, status, started_at)
) ENGINE=InnoDB;

CREATE TABLE sync_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  integration_run_id BIGINT UNSIGNED NULL,
  provider ENUM('gier','centi') NOT NULL,
  sync_type ENUM('escolas','turmas','matriculas','vagas') NOT NULL,
  result ENUM('sucesso','erro') NOT NULL,
  message TEXT NULL,
  payload_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sync_logs_run FOREIGN KEY (integration_run_id) REFERENCES integration_runs(id) ON DELETE SET NULL,
  INDEX idx_sync_logs_provider_time (provider, created_at)
) ENGINE=InnoDB;

CREATE TABLE cache_store (
  cache_key VARCHAR(191) PRIMARY KEY,
  cache_value LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  tags VARCHAR(500) NULL,
  checksum CHAR(64) NOT NULL,
  hits BIGINT UNSIGNED NOT NULL DEFAULT 0,
  last_hit_at DATETIME NULL,
  last_modified_at DATETIME NOT NULL,
  INDEX idx_cache_expires (expires_at),
  INDEX idx_cache_lastmod (last_modified_at)
) ENGINE=InnoDB;

CREATE TABLE cache_tags (
  tag_name VARCHAR(100) NOT NULL,
  cache_key VARCHAR(191) NOT NULL,
  PRIMARY KEY (tag_name, cache_key),
  CONSTRAINT fk_cache_tags_key FOREIGN KEY (cache_key) REFERENCES cache_store(cache_key) ON DELETE CASCADE,
  INDEX idx_cache_tags_key (cache_key)
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  subject VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_contact_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_user_id BIGINT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity VARCHAR(80) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  before_json JSON NULL,
  after_json JSON NULL,
  ip VARCHAR(45) NOT NULL,
  user_agent VARCHAR(255) NOT NULL,
  request_id CHAR(36) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_log_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_entity_time (entity, entity_id, created_at),
  INDEX idx_audit_actor_time (actor_user_id, created_at)
) ENGINE=InnoDB;

INSERT INTO roles (name, description) VALUES
('super_admin', 'Acesso total ao sistema'),
('secretaria', 'Gestão institucional'),
('gestor', 'Gestão escolar e estoque'),
('diretor', 'Acesso de direção escolar'),
('estoque', 'Operações de estoque'),
('conteudo', 'Publicação de conteúdo');

INSERT INTO permissions (name, description) VALUES
('admin.access', 'Acesso ao painel administrativo'),
('schools.manage', 'Cadastrar e alterar escolas'),
('content.manage', 'Cadastrar notícias/documentos'),
('inventory.manage', 'Movimentar estoque'),
('audit.view', 'Consultar auditoria'),
('audit.export', 'Exportar auditoria');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r
JOIN permissions p ON (
    (r.name = 'super_admin')
    OR (r.name = 'secretaria' AND p.name IN ('admin.access','schools.manage','content.manage','inventory.manage','audit.view','audit.export'))
    OR (r.name = 'gestor' AND p.name IN ('admin.access','schools.manage','inventory.manage','audit.view'))
    OR (r.name = 'diretor' AND p.name IN ('admin.access','audit.view'))
    OR (r.name = 'estoque' AND p.name IN ('admin.access','inventory.manage','audit.view'))
    OR (r.name = 'conteudo' AND p.name IN ('admin.access','content.manage','audit.view'))
);

INSERT INTO users (username, email, password_hash, full_name, password_changed_at)
VALUES ('superadmin', 'admin@educasade.com.br', '$2y$10$2Ug0Qn9QSYjLVH9qgxQqkOaYfMNLxV7L4nVcn4sFd4gfdw4Ni2xRe', 'Administrador Geral', NOW());

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u JOIN roles r ON r.name = 'super_admin' WHERE u.username = 'superadmin';
