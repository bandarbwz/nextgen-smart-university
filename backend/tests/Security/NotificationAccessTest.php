<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Services\ApiException;
use App\Services\NotificationCenterService;
use App\Services\NotificationService;
use Tests\TestCase;

class NotificationAccessTest extends TestCase
{
    private NotificationService $dispatcher;

    private NotificationCenterService $centre;

    private array $studentUser;

    private array $classmateUser;

    private int $studentUserId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new NotificationService();
        $this->centre = new NotificationCenterService();

        $structure = $this->createAcademicStructure();
        $student = $this->createStudent($structure);
        $classmate = $this->createStudent($structure, 'classmate@test.edu', 'Classmate');

        $this->studentUserId = $student['user_id'];
        $this->studentUser = $this->actingAs($student['user_id'], 'Student');
        $this->classmateUser = $this->actingAs($classmate['user_id'], 'Student');
    }

    public function testANotificationIsOnlyVisibleToItsRecipient(): void
    {
        $this->dispatcher->notify($this->studentUserId, 'Finance', 'Your balance', 'You owe 4000.');

        $this->assertCount(1, $this->centre->list($this->studentUser, [])['notifications']);
        $this->assertCount(0, $this->centre->list($this->classmateUser, [])['notifications']);
    }

    public function testAnotherUsersNotificationLooksMissingRatherThanForbidden(): void
    {
        $this->dispatcher->notify($this->studentUserId, 'Finance', 'Your balance', 'You owe 4000.');

        $notification = $this->centre->list($this->studentUser, [])['notifications'][0];

        try {
            $this->centre->get((int) $notification['id'], $this->classmateUser);

            $this->fail('A classmate must not reach another user notification.');
        } catch (ApiException $exception) {
            $this->assertSame(404, $exception->statusCode());
        }
    }

    public function testAUserCannotMarkAnotherUsersNotificationAsRead(): void
    {
        $this->dispatcher->notify($this->studentUserId, 'Academic', 'Result published', 'Body.');

        $notification = $this->centre->list($this->studentUser, [])['notifications'][0];

        $this->expectException(ApiException::class);

        $this->centre->markRead((int) $notification['id'], $this->classmateUser);
    }

    public function testAUserCannotDeleteAnotherUsersNotification(): void
    {
        $this->dispatcher->notify($this->studentUserId, 'Academic', 'Result published', 'Body.');

        $notification = $this->centre->list($this->studentUser, [])['notifications'][0];

        try {
            $this->centre->delete((int) $notification['id'], $this->classmateUser);

            $this->fail('A classmate must not delete another user notification.');
        } catch (ApiException $exception) {
            $this->assertSame(404, $exception->statusCode());
        }

        $this->assertCount(1, $this->centre->list($this->studentUser, [])['notifications']);
    }

    public function testDeleteAllOnlyClearsTheCallersOwnInbox(): void
    {
        $this->dispatcher->notify($this->studentUserId, 'System', 'Mine', 'Body.');
        $this->dispatcher->notify(
            (int) $this->classmateUser['user_id'],
            'System',
            'Theirs',
            'Body.'
        );

        $this->centre->deleteAll($this->studentUser);

        $this->assertCount(0, $this->centre->list($this->studentUser, [])['notifications']);
        $this->assertCount(1, $this->centre->list($this->classmateUser, [])['notifications']);
    }

    public function testPreferencesAreScopedToTheCaller(): void
    {
        $this->centre->updatePreferences($this->studentUser, [
            'in_app_enabled' => false,
            'email_enabled' => true,
        ]);

        $mine = $this->centre->preferences($this->studentUser);
        $theirs = $this->centre->preferences($this->classmateUser);

        $this->assertSame(0, (int) $mine['in_app_enabled']);
        $this->assertSame(1, (int) $theirs['in_app_enabled']);
    }
}
