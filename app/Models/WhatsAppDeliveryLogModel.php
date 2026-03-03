<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class WhatsAppDeliveryLogModel
{
    public function ensureTable(): void
    {
        Database::connection()->exec("CREATE TABLE IF NOT EXISTS whatsapp_delivery_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            solicitacao_id INT NULL,
            evento VARCHAR(80) NOT NULL,
            destino VARCHAR(40) NOT NULL,
            mensagem TEXT NOT NULL,
            canal VARCHAR(30) NOT NULL,
            status VARCHAR(30) NOT NULL,
            http_status INT NULL,
            response_body LONGTEXT NULL,
            erro TEXT NULL,
            tentativas INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_whatsapp_logs_created (created_at),
            INDEX idx_whatsapp_logs_destino (destino)
        ) ENGINE=InnoDB");
    }

    public function create(array $row): int
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare('INSERT INTO whatsapp_delivery_logs (solicitacao_id, evento, destino, mensagem, canal, status, http_status, response_body, erro, tentativas, created_at, updated_at)
            VALUES (:solicitacao_id, :evento, :destino, :mensagem, :canal, :status, :http_status, :response_body, :erro, :tentativas, NOW(), NOW())');
        $stmt->execute([
            'solicitacao_id' => $row['solicitacao_id'] ?? null,
            'evento' => (string)($row['evento'] ?? 'teste'),
            'destino' => (string)($row['destino'] ?? ''),
            'mensagem' => (string)($row['mensagem'] ?? ''),
            'canal' => (string)($row['canal'] ?? 'cloud_api'),
            'status' => (string)($row['status'] ?? 'queued'),
            'http_status' => $row['http_status'] ?? null,
            'response_body' => $row['response_body'] ?? null,
            'erro' => $row['erro'] ?? null,
            'tentativas' => (int)($row['tentativas'] ?? 0),
        ]);
        return (int)Database::connection()->lastInsertId();
    }

    public function updateById(int $id, array $row): void
    {
        $stmt = Database::connection()->prepare('UPDATE whatsapp_delivery_logs SET canal = :canal, status = :status, http_status = :http_status, response_body = :response_body, erro = :erro, tentativas = :tentativas, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'canal' => (string)($row['canal'] ?? 'cloud_api'),
            'status' => (string)($row['status'] ?? 'failed'),
            'http_status' => $row['http_status'] ?? null,
            'response_body' => $row['response_body'] ?? null,
            'erro' => $row['erro'] ?? null,
            'tentativas' => (int)($row['tentativas'] ?? 0),
        ]);
    }

    public function list(array $filters): array
    {
        $this->ensureTable();
        $sql = 'SELECT * FROM whatsapp_delivery_logs WHERE 1=1';
        $params = [];
        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(created_at) >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(created_at) <= :to';
            $params['to'] = $filters['to'];
        }
        if (!empty($filters['destino'])) {
            $sql .= ' AND destino LIKE :destino';
            $params['destino'] = '%' . $filters['destino'] . '%';
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['evento'])) {
            $sql .= ' AND evento = :evento';
            $params['evento'] = $filters['evento'];
        }
        $sql .= ' ORDER BY id DESC LIMIT 300';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
