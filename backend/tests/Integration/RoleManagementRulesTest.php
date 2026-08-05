<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\RoleManagementService;
use Tests\TestCase;

class RoleManagementRulesTest extends TestCase
{
    private RoleManagementService $roleManagement;

    private array $adminUser;

    private int $studentUserId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleManagement = new RoleManagementService();

        // The harness keeps Role between tests because every test needs the six
        // defaults. Custom roles are this module's business, so they go here.
        $this->db->exec('DELETE FROM Role WHERE is_system = 0');

        $adminId = $this->createUser('Administrator', 'admin@test.edu', 'Test Admin');
        $this->studentUserId = $this->createUser('Student', 'student@test.edu', 'Test Student');

        $this->adminUser = $this->actingAs($adminId, 'Administrator');
    }

    public function testTheSixDefaultRolesAreMarkedAsSystemRoles(): void
    {
        $roles = $this->roleManagement->list();

        $system = array_filter($roles, static fn (array $role): bool => (bool) $role['is_system']);

        $this->assertCount(6, $system);
    }

    /**
     * Coordinator deliberately, because no test user holds it. A role with users
     * is refused by a different guard, which would make this pass for the wrong
     * reason and hide the is_system rule entirely.
     */
    public function testASystemRoleCannotBeDeleted(): void
    {
        $coordinator = $this->roleId('Coordinator');

        $this->assertSame(0, (int) $this->scalar(
            'SELECT COUNT(*) FROM User WHERE role_id = ?',
            [$coordinator]
        ));

        try {
            $this->roleManagement->delete($coordinator, $this->adminUser);

            $this->fail('A system default role must not be deletable.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
            $this->assertStringContainsString('system default role', $exception->getMessage());
        }
    }

    public function testASystemRoleCannotBeRenamed(): void
    {
        $student = $this->roleId('Student');

        try {
            $this->roleManagement->update($student, $this->adminUser, [
                'name' => 'Pupil',
                'description' => 'Renamed',
            ]);

            $this->fail('A system role must not be renamable.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public function testASystemRoleCanStillBeDescribedAndDeactivated(): void
    {
        $student = $this->roleId('Student');

        $updated = $this->roleManagement->update($student, $this->adminUser, [
            'name' => 'Student',
            'description' => 'A clearer description',
            'status' => 'inactive',
        ]);

        $this->assertSame('A clearer description', $updated['description']);
        $this->assertSame('inactive', $updated['status']);
    }

    public function testACustomRoleCanBeCreatedAndDeleted(): void
    {
        $role = $this->roleManagement->create($this->adminUser, [
            'name' => 'Library Staff',
            'description' => 'Manages the library',
        ]);

        $this->assertSame(0, (int) $role['is_system']);

        $this->roleManagement->delete((int) $role['id'], $this->adminUser);

        $this->assertCount(6, $this->roleManagement->list());
    }

    public function testRoleNamesMustBeUnique(): void
    {
        $this->roleManagement->create($this->adminUser, ['name' => 'Library Staff']);

        $this->expectException(ApiException::class);

        $this->roleManagement->create($this->adminUser, ['name' => 'Library Staff']);
    }

    public function testARoleInUseCannotBeDeleted(): void
    {
        $role = $this->roleManagement->create($this->adminUser, ['name' => 'Library Staff']);

        $this->roleManagement->assignUserRole(
            $this->studentUserId,
            (int) $role['id'],
            $this->adminUser
        );

        try {
            $this->roleManagement->delete((int) $role['id'], $this->adminUser);

            $this->fail('A role still assigned to somebody must not be deletable.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public function testAssigningPermissionsReplacesTheWholeSet(): void
    {
        $role = $this->roleManagement->create($this->adminUser, ['name' => 'Library Staff']);
        $ids = $this->permissionIds(3);

        $withThree = $this->roleManagement->assignPermissions(
            (int) $role['id'],
            $this->adminUser,
            $ids
        );

        $withOne = $this->roleManagement->assignPermissions(
            (int) $role['id'],
            $this->adminUser,
            [$ids[0]]
        );

        $this->assertCount(3, $withThree['permission_ids']);
        $this->assertCount(1, $withOne['permission_ids']);
    }

    public function testAnUnknownPermissionIdIsRefused(): void
    {
        $role = $this->roleManagement->create($this->adminUser, ['name' => 'Library Staff']);

        try {
            $this->roleManagement->assignPermissions(
                (int) $role['id'],
                $this->adminUser,
                [999999]
            );

            $this->fail('An unknown permission id must be refused.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->statusCode());
        }
    }

    public function testDuplicatePermissionIdsAreCollapsed(): void
    {
        $role = $this->roleManagement->create($this->adminUser, ['name' => 'Library Staff']);
        $ids = $this->permissionIds(1);

        $updated = $this->roleManagement->assignPermissions(
            (int) $role['id'],
            $this->adminUser,
            [$ids[0], $ids[0], $ids[0]]
        );

        $this->assertCount(1, $updated['permission_ids']);
    }

    public function testChangingAUserRoleUpdatesTheOneSourceOfTruth(): void
    {
        $lecturer = $this->roleId('Lecturer');

        $this->roleManagement->assignUserRole($this->studentUserId, $lecturer, $this->adminUser);

        $roleId = (int) $this->scalar('SELECT role_id FROM User WHERE id = ?', [$this->studentUserId]);

        $this->assertSame($lecturer, $roleId);
    }

    public function testAnInactiveRoleCannotBeAssigned(): void
    {
        $role = $this->roleManagement->create($this->adminUser, [
            'name' => 'Library Staff',
            'status' => 'inactive',
        ]);

        $this->expectException(ApiException::class);

        $this->roleManagement->assignUserRole(
            $this->studentUserId,
            (int) $role['id'],
            $this->adminUser
        );
    }

    /**
     * An administrator demoting themselves by accident would lock the platform
     * out of its own administration.
     */
    public function testAnAdministratorCannotChangeTheirOwnRole(): void
    {
        $student = $this->roleId('Student');

        try {
            $this->roleManagement->assignUserRole(
                $this->adminUser['user_id'],
                $student,
                $this->adminUser
            );

            $this->fail('Changing your own role must be refused.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public function testEveryAuthorizationChangeIsLogged(): void
    {
        $role = $this->roleManagement->create($this->adminUser, ['name' => 'Library Staff']);

        $this->roleManagement->assignPermissions(
            (int) $role['id'],
            $this->adminUser,
            $this->permissionIds(2)
        );

        $this->roleManagement->update((int) $role['id'], $this->adminUser, [
            'name' => 'Library Team',
        ]);

        $this->roleManagement->assignUserRole(
            $this->studentUserId,
            (int) $role['id'],
            $this->adminUser
        );

        $actions = array_column($this->roleManagement->auditLog(null), 'action');

        $this->assertContains('Role Created', $actions);
        $this->assertContains('Permissions Assigned', $actions);
        $this->assertContains('Role Updated', $actions);
        $this->assertContains('User Role Changed', $actions);
    }

    public function testTheLogNamesWhoDidItAndToWhom(): void
    {
        $lecturer = $this->roleId('Lecturer');

        $this->roleManagement->assignUserRole($this->studentUserId, $lecturer, $this->adminUser);

        $entry = $this->roleManagement->auditLog(null)[0];

        $this->assertSame('User Role Changed', $entry['action']);
        $this->assertSame('Test Admin', $entry['performed_by_name']);
        $this->assertSame('Test Student', $entry['target_user_name']);
    }

    public function testThePermissionCatalogueIsGroupedByModule(): void
    {
        $catalogue = $this->roleManagement->permissionCatalogue();

        $this->assertArrayHasKey('Academic', $catalogue);
        $this->assertGreaterThan(1, count($catalogue));
    }

    private function roleId(string $name): int
    {
        return (int) $this->scalar('SELECT id FROM Role WHERE name = ?', [$name]);
    }

    private function permissionIds(int $count): array
    {
        $statement = $this->db->prepare('SELECT id FROM Permission ORDER BY id LIMIT ' . $count);
        $statement->execute();

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }
}
