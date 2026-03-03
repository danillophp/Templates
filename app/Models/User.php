<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public function findAuthUser(string $username): ?array
    {
        $sql = 'SELECT u.*, r.name AS role_name
            FROM users u
            JOIN user_roles ur ON ur.user_id = u.id
            JOIN roles r ON r.id = ur.role_id
            WHERE u.username = :username AND u.is_active = 1
            LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        $permStmt = Database::connection()->prepare(
            'SELECT p.name
             FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = :user_id'
        );
        $permStmt->execute([':user_id' => $user['id']]);
        $user['permissions'] = array_values(array_unique(array_column($permStmt->fetchAll(), 'name')));

        return $user;
    }

    public function registerLoginAttempt(string $username, string $ip, bool $success): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO login_attempts (username, ip_address, was_successful, attempted_at) VALUES (:username, :ip, :ok, NOW())');
        $stmt->execute([':username' => $username, ':ip' => $ip, ':ok' => $success ? 1 : 0]);
    }

    public function failedAttemptsInWindow(string $username, string $ip, int $minutes = 15): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM login_attempts WHERE username = :username AND ip_address = :ip AND was_successful = 0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)');
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':ip', $ip);
        $stmt->bindValue(':minutes', $minutes, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function lockUser(int $userId, int $seconds): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET failed_login_count = failed_login_count + 1, locked_until = DATE_ADD(NOW(), INTERVAL :seconds SECOND) WHERE id = :id');
        $stmt->bindValue(':seconds', $seconds, \PDO::PARAM_INT);
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
    }

    public function clearFailures(int $userId): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $userId]);
    }
}
