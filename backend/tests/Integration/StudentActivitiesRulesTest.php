<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ActivityPointService;
use App\Services\ApiException;
use App\Services\ClubService;
use App\Services\EventAttendanceService;
use App\Services\EventRegistrationService;
use App\Services\EventService;
use Tests\TestCase;

class StudentActivitiesRulesTest extends TestCase
{
    private ClubService $clubs;

    private EventService $events;

    private EventRegistrationService $registrations;

    private EventAttendanceService $attendance;

    private ActivityPointService $points;

    private array $stadUser;

    private array $studentUser;

    private array $otherStudentUser;

    private int $studentId;

    private int $otherStudentId;

    private int $eventId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clubs = new ClubService();
        $this->events = new EventService();
        $this->registrations = new EventRegistrationService();
        $this->attendance = new EventAttendanceService();
        $this->points = new ActivityPointService();

        $structure = $this->createAcademicStructure();
        $student = $this->createStudent($structure);
        $other = $this->createStudent($structure, 'other-student@test.edu', 'Other Student');
        $stadId = $this->createUser('STAD Staff', 'stad@test.edu', 'STAD Officer');

        $this->studentId = $student['student_id'];
        $this->otherStudentId = $other['student_id'];

        $this->stadUser = $this->actingAs($stadId, 'STAD Staff');
        $this->studentUser = $this->actingAs($student['user_id'], 'Student');
        $this->otherStudentUser = $this->actingAs($other['user_id'], 'Student');

        $this->eventId = (int) $this->events->create($this->stadUser, $this->eventFields())['id'];
    }

    public function testAClubNameCannotBeUsedTwice(): void
    {
        $this->clubs->create(['club_name' => 'Robotics Society']);

        $this->expectException(ApiException::class);

        $this->clubs->create(['club_name' => 'Robotics Society']);
    }

    public function testStudentsOnlySeePublishedEvents(): void
    {
        $this->events->create($this->stadUser, ['status' => 'draft'] + $this->eventFields());

        $forStad = $this->events->list($this->stadUser, []);
        $forStudent = $this->events->list($this->studentUser, []);

        $this->assertCount(2, $forStad);
        $this->assertCount(1, $forStudent);
    }

    public function testADraftEventIsNotFoundForAStudent(): void
    {
        $draftId = (int) $this->events->create(
            $this->stadUser,
            ['status' => 'draft'] + $this->eventFields()
        )['id'];

        try {
            $this->events->get($draftId, $this->studentUser);

            $this->fail('A student must not reach a draft event.');
        } catch (ApiException $exception) {
            $this->assertSame(404, $exception->statusCode());
        }
    }

    public function testTheRegistrationDeadlineMustBeBeforeTheEventStarts(): void
    {
        $fields = $this->eventFields();
        $fields['registration_deadline'] = gmdate('Y-m-d H:i:s', time() + 6 * 86400);

        try {
            $this->events->create($this->stadUser, $fields);

            $this->fail('A deadline after the event starts must be refused.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->statusCode());
        }
    }

    public function testAStudentCannotRegisterTwiceForTheSameEvent(): void
    {
        $this->registrations->register($this->studentUser, $this->eventId);

        $this->expectException(ApiException::class);

        $this->registrations->register($this->studentUser, $this->eventId);
    }

    public function testRegistrationClosesAfterTheDeadline(): void
    {
        $fields = $this->eventFields();
        $fields['registration_deadline'] = gmdate('Y-m-d H:i:s', time() - 3600);
        $fields['event_date'] = gmdate('Y-m-d', time() + 86400);

        $closedId = (int) $this->events->create($this->stadUser, $fields)['id'];

        $this->expectException(ApiException::class);

        $this->registrations->register($this->studentUser, $closedId);
    }

    public function testACancelledEventRefusesRegistration(): void
    {
        $this->events->cancel($this->eventId, $this->stadUser);

        $this->expectException(ApiException::class);

        $this->registrations->register($this->studentUser, $this->eventId);
    }

    /**
     * A pending request holds no seat, so two students may both be waiting on a
     * single place. The limit bites when the second one is approved.
     */
    public function testAPendingRequestDoesNotHoldASeat(): void
    {
        $fields = $this->eventFields();
        $fields['maximum_participants'] = 1;

        $smallId = (int) $this->events->create($this->stadUser, $fields)['id'];

        $first = $this->registrations->register($this->studentUser, $smallId);
        $second = $this->registrations->register($this->otherStudentUser, $smallId);

        $this->registrations->approve((int) $first['id'], $this->stadUser);

        try {
            $this->registrations->approve((int) $second['id'], $this->stadUser);

            $this->fail('Approving past the participant limit must be refused.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public function testTheParticipantLimitCannotDropBelowTheApprovedCount(): void
    {
        $registration = $this->registrations->register($this->studentUser, $this->eventId);
        $this->registrations->approve((int) $registration['id'], $this->stadUser);

        $fields = $this->eventFields();
        $fields['maximum_participants'] = 0;

        $this->expectException(ApiException::class);

        $this->events->update($this->eventId, $this->stadUser, $fields);
    }

    public function testAStudentCanCancelTheirOwnRegistration(): void
    {
        $registration = $this->registrations->register($this->studentUser, $this->eventId);

        $cancelled = $this->registrations->cancel((int) $registration['id'], $this->studentUser);

        $this->assertSame('Cancelled', $cancelled['status']);
    }

    public function testARegistrationCannotBeCancelledOnceAttendanceIsRecorded(): void
    {
        $registration = $this->registrations->register($this->studentUser, $this->eventId);
        $this->registrations->approve((int) $registration['id'], $this->stadUser);
        $this->attendance->recordManually((int) $registration['id'], $this->stadUser);

        $this->expectException(ApiException::class);

        $this->registrations->cancel((int) $registration['id'], $this->studentUser);
    }

    public function testAttendanceCannotBeRecordedTwice(): void
    {
        $registration = $this->registrations->register($this->studentUser, $this->eventId);
        $this->registrations->approve((int) $registration['id'], $this->stadUser);
        $this->attendance->recordManually((int) $registration['id'], $this->stadUser);

        $this->expectException(ApiException::class);

        $this->attendance->recordManually((int) $registration['id'], $this->stadUser);
    }

    public function testAttendanceNeedsAnApprovedRegistration(): void
    {
        $registration = $this->registrations->register($this->studentUser, $this->eventId);

        $this->expectException(ApiException::class);

        $this->attendance->recordManually((int) $registration['id'], $this->stadUser);
    }

    public function testAQrCodeCannotBeOpenedBeforeTheEventStarts(): void
    {
        $this->expectException(ApiException::class);

        $this->attendance->openQr($this->eventId, $this->stadUser);
    }

    public function testAQrScanRecordsAttendanceForAnApprovedStudent(): void
    {
        $eventId = $this->createRunningEvent();

        $registration = $this->registrations->register($this->studentUser, $eventId);
        $this->registrations->approve((int) $registration['id'], $this->stadUser);

        $session = $this->attendance->openQr($eventId, $this->stadUser);
        $recorded = $this->attendance->scan($this->studentUser, $session['qr_token']);

        $this->assertSame('QR', $recorded['attendance_method']);
        $this->assertSame((int) $registration['id'], (int) $recorded['registration_id']);
    }

    public function testAQrScanIsRefusedWithoutAnApprovedRegistration(): void
    {
        $eventId = $this->createRunningEvent();

        $this->registrations->register($this->studentUser, $eventId);

        $session = $this->attendance->openQr($eventId, $this->stadUser);

        try {
            $this->attendance->scan($this->studentUser, $session['qr_token']);

            $this->fail('A pending registration must not pass the QR check.');
        } catch (ApiException $exception) {
            $this->assertSame(403, $exception->statusCode());
        }
    }

    public function testAnExpiredQrTokenIsRefused(): void
    {
        $eventId = $this->createRunningEvent();

        $registration = $this->registrations->register($this->studentUser, $eventId);
        $this->registrations->approve((int) $registration['id'], $this->stadUser);

        $session = $this->attendance->openQr($eventId, $this->stadUser);

        $this->db->prepare('UPDATE EventQrSession SET expires_at = :expired WHERE id = :id')
            ->execute([
                'expired' => gmdate('Y-m-d H:i:s', time() - 60),
                'id' => $session['id'],
            ]);

        $this->expectException(ApiException::class);

        $this->attendance->scan($this->studentUser, $session['qr_token']);
    }

    public function testAnInvalidTokenIsRefused(): void
    {
        $this->expectException(ApiException::class);

        $this->attendance->scan($this->studentUser, 'not-a-real-token');
    }

    public function testPointsAreOnlyAwardedAfterVerifiedAttendance(): void
    {
        $registration = $this->registrations->register($this->studentUser, $this->eventId);
        $this->registrations->approve((int) $registration['id'], $this->stadUser);

        try {
            $this->points->award($this->stadUser, $this->studentId, $this->eventId, 10);

            $this->fail('Points must not be awarded without attendance.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public function testPointsAreAwardedOnceAttendanceIsVerified(): void
    {
        $registration = $this->registrations->register($this->studentUser, $this->eventId);
        $this->registrations->approve((int) $registration['id'], $this->stadUser);
        $this->attendance->recordManually((int) $registration['id'], $this->stadUser);

        $awarded = $this->points->award($this->stadUser, $this->studentId, $this->eventId, 10);

        $this->assertSame(10, (int) $awarded['points']);
        $this->assertSame(10, $this->points->forStudent($this->studentId)['total_points']);
    }

    public function testPointsCannotBeAwardedTwiceForTheSameEvent(): void
    {
        $registration = $this->registrations->register($this->studentUser, $this->eventId);
        $this->registrations->approve((int) $registration['id'], $this->stadUser);
        $this->attendance->recordManually((int) $registration['id'], $this->stadUser);

        $this->points->award($this->stadUser, $this->studentId, $this->eventId, 10);

        $this->expectException(ApiException::class);

        $this->points->award($this->stadUser, $this->studentId, $this->eventId, 10);
    }

    public function testAnEventWithRegistrationsCannotBeDeleted(): void
    {
        $this->registrations->register($this->studentUser, $this->eventId);

        $this->expectException(ApiException::class);

        $this->events->delete($this->eventId);
    }

    public function testSeatsRemainingReflectsApprovalsOnly(): void
    {
        $first = $this->registrations->register($this->studentUser, $this->eventId);
        $this->registrations->register($this->otherStudentUser, $this->eventId);

        $before = $this->events->get($this->eventId, $this->stadUser);

        $this->registrations->approve((int) $first['id'], $this->stadUser);

        $after = $this->events->get($this->eventId, $this->stadUser);

        $this->assertSame(0, $before['registered_count']);
        $this->assertSame(1, $after['registered_count']);
        $this->assertSame(9, $after['seats_remaining']);
    }

    private function createRunningEvent(): int
    {
        $fields = $this->eventFields();
        $fields['event_date'] = gmdate('Y-m-d');
        $fields['start_time'] = gmdate('H:i:s', time() - 1800);
        $fields['end_time'] = gmdate('H:i:s', time() + 1800);
        $fields['registration_deadline'] = gmdate('Y-m-d H:i:s', time() - 3600);

        $id = (int) $this->events->create($this->stadUser, $fields)['id'];

        // The deadline is in the past so the event can be running; reopen it so
        // the fixture can still register students.
        $this->db->prepare('UPDATE Event SET registration_deadline = :deadline WHERE id = :id')
            ->execute([
                'deadline' => gmdate('Y-m-d H:i:s', time() + 3600),
                'id' => $id,
            ]);

        return $id;
    }

    private function eventFields(): array
    {
        return [
            'event_name' => 'Line Follower Competition',
            'description' => 'Teams of three.',
            'event_type' => 'Competition',
            'venue' => 'Engineering Hall B',
            'event_date' => gmdate('Y-m-d', time() + 5 * 86400),
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'registration_deadline' => gmdate('Y-m-d H:i:s', time() + 2 * 86400),
            'maximum_participants' => 10,
            'award_points' => 10,
            'qr_enabled' => true,
            'status' => 'published',
        ];
    }
}
