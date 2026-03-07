<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\PaymentProcessorService;

class PaymentWebhookController extends Controller
{
    public function handle(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);

        $reference = (string) ($payload['referencia_externa'] ?? $_POST['referencia_externa'] ?? '');
        $status = (string) ($payload['status'] ?? $_POST['status'] ?? '');
        $transactionId = (string) ($payload['transaction_id'] ?? $_POST['transaction_id'] ?? '');

        if ($reference === '' || $status === '') {
            http_response_code(400);
            echo 'invalid_payload';
            return;
        }

        $dbConfig = require __DIR__ . '/../config/database.php';
        $db = Database::getConnection($dbConfig);

        (new PaymentProcessorService($db))->handleGatewayCallback($reference, $status, $transactionId, is_array($payload) ? $payload : $_POST);

        echo 'ok';
    }
}
