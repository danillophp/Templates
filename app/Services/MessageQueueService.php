<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class MessageQueueService
{
    public function enqueue(int $tenantId, int $solicitacaoId, string $telefone, string $tipo, array $payload): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO mensagens_fila (tenant_id, solicitacao_id, telefone_destino, tipo, payload_json, status, tentativas, criado_em)
             VALUES (:tenant_id, :solicitacao_id, :telefone_destino, :tipo, :payload_json, "pendente", 0, NOW())'
        );

        $stmt->execute([
            'tenant_id' => $tenantId,
            'solicitacao_id' => $solicitacaoId,
            'telefone_destino' => preg_replace('/\D+/', '', $telefone) ?? '',
            'tipo' => $tipo,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function reservePending(int $limit = 20): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT * FROM mensagens_fila WHERE status = "pendente" ORDER BY id ASC LIMIT :limite FOR UPDATE');
        $stmt->bindValue(':limite', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $ids = array_map(static fn(array $r) => (int)$r['id'], $rows);
        if (!empty($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $up = $pdo->prepare("UPDATE mensagens_fila SET status = 'enviando', ultima_tentativa_em = NOW() WHERE id IN ($in) AND status = 'pendente'");
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
        Database::connection()->prepare('UPDATE mensagens_fila SET status = "enviado", erro_mensagem = NULL, ultima_tentativa_em = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public function markError(int $id, string $erro): void
    {
        Database::connection()->prepare(
            'UPDATE mensagens_fila
             SET tentativas = tentativas + 1,
                 ultima_tentativa_em = NOW(),
                 erro_mensagem = :erro,
                 status = CASE WHEN tentativas + 1 >= 3 THEN "erro" ELSE "pendente" END
             WHERE id = :id'
        )->execute([
            'id' => $id,
            'erro' => mb_substr($erro, 0, 500),
        ]);
    }

    public function report(int $tenantId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT
                SUM(CASE WHEN status = "enviado" THEN 1 ELSE 0 END) AS enviadas,
                SUM(CASE WHEN status = "erro" THEN 1 ELSE 0 END) AS erros,
                AVG(TIMESTAMPDIFF(SECOND, criado_em, COALESCE(ultima_tentativa_em, criado_em))) AS tempo_medio
             FROM mensagens_fila WHERE tenant_id = :tenant_id');
        $stmt->execute(['tenant_id' => $tenantId]);
        $agg = $stmt->fetch() ?: [];

        $fails = $pdo->prepare('SELECT id, solicitacao_id, telefone_destino, erro_mensagem, tentativas, ultima_tentativa_em FROM mensagens_fila WHERE tenant_id = :tenant_id AND status = "erro" ORDER BY ultima_tentativa_em DESC LIMIT 10');
        $fails->execute(['tenant_id' => $tenantId]);

        $chart = $pdo->prepare('SELECT DATE_FORMAT(criado_em, "%Y-%m") as mes, COUNT(*) as total, SUM(CASE WHEN status = "enviado" THEN 1 ELSE 0 END) as enviados FROM mensagens_fila WHERE tenant_id = :tenant_id GROUP BY DATE_FORMAT(criado_em, "%Y-%m") ORDER BY mes ASC');
        $chart->execute(['tenant_id' => $tenantId]);

        $enviadas = (int)($agg['enviadas'] ?? 0);
        $erros = (int)($agg['erros'] ?? 0);
        $all = $enviadas + $erros;
        $taxa = $all > 0 ? round(($enviadas / $all) * 100, 2) : 0.0;

        return [
            'enviadas' => $enviadas,
            'erros' => $erros,
            'taxa_entrega' => $taxa,
            'tempo_medio' => (float)($agg['tempo_medio'] ?? 0),
            'falhas' => $fails->fetchAll(),
            'chart' => $chart->fetchAll(),
        ];
    }
}
