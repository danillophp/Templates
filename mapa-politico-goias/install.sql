-- Referência de schema (criação oficial via dbDelta em includes/database.php)

CREATE TABLE wp_mpg_fila_sync (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
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
  UNIQUE KEY uniq_municipio_codigo (municipio_codigo),
  KEY idx_status (status)
);

CREATE TABLE wp_mpg_prefeitos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  municipio_nome VARCHAR(120) NOT NULL,
  municipio_codigo VARCHAR(12) NOT NULL,
  prefeito_nome VARCHAR(190) NOT NULL,
  vice_nome VARCHAR(190) NOT NULL,
  cargo VARCHAR(120) NULL,
  estado VARCHAR(80) NULL,
  partido VARCHAR(50) NULL,
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
  UNIQUE KEY uniq_municipio_prefeito (municipio_codigo),
  KEY idx_municipio_nome (municipio_nome),
  KEY idx_prefeito_nome (prefeito_nome),
  KEY idx_partido (partido)
);

CREATE TABLE wp_mpg_manual_search_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
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
  KEY idx_status (status)
);
