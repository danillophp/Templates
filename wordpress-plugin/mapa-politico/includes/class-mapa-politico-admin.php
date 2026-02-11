<?php

if (!defined('ABSPATH')) {
    exit;
}

class MapaPoliticoAdmin
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerMenu']);
        add_action('admin_post_mapa_politico_save_settings', [self::class, 'saveSettings']);

        add_action('admin_post_mapa_politico_save_location', [self::class, 'saveLocation']);
        add_action('admin_post_mapa_politico_delete_location', [self::class, 'deleteLocation']);

        add_action('admin_post_mapa_politico_save_politician', [self::class, 'savePolitician']);
        add_action('admin_post_mapa_politico_delete_politician', [self::class, 'deletePolitician']);
    }

    public static function registerMenu(): void
    {
        add_menu_page('Mapa Político', 'Mapa Político', 'manage_options', 'mapa-politico', [self::class, 'renderDashboard'], 'dashicons-location-alt', 26);
        add_submenu_page('mapa-politico', 'Configurações', 'Configurações', 'manage_options', 'mapa-politico', [self::class, 'renderDashboard']);
        add_submenu_page('mapa-politico', 'Localizações', 'Localizações', 'manage_options', 'mapa-politico-locations', [self::class, 'renderLocations']);
        add_submenu_page('mapa-politico', 'Políticos', 'Políticos', 'manage_options', 'mapa-politico-politicians', [self::class, 'renderPoliticians']);
    }

    public static function renderDashboard(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        $apiKey = get_option('mapa_politico_google_maps_api_key', '');
        ?>
        <div class="wrap">
            <h1>Mapa Político - Configurações</h1>
            <p>Use o shortcode <code>[mapa_politico]</code> em qualquer página/post para exibir o mapa.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('mapa_politico_save_settings'); ?>
                <input type="hidden" name="action" value="mapa_politico_save_settings">
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="google_maps_api_key">Google Maps API Key</label></th>
                        <td><input id="google_maps_api_key" class="regular-text" type="text" name="google_maps_api_key" value="<?php echo esc_attr($apiKey); ?>"></td>
                    </tr>
                </table>
                <?php submit_button('Salvar configurações'); ?>
            </form>
        </div>
        <?php
    }

    public static function saveSettings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        check_admin_referer('mapa_politico_save_settings');

        $apiKey = isset($_POST['google_maps_api_key']) ? sanitize_text_field(wp_unslash($_POST['google_maps_api_key'])) : '';
        update_option('mapa_politico_google_maps_api_key', $apiKey);

        wp_safe_redirect(admin_url('admin.php?page=mapa-politico&updated=1'));
        exit;
    }

    public static function renderLocations(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapa_politico_locations';
        $items = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A);

        $editId = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $editing = null;
        if ($editId > 0) {
            $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $editId), ARRAY_A);
        }
        ?>
        <div class="wrap">
            <h1>Localizações</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('mapa_politico_save_location'); ?>
                <input type="hidden" name="action" value="mapa_politico_save_location">
                <input type="hidden" name="id" value="<?php echo esc_attr((string) ($editing['id'] ?? '')); ?>">
                <table class="form-table" role="presentation">
                    <tr><th>Nome do local</th><td><input required class="regular-text" name="name" value="<?php echo esc_attr($editing['name'] ?? ''); ?>"></td></tr>
                    <tr><th>Endereço</th><td><input required class="regular-text" name="address" value="<?php echo esc_attr($editing['address'] ?? ''); ?>"></td></tr>
                    <tr><th>CEP</th><td><input class="regular-text" name="postal_code" value="<?php echo esc_attr($editing['postal_code'] ?? ''); ?>"></td></tr>
                    <tr><th>Latitude</th><td><input required step="0.000001" type="number" name="latitude" value="<?php echo esc_attr((string) ($editing['latitude'] ?? '')); ?>"></td></tr>
                    <tr><th>Longitude</th><td><input required step="0.000001" type="number" name="longitude" value="<?php echo esc_attr((string) ($editing['longitude'] ?? '')); ?>"></td></tr>
                    <tr><th>Informações do município</th><td><textarea class="large-text" rows="3" name="city_info"><?php echo esc_textarea($editing['city_info'] ?? ''); ?></textarea></td></tr>
                    <tr><th>Informações da região</th><td><textarea class="large-text" rows="3" name="region_info"><?php echo esc_textarea($editing['region_info'] ?? ''); ?></textarea></td></tr>
                </table>
                <?php submit_button($editing ? 'Atualizar localização' : 'Adicionar localização'); ?>
            </form>

            <hr>
            <h2>Registros</h2>
            <table class="widefat striped">
                <thead><tr><th>ID</th><th>Nome</th><th>Endereço</th><th>Coordenadas</th><th>Ações</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo esc_html((string) $item['id']); ?></td>
                        <td><?php echo esc_html($item['name']); ?></td>
                        <td><?php echo esc_html($item['address']); ?></td>
                        <td><?php echo esc_html($item['latitude'] . ', ' . $item['longitude']); ?></td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=mapa-politico-locations&edit=' . absint($item['id']))); ?>">Editar</a>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                                <?php wp_nonce_field('mapa_politico_delete_location_' . absint($item['id'])); ?>
                                <input type="hidden" name="action" value="mapa_politico_delete_location">
                                <input type="hidden" name="id" value="<?php echo esc_attr((string) $item['id']); ?>">
                                <button class="button button-link-delete" type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function saveLocation(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }
        check_admin_referer('mapa_politico_save_location');

        global $wpdb;
        $table = $wpdb->prefix . 'mapa_politico_locations';

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $data = [
            'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'address' => sanitize_text_field(wp_unslash($_POST['address'] ?? '')),
            'postal_code' => sanitize_text_field(wp_unslash($_POST['postal_code'] ?? '')),
            'latitude' => (float) ($_POST['latitude'] ?? 0),
            'longitude' => (float) ($_POST['longitude'] ?? 0),
            'city_info' => sanitize_textarea_field(wp_unslash($_POST['city_info'] ?? '')),
            'region_info' => sanitize_textarea_field(wp_unslash($_POST['region_info'] ?? '')),
        ];

        if ($id > 0) {
            $wpdb->update($table, $data, ['id' => $id]);
        } else {
            $wpdb->insert($table, $data);
        }

        wp_safe_redirect(admin_url('admin.php?page=mapa-politico-locations'));
        exit;
    }

    public static function deleteLocation(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        check_admin_referer('mapa_politico_delete_location_' . $id);

        global $wpdb;
        $locations = $wpdb->prefix . 'mapa_politico_locations';
        $politicians = $wpdb->prefix . 'mapa_politico_politicians';

        $wpdb->delete($politicians, ['location_id' => $id]);
        $wpdb->delete($locations, ['id' => $id]);

        wp_safe_redirect(admin_url('admin.php?page=mapa-politico-locations'));
        exit;
    }

    public static function renderPoliticians(): void
    {
        global $wpdb;
        $locationsTable = $wpdb->prefix . 'mapa_politico_locations';
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';

        $locations = $wpdb->get_results("SELECT id, name FROM {$locationsTable} ORDER BY name ASC", ARRAY_A);
        $items = $wpdb->get_results("SELECT p.*, l.name AS location_name FROM {$politiciansTable} p LEFT JOIN {$locationsTable} l ON l.id = p.location_id ORDER BY p.id DESC", ARRAY_A);

        $editId = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $editing = null;
        if ($editId > 0) {
            $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$politiciansTable} WHERE id = %d", $editId), ARRAY_A);
        }
        ?>
        <div class="wrap">
            <h1>Políticos</h1>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('mapa_politico_save_politician'); ?>
                <input type="hidden" name="action" value="mapa_politico_save_politician">
                <input type="hidden" name="id" value="<?php echo esc_attr((string) ($editing['id'] ?? '')); ?>">
                <table class="form-table" role="presentation">
                    <tr><th>Localização</th><td><select required name="location_id"><option value="">Selecione</option><?php foreach ($locations as $location): ?><option value="<?php echo esc_attr((string) $location['id']); ?>" <?php selected((int) ($editing['location_id'] ?? 0), (int) $location['id']); ?>><?php echo esc_html($location['name']); ?></option><?php endforeach; ?></select></td></tr>
                    <tr><th>Nome completo</th><td><input required class="regular-text" name="full_name" value="<?php echo esc_attr($editing['full_name'] ?? ''); ?>"></td></tr>
                    <tr><th>Cargo político</th><td><input required class="regular-text" name="position" value="<?php echo esc_attr($editing['position'] ?? ''); ?>"></td></tr>
                    <tr><th>Partido</th><td><input required class="regular-text" name="party" value="<?php echo esc_attr($editing['party'] ?? ''); ?>"></td></tr>
                    <tr><th>Idade</th><td><input type="number" min="18" name="age" value="<?php echo esc_attr((string) ($editing['age'] ?? '')); ?>"></td></tr>
                    <tr><th>Biografia</th><td><textarea class="large-text" rows="3" name="biography"><?php echo esc_textarea($editing['biography'] ?? ''); ?></textarea></td></tr>
                    <tr><th>Histórico da carreira</th><td><textarea class="large-text" rows="3" name="career_history"><?php echo esc_textarea($editing['career_history'] ?? ''); ?></textarea></td></tr>
                    <tr><th>História município/região</th><td><textarea class="large-text" rows="3" name="municipality_history"><?php echo esc_textarea($editing['municipality_history'] ?? ''); ?></textarea></td></tr>
                    <tr><th>Telefone</th><td><input class="regular-text" name="phone" value="<?php echo esc_attr($editing['phone'] ?? ''); ?>"></td></tr>
                    <tr><th>E-mail</th><td><input type="email" class="regular-text" name="email" value="<?php echo esc_attr($editing['email'] ?? ''); ?>"></td></tr>
                    <tr><th>Assessores</th><td><input class="regular-text" name="advisors" value="<?php echo esc_attr($editing['advisors'] ?? ''); ?>"></td></tr>
                    <tr><th>Foto</th><td><input type="file" name="photo" accept="image/png,image/jpeg,image/webp"></td></tr>
                </table>
                <?php submit_button($editing ? 'Atualizar político' : 'Adicionar político'); ?>
            </form>

            <hr>
            <h2>Registros</h2>
            <table class="widefat striped">
                <thead><tr><th>ID</th><th>Nome</th><th>Cargo</th><th>Partido</th><th>Local</th><th>Ações</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo esc_html((string) $item['id']); ?></td>
                        <td><?php echo esc_html($item['full_name']); ?></td>
                        <td><?php echo esc_html($item['position']); ?></td>
                        <td><?php echo esc_html($item['party']); ?></td>
                        <td><?php echo esc_html($item['location_name'] ?? ''); ?></td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=mapa-politico-politicians&edit=' . absint($item['id']))); ?>">Editar</a>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                                <?php wp_nonce_field('mapa_politico_delete_politician_' . absint($item['id'])); ?>
                                <input type="hidden" name="action" value="mapa_politico_delete_politician">
                                <input type="hidden" name="id" value="<?php echo esc_attr((string) $item['id']); ?>">
                                <button class="button button-link-delete" type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function savePolitician(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        check_admin_referer('mapa_politico_save_politician');

        global $wpdb;
        $table = $wpdb->prefix . 'mapa_politico_politicians';

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        $photoId = null;
        if (!empty($_FILES['photo']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $photoId = media_handle_upload('photo', 0);
            if (is_wp_error($photoId)) {
                $photoId = null;
            }
        }

        $data = [
            'location_id' => absint($_POST['location_id'] ?? 0),
            'full_name' => sanitize_text_field(wp_unslash($_POST['full_name'] ?? '')),
            'position' => sanitize_text_field(wp_unslash($_POST['position'] ?? '')),
            'party' => sanitize_text_field(wp_unslash($_POST['party'] ?? '')),
            'age' => absint($_POST['age'] ?? 0),
            'biography' => sanitize_textarea_field(wp_unslash($_POST['biography'] ?? '')),
            'career_history' => sanitize_textarea_field(wp_unslash($_POST['career_history'] ?? '')),
            'municipality_history' => sanitize_textarea_field(wp_unslash($_POST['municipality_history'] ?? '')),
            'phone' => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
            'email' => sanitize_email(wp_unslash($_POST['email'] ?? '')),
            'advisors' => sanitize_text_field(wp_unslash($_POST['advisors'] ?? '')),
        ];

        if ($photoId) {
            $data['photo_id'] = $photoId;
        }

        if ($id > 0) {
            $wpdb->update($table, $data, ['id' => $id]);
        } else {
            $wpdb->insert($table, $data);
        }

        wp_safe_redirect(admin_url('admin.php?page=mapa-politico-politicians'));
        exit;
    }

    public static function deletePolitician(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        check_admin_referer('mapa_politico_delete_politician_' . $id);

        global $wpdb;
        $table = $wpdb->prefix . 'mapa_politico_politicians';
        $wpdb->delete($table, ['id' => $id]);

        wp_safe_redirect(admin_url('admin.php?page=mapa-politico-politicians'));
        exit;
    }
}
