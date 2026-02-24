<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_admin_logs_init(): void
{
    // página renderizada pelo menu
}

function mpg_render_admin_logs_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão.');
    }

    $logs = get_option('mpg_ai_logs', []);
    if (!is_array($logs)) {
        $logs = [];
    }
    $logs = array_reverse(array_slice($logs, -300));
    ?>
    <div class="wrap">
        <h1>Logs da IA</h1>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Município</th>
                    <th>Etapa</th>
                    <th>Motivo</th>
                    <th>Fontes</th>
                    <th>Quando</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6">Sem logs no momento.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html((string) ($log['type'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($log['municipio'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($log['etapa'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($log['motivo'] ?? '')); ?></td>
                            <td style="max-width:360px;overflow-wrap:anywhere;"><?php echo esc_html((string) ($log['fontes'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($log['quando'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
