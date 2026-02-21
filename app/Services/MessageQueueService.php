<?php
namespace App\Services;

use App\Models\MessageQueueModel;

class MessageQueueService
{
    private MessageQueueModel $model;
    public function __construct() { $this->model = new MessageQueueModel(); }

    public function enqueueStatusMessages(array $request, string $template): void
    {
        $payload = ['nome' => $request['nome'], 'protocolo' => $request['protocolo'], 'status' => $request['status'], 'data' => $request['data_agendada']];
        $this->model->enqueue([
            'solicitacao_id' => $request['id'], 'canal' => 'email', 'destino' => $request['email'], 'template' => $template, 'payload' => $payload
        ]);
        $this->model->enqueue([
            'solicitacao_id' => $request['id'], 'canal' => 'whatsapp', 'destino' => $request['telefone_whatsapp'], 'template' => $template, 'payload' => $payload
        ]);
    }

    public function process(): void
    {
        $email = new EmailService();
        $wa = new WhatsAppService();
        foreach ($this->model->pending() as $msg) {
            $this->model->markSending((int)$msg['id']);
            try {
                $payload = json_decode($msg['payload_json'], true) ?: [];
                $text = sprintf('Olá %s, protocolo %s atualizado para %s. Data: %s', $payload['nome'] ?? '', $payload['protocolo'] ?? '', $payload['status'] ?? '', $payload['data'] ?? '');
                if ($msg['canal'] === 'email') {
                    $ok = $email->send($msg['destino'], 'Atualização Cata Treco', nl2br(htmlspecialchars($text)));
                    if (!$ok) {
                        throw new \RuntimeException('mail() falhou');
                    }
                } else {
                    $wa->send($msg['destino'], $text);
                }
                $this->model->markSent((int)$msg['id']);
            } catch (\Throwable $e) {
                $this->model->markError((int)$msg['id'], substr($e->getMessage(), 0, 250));
            }
        }
    }
}
