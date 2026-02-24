# Mapa Político — Instalação e uso

## Instalação
1. Compacte a pasta `mapa-politico` em um `.zip`.
2. No WordPress: **Plugins > Adicionar novo > Enviar plugin**.
3. Ative o plugin.
4. Menu admin: **Mapa Político**.

## Sincronização por fila
No menu **Mapa Político > Atualização IA Goiás**:
- **Sincronizar município**: enfileira 1 município.
- **Sincronizar todos**: enfileira todos os municípios de GO.
- **Processar próximo da fila**: processa 1 item por execução.

Status da fila:
- `pendente`
- `processando`
- `concluido`
- `erro`

## Front-end
Use o shortcode:

```
[mapa_politico]
```

O mapa mostra apenas **Prefeitos**, com pesquisa e ações de rota/contato.

## Segurança
- Todos os endpoints admin usam `nonce` + `current_user_can('manage_options')`.
- Dados inseridos são sanitizados.
- Somente fontes públicas/institucionais são persistidas como oficiais.

## Observações
- A rotina de IA registra logs detalhados por município/etapa.
- A criação de tabelas ocorre automaticamente na ativação.
