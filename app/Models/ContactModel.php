<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ContactModel
{
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO contact_messages (name, email, subject, message, ip_address, created_at) VALUES (:name, :email, :subject, :message, :ip_address, NOW())');
        $stmt->execute($data);
        return (int) Database::connection()->lastInsertId();
    }
}
