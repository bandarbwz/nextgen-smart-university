<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\CalendarService;
use App\Services\CalendarSyncService;
use Tests\TestCase;

class CalendarSyncTest extends TestCase
{
    private CalendarSyncService $sync;

    private CalendarService $calendar;

    private array $structure;

    private array $student;

    private array $lecturer;

    private array $studentUser;

    private int $sectionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sync = new CalendarSyncService();
        $this->calendar = new CalendarService();

        $this->structure = $this->createAcademicStructure();
        $this->lecturer = $this->createLecturer($this->structure);
        $this->student = $this->createStudent($this->structure);

        $courseId = $this->createCourse($this->structure['department_id'], 'CS101');
        $this->sectionId = $this->createSection(
            $courseId,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id']
        );

        $this->enrol($this->student['student_id'], $this->sectionId, 'Approved');
        $this->addSchedule($this->sectionId, 'Monday', '09:00:00', '11:00:00');

        $this->studentUser = $this->actingAs($this->student['user_id'], 'Student');
    }

    public function testSyncExpandsAWeeklyClassAcrossTheSemester(): void
    {
        $result = $this->sync->synchronise($this->studentUser);

        $this->assertSame(
            19,
            $result['classes'],
            'The seeded semester spans 2026-09-01 to 2027-01-15, which contains 19 Mondays.'
        );

        $everyEventIsMonday = (int) $this->scalar(
            "SELECT COUNT(*) FROM CalendarEvent WHERE event_type = 'Class' AND DAYOFWEEK(start_datetime) != 2"
        );

        $this->assertSame(0, $everyEventIsMonday, 'Every generated class must land on a Monday.');
    }

    public function testRunningSyncTwiceCreatesNoDuplicates(): void
    {
        $this->sync->synchronise($this->studentUser);
        $afterFirst = (int) $this->scalar('SELECT COUNT(*) FROM CalendarEvent');

        $this->sync->synchronise($this->studentUser);
        $afterSecond = (int) $this->scalar('SELECT COUNT(*) FROM CalendarEvent');

        $this->assertSame($afterFirst, $afterSecond, 'Synchronisation must be idempotent.');
    }

    public function testSyncRefreshesTheTitleOfAnAlreadyGeneratedEvent(): void
    {
        $this->sync->synchronise($this->studentUser);

        $this->db->exec("UPDATE Course SET course_name = 'Renamed Course' WHERE course_code = 'CS101'");

        $this->sync->synchronise($this->studentUser);

        $this->assertSame(
            0,
            (int) $this->scalar(
                "SELECT COUNT(*) FROM CalendarEvent WHERE event_type = 'Class' AND title NOT LIKE '%Renamed Course%'"
            ),
            'A repeat sync should update titles in place rather than leaving stale copies.'
        );
    }

    public function testAnInstantEventOnTheRangeBoundaryIsReturned(): void
    {
        $this->db->prepare(
            "INSERT INTO CalendarEvent
                (user_id, title, event_type, module, reference_id, start_datetime, end_datetime)
             VALUES (?, 'Assignment deadline', 'Assignment', 'LMS', 1, ?, ?)"
        )->execute([$this->student['user_id'], '2026-10-01 00:00:00', '2026-10-01 00:00:00']);

        $events = $this->calendar->range(
            $this->student['user_id'],
            '2026-10-01 00:00:00',
            '2026-11-01 00:00:00',
            null
        );

        $this->assertCount(
            1,
            $events,
            'A deadline landing exactly on the first instant of a month view must not disappear.'
        );
    }

    public function testAGeneratedEventCannotBeEdited(): void
    {
        $this->sync->synchronise($this->studentUser);

        $eventId = (int) $this->scalar("SELECT id FROM CalendarEvent WHERE module = 'Academic' LIMIT 1");

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('generated from another module');

        $this->calendar->update($eventId, $this->student['user_id'], [
            'title' => 'Hijacked',
            'start_datetime' => '2026-09-07 09:00:00',
            'end_datetime' => '2026-09-07 11:00:00',
        ]);
    }

    public function testAGeneratedEventCannotBeDeleted(): void
    {
        $this->sync->synchronise($this->studentUser);

        $eventId = (int) $this->scalar("SELECT id FROM CalendarEvent WHERE module = 'Academic' LIMIT 1");

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('generated from another module');

        $this->calendar->delete($eventId, $this->student['user_id']);
    }

    public function testAPersonalEventCanBeCreatedAndDeleted(): void
    {
        $event = $this->calendar->create($this->student['user_id'], [
            'title' => 'Study group',
            'start_datetime' => '2026-09-10 14:00:00',
            'end_datetime' => '2026-09-10 16:00:00',
        ]);

        $this->calendar->delete((int) $event['id'], $this->student['user_id']);

        $this->assertFalse(
            (bool) $this->scalar('SELECT COUNT(*) FROM CalendarEvent WHERE id = ?', [$event['id']])
        );
    }

    public function testAnEventEndingBeforeItStartsIsRejected(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('end time must be after');

        $this->calendar->create($this->student['user_id'], [
            'title' => 'Backwards',
            'start_datetime' => '2026-09-10 16:00:00',
            'end_datetime' => '2026-09-10 14:00:00',
        ]);
    }

    public function testAReminderMustBeScheduledBeforeItsEvent(): void
    {
        $event = $this->calendar->create($this->student['user_id'], [
            'title' => 'Study group',
            'start_datetime' => '2026-09-10 14:00:00',
            'end_datetime' => '2026-09-10 16:00:00',
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('before the event starts');

        $this->calendar->createReminder($this->student['user_id'], [
            'calendar_event_id' => $event['id'],
            'reminder_time' => '2026-09-10 15:00:00',
        ]);
    }

    public function testCalendarsAreIsolatedBetweenUsers(): void
    {
        $this->sync->synchronise($this->studentUser);

        $otherEvents = $this->calendar->range(
            $this->lecturer['user_id'],
            '2026-01-01 00:00:00',
            '2027-12-31 00:00:00',
            null
        );

        $this->assertSame([], $otherEvents, 'One user must never see another user\'s calendar.');
    }
}
