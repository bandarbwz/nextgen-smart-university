<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class MenuItem extends Model
{
    protected string $table = 'MenuItem';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'item_name';

    public function forRestaurant(int $restaurantId, bool $availableOnly): array
    {
        $sql = 'SELECT m.*, c.category_name
                FROM MenuItem m
                JOIN FoodCategory c ON c.id = m.category_id
                WHERE m.restaurant_id = :restaurant_id AND m.deleted_at IS NULL';

        if ($availableOnly) {
            $sql .= " AND m.availability = 'available'";
        }

        $statement = $this->db->prepare($sql . ' ORDER BY c.category_name, m.item_name');
        $statement->execute(['restaurant_id' => $restaurantId]);

        return $statement->fetchAll();
    }

    /**
     * Loads the authoritative price and availability for the requested items.
     * The client never supplies a price; the order total is computed from these rows.
     */
    public function findManyForOrder(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $statement = $this->db->prepare(
            'SELECT id, restaurant_id, item_name, price, availability
             FROM MenuItem
             WHERE id IN (' . $placeholders . ') AND deleted_at IS NULL'
        );

        $statement->execute($ids);

        $items = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[(int) $row['id']] = $row;
        }

        return $items;
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT m.*, c.category_name, r.restaurant_name, r.owner_id
             FROM MenuItem m
             JOIN FoodCategory c ON c.id = m.category_id
             JOIN Restaurant r ON r.id = m.restaurant_id
             WHERE m.id = :id AND m.deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function popularItems(int $restaurantId, int $limit): array
    {
        $statement = $this->db->prepare(
            'SELECT oi.menu_item_id, oi.item_name,
                    SUM(oi.quantity) AS units_sold,
                    SUM(oi.subtotal) AS revenue
             FROM OrderItem oi
             JOIN FoodOrder o ON o.id = oi.order_id
             WHERE o.restaurant_id = :restaurant_id AND o.order_status = :completed
             GROUP BY oi.menu_item_id, oi.item_name
             ORDER BY units_sold DESC
             LIMIT :row_limit'
        );

        $statement->bindValue('restaurant_id', $restaurantId, PDO::PARAM_INT);
        $statement->bindValue('completed', 'Completed');
        $statement->bindValue('row_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
