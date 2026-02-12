<?php

namespace App\Models;

class Location extends Model
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM locations ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM locations WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO locations (name, address, postal_code, latitude, longitude, city_info, region_info) VALUES (:name, :address, :postal_code, :latitude, :longitude, :city_info, :region_info)');
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->db->prepare('UPDATE locations SET name=:name, address=:address, postal_code=:postal_code, latitude=:latitude, longitude=:longitude, city_info=:city_info, region_info=:region_info, updated_at=NOW() WHERE id=:id');
        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM locations WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function forMap(): array
    {
        $sql = 'SELECT l.id as location_id, l.name as location_name, l.latitude, l.longitude, l.city_info, l.region_info,
                    p.id as politician_id, p.full_name, p.position, p.party, p.age, p.biography, p.career_history,
                    p.photo_path, p.phone, p.email, p.advisors
                FROM locations l
                LEFT JOIN politicians p ON p.location_id = l.id
                ORDER BY l.name, p.full_name';

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $id = (int) $row['location_id'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'location_id' => $id,
                    'location_name' => $row['location_name'],
                    'latitude' => (float) $row['latitude'],
                    'longitude' => (float) $row['longitude'],
                    'city_info' => $row['city_info'],
                    'region_info' => $row['region_info'],
                    'politicians' => [],
                ];
            }

            if (!empty($row['politician_id'])) {
                $grouped[$id]['politicians'][] = [
                    'id' => (int) $row['politician_id'],
                    'full_name' => $row['full_name'],
                    'position' => $row['position'],
                    'party' => $row['party'],
                    'age' => $row['age'],
                    'biography' => $row['biography'],
                    'career_history' => $row['career_history'],
                    'photo_path' => $row['photo_path'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'advisors' => $row['advisors'],
                ];
            }
        }

        return array_values($grouped);
    }
}
