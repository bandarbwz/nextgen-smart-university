<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Models\ExamResetLog;
use App\Models\ExamResetRequest;
use App\Models\ExamSession;
use App\Models\ExamSubmission;
use App\Models\Student;
use Throwable;

/**
 * The route back into an examination for a student who was cut off, whether by
 * a technical failure or by the proctor terminating their session.
 *
 * A reset never deletes anything. The original submission is marked as reset
 * and the retake is a new attempt beside it, so the first sitting and the
 * reason it was abandoned both stay on record.
 */
class ExamResetService
{
    public function __construct(
        private readonly ExamResetRequest $requests = new ExamResetRequest(),
        private readonly ExamResetLog $logs = new ExamResetLog(),
        private readonly ExamSubmission $submissions = new ExamSubmission(),
        private readonly ExamSession $sessions = new ExamSession(),
        private readonly Student $students = new Student(),
        private readonly ExamService $exams = new ExamService(),
        private readonly CourseAccessService $access = new CourseAccessService(),
        private readonly NotificationService $notifications = new NotificationService()
    ) {
    }

    public function list(array $user, ?string $status): array
    {
        $filters = $status === null ? [] : ['status' => $status];

        if ($user['role'] === 'Student') {
            $filters['student_id'] = $this->access->requireStudentId($user['user_id']);
        }

        if ($user['role'] === 'Lecturer') {
            $filters['lecturer_id'] = $this->access->requireLecturerId($user['user_id']);
        }

        return $this->requests->listing($filters);
    }

    public function get(int $id, array $user): array
    {
        $request = $this->requireRequest($id);

        $this->guardVisible($request, $user);

        $request['log'] = $this->logs->forRequest($id);

        return $request;
    }

    /**
     * A student can only ask for a reset of an examination they actually sat
     * and did not finish cleanly. Asking to redo a completed paper is not a
     * reset, it is a second attempt, and that is a different decision.
     */
    public function request(array $user, int $examId, string $reason): array
    {
        $exam = $this->exams->requireExam($examId);
        $studentId = $this->access->requireStudentId($user['user_id']);

        $this->access->guardSectionVisible((int) $exam['section_id'], $user);

        if ($this->requests->openForStudentAndExam($examId, $studentId) !== null) {
            throw new ApiException('You already have a reset request for this examination.', 409);
        }

        $session = $this->latestSession($examId, $studentId);

        if ($session === null) {
            throw new ApiException('You have not sat this examination.', 409);
        }

        if ($session['status'] === 'active' || $session['status'] === 'paused') {
            throw new ApiException(
                'This examination is still in progress. Finish or close it before asking for a reset.',
                409
            );
        }

        $id = $this->requests->create([
            'exam_id' => $examId,
            'student_id' => $studentId,
            'session_id' => (int) $session['id'],
            'requested_by' => $user['user_id'],
            'request_reason' => $reason,
            'request_date' => gmdate('Y-m-d H:i:s'),
            'approval_status' => 'Pending',
        ]);

        $this->logs->record($id, 'Requested', $user['user_id'], null);

        $this->notifyLecturer($exam, 'New examination reset request',
            'A student has asked for a reset of ' . $exam['title'] . '.');

        return $this->get($id, $user);
    }

    public function recommend(int $id, array $user, ?string $remarks): array
    {
        $request = $this->requireStatus($id, ['Pending']);

        $this->access->guardSectionOwned((int) $request['section_id'], $user);

        $this->requests->decide($id, 'Recommended', [
            'reviewed_by' => $user['user_id'],
            'remarks' => $remarks,
        ]);

        $this->logs->record($id, 'Recommended', $user['user_id'], $remarks);

        $this->notifications->notifyRole(
            'Coordinator',
            'Reset Examination',
            'Reset request recommended',
            'A lecturer has recommended a reset for ' . $request['exam_title'] . '.',
            ['priority' => 'High']
        );

        return $this->get($id, $user);
    }

    /**
     * Approval and the reset itself happen together. A request left approved
     * but not carried out would leave the student unable to sit the paper and
     * nobody able to see why.
     */
    public function approve(int $id, array $user, ?string $remarks): array
    {
        $request = $this->requireStatus($id, ['Pending', 'Recommended']);

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $this->requests->decide($id, 'Completed', [
                'approved_by' => $user['user_id'],
                'approved_at' => gmdate('Y-m-d H:i:s'),
                'completed_at' => gmdate('Y-m-d H:i:s'),
                'remarks' => $remarks,
            ]);

            $this->logs->record($id, 'Approved', $user['user_id'], $remarks);

            $this->submissions->markReset(
                (int) $request['exam_id'],
                (int) $request['student_id']
            );

            if ($request['session_id'] !== null) {
                $this->sessions->update((int) $request['session_id'], [
                    'status' => 'terminated',
                    'termination_reason' => 'Reset approved. The student may sit the paper again.',
                ]);
            }

            $this->logs->record($id, 'Reset Completed', $user['user_id'], null);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        $this->notifyStudent(
            (int) $request['student_id'],
            'Your examination has been reset',
            'You may now sit ' . $request['exam_title'] . ' again.'
        );

        return $this->get($id, $user);
    }

    public function reject(int $id, array $user, string $remarks): array
    {
        $request = $this->requireStatus($id, ['Pending', 'Recommended']);

        $this->requests->decide($id, 'Rejected', [
            'approved_by' => $user['user_id'],
            'approved_at' => gmdate('Y-m-d H:i:s'),
            'remarks' => $remarks,
        ]);

        $this->logs->record($id, 'Rejected', $user['user_id'], $remarks);

        $this->notifyStudent(
            (int) $request['student_id'],
            'Your reset request was not approved',
            $remarks
        );

        return $this->get($id, $user);
    }

    public function history(): array
    {
        return $this->logs->history();
    }

    private function latestSession(int $examId, int $studentId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM ExamSession
             WHERE exam_id = :exam_id AND student_id = :student_id
             ORDER BY id DESC
             LIMIT 1'
        );

        $statement->execute([
            'exam_id' => $examId,
            'student_id' => $studentId,
        ]);

        return $statement->fetch() ?: null;
    }

    private function requireRequest(int $id): array
    {
        $request = $this->requests->findDetailed($id);

        if ($request === null) {
            throw new ApiException('Reset request not found.', 404);
        }

        return $request;
    }

    private function requireStatus(int $id, array $allowed): array
    {
        $request = $this->requireRequest($id);

        if (!in_array($request['approval_status'], $allowed, true)) {
            throw new ApiException(
                'This request is ' . strtolower($request['approval_status'])
                    . ' and cannot be changed.',
                409
            );
        }

        return $request;
    }

    private function guardVisible(array $request, array $user): void
    {
        if ($user['role'] === 'Student'
            && (int) $request['student_id'] !== $this->access->requireStudentId($user['user_id'])) {
            throw new ApiException('Reset request not found.', 404);
        }

        if ($user['role'] === 'Lecturer'
            && (int) $request['lecturer_id'] !== $this->access->requireLecturerId($user['user_id'])) {
            throw new ApiException('Reset request not found.', 404);
        }
    }

    private function notifyStudent(int $studentId, string $title, string $message): void
    {
        $student = $this->students->find($studentId);

        if ($student === null) {
            return;
        }

        $this->notifications->notify(
            (int) $student['user_id'],
            'Reset Examination',
            $title,
            $message,
            ['priority' => 'High']
        );
    }

    private function notifyLecturer(array $exam, string $title, string $message): void
    {
        $statement = Database::connection()->prepare(
            'SELECT l.user_id
             FROM Section s
             JOIN Lecturer l ON l.id = s.lecturer_id
             WHERE s.id = :section_id
             LIMIT 1'
        );

        $statement->execute(['section_id' => (int) $exam['section_id']]);

        $userId = $statement->fetchColumn();

        if ($userId !== false) {
            $this->notifications->notify(
                (int) $userId,
                'Reset Examination',
                $title,
                $message,
                ['priority' => 'Normal']
            );
        }
    }
}
