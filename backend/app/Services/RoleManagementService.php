<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuthorizationLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

/**
 * Central authorization management. Every method here changes who can do what,
 * so every method here writes to the log.
 */
class RoleManagementService
{
    public function __construct(
        private readonly Role $roles = new Role(),
        private readonly Permission $permissions = new Permission(),
        private readonly User $users = new User(),
        private readonly AuthorizationLog $log = new AuthorizationLog()
    ) {
    }

    public function list(): array
    {
        return $this->roles->withPermissionCounts();
    }

    public function get(int $id): array
    {
        $role = $this->requireRole($id);

        $role['permission_ids'] = $this->permissions->idsForRole($id);
        $role['permissions'] = $this->permissions->namesForRole($id);

        return $role;
    }

    public function permissionCatalogue(): array
    {
        $grouped = [];

        foreach ($this->permissions->all() as $permission) {
            $grouped[$permission['module']][] = $permission;
        }

        return $grouped;
    }

    public function create(array $user, array $fields): array
    {
        $name = trim($fields['name']);

        $this->guardName($name);

        $id = $this->roles->create([
            'name' => $name,
            'description' => $fields['description'] ?? null,
            'status' => $fields['status'] ?? 'active',
        ]);

        $this->log->record('Role Created', $user['user_id'], $id, null, 'Created role ' . $name);

        return $this->get($id);
    }

    /**
     * A system role can be described differently or deactivated, but it cannot
     * be renamed. Code and seed data both refer to these six by name.
     */
    public function update(int $id, array $user, array $fields): array
    {
        $role = $this->requireRole($id);
        $name = trim($fields['name']);

        if ((bool) $role['is_system'] && $name !== $role['name']) {
            throw new ApiException(
                'A system role cannot be renamed. Other parts of the platform refer to it by name.',
                409
            );
        }

        $this->guardName($name, $id);

        $this->roles->update($id, [
            'name' => $name,
            'description' => $fields['description'] ?? null,
            'status' => $fields['status'] ?? $role['status'],
        ]);

        $this->log->record('Role Updated', $user['user_id'], $id, null, 'Updated role ' . $name);

        return $this->get($id);
    }

    public function delete(int $id, array $user): void
    {
        $role = $this->requireRole($id);

        if ((bool) $role['is_system']) {
            throw new ApiException('A system default role cannot be deleted.', 409);
        }

        $userCount = $this->roles->userCount($id);

        if ($userCount > 0) {
            throw new ApiException(
                'This role is assigned to ' . $userCount . ' user(s) and cannot be deleted.',
                409
            );
        }

        $this->roles->delete($id);

        $this->log->record('Role Deleted', $user['user_id'], null, null, 'Deleted role ' . $role['name']);
    }

    public function assignPermissions(int $id, array $user, array $permissionIds): array
    {
        $role = $this->requireRole($id);

        $ids = array_values(array_unique(array_map('intval', $permissionIds)));
        $valid = $this->permissions->existingIds($ids);
        $unknown = array_diff($ids, $valid);

        if ($unknown !== []) {
            throw new ApiException(
                'Unknown permission id(s): ' . implode(', ', $unknown) . '.',
                422
            );
        }

        $this->permissions->replaceForRole($id, $valid);

        $this->log->record(
            'Permissions Assigned',
            $user['user_id'],
            $id,
            null,
            count($valid) . ' permission(s) set on ' . $role['name']
        );

        return $this->get($id);
    }

    /**
     * A user's role lives in User.role_id and nowhere else. Changing it here is
     * the only supported way, so the log is guaranteed to see it.
     */
    public function assignUserRole(int $targetUserId, int $roleId, array $user): array
    {
        $role = $this->requireRole($roleId);
        $target = $this->users->findById($targetUserId);

        if ($target === null) {
            throw new ApiException('User not found.', 404);
        }

        if ($role['status'] !== 'active') {
            throw new ApiException('An inactive role cannot be assigned.', 409);
        }

        if ((int) $targetUserId === (int) $user['user_id']) {
            throw new ApiException('You cannot change your own role.', 409);
        }

        $this->users->changeRole($targetUserId, $roleId);

        $this->log->record(
            'User Role Changed',
            $user['user_id'],
            $roleId,
            $targetUserId,
            $target['full_name'] . ' is now ' . $role['name']
        );

        return [
            'user_id' => $targetUserId,
            'full_name' => $target['full_name'],
            'role' => $role['name'],
        ];
    }

    public function auditLog(?int $roleId): array
    {
        return $this->log->recent($roleId);
    }

    private function guardName(string $name, ?int $ignoreId = null): void
    {
        if ($name === '') {
            throw new ApiException('The role name is required.', 422);
        }

        if ($this->roles->nameExists($name, $ignoreId)) {
            throw new ApiException('A role with this name already exists.', 409);
        }
    }

    private function requireRole(int $id): array
    {
        $role = $this->roles->detailed($id);

        if ($role === null) {
            throw new ApiException('Role not found.', 404);
        }

        return $role;
    }
}
