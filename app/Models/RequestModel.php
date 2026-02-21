<?php
namespace App\Models;

use App\Core\Database;

class RequestModel
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO solicitacoes(nome,email,telefone_whatsapp,endereco,cep,data_agendada,latitude,longitude,foto_path,status,criado_em,atualizado_em)
                VALUES(:nome,:email,:tel,:end,:cep,:data,:lat,:lng,:foto,"PENDENTE",NOW(),NOW())';
        $st = Database::connection()->prepare($sql);
        $st->execute([
            'nome'=>$data['nome'],'email'=>$data['email'],'tel'=>$data['telefone_whatsapp'],'end'=>$data['endereco'],'cep'=>$data['cep'],
            'data'=>$data['data_agendada'],'lat'=>$data['latitude'],'lng'=>$data['longitude'],'foto'=>$data['foto_path']
        ]);
        $id = (int) Database::connection()->lastInsertId();
        $protocol = 'CAT-' . date('Y') . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
        $up = Database::connection()->prepare('UPDATE solicitacoes SET protocolo=:p WHERE id=:id');
        $up->execute(['p'=>$protocol,'id'=>$id]);
        return $id;
    }

    public function find(int $id): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM solicitacoes WHERE id=:id');
        $st->execute(['id'=>$id]);
        return $st->fetch() ?: null;
    }

    public function byProtocol(string $protocol): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM solicitacoes WHERE protocolo=:p');
        $st->execute(['p'=>$protocol]);
        return $st->fetch() ?: null;
    }

    public function byPhone(string $phone): array
    {
        $st = Database::connection()->prepare('SELECT * FROM solicitacoes WHERE telefone_whatsapp=:t ORDER BY criado_em DESC');
        $st->execute(['t'=>$phone]);
        return $st->fetchAll();
    }

    public function listByDate(string $date): array
    {
        $st = Database::connection()->prepare('SELECT * FROM solicitacoes WHERE data_agendada=:d ORDER BY data_agendada ASC, id DESC');
        $st->execute(['d'=>$date]);
        return $st->fetchAll();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $st = Database::connection()->prepare('UPDATE solicitacoes SET status=:s, atualizado_em=NOW() WHERE id=:id');
        return $st->execute(['s'=>$status,'id'=>$id]);
    }

    public function updateDate(int $id, string $date): bool
    {
        $st = Database::connection()->prepare('UPDATE solicitacoes SET data_agendada=:d,status="ALTERADO", atualizado_em=NOW() WHERE id=:id');
        return $st->execute(['d'=>$date,'id'=>$id]);
    }

    public function delete(int $id): bool
    {
        $st = Database::connection()->prepare('DELETE FROM solicitacoes WHERE id=:id');
        return $st->execute(['id'=>$id]);
    }

    public function statusCount(): array
    {
        return Database::connection()->query('SELECT status, COUNT(*) total FROM solicitacoes GROUP BY status')->fetchAll();
    }

    public function monthlyCount(): array
    {
        return Database::connection()->query('SELECT DATE_FORMAT(data_agendada, "%Y-%m") mes, COUNT(*) total FROM solicitacoes GROUP BY mes ORDER BY mes')->fetchAll();
    }
}
