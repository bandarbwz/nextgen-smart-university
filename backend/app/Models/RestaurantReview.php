<?php

declare(strict_types=1);

namespace App\Models;

class RestaurantReview extends Model
{
    protected string $table = 'RestaurantReview';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'created_at DESC';

    public function forRestaurant(int $restaurantId): array
    {
        $statement = $this->db->prepare(
            'SELECT rv.id, rv.rating, rv.comment, rv.created_at, u.full_name AS customer_name
             FROM RestaurantReview rv
             JOIN User u ON u.id = rv.customer_id
             WHERE rv.restaurant_id = :restaurant_id AND rv.deleted_at IS NULL
             ORDER BY rv.created_at DESC'
        );

        $statement->execute(['restaurant_id' => $restaurantId]);

        return $statement->fetchAll();
    }

    public function existsForOrder(int $orderId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM RestaurantReview WHERE order_id = :order_id LIMIT 1'
        );

        $statement->execute(['order_id' => $orderId]);

        return $statement->fetchColumn() !== false;
    }
}
