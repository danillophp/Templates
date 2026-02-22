CREATE DATABASE IF NOT EXISTS santo821_treco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE santo821_treco;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  usuario VARCHAR(60) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  role ENUM('admin') NOT NULL DEFAULT 'admin',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pontos_coleta (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  descricao TEXT,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS solicitacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  protocolo VARCHAR(20) UNIQUE,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL,
  telefone_whatsapp VARCHAR(20) NOT NULL,
  endereco VARCHAR(255) NOT NULL,
  cep VARCHAR(10) NOT NULL,
  data_agendada DATE NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  foto_path VARCHAR(255) NULL,
  status ENUM('PENDENTE','APROVADO','RECUSADO','ALTERADO','FINALIZADO') NOT NULL DEFAULT 'PENDENTE',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS logs_auditoria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  acao VARCHAR(120) NOT NULL,
  entidade VARCHAR(80) NOT NULL,
  entidade_id INT NOT NULL,
  dados_antes JSON NULL,
  dados_depois JSON NULL,
  ip VARCHAR(50),
  user_agent VARCHAR(255),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notificacoes_admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('novo_agendamento') NOT NULL,
  solicitacao_id INT NOT NULL,
  payload_json JSON NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mensagens_fila (
  id INT AUTO_INCREMENT PRIMARY KEY,
  solicitacao_id INT NOT NULL,
  canal ENUM('email') NOT NULL,
  destino VARCHAR(120) NOT NULL,
  template VARCHAR(80) NOT NULL,
  payload_json JSON NOT NULL,
  status ENUM('pendente','enviando','enviado','erro') NOT NULL DEFAULT 'pendente',
  tentativas TINYINT NOT NULL DEFAULT 0,
  erro_mensagem TEXT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO usuarios (nome, usuario, email, senha_hash, role, ativo)
VALUES ('Administrador', 'admin', 'admin@prefsade.com.br', '$2y$10$M37xHiL8nC2nTk65JvSxyepvRlOV9L6fRj3gyEjxW4P0di9hNf9du', 'admin', 1);

INSERT INTO pontos_coleta (nome, descricao, latitude, longitude, ativo)
VALUES ('Ecoponto Central', 'Ponto de descarte de móveis e volumosos', -15.9404070, -48.2571520, 1);
