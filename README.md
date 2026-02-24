# CATA TRECO - Sistema de Coleta de Resíduos Volumosos

Sistema web em PHP 8+ (MVC) para gestão de solicitações de Cata Treco da prefeitura, com painel administrativo, painel de funcionário, mapa Leaflet/OSM, integração WhatsApp e trilha de auditoria LGPD.

## Funcionalidades

- Formulário público com:
  - nome, endereço, CEP, WhatsApp
  - upload de foto
  - seleção de data/hora
  - consentimento LGPD
  - geocodificação por endereço/CEP (Nominatim)
  - mapa com marcador arrastável (Leaflet)
- API interna REST JSON:
  - `POST /public/api/solicitacoes`
- Login por sessão com perfis:
  - `ADMINISTRADOR`
  - `FUNCIONARIO`
- Dashboard administrativo:
  - cards de status
  - filtros por data, status e localidade
  - aprovação/recusa
  - alteração de data/hora
  - atribuição de funcionário (muda para `EM_ANDAMENTO`)
- Painel do funcionário:
  - coletas atribuídas
  - botão ligar / WhatsApp / como chegar
  - iniciar e finalizar coleta
- WhatsApp:
  - pronto para Cloud API (Meta)
  - fallback automático para `wa.me`
- LGPD e auditoria:
  - consentimento explícito
  - registro de IP
  - histórico de status
  - logs de ações administrativas

## Arquitetura

- Backend: PHP 8+, MVC simples, PDO, sessões, CSRF, OOP.
- Frontend: Bootstrap 5, JavaScript ES6, Fetch API, layout responsivo.
- Banco: MySQL com histórico e auditoria.

## Banco de dados (HostGator)

Credenciais configuradas:

- Banco: `santo821_treco`
- Usuário: `santo821_catatreco`
- Senha: `php@3903`

Arquivos:

- `database/schema.sql` (estrutura completa)
- `config/db.php` (credenciais)

## Instalação

1. Suba os arquivos para `/catatreco`.
2. Aponte o domínio para `catatreco/public` **ou** mantenha a estrutura com `/public` no path.
3. Importe `database/schema.sql` no phpMyAdmin.
4. Ajuste `.env` com base em `.env.example` (opcional).
5. Garanta permissão de escrita em `public/assets/uploads`.
6. Acesse:
   - Público: `https://www.prefsade.com.br/catatreco/public/`
   - Admin: `https://www.prefsade.com.br/catatreco/public/admin/login`

## Usuários iniciais

- Admin: `admin` / `admin123`
- Funcionário: `funcionario1` / `funcionario123`

> Altere as senhas após a implantação.

## Observações de produção

- Ative HTTPS obrigatório.
- Restrinja upload com WAF/ModSecurity quando disponível.
- Configure token oficial do WhatsApp para envio automatizado em produção.
- Para LGPD avançada, implementar rotina de anonimização em lote (estrutura já preparada via logs e histórico).
