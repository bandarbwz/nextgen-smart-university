<?php

declare(strict_types=1);

namespace App\Models;

class FoodCategory extends Model
{
    protected string $table = 'FoodCategory';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'category_name';

    public function forRestaurant(int $restaurantId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM FoodCategory
             WHERE restaurant_id = :restaurant_id AND deleted_at IS NULL
             ORDER BY category_name'
        );

        $statement->execute(['restaurant_id' => $restaurantId]);

        return $statement->fetchAll();
    }

    public function hasItems(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM MenuItem WHERE category_id = :id AND deleted_at IS NULL LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }
}
