<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class NewsModel
{
    public function latestPublic(int $limit = 10): array
    {
        $stmt = Database::connection()->prepare('SELECT id, title, slug, summary, published_at FROM news WHERE status = "published" ORDER BY published_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function lastModifiedPublic(): string
    {
        $row = Database::connection()->query("SELECT COALESCE(MAX(updated_at), NOW()) AS last_modified FROM news WHERE status = 'published'")->fetch();
        return (string) ($row['last_modified'] ?? date('Y-m-d H:i:s'));
    }

    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO news (title, slug, category, summary, body, image_path, status, published_at, created_by, created_at, updated_at) VALUES (:title, :slug, :category, :summary, :body, :image_path, :status, :published_at, :created_by, NOW(), NOW())');
        $stmt->execute($data);
        return (int) Database::connection()->lastInsertId();
    }
}
