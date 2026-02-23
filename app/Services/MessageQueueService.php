<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class MessageQueueService
{
    public function ensureTable(): void
    {
        Database::connection()->exec("CREATE TABLE IF NOT EXISTS mensagens_fila (
            id INT AUTO_INCREMENT PRIMARY KEY,
            canal VARCHAR(30) NOT NULL DEFAULT 'whatsapp',
            tenant_id INT NOT NULL,
            solicitacao_id INT NULL,
            destino VARCHAR(40) NOT NULL,
            payload_json JSON NOT NULL,
            status ENUM('pendente','enviando','enviado','erro','manual') NOT NULL DEFAULT 'pendente',
            tentativas INT NOT NULL DEFAULT 0,
            erro_mensagem VARCHAR(500) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_mensagens_fila_status (status)
        ) ENGINE=InnoDB");
    }

    public function enqueue(int $tenantId, int $solicitacaoId, string $telefone, string $evento, array $payload): int
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare('INSERT INTO mensagens_fila (canal, tenant_id, solicitacao_id, destino, payload_json, status, tentativas, created_at, updated_at)
            VALUES ("whatsapp", :tenant_id, :solicitacao_id, :destino, :payload_json, "pendente", 0, NOW(), NOW())');

        $stmt->execute([
            'tenant_id' => $tenantId,
            'solicitacao_id' => $solicitacaoId,
            'destino' => preg_replace('/\D+/', '', $telefone) ?? '',
            'payload_json' => json_encode(array_merge($payload, ['evento' => $evento]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function reservePending(int $limit = 10): array
    {
        $this->ensureTable();
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT * FROM mensagens_fila WHERE status = "pendente" AND (updated_at <= DATE_SUB(NOW(), INTERVAL 2 MINUTE) OR tentativas = 0) ORDER BY id ASC LIMIT :limite FOR UPDATE');
        $stmt->bindValue(':limite', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $ids = array_map(static fn(array $r) => (int)$r['id'], $rows);
        if (!empty($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $up = $pdo->prepare("UPDATE mensagens_fila SET status = 'enviando', updated_at = NOW() WHERE id IN ($in) AND status = 'pendente'");
            foreach ($ids as $i => $id) {
                $up->bindValue($i + 1, $id, \PDO::PARAM_INT);
            }
            $up->execute();
        }

        $pdo->commit();
        return $rows;
    }

    public function markSent(int $id): void
    {
        Database::connection()->prepare('UPDATE mensagens_fila SET status = "enviado", erro_mensagem = NULL, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public function markManual(int $id, string $erro): void
    {
        Database::connection()->prepare('UPDATE mensagens_fila SET status = "manual", erro_mensagem = :erro, tentativas = tentativas + 1, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id, 'erro' => mb_substr($erro, 0, 500)]);
    }

    public function markError(int $id, string $erro): void
    {
        Database::connection()->prepare('UPDATE mensagens_fila SET tentativas = tentativas + 1, erro_mensagem = :erro, status = CASE WHEN tentativas + 1 >= 3 THEN "erro" ELSE "pendente" END, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id, 'erro' => mb_substr($erro, 0, 500)]);
    }

    public function report(int $tenantId): array
    {
        $this->ensureTable();
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT
            SUM(CASE WHEN status = "enviado" THEN 1 ELSE 0 END) AS enviadas,
            SUM(CASE WHEN status IN ("erro","manual") THEN 1 ELSE 0 END) AS erros
            FROM mensagens_fila WHERE tenant_id = :tenant_id');
        $stmt->execute(['tenant_id' => $tenantId]);
        $agg = $stmt->fetch() ?: [];

        $fails = $pdo->prepare('SELECT id, solicitacao_id, destino as telefone_destino, erro_mensagem, tentativas, updated_at as ultima_tentativa_em FROM mensagens_fila WHERE tenant_id = :tenant_id AND status IN ("erro","manual") ORDER BY updated_at DESC LIMIT 10');
        $fails->execute(['tenant_id' => $tenantId]);

        return [
            'enviadas' => (int)($agg['enviadas'] ?? 0),
            'erros' => (int)($agg['erros'] ?? 0),
            'taxa_entrega' => 0,
            'tempo_medio' => 0,
            'falhas' => $fails->fetchAll(),
            'chart' => [],
        ];
    }
}
