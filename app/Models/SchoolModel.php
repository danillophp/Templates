<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class SchoolModel
{
    public function allPublic(): array
    {
        $sql = 'SELECT id, name, type, address, phone, director_name, latitude, longitude, total_students, available_slots, rooms_count, region FROM schools WHERE is_active = 1 ORDER BY name';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function all(): array
    {
        return Database::connection()->query('SELECT * FROM schools ORDER BY created_at DESC')->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO schools (name, type, address, phone, director_name, latitude, longitude, total_students, available_slots, rooms_count, region, created_at, updated_at) VALUES (:name, :type, :address, :phone, :director_name, :latitude, :longitude, :total_students, :available_slots, :rooms_count, :region, NOW(), NOW())');
        $stmt->execute($data);
        return (int) Database::connection()->lastInsertId();
    }
}
