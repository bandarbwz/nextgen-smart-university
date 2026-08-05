<?php

declare(strict_types=1);

namespace App\Validation;

class RoleValidator
{
    public function role(array $data): array
    {
        return (new Validator())
            ->required($data, 'name', 'Role name')
            ->maxLength($data, 'name', 100, 'Role name')
            ->maxLength($data, 'description', 255, 'Description')
            ->inList($data, 'status', ['active', 'inactive'], 'Status')
            ->errors();
    }

    public function permissions(array $data): array
    {
        $ids = $data['permission_ids'] ?? null;

        if (!is_array($ids)) {
            return ['permission_ids' => ['A list of permission ids is required.']];
        }

        foreach ($ids as $index => $id) {
            if (filter_var($id, FILTER_VALIDATE_INT) === false) {
                return ['permission_ids' => ['Entry ' . $index . ' is not a valid id.']];
            }
        }

        return [];
    }

    public function userRole(array $data): array
    {
        return (new Validator())
            ->required($data, 'role_id', 'Role')
            ->integer($data, 'role_id', 'Role')
            ->errors();
    }
}
