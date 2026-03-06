-- ==========================================================
-- Studio Bruna Nayara - Schema inicial MySQL 8 / HostGator
-- ==========================================================
SET NAMES utf8mb4;
SET time_zone = '-03:00';

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin','atendente') NOT NULL DEFAULT 'admin',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_login_em DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(160) NOT NULL,
    telefone VARCHAR(30) NOT NULL,
    email VARCHAR(160) NULL,
    data_nascimento DATE NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clientes_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categorias_servicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL UNIQUE,
    descricao VARCHAR(255) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT UNSIGNED NOT NULL,
    nome VARCHAR(160) NOT NULL,
    descricao TEXT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    percentual_entrada DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_servicos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_servicos(id),
    INDEX idx_servicos_categoria (categoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes_agenda (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dia_semana TINYINT UNSIGNED NOT NULL COMMENT '0=domingo ... 6=sábado',
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    intervalo_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_agenda_dia (dia_semana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bloqueios_agenda (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    motivo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bloqueios_periodo (data_inicio, data_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agendamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    servico_id INT UNSIGNED NOT NULL,
    usuario_responsavel_id INT UNSIGNED NULL,
    data_hora_inicio DATETIME NOT NULL,
    data_hora_fim DATETIME NOT NULL,
    status ENUM('pre_reservado','aguardando_pagamento','confirmado','cancelado','realizado','faltou') NOT NULL DEFAULT 'pre_reservado',
    valor_total DECIMAL(10,2) NOT NULL,
    valor_entrada DECIMAL(10,2) NOT NULL,
    valor_restante DECIMAL(10,2) NOT NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_agendamento_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    CONSTRAINT fk_agendamento_servico FOREIGN KEY (servico_id) REFERENCES servicos(id),
    CONSTRAINT fk_agendamento_usuario FOREIGN KEY (usuario_responsavel_id) REFERENCES usuarios(id),
    INDEX idx_agendamento_data (data_hora_inicio),
    INDEX idx_agendamento_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pagamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agendamento_id INT UNSIGNED NOT NULL,
    tipo ENUM('entrada','saldo') NOT NULL DEFAULT 'entrada',
    valor DECIMAL(10,2) NOT NULL,
    metodo VARCHAR(50) NULL COMMENT 'pix,cartao,dinheiro etc',
    status ENUM('pendente','aguardando_confirmacao','pago','cancelado','expirado','estornado') NOT NULL DEFAULT 'pendente',
    transacao_externa_id VARCHAR(100) NULL,
    pago_em DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pagamentos_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id),
    INDEX idx_pagamentos_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agendamento_id INT UNSIGNED NULL,
    cliente_id INT UNSIGNED NULL,
    tipo ENUM('lembrete_cliente','resumo_proprietaria','sistema') NOT NULL,
    canal ENUM('email','whatsapp','sms','interno') NOT NULL DEFAULT 'interno',
    mensagem TEXT NOT NULL,
    status ENUM('pendente','enviada','erro') NOT NULL DEFAULT 'pendente',
    enviado_em DATETIME NULL,
    erro_detalhes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id),
    CONSTRAINT fk_notif_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    INDEX idx_notif_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_sistema (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nivel ENUM('info','warning','error') NOT NULL DEFAULT 'info',
    origem VARCHAR(100) NOT NULL,
    mensagem TEXT NOT NULL,
    contexto JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logs_origem (origem),
    INDEX idx_logs_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário administrador inicial (senha: admin123)
INSERT INTO usuarios (nome, email, senha, perfil)
VALUES ('Administrador', 'admin@studiobrunanayara.com', '$2y$10$9fYYf93S5Q1wG8iM8w2fTOxMpk8J0M6lJzi10BGSAdoo6gWQBaLKm', 'admin')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Categorias padrão
INSERT IGNORE INTO categorias_servicos (id, nome, descricao, ativo) VALUES
(1, 'Cílios', 'Serviços de extensão e manutenção de cílios', 1),
(2, 'Limpeza de Pele', 'Serviços faciais de limpeza e cuidados', 1);

-- Serviços iniciais
INSERT IGNORE INTO servicos (id, categoria_id, nome, descricao, valor_total, percentual_entrada, duracao_minutos, ativo) VALUES
(1, 1, 'Alongamento Fio a Fio', 'Aplicação clássica fio a fio', 220.00, 20.00, 120, 1),
(2, 1, 'Volume Brasileiro', 'Extensão com efeito natural volumoso', 260.00, 20.00, 140, 1),
(3, 2, 'Limpeza de Pele Profunda', 'Procedimento completo facial', 180.00, 20.00, 90, 1);
