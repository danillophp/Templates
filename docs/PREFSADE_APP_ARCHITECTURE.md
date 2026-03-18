# PrefSADE — Arquitetura do Aplicativo Unificado

## 1. Visão geral executiva

O ecossistema **PrefSADE** deve evoluir de um portal agregador de sistemas internos para um **super app governamental modular**, com experiência unificada para cidadão, servidor e administrador. Hoje, o portal em `https://www.prefsade.com.br/` atua como uma vitrine de acessos rápidos para sistemas distintos, incluindo **Cata Treco**, **Sis-Emendas**, **Pesquisa de Satisfação**, **Chamado de TI** e **Meu Sonho Meu Lar**. A proposta ideal não é reconstruir tudo do zero, mas criar uma **camada de experiência mobile, identidade, autenticação, navegação, telemetria e integrações nativas** sobre os sistemas já existentes.

A estratégia recomendada é:

1. **Padronizar o frontend web para PWA responsivo**.
2. **Criar um app contêiner oficial PrefSADE**, com shell institucional, navegação centralizada e módulos desacoplados.
3. **No Android**, distribuir prioritariamente via **PWA + Trusted Web Activity (TWA)**.
4. **No iOS**, distribuir via **shell híbrido com Capacitor** (preferência) ou Flutter, adicionando recursos nativos e UX própria.
5. **Padronizar autenticação, permissões, observabilidade, notificações e histórico de protocolos** em uma camada comum.
6. **Evoluir o portal para uma arquitetura multi-módulo**, preparada para novos sistemas futuros sem retrabalho estrutural.

---

## 2. Etapa 1 — Diagnóstico técnico

### 2.1 Leitura do cenário atual

Com base no portal público atual, o site funciona como um hub simples de links para sistemas independentes. Isso indica um estágio inicial funcional, porém com limitações para experiência mobile, governança técnica e distribuição em lojas.

### 2.2 Estrutura ideal para transformar o portal atual em app

A estrutura ideal é composta por **três camadas**:

#### Camada A — Experiência unificada (App Shell)
Responsável por:
- splash institucional;
- home com cards dos módulos;
- busca global;
- atalhos rápidos;
- central de notificações;
- perfil do usuário;
- histórico de protocolos;
- configurações;
- política de privacidade e consentimentos.

#### Camada B — Módulos de negócio
Cada sistema atual vira um **módulo funcional** dentro do app:
- `catatreco`;
- `lampadas`;
- `chamado-ti`;
- `emendas`;
- `pesquisa-satisfacao`;
- `meu-sonho-meu-lar`;
- `modulos-futuros`.

Cada módulo deve expor:
- rota inicial;
- metadados do módulo;
- permissões necessárias;
- recursos nativos usados;
- indicadores de status;
- integração com autenticação compartilhada.

#### Camada C — Plataforma compartilhada
Recursos transversais:
- autenticação SSO ou sessão federada;
- API gateway / BFF;
- catálogo de módulos;
- gerenciamento de perfil e permissões;
- push notifications;
- analytics e logs;
- upload centralizado;
- storage local e sincronização;
- auditoria.

### 2.3 Ajustes necessários no site para virar PWA

Para o portal atual evoluir para PWA real, ele precisa obrigatoriamente de:

1. **HTTPS pleno em todos os submódulos e assets**.
2. **Manifest Web App válido** com nome curto, cor de tema, ícones e modo standalone.
3. **Service worker** registrado na raiz adequada.
4. **Design responsivo mobile-first** em todas as telas críticas.
5. **Meta tags mobile** (`theme-color`, `apple-mobile-web-app-capable`, viewport, etc.).
6. **Ícones em múltiplas resoluções**.
7. **Tela offline/fallback**.
8. **Controle de cache de assets e chamadas API**.
9. **Critérios mínimos de instalabilidade**.
10. **Performance aceitável em 4G e dispositivos modestos**.

### 2.4 Requisitos obrigatórios para Android

#### Para instalação como PWA/TWA
- domínio com HTTPS válido;
- PWA instalável e consistente;
- `manifest.json` correto;
- service worker ativo;
- `display: standalone` ou `fullscreen`;
- ícones 192x192 e 512x512;
- Digital Asset Links para associação entre domínio e app Android;
- app Android empacotado via TWA com package próprio;
- splash screen nativa e branding consistentes;
- estratégia de fallback caso o navegador não suporte os requisitos esperados.

#### Para qualidade de loja
- política de privacidade acessível;
- tratamento de permissões em tempo de uso;
- estabilidade e carregamento confiável;
- conteúdo institucional e descrição clara;
- evidência de utilidade pública real.

### 2.5 Requisitos obrigatórios para iOS

Como o iOS não oferece o mesmo modelo de TWA do Android, é necessário um shell híbrido/nativo.

#### Requisitos mínimos recomendados
- app shell em **Capacitor** com `WKWebView`;
- navegação nativa mínima;
- bridge para câmera, push, geolocalização, compartilhamento e armazenamento local;
- telas de loading, falha de rede e permissões com UX nativa;
- identidade visual própria de aplicativo;
- tratamento de deep links;
- política de privacidade, termos e fluxo de consentimento;
- conformidade com as diretrizes da App Store, evitando aparência de simples empacotamento de site sem valor agregado.

### 2.6 Riscos técnicos

#### Técnicos
- sistemas atuais podem ter stacks diferentes, autenticações distintas e padrões inconsistentes;
- módulos podem depender de sessões PHP não compartilhadas entre subdomínios;
- ausência de APIs formais pode levar a acoplamento excessivo ao HTML;
- service worker mal configurado pode causar cache inconsistente ou conteúdo desatualizado;
- integração de upload, geolocalização e câmera pode variar entre navegadores e shells.

#### UX
- se cada módulo mantiver layout diferente, o app parecerá fragmentado;
- login repetido por sistema destrói a experiência;
- formulários longos sem salvamento local reduzem conversão;
- falta de feedback de protocolo e status gera baixa confiança.

#### Publicação
- App Store pode rejeitar app que pareça apenas um site empacotado;
- Play Store pode exigir mais estabilidade, política clara e associação de domínio consistente;
- permissões pedidas sem contexto podem prejudicar aprovação;
- inconsistências de acessibilidade e privacidade elevam risco institucional.

---

## 3. Etapa 2 — Arquitetura do projeto

### 3.1 Arquitetura ideal

A recomendação é adotar uma arquitetura **modular, orientada a domínio e com backend federado**.

### 3.2 Modelo arquitetural recomendado

```text
[ Android TWA ]      [ iOS Capacitor Shell ]      [ Web PWA ]
         \                  |                     /
                 [ App Shell / BFF / API Gateway ]
                           |
      -------------------------------------------------------
      |            |              |            |             |
 [Cata Treco] [Lâmpadas] [Chamado TI] [Emendas] [Meu Lar] ...
      |            |              |            |             |
   [APIs/DB]    [APIs/DB]      [APIs/DB]    [APIs/DB]    [APIs/DB]
                           |
                [Auth / Logs / Push / Files / Analytics]
```

### 3.3 Princípios arquiteturais

- **App único, módulos múltiplos**;
- **Frontend desacoplado de backend legado** quando possível;
- **BFF/API Gateway** para unificar autenticação, menu, permissões e protocolos;
- **design system institucional** compartilhado;
- **feature flags** para ativar/desativar módulos;
- **telemetria e auditoria centralizadas**;
- **módulos independentes**, mas com experiência unificada.

### 3.4 Estrutura em módulos

#### Núcleo
- autenticação;
- perfil do usuário;
- catálogo de módulos;
- notificações;
- histórico de protocolos;
- configurações;
- ajuda/suporte;
- aceite LGPD.

#### Módulos prioritários

1. **Cata Treco**
   - abertura de solicitação;
   - geolocalização;
   - anexos/fotos;
   - status do atendimento;
   - histórico de coletas.

2. **Solicitação de Lâmpadas**
   - abertura de pedido com endereço;
   - georreferenciamento do ponto;
   - foto do poste/local;
   - acompanhamento do protocolo;
   - atualização do status pela equipe.

3. **Chamado de TI**
   - abertura de ticket;
   - categorização por secretaria/unidade;
   - prioridade;
   - evidência por imagem;
   - acompanhamento e encerramento.

4. **Emendas**
   - consulta de emendas;
   - filtros por ano, autor, secretaria e status;
   - detalhamento;
   - exportações futuras.

5. **Pesquisa de Satisfação**
   - campanhas por serviço;
   - formulários rápidos;
   - métricas agregadas;
   - push pós-atendimento.

6. **Meu Sonho Meu Lar**
   - cadastro/inscrição;
   - upload documental;
   - checklist de elegibilidade;
   - consulta de andamento.

### 3.5 Árvore de navegação

```text
Splash
└── Onboarding institucional (opcional na 1ª execução)
    └── Login / Acesso rápido
        └── Home
            ├── Busca global
            ├── Atalhos rápidos
            ├── Módulos em destaque
            ├── Notificações
            ├── Protocolos
            ├── Perfil
            ├── Configurações
            └── Lista completa de módulos
                ├── Cata Treco
                │   ├── Nova solicitação
                │   ├── Minhas solicitações
                │   └── Detalhe do protocolo
                ├── Solicitação de Lâmpadas
                ├── Chamado de TI
                ├── Emendas
                ├── Pesquisa de Satisfação
                └── Meu Sonho Meu Lar
```

### 3.6 Perfis de usuário

#### Cidadão
- acessa serviços públicos externos;
- abre solicitações;
- consulta protocolos;
- recebe notificações;
- edita dados cadastrais básicos.

#### Servidor
- acessa serviços internos autorizados;
- abre e acompanha chamados internos;
- visualiza filas operacionais;
- atualiza status conforme setor.

#### Administrador
- gerencia módulos;
- controla usuários e permissões;
- visualiza dashboards;
- configura notificações;
- exporta relatórios;
- audita ações críticas.

### 3.7 Reaproveitamento do backend atual

A regra deve ser **reaproveitar o máximo sem perpetuar limitações estruturais**.

#### Estratégia recomendada
- manter os sistemas atuais em operação;
- encapsular funcionalidades críticas via APIs progressivas;
- usar o portal atual como base de transição;
- migrar gradualmente telas web para componentes responsivos padronizados;
- centralizar login, menu, protocolos e notificações no núcleo do app.

#### Ordem de reaproveitamento
1. **Banco e regras de negócio atuais**.
2. **Endpoints existentes**, se seguros e estruturados.
3. **Telas web responsivas atuais**, quando já forem suficientemente boas.
4. **Reescrita seletiva** somente das partes com maior impacto em UX, segurança ou manutenção.

### 3.8 Solução escalável

Para novos módulos futuros, cada novo sistema deve seguir um contrato mínimo:

```json
{
  "id": "lampadas",
  "name": "Solicitação de Lâmpadas",
  "icon": "lightbulb",
  "entryRoute": "/modules/lampadas",
  "roles": ["CIDADAO", "SERVIDOR"],
  "features": ["camera", "geolocation", "push"],
  "offlineMode": "read-only",
  "status": "active"
}
```

Isso permite catálogo dinâmico, ativação por perfil e evolução sem refazer a base do app.

---

## 4. Etapa 3 — UX/UI mobile

### 4.1 Direção de experiência

A experiência mobile deve seguir cinco princípios:
- **um serviço por vez, com clareza**;
- **poucos toques para concluir**;
- **linguagem institucional simples**;
- **feedback permanente de status**;
- **acessibilidade e legibilidade acima de efeitos visuais**.

### 4.2 Estrutura das telas principais

#### Splash
Objetivo:
- reforçar marca institucional;
- validar sessão;
- pré-carregar catálogo de módulos;
- decidir se abre home, onboarding ou login.

Conteúdo:
- brasão/logotipo;
- nome `PrefSADE`;
- subtítulo `Prefeitura de Santo Antônio do Descoberto`;
- indicador discreto de carregamento.

#### Home
Componentes:
- cabeçalho com saudação e perfil;
- campo de busca (`Qual serviço você precisa?`);
- banner institucional rotativo com avisos;
- grade de módulos em cards;
- atalhos rápidos (`Novo protocolo`, `Meus protocolos`, `Notificações`, `Ajuda`);
- seção de protocolos recentes;
- rodapé com navegação principal.

#### Lista de módulos
- filtro por categoria;
- busca;
- ordenação por mais usados;
- cards com ícone, descrição curta e selo de disponibilidade.

#### Tela de cada sistema
Padrão recomendado:
- cabeçalho contextual;
- resumo do serviço;
- CTA principal;
- histórico do usuário naquele módulo;
- FAQ curta;
- canal de ajuda.

#### Histórico de protocolos
- lista com protocolo, módulo, data, status e última atualização;
- filtros por período, módulo e status;
- timeline no detalhe;
- botão de compartilhar protocolo;
- botão de repetir solicitação quando aplicável.

#### Perfil / configurações
- dados pessoais;
- documentos e validações cadastrais;
- notificações;
- privacidade/LGPD;
- idioma/acessibilidade;
- sair da conta.

### 4.3 Layout visual recomendado

#### Identidade
- cores institucionais derivadas da identidade da prefeitura;
- branco predominante com azul/verde institucional para confiança;
- contrastes altos e tipografia sem serifa.

#### Componentes-chave
- **cards** com cantos moderadamente arredondados;
- ícones claros e consistentes;
- botões primários sólidos;
- status com chips (`Pendente`, `Em andamento`, `Concluído`);
- mapas e anexos embutidos quando necessários.

#### Navegação
- bottom navigation com 4 a 5 áreas:
  - Home;
  - Módulos;
  - Protocolos;
  - Notificações;
  - Perfil.

### 4.4 Recomendações de UX específicas por módulo

#### Cata Treco
- formulário em passos curtos;
- captura de foto com pré-visualização;
- localização automática + edição manual;
- confirmação com número de protocolo.

#### Solicitação de Lâmpadas
- botão rápido `Usar minha localização`;
- mapa do ponto com marcador ajustável;
- checklist simples do problema;
- histórico com status operacional.

#### Chamado de TI
- atalho para secretaria/setor;
- categorização guiada;
- anexos de erro/print;
- SLA visível.

---

## 5. Etapa 4 — PWA

### 5.1 Especificação de `manifest.json`

Exemplo recomendado:

```json
{
  "name": "PrefSADE - Prefeitura de Santo Antônio do Descoberto",
  "short_name": "PrefSADE",
  "description": "Aplicativo oficial com serviços digitais da Prefeitura de Santo Antônio do Descoberto.",
  "start_url": "/app/",
  "scope": "/",
  "display": "standalone",
  "orientation": "portrait-primary",
  "background_color": "#FFFFFF",
  "theme_color": "#0B5ED7",
  "lang": "pt-BR",
  "dir": "ltr",
  "categories": ["government", "utilities", "productivity"],
  "icons": [
    {
      "src": "/assets/icons/icon-192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any maskable"
    },
    {
      "src": "/assets/icons/icon-512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any maskable"
    }
  ]
}
```

### 5.2 Estratégia de service worker

#### Caches recomendados
- `app-shell-v1`: HTML base, CSS, JS principal, fontes locais, ícones;
- `static-assets-v1`: imagens institucionais e assets estáticos;
- `api-read-v1`: respostas GET com TTL curto para consulta;
- `offline-fallback-v1`: página offline.

#### Estratégia por tipo
- **App shell**: cache-first;
- **APIs de leitura**: stale-while-revalidate;
- **APIs de escrita**: network-only com fila futura se houver offline queue;
- **imagens**: cache-first com limite de expiração.

### 5.3 Exemplo de service worker base

```javascript
const APP_CACHE = 'prefsade-app-shell-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(APP_CACHE).then(cache =>
      cache.addAll([
        '/',
        '/app/',
        '/assets/css/app.css',
        '/assets/js/app.js',
        '/manifest.json',
        OFFLINE_URL
      ])
    )
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then(response => {
        const copy = response.clone();
        caches.open(APP_CACHE).then(cache => cache.put(event.request, copy));
        return response;
      })
      .catch(() => caches.match(event.request).then(resp => resp || caches.match(OFFLINE_URL)))
  );
});
```

### 5.4 Offline fallback

A página offline deve conter:
- marca institucional;
- mensagem clara de indisponibilidade momentânea;
- botão `Tentar novamente`;
- acesso a protocolos já sincronizados localmente, se disponível;
- telefone/e-mail institucional para contingência.

### 5.5 Ícones e splash

#### Ícones obrigatórios
- 72x72;
- 96x96;
- 128x128;
- 144x144;
- 152x152;
- 192x192;
- 384x384;
- 512x512.

#### Splash
- fundo sólido institucional;
- logotipo centralizado;
- nome curto;
- sem excesso de texto.

### 5.6 Meta tags mobile essenciais

```html
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0B5ED7">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="PrefSADE">
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
```

### 5.7 Garantia de instalação no Android

Checklist mínimo:
- manifest correto;
- service worker funcional;
- HTTPS;
- ícones válidos;
- navegação consistente;
- sem intersticiais bloqueando uso;
- Core Web Vitals aceitáveis;
- Digital Asset Links configurado para TWA.

---

## 6. Etapa 5 — Recursos nativos

### 6.1 Recursos prioritários

#### Câmera
Uso principal:
- foto do resíduo no Cata Treco;
- foto do poste/lâmpada;
- print/erro no Chamado de TI;
- captura documental no Meu Sonho Meu Lar.

#### Geolocalização
Uso principal:
- confirmar endereço da coleta;
- marcar ponto exato da lâmpada;
- facilitar despacho operacional.

#### Upload de imagem
- compressão client-side;
- pré-visualização;
- retry em rede instável;
- validação de extensão/tamanho;
- remoção de metadados sensíveis quando aplicável.

#### Push notification
- protocolo criado;
- status alterado;
- pendência documental;
- lembrete de pesquisa de satisfação;
- mensagens segmentadas por módulo/perfil.

#### Armazenamento local
- cache de módulos;
- preferências do usuário;
- protocolos recentes;
- rascunhos de formulários;
- tokens e sessão de forma segura.

#### Compartilhamento de protocolo
- compartilhar número do protocolo via apps do sistema;
- exportar resumo em texto ou PDF futuramente;
- deep link para consulta dentro do app.

### 6.2 Prioridade por módulo

#### Cata Treco
Alta prioridade para:
- câmera;
- geolocalização;
- upload;
- push;
- protocolo compartilhável.

#### Solicitação de Lâmpadas
Alta prioridade para:
- geolocalização;
- câmera;
- upload;
- push.

#### Chamado de TI
Alta prioridade para:
- upload de imagem;
- notificações de atualização;
- armazenamento local de rascunho;
- compartilhamento do chamado.

### 6.3 Implementação recomendada

#### Android TWA
- usar web APIs padrão primeiro (`getUserMedia`, geolocation, Web Share, notifications);
- para limites específicos, complementar com Android wrapper apenas quando necessário.

#### iOS Capacitor
Plugins recomendados:
- `@capacitor/camera`;
- `@capacitor/geolocation`;
- `@capacitor/push-notifications`;
- `@capacitor/preferences`;
- `@capacitor/share`;
- `@capacitor/filesystem`.

---

## 7. Etapa 6 — Empacotamento

### 7.1 Estratégia Android via Trusted Web Activity

#### Recomendação
Usar TWA para publicar o app Android mantendo o PWA como fonte principal de interface.

#### Vantagens
- menor retrabalho;
- atualização web rápida;
- melhor aderência ao reaproveitamento do portal atual;
- experiência próxima de app quando o PWA estiver bem feito.

#### Itens de implementação
- package `br.gov.sade.prefsade`;
- assinatura do app;
- `assetlinks.json` no domínio;
- ícones e splash Android;
- fallback webview opcional para contingência controlada;
- monitoramento de crashes e cold start.

### 7.2 Estratégia iOS via Capacitor

#### Recomendação preferencial
Capacitor é mais adequado que Flutter neste cenário porque:
- reaproveita mais diretamente o web app existente;
- reduz tempo de transição;
- facilita bridge nativa incremental;
- evita duplicação de frontend em curto prazo.

#### Quando Flutter faria sentido
Somente se a prefeitura decidir, no médio prazo, reconstruir a experiência de ponta a ponta com UI totalmente nativa/multiplataforma e equipe dedicada.

### 7.3 Estrutura de pastas ideal

```text
prefsade/
├── apps/
│   ├── web-pwa/
│   │   ├── src/
│   │   ├── public/
│   │   ├── manifest.json
│   │   ├── sw.js
│   │   └── offline.html
│   ├── android-twa/
│   │   ├── app/
│   │   └── twa-config/
│   └── ios-shell/
│       ├── capacitor.config.ts
│       ├── ios/
│       └── native-plugins/
├── packages/
│   ├── design-system/
│   ├── shared-types/
│   ├── auth-client/
│   ├── protocol-center/
│   └── analytics/
├── services/
│   ├── bff-gateway/
│   ├── auth-service/
│   ├── notification-service/
│   ├── file-service/
│   └── module-registry/
├── legacy/
│   ├── catatreco/
│   ├── emendas/
│   ├── pesquisa/
│   └── chamados/
├── infra/
│   ├── nginx/
│   ├── ci-cd/
│   └── observability/
└── docs/
```

### 7.4 Base técnica pronta para desenvolvimento

#### Fase 1
- design system;
- PWA shell;
- autenticação central;
- home e catálogo de módulos;
- integração inicial com Cata Treco.

#### Fase 2
- protocolos unificados;
- push notifications;
- módulo Lâmpadas;
- módulo Chamado de TI.

#### Fase 3
- iOS shell refinado;
- analytics e auditoria avançada;
- onboarding e acessibilidade ampliada;
- novos módulos.

---

## 8. Etapa 7 — Segurança e manutenção

### 8.1 Autenticação segura

Modelo recomendado:
- **OIDC/OAuth2** se houver maturidade para identidade central;
- caso contrário, **BFF com sessão segura federada** como etapa de transição;
- login por CPF/e-mail/matrícula conforme perfil;
- MFA opcional/obrigatório para administradores e servidores críticos.

### 8.2 Proteção de sessão

- cookies `HttpOnly`, `Secure`, `SameSite=Lax/Strict`;
- rotação de sessão após login;
- expiração por inatividade;
- revogação central;
- device binding leve para perfis administrativos.

### 8.3 Proteção de rotas e APIs

- RBAC por perfil e escopo;
- autorização por módulo e ação;
- JWT de curta duração apenas quando necessário;
- validação server-side de todas as permissões;
- rate limiting;
- proteção CSRF em contextos baseados em sessão;
- validação e sanitização de upload;
- WAF e cabeçalhos de segurança.

### 8.4 Padrão de permissões por perfil

```text
CIDADAO
- abrir solicitações
- consultar próprios protocolos
- editar perfil

SERVIDOR
- consultar filas do setor
- atualizar chamados atribuídos
- acessar dashboards operacionais

ADMINISTRADOR
- gerenciar usuários
- alterar parâmetros de módulo
- exportar relatórios
- auditar trilhas críticas
```

### 8.5 Logs e auditoria

Registrar pelo menos:
- login/logout;
- criação e alteração de protocolo;
- mudança de status;
- upload e exclusão de arquivos;
- acessos administrativos;
- falhas de autenticação;
- eventos de integração.

### 8.6 Boas práticas de manutenção

- versionamento semântico dos módulos;
- documentação viva por módulo;
- CI/CD com ambientes dev/homologação/produção;
- observabilidade com logs, métricas e tracing;
- backups e testes de restauração;
- esteira de segurança com SAST/DAST quando possível;
- política de descontinuação e migração de módulos legados.

---

## 9. Etapa 8 — Entrega final consolidada

### 9.1 Visão geral do app

**PrefSADE** será o aplicativo oficial unificado da Prefeitura de Santo Antônio do Descoberto para concentrar serviços digitais ao cidadão, fluxos internos de servidores e gestão administrativa de módulos estratégicos.

### 9.2 Estrutura de menus

#### Menu principal
- Home
- Módulos
- Protocolos
- Notificações
- Perfil

#### Menu lateral/opcional
- Sobre o app
- Privacidade e LGPD
- Ajuda
- Fale conosco
- Termos de uso
- Acessibilidade

### 9.3 Mapa de telas

```text
Splash
Login / Acesso identificado / Acesso convidado
Home
Lista de módulos
Detalhe do módulo
Formulário de solicitação
Confirmação de protocolo
Histórico de protocolos
Detalhe de protocolo
Notificações
Perfil
Configurações
Ajuda
Privacidade / Termos
```

### 9.4 Requisitos técnicos consolidados

#### Frontend
- PWA responsivo;
- design system compartilhado;
- suporte a cache e offline controlado;
- acessibilidade;
- analytics.

#### Backend
- BFF/API gateway;
- integração com legados;
- autenticação central;
- logs e auditoria;
- gestão de arquivos e notificações.

#### Mobile packaging
- Android TWA;
- iOS Capacitor shell.

### 9.5 Checklist de publicação

#### Play Store
- [ ] package name final definido;
- [ ] assinatura e keystore seguras;
- [ ] Digital Asset Links publicados;
- [ ] política de privacidade pública;
- [ ] screenshots e descrição institucional;
- [ ] permissões justificadas;
- [ ] testes em múltiplos devices;
- [ ] crash reporting configurado.

#### App Store
- [ ] bundle identifier definido;
- [ ] telas e metadados completos;
- [ ] fluxo claro de login e utilidade do app;
- [ ] valor agregado nativo perceptível;
- [ ] política de privacidade e tratamento de dados;
- [ ] permissões explicadas no `Info.plist`;
- [ ] validação em rede instável;
- [ ] revisão jurídica e institucional.

### 9.6 Checklist de testes

#### Funcionais
- [ ] login por perfil;
- [ ] abertura de protocolo por módulo;
- [ ] upload de imagem;
- [ ] geolocalização;
- [ ] push notification;
- [ ] histórico e consulta;
- [ ] logout e expiração de sessão.

#### Não funcionais
- [ ] Lighthouse PWA;
- [ ] acessibilidade;
- [ ] performance em 4G;
- [ ] segurança OWASP básica;
- [ ] compatibilidade Android/iOS;
- [ ] resiliência offline parcial.

### 9.7 Plano de implantação

#### Fase 0 — Descoberta e padronização
- inventário dos sistemas atuais;
- mapeamento de autenticação, banco e integrações;
- definição visual institucional;
- priorização de módulos.

#### Fase 1 — Fundação técnica
- app shell web;
- design system;
- autenticação central;
- catálogo dinâmico;
- PWA base.

#### Fase 2 — Primeiro release oficial
- Cata Treco integrado;
- Histórico de protocolos;
- push básico;
- Android TWA em homologação.

#### Fase 3 — Expansão
- Lâmpadas;
- Chamado de TI;
- iOS Capacitor;
- relatórios e observabilidade.

#### Fase 4 — Consolidação
- Emendas;
- Pesquisa de Satisfação;
- Meu Sonho Meu Lar;
- novos módulos por catálogo.

### 9.8 Sugestões de tecnologia

#### Frontend web/PWA
Preferência:
- **React + Vite + TypeScript**;
- **Next.js** também é viável se houver necessidade forte de SSR, mas para app shell modular o Vite pode ser mais simples.

#### UI
- Tailwind CSS ou Bootstrap 5 customizado;
- biblioteca de componentes institucional própria.

#### Mobile iOS shell
- Capacitor.

#### Backend/BFF
- Node.js/NestJS, Laravel ou PHP moderno, conforme maturidade da equipe;
- considerando o legado atual em PHP, uma trilha pragmática é **manter legados PHP** e criar o **BFF em PHP moderno/Laravel** ou **Node/NestJS**.

#### Banco e integração
- MySQL/MariaDB para compatibilidade;
- Redis para sessão, fila e cache;
- Firebase Cloud Messaging ou OneSignal para push.

### 9.9 Organização de código recomendada

- monorepo com apps e packages compartilhados;
- design system separado;
- contratos de módulo em JSON/TypeScript;
- integração legada encapsulada por adaptadores;
- testes automatizados por camada.

### 9.10 Recomendações profissionais para aprovação nas lojas

1. **Não publique um simples webview cru**.
2. **Entregue valor nativo perceptível**: notificações, compartilhamento, geolocalização, câmera, protocolos e experiência consistente.
3. **Padronize a identidade institucional** em todos os módulos.
4. **Reduza fricção de autenticação**.
5. **Inclua política de privacidade, canal de suporte e acessibilidade** desde o MVP.
6. **Garanta estabilidade real em dispositivos intermediários**.
7. **Comece com poucos módulos bem resolvidos**, em vez de muitos módulos inconsistentes.

---

## 10. Recomendação executiva final

A melhor decisão para o cenário da Prefeitura é adotar uma **estratégia de transformação progressiva**, e não uma reescrita total imediata.

### Decisão recomendada
- **Portal atual evolui para PWA institucional**;
- **Android publicado com TWA**;
- **iOS publicado com shell Capacitor**;
- **Cata Treco, Lâmpadas e Chamado de TI** entram como primeira onda prioritária;
- **autenticação, protocolos, notificações e design system** formam o núcleo compartilhado;
- **novos módulos entram por contrato padrão**, preservando escalabilidade.

### Resultado esperado
Esse caminho reduz retrabalho, aproveita o ecossistema existente, melhora a governança técnica, acelera time-to-market e posiciona o PrefSADE como um produto digital oficial, moderno, seguro e sustentável para evolução futura.
