<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_admin_cadastro_init(): void
{
    add_action('wp_ajax_mpg_ai_enrich_form', 'mpg_ajax_ai_enrich_form');
    add_action('wp_ajax_mpg_save_manual_politico', 'mpg_ajax_save_manual_politico');
}

function mpg_render_admin_cadastro_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão.');
    }

    $nonce = wp_create_nonce('mpg_cadastro_nonce');
    ?>
    <div class="wrap">
        <h1>Cadastrar Político</h1>
        <p>Pesquise por nome para preencher automaticamente o histórico, revise e salve manualmente.</p>

        <table class="form-table" role="presentation" style="max-width:980px;">
            <tr>
                <th><label for="mpg_nome">Nome completo do político</label></th>
                <td style="display:flex;gap:8px;align-items:center;">
                    <input type="text" id="mpg_nome" class="regular-text" required>
                    <button class="button" id="mpg_pesquisar_nome">🔍 Pesquisar</button>
                </td>
            </tr>
            <tr>
                <th><label for="mpg_cargo">Cargo</label></th>
                <td>
                    <select id="mpg_cargo">
                        <option>Prefeito</option>
                        <option>Vice-Prefeito</option>
                        <option>Vereador</option>
                        <option>Deputado Estadual</option>
                        <option>Deputado Federal</option>
                        <option>Senador</option>
                        <option>Governador</option>
                    </select>
                </td>
            </tr>
            <tr><th><label for="mpg_municipio">Município</label></th><td><input type="text" id="mpg_municipio" class="regular-text" required></td></tr>
            <tr>
                <th><label for="mpg_estado">Estado</label></th>
                <td>
                    <select id="mpg_estado">
                        <option value="GO" selected>Goiás (GO)</option>
                        <option value="SP">São Paulo (SP)</option>
                        <option value="RJ">Rio de Janeiro (RJ)</option>
                        <option value="MG">Minas Gerais (MG)</option>
                        <option value="DF">Distrito Federal (DF)</option>
                    </select>
                </td>
            </tr>
            <tr><th><label for="mpg_rua_quadra">Endereço da Prefeitura - Rua / Quadra</label></th><td><input type="text" id="mpg_rua_quadra" class="regular-text"></td></tr>
            <tr><th><label for="mpg_lote">Endereço da Prefeitura - Lote</label></th><td><input type="text" id="mpg_lote" class="regular-text"></td></tr>
        </table>

        <h2>Campos automáticos (IA)</h2>
        <table class="form-table" role="presentation" style="max-width:980px;">
            <tr><th>Partido político</th><td><input type="text" id="mpg_partido" class="regular-text"></td></tr>
            <tr><th>Idade</th><td><input type="text" id="mpg_idade" class="small-text"></td></tr>
            <tr><th>Biografia</th><td><textarea id="mpg_bio" rows="4" class="large-text"></textarea></td></tr>
            <tr><th>Histórico político</th><td><textarea id="mpg_hist" rows="8" class="large-text"></textarea></td></tr>
            <tr><th>Telefone</th><td><input type="text" id="mpg_telefone" class="regular-text"></td></tr>
            <tr><th>Foto (URL)</th><td><input type="text" id="mpg_foto_url" class="regular-text"></td></tr>
            <tr><th>Latitude</th><td><input type="text" id="mpg_latitude" class="regular-text"></td></tr>
            <tr><th>Longitude</th><td><input type="text" id="mpg_longitude" class="regular-text"></td></tr>
        </table>

        <p><button class="button button-primary" id="mpg_save_politico">Salvar cadastro</button></p>
        <div id="mpg_cadastro_status" class="notice" style="display:block;"><p>Aguardando ação.</p></div>
    </div>

    <script>
        (() => {
            const ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            const nonce = <?php echo wp_json_encode($nonce); ?>;

            const fields = {
                nome: document.getElementById('mpg_nome'),
                cargo: document.getElementById('mpg_cargo'),
                municipio: document.getElementById('mpg_municipio'),
                estado: document.getElementById('mpg_estado'),
                rua_quadra: document.getElementById('mpg_rua_quadra'),
                lote: document.getElementById('mpg_lote'),
                partido: document.getElementById('mpg_partido'),
                idade: document.getElementById('mpg_idade'),
                bio: document.getElementById('mpg_bio'),
                hist: document.getElementById('mpg_hist'),
                telefone: document.getElementById('mpg_telefone'),
                foto_url: document.getElementById('mpg_foto_url'),
                latitude: document.getElementById('mpg_latitude'),
                longitude: document.getElementById('mpg_longitude')
            };

            const status = document.getElementById('mpg_cadastro_status');
            const setStatus = (msg, ok = true) => {
                status.className = ok ? 'notice notice-success' : 'notice notice-error';
                status.innerHTML = `<p>${msg}</p>`;
            };

            const send = async (action, payload) => {
                const body = new URLSearchParams({ action, nonce, ...payload });
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString()
                });
                const json = await response.json();
                if (!json?.success) {
                    throw new Error(json?.data?.message || 'Falha na requisição.');
                }
                return json.data;
            };

            document.getElementById('mpg_pesquisar_nome')?.addEventListener('click', async (ev) => {
                ev.preventDefault();
                try {
                    setStatus('Pesquisando na IA...');
                    const data = await send('mpg_ai_enrich_form', {
                        nome: fields.nome.value,
                        cargo: fields.cargo.value,
                        municipio: fields.municipio.value,
                        estado: fields.estado.value
                    });

                    fields.partido.value = data.partido || '';
                    fields.idade.value = data.idade || '';
                    if (data.cidade && !fields.municipio.value) fields.municipio.value = data.cidade;
                    fields.bio.value = data.biografia_resumida || '';
                    fields.hist.value = data.historico_politico || '';
                    fields.telefone.value = data.telefone || fields.telefone.value || '';
                    fields.foto_url.value = data.foto_url || fields.foto_url.value || '';
                    setStatus('Pesquisa concluída. Revise os dados antes de salvar.');
                } catch (e) {
                    setStatus('Erro: ' + e.message, false);
                }
            });

            document.getElementById('mpg_save_politico')?.addEventListener('click', async () => {
                try {
                    setStatus('Salvando cadastro...');
                    const data = await send('mpg_save_manual_politico', {
                        nome_completo: fields.nome.value,
                        cargo: fields.cargo.value,
                        cidade: fields.municipio.value,
                        estado: fields.estado.value,
                        rua_quadra: fields.rua_quadra.value,
                        lote: fields.lote.value,
                        partido: fields.partido.value,
                        idade: fields.idade.value,
                        biografia_resumida: fields.bio.value,
                        historico_politico: fields.hist.value,
                        telefone: fields.telefone.value,
                        foto_url: fields.foto_url.value,
                        latitude: fields.latitude.value,
                        longitude: fields.longitude.value
                    });
                    setStatus(data.message || 'Cadastro salvo com sucesso.');
                } catch (e) {
                    setStatus('Erro: ' + e.message, false);
                }
            });
        })();
    </script>
    <?php
}

function mpg_ajax_ai_enrich_form(): void
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Sem permissão'], 403);
    }
    check_ajax_referer('mpg_cadastro_nonce', 'nonce');

    $input = [
        'nome' => sanitize_text_field(wp_unslash($_POST['nome'] ?? '')),
        'cargo' => sanitize_text_field(wp_unslash($_POST['cargo'] ?? '')),
        'municipio' => sanitize_text_field(wp_unslash($_POST['municipio'] ?? '')),
        'estado' => sanitize_text_field(wp_unslash($_POST['estado'] ?? 'GO')),
    ];

    $result = mpg_ai_search_by_name($input);
    if (!$result['ok']) {
        mpg_log_event('erro', (string) $input['municipio'], 'pesquisa_nome_ia', (string) $result['message'], []);
        wp_send_json_error(['message' => (string) $result['message']], 400);
    }

    mpg_log_event('sucesso', (string) ($result['data']['cidade'] ?? $input['municipio']), 'pesquisa_nome_ia', 'Pesquisa por nome concluída', (array) ($result['data']['fontes'] ?? []));
    wp_send_json_success($result['data']);
}

function mpg_ajax_save_manual_politico(): void
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Sem permissão'], 403);
    }
    check_ajax_referer('mpg_cadastro_nonce', 'nonce');

    $payload = [
        'nome_completo' => sanitize_text_field(wp_unslash($_POST['nome_completo'] ?? '')),
        'cargo' => sanitize_text_field(wp_unslash($_POST['cargo'] ?? '')),
        'cidade' => sanitize_text_field(wp_unslash($_POST['cidade'] ?? '')),
        'estado' => sanitize_text_field(wp_unslash($_POST['estado'] ?? 'GO')),
        'rua_quadra' => sanitize_text_field(wp_unslash($_POST['rua_quadra'] ?? '')),
        'lote' => sanitize_text_field(wp_unslash($_POST['lote'] ?? '')),
        'partido' => sanitize_text_field(wp_unslash($_POST['partido'] ?? '')),
        'idade' => sanitize_text_field(wp_unslash($_POST['idade'] ?? '')),
        'telefone' => sanitize_text_field(wp_unslash($_POST['telefone'] ?? '')),
        'latitude' => wp_unslash($_POST['latitude'] ?? ''),
        'longitude' => wp_unslash($_POST['longitude'] ?? ''),
        'biografia_resumida' => sanitize_textarea_field(wp_unslash($_POST['biografia_resumida'] ?? '')),
        'historico_politico' => sanitize_textarea_field(wp_unslash($_POST['historico_politico'] ?? '')),
        'foto_url' => esc_url_raw(wp_unslash($_POST['foto_url'] ?? '')),
        'fontes' => [],
        'fonte_primaria' => 'https://api.openai.com',
        'site_oficial' => '',
    ];

    $result = mpg_save_manual_politico($payload);
    if (!$result['ok']) {
        mpg_log_event('erro', (string) $payload['cidade'], 'salvar_cadastro_manual', (string) $result['message'], []);
        wp_send_json_error(['message' => (string) $result['message']], 400);
    }

    mpg_log_event('sucesso', (string) $payload['cidade'], 'salvar_cadastro_manual', (string) $result['message'], []);
    wp_send_json_success(['message' => (string) $result['message']]);
}
