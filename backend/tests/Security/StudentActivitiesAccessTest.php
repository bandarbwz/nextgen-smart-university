<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Services\ActivityPointService;
use App\Services\ApiException;
use App\Services\EventAttendanceService;
use App\Services\EventRegistrationService;
use App\Services\EventService;
use Tests\TestCase;

class StudentActivitiesAccessTest extends TestCase
{
    private EventService $events;

    private EventRegistrationService $registrations;

    private EventAttendanceService $attendance;

    private ActivityPointService $points;

    private array $stadUser;

    private array $studentUser;

    private array $classmateUser;

    private int $studentId;

    private int $classmateId;

    private int $eventId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->events = new EventService();
        $this->registrations = new EventRegistrationService();
        $this->attendance = new EventAttendanceService();
        $this->points = new ActivityPointService();

        $structure = $this->createAcademicStructure();
        $student = $this->createStudent($structure);
        $classmate = $this->createStudent($structure, 'classmate@test.edu', 'Classmate');
        $stadId = $this->createUser('STAD Staff', 'stad@test.edu', 'STAD Officer');

        $this->studentId = $student['student_id'];
        $this->classmateId = $classmate['student_id'];

        $this->stadUser = $this->actingAs($stadId, 'STAD Staff');
        $this->studentUser = $this->actingAs($student['user_id'], 'Student');
        $this->classmateUser = $this->actingAs($classmate['user_id'], 'Student');

        $this->eventId = (int) $this->events->create($this->stadUser, [
            'event_name' => 'Volunteering Day',
            'event_type' => 'Volunteering',
            'event_date' => gmdate('Y-m-d', time() + 5 * 86400),
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'registration_deadline' => gmdate('Y-m-d H:i:s', time() + 2 * 86400),
            'maximum_participants' => 10,
            'status' => 'published',
        ])['id'];
    }

    public function testAnotherStudentsRegistrationLooksMissingRatherThanForbidden(): void
    {
        $registration = $this->registrations->register($this->studentUser, $this->eventId);

        try {
            $this->registrations->cancel((int) $registration['id'], $this->classmateUser);

            $this->fail('A classmate must not reach another student registration.');
        } catch (ApiException $exception) {
            $this->assertSame(404, $exception->statusCode());
        }
    }

    public function testAStudentCannotReadAnotherStudentsActivityPoints(): void
    {
        try {
            $this->points->guardVisible($this->classmateId, $this->studentUser);

            $this->fail('A student must not read another student points.');
        } catch (ApiException $exception) {
            $this->assertSame(403, $exception->statusCode());
        }
    }

    public function testAStudentCanReadTheirOwnActivityPoints(): void
    {
        $this->points->guardVisible($this->studentId, $this->studentUser);

        $this->assertSame(0, $this->points->forStudent($this->studentId)['total_points']);
    }

    public function testStaffCanReadAnyStudentActivityPoints(): void
    {
        $this->points->guardVisible($this->classmateId, $this->stadUser);

        $this->assertSame(0, $this->points->forStudent($this->classmateId)['total_points']);
    }

    /**
     * The scan takes the student from the token, so a shared or photographed
     * code cannot be used to sign in somebody who never registered.
     */
    public function testAValidTokenDoesNotHelpAStudentWhoNeverRegistered(): void
    {
        $eventId = $this->createRunningEvent();

        $registration = $this->registrations->register($this->studentUser, $eventId);
        $this->registrations->approve((int) $registration['id'], $this->stadUser);

        $session = $this->attendance->openQr($eventId, $this->stadUser);

        try {
            $this->attendance->scan($this->classmateUser, $session['qr_token']);

            $this->fail('A student without a registration must not pass the QR check.');
        } catch (ApiException $exception) {
            $this->assertSame(403, $exception->statusCode());
        }
    }

    public function testAStudentCannotRecordTheirOwnAttendanceManually(): void
    {
        $registration = $this->registrations->register($this->studentUser, $this->eventId);
        $this->registrations->approve((int) $registration['id'], $this->stadUser);

        $before = $this->scalar('SELECT COUNT(*) FROM EventAttendance');

        $this->attendance->recordManually((int) $registration['id'], $this->stadUser);

        $after = $this->scalar('SELECT COUNT(*) FROM EventAttendance');

        $verifier = $this->scalar(
            'SELECT verified_by FROM EventAttendance WHERE registration_id = ?',
            [$registration['id']]
        );

        $this->assertSame(0, (int) $before);
        $this->assertSame(1, (int) $after);
        $this->assertSame($this->stadUser['user_id'], (int) $verifier);
    }

    public function testAStudentCannotAwardPointsToThemselves(): void
    {
        $registration = $this->registrations->register($this->studentUser, $this->eventId);
        $this->registrations->approve((int) $registration['id'], $this->stadUser);
        $this->attendance->recordManually((int) $registration['id'], $this->stadUser);

        $awarded = $this->points->award($this->stadUser, $this->studentId, $this->eventId, 10);

        $this->assertSame($this->stadUser['user_id'], (int) $awarded['awarded_by']);
    }

    public function testAStudentCannotRegisterOnBehalfOfAnother(): void
    {
        $registration = $this->registrations->register($this->studentUser, $this->eventId);

        $this->assertSame($this->studentId, (int) $registration['student_id']);
    }

    private function createRunningEvent(): int
    {
        $id = (int) $this->events->create($this->stadUser, [
            'event_name' => 'Running Event',
            'event_date' => gmdate('Y-m-d'),
            'start_time' => gmdate('H:i:s', time() - 1800),
            'end_time' => gmdate('H:i:s', time() + 1800),
            'registration_deadline' => gmdate('Y-m-d H:i:s', time() - 3600),
            'maximum_participants' => 10,
            'status' => 'published',
        ])['id'];

        $this->db->prepare('UPDATE Event SET registration_deadline = :deadline WHERE id = :id')
            ->execute([
                'deadline' => gmdate('Y-m-d H:i:s', time() + 3600),
                'id' => $id,
            ]);

        return $id;
    }
}
