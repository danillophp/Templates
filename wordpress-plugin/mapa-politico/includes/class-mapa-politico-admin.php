<?php

if (!defined('ABSPATH')) {
    exit;
}

class MapaPoliticoAdmin
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);

        add_action('admin_post_mapa_politico_save_entry', [self::class, 'saveEntry']);
        add_action('admin_post_mapa_politico_delete_entry', [self::class, 'deleteEntry']);

        add_action('wp_ajax_mapa_politico_geocode_address', [self::class, 'ajaxGeocodeAddress']);

        add_action('admin_notices', [self::class, 'renderNotices']);
    }

    public static function registerMenu(): void
    {
        add_menu_page(
            'Mapa Político',
            'Mapa Político',
            'manage_options',
            'mapa-politico-cadastro',
            [self::class, 'renderUnifiedForm'],
            'dashicons-location-alt',
            26
        );

        add_submenu_page('mapa-politico-cadastro', 'Cadastro Manual', 'Cadastro Manual', 'manage_options', 'mapa-politico-cadastro', [self::class, 'renderUnifiedForm']);
        add_submenu_page('mapa-politico-cadastro', 'Logs da IA', 'Logs da IA', 'manage_options', 'mapa-politico-logs', [self::class, 'renderLogs']);
    }

    public static function enqueueAssets(string $hook): void
    {
        if ($hook !== 'toplevel_page_mapa-politico-cadastro') {
            return;
        }

        wp_enqueue_style('mapa-politico-admin-leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        wp_enqueue_script('mapa-politico-admin-leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

        wp_enqueue_script(
            'mapa-politico-admin-cadastro-js',
            MAPA_POLITICO_URL . 'assets/js/mapa-politico-admin.js',
            ['mapa-politico-admin-leaflet-js'],
            MAPA_POLITICO_VERSION,
            true
        );

        wp_localize_script('mapa-politico-admin-cadastro-js', 'MapaPoliticoAdminConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mapa_politico_admin_nonce'),
            'defaultLat' => -15.827,
            'defaultLng' => -49.8362,
            'defaultZoom' => 7,
        ]);
    }

    public static function renderNotices(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Cadastro salvo com sucesso.</p></div>';
        }

        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Cadastro removido com sucesso.</p></div>';
        }

        if (isset($_GET['error'])) {
            echo '<div class="notice notice-error"><p>' . esc_html((string) wp_unslash($_GET['error'])) . '</p></div>';
        }

        if (!MapaPoliticoAI::hasApiKey()) {
            echo '<div class="notice notice-warning"><p>'
                . esc_html__('Mapa Político: defina a chave da OpenAI em MAPA_POLITICO_OPENAI_API_KEY (wp-config.php) ou na variável de ambiente OPENAI_API_KEY para usar o botão de IA.', 'mapa-politico')
                . '</p></div>';
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

        $entries = $wpdb->get_results(
            "SELECT p.id AS politician_id, p.full_name, p.position, p.party, p.phone,
                    l.city, l.state, l.postal_code, l.latitude, l.longitude
             FROM {$politiciansTable} p
             INNER JOIN {$locationsTable} l ON l.id = p.location_id
             ORDER BY p.id DESC LIMIT 200",
            ARRAY_A
        );
        ?>
        <div class="wrap">
            <h1>Cadastro Manual de Político</h1>
            <p>Use o botão <strong>📍 Atualizar localização no mapa</strong> para geocodificar por CEP ou por cidade/estado. Depois ajuste manualmente o marcador se necessário.</p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('mapa_politico_save_entry'); ?>
                <input type="hidden" name="action" value="mapa_politico_save_entry">

                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="full_name">Nome completo do político</label></th>
                        <td style="display:flex;gap:8px;align-items:center;">
                            <input required class="regular-text" id="full_name" name="full_name">
                            <button type="button" class="button" id="mp-search-ai">🔍 Pesquisar informações com IA</button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="position">Cargo</label></th>
                        <td>
                            <select id="position" name="position">
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
                    <tr><th><label for="city">Cidade</label></th><td><input required class="regular-text" id="city" name="city"></td></tr>
                    <tr><th><label for="state">Estado</label></th><td><input class="regular-text" id="state" name="state" value="GO"></td></tr>
                    <tr><th><label for="postal_code">CEP</label></th><td><input class="regular-text" id="postal_code" name="postal_code"></td></tr>
                    <tr><th><label for="address_street">Rua / Quadra</label></th><td><input required class="regular-text" id="address_street" name="address_street"></td></tr>
                    <tr><th><label for="address_lot">Lote</label></th><td><input required class="regular-text" id="address_lot" name="address_lot"></td></tr>
                    <tr><th><label for="party">Partido</label></th><td><input class="regular-text" id="party" name="party"></td></tr>
                    <tr><th><label for="phone">Telefone</label></th><td><input class="regular-text" id="phone" name="phone"></td></tr>
                    <tr><th><label for="latitude_display">Latitude</label></th><td><input readonly class="regular-text" id="latitude_display"></td></tr>
                    <tr><th><label for="longitude_display">Longitude</label></th><td><input readonly class="regular-text" id="longitude_display"></td></tr>
                    <tr>
                        <th>Pré-visualização do mapa</th>
                        <td>
                            <p>
                                <button type="button" class="button" id="mp-find-location">📍 Atualizar localização no mapa</button>
                                <span id="mp-geo-feedback" style="margin-left:8px;"></span>
                            </p>
                            <div id="mp-admin-map" style="height:360px;max-width:900px;border:1px solid #dcdcde;border-radius:8px;"></div>
                            <p><em>Você pode arrastar o marcador para ajustar latitude/longitude manualmente.</em></p>
                        </td>
                    </tr>
                    <tr><th><label for="photo">Foto (upload manual)</label></th><td><input type="file" id="photo" name="photo" accept="image/png,image/jpeg,image/webp"></td></tr>
                    <tr><th><label for="biography">Biografia</label></th><td><textarea class="large-text" rows="4" id="biography" name="biography"></textarea></td></tr>
                    <tr><th><label for="career_history">Histórico político</label></th><td><textarea class="large-text" rows="5" id="career_history" name="career_history"></textarea></td></tr>
                </table>

                <input type="hidden" id="latitude" name="latitude" value="">
                <input type="hidden" id="longitude" name="longitude" value="">

                <?php submit_button('Salvar cadastro'); ?>
            </form>

            <h2>Registros</h2>
            <table class="widefat striped">
                <thead><tr><th>Nome</th><th>Cargo</th><th>Cidade</th><th>Estado</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?php echo esc_html((string) $entry['full_name']); ?></td>
                            <td><?php echo esc_html((string) $entry['position']); ?></td>
                            <td><?php echo esc_html((string) $entry['city']); ?></td>
                            <td><?php echo esc_html((string) $entry['state']); ?></td>
                            <td>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <?php wp_nonce_field('mapa_politico_delete_entry_' . absint($entry['politician_id'])); ?>
                                    <input type="hidden" name="action" value="mapa_politico_delete_entry">
                                    <input type="hidden" name="politician_id" value="<?php echo esc_attr((string) $entry['politician_id']); ?>">
                                    <button class="button button-link-delete">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function ajaxGeocodeAddress(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sem permissão.'], 403);
        }

        check_ajax_referer('mapa_politico_admin_nonce', 'nonce');

        $userId = get_current_user_id();
        $rateKey = 'mapa_politico_geo_rate_' . (string) $userId;
        $last = (int) get_transient($rateKey);
        $now = time();
        if ($last > 0 && ($now - $last) < 2) {
            wp_send_json_error(['message' => 'Aguarde um instante antes de nova consulta.'], 429);
        }
        set_transient($rateKey, $now, 10);

        $address = sanitize_text_field(wp_unslash($_POST['address'] ?? ''));
        if ($address === '') {
            wp_send_json_error(['message' => 'Endereço vazio para geocodificação.'], 400);
        }

        $cacheKey = 'mapa_politico_geo_cache_' . md5($address);
        $cached = get_transient($cacheKey);
        if (is_array($cached) && isset($cached['lat'], $cached['lng'])) {
            wp_send_json_success($cached);
        }

        $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . rawurlencode($address);
        $response = wp_remote_get($url, [
            'timeout' => 12,
            'headers' => [
                'User-Agent' => 'MapaPolitico/' . MAPA_POLITICO_VERSION . ' (' . home_url('/') . ')',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.7',
            ],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Falha de conexão com serviço de geocodificação.'], 400);
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);
        if ($statusCode < 200 || $statusCode >= 300) {
            wp_send_json_error(['message' => 'Serviço de geocodificação indisponível no momento.'], 400);
        }

        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($json) || empty($json[0]['lat']) || empty($json[0]['lon'])) {
            wp_send_json_error(['message' => 'Endereço não encontrado. Ajuste os dados ou posicione no mapa manualmente.'], 404);
        }

        $result = [
            'lat' => (float) $json[0]['lat'],
            'lng' => (float) $json[0]['lon'],
        ];

        set_transient($cacheKey, $result, DAY_IN_SECONDS);

        wp_send_json_success($result);
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

        $fullName = sanitize_text_field(wp_unslash($_POST['full_name'] ?? ''));
        $position = sanitize_text_field(wp_unslash($_POST['position'] ?? ''));
        $party = sanitize_text_field(wp_unslash($_POST['party'] ?? ''));
        $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        $city = sanitize_text_field(wp_unslash($_POST['city'] ?? ''));
        $state = sanitize_text_field(wp_unslash($_POST['state'] ?? 'GO'));
        $postalCode = sanitize_text_field(wp_unslash($_POST['postal_code'] ?? ''));
        $street = sanitize_text_field(wp_unslash($_POST['address_street'] ?? ''));
        $lot = sanitize_text_field(wp_unslash($_POST['address_lot'] ?? ''));
        $address = trim($street . ($lot !== '' ? ' - Lote ' . $lot : ''));
        $latitude = (float) ($_POST['latitude'] ?? 0);
        $longitude = (float) ($_POST['longitude'] ?? 0);

        if ($fullName === '' || $position === '' || $city === '' || $street === '' || $lot === '' || !is_finite($latitude) || !is_finite($longitude)) {
            wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&error=Campos obrigatórios inválidos.'));
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

        $wpdb->insert($locationsTable, [
            'city' => $city,
            'state' => $state,
            'postal_code' => $postalCode,
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        if ($wpdb->insert_id < 1) {
            wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&error=Falha ao salvar localização.'));
            exit;
        }

        $locationId = (int) $wpdb->insert_id;

        $wpdb->insert($politiciansTable, [
            'location_id' => $locationId,
            'full_name' => $fullName,
            'position' => $position,
            'party' => $party,
            'phone' => $phone,
            'biography' => sanitize_textarea_field(wp_unslash($_POST['biography'] ?? '')),
            'career_history' => sanitize_textarea_field(wp_unslash($_POST['career_history'] ?? '')),
            'email' => sanitize_email(wp_unslash($_POST['email'] ?? '')),
            'photo_id' => $photoId,
        ]);

        if ($wpdb->insert_id < 1) {
            wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&error=Falha ao salvar político.'));
            exit;
        }

        wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&saved=1'));
        exit;
    }

    public static function deleteEntry(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        $politicianId = absint($_POST['politician_id'] ?? 0);
        check_admin_referer('mapa_politico_delete_entry_' . $politicianId);

        global $wpdb;
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';

        $wpdb->delete($politiciansTable, ['id' => $politicianId]);
        wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&deleted=1'));
        exit;
    }

    public static function renderLogs(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        $logs = get_option('mapa_politico_ai_logs', []);
        if (!is_array($logs)) {
            $logs = [];
        }

        $logs = array_reverse(array_slice($logs, -300));
        ?>
        <div class="wrap">
            <h1>Logs da IA</h1>
            <table class="widefat striped">
                <thead><tr><th>Tipo</th><th>Cidade</th><th>Etapa</th><th>Motivo</th><th>Fontes</th><th>Quando</th></tr></thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6">Sem logs.</td></tr>
                    <?php else: foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html((string) ($log['type'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($log['city'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($log['step'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($log['reason'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($log['sources'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($log['created_at'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
