<?php

declare(strict_types=1);

namespace App\Models;

class AuthorizationLog extends Model
{
    protected string $table = 'AuthorizationLog';

    protected string $defaultOrder = 'created_at DESC';

    public function recent(?int $roleId): array
    {
        $sql = 'SELECT l.*, actor.full_name AS performed_by_name,
                       target.full_name AS target_user_name, r.name AS role_name
                FROM AuthorizationLog l
                JOIN User actor ON actor.id = l.performed_by
                LEFT JOIN User target ON target.id = l.target_user_id
                LEFT JOIN Role r ON r.id = l.role_id';

        $parameters = [];

        if ($roleId !== null) {
            $sql .= ' WHERE l.role_id = :role_id';
            $parameters['role_id'] = $roleId;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY l.created_at DESC LIMIT 200');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function record(
        string $action,
        int $performedBy,
        ?int $roleId,
        ?int $targetUserId,
        ?string $detail
    ): int {
        return $this->create([
            'action' => $action,
            'role_id' => $roleId,
            'target_user_id' => $targetUserId,
            'performed_by' => $performedBy,
            'detail' => $detail,
        ]);
    }
}
