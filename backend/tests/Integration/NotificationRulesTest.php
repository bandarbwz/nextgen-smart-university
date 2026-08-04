<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\AnnouncementBroadcastService;
use App\Services\ApiException;
use App\Services\EnrollmentService;
use App\Services\FinanceService;
use App\Services\NotificationCenterService;
use App\Services\NotificationService;
use Tests\TestCase;

class NotificationRulesTest extends TestCase
{
    private NotificationService $dispatcher;

    private NotificationCenterService $centre;

    private AnnouncementBroadcastService $announcements;

    private array $adminUser;

    private array $studentUser;

    private int $studentUserId;

    private int $studentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new NotificationService();
        $this->centre = new NotificationCenterService();
        $this->announcements = new AnnouncementBroadcastService();

        $structure = $this->createAcademicStructure();
        $student = $this->createStudent($structure);
        $adminId = $this->createUser('Administrator', 'admin@test.edu', 'Test Admin');

        $this->studentUserId = $student['user_id'];
        $this->studentId = $student['student_id'];

        $this->adminUser = $this->actingAs($adminId, 'Administrator');
        $this->studentUser = $this->actingAs($student['user_id'], 'Student');
    }

    public function testANotificationLandsInTheRecipientInbox(): void
    {
        $this->dispatcher->notify($this->studentUserId, 'Academic', 'Welcome', 'Term starts Monday.');

        $result = $this->centre->list($this->studentUser, []);

        $this->assertCount(1, $result['notifications']);
        $this->assertSame('Welcome', $result['notifications'][0]['title']);
        $this->assertSame(1, $result['unread_count']);
    }

    public function testMarkingAsReadClearsTheUnreadCount(): void
    {
        $this->dispatcher->notify($this->studentUserId, 'Academic', 'Welcome', 'Term starts Monday.');

        $notification = $this->centre->list($this->studentUser, [])['notifications'][0];

        $this->centre->markRead((int) $notification['id'], $this->studentUser);

        $this->assertSame(0, $this->centre->unreadCount($this->studentUser));
    }

    public function testMarkAllReadClearsEverything(): void
    {
        foreach (['One', 'Two', 'Three'] as $title) {
            $this->dispatcher->notify($this->studentUserId, 'System', $title, 'Body.');
        }

        $updated = $this->centre->markAllRead($this->studentUser);

        $this->assertSame(3, $updated);
        $this->assertSame(0, $this->centre->unreadCount($this->studentUser));
    }

    public function testAnArchivedNotificationLeavesTheDefaultList(): void
    {
        $this->dispatcher->notify($this->studentUserId, 'System', 'Old news', 'Body.');

        $notification = $this->centre->list($this->studentUser, [])['notifications'][0];

        $this->centre->archive((int) $notification['id'], $this->studentUser);

        $this->assertCount(0, $this->centre->list($this->studentUser, [])['notifications']);
        $this->assertCount(1, $this->centre->list($this->studentUser, ['archived' => true])['notifications']);
    }

    public function testTurningOffInAppStopsOrdinaryNotifications(): void
    {
        $this->centre->updatePreferences($this->studentUser, [
            'in_app_enabled' => false,
            'email_enabled' => true,
        ]);

        $this->dispatcher->notify($this->studentUserId, 'System', 'Ordinary', 'Body.');

        $this->assertCount(0, $this->centre->list($this->studentUser, [])['notifications']);
    }

    /**
     * A student who has muted notifications still has to be told their
     * examination was terminated or their account is on hold.
     */
    public function testACriticalNotificationIgnoresThePreference(): void
    {
        $this->db->prepare(
            'INSERT INTO NotificationPreference (user_id, in_app_enabled, email_enabled)
             VALUES (?, 0, 0)'
        )->execute([$this->studentUserId]);

        $this->dispatcher->notify(
            $this->studentUserId,
            'Finance',
            'Financial hold',
            'You cannot register.',
            ['priority' => 'Critical']
        );

        $this->assertCount(1, $this->centre->list($this->studentUser, [])['notifications']);
    }

    public function testPreferencesCannotDisableEveryChannel(): void
    {
        try {
            $this->centre->updatePreferences($this->studentUser, [
                'in_app_enabled' => false,
                'email_enabled' => false,
            ]);

            $this->fail('Disabling every delivery method must be refused.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->statusCode());
        }
    }

    public function testDefaultPreferencesExistWithoutARow(): void
    {
        $preferences = $this->centre->preferences($this->studentUser);

        $this->assertSame(1, (int) $preferences['in_app_enabled']);
        $this->assertSame(0, (int) $preferences['push_enabled']);
    }

    public function testABroadcastReachesEveryUserInTheAudience(): void
    {
        $this->createStudent(
            ['faculty_id' => 1, 'department_id' => 1, 'program_id' => 1],
            'second@test.edu',
            'Second Student'
        );

        $recipients = $this->announcements->broadcast($this->adminUser, [
            'title' => 'Campus closed',
            'message' => 'The campus is closed tomorrow.',
            'audience' => 'Student',
        ]);

        $this->assertSame(2, $recipients);
        $this->assertSame(1, $this->centre->unreadCount($this->studentUser));
    }

    public function testAPublishedAnnouncementFansOutAsNotifications(): void
    {
        $this->announcements->create($this->adminUser, [
            'title' => 'Library hours',
            'content' => 'The library now closes at ten.',
            'audience' => 'Student',
            'status' => 'published',
        ]);

        $this->assertSame(1, $this->centre->unreadCount($this->studentUser));
    }

    public function testADraftAnnouncementNotifiesNobody(): void
    {
        $this->announcements->create($this->adminUser, [
            'title' => 'Not ready',
            'content' => 'Draft body.',
            'audience' => 'Student',
            'status' => 'draft',
        ]);

        $this->assertSame(0, $this->centre->unreadCount($this->studentUser));
    }

    public function testPublishingADraftLaterFansOutOnce(): void
    {
        $announcement = $this->announcements->create($this->adminUser, [
            'title' => 'Exam timetable',
            'content' => 'Published soon.',
            'audience' => 'Student',
            'status' => 'draft',
        ]);

        $this->announcements->update((int) $announcement['id'], [
            'title' => 'Exam timetable',
            'content' => 'Now available.',
            'audience' => 'Student',
            'status' => 'published',
        ]);

        $this->announcements->update((int) $announcement['id'], [
            'title' => 'Exam timetable',
            'content' => 'Now available.',
            'audience' => 'Student',
            'status' => 'published',
        ]);

        $this->assertSame(1, $this->centre->unreadCount($this->studentUser));
    }

    public function testAStudentOnlySeesAnnouncementsForTheirAudience(): void
    {
        $this->announcements->create($this->adminUser, [
            'title' => 'For lecturers',
            'content' => 'Staff meeting.',
            'audience' => 'Lecturer',
            'status' => 'published',
        ]);

        $this->announcements->create($this->adminUser, [
            'title' => 'For everyone',
            'content' => 'Campus notice.',
            'audience' => 'All',
            'status' => 'published',
        ]);

        $visible = $this->announcements->list($this->studentUser);

        $this->assertCount(1, $visible);
        $this->assertSame('For everyone', $visible[0]['title']);
    }

    /**
     * The notification is a side effect of the enrolment, never a condition of
     * it, so this proves the wiring exists at all.
     */
    public function testApprovingAnEnrolmentNotifiesTheStudent(): void
    {
        $lecturer = $this->createLecturer(
            ['faculty_id' => 1, 'department_id' => 1],
            'lecturer-notify@test.edu'
        );

        $courseId = $this->createCourse(1, 'CS900');
        $sectionId = $this->createSection($courseId, $lecturer['lecturer_id'], 1);

        $enrollmentId = $this->enrol($this->studentId, $sectionId, 'Pending');

        (new EnrollmentService())->approve($enrollmentId, $this->adminUser['user_id']);

        $notifications = $this->centre->list($this->studentUser, [])['notifications'];

        $this->assertCount(1, $notifications);
        $this->assertSame('Academic', $notifications[0]['module']);
        $this->assertStringContainsString('approved', $notifications[0]['title']);
    }

    public function testAFinancialHoldNotifiesTheStudentAsCritical(): void
    {
        (new FinanceService())->applyHold($this->adminUser, [
            'student_id' => $this->studentId,
            'reason' => 'Unpaid tuition.',
        ]);

        $notifications = $this->centre->list($this->studentUser, [])['notifications'];

        $this->assertCount(1, $notifications);
        $this->assertSame('Critical', $notifications[0]['priority']);
        $this->assertSame('Finance', $notifications[0]['module']);
    }
}
