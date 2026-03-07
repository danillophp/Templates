# Auditoria de Segurança e Qualidade — Studio Bruna Nayara

## Escopo
- Arquitetura MVC PHP 8 + MySQL
- Rotas administrativas e públicas em `/agenda`
- Upload de logo
- Configuração HostGator/Apache

## Vulnerabilidades encontradas e corrigidas

### 1) Credencial hardcoded em fallback de banco (Risco: Alto)
- **Problema**: senha de banco definida em código-fonte.
- **Correção**: fallback de senha removido para vazio, priorizando `getenv('DB_PASSWORD')`.

### 2) Falta de headers de segurança HTTP (Risco: Alto)
- **Problema**: ausência de CSP, anti-clickjacking e anti-MIME sniffing.
- **Correção**: inclusão de headers em `public/index.php` e reforço no `public/.htaccess`.

### 3) Upload com validações incompletas (Risco: Alto)
- **Problema**: upload aceitava validação parcial.
- **Correção**: validação de `is_uploaded_file`, tamanho, extensão, MIME e `getimagesize`; renomeação segura; permissões de arquivo.

### 4) Hardening insuficiente da pasta de upload (Risco: Alto)
- **Problema**: risco de execução de scripts e listagem.
- **Correção**: `.htaccess` em `public/uploads/logo` bloqueando execução e listagem.

### 5) Sessão com hardening parcial (Risco: Médio)
- **Problema**: timeout de inatividade ausente e strict mode não forçado.
- **Correção**: `session.use_strict_mode=1`, timeout por atividade e renovação de estado.

### 6) Exposição de diretórios sensíveis em cenários de configuração incorreta (Risco: Médio)
- **Problema**: diretórios internos sem bloqueio explícito.
- **Correção**: `.htaccess` de negação em `app/`, `storage/`, `database/`, `sql/`.

### 7) Tratamento de método HTTP no roteador (Risco: Baixo)
- **Problema**: métodos não mapeados retornavam fluxo genérico.
- **Correção**: resposta explícita `405 Method Not Allowed`.

## Itens verificados sem necessidade de correção estrutural
- PDO centralizado com `ERRMODE_EXCEPTION` e `ATTR_EMULATE_PREPARES=false`.
- Uso amplo de prepared statements nos modelos.
- `password_verify` no login e `session_regenerate_id(true)` em autenticação.
- Presença de CSRF token e validação em formulários críticos.
- Escapamento de saída com helper `e()` nas views principais.

## Recomendações adicionais
1. Adotar rotação de logs e retenção em `storage/logs`.
2. Implementar rate limit de login (IP + janela de tempo).
3. Adicionar testes automatizados de segurança (CSRF, upload e auth).
4. Em produção, habilitar HTTPS forçado no Apache/HostGator.
5. Revisar periodicamente a CSP caso novos scripts externos sejam adicionados.

## Checklist de produção segura
- [x] Sem credencial sensível hardcoded
- [x] Headers de segurança ativos
- [x] Upload endurecido
- [x] CSRF aplicado em operações de escrita
- [x] Sessão com timeout de inatividade
- [x] Diretórios sensíveis protegidos
- [x] Compatibilidade com `/agenda`
