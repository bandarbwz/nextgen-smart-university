<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Config;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Lecturer;
use App\Models\QRSession;
use App\Models\Section;
use App\Models\Student;

class AttendanceService
{
    public function __construct(
        private readonly Attendance $attendance = new Attendance(),
        private readonly Enrollment $enrollments = new Enrollment(),
        private readonly Section $sections = new Section(),
        private readonly Student $students = new Student(),
        private readonly Lecturer $lecturers = new Lecturer(),
        private readonly QRSession $sessions = new QRSession(),
        private readonly QrSessionService $qrSessions = new QrSessionService(),
        private readonly GeoService $geo = new GeoService()
    ) {
    }

    public function scan(int $studentId, string $token, ?float $latitude, ?float $longitude): array
    {
        $session = $this->qrSessions->requireActiveByToken($token);
        $sectionId = (int) $session['section_id'];

        $this->guardEnrolled($studentId, $sectionId);
        $this->guardLocation($session, $latitude, $longitude);

        $date = $session['session_date'];

        if ($this->attendance->existsForDate($studentId, $sectionId, $date)) {
            throw new ApiException('Your attendance for this session has already been recorded.', 409);
        }

        $id = $this->attendance->create([
            'student_id' => $studentId,
            'section_id' => $sectionId,
            'qr_session_id' => (int) $session['id'],
            'attendance_date' => $date,
            'attendance_time' => gmdate('H:i:s'),
            'attendance_status' => $this->statusForScanTime($session),
            'attendance_method' => $latitude === null ? 'QR' : 'GPS',
        ]);

        return $this->attendance->find($id);
    }

    public function recordManually(
        int $lecturerUserId,
        string $role,
        int $studentId,
        int $sectionId,
        string $date,
        string $status,
        ?string $remarks
    ): array {
        $this->guardSectionOwnership($sectionId, $lecturerUserId, $role);
        $this->guardEnrolled($studentId, $sectionId);

        $existing = $this->attendance->findForStudentAndDate($studentId, $sectionId, $date);

        if ($existing !== null) {
            $this->attendance->updateStatus((int) $existing['id'], $status, $lecturerUserId, $remarks);

            return $this->attendance->find((int) $existing['id']);
        }

        $id = $this->attendance->create([
            'student_id' => $studentId,
            'section_id' => $sectionId,
            'attendance_date' => $date,
            'attendance_time' => gmdate('H:i:s'),
            'attendance_status' => $status,
            'attendance_method' => 'Manual',
            'verified_by' => $lecturerUserId,
            'remarks' => $remarks,
        ]);

        return $this->attendance->find($id);
    }

    public function update(
        int $id,
        int $lecturerUserId,
        string $role,
        string $status,
        ?string $remarks
    ): array {
        $record = $this->requireRecord($id);

        $this->guardSectionOwnership((int) $record['section_id'], $lecturerUserId, $role);
        $this->attendance->updateStatus($id, $status, $lecturerUserId, $remarks);

        return $this->attendance->find($id);
    }

    public function delete(int $id, int $lecturerUserId, string $role): void
    {
        $record = $this->requireRecord($id);

        $this->guardSectionOwnership((int) $record['section_id'], $lecturerUserId, $role);
        $this->attendance->delete($id);
    }

    public function forStudent(int $studentId, ?int $sectionId = null): array
    {
        return $this->attendance->forStudent($studentId, $sectionId);
    }

    public function forSection(int $sectionId, ?string $date, int $userId, string $role): array
    {
        $this->guardSectionOwnership($sectionId, $userId, $role, ['Coordinator']);

        return $this->attendance->forSection($sectionId, $date);
    }

    public function forLecturer(int $lecturerId, ?string $date): array
    {
        if (!$this->lecturers->exists($lecturerId)) {
            throw new ApiException('Lecturer not found.', 404);
        }

        return $this->attendance->forLecturer($lecturerId, $date);
    }

    public function statisticsForStudent(int $studentId): array
    {
        $rows = $this->attendance->statisticsForStudent($studentId);

        return array_map(static function (array $row): array {
            $total = (int) $row['total_sessions'];
            $attended = (int) $row['attended'];

            return [
                'course_code' => $row['course_code'],
                'course_name' => $row['course_name'],
                'section_id' => (int) $row['section_id'],
                'total_sessions' => $total,
                'attended' => $attended,
                'excused' => (int) $row['excused'],
                'absent' => (int) $row['absent'],
                'attendance_rate' => $total === 0 ? 0.0 : round($attended / $total * 100, 1),
            ];
        }, $rows);
    }

    public function dailyReport(string $date): array
    {
        return $this->attendance->dailySummary($date);
    }

    public function monthlyReport(int $year, int $month): array
    {
        return $this->attendance->monthlySummary($year, $month);
    }

    public function requireRecord(int $id): array
    {
        $record = $this->attendance->find($id);

        if ($record === null) {
            throw new ApiException('Attendance record not found.', 404);
        }

        return $record;
    }

    private function guardEnrolled(int $studentId, int $sectionId): void
    {
        $section = $this->sections->find($sectionId);

        if ($section === null) {
            throw new ApiException('Section not found.', 404);
        }

        $enrollment = $this->enrollments->activeForStudentAndCourse(
            $studentId,
            (int) $section['course_id']
        );

        if ($enrollment === null || $enrollment['enrollment_status'] === 'Pending') {
            throw new ApiException('You are not enrolled in this section.', 403);
        }
    }

    private function guardLocation(array $session, ?float $latitude, ?float $longitude): void
    {
        if ($session['latitude'] === null || $session['longitude'] === null) {
            return;
        }

        if ($latitude === null || $longitude === null) {
            throw new ApiException('This session requires your location to record attendance.', 422);
        }

        $withinRange = $this->geo->isWithinRadius(
            $latitude,
            $longitude,
            (float) $session['latitude'],
            (float) $session['longitude'],
            (int) $session['allowed_radius']
        );

        if (!$withinRange) {
            throw new ApiException('You are too far from the classroom to record attendance.', 403);
        }
    }

    private function guardSectionOwnership(
        int $sectionId,
        int $userId,
        string $role,
        array $alsoAllowedRoles = []
    ): void {
        if ($role === 'Administrator' || in_array($role, $alsoAllowedRoles, true)) {
            return;
        }

        $section = $this->sections->find($sectionId);

        if ($section === null) {
            throw new ApiException('Section not found.', 404);
        }

        $lecturer = $this->lecturers->findByUserId($userId);

        if ($lecturer === null || (int) $section['lecturer_id'] !== (int) $lecturer['id']) {
            throw new ApiException('You can only manage attendance for your own sections.', 403);
        }
    }

    private function statusForScanTime(array $session): string
    {
        $lateAfter = Config::get('attendance.late_after_minutes') * 60;

        return time() - strtotime($session['generated_at'] . ' UTC') > $lateAfter ? 'Late' : 'Present';
    }
}
