<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\AttendanceService;
use App\Services\ExcuseService;
use App\Services\QrSessionService;
use Tests\TestCase;

class AttendanceRulesTest extends TestCase
{
    private AttendanceService $attendance;

    private QrSessionService $sessions;

    private ExcuseService $excuses;

    private array $structure;

    private array $student;

    private array $lecturer;

    private int $sectionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attendance = new AttendanceService();
        $this->sessions = new QrSessionService();
        $this->excuses = new ExcuseService();

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
    }

    public function testScanningAValidCodeRecordsAttendance(): void
    {
        $session = $this->openSession();

        $record = $this->attendance->scan(
            $this->student['student_id'],
            $session['qr_token'],
            null,
            null
        );

        $this->assertSame('Present', $record['attendance_status']);
        $this->assertSame('QR', $record['attendance_method']);
    }

    public function testTheSameStudentCannotScanTwiceForOneSession(): void
    {
        $session = $this->openSession();

        $this->attendance->scan($this->student['student_id'], $session['qr_token'], null, null);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('already been recorded');

        $this->attendance->scan($this->student['student_id'], $session['qr_token'], null, null);
    }

    public function testAnUnknownTokenIsRejected(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not valid');

        $this->attendance->scan($this->student['student_id'], 'not-a-real-token', null, null);
    }

    public function testAnExpiredSessionIsRejected(): void
    {
        $session = $this->openSession();

        $this->db->prepare(
            'UPDATE QRSession SET expires_at = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 MINUTE) WHERE id = ?'
        )->execute([$session['id']]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('expired');

        $this->attendance->scan($this->student['student_id'], $session['qr_token'], null, null);
    }

    public function testAClosedSessionIsRejected(): void
    {
        $session = $this->openSession();

        $this->sessions->close(
            (int) $session['id'],
            $this->lecturer['user_id'],
            'Lecturer'
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('closed');

        $this->attendance->scan($this->student['student_id'], $session['qr_token'], null, null);
    }

    public function testOnlyOneSessionCanBeOpenPerSection(): void
    {
        $this->openSession();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('already open');

        $this->openSession();
    }

    public function testAStudentWhoIsNotEnrolledCannotRecordAttendance(): void
    {
        $session = $this->openSession();
        $outsider = $this->createStudent($this->structure, 'outsider@test.edu');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not enrolled');

        $this->attendance->scan($outsider['student_id'], $session['qr_token'], null, null);
    }

    public function testAPendingEnrollmentDoesNotGrantAttendance(): void
    {
        $session = $this->openSession();

        $otherCourse = $this->createCourse($this->structure['department_id'], 'CS210');
        $otherSection = $this->createSection(
            $otherCourse,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id'],
            30,
            '02'
        );

        $pendingStudent = $this->createStudent($this->structure, 'pending@test.edu');
        $this->enrol($pendingStudent['student_id'], $otherSection, 'Pending');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not enrolled');

        $this->attendance->scan($pendingStudent['student_id'], $session['qr_token'], null, null);
    }

    public function testScanningFromInsideTheAllowedRadiusSucceeds(): void
    {
        $session = $this->openSession(['latitude' => 3.0738, 'longitude' => 101.5183, 'allowed_radius' => 150]);

        $record = $this->attendance->scan(
            $this->student['student_id'],
            $session['qr_token'],
            3.07425,
            101.5183
        );

        $this->assertSame('GPS', $record['attendance_method']);
    }

    public function testScanningFromOutsideTheAllowedRadiusIsRejected(): void
    {
        $session = $this->openSession(['latitude' => 3.0738, 'longitude' => 101.5183, 'allowed_radius' => 150]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('too far');

        $this->attendance->scan($this->student['student_id'], $session['qr_token'], 3.0918, 101.5183);
    }

    public function testALocationBoundSessionRequiresCoordinates(): void
    {
        $session = $this->openSession(['latitude' => 3.0738, 'longitude' => 101.5183, 'allowed_radius' => 150]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('requires your location');

        $this->attendance->scan($this->student['student_id'], $session['qr_token'], null, null);
    }

    public function testApprovingAnExcuseMarksTheAttendanceAsExcused(): void
    {
        $session = $this->openSession();
        $record = $this->attendance->scan(
            $this->student['student_id'],
            $session['qr_token'],
            null,
            null
        );

        $excuse = $this->excuses->submit($this->student['student_id'], [
            'attendance_id' => $record['id'],
            'excuse_type' => 'Medical',
            'reason' => 'Hospital appointment',
        ], null);

        $this->excuses->review(
            (int) $excuse['id'],
            $this->lecturer['user_id'],
            'Lecturer',
            'Approved',
            'Certificate seen'
        );

        $this->assertSame(
            'Excused',
            $this->scalar('SELECT attendance_status FROM Attendance WHERE id = ?', [$record['id']])
        );
    }

    public function testAnExcuseCannotBeSubmittedTwiceForOneSession(): void
    {
        $session = $this->openSession();
        $record = $this->attendance->scan(
            $this->student['student_id'],
            $session['qr_token'],
            null,
            null
        );

        $fields = [
            'attendance_id' => $record['id'],
            'excuse_type' => 'Medical',
            'reason' => 'Hospital appointment',
        ];

        $this->excuses->submit($this->student['student_id'], $fields, null);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('already been submitted');

        $this->excuses->submit($this->student['student_id'], $fields, null);
    }

    public function testALecturerCannotReviewAnExcuseForAnotherLecturersSection(): void
    {
        $session = $this->openSession();
        $record = $this->attendance->scan(
            $this->student['student_id'],
            $session['qr_token'],
            null,
            null
        );

        $excuse = $this->excuses->submit($this->student['student_id'], [
            'attendance_id' => $record['id'],
            'excuse_type' => 'Family',
            'reason' => 'Family matter',
        ], null);

        $otherLecturer = $this->createLecturer($this->structure, 'other.lecturer@test.edu');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('your own sections');

        $this->excuses->review(
            (int) $excuse['id'],
            $otherLecturer['user_id'],
            'Lecturer',
            'Approved',
            null
        );
    }

    public function testALecturerCannotRecordAttendanceForAnotherLecturersSection(): void
    {
        $otherLecturer = $this->createLecturer($this->structure, 'other.lecturer@test.edu');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('your own sections');

        $this->attendance->recordManually(
            $otherLecturer['user_id'],
            'Lecturer',
            $this->student['student_id'],
            $this->sectionId,
            '2026-09-07',
            'Present',
            null
        );
    }

    public function testAttendanceRateIsCalculatedFromRecordedSessions(): void
    {
        $this->attendance->recordManually(
            $this->lecturer['user_id'],
            'Lecturer',
            $this->student['student_id'],
            $this->sectionId,
            '2026-09-07',
            'Present',
            null
        );

        $this->attendance->recordManually(
            $this->lecturer['user_id'],
            'Lecturer',
            $this->student['student_id'],
            $this->sectionId,
            '2026-09-14',
            'Absent',
            null
        );

        $statistics = $this->attendance->statisticsForStudent($this->student['student_id']);

        $this->assertCount(1, $statistics);
        $this->assertSame(2, $statistics[0]['total_sessions']);
        $this->assertSame(1, $statistics[0]['attended']);
        $this->assertSame(50.0, $statistics[0]['attendance_rate']);
    }

    private function openSession(array $options = []): array
    {
        return $this->sessions->open($this->sectionId, $this->lecturer['user_id'], $options);
    }
}
