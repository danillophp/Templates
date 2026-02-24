-- SQL de referência para estrutura do plugin Mapa Político
-- Use somente como documentação; a criação oficial é feita por dbDelta() na ativação do plugin.

CREATE TABLE wp_mapa_politico_locations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  city VARCHAR(120) NOT NULL,
  state VARCHAR(120) NULL,
  address VARCHAR(255) NULL,
  postal_code VARCHAR(20) NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  city_info TEXT NULL,
  region_info TEXT NULL,
  ibge_code VARCHAR(12) NULL,
  institution_type VARCHAR(30) NULL,
  source_url TEXT NULL,
  last_synced_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE wp_mapa_politico_politicians (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  location_id BIGINT UNSIGNED NOT NULL,
  full_name VARCHAR(180) NOT NULL,
  position VARCHAR(120) NOT NULL,
  party VARCHAR(100) NOT NULL,
  age TINYINT UNSIGNED NULL,
  biography TEXT NULL,
  career_history TEXT NULL,
  municipality_history TEXT NULL,
  photo_url TEXT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(190) NULL,
  advisors VARCHAR(255) NULL,
  source_url TEXT NULL,
  source_name VARCHAR(190) NULL,
  data_status VARCHAR(40) NOT NULL DEFAULT 'completo',
  validation_notes TEXT NULL,
  is_auto TINYINT(1) NOT NULL DEFAULT 0,
  municipality_code VARCHAR(12) NULL,
  last_synced_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE wp_mapa_politico_sync_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  municipality_name VARCHAR(120) NOT NULL,
  municipality_code VARCHAR(12) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pendente',
  attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  source_note VARCHAR(190) NULL,
  last_error TEXT NULL,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_municipality_code (municipality_code)
);
