<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class DocumentModel
{
    public function allPublic(): array
    {
        return Database::connection()->query('SELECT id, title, category, year_ref, file_path FROM documents WHERE is_public = 1 ORDER BY year_ref DESC')->fetchAll();
    }

    public function lastModifiedPublic(): string
    {
        $row = Database::connection()->query('SELECT COALESCE(MAX(updated_at), NOW()) AS last_modified FROM documents WHERE is_public = 1')->fetch();
        return (string) ($row['last_modified'] ?? date('Y-m-d H:i:s'));
    }

    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO documents (title, category, tags, year_ref, file_path, is_public, created_by, created_at, updated_at) VALUES (:title, :category, :tags, :year_ref, :file_path, :is_public, :created_by, NOW(), NOW())');
        $stmt->execute($data);
        return (int) Database::connection()->lastInsertId();
    }
}
