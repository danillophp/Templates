<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class EmailService
{
    public function sendReceipt(int $tenantId, string $to, array $receipt): array
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'E-mail inválido para envio.'];
        }

        $subject = 'Comprovante de Agendamento - Cata Treco';
        $body = $this->buildReceiptHtml($receipt);

        try {
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                $result = $this->sendWithPhpmailer($to, $subject, $body);
            } else {
                $result = $this->sendWithNativeMail($to, $subject, $body);
            }

            $this->log($tenantId, $to, $result['ok'] ? 'ENVIADO' : 'ERRO', $result['message']);
            return $result;
        } catch (\Throwable $e) {
            $this->log($tenantId, $to, 'ERRO', $e->getMessage());
            return ['ok' => false, 'message' => 'Falha ao enviar e-mail automático.'];
        }
    }


    public function sendRecovery(int $tenantId, string $to, string $tempPassword, string $nome): array
    {
        $subject = 'Recuperação de Senha - Cata Treco';
        $body = '<div style="font-family:Arial,sans-serif;color:#123">'
            . '<h2 style="color:#0b6e4f">Recuperação de Senha</h2>'
            . '<p>Olá, ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '.</p>'
            . '<p>Sua nova senha temporária é: <strong>' . htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p>Ao entrar, altere imediatamente a senha.</p></div>';

        try {
            $result = class_exists('PHPMailer\PHPMailer\PHPMailer')
                ? $this->sendWithPhpmailer($to, $subject, $body)
                : $this->sendWithNativeMail($to, $subject, $body);
            $this->log($tenantId, $to, $result['ok'] ? 'RECUPERACAO_ENVIADA' : 'RECUPERACAO_ERRO', $result['message']);
            return $result;
        } catch (\Throwable $e) {
            $this->log($tenantId, $to, 'RECUPERACAO_ERRO', $e->getMessage());
            return ['ok' => false, 'message' => 'Falha ao enviar recuperação.'];
        }
    }

    private function sendWithPhpmailer(string $to, string $subject, string $body): array
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        $mail->send();

        return ['ok' => true, 'message' => 'Comprovante enviado por e-mail.'];
    }

    private function sendWithNativeMail(string $to, string $subject, string $body): array
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        ];

        $ok = @mail($to, $subject, $body, implode("\r\n", $headers));
        return $ok ? ['ok' => true, 'message' => 'Comprovante enviado por e-mail.'] : ['ok' => false, 'message' => 'Não foi possível enviar o e-mail neste momento.'];
    }

    private function buildReceiptHtml(array $receipt): string
    {
        $safe = static fn(string $key) => htmlspecialchars((string)($receipt[$key] ?? ''), ENT_QUOTES, 'UTF-8');

        return '<div style="font-family:Arial,sans-serif;color:#123">'
            . '<h2 style="color:#0b6e4f">Comprovante de Agendamento - Cata Treco</h2>'
            . '<p><strong>Nome:</strong> ' . $safe('nome') . '</p>'
            . '<p><strong>Endereço:</strong> ' . $safe('endereco') . '</p>'
            . '<p><strong>Data solicitada:</strong> ' . $safe('data_solicitada') . '</p>'
            . '<p><strong>Telefone:</strong> ' . $safe('telefone') . '</p>'
            . '<p><strong>E-mail:</strong> ' . $safe('email') . '</p>'
            . '<p><strong>Protocolo:</strong> ' . $safe('protocolo') . '</p>'
            . '<p><strong>Status:</strong> ' . $safe('status') . '</p>'
            . '</div>';
    }

    private function log(int $tenantId, string $destino, string $status, string $resposta): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO notificacoes (tenant_id, canal, destino, status, resposta, criado_em) VALUES (:tenant_id, "email", :destino, :status, :resposta, NOW())');
        $stmt->execute([
            'tenant_id' => $tenantId,
            'destino' => $destino,
            'status' => $status,
            'resposta' => $resposta,
        ]);
    }
}
