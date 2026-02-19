# Cata Treco

Sistema web em PHP + MySQL para solicitações de coleta de trecos, com painel administrativo, painel do funcionário, mapa com geolocalização e trilha de auditoria (LGPD).

## Estrutura de pastas

- `config/` configurações (`app.php`, `db.php`)
- `includes/` funções utilitárias e autenticação
- `public/` páginas públicas, administrativas e do funcionário
- `assets/` CSS e JavaScript
- `uploads/` fotos enviadas pelos cidadãos
- `sql/` script SQL completo

## Requisitos

- PHP 8.0+
- Extensões PHP: `mysqli`, `fileinfo`, `session`
- MySQL 5.7+ ou MariaDB compatível

## Instalação na HostGator

1. Faça upload dos arquivos para `www.prefsade.com.br/catatreco`.
2. No cPanel, crie o banco e usuário com os dados:
   - Banco: `santo821_treco`
   - Usuário: `catatreco`
   - Senha: `php@3903`
3. Importe o SQL em `sql/catatreco.sql` via phpMyAdmin.
4. Confirme as credenciais em `config/db.php`.
5. Garanta permissão de escrita para `uploads/` (ex.: `775`).
6. Acesse:
   - Formulário público: `https://www.prefsade.com.br/catatreco/public/index.php`
   - Login admin/funcionário: `https://www.prefsade.com.br/catatreco/public/admin/login.php`

## Credenciais iniciais

- Admin: `admin` / `Admin@123`
- Funcionário: `funcionario1` / `Func@123`

> Altere as senhas imediatamente em produção.

## LGPD e auditoria

- Consentimento obrigatório no formulário público.
- Registro de IP na solicitação e em logs de ações.
- Tabela `logs` para trilha de auditoria.
- Estrutura permite anonimização futura dos campos pessoais na tabela `requests`.

## WhatsApp

As ações administrativas geram link `wa.me` com mensagem pronta para notificação ao cidadão. A estrutura pode ser substituída por API oficial do WhatsApp sem alterar o fluxo do sistema.
