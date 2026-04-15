<?php

declare(strict_types=1);

namespace App\Models\Festival;

use App\Core\FestivalDatabase;
use PDO;

final class VoteModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = FestivalDatabase::connection();
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO votos (
                    candidata_id, ip_hash, user_agent_hash, device_fingerprint, cookie_token,
                    sessao_token, origem, status, created_at
                ) VALUES (
                    :candidata_id, :ip_hash, :user_agent_hash, :device_fingerprint, :cookie_token,
                    :sessao_token, :origem, :status, NOW()
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function countRecentByIp(string $ipHash): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM votos WHERE ip_hash = :ip_hash AND status = "VALIDO" AND created_at >= (NOW() - INTERVAL 24 HOUR)');
        $stmt->execute(['ip_hash' => $ipHash]);
        return (int) $stmt->fetchColumn();
    }

    public function countRecentByFingerprint(string $fingerprint): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM votos WHERE device_fingerprint = :fingerprint AND status = "VALIDO" AND created_at >= (NOW() - INTERVAL 24 HOUR)');
        $stmt->execute(['fingerprint' => $fingerprint]);
        return (int) $stmt->fetchColumn();
    }

    public function lastVoteUnixBySession(string $sessionToken): ?int
    {
        $stmt = $this->db->prepare('SELECT UNIX_TIMESTAMP(created_at) AS ts FROM votos WHERE sessao_token = :sessao_token ORDER BY id DESC LIMIT 1');
        $stmt->execute(['sessao_token' => $sessionToken]);
        $row = $stmt->fetch();

        return $row ? (int) $row['ts'] : null;
    }

    public function listPaginated(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT v.id, v.candidata_id, c.nome AS candidata_nome, c.numero AS candidata_numero,
                    v.origem, v.status, v.created_at
             FROM votos v
             INNER JOIN candidatas c ON c.id = v.candidata_id
             ORDER BY v.id DESC
             LIMIT :limit OFFSET :offset'
        );

        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE votos SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function statsOverview(): array
    {
        $stmt = $this->db->query(
            'SELECT
                (SELECT COUNT(*) FROM candidatas WHERE status = "ATIVA") AS total_candidatas,
                (SELECT COUNT(*) FROM votos WHERE status = "VALIDO") AS total_votos_validos,
                (SELECT COUNT(*) FROM tentativas_fraude WHERE created_at >= (NOW() - INTERVAL 7 DAY)) AS alertas_fraude_7d'
        );

        return $stmt->fetch() ?: [];
    }

    public function votesPerDay(int $days = 14): array
    {
        $stmt = $this->db->prepare(
            'SELECT DATE(created_at) AS dia, COUNT(*) AS total
             FROM votos
             WHERE status = "VALIDO" AND created_at >= (CURDATE() - INTERVAL :days DAY)
             GROUP BY DATE(created_at)
             ORDER BY DATE(created_at) ASC'
        );
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
