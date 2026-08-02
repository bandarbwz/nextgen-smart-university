<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use Throwable;

class EnrollmentService
{
    private const MAX_CREDIT_HOURS = 21;

    public function __construct(
        private readonly Enrollment $enrollments = new Enrollment(),
        private readonly Section $sections = new Section(),
        private readonly Course $courses = new Course(),
        private readonly Student $students = new Student(),
        private readonly Semester $semesters = new Semester(),
        private readonly ClassSchedule $schedules = new ClassSchedule(),
        private readonly CourseChatProvisioner $chat = new CourseChatProvisioner()
    ) {
    }

    public function register(int $studentId, int $sectionId): array
    {
        $this->requireStudent($studentId);

        $section = $this->sections->findDetailed($sectionId);

        if ($section === null) {
            throw new ApiException('Section not found.', 404);
        }

        $semesterId = (int) $section['semester_id'];

        $this->guardRegistrationPeriod($semesterId);
        $this->guardSectionIsOpen($section);
        $this->guardNotAlreadyRegistered($studentId, (int) $section['course_id']);
        $this->guardPrerequisites($studentId, (int) $section['course_id']);
        $this->guardCreditLimit($studentId, $semesterId, (int) $section['credit_hours']);
        $this->guardNoScheduleClash($studentId, $semesterId, $sectionId);

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            if (!$this->sections->hasAvailableSeats($sectionId)) {
                throw new ApiException('This section is already full.', 409);
            }

            $enrollmentId = $this->enrollments->create([
                'student_id' => $studentId,
                'section_id' => $sectionId,
                'registration_date' => gmdate('Y-m-d H:i:s'),
                'enrollment_status' => 'Pending',
            ]);

            $this->sections->incrementRegistered($sectionId);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        return $this->enrollments->findDetailed($enrollmentId);
    }

    public function drop(int $studentId, int $enrollmentId): void
    {
        $enrollment = $this->enrollments->find($enrollmentId);

        if ($enrollment === null || (int) $enrollment['student_id'] !== $studentId) {
            throw new ApiException('Enrollment not found.', 404);
        }

        if (!in_array($enrollment['enrollment_status'], ['Pending', 'Approved'], true)) {
            throw new ApiException('This enrollment can no longer be dropped.', 409);
        }

        $sectionId = (int) $enrollment['section_id'];
        $section = $this->sections->findDetailed($sectionId);

        if ($section !== null) {
            $this->guardRegistrationPeriod((int) $section['semester_id']);
        }

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $this->enrollments->updateStatus($enrollmentId, 'Dropped');
            $this->sections->decrementRegistered($sectionId);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        $this->chat->removeStudent($sectionId, $studentId);
    }

    public function currentForStudent(int $studentId): array
    {
        $this->requireStudent($studentId);

        $semester = $this->semesters->current();

        if ($semester === null) {
            throw new ApiException('No current semester has been set.', 404);
        }

        return $this->enrollments->forStudentInSemester($studentId, (int) $semester['id']);
    }

    public function historyForStudent(int $studentId): array
    {
        $this->requireStudent($studentId);

        return $this->enrollments->historyForStudent($studentId);
    }

    public function pendingForDepartment(int $departmentId): array
    {
        return $this->enrollments->pendingForDepartment($departmentId);
    }

    public function approve(int $enrollmentId, int $approvedByUserId): array
    {
        $enrollment = $this->requirePendingEnrollment($enrollmentId);

        $this->enrollments->recordDecision($enrollmentId, 'Approved', $approvedByUserId);

        $this->chat->addStudent((int) $enrollment['section_id'], (int) $enrollment['student_id']);

        return $this->enrollments->findDetailed((int) $enrollment['id']);
    }

    public function reject(int $enrollmentId, int $approvedByUserId): array
    {
        $enrollment = $this->requirePendingEnrollment($enrollmentId);

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $this->enrollments->recordDecision($enrollmentId, 'Rejected', $approvedByUserId);
            $this->sections->decrementRegistered((int) $enrollment['section_id']);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        return $this->enrollments->findDetailed($enrollmentId);
    }

    private function requirePendingEnrollment(int $enrollmentId): array
    {
        $enrollment = $this->enrollments->find($enrollmentId);

        if ($enrollment === null) {
            throw new ApiException('Enrollment not found.', 404);
        }

        if ($enrollment['enrollment_status'] !== 'Pending') {
            throw new ApiException('This enrollment has already been reviewed.', 409);
        }

        return $enrollment;
    }

    private function requireStudent(int $studentId): array
    {
        $student = $this->students->find($studentId);

        if ($student === null) {
            throw new ApiException('Student not found.', 404);
        }

        if ($student['academic_status'] !== 'active') {
            throw new ApiException('This student account is not active for registration.', 403);
        }

        return $student;
    }

    private function guardRegistrationPeriod(int $semesterId): void
    {
        if (!$this->semesters->registrationIsOpen($semesterId)) {
            throw new ApiException('The registration period is closed.', 403);
        }
    }

    private function guardSectionIsOpen(array $section): void
    {
        if ($section['status'] !== 'open') {
            throw new ApiException('This section is not open for registration.', 409);
        }
    }

    private function guardNotAlreadyRegistered(int $studentId, int $courseId): void
    {
        if ($this->enrollments->activeForStudentAndCourse($studentId, $courseId) !== null) {
            throw new ApiException('You are already registered for this course.', 409);
        }
    }

    private function guardPrerequisites(int $studentId, int $courseId): void
    {
        $required = $this->courses->prerequisiteIds($courseId);

        if ($required === []) {
            return;
        }

        $completed = $this->enrollments->completedCourseIds($studentId);
        $missing = array_diff($required, $completed);

        if ($missing !== []) {
            throw new ApiException('You have not completed the prerequisites for this course.', 409);
        }
    }

    private function guardCreditLimit(int $studentId, int $semesterId, int $creditHours): void
    {
        $registered = $this->enrollments->registeredCreditHours($studentId, $semesterId);

        if ($registered + $creditHours > self::MAX_CREDIT_HOURS) {
            throw new ApiException(
                'This registration exceeds the maximum of ' . self::MAX_CREDIT_HOURS . ' credit hours per semester.',
                409
            );
        }
    }

    private function guardNoScheduleClash(int $studentId, int $semesterId, int $sectionId): void
    {
        $conflicts = $this->schedules->conflictsForStudent($studentId, $semesterId, $sectionId);

        if ($conflicts !== []) {
            throw new ApiException(
                'This section clashes with your existing schedule for ' . $conflicts[0]['course_code'] . '.',
                409
            );
        }
    }
}
