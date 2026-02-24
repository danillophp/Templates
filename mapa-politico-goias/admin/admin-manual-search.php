<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_admin_manual_search_init(): void
{
    add_action('wp_ajax_mpg_manual_enqueue_search', 'mpg_ajax_manual_enqueue_search');
    add_action('wp_ajax_mpg_manual_process_next', 'mpg_ajax_manual_process_next');
}

function mpg_render_admin_manual_search_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão.');
    }

    $nonce = wp_create_nonce('mpg_manual_search_nonce');
    ?>
    <div class="wrap">
        <h1>🔍 Buscar Político por IA</h1>
        <p>Use busca manual inteligente para pesquisar e cadastrar um político específico.</p>

        <textarea id="mpg-manual-query" rows="6" style="width:100%;max-width:900px;" placeholder="Digite o nome do político, cidade, cargo ou informações da biografia…"></textarea>
        <p>
            <button class="button button-primary" id="mpg-manual-run">Pesquisar e Cadastrar com IA</button>
        </p>

        <div id="mpg-manual-status" style="padding:10px;background:#fff;border:1px solid #ddd;max-width:900px;">
            Aguardando ação.
        </div>
    </div>

    <script>
        (() => {
            const ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            const nonce = <?php echo wp_json_encode($nonce); ?>;
            const queryEl = document.getElementById('mpg-manual-query');
            const statusEl = document.getElementById('mpg-manual-status');

            const send = async (action, extra = {}) => {
                const body = new URLSearchParams({ action, nonce, ...extra });
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString(),
                });
                const json = await response.json();
                if (!response.ok || !json.success) throw new Error(json?.data?.message || 'Erro na requisição');
                return json.data;
            };

            document.getElementById('mpg-manual-run')?.addEventListener('click', async () => {
                const query = (queryEl?.value || '').trim();
                if (!query) {
                    statusEl.textContent = 'Informe um texto para busca.';
                    return;
                }

                try {
                    statusEl.textContent = 'Pesquisando... (enfileirando)';
                    await send('mpg_manual_enqueue_search', { query_text: query });

                    statusEl.textContent = 'Pesquisando... (processando fila manual)';
                    const result = await send('mpg_manual_process_next');
                    statusEl.textContent = result.message || 'Processo concluído.';
                } catch (e) {
                    statusEl.textContent = 'Erro: ' + e.message;
                }
            });
        })();
    </script>
    <?php
}

function mpg_ajax_manual_enqueue_search(): void
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Sem permissão'], 403);
    }

    check_ajax_referer('mpg_manual_search_nonce', 'nonce');

    $query = sanitize_textarea_field(wp_unslash($_POST['query_text'] ?? ''));
    $result = mpg_manual_enqueue_query($query, get_current_user_id());
    if (!$result['ok']) {
        wp_send_json_error(['message' => (string) ($result['message'] ?? 'Falha ao enfileirar')], 400);
    }

    wp_send_json_success(['message' => (string) $result['message']]);
}

function mpg_ajax_manual_process_next(): void
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Sem permissão'], 403);
    }

    check_ajax_referer('mpg_manual_search_nonce', 'nonce');
    $result = mpg_manual_process_next();
    wp_send_json_success(['message' => (string) ($result['message'] ?? 'Processado')]);
}
