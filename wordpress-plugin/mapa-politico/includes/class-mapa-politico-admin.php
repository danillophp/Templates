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
    }

    public static function registerMenu(): void
    {
        add_menu_page('Mapa Político', 'Mapa Político', 'manage_options', 'mapa-politico', [self::class, 'renderDashboard'], 'dashicons-location-alt', 26);
        add_submenu_page('mapa-politico', 'Visão geral', 'Visão geral', 'manage_options', 'mapa-politico', [self::class, 'renderDashboard']);
        add_submenu_page('mapa-politico', 'Cadastro Unificado', 'Cadastro Unificado', 'manage_options', 'mapa-politico-cadastro', [self::class, 'renderUnifiedForm']);
    }

    public static function renderDashboard(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }
        ?>
        <div class="wrap">
            <h1>Mapa Político</h1>
            <p>Fluxo simplificado para usuários leigos:</p>
            <ol>
                <li>Acesse <strong>Cadastro Unificado</strong>.</li>
                <li>Preencha dados do político e localização na mesma tela.</li>
                <li>Use o mapa para definir latitude/longitude com clique.</li>
                <li>Publique o shortcode <code>[mapa_politico]</code> no front-end.</li>
            </ol>
        </div>
        <?php
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
            "SELECT p.id AS politician_id, p.full_name, p.position, p.party,
                    l.city, l.state, l.postal_code, l.latitude, l.longitude
             FROM {$politiciansTable} p
             INNER JOIN {$locationsTable} l ON l.id = p.location_id
             ORDER BY p.id DESC",
            ARRAY_A
        );

        $leafletCss = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        $leafletJs = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        ?>
        <div class="wrap">
            <h1>Cadastro Unificado (Político + Localização)</h1>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('mapa_politico_save_entry'); ?>
                <input type="hidden" name="action" value="mapa_politico_save_entry">
                <input type="hidden" name="politician_id" value="<?php echo esc_attr((string) ($editing['id'] ?? '')); ?>">

                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="full_name">Nome do político</label></th>
                        <td><input required class="regular-text" id="full_name" name="full_name" value="<?php echo esc_attr($editing['full_name'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="position">Cargo</label></th>
                        <td><input required class="regular-text" id="position" name="position" value="<?php echo esc_attr($editing['position'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="party">Partido</label></th>
                        <td><input required class="regular-text" id="party" name="party" value="<?php echo esc_attr($editing['party'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="city">Cidade</label></th>
                        <td><input required class="regular-text" id="city" name="city" value="<?php echo esc_attr($editing['city'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="state">Estado</label></th>
                        <td><input class="regular-text" id="state" name="state" value="<?php echo esc_attr($editing['state'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="postal_code">CEP</label></th>
                        <td><input class="regular-text" id="postal_code" name="postal_code" value="<?php echo esc_attr($editing['postal_code'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="latitude">Latitude</label></th>
                        <td><input required type="number" step="0.000001" id="latitude" name="latitude" value="<?php echo esc_attr((string) ($editing['latitude'] ?? '')); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="longitude">Longitude</label></th>
                        <td><input required type="number" step="0.000001" id="longitude" name="longitude" value="<?php echo esc_attr((string) ($editing['longitude'] ?? '')); ?>"></td>
                    </tr>
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
                    <tr>
                        <th><label for="photo">Foto</label></th>
                        <td><input type="file" id="photo" name="photo" accept="image/png,image/jpeg,image/webp"></td>
                    </tr>
                    <tr>
                        <th><label for="biography">Biografia</label></th>
                        <td><textarea class="large-text" rows="3" id="biography" name="biography"><?php echo esc_textarea($editing['biography'] ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="career_history">Histórico</label></th>
                        <td><textarea class="large-text" rows="3" id="career_history" name="career_history"><?php echo esc_textarea($editing['career_history'] ?? ''); ?></textarea></td>
                    </tr>
                </table>

                <?php submit_button($editing ? 'Atualizar cadastro' : 'Salvar cadastro'); ?>
            </form>

            <hr>

            <h2>Registros cadastrados</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Nome</th><th>Cargo</th><th>Partido</th><th>Cidade</th><th>Estado</th><th>CEP</th><th>Latitude</th><th>Longitude</th><th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?php echo esc_html($entry['full_name']); ?></td>
                        <td><?php echo esc_html($entry['position']); ?></td>
                        <td><?php echo esc_html($entry['party']); ?></td>
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

        <link rel="stylesheet" href="<?php echo esc_url($leafletCss); ?>" />
        <script src="<?php echo esc_url($leafletJs); ?>"></script>
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

                const initialLat = Number(latInput?.value || -14.235);
                const initialLng = Number(lngInput?.value || -51.9253);
                const initialZoom = (latInput?.value && lngInput?.value) ? 12 : 4;

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
        $city = sanitize_text_field(wp_unslash($_POST['city'] ?? ''));
        $state = sanitize_text_field(wp_unslash($_POST['state'] ?? ''));
        $postalCode = sanitize_text_field(wp_unslash($_POST['postal_code'] ?? ''));
        $latitude = (float) ($_POST['latitude'] ?? 0);
        $longitude = (float) ($_POST['longitude'] ?? 0);

        if ($fullName === '' || $position === '' || $party === '' || $city === '' || $latitude === 0.0 || $longitude === 0.0) {
            wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro&error=1'));
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

        if ($politicianId > 0) {
            $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$politiciansTable} WHERE id = %d", $politicianId), ARRAY_A);
            if (!$existing) {
                wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro'));
                exit;
            }

            $locationId = (int) $existing['location_id'];

            $wpdb->update($locationsTable, [
                'name' => $city,
                'city' => $city,
                'state' => $state,
                'postal_code' => $postalCode,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => trim($city . ($state ? ' - ' . $state : '')),
            ], ['id' => $locationId]);

            $politicianData = [
                'full_name' => $fullName,
                'position' => $position,
                'party' => $party,
                'biography' => sanitize_textarea_field(wp_unslash($_POST['biography'] ?? '')),
                'career_history' => sanitize_textarea_field(wp_unslash($_POST['career_history'] ?? '')),
            ];
            if ($photoId) {
                $politicianData['photo_id'] = $photoId;
            }

            $wpdb->update($politiciansTable, $politicianData, ['id' => $politicianId]);
        } else {
            $wpdb->insert($locationsTable, [
                'name' => $city,
                'city' => $city,
                'state' => $state,
                'postal_code' => $postalCode,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => trim($city . ($state ? ' - ' . $state : '')),
            ]);

            $locationId = (int) $wpdb->insert_id;

            $wpdb->insert($politiciansTable, [
                'location_id' => $locationId,
                'full_name' => $fullName,
                'position' => $position,
                'party' => $party,
                'biography' => sanitize_textarea_field(wp_unslash($_POST['biography'] ?? '')),
                'career_history' => sanitize_textarea_field(wp_unslash($_POST['career_history'] ?? '')),
                'photo_id' => $photoId,
            ]);
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
        $locationsTable = $wpdb->prefix . 'mapa_politico_locations';
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';

        $existing = $wpdb->get_row($wpdb->prepare("SELECT location_id FROM {$politiciansTable} WHERE id = %d", $politicianId), ARRAY_A);
        if ($existing) {
            $locationId = (int) $existing['location_id'];
            $wpdb->delete($politiciansTable, ['id' => $politicianId]);
            $wpdb->delete($locationsTable, ['id' => $locationId]);
        }

        wp_safe_redirect(admin_url('admin.php?page=mapa-politico-cadastro'));
        exit;
    }
}
