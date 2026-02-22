<?php
namespace App\Services;

use App\Models\MessageQueueModel;

class MessageQueueService
{
    public function __construct(private ?MessageQueueModel $model = null, private ?EmailService $email = null)
    {
        $this->model ??= new MessageQueueModel();
        $this->email ??= new EmailService();
    }

    public function enqueueStatusEmail(int $sid, string $to, string $status, array $extra = []): void
    {
        $this->model->enqueue([
            'solicitacao_id' => $sid,
            'canal' => 'email',
            'destino' => $to,
            'template' => 'status_update',
            'payload_json' => json_encode(array_merge(['status' => $status], $extra), JSON_UNESCAPED_UNICODE)
        ]);
    }

    public function processPending(): array
    {
        $result = ['sent' => 0, 'error' => 0];
        foreach ($this->model->pending() as $msg) {
            $this->model->markSending((int)$msg['id']);
            try {
                $p = json_decode($msg['payload_json'], true) ?: [];
                $ok = $this->email->send($msg['destino'], 'Atualização Cata Treco', '<p>Status: ' . htmlspecialchars($p['status'] ?? '-') . '</p>');
                if (!$ok) throw new \RuntimeException('Falha mail()');
                $this->model->markSent((int)$msg['id']);
                $result['sent']++;
            } catch (\Throwable $e) {
                $this->model->markError((int)$msg['id'], $e->getMessage());
                $result['error']++;
            }
        }
        return $result;
    }
}
