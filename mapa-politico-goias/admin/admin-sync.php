<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_admin_sync_init(): void
{
    add_action('wp_ajax_mpg_enqueue_all', 'mpg_ajax_enqueue_all');
    add_action('wp_ajax_mpg_enqueue_one', 'mpg_ajax_enqueue_one');
    add_action('wp_ajax_mpg_process_next', 'mpg_ajax_process_next');
}

function mpg_render_admin_sync_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão.');
    }

    $rows = mpg_queue_rows(300);
    $municipios = mpg_get_municipios_goias();
    $nonce = wp_create_nonce('mpg_queue_nonce');
    $lastSync = get_option('mpg_last_sync', 'nunca');
    ?>
    <div class="wrap">
        <h1>Sincronizar Prefeitos</h1>
        <p><strong>Última sincronização:</strong> <?php echo esc_html((string) $lastSync); ?></p>

        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <select id="mpg-municipio-input">
                <option value="">Escolha um município</option>
                <?php foreach ($municipios as $m): ?>
                    <option value="<?php echo esc_attr((string) ($m['nome'] ?? '')); ?>"><?php echo esc_html((string) ($m['nome'] ?? '')); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button" id="mpg-sync-one">Sincronizar município</button>
            <button class="button button-primary" id="mpg-sync-all">Sincronizar TODOS os municípios de Goiás</button>
            <button class="button" id="mpg-process-next">Processar próximo da fila</button>
        </div>
        <p id="mpg-sync-feedback"></p>

        <h2>Status da fila</h2>
        <table class="widefat striped">
            <thead><tr><th>ID</th><th>Município</th><th>Código</th><th>Status</th><th>Tentativas</th><th>Erro</th><th>Atualizado</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php echo esc_html((string) $row['id']); ?></td>
                    <td><?php echo esc_html((string) $row['municipio_nome']); ?></td>
                    <td><?php echo esc_html((string) $row['municipio_codigo']); ?></td>
                    <td><?php echo esc_html((string) $row['status']); ?></td>
                    <td><?php echo esc_html((string) $row['tentativas']); ?></td>
                    <td><?php echo esc_html((string) ($row['ultimo_erro'] ?? '')); ?></td>
                    <td><?php echo esc_html((string) $row['atualizado_em']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        (() => {
            const ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            const nonce = <?php echo wp_json_encode($nonce); ?>;
            const feedback = document.getElementById('mpg-sync-feedback');

            const send = async (action, extra = {}) => {
                const body = new URLSearchParams({ action, nonce, ...extra });
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString(),
                });
                const json = await response.json();
                if (!response.ok || !json.success) {
                    throw new Error(json?.data?.message || 'Erro na ação');
                }
                return json.data;
            };

            document.getElementById('mpg-sync-all')?.addEventListener('click', async () => {
                try {
                    const data = await send('mpg_enqueue_all');
                    feedback.textContent = data.message;
                    window.location.reload();
                } catch (e) {
                    feedback.textContent = e.message;
                }
            });

            document.getElementById('mpg-sync-one')?.addEventListener('click', async () => {
                const municipio = document.getElementById('mpg-municipio-input')?.value || '';
                if (!municipio) {
                    feedback.textContent = 'Selecione um município.';
                    return;
                }

                try {
                    const data = await send('mpg_enqueue_one', { municipio });
                    feedback.textContent = data.message;
                    window.location.reload();
                } catch (e) {
                    feedback.textContent = e.message;
                }
            });

            document.getElementById('mpg-process-next')?.addEventListener('click', async () => {
                try {
                    const data = await send('mpg_process_next');
                    feedback.textContent = data.message;
                    window.location.reload();
                } catch (e) {
                    feedback.textContent = e.message;
                }
            });
        })();
    </script>
    <?php
}

function mpg_ajax_enqueue_all(): void
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Sem permissão'], 403);
    }

    check_ajax_referer('mpg_queue_nonce', 'nonce');
    $result = mpg_queue_enqueue_all();
    wp_send_json_success(['message' => 'Municípios enfileirados: ' . (int) ($result['enqueued'] ?? 0)]);
}

function mpg_ajax_enqueue_one(): void
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Sem permissão'], 403);
    }

    check_ajax_referer('mpg_queue_nonce', 'nonce');
    $municipio = sanitize_text_field(wp_unslash($_POST['municipio'] ?? ''));
    $result = mpg_queue_enqueue_by_search($municipio);
    if (!$result['ok']) {
        wp_send_json_error(['message' => (string) $result['message']], 400);
    }

    wp_send_json_success(['message' => (string) $result['message']]);
}

function mpg_ajax_process_next(): void
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Sem permissão'], 403);
    }

    check_ajax_referer('mpg_queue_nonce', 'nonce');
    $result = mpg_queue_process_next();
    wp_send_json_success(['message' => (string) ($result['message'] ?? 'Processado')]);
}
