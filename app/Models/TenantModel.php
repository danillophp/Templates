<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class TenantModel
{
    public function all(): array
    {
        return Database::connection()->query('SELECT * FROM tenants ORDER BY criado_em DESC')->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO tenants (nome, slug, dominio, ativo, criado_em) VALUES (:nome,:slug,:dominio,1,NOW())');
        $stmt->execute($data);
        return (int)Database::connection()->lastInsertId();
    }

    public function globalMetrics(): array
    {
        $pdo = Database::connection();
        return [
            'tenants_ativos' => (int)$pdo->query('SELECT COUNT(*) FROM tenants WHERE ativo = 1')->fetchColumn(),
            'total_solicitacoes' => (int)$pdo->query('SELECT COUNT(*) FROM solicitacoes')->fetchColumn(),
            'receita_estimada' => (float)$pdo->query("SELECT COALESCE(SUM(p.valor_mensal),0) FROM assinaturas a JOIN planos p ON p.id = a.plano_id WHERE a.status = 'ATIVA'")->fetchColumn(),
            'inadimplentes' => (int)$pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status = 'INADIMPLENTE'")->fetchColumn(),
        ];
    }
}
