CREATE DATABASE IF NOT EXISTS santo821_treco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE santo821_treco;

CREATE TABLE IF NOT EXISTS super_admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(140) NOT NULL,
  slug VARCHAR(90) NOT NULL UNIQUE,
  dominio VARCHAR(180) NOT NULL UNIQUE,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS planos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL,
  limite_solicitacoes_mes INT NOT NULL,
  limite_funcionarios INT NOT NULL,
  valor_mensal DECIMAL(10,2) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assinaturas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  plano_id INT NOT NULL,
  status ENUM('ATIVA','INADIMPLENTE','CANCELADA') NOT NULL DEFAULT 'ATIVA',
  vencimento DATE NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  FOREIGN KEY (plano_id) REFERENCES planos(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NULL,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  tipo ENUM('super_admin','admin','funcionario') NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email_tenant (tenant_id, email),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS configuracoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  nome_prefeitura VARCHAR(140) NOT NULL,
  logo VARCHAR(255) NULL,
  cor_primaria VARCHAR(20) NOT NULL DEFAULT '#198754',
  whatsapp_numero VARCHAR(30) NULL,
  email_contato VARCHAR(160) NULL,
  horario_funcionamento VARCHAR(160) NULL,
  texto_rodape VARCHAR(255) NULL,
  wa_token VARCHAR(255) NULL,
  wa_phone_number_id VARCHAR(80) NULL,
  wa_template VARCHAR(120) NULL,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  UNIQUE KEY uq_config_tenant (tenant_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pontos_mapa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  titulo VARCHAR(120) NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  cor_pin VARCHAR(20) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  protocolo VARCHAR(25) NULL,
  nome VARCHAR(120) NOT NULL,
  endereco VARCHAR(255) NOT NULL,
  bairro VARCHAR(120) NULL,
  cep VARCHAR(20) NOT NULL,
  telefone VARCHAR(30) NOT NULL,
  foto VARCHAR(255) NOT NULL,
  data_solicitada DATETIME NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  status ENUM('PENDENTE','APROVADO','RECUSADO','ALTERADO','FINALIZADO') NOT NULL DEFAULT 'PENDENTE',
  funcionario_id INT NULL,
  finalizado_em DATETIME NULL,
  criado_em DATETIME NOT NULL,
  atualizado_em DATETIME NOT NULL,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (funcionario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_tenant_status (tenant_id, status),
  INDEX idx_tenant_data (tenant_id, data_solicitada),
  UNIQUE KEY uq_protocolo_tenant (tenant_id, protocolo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  solicitacao_id INT NULL,
  usuario_id INT NULL,
  acao VARCHAR(120) NOT NULL,
  detalhes TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (solicitacao_id) REFERENCES solicitacoes(id) ON DELETE SET NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notificacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  canal VARCHAR(30) NOT NULL,
  destino VARCHAR(120) NOT NULL,
  status VARCHAR(40) NOT NULL,
  resposta TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pagamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  assinatura_id INT NOT NULL,
  gateway VARCHAR(40) NOT NULL DEFAULT 'stripe',
  referencia_externa VARCHAR(120) NULL,
  valor DECIMAL(10,2) NOT NULL,
  status VARCHAR(30) NOT NULL,
  vencimento DATE NULL,
  pago_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  FOREIGN KEY (assinatura_id) REFERENCES assinaturas(id)
) ENGINE=InnoDB;

INSERT INTO planos (nome, limite_solicitacoes_mes, limite_funcionarios, valor_mensal, ativo) VALUES
('Starter', 500, 5, 299.90, 1),
('Pro', 2000, 20, 799.90, 1);

INSERT INTO tenants (nome, slug, dominio, ativo) VALUES
('Prefeitura Demo', 'demo', 'demo.catatreco.com', 1);

INSERT INTO assinaturas (tenant_id, plano_id, status, vencimento) VALUES
(1, 2, 'ATIVA', DATE_ADD(CURDATE(), INTERVAL 30 DAY));

INSERT INTO configuracoes (tenant_id, nome_prefeitura, cor_primaria, texto_rodape, email_contato) VALUES
(1, 'Prefeitura Demo', '#198754', 'Cata Treco SaaS', 'contato@prefdemo.gov.br');

INSERT INTO usuarios (tenant_id, nome, email, senha, tipo, ativo) VALUES
(NULL, 'Owner SaaS', 'owner@catatreco.com', '$2y$12$Y7qehFLNLO20wLibm2gL0eBcPoSpuXeng43FC8OIR.mJvvL2Cimwy', 'super_admin', 1),
(1, 'Admin Demo', 'admin@prefdemo.gov.br', '$2y$12$Y7qehFLNLO20wLibm2gL0eBcPoSpuXeng43FC8OIR.mJvvL2Cimwy', 'admin', 1),
(1, 'Funcionário Demo', 'funcionario@prefdemo.gov.br', '$2y$12$pl9kIpvdu3INlC.3LOdGyuZ61pLjHYL/urAZA9DWdfKuvv/O54KGC', 'funcionario', 1);

INSERT INTO pontos_mapa (tenant_id, titulo, latitude, longitude, cor_pin, ativo) VALUES
(1, 'Ecoponto Centro', -23.550520, -46.633308, '#198754', 1);
