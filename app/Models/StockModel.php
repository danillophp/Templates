<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class StockModel
{
    public function itemsWithAlerts(): array
    {
        $sql = 'SELECT si.*, (si.quantity_current <= si.minimum_quantity) AS is_low_stock, (si.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS near_expiry FROM stock_items si ORDER BY si.name';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function move(array $data): int
    {
        $conn = Database::connection();
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare('INSERT INTO stock_movements (stock_item_id, school_id, movement_type, quantity, notes, created_by, created_at) VALUES (:stock_item_id, :school_id, :movement_type, :quantity, :notes, :created_by, NOW())');
            $stmt->execute($data);

            $delta = $data['movement_type'] === 'entrada' ? (float) $data['quantity'] : -(float) $data['quantity'];
            $upd = $conn->prepare('UPDATE stock_items SET quantity_current = quantity_current + :delta, updated_at = NOW() WHERE id = :id');
            $upd->execute([':delta' => $delta, ':id' => $data['stock_item_id']]);
            $conn->commit();
            return (int) $conn->lastInsertId();
        } catch (\Throwable $exception) {
            $conn->rollBack();
            throw $exception;
        }
    }
}
