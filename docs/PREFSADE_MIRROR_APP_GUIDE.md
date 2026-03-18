# PrefSADE — Guia do App Espelhado (Servidor como Fonte Única)

## 1. Arquitetura ideal

A arquitetura correta para o PrefSADE é **server-driven / mirror-first**:

- o domínio `https://www.prefsade.com.br/` é a fonte única de verdade;
- o hub `/app` é a porta de entrada oficial do aplicativo;
- os sistemas já existentes continuam em suas rotas atuais;
- Android e iOS consomem o conteúdo online do servidor;
- mudanças no FTP refletem automaticamente no app porque o container móvel carrega o conteúdo remoto do domínio.

## 2. Estrutura da pasta `/app`

```text
/app
├── index.php
├── manifest.json
├── sw.js
├── offline.html
├── .htaccess
├── assets/
│   ├── css/style.css
│   ├── js/app.js
│   ├── img/prefsade-mark.svg
│   └── icons/icon-app.svg
├── mobile/
│   ├── README.md
│   ├── android-twa/
│   │   ├── twa-manifest.json
│   │   └── assetlinks.example.json
│   └── ios-capacitor/
│       └── capacitor.config.json
└── modules/
```

## 3. Como garantir que o app seja um espelho do site

### Regra principal

O container Android/iOS **não armazena conteúdo institucional próprio**. Ele apenas abre o domínio oficial.

### Estratégia usada

- `index.php` roda no servidor e gera a home do hub;
- o `service worker` usa **network-first** para `/app` e navegações, priorizando sempre a resposta mais recente do servidor;
- o `.htaccess` aplica `no-store` para HTML/PHP, reduzindo risco de conteúdo visual desatualizado;
- TWA e Capacitor apontam para `https://www.prefsade.com.br/app/index.php`.

## 4. O que atualiza automaticamente

Atualiza automaticamente no app após alteração no servidor:
- home `/app`;
- cards, textos, banners e links do hub;
- PHP, HTML, CSS e JS do servidor;
- qualquer sistema já hospedado nas rotas existentes;
- formulários e menus servidos pelo domínio.

## 5. O que exige nova publicação na loja

Exige nova publicação na loja apenas quando mudar:
- nome do app;
- package Android ou bundle iOS;
- splash nativa;
- ícones nativos da loja;
- permissões nativas;
- integrações Capacitor/TWA;
- qualquer código do container nativo.

## 6. Implantação via FTP

1. Envie a pasta `/app` completa para o domínio principal.
2. Mantenha os sistemas atuais nas rotas existentes.
3. Confirme o SSL ativo no domínio.
4. Verifique se o Apache/HostGator aplica o `.htaccess` da pasta `/app`.
5. Acesse `https://www.prefsade.com.br/app/index.php`.
6. Teste o manifesto, o service worker e a navegação mobile.
7. Publique `assetlinks.json` em `/.well-known/assetlinks.json` ao preparar a TWA.

## 7. Checklist final

- [x] Hub `/app` operacional em navegador.
- [x] Layout responsivo para celular.
- [x] PWA com `manifest.json`.
- [x] Service worker com estratégia espelhada.
- [x] Offline fallback funcional.
- [x] Base pronta para Android TWA.
- [x] Base pronta para iOS Capacitor.
- [x] Servidor mantido como fonte única de verdade.
- [x] Rotas legadas preservadas.
