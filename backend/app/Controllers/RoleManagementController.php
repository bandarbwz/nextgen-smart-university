<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\RoleManagementService;
use App\Validation\RoleValidator;

class RoleManagementController extends Controller
{
    public function __construct(
        private readonly RoleManagementService $roleManagement = new RoleManagementService(),
        private readonly RoleValidator $validator = new RoleValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $this->authenticateAsAdministrator();

        $roles = $this->run(fn () => $this->roleManagement->list());

        Response::success('Roles retrieved.', ['roles' => $roles]);
    }

    public function show(string $id): void
    {
        $this->authenticateAsAdministrator();

        $role = $this->run(fn () => $this->roleManagement->get((int) $id));

        Response::success('Role retrieved.', ['role' => $role]);
    }

    public function store(): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->role($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $role = $this->run(fn () => $this->roleManagement->create($user, $data));

        Response::success('Role created.', ['role' => $role], 201);
    }

    public function update(string $id): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->role($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $role = $this->run(fn () => $this->roleManagement->update((int) $id, $user, $data));

        Response::success('Role updated.', ['role' => $role]);
    }

    public function destroy(string $id): void
    {
        $user = $this->authenticateAsAdministrator();

        $this->run(fn () => $this->roleManagement->delete((int) $id, $user));

        Response::success('Role deleted.');
    }

    public function permissions(): void
    {
        $this->authenticateAsAdministrator();

        $catalogue = $this->run(fn () => $this->roleManagement->permissionCatalogue());

        Response::success('Permissions retrieved.', ['permissions' => $catalogue]);
    }

    public function assignPermissions(string $id): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->permissions($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $role = $this->run(
            fn () => $this->roleManagement->assignPermissions((int) $id, $user, $data['permission_ids'])
        );

        Response::success('Permissions assigned.', ['role' => $role]);
    }

    public function assignUserRole(string $userId): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->userRole($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $result = $this->run(
            fn () => $this->roleManagement->assignUserRole((int) $userId, (int) $data['role_id'], $user)
        );

        Response::success('User role updated.', $result);
    }

    public function auditLog(): void
    {
        $this->authenticateAsAdministrator();

        $log = $this->run(fn () => $this->roleManagement->auditLog($this->queryInt('role_id')));

        Response::success('Authorization log retrieved.', ['log' => $log]);
    }
}
