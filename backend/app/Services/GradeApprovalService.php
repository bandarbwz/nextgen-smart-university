<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Models\Enrollment;
use App\Models\GradeApproval;
use App\Models\GradeApprovalLog;
use App\Models\Section;
use App\Models\Student;
use App\Models\Transcript;
use Throwable;

/**
 * The gate between a lecturer finishing marking and grades becoming part of a
 * student's permanent record. Approval is the only thing in the platform that
 * writes a Transcript row, which is what a GPA is calculated from.
 */
class GradeApprovalService
{
    public function __construct(
        private readonly GradeApproval $approvals = new GradeApproval(),
        private readonly GradeApprovalLog $logs = new GradeApprovalLog(),
        private readonly Section $sections = new Section(),
        private readonly Enrollment $enrollments = new Enrollment(),
        private readonly Student $students = new Student(),
        private readonly Transcript $transcripts = new Transcript(),
        private readonly AssessmentService $assessments = new AssessmentService(),
        private readonly AssessmentResultService $results = new AssessmentResultService(),
        private readonly CourseAccessService $access = new CourseAccessService(),
        private readonly GpaService $gpa = new GpaService(),
        private readonly NotificationService $notifications = new NotificationService()
    ) {
    }

    public function list(array $user, ?string $status): array
    {
        $filters = $status === null ? [] : ['status' => $status];

        if ($user['role'] === 'Lecturer') {
            $filters['lecturer_id'] = $this->access->requireLecturerId($user['user_id']);
        }

        return $this->approvals->listing($filters);
    }

    public function get(int $id, array $user): array
    {
        $approval = $this->requireApproval($id);

        $this->guardVisible($approval, $user);

        $approval['log'] = $this->logs->forApproval($id);
        $approval['grades'] = $this->draftGrades((int) $approval['section_id']);

        return $approval;
    }

    /**
     * Grades have to be finished before they can be submitted, which means the
     * scheme adds up to a hundred and every enrolled student has a complete set
     * of published component results. Submitting half marked grades would put
     * the coordinator in the position of approving something incomplete.
     */
    public function submit(int $sectionId, array $user): array
    {
        $lecturerId = $this->access->guardSectionOwned($sectionId, $user);

        if ($this->approvals->approvedForSection($sectionId) !== null) {
            throw new ApiException('The grades for this section are already approved.', 409);
        }

        $existing = $this->approvals->openForSection($sectionId);

        if ($existing !== null && $existing['approval_status'] === 'Pending') {
            throw new ApiException('These grades are already awaiting approval.', 409);
        }

        $grades = $this->guardFinalised($sectionId);

        if ($existing !== null) {
            $id = (int) $existing['id'];

            $this->approvals->update($id, [
                'approval_status' => 'Pending',
                'submitted_at' => gmdate('Y-m-d H:i:s'),
                'reviewed_at' => null,
                'remarks' => null,
                'student_count' => count($grades),
            ]);

            $this->logs->record($id, 'Resubmitted', $user['user_id'], null);
        } else {
            $id = $this->approvals->create([
                'section_id' => $sectionId,
                'lecturer_id' => $lecturerId,
                'submitted_at' => gmdate('Y-m-d H:i:s'),
                'approval_status' => 'Pending',
                'student_count' => count($grades),
            ]);

            $this->logs->record($id, 'Submitted', $user['user_id'], null);
        }

        $this->notifyCoordinators($sectionId, count($grades));

        return $this->get($id, $user);
    }

    public function approve(int $id, array $user, ?string $remarks): array
    {
        $approval = $this->requirePending($id);

        $grades = $this->guardFinalised((int) $approval['section_id']);

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $this->approvals->decide($id, 'Approved', $user['user_id'], $remarks);
            $this->logs->record($id, 'Approved', $user['user_id'], $remarks);

            $this->writeTranscripts($approval, $grades);

            $this->approvals->update($id, ['published_at' => gmdate('Y-m-d H:i:s')]);
            $this->logs->record($id, 'Published', $user['user_id'], null);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        foreach ($grades as $grade) {
            $this->gpa->recalculate((int) $grade['student_id']);

            $this->notifyStudent(
                (int) $grade['student_id'],
                'Your grade has been published',
                'Your final grade for ' . $approval['course_code'] . ' is '
                    . $grade['grade_letter'] . '.'
            );
        }

        $this->notifyLecturer($approval, 'Grades approved', 'Your grades for '
            . $approval['course_code'] . ' have been approved and published.');

        return $this->get($id, $user);
    }

    public function reject(int $id, array $user, string $remarks): array
    {
        $approval = $this->requirePending($id);

        $this->approvals->decide($id, 'Rejected', $user['user_id'], $remarks);
        $this->logs->record($id, 'Rejected', $user['user_id'], $remarks);

        $this->notifyLecturer($approval, 'Grades rejected', 'Your grades for '
            . $approval['course_code'] . ' were rejected. ' . $remarks);

        return $this->get($id, $user);
    }

    public function returnForRevision(int $id, array $user, string $remarks): array
    {
        $approval = $this->requirePending($id);

        $this->approvals->decide($id, 'Returned for Revision', $user['user_id'], $remarks);
        $this->logs->record($id, 'Returned for Revision', $user['user_id'], $remarks);

        $this->notifyLecturer($approval, 'Revision required', 'Your grades for '
            . $approval['course_code'] . ' need revision. ' . $remarks);

        return $this->get($id, $user);
    }

    public function history(array $user, ?int $departmentId): array
    {
        return $this->logs->history($departmentId);
    }

    /**
     * The grades as they stand, computed from the assessment scheme. This is
     * what the coordinator reviews and what approval turns into transcript
     * rows, so both read the same calculation.
     */
    private function draftGrades(int $sectionId): array
    {
        $section = $this->sections->find($sectionId);

        if ($section === null) {
            return [];
        }

        $administrator = ['user_id' => 0, 'role' => 'Administrator', 'permissions' => []];
        $grades = [];

        foreach ($this->enrollments->approvedStudentIds($sectionId) as $studentId) {
            $result = $this->results->courseResult((int) $studentId, $sectionId, $administrator);
            $student = $this->students->find((int) $studentId);

            $grades[] = [
                'student_id' => (int) $studentId,
                'student_number' => $student['student_number'] ?? null,
                'weighted_percentage' => $result['weighted_percentage'],
                'grade_letter' => $result['grade_letter'],
                'grade_points' => $result['grade_points'],
                'weight_counted' => $result['weight_counted'],
                'is_complete' => $result['is_complete'],
            ];
        }

        return $grades;
    }

    private function guardFinalised(int $sectionId): array
    {
        $administrator = ['user_id' => 0, 'role' => 'Administrator', 'permissions' => []];
        $weights = $this->assessments->weightSummary($sectionId, $administrator);

        if (!$weights['is_complete']) {
            throw new ApiException(
                'The assessment scheme uses ' . $weights['weight_used']
                    . ' per cent. It must reach 100 before grades can be submitted.',
                409
            );
        }

        $grades = $this->draftGrades($sectionId);

        if ($grades === []) {
            throw new ApiException('There are no enrolled students to grade.', 409);
        }

        foreach ($grades as $grade) {
            if (!$grade['is_complete']) {
                throw new ApiException(
                    'Student ' . $grade['student_number'] . ' has only '
                        . $grade['weight_counted'] . ' per cent of components published.',
                    409
                );
            }
        }

        return $grades;
    }

    private function writeTranscripts(array $approval, array $grades): void
    {
        foreach ($grades as $grade) {
            $this->transcripts->record(
                (int) $grade['student_id'],
                (int) $approval['course_id'],
                (int) $approval['semester_id'],
                $grade['grade_letter'],
                (float) $grade['grade_points'],
                $grade['grade_letter'] === 'F' ? 0 : (int) $approval['credit_hours']
            );
        }
    }

    private function requireApproval(int $id): array
    {
        $approval = $this->approvals->findDetailed($id);

        if ($approval === null) {
            throw new ApiException('Grade approval not found.', 404);
        }

        return $approval;
    }

    private function requirePending(int $id): array
    {
        $approval = $this->requireApproval($id);

        if ($approval['approval_status'] !== 'Pending') {
            throw new ApiException(
                'This request is ' . strtolower($approval['approval_status']) . ' and cannot be decided again.',
                409
            );
        }

        return $approval;
    }

    private function guardVisible(array $approval, array $user): void
    {
        if ($user['role'] !== 'Lecturer') {
            return;
        }

        if ((int) $approval['lecturer_id'] !== $this->access->requireLecturerId($user['user_id'])) {
            throw new ApiException('Grade approval not found.', 404);
        }
    }

    private function notifyCoordinators(int $sectionId, int $studentCount): void
    {
        $this->notifications->notifyRole(
            'Coordinator',
            'Grade Approval',
            'Grades submitted for approval',
            $studentCount . ' grade(s) are waiting for your review.',
            ['priority' => 'High']
        );
    }

    private function notifyLecturer(array $approval, string $title, string $message): void
    {
        $lecturerUserId = $this->lecturerUserId((int) $approval['lecturer_id']);

        if ($lecturerUserId === null) {
            return;
        }

        $this->notifications->notify(
            $lecturerUserId,
            'Grade Approval',
            $title,
            $message,
            ['priority' => 'High']
        );
    }

    private function notifyStudent(int $studentId, string $title, string $message): void
    {
        $student = $this->students->find($studentId);

        if ($student === null) {
            return;
        }

        $this->notifications->notify(
            (int) $student['user_id'],
            'Grade Approval',
            $title,
            $message,
            ['type' => 'success', 'priority' => 'High']
        );
    }

    private function lecturerUserId(int $lecturerId): ?int
    {
        $statement = Database::connection()->prepare(
            'SELECT user_id FROM Lecturer WHERE id = :id LIMIT 1'
        );

        $statement->execute(['id' => $lecturerId]);

        $userId = $statement->fetchColumn();

        return $userId === false ? null : (int) $userId;
    }
}
