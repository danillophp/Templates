<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PointModel
{
    public function active(): array
    {
        $stmt = Database::connection()->query('SELECT id, titulo, latitude, longitude FROM pontos_mapa WHERE ativo = 1 ORDER BY id DESC');
        return $stmt->fetchAll();
    }

    public function create(string $titulo, float $latitude, float $longitude): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO pontos_mapa (titulo, latitude, longitude, ativo) VALUES (:titulo,:latitude,:longitude,1)');
        $stmt->execute([
            'titulo' => $titulo,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }
}
