<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class PasswordRecoveryService
{
    public function recover(?int $tenantId, string $email): array
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'E-mail inválido.'];
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email, $tenantId);
        if (!$user) {
            return ['ok' => false, 'message' => 'E-mail não localizado.'];
        }

        $temp = $this->generateTempPassword();
        $hash = password_hash($temp, PASSWORD_BCRYPT);
        $userModel->updatePassword((int)$user['id'], $hash);

        $mail = new EmailService();
        $result = $mail->sendRecovery((int)($user['tenant_id'] ?? $tenantId ?? 0), $email, $temp, (string)$user['nome']);
        return $result['ok']
            ? ['ok' => true, 'message' => 'Nova senha enviada para o e-mail cadastrado.']
            : ['ok' => false, 'message' => 'Não foi possível enviar a recuperação no momento.'];
    }

    private function generateTempPassword(): string
    {
        return 'CT' . substr(bin2hex(random_bytes(6)), 0, 10) . '!';
    }
}
