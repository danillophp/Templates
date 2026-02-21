INSERT INTO usuarios (nome, usuario, email, senha_hash, role, ativo)
VALUES ('Administrador', 'admin', 'admin@prefsade.com.br', '$2y$10$ylkT9r5vEhZViYrw0lByauAPUNf4jvVWu1Gv6L2k9VG66sxq9dYny', 'admin', 1);

INSERT INTO pontos_coleta (nome, descricao, latitude, longitude, ativo)
VALUES ('Ponto Central SAD', 'Ponto exemplo para testes', -15.9447123, -48.2573012, 1);
