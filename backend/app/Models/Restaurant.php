<?php

declare(strict_types=1);

namespace App\Models;

class Restaurant extends Model
{
    protected string $table = 'Restaurant';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'restaurant_name';

    public function listing(?string $status): array
    {
        $sql = 'SELECT r.*, u.full_name AS owner_name,
                       (SELECT ROUND(AVG(rating), 1) FROM RestaurantReview
                        WHERE restaurant_id = r.id AND deleted_at IS NULL) AS average_rating,
                       (SELECT COUNT(*) FROM RestaurantReview
                        WHERE restaurant_id = r.id AND deleted_at IS NULL) AS review_count
                FROM Restaurant r
                JOIN User u ON u.id = r.owner_id
                WHERE r.deleted_at IS NULL';

        $parameters = [];

        if ($status !== null) {
            $sql .= ' AND r.status = :status';
            $parameters['status'] = $status;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY r.restaurant_name');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function forOwner(int $ownerId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM Restaurant WHERE owner_id = :owner_id AND deleted_at IS NULL'
        );

        $statement->execute(['owner_id' => $ownerId]);

        return $statement->fetchAll();
    }

    public function isOwnedBy(int $restaurantId, int $userId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Restaurant WHERE id = :id AND owner_id = :owner_id LIMIT 1'
        );

        $statement->execute([
            'id' => $restaurantId,
            'owner_id' => $userId,
        ]);

        return $statement->fetchColumn() !== false;
    }
}
