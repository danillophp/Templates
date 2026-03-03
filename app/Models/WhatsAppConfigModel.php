<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class WhatsAppConfigModel
{
    public function ensureTable(): void
    {
        Database::connection()->exec("CREATE TABLE IF NOT EXISTS whatsapp_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ativo TINYINT(1) NOT NULL DEFAULT 0,
            phone_number_id VARCHAR(80) NOT NULL,
            business_account_id VARCHAR(80) NULL,
            access_token TEXT NOT NULL,
            api_version VARCHAR(20) NOT NULL DEFAULT 'v20.0',
            sender_name VARCHAR(120) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
    }

    public function getActive(): ?array
    {
        $this->ensureTable();
        $stmt = Database::connection()->query('SELECT * FROM whatsapp_config WHERE ativo = 1 ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getLatest(): ?array
    {
        $this->ensureTable();
        $stmt = Database::connection()->query('SELECT * FROM whatsapp_config ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function save(array $data): void
    {
        $this->ensureTable();
        Database::connection()->exec('UPDATE whatsapp_config SET ativo = 0');

        $stmt = Database::connection()->prepare('INSERT INTO whatsapp_config (ativo, phone_number_id, business_account_id, access_token, api_version, sender_name, created_at, updated_at)
            VALUES (:ativo, :phone_number_id, :business_account_id, :access_token, :api_version, :sender_name, NOW(), NOW())');
        $stmt->execute([
            'ativo' => !empty($data['ativo']) ? 1 : 0,
            'phone_number_id' => (string)($data['phone_number_id'] ?? ''),
            'business_account_id' => (string)($data['business_account_id'] ?? ''),
            'access_token' => (string)($data['access_token'] ?? ''),
            'api_version' => (string)($data['api_version'] ?? 'v20.0'),
            'sender_name' => (string)($data['sender_name'] ?? ''),
        ]);
    }
}
