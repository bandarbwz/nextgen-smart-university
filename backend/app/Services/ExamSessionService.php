<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamSubmission;
use App\Models\Student;
use Throwable;

class ExamSessionService
{
    private const AUTO_GRADED_TYPES = ['Multiple Choice', 'True / False'];

    public function __construct(
        private readonly Exam $exams = new Exam(),
        private readonly ExamQuestion $questions = new ExamQuestion(),
        private readonly ExamSession $sessions = new ExamSession(),
        private readonly ExamSubmission $submissions = new ExamSubmission(),
        private readonly ExamService $examService = new ExamService(),
        private readonly CourseAccessService $access = new CourseAccessService(),
        private readonly FaceVerificationService $faces = new FaceVerificationService(),
        private readonly AiProctorService $proctor = new AiProctorService(),
        private readonly NotificationService $notifications = new NotificationService(),
        private readonly Student $students = new Student()
    ) {
    }

    public function start(array $user, int $examId, array $context): array
    {
        $exam = $this->examService->requireExam($examId);
        $studentId = $this->access->requireStudentId($user['user_id']);

        $this->access->guardSectionVisible((int) $exam['section_id'], $user);
        $this->guardOpen($exam);

        if ($this->submissions->findForStudent($examId, $studentId) !== null) {
            throw new ApiException('You have already submitted this examination.', 409);
        }

        $existing = $this->sessions->openForStudent($examId, $studentId);

        if ($existing !== null) {
            return $this->resumeExisting($existing, $exam);
        }

        $verification = $this->verifyIdentity($user, $exam, $context['image'] ?? null);

        $start = time();
        $expires = min($start + ((int) $exam['duration'] * 60), strtotime($exam['end_time'] . ' UTC'));

        $id = $this->sessions->create([
            'exam_id' => $examId,
            'student_id' => $studentId,
            'session_start' => gmdate('Y-m-d H:i:s', $start),
            'expires_at' => gmdate('Y-m-d H:i:s', $expires),
            'ip_address' => $this->clip($context['ip_address'] ?? null, 45),
            'browser' => $this->clip($context['browser'] ?? null, 100),
            'device' => $this->clip($context['device'] ?? null, 100),
            'identity_verified' => $verification['verified'],
            'verification_note' => $verification['note'],
            'status' => 'active',
        ]);

        return $this->present($this->sessions->find($id), $exam);
    }

    public function end(array $user, int $sessionId, array $answers): array
    {
        $session = $this->requireOwnSession($sessionId, $user);
        $exam = $this->examService->requireExam((int) $session['exam_id']);

        if ($session['status'] !== 'active' && $session['status'] !== 'paused') {
            throw new ApiException('This examination session is already closed.', 409);
        }

        $expired = time() > strtotime($session['expires_at'] . ' UTC');

        return $expired
            ? $this->finish($session, $exam, $answers, 'Auto Submitted', 'expired', 'The allowed time ran out.')
            : $this->finish($session, $exam, $answers, 'Submitted');
    }

    public function pause(array $user, int $sessionId): array
    {
        $session = $this->requireSupervisedSession($sessionId, $user);

        if ($session['status'] !== 'active') {
            throw new ApiException('Only an active session can be paused.', 409);
        }

        $this->sessions->update($sessionId, [
            'status' => 'paused',
            'paused_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return $this->sessions->findDetailed($sessionId);
    }

    public function resume(array $user, int $sessionId): array
    {
        $session = $this->requireSupervisedSession($sessionId, $user);

        if ($session['status'] !== 'paused') {
            throw new ApiException('Only a paused session can be resumed.', 409);
        }

        $paused = time() - strtotime($session['paused_at'] . ' UTC');

        $this->sessions->update($sessionId, [
            'status' => 'active',
            'paused_at' => null,
            'expires_at' => gmdate('Y-m-d H:i:s', strtotime($session['expires_at'] . ' UTC') + max(0, $paused)),
        ]);

        return $this->sessions->findDetailed($sessionId);
    }

    public function terminate(int $sessionId, string $reason): void
    {
        $session = $this->sessions->find($sessionId);

        if ($session === null || $session['status'] === 'submitted' || $session['status'] === 'terminated') {
            return;
        }

        $exam = $this->exams->find((int) $session['exam_id']) ?? [];

        $this->finish($session, $exam, [], 'Auto Submitted', 'terminated', $reason);

        $student = $this->students->find((int) $session['student_id']);

        if ($student !== null) {
            $this->notifications->notify(
                (int) $student['user_id'],
                'AI Examination',
                'Examination terminated',
                'Your examination was ended early and submitted automatically. ' . $reason,
                ['type' => 'error', 'priority' => 'Critical']
            );
        }
    }

    public function mine(array $user): array
    {
        return $this->sessions->forStudent($this->access->requireStudentId($user['user_id']));
    }

    public function forExam(int $examId, array $user): array
    {
        $exam = $this->examService->requireExam($examId);

        $this->access->guardSectionOwned((int) $exam['section_id'], $user);

        return $this->sessions->forExam($examId);
    }

    public function requireOwnSession(int $sessionId, array $user): array
    {
        $session = $this->sessions->find($sessionId);

        if ($session === null) {
            throw new ApiException('Examination session not found.', 404);
        }

        $studentId = $this->access->requireStudentId($user['user_id']);

        if ((int) $session['student_id'] !== $studentId) {
            throw new ApiException('Examination session not found.', 404);
        }

        return $session;
    }

    public function requireSupervisedSession(int $sessionId, array $user): array
    {
        $session = $this->sessions->findDetailed($sessionId);

        if ($session === null) {
            throw new ApiException('Examination session not found.', 404);
        }

        $this->access->guardSectionOwned((int) $session['section_id'], $user);

        return $session;
    }

    private function finish(
        array $session,
        array $exam,
        array $answers,
        string $status,
        string $sessionStatus = 'submitted',
        ?string $reason = null
    ): array {
        $questions = $this->questions->forExam((int) $session['exam_id']);

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $scored = $this->score($questions, $answers);

            $submissionId = $this->submissions->create([
                'exam_id' => (int) $session['exam_id'],
                'session_id' => (int) $session['id'],
                'student_id' => (int) $session['student_id'],
                'attempt_number' => $this->submissions->nextAttemptNumber(
                    (int) $session['exam_id'],
                    (int) $session['student_id']
                ),
                'answers' => json_encode($scored['answers']),
                'auto_scored_marks' => $scored['marks'],
                'score' => $scored['manual'] ? null : $scored['marks'],
                'submission_status' => $scored['manual'] ? 'Pending Review' : $status,
                'submitted_at' => gmdate('Y-m-d H:i:s'),
            ]);

            $this->sessions->close((int) $session['id'], $sessionStatus, $reason);

            $this->sessions->refreshViolationCount((int) $session['id']);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        $submission = $this->submissions->find($submissionId);
        $submission['total_marks'] = $exam['total_marks'] ?? null;

        return $submission;
    }

    /**
     * Objective questions are marked here. Short answer and essay questions are
     * left unscored so a human can grade them.
     */
    private function score(array $questions, array $answers): array
    {
        $recorded = [];
        $marks = 0.0;
        $manual = false;

        foreach ($questions as $question) {
            $questionId = (int) $question['id'];
            $given = $answers[$questionId] ?? $answers[(string) $questionId] ?? null;
            $given = is_string($given) ? trim($given) : null;

            $entry = [
                'question_id' => $questionId,
                'answer' => $given,
                'awarded_marks' => null,
                'is_correct' => null,
            ];

            if (in_array($question['question_type'], self::AUTO_GRADED_TYPES, true)) {
                $correct = $given !== null
                    && strcasecmp($given, (string) $question['correct_answer']) === 0;

                $entry['is_correct'] = $correct;
                $entry['awarded_marks'] = $correct ? (float) $question['marks'] : 0.0;

                $marks += $entry['awarded_marks'];
            } else {
                $manual = true;
            }

            $recorded[] = $entry;
        }

        return [
            'answers' => $recorded,
            'marks' => round($marks, 2),
            'manual' => $manual,
        ];
    }

    /**
     * The documented rule is that verification must succeed before the
     * examination begins. That is enforced whenever the AI service is
     * configured. When it is not, the session still starts but is recorded as
     * unverified and the reason is carried into the AI report — a missing
     * proctor is never recorded as a passed check.
     */
    private function verifyIdentity(array $user, array $exam, ?string $image): array
    {
        if (!(bool) $exam['require_camera']) {
            return [
                'verified' => false,
                'note' => 'This examination does not require camera proctoring.',
            ];
        }

        if (!$this->proctor->isConfigured()) {
            return [
                'verified' => false,
                'note' => 'Started without identity verification: the AI service is not configured.',
            ];
        }

        if ($image === null || trim($image) === '') {
            throw new ApiException('A camera image is required to verify your identity.', 422);
        }

        $result = $this->faces->verify((int) $user['user_id'], $image);

        if (!$result['verified']) {
            throw new ApiException('Identity verification failed. Please try again.', 403);
        }

        return [
            'verified' => true,
            'note' => 'Identity verified at ' . gmdate('Y-m-d H:i:s') . ' UTC.',
        ];
    }

    private function resumeExisting(array $session, array $exam): array
    {
        if (time() > strtotime($session['expires_at'] . ' UTC')) {
            $this->finish($session, $exam, [], 'Auto Submitted', 'expired', 'The allowed time ran out.');

            throw new ApiException('Your time for this examination has run out.', 409);
        }

        return $this->present($session, $exam);
    }

    /**
     * A browser sends a user agent far longer than the column, and a client
     * must never be able to turn that into a 500.
     */
    private function clip(?string $value, int $length): ?string
    {
        return $value === null ? null : mb_substr(trim($value), 0, $length);
    }

    private function present(array $session, array $exam): array
    {
        $session['exam_title'] = $exam['title'];
        $session['duration'] = (int) $exam['duration'];
        $session['seconds_remaining'] = max(0, strtotime($session['expires_at'] . ' UTC') - time());

        return $session;
    }

    private function guardOpen(array $exam): void
    {
        if ($exam['status'] !== 'published') {
            throw new ApiException('This examination is not open.', 409);
        }

        $now = time();

        if ($now < strtotime($exam['start_time'] . ' UTC')) {
            throw new ApiException('This examination has not started yet.', 409);
        }

        if ($now > strtotime($exam['end_time'] . ' UTC')) {
            throw new ApiException('This examination has closed.', 409);
        }
    }
}
