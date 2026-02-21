<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PointModel
{
    public function active(int $tenantId): array
    {
        $stmt = Database::connection()->prepare('SELECT id, titulo, latitude, longitude, cor_pin FROM pontos_mapa WHERE tenant_id = :tenant_id AND ativo = 1 ORDER BY id DESC');
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    public function create(int $tenantId, string $titulo, float $latitude, float $longitude): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO pontos_mapa (tenant_id, titulo, latitude, longitude, ativo) VALUES (:tenant_id,:titulo,:latitude,:longitude,1)');
        $stmt->execute([
            'tenant_id' => $tenantId,
            'titulo' => $titulo,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }
}
