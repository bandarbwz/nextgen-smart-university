<?php

declare(strict_types=1);

namespace App\Models;

class FoodOrder extends Model
{
    protected string $table = 'FoodOrder';

    protected string $defaultOrder = 'ordered_at DESC';

    public function forCustomer(int $customerId): array
    {
        $statement = $this->db->prepare(
            'SELECT o.*, r.restaurant_name
             FROM FoodOrder o
             JOIN Restaurant r ON r.id = o.restaurant_id
             WHERE o.customer_id = :customer_id
             ORDER BY o.ordered_at DESC'
        );

        $statement->execute(['customer_id' => $customerId]);

        return $statement->fetchAll();
    }

    public function forRestaurant(int $restaurantId, ?string $status): array
    {
        $sql = 'SELECT o.*, u.full_name AS customer_name
                FROM FoodOrder o
                JOIN User u ON u.id = o.customer_id
                WHERE o.restaurant_id = :restaurant_id';

        $parameters = ['restaurant_id' => $restaurantId];

        if ($status !== null) {
            $sql .= ' AND o.order_status = :status';
            $parameters['status'] = $status;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY o.ordered_at DESC');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT o.*, r.restaurant_name, r.owner_id, u.full_name AS customer_name
             FROM FoodOrder o
             JOIN Restaurant r ON r.id = o.restaurant_id
             JOIN User u ON u.id = o.customer_id
             WHERE o.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        $order = $statement->fetch();

        if ($order === false) {
            return null;
        }

        $order['items'] = $this->items($id);

        return $order;
    }

    public function items(int $orderId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM OrderItem WHERE order_id = :order_id ORDER BY id'
        );

        $statement->execute(['order_id' => $orderId]);

        return $statement->fetchAll();
    }

    public function addItem(array $fields): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO OrderItem (order_id, menu_item_id, item_name, quantity, unit_price, subtotal)
             VALUES (:order_id, :menu_item_id, :item_name, :quantity, :unit_price, :subtotal)'
        );

        $statement->execute($fields);

        return (int) $this->db->lastInsertId();
    }

    public function changeStatus(int $id, string $status): bool
    {
        $statement = $this->db->prepare(
            'UPDATE FoodOrder
             SET order_status = :status,
                 completed_at = CASE WHEN :completed_check = :completed_value
                     THEN UTC_TIMESTAMP() ELSE completed_at END
             WHERE id = :id'
        );

        return $statement->execute([
            'status' => $status,
            'completed_check' => $status,
            'completed_value' => 'Completed',
            'id' => $id,
        ]);
    }

    public function nextOrderNumber(): string
    {
        $statement = $this->db->query('SELECT COUNT(*) FROM FoodOrder');

        return sprintf('ORD-%s-%05d', gmdate('Ymd'), (int) $statement->fetchColumn() + 1);
    }

    public function salesReport(int $restaurantId, ?string $from, ?string $to): array
    {
        $sql = 'SELECT DATE(ordered_at) AS sales_date,
                       COUNT(*) AS order_count,
                       SUM(total_amount) AS revenue
                FROM FoodOrder
                WHERE restaurant_id = :restaurant_id AND order_status = :completed';

        $parameters = [
            'restaurant_id' => $restaurantId,
            'completed' => 'Completed',
        ];

        if ($from !== null) {
            $sql .= ' AND ordered_at >= :from';
            $parameters['from'] = $from . ' 00:00:00';
        }

        if ($to !== null) {
            $sql .= ' AND ordered_at <= :to';
            $parameters['to'] = $to . ' 23:59:59';
        }

        $statement = $this->db->prepare($sql . ' GROUP BY DATE(ordered_at) ORDER BY sales_date DESC');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }
}
