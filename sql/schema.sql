-- ===============================================================
-- Studio Bruna Nayara - Banco inicial (MySQL 8+ / MariaDB 10.6+)
-- ===============================================================
-- Este script cria a base de dados inicial para:
-- clientes, serviços, agenda, agendamentos, pagamentos, notificações
-- e logs de sistema, com integridade referencial e índices.

SET NAMES utf8mb4;
SET time_zone = '-03:00';

-- ---------------------------------------------------------------
-- TABELA: usuarios
-- Guarda usuários internos do sistema (admin/atendente).
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'atendente') NOT NULL DEFAULT 'admin',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_login_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- TABELA: clientes
-- Cadastro de clientes do Studio.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(160) NOT NULL,
    telefone VARCHAR(30) NOT NULL,
    email VARCHAR(160) NULL,
    data_nascimento DATE NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_clientes_nome (nome),
    KEY idx_clientes_telefone (telefone),
    KEY idx_clientes_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- TABELA: categorias_servicos
-- Organiza os serviços por categoria (ex.: cílios, limpeza de pele).
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias_servicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    descricao VARCHAR(255) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categorias_nome (nome),
    KEY idx_categorias_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- TABELA: servicos
-- Serviços ofertados com valor, duração e percentual de entrada.
-- Campos obrigatórios solicitados:
-- id, categoria_id, nome, descricao, duracao_minutos, valor,
-- percentual_entrada, ativo.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS servicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT UNSIGNED NOT NULL,
    nome VARCHAR(160) NOT NULL,
    descricao TEXT NULL,
    duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    valor DECIMAL(10,2) NOT NULL,
    percentual_entrada DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_servicos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias_servicos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    KEY idx_servicos_categoria (categoria_id),
    KEY idx_servicos_ativo (ativo),
    KEY idx_servicos_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- TABELA: configuracoes_agenda
-- Define janelas de atendimento por dia da semana.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracoes_agenda (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dia_semana TINYINT UNSIGNED NOT NULL COMMENT '0=domingo ... 6=sábado',
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    intervalo_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_agenda_dia_semana (dia_semana),
    KEY idx_agenda_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- TABELA: bloqueios_agenda
-- Registra períodos indisponíveis na agenda.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bloqueios_agenda (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data_bloqueio DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    motivo VARCHAR(255) NULL,
    criado_por_usuario_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bloqueio_usuario
        FOREIGN KEY (criado_por_usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    KEY idx_bloqueio_data_hora (data_bloqueio, hora_inicio, hora_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- TABELA: agendamentos
-- Agenda de atendimentos.
-- Campos obrigatórios solicitados:
-- cliente_id, servico_id, data_agendamento, hora_inicio, hora_fim,
-- status, valor_total, valor_entrada, valor_restante.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS agendamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    servico_id INT UNSIGNED NOT NULL,
    usuario_responsavel_id INT UNSIGNED NULL,
    data_agendamento DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    status ENUM('pre_reservado','aguardando_pagamento','confirmado','cancelado','realizado','faltou') NOT NULL DEFAULT 'pre_reservado',
    valor_total DECIMAL(10,2) NOT NULL,
    valor_entrada DECIMAL(10,2) NOT NULL,
    valor_restante DECIMAL(10,2) NOT NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_agendamento_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_agendamento_servico
        FOREIGN KEY (servico_id) REFERENCES servicos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_agendamento_usuario
        FOREIGN KEY (usuario_responsavel_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    KEY idx_agendamentos_data_hora (data_agendamento, hora_inicio),
    KEY idx_agendamentos_status (status),
    KEY idx_agendamentos_cliente (cliente_id),
    KEY idx_agendamentos_servico (servico_id),
    KEY idx_agendamentos_usuario (usuario_responsavel_id),
    UNIQUE KEY uq_agendamento_slot (data_agendamento, hora_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- TABELA: pagamentos
-- Controle de pagamento da entrada e saldo do atendimento.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pagamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agendamento_id INT UNSIGNED NOT NULL,
    tipo ENUM('entrada','saldo') NOT NULL DEFAULT 'entrada',
    valor DECIMAL(10,2) NOT NULL,
    metodo VARCHAR(50) NULL COMMENT 'pix,cartao,dinheiro,etc',
    status ENUM('pendente','aguardando_confirmacao','pago','cancelado','expirado','estornado') NOT NULL DEFAULT 'pendente',
    transacao_externa_id VARCHAR(100) NULL,
    pago_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pagamento_agendamento
        FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    KEY idx_pagamentos_agendamento (agendamento_id),
    KEY idx_pagamentos_status (status),
    KEY idx_pagamentos_tipo (tipo),
    KEY idx_pagamentos_transacao (transacao_externa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- TABELA: notificacoes
-- Histórico de notificações para cliente e proprietária.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agendamento_id INT UNSIGNED NULL,
    cliente_id INT UNSIGNED NULL,
    tipo ENUM('lembrete_cliente','resumo_proprietaria','sistema') NOT NULL,
    canal ENUM('email','whatsapp','sms','interno') NOT NULL DEFAULT 'interno',
    destinatario VARCHAR(160) NULL,
    mensagem TEXT NOT NULL,
    status ENUM('pendente','enviada','erro') NOT NULL DEFAULT 'pendente',
    enviado_em DATETIME NULL,
    erro_detalhes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificacao_agendamento
        FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_notificacao_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    KEY idx_notificacoes_status (status),
    KEY idx_notificacoes_tipo (tipo),
    KEY idx_notificacoes_agendamento (agendamento_id),
    KEY idx_notificacoes_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- TABELA: logs_sistema
-- Auditoria técnica das operações do sistema.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS logs_sistema (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nivel ENUM('info','warning','error') NOT NULL DEFAULT 'info',
    origem VARCHAR(120) NOT NULL,
    mensagem TEXT NOT NULL,
    contexto LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_logs_nivel (nivel),
    KEY idx_logs_origem (origem),
    KEY idx_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================================
-- DADOS INICIAIS DE EXEMPLO
-- ===============================================================

-- Usuário administrador padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha, perfil, ativo)
VALUES ('Administrador', 'admin@studiobrunanayara.com', '$2y$10$9fYYf93S5Q1wG8iM8w2fTOxMpk8J0M6lJzi10BGSAdoo6gWQBaLKm', 'admin', 1)
ON DUPLICATE KEY UPDATE nome = VALUES(nome), ativo = VALUES(ativo);

-- Categorias iniciais
INSERT IGNORE INTO categorias_servicos (id, nome, descricao, ativo)
VALUES
(1, 'Cílios', 'Serviços de extensão e manutenção de cílios', 1),
(2, 'Limpeza de Pele', 'Procedimentos de limpeza e tratamento facial', 1);

-- Serviços iniciais
INSERT IGNORE INTO servicos (id, categoria_id, nome, descricao, duracao_minutos, valor, percentual_entrada, ativo)
VALUES
(1, 1, 'Alongamento Fio a Fio', 'Aplicação clássica fio a fio', 120, 220.00, 20.00, 1),
(2, 1, 'Volume Brasileiro', 'Extensão com efeito natural volumoso', 140, 260.00, 20.00, 1),
(3, 2, 'Limpeza de Pele Profunda', 'Procedimento completo facial', 90, 180.00, 20.00, 1);

-- Clientes exemplo
INSERT IGNORE INTO clientes (id, nome, telefone, email, data_nascimento, observacoes)
VALUES
(1, 'Ana Paula Souza', '(11) 99999-1111', 'ana.souza@email.com', '1993-04-10', 'Prefere horário da manhã.'),
(2, 'Beatriz Lima', '(11) 98888-2222', 'beatriz.lima@email.com', '1990-09-21', 'Alergia leve a ácido forte.');

-- Configuração de agenda padrão (segunda a sábado)
INSERT IGNORE INTO configuracoes_agenda (id, dia_semana, hora_inicio, hora_fim, intervalo_minutos, ativo)
VALUES
(1, 1, '08:00:00', '18:00:00', 30, 1),
(2, 2, '08:00:00', '18:00:00', 30, 1),
(3, 3, '08:00:00', '18:00:00', 30, 1),
(4, 4, '08:00:00', '18:00:00', 30, 1),
(5, 5, '08:00:00', '18:00:00', 30, 1),
(6, 6, '08:00:00', '13:00:00', 30, 1);

-- Agendamento exemplo (entrada de 20%)
INSERT IGNORE INTO agendamentos (
    id, cliente_id, servico_id, usuario_responsavel_id, data_agendamento,
    hora_inicio, hora_fim, status, valor_total, valor_entrada, valor_restante, observacoes
) VALUES (
    1, 1, 1, 1, CURDATE(),
    '09:00:00', '11:00:00', 'aguardando_pagamento', 220.00, 44.00, 176.00,
    'Pré-reserva criada pelo administrativo.'
);

-- Pagamento exemplo (entrada pendente)
INSERT IGNORE INTO pagamentos (id, agendamento_id, tipo, valor, metodo, status)
VALUES (1, 1, 'entrada', 44.00, 'pix', 'pendente');

-- Notificação exemplo
INSERT IGNORE INTO notificacoes (id, agendamento_id, cliente_id, tipo, canal, destinatario, mensagem, status)
VALUES (1, 1, 1, 'lembrete_cliente', 'whatsapp', '(11) 99999-1111', 'Lembrete: seu atendimento é hoje às 09:00.', 'pendente');

-- Log exemplo
INSERT IGNORE INTO logs_sistema (id, nivel, origem, mensagem, contexto)
VALUES (1, 'info', 'seed', 'Base inicial criada com sucesso', '{"versao":"1.0.0"}');
