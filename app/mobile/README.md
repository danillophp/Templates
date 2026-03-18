# PrefSADE Mobile Mirror Base

## Estratégia espelhada

O app Android e o app iOS devem funcionar como **camadas de acesso ao domínio oficial** `https://www.prefsade.com.br/`, usando `/app/index.php` como porta de entrada principal.

## O que atualiza automaticamente

Atualiza automaticamente sem nova publicação nas lojas:
- páginas PHP, HTML e rotas existentes no servidor;
- estilos CSS e scripts JS publicados no domínio;
- banners, cards, menus e textos do hub `/app`;
- mudanças em módulos existentes como `/catatreco`, `/iluminacao`, `/chamado`, `/emendas`, `/educasad` e `/suporte`.

## O que exige nova publicação na loja

Exige nova publicação quando houver mudança em:
- package/bundle identifier;
- splash nativa;
- ícones nativos do app;
- permissões Android/iOS;
- integrações nativas (push, câmera, geolocalização, arquivos);
- código do container TWA/Capacitor.

## Arquivos-base

- `android-twa/twa-manifest.json`: configuração-base do container Android Trusted Web Activity.
- `android-twa/assetlinks.example.json`: modelo para publicação em `/.well-known/assetlinks.json`.
- `ios-capacitor/capacitor.config.json`: configuração-base do shell iOS com carregamento remoto do domínio oficial.
