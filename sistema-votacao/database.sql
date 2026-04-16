SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  senha VARCHAR(5) NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  used_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uq_tokens_senha (senha),
  KEY idx_tokens_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS candidatos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  foto VARCHAR(255) NOT NULL,
  numero SMALLINT UNSIGNED NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_candidatos_ativo_ordem (ativo, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS participantes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token_id BIGINT UNSIGNED NOT NULL,
  nome_completo VARCHAR(180) NOT NULL,
  whatsapp VARCHAR(20) NOT NULL,
  fingerprint CHAR(64) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_participantes_token FOREIGN KEY (token_id) REFERENCES tokens(id),
  KEY idx_participantes_token_id (token_id),
  KEY idx_participantes_whatsapp (whatsapp),
  KEY idx_participantes_fingerprint (fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS votos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token_id BIGINT UNSIGNED NOT NULL,
  participante_id BIGINT UNSIGNED NOT NULL,
  candidato_id INT UNSIGNED NOT NULL,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ip VARCHAR(45) DEFAULT NULL,
  dispositivo VARCHAR(120) DEFAULT NULL,
  navegador VARCHAR(120) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  localizacao VARCHAR(100) DEFAULT NULL,
  fingerprint CHAR(64) DEFAULT NULL,
  CONSTRAINT fk_votos_token FOREIGN KEY (token_id) REFERENCES tokens(id),
  CONSTRAINT fk_votos_participante FOREIGN KEY (participante_id) REFERENCES participantes(id),
  CONSTRAINT fk_votos_candidato FOREIGN KEY (candidato_id) REFERENCES candidatos(id),
  UNIQUE KEY uq_votos_token_id (token_id),
  KEY idx_votos_candidato_id (candidato_id),
  KEY idx_votos_timestamp (timestamp),
  KEY idx_votos_ip (ip),
  KEY idx_votos_fingerprint (fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  nome VARCHAR(120) NOT NULL,
  ultimo_login TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admin_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acessos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) DEFAULT NULL,
  rota VARCHAR(180) NOT NULL,
  metodo VARCHAR(10) NOT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  fingerprint CHAR(64) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_acessos_created_at (created_at),
  KEY idx_acessos_ip (ip),
  KEY idx_acessos_fingerprint (fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  chave VARCHAR(80) NOT NULL,
  valor TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_configuracoes_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO candidatos (nome, foto, numero, ativo, ordem) VALUES
('Candidata 01', '/assets/img/candidata1.jpg', 11, 1, 1),
('Candidata 02', '/assets/img/candidata2.jpg', 12, 1, 2),
('Candidata 03', '/assets/img/candidata3.jpg', 13, 1, 3),
('Candidata 04', '/assets/img/candidata4.jpg', 14, 1, 4),
('Candidata 05', '/assets/img/candidata5.jpg', 15, 1, 5),
('Candidata 06', '/assets/img/candidata6.jpg', 16, 1, 6),
('Candidata 07', '/assets/img/candidata7.jpg', 17, 1, 7),
('Candidata 08', '/assets/img/candidata8.jpg', 18, 1, 8),
('Candidata 09', '/assets/img/candidata9.jpg', 19, 1, 9),
('Candidata 10', '/assets/img/candidata10.jpg', 20, 1, 10)
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

INSERT INTO configuracoes (chave, valor) VALUES
('modo_manutencao', '0'),
('cache_ttl_segundos', '120'),
('nome_evento', 'Votação Municipal 2026'),
('rate_limit_tentativas', '10'),
('rate_limit_janela_segundos', '300')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- Usuário padrão: admin / Trocar senha após instalação.
-- Hash abaixo corresponde a: Admin@123
INSERT INTO admin_users (usuario, senha_hash, nome)
VALUES ('admin', '$2y$10$EK7BAtcD6McKd0Vwbj9SPeGHhVhtwAtxX06Qop5gQ3C7viYz5N8Q2', 'Administrador')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);
