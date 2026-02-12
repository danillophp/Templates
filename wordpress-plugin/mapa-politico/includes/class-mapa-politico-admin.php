<?php

if (!defined('ABSPATH')) {
    exit;
}

class MapaPoliticoAdmin
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerMenu']);
        add_action('admin_post_mapa_politico_save_entry', [self::class, 'saveEntry']);
        add_action('admin_post_mapa_politico_delete_entry', [self::class, 'deleteEntry']);
        add_action('admin_notices', [self::class, 'renderNotices']);
        add_action('admin_post_mapa_politico_run_ai_sync', [self::class, 'runAiSync']);
        add_action('admin_post_mapa_politico_update_auto_status', [self::class, 'updateAutoStatus']);
        add_action('wp_ajax_mapa_politico_delete_records', [self::class, 'ajaxDeleteRecords']);
    }

    public static function registerMenu(): void
    {
        add_menu_page('Mapa Político', 'Mapa Político', 'manage_options', 'mapa-politico', [self::class, 'renderDashboard'], 'dashicons-location-alt', 26);
        add_submenu_page('mapa-politico', 'Visão geral', 'Visão geral', 'manage_options', 'mapa-politico', [self::class, 'renderDashboard']);
        add_submenu_page('mapa-politico', 'Cadastro Unificado', 'Cadastro Unificado', 'manage_options', 'mapa-politico-cadastro', [self::class, 'renderUnifiedForm']);
        add_submenu_page('mapa-politico', 'Atualização IA Goiás', 'Atualização IA Goiás', 'manage_options', 'mapa-politico-ia', [self::class, 'renderAiSync']);
    }

    public static function renderDashboard(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }
        ?>
        <div class="wrap">
            <h1>Mapa Político</h1>
            <p>Use o menu <strong>Cadastro Unificado</strong> para cadastrar político + localização na mesma tela.</p>
            <ul>
                <li>Mapa com Leaflet + OpenStreetMap (sem API paga).</li>
                <li>Geocodificação opcional via Nominatim.</li>
                <li>Shortcode público: <code>[mapa_politico]</code>.</li>
            </ul>
        </div>
        <?php
    }

    public static function renderNotices(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_GET['page']) || !in_array((string) $_GET['page'], ['mapa-politico-cadastro', 'mapa-politico-ia'], true)) {
            return;
        }

        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Cadastro salvo com sucesso.</p></div>';
        }

        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Cadastro removido com sucesso.</p></div>';
        }

        if (isset($_GET['synced'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Sincronização automática concluída.</p></div>';
        }

        if (isset($_GET['error'])) {
            $message = sanitize_text_field((string) wp_unslash($_GET['error']));
            echo '<div class="notice notice-error"><p>Erro ao salvar cadastro: ' . esc_html($message) . '.</p></div>';
        }
    }

    public static function renderUnifiedForm(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        global $wpdb;
        $locationsTable = $wpdb->prefix . 'mapa_politico_locations';
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';

        $editPoliticianId = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $editing = null;

        if ($editPoliticianId > 0) {
            $editing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT p.*, l.name AS location_name, l.city, l.state, l.postal_code, l.latitude, l.longitude, l.address
                     FROM {$politiciansTable} p
                     INNER JOIN {$locationsTable} l ON l.id = p.location_id
                     WHERE p.id = %d",
                    $editPoliticianId
                ),
                ARRAY_A
            );
        }

        $entries = $wpdb->get_results(
            "SELECT p.id AS politician_id, p.full_name, p.position, p.party, p.phone, p.data_status, p.is_auto, p.source_url,
                    l.city, l.state, l.postal_code, l.latitude, l.longitude
             FROM {$politiciansTable} p
             INNER JOIN {$locationsTable} l ON l.id = p.location_id
             ORDER BY p.id DESC",
            ARRAY_A
        );

        ?>
        <div class="wrap">
            <h1>Cadastro Unificado (Político + Localização)</h1>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('mapa_politico_save_entry'); ?>
                <input type="hidden" name="action" value="mapa_politico_save_entry">
                <input type="hidden" name="politician_id" value="<?php echo esc_attr((string) ($editing['id'] ?? '')); ?>">

                <table class="form-table" role="presentation">
                    <tr><th><label for="full_name">Nome do político</label></th><td><input required class="regular-text" id="full_name" name="full_name" value="<?php echo esc_attr($editing['full_name'] ?? ''); ?>"></td></tr>
                    <tr><th><label for="position">Cargo</label></th><td><input required class="regular-text" id="position" name="position" value="<?php echo esc_attr($editing['position'] ?? ''); ?>"></td></tr>
                    <tr><th><label for="party">Partido</label></th><td><input required class="regular-text" id="party" name="party" value="<?php echo esc_attr($editing['party'] ?? ''); ?>"></td></tr>
                    <tr><th><label for="phone">Telefone</label></th><td><input class="regular-text" id="phone" name="phone" placeholder="(62) 99999-9999" value="<?php echo esc_attr($editing['phone'] ?? ''); ?>"></td></tr>
                    <tr><th><label for="city">Cidade</label></th><td><input required class="regular-text" id="city" name="city" value="<?php echo esc_attr($editing['city'] ?? ''); ?>"></td></tr>
                    <tr><th><label for="state">Estado</label></th><td><input class="regular-text" id="state" name="state" value="<?php echo esc_attr($editing['state'] ?? ''); ?>"></td></tr>
                    <tr><th><label for="postal_code">CEP</label></th><td><input class="regular-text" id="postal_code" name="postal_code" value="<?php echo esc_attr($editing['postal_code'] ?? ''); ?>"></td></tr>
                    <tr><th><label for="latitude">Latitude</label></th><td><input required type="number" step="0.000001" id="latitude" name="latitude" value="<?php echo esc_attr((string) ($editing['latitude'] ?? '')); ?>"></td></tr>
                    <tr><th><label for="longitude">Longitude</label></th><td><input required type="number" step="0.000001" id="longitude" name="longitude" value="<?php echo esc_attr((string) ($editing['longitude'] ?? '')); ?>"></td></tr>
                    <tr>
                        <th>Mapa interativo</th>
                        <td>
                            <p>Digite cidade/CEP e clique em <strong>Centralizar no mapa</strong>. Clique no mapa para preencher latitude/longitude.</p>
                            <p>
                                <button class="button" type="button" id="mapa-politico-centralizar">Centralizar no mapa</button>
                                <span id="mapa-politico-admin-feedback" style="margin-left:8px;"></span>
                            </p>
                            <div id="mapa-politico-admin-map" style="height: 420px; max-width: 900px; border-radius: 10px; border: 1px solid #dcdcde;"></div>
                        </td>
                    </tr>
                    <tr><th><label for="photo">Foto</label></th><td><input type="file" id="photo" name="photo" accept="image/png,image/jpeg,image/webp"></td></tr>
                    <tr><th><label for="biography">Biografia</label></th><td><textarea class="large-text" rows="3" id="biography" name="biography"><?php echo esc_textarea($editing['biography'] ?? ''); ?></textarea></td></tr>
                    <tr><th><label for="career_history">Histórico</label></th><td><textarea class="large-text" rows="3" id="career_history" name="career_history"><?php echo esc_textarea($editing['career_history'] ?? ''); ?></textarea></td></tr>
                </table>

                <?php submit_button($editing ? 'Atualizar cadastro' : 'Salvar cadastro'); ?>
            </form>

            <hr>

            <h2>Registros cadastrados</h2>
            <table class="widefat striped">
                <thead><tr><th>Nome</th><th>Cargo</th><th>Partido</th><th>Telefone</th><th>Status</th><th>Cidade</th><th>Estado</th><th>CEP</th><th>Latitude</th><th>Longitude</th><th>Ações</th></tr></thead>
                <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?php echo esc_html($entry['full_name']); ?></td>
                        <td><?php echo esc_html($entry['position']); ?></td>
                        <td><?php echo esc_html($entry['party']); ?></td>
                        <td><?php echo esc_html($entry['phone']); ?></td>
                        <td><?php echo esc_html($entry['data_status'] ?? 'completo'); ?></td>
                        <td><?php echo esc_html($entry['city']); ?></td>
                        <td><?php echo esc_html($entry['state']); ?></td>
                        <td><?php echo esc_html($entry['postal_code']); ?></td>
                        <td><?php echo esc_html($entry['latitude']); ?></td>
                        <td><?php echo esc_html($entry['longitude']); ?></td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=mapa-politico-cadastro&edit=' . absint($entry['politician_id']))); ?>">Editar</a>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                                <?php wp_nonce_field('mapa_politico_delete_entry_' . absint($entry['politician_id'])); ?>
                                <input type="hidden" name="action" value="mapa_politico_delete_entry">
                                <input type="hidden" name="politician_id" value="<?php echo esc_attr((string) $entry['politician_id']); ?>">
                                <button type="submit" class="button button-link-delete">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            (function () {
                const mapContainer = document.getElementById('mapa-politico-admin-map');
                if (!mapContainer || typeof L === 'undefined') return;

                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                const cityInput = document.getElementById('city');
                const stateInput = document.getElementById('state');
                const postalInput = document.getElementById('postal_code');
                const centerBtn = document.getElementById('mapa-politico-centralizar');
                const feedback = document.getElementById('mapa-politico-admin-feedback');

                const goiasLat = -15.8270;
                const goiasLng = -49.8362;
                const goiasZoom = 7;

                const initialLat = Number(latInput?.value || goiasLat);
                const initialLng = Number(lngInput?.value || goiasLng);
                const initialZoom = (latInput?.value && lngInput?.value) ? 12 : goiasZoom;

                const map = L.map(mapContainer).setView([initialLat, initialLng], initialZoom);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                }).addTo(map);

                let marker = null;
                if (latInput?.value && lngInput?.value) {
                    marker = L.marker([initialLat, initialLng]).addTo(map);
                }

                function setMarker(lat, lng) {
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
                    if (!marker) {
                        marker = L.marker([lat, lng]).addTo(map);
                    } else {
                        marker.setLatLng([lat, lng]);
                    }
                    latInput.value = Number(lat).toFixed(6);
                    lngInput.value = Number(lng).toFixed(6);
                }

                map.on('click', (event) => {
                    setMarker(event.latlng.lat, event.latlng.lng);
                    feedback.textContent = 'Coordenadas definidas pelo clique no mapa.';
                });

                centerBtn?.addEventListener('click', async () => {
                    const query = [cityInput?.value || '', stateInput?.value || '', postalInput?.value || ''].filter(Boolean).join(', ');
                    if (!query) {
                        feedback.textContent = 'Informe cidade e/ou CEP para centralizar.';
                        return;
                    }

                    feedback.textContent = 'Buscando localidade...';
                    centerBtn.disabled = true;

                    try {
                        const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(query)}`;
                        const res = await fetch(url, { headers: { Accept: 'application/json' } });
                        if (!res.ok) throw new Error('Falha na geocodificação.');
                        const data = await res.json();
                        if (!Array.isArray(data) || data.length === 0) {
                            feedback.textContent = 'Não encontramos esta localidade. Ajuste os dados e tente novamente.';
                            return;
                        }

                        const lat = Number(data[0].lat);
                        const lng = Number(data[0].lon);
                        map.setView([lat, lng], 12);
                        setMarker(lat, lng);
                        feedback.textContent = 'Mapa centralizado com sucesso.';
                    } catch (error) {
                        feedback.textContent = 'Erro ao buscar a localidade no Nominatim.';
                    } finally {
                        centerBtn.disabled = false;
                    }
                });
            })();
        </script>
        <?php
    }

    public static function saveEntry(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        check_admin_referer('mapa_politico_save_entry');

        global $wpdb;
        $locationsTable = $wpdb->prefix . 'mapa_politico_locations';
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';

        $politicianId = absint($_POST['politician_id'] ?? 0);
        $fullName = sanitize_text_field(wp_unslash($_POST['full_name'] ?? ''));
        $position = sanitize_text_field(wp_unslash($_POST['position'] ?? ''));
        $party = sanitize_text_field(wp_unslash($_POST['party'] ?? ''));
        $phoneRaw = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        $phoneDigits = preg_replace('/[^0-9]/', '', $phoneRaw);
        $phone = $phoneDigits ? '+' . $phoneDigits : '';
        $city = sanitize_text_field(wp_unslash($_POST['city'] ?? ''));
        $state = sanitize_text_field(wp_unslash($_POST['state'] ?? ''));
        $postalCode = sanitize_text_field(wp_unslash($_POST['postal_code'] ?? ''));
        $latitude = (float) ($_POST['latitude'] ?? 0);
        $longitude = (float) ($_POST['longitude'] ?? 0);

        if ($fullName === '' || $position === '' || $party === '' || $city === '' || !is_finite($latitude) || !is_finite($longitude)) {
            wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&error=Campos obrigat%C3%B3rios%20inv%C3%A1lidos'));
            exit;
        }

        if ($phone !== '' && (strlen($phone) < 11 || strlen($phone) > 16)) {
            wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&error=Telefone%20inv%C3%A1lido'));
            exit;
        }

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&error=Coordenadas%20inv%C3%A1lidas'));
            exit;
        }

        $photoId = null;
        if (!empty($_FILES['photo']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $uploadedPhotoId = media_handle_upload('photo', 0);
            if (!is_wp_error($uploadedPhotoId)) {
                $photoId = (int) $uploadedPhotoId;
            }
        }

        $wpdb->query('START TRANSACTION');

        try {
            if ($politicianId > 0) {
                $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$politiciansTable} WHERE id = %d", $politicianId), ARRAY_A);
                if (!$existing) {
                    throw new RuntimeException('Registro para edição não encontrado');
                }

                $locationId = (int) $existing['location_id'];

                $okLocation = $wpdb->update($locationsTable, [
                    'name' => $city,
                    'city' => $city,
                    'state' => $state,
                    'postal_code' => $postalCode,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'address' => trim($city . ($state ? ' - ' . $state : '')),
                ], ['id' => $locationId]);

                if ($okLocation === false) {
                    throw new RuntimeException('Falha ao atualizar localização: ' . $wpdb->last_error);
                }

                $politicianData = [
                    'full_name' => $fullName,
                    'position' => $position,
                    'party' => $party,
                    'phone' => $phone,
                    'biography' => sanitize_textarea_field(wp_unslash($_POST['biography'] ?? '')),
                    'career_history' => sanitize_textarea_field(wp_unslash($_POST['career_history'] ?? '')),
                    'data_status' => 'completo',
                    'is_auto' => 0,
                    'last_synced_at' => current_time('mysql'),
                    'source_name' => 'Cadastro manual',
                ];
                if ($photoId) {
                    $politicianData['photo_id'] = $photoId;
                }

                $okPolitician = $wpdb->update($politiciansTable, $politicianData, ['id' => $politicianId]);
                if ($okPolitician === false) {
                    throw new RuntimeException('Falha ao atualizar político: ' . $wpdb->last_error);
                }
            } else {
                $okLocation = $wpdb->insert($locationsTable, [
                    'name' => $city,
                    'city' => $city,
                    'state' => $state,
                    'postal_code' => $postalCode,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'address' => trim($city . ($state ? ' - ' . $state : '')),
                ]);

                if ($okLocation === false) {
                    throw new RuntimeException('Falha ao inserir localização: ' . $wpdb->last_error);
                }

                $locationId = (int) $wpdb->insert_id;

                $okPolitician = $wpdb->insert($politiciansTable, [
                    'location_id' => $locationId,
                    'full_name' => $fullName,
                    'position' => $position,
                    'party' => $party,
                    'phone' => $phone,
                    'biography' => sanitize_textarea_field(wp_unslash($_POST['biography'] ?? '')),
                    'career_history' => sanitize_textarea_field(wp_unslash($_POST['career_history'] ?? '')),
                    'data_status' => 'completo',
                    'is_auto' => 0,
                    'last_synced_at' => current_time('mysql'),
                    'source_name' => 'Cadastro manual',
                    'photo_id' => $photoId,
                ]);

                if ($okPolitician === false) {
                    throw new RuntimeException('Falha ao inserir político: ' . $wpdb->last_error);
                }
            }

            $wpdb->query('COMMIT');
            wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&saved=1'));
            exit;
        } catch (Throwable $e) {
            $wpdb->query('ROLLBACK');
            error_log('[MapaPolitico] saveEntry error: ' . $e->getMessage());
            wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&error=Falha%20no%20salvamento'));
            exit;
        }
    }

    public static function renderAiSync(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        global $wpdb;
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';
        $locationsTable = $wpdb->prefix . 'mapa_politico_locations';

        $rows = $wpdb->get_results(
            "SELECT p.id, p.full_name, p.position, p.data_status, p.source_url, p.source_name, p.created_at, p.last_synced_at,
                    l.city
             FROM {$politiciansTable} p
             LEFT JOIN {$locationsTable} l ON l.id = p.location_id
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT 500",
            ARRAY_A
        );

        $lastSync = get_option('mapa_politico_ai_last_sync', 'nunca');
        $deleteNonce = wp_create_nonce('mapa_politico_delete_records_nonce');
        ?>
        <div class="wrap">
            <h1>Atualização IA Goiás</h1>
            <p>Fonte pública principal: IBGE (municípios de Goiás) + Nominatim para geocodificação institucional.</p>
            <p><strong>Última sincronização:</strong> <?php echo esc_html((string) $lastSync); ?></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('mapa_politico_run_ai_sync'); ?>
                <input type="hidden" name="action" value="mapa_politico_run_ai_sync">
                <?php submit_button('Executar sincronização automática agora', 'primary', 'submit', false); ?>
            </form>

            <hr>
            <h2>Cadastros (manual + IA)</h2>
            <p>Use as opções abaixo para excluir registros individuais, selecionados ou todos os registros.</p>

            <p>
                <button type="button" class="button" id="mp-delete-selected">🗑️ Excluir selecionados</button>
                <button type="button" class="button button-link-delete" id="mp-delete-all">🗑️ Excluir todos</button>
            </p>

            <div id="mp-delete-feedback" class="notice" style="display:none;"></div>

            <table class="widefat striped" id="mp-entries-table">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="mp-select-all"></th>
                        <th>Nome</th>
                        <th>Cargo</th>
                        <th>Município</th>
                        <th>Data de criação</th>
                        <th>Fonte dos dados</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr data-id="<?php echo esc_attr((string) $row['id']); ?>">
                        <td><input type="checkbox" class="mp-select-item" value="<?php echo esc_attr((string) $row['id']); ?>"></td>
                        <td><?php echo esc_html((string) $row['full_name']); ?></td>
                        <td><?php echo esc_html((string) $row['position']); ?></td>
                        <td><?php echo esc_html((string) ($row['city'] ?: 'Não informado')); ?></td>
                        <td><?php echo esc_html((string) $row['created_at']); ?></td>
                        <td>
                            <?php if (!empty($row['source_url'])): ?>
                                <a href="<?php echo esc_url((string) $row['source_url']); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html((string) ($row['source_name'] ?: 'Fonte')); ?>
                                </a>
                            <?php else: ?>
                                <?php echo esc_html((string) ($row['source_name'] ?: 'Cadastro manual')); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=mapa-politico-cadastro&edit=' . absint($row['id']))); ?>">Editar</a>
                            <button type="button" class="button button-link-delete mp-delete-single" data-id="<?php echo esc_attr((string) $row['id']); ?>">🗑️ Excluir individual</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="mp-delete-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99999;align-items:center;justify-content:center;">
            <div style="background:#fff;max-width:520px;width:90%;padding:20px;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,.2);">
                <h2 style="margin-top:0;">Confirmação de exclusão</h2>
                <p id="mp-delete-modal-message" style="margin-bottom:10px;"></p>
                <p><strong>Esta ação é irreversível.</strong></p>
                <div id="mp-delete-all-extra" style="display:none;border:1px solid #ddd;padding:10px;border-radius:6px;margin:12px 0;">
                    <label style="display:block;margin-bottom:8px;">
                        <input type="checkbox" id="mp-double-confirm"> Confirmo que desejo excluir todos os cadastros.
                    </label>
                    <label for="mp-keyword">Digite <strong>EXCLUIR</strong> para confirmar:</label>
                    <input type="text" id="mp-keyword" class="regular-text" autocomplete="off" placeholder="EXCLUIR">
                </div>
                <p>
                    <button type="button" class="button" id="mp-delete-cancel">Cancelar</button>
                    <button type="button" class="button button-primary" id="mp-delete-confirm">Confirmar exclusão</button>
                </p>
            </div>
        </div>

        <script>
            (() => {
                const ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
                const nonce = <?php echo wp_json_encode($deleteNonce); ?>;

                const table = document.getElementById('mp-entries-table');
                const selectAll = document.getElementById('mp-select-all');
                const feedback = document.getElementById('mp-delete-feedback');
                const modal = document.getElementById('mp-delete-modal');
                const modalMessage = document.getElementById('mp-delete-modal-message');
                const modalAllExtra = document.getElementById('mp-delete-all-extra');
                const keywordInput = document.getElementById('mp-keyword');
                const doubleConfirm = document.getElementById('mp-double-confirm');
                const confirmBtn = document.getElementById('mp-delete-confirm');
                const cancelBtn = document.getElementById('mp-delete-cancel');

                let pendingAction = null;

                const selectedIds = () => Array.from(document.querySelectorAll('.mp-select-item:checked')).map((item) => Number(item.value)).filter((n) => Number.isInteger(n) && n > 0);

                const showFeedback = (ok, message) => {
                    feedback.className = ok ? 'notice notice-success' : 'notice notice-error';
                    feedback.style.display = 'block';
                    feedback.innerHTML = `<p>${message}</p>`;
                };

                const removeRows = (ids) => {
                    ids.forEach((id) => {
                        const row = table?.querySelector(`tr[data-id="${id}"]`);
                        if (row) row.remove();
                    });
                };

                const openModal = (action, message) => {
                    pendingAction = action;
                    modalMessage.textContent = message;
                    modalAllExtra.style.display = action.scope === 'all' ? 'block' : 'none';
                    keywordInput.value = '';
                    doubleConfirm.checked = false;
                    modal.style.display = 'flex';
                };

                const closeModal = () => {
                    pendingAction = null;
                    modal.style.display = 'none';
                };

                const sendDelete = async (payload) => {
                    const body = new URLSearchParams({
                        action: 'mapa_politico_delete_records',
                        nonce,
                        scope: payload.scope,
                        ids: JSON.stringify(payload.ids || []),
                        keyword: payload.keyword || '',
                        double_confirm: payload.doubleConfirm ? '1' : '0',
                    });

                    const response = await fetch(ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                        body: body.toString(),
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data?.data?.message || 'Falha ao excluir registros.');
                    }

                    return data.data;
                };

                document.getElementById('mp-delete-selected')?.addEventListener('click', () => {
                    const ids = selectedIds();
                    if (ids.length === 0) {
                        showFeedback(false, 'Selecione pelo menos um cadastro para excluir.');
                        return;
                    }
                    openModal({ scope: 'selected', ids }, `Você está prestes a excluir ${ids.length} cadastro(s) selecionado(s).`);
                });

                document.getElementById('mp-delete-all')?.addEventListener('click', () => {
                    openModal({ scope: 'all', ids: [] }, 'Você está prestes a excluir TODOS os cadastros do sistema.');
                });

                table?.addEventListener('click', (event) => {
                    const button = event.target.closest('.mp-delete-single');
                    if (!button) return;
                    const id = Number(button.getAttribute('data-id'));
                    if (!Number.isInteger(id) || id <= 0) return;
                    openModal({ scope: 'single', ids: [id] }, 'Você está prestes a excluir este cadastro individual.');
                });

                selectAll?.addEventListener('change', () => {
                    document.querySelectorAll('.mp-select-item').forEach((item) => {
                        item.checked = !!selectAll.checked;
                    });
                });

                cancelBtn?.addEventListener('click', closeModal);
                modal?.addEventListener('click', (event) => {
                    if (event.target === modal) closeModal();
                });

                confirmBtn?.addEventListener('click', async () => {
                    if (!pendingAction) return;

                    if (pendingAction.scope === 'all') {
                        if (!doubleConfirm.checked || keywordInput.value.trim().toUpperCase() !== 'EXCLUIR') {
                            showFeedback(false, 'Para excluir todos os registros, marque a confirmação e digite EXCLUIR.');
                            return;
                        }
                    }

                    confirmBtn.disabled = true;
                    try {
                        const result = await sendDelete({
                            scope: pendingAction.scope,
                            ids: pendingAction.ids,
                            keyword: keywordInput.value.trim(),
                            doubleConfirm: doubleConfirm.checked,
                        });

                        if (pendingAction.scope === 'all') {
                            document.querySelectorAll('#mp-entries-table tbody tr').forEach((row) => row.remove());
                        } else {
                            removeRows(result.deleted_ids || pendingAction.ids);
                        }

                        showFeedback(true, `Exclusão concluída. Registros excluídos: ${result.deleted_count}.`);
                        closeModal();
                    } catch (error) {
                        showFeedback(false, error.message || 'Falha ao excluir registros.');
                    } finally {
                        confirmBtn.disabled = false;
                    }
                });
            })();
        </script>
        <?php
    }

    public static function runAiSync(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        check_admin_referer('mapa_politico_run_ai_sync');
        MapaPoliticoAI::runSync();
        wp_safe_redirect(admin_url('admin.php?page=mapa-politico-ia&synced=1'));
        exit;
    }

    public static function updateAutoStatus(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        $id = absint($_POST['id'] ?? 0);
        check_admin_referer('mapa_politico_update_auto_status_' . $id);
        $status = sanitize_text_field(wp_unslash($_POST['status'] ?? 'aguardando_validacao'));
        if (!in_array($status, ['completo', 'incompleto', 'aguardando_validacao', 'rejeitado'], true)) {
            $status = 'aguardando_validacao';
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mapa_politico_politicians';
        $wpdb->update($table, ['data_status' => $status, 'last_synced_at' => current_time('mysql')], ['id' => $id]);

        wp_safe_redirect(admin_url('admin.php?page=mapa-politico-ia'));
        exit;
    }

    public static function ajaxDeleteRecords(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sem permissão.'], 403);
        }

        check_ajax_referer('mapa_politico_delete_records_nonce', 'nonce');

        $scope = sanitize_text_field(wp_unslash($_POST['scope'] ?? 'single'));
        if (!in_array($scope, ['single', 'selected', 'all'], true)) {
            wp_send_json_error(['message' => 'Escopo inválido.'], 400);
        }

        $idsRaw = (string) wp_unslash($_POST['ids'] ?? '[]');
        $idsDecoded = json_decode($idsRaw, true);
        $ids = is_array($idsDecoded) ? wp_parse_id_list($idsDecoded) : [];

        if ($scope === 'single') {
            $ids = array_slice($ids, 0, 1);
        }

        if ($scope !== 'all' && empty($ids)) {
            wp_send_json_error(['message' => 'Nenhum registro válido foi informado para exclusão.'], 400);
        }

        if ($scope === 'all') {
            $keyword = strtoupper(sanitize_text_field(wp_unslash($_POST['keyword'] ?? '')));
            $doubleConfirm = sanitize_text_field(wp_unslash($_POST['double_confirm'] ?? '0')) === '1';
            if (!$doubleConfirm || $keyword !== 'EXCLUIR') {
                wp_send_json_error(['message' => 'Confirmação dupla obrigatória para excluir todos os registros.'], 400);
            }
        }

        $result = self::deletePoliticians($ids, $scope);
        if (($result['deleted_count'] ?? 0) < 1) {
            wp_send_json_error(['message' => 'Nenhum registro foi excluído.'], 400);
        }

        wp_send_json_success($result);
    }

    public static function deleteEntry(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        $politicianId = absint($_POST['politician_id'] ?? 0);
        check_admin_referer('mapa_politico_delete_entry_' . $politicianId);

        $result = self::deletePoliticians([$politicianId], 'single');
        if (($result['deleted_count'] ?? 0) < 1) {
            wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&error=Registro%20n%C3%A3o%20encontrado'));
            exit;
        }

        wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&deleted=1'));
        exit;
    }

    private static function deletePoliticians(array $ids, string $scope): array
    {
        global $wpdb;
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';
        $locationsTable = $wpdb->prefix . 'mapa_politico_locations';

        if ($scope === 'all') {
            $targets = $wpdb->get_results("SELECT id, location_id, photo_id FROM {$politiciansTable}", ARRAY_A);
        } else {
            $ids = wp_parse_id_list($ids);
            if (empty($ids)) {
                return ['deleted_count' => 0, 'deleted_ids' => []];
            }

            $in = implode(',', array_map('intval', $ids));
            $targets = $wpdb->get_results("SELECT id, location_id, photo_id FROM {$politiciansTable} WHERE id IN ({$in})", ARRAY_A);
        }

        if (empty($targets)) {
            return ['deleted_count' => 0, 'deleted_ids' => []];
        }

        $targetIds = array_map('intval', array_column($targets, 'id'));
        $locationIds = array_values(array_unique(array_map('intval', array_column($targets, 'location_id'))));
        $photoIds = array_values(array_unique(array_filter(array_map('intval', array_column($targets, 'photo_id')))));

        $wpdb->query('START TRANSACTION');

        try {
            $inTarget = implode(',', $targetIds);
            $deleted = $wpdb->query("DELETE FROM {$politiciansTable} WHERE id IN ({$inTarget})");
            if ($deleted === false) {
                throw new RuntimeException('Falha ao excluir políticos: ' . $wpdb->last_error);
            }

            foreach ($locationIds as $locationId) {
                if ($locationId < 1) {
                    continue;
                }

                $remaining = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$politiciansTable} WHERE location_id = %d", $locationId));
                if ($remaining === 0) {
                    $wpdb->delete($locationsTable, ['id' => $locationId]);
                }
            }

            foreach ($photoIds as $photoId) {
                $remainingPhotoUsage = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$politiciansTable} WHERE photo_id = %d", $photoId));
                if ($remainingPhotoUsage === 0) {
                    wp_delete_attachment($photoId, true);
                }
            }

            $wpdb->query('COMMIT');

            self::registerDeletionLog($scope, $targetIds, (int) $deleted);

            return [
                'deleted_count' => (int) $deleted,
                'deleted_ids' => $targetIds,
            ];
        } catch (Throwable $e) {
            $wpdb->query('ROLLBACK');
            error_log('[MapaPolitico] deletePoliticians error: ' . $e->getMessage());
            return ['deleted_count' => 0, 'deleted_ids' => []];
        }
    }

    private static function registerDeletionLog(string $scope, array $deletedIds, int $deletedCount): void
    {
        $user = wp_get_current_user();
        $logs = get_option('mapa_politico_deletion_logs', []);
        if (!is_array($logs)) {
            $logs = [];
        }

        $logs[] = [
            'scope' => $scope,
            'deleted_count' => $deletedCount,
            'deleted_ids' => array_values(array_map('intval', $deletedIds)),
            'deleted_at' => current_time('mysql'),
            'user_id' => (int) $user->ID,
            'user_login' => (string) ($user->user_login ?? ''),
        ];

        if (count($logs) > 200) {
            $logs = array_slice($logs, -200);
        }

        update_option('mapa_politico_deletion_logs', $logs, false);
    }
}
