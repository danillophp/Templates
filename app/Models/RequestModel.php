<?php
namespace App\Models;

use App\Core\Database;

class RequestModel
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO solicitacoes (nome,email,telefone_whatsapp,endereco,cep,data_agendada,latitude,longitude,foto_path,status) VALUES (:nome,:email,:telefone,:endereco,:cep,:data_agendada,:latitude,:longitude,:foto_path,:status)';
        $st = Database::connection()->prepare($sql);
        $st->execute($data);
        $id = (int) Database::connection()->lastInsertId();
        $proto = 'CAT-' . date('Y') . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
        $up = Database::connection()->prepare('UPDATE solicitacoes SET protocolo = :protocolo WHERE id = :id');
        $up->execute(['protocolo' => $proto, 'id' => $id]);
        return $id;
    }

    public function find(int $id): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM solicitacoes WHERE id = :id');
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    public function findByProtocolOrPhone(string $term): array
    {
        $isProtocol = str_starts_with(strtoupper($term), 'CAT-');
        $sql = $isProtocol ? 'SELECT * FROM solicitacoes WHERE protocolo = :term' : 'SELECT * FROM solicitacoes WHERE telefone_whatsapp = :term ORDER BY criado_em DESC';
        $st = Database::connection()->prepare($sql);
        $st->execute(['term' => $term]);
        return $st->fetchAll();
    }

    public function listByDate(string $date): array
    {
        $st = Database::connection()->prepare('SELECT * FROM solicitacoes WHERE data_agendada = :d ORDER BY data_agendada ASC, id ASC');
        $st->execute(['d' => $date]);
        return $st->fetchAll();
    }

    public function updateStatus(array $ids, string $status, ?string $newDate = null): void
    {
        if (!$ids) return;
        $pdo = Database::connection();
        $in = implode(',', array_fill(0, count($ids), '?'));
        $params = [$status];
        $sql = 'UPDATE solicitacoes SET status = ?, atualizado_em = NOW()';
        if ($newDate) { $sql .= ', data_agendada = ?'; $params[] = $newDate; }
        $sql .= " WHERE id IN ($in)";
        $params = array_merge($params, $ids);
        $pdo->prepare($sql)->execute($params);
    }

    public function deleteByIds(array $ids): void
    {
        if (!$ids) return;
        $in = implode(',', array_fill(0, count($ids), '?'));
        Database::connection()->prepare("DELETE FROM solicitacoes WHERE id IN ($in)")->execute($ids);
    }
}
