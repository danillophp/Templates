<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Logger;
use App\Models\Appointment;
use App\Models\Notification;

require_once __DIR__ . '/../app/helpers/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $segments = explode('\\', $relativeClass);
    $segments[0] = strtolower($segments[0]);
    $file = $baseDir . implode('/', $segments) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

$config = require __DIR__ . '/../app/config/config.php';
$dbConfig = require __DIR__ . '/../app/config/database.php';

date_default_timezone_set($config['app']['timezone']);

$db = Database::getConnection($dbConfig);
$appointmentModel = new Appointment($db);
$notificationModel = new Notification($db);

$today = date('Y-m-d');
$appointments = $appointmentModel->listTodayForReminder($today);

$sentClientReminders = 0;

foreach ($appointments as $item) {
    $destinatario = $item['whatsapp'] ?: $item['telefone'] ?: $item['email'];
    $mensagem = sprintf(
        'Olá %s! Lembrete: você possui atendimento hoje às %s (%s).',
        $item['cliente_nome'],
        substr((string) $item['hora_inicio'], 0, 5),
        $item['servico_nome']
    );

    // Placeholder de envio real (WhatsApp/SMS/E-mail): nesta etapa apenas registramos.
    $notificationModel->create([
        'agendamento_id' => (int) $item['id'],
        'cliente_id' => (int) $item['cliente_id'],
        'tipo' => 'lembrete_cliente',
        'canal' => 'whatsapp',
        'destinatario' => $destinatario,
        'mensagem' => $mensagem,
        'status' => 'enviada',
        'enviado_em' => date('Y-m-d H:i:s'),
    ]);

    $sentClientReminders++;
}

$adminDestination = getenv('ADMIN_EMAIL') ?: 'proprietaria@studiobrunanayara.com';
$summaryMessage = sprintf(
    "Resumo diário (%s): %d atendimento(s) hoje. Lembretes enviados: %d.",
    $today,
    count($appointments),
    $sentClientReminders
);

$notificationModel->create([
    'agendamento_id' => null,
    'cliente_id' => null,
    'tipo' => 'resumo_admin',
    'canal' => 'email',
    'destinatario' => $adminDestination,
    'mensagem' => $summaryMessage,
    'status' => 'enviada',
    'enviado_em' => date('Y-m-d H:i:s'),
]);

Logger::info('CRON diário executado', [
    'data' => $today,
    'agendamentos_hoje' => count($appointments),
    'lembretes_cliente' => $sentClientReminders,
    'resumo_admin_destino' => $adminDestination,
]);

echo "CRON concluído com sucesso.\n";
