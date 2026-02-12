<?php

namespace App\Models;

class Politician extends Model
{
    public function allWithLocation(): array
    {
        $sql = 'SELECT p.*, l.name AS location_name
                FROM politicians p
                INNER JOIN locations l ON l.id = p.location_id
                ORDER BY p.created_at DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM politicians WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO politicians (location_id, full_name, position, party, age, biography, career_history, municipality_history, photo_path, phone, email, advisors)
                VALUES (:location_id, :full_name, :position, :party, :age, :biography, :career_history, :municipality_history, :photo_path, :phone, :email, :advisors)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $sql = 'UPDATE politicians SET
                    location_id = :location_id,
                    full_name = :full_name,
                    position = :position,
                    party = :party,
                    age = :age,
                    biography = :biography,
                    career_history = :career_history,
                    municipality_history = :municipality_history,
                    photo_path = :photo_path,
                    phone = :phone,
                    email = :email,
                    advisors = :advisors,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM politicians WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
