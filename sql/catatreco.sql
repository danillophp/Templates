CREATE DATABASE IF NOT EXISTS santo821_treco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE santo821_treco;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin','funcionario') NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pontos_mapa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(120) NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    endereco VARCHAR(255) NOT NULL,
    cep VARCHAR(20) NOT NULL,
    telefone VARCHAR(30) NOT NULL,
    foto VARCHAR(255) NOT NULL,
    data_solicitada DATETIME NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    status ENUM('PENDENTE','APROVADO','RECUSADO','FINALIZADO') NOT NULL DEFAULT 'PENDENTE',
    funcionario_id INT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitacoes_funcionario FOREIGN KEY (funcionario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitacao_id INT NULL,
    usuario_id INT NULL,
    acao VARCHAR(255) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_solicitacao FOREIGN KEY (solicitacao_id) REFERENCES solicitacoes(id) ON DELETE SET NULL,
    CONSTRAINT fk_logs_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO usuarios (nome,email,senha,tipo) VALUES
('Administrador','admin@prefeitura.gov.br','$2y$12$Y7qehFLNLO20wLibm2gL0eBcPoSpuXeng43FC8OIR.mJvvL2Cimwy','admin'),
('Funcionário 01','funcionario@prefeitura.gov.br','$2y$12$pl9kIpvdu3INlC.3LOdGyuZ61pLjHYL/urAZA9DWdfKuvv/O54KGC','funcionario');

INSERT INTO pontos_mapa (titulo, latitude, longitude, ativo) VALUES
('Ecoponto Centro', -23.550520, -46.633308, 1),
('Ecoponto Bairro Sul', -23.567000, -46.648000, 1);
