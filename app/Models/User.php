<?php

namespace App\Models;

class User extends Model
{
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, username, role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        return $stmt->fetch() ?: null;
    }

    public function allEmployees(): array
    {
        return $this->db->query("SELECT id, name FROM users WHERE role='FUNCIONARIO' ORDER BY name")->fetchAll();
    }
}
