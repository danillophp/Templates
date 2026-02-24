<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_admin_delete_init(): void
{
    add_action('wp_ajax_mpg_delete_records', 'mpg_ajax_delete_records');
}

function mpg_render_admin_delete_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão.');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'mpg_prefeitos';
    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC LIMIT 500", ARRAY_A);
    $nonce = wp_create_nonce('mpg_delete_nonce');
    ?>
    <div class="wrap">
        <h1>Excluir Cadastros</h1>
        <p>
            <button class="button" id="mpg-delete-selected">Excluir selecionados</button>
            <button class="button button-link-delete" id="mpg-delete-all">Excluir TODOS</button>
        </p>
        <p id="mpg-delete-feedback"></p>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th><input type="checkbox" id="mpg-select-all"></th>
                    <th>ID</th>
                    <th>Prefeito</th>
                    <th>Vice</th>
                    <th>Município</th>
                    <th>Partido</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr data-id="<?php echo esc_attr((string) $row['id']); ?>">
                        <td><input type="checkbox" class="mpg-select" value="<?php echo esc_attr((string) $row['id']); ?>"></td>
                        <td><?php echo esc_html((string) $row['id']); ?></td>
                        <td><?php echo esc_html((string) $row['prefeito_nome']); ?></td>
                        <td><?php echo esc_html((string) $row['vice_nome']); ?></td>
                        <td><?php echo esc_html((string) $row['municipio_nome']); ?></td>
                        <td><?php echo esc_html((string) $row['partido']); ?></td>
                        <td><button class="button button-link-delete mpg-delete-one" data-id="<?php echo esc_attr((string) $row['id']); ?>">Excluir</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        (() => {
            const ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            const nonce = <?php echo wp_json_encode($nonce); ?>;
            const feedback = document.getElementById('mpg-delete-feedback');

            const selectedIds = () => Array.from(document.querySelectorAll('.mpg-select:checked')).map(i => Number(i.value)).filter(v => v > 0);

            const sendDelete = async (scope, ids = []) => {
                const body = new URLSearchParams({ action: 'mpg_delete_records', nonce, scope, ids: JSON.stringify(ids) });
                const response = await fetch(ajaxUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() });
                const json = await response.json();
                if (!response.ok || !json.success) throw new Error(json?.data?.message || 'Falha na exclusão');
                return json.data;
            };

            document.getElementById('mpg-select-all')?.addEventListener('change', (e) => {
                document.querySelectorAll('.mpg-select').forEach(i => i.checked = !!e.target.checked);
            });

            document.getElementById('mpg-delete-selected')?.addEventListener('click', async () => {
                const ids = selectedIds();
                if (!ids.length) { feedback.textContent = 'Selecione registros.'; return; }
                if (!confirm('Esta ação é irreversível. Confirmar exclusão selecionada?')) return;
                try {
                    const data = await sendDelete('selected', ids);
                    feedback.textContent = `Excluídos: ${data.deleted_count}`;
                    window.location.reload();
                } catch (e) { feedback.textContent = e.message; }
            });

            document.getElementById('mpg-delete-all')?.addEventListener('click', async () => {
                if (!confirm('ATENÇÃO: excluir TODOS os registros? Esta ação é irreversível.')) return;
                try {
                    const data = await sendDelete('all', []);
                    feedback.textContent = `Excluídos: ${data.deleted_count}`;
                    window.location.reload();
                } catch (e) { feedback.textContent = e.message; }
            });

            document.querySelectorAll('.mpg-delete-one').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = Number(btn.getAttribute('data-id'));
                    if (!id || !confirm('Excluir este registro?')) return;
                    try {
                        const data = await sendDelete('single', [id]);
                        feedback.textContent = `Excluídos: ${data.deleted_count}`;
                        window.location.reload();
                    } catch (e) { feedback.textContent = e.message; }
                });
            });
        })();
    </script>
    <?php
}

function mpg_ajax_delete_records(): void
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Sem permissão'], 403);
    }

    check_ajax_referer('mpg_delete_nonce', 'nonce');

    global $wpdb;
    $table = $wpdb->prefix . 'mpg_prefeitos';

    $scope = sanitize_text_field(wp_unslash($_POST['scope'] ?? 'single'));
    $idsRaw = (string) wp_unslash($_POST['ids'] ?? '[]');
    $idsDecoded = json_decode($idsRaw, true);
    $ids = is_array($idsDecoded) ? wp_parse_id_list($idsDecoded) : [];

    if ($scope === 'all') {
        $deleted = $wpdb->query("DELETE FROM {$table}");
        wp_send_json_success(['deleted_count' => (int) $deleted]);
    }

    if ($scope === 'single') {
        $ids = array_slice($ids, 0, 1);
    }

    if (empty($ids)) {
        wp_send_json_error(['message' => 'Nenhum ID válido'], 400);
    }

    $in = implode(',', array_map('intval', $ids));
    $deleted = $wpdb->query("DELETE FROM {$table} WHERE id IN ({$in})");
    wp_send_json_success(['deleted_count' => (int) $deleted]);
}
