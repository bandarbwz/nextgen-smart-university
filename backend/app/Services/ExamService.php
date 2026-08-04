<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamSubmission;
use Throwable;

class ExamService
{
    private const AUTO_GRADED_TYPES = ['Multiple Choice', 'True / False'];

    public function __construct(
        private readonly Exam $exams = new Exam(),
        private readonly ExamQuestion $questions = new ExamQuestion(),
        private readonly ExamSession $sessions = new ExamSession(),
        private readonly ExamSubmission $submissions = new ExamSubmission(),
        private readonly CourseAccessService $access = new CourseAccessService()
    ) {
    }

    public function list(array $user, ?int $sectionId): array
    {
        if ($sectionId !== null) {
            $this->access->guardSectionVisible($sectionId, $user);
        }

        $sectionIds = $sectionId === null ? $this->access->visibleSectionIds($user) : [$sectionId];
        $isStudent = $user['role'] === 'Student';

        $exams = $this->exams->forSections($sectionIds, $isStudent);

        if (!$isStudent) {
            return $exams;
        }

        $studentId = $this->access->requireStudentId($user['user_id']);

        foreach ($exams as $index => $exam) {
            $submission = $this->submissions->findForStudent((int) $exam['id'], $studentId);

            $exams[$index]['submitted'] = $submission !== null;
            $exams[$index]['score'] = $submission['score'] ?? null;
        }

        return $exams;
    }

    public function get(int $id, array $user): array
    {
        $exam = $this->requireExam($id);

        $this->access->guardSectionVisible((int) $exam['section_id'], $user);

        $questions = $this->questions->forExam($id);

        if ($user['role'] !== 'Student') {
            $exam['questions'] = $questions;

            return $exam;
        }

        $this->guardPublished($exam);

        $studentId = $this->access->requireStudentId($user['user_id']);
        $submission = $this->submissions->findForStudent($id, $studentId);
        $session = $this->sessions->openForStudent($id, $studentId);

        $exam['questions'] = $this->hideAnswers($questions);
        $exam['submitted'] = $submission !== null;
        $exam['score'] = $submission['score'] ?? null;
        $exam['open_session_id'] = $session === null ? null : (int) $session['id'];

        return $exam;
    }

    public function create(array $user, array $fields, array $questions): array
    {
        $this->access->guardSectionOwned((int) $fields['section_id'], $user);
        $this->guardPeriod($fields);

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $id = $this->exams->create([
                'section_id' => (int) $fields['section_id'],
                'title' => $fields['title'],
                'description' => $fields['description'] ?? null,
                'passing_marks' => (float) ($fields['passing_marks'] ?? 0),
                'duration' => (int) $fields['duration'],
                'start_time' => $fields['start_time'],
                'end_time' => $fields['end_time'],
                'require_camera' => (bool) ($fields['require_camera'] ?? true),
                'status' => $fields['status'] ?? 'draft',
                'created_by' => $user['user_id'],
            ]);

            $this->replaceQuestions($id, $questions);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        return $this->get($id, $user);
    }

    public function update(int $id, array $user, array $fields, array $questions): array
    {
        $exam = $this->requireExam($id);

        $this->access->guardSectionOwned((int) $exam['section_id'], $user);
        $this->guardPeriod($fields);

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $this->exams->update($id, [
                'title' => $fields['title'],
                'description' => $fields['description'] ?? null,
                'passing_marks' => (float) ($fields['passing_marks'] ?? 0),
                'duration' => (int) $fields['duration'],
                'start_time' => $fields['start_time'],
                'end_time' => $fields['end_time'],
                'require_camera' => (bool) ($fields['require_camera'] ?? true),
                'status' => $fields['status'] ?? $exam['status'],
            ]);

            if ($questions !== []) {
                $this->guardNoSessions($id, 'The questions cannot be changed once students have started.');

                $this->questions->deleteForExam($id);
                $this->replaceQuestions($id, $questions);
            }

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        return $this->get($id, $user);
    }

    public function delete(int $id, array $user): void
    {
        $exam = $this->requireExam($id);

        $this->access->guardSectionOwned((int) $exam['section_id'], $user);
        $this->guardNoSessions($id, 'This examination has already been sat and cannot be deleted.');

        $this->exams->delete($id);
    }

    public function submissions(int $examId, array $user): array
    {
        $exam = $this->requireExam($examId);

        $this->access->guardSectionOwned((int) $exam['section_id'], $user);

        return $this->submissions->forExam($examId);
    }

    public function grade(int $submissionId, array $user, float $score): array
    {
        $submission = $this->submissions->find($submissionId);

        if ($submission === null) {
            throw new ApiException('Submission not found.', 404);
        }

        $exam = $this->requireExam((int) $submission['exam_id']);

        $this->access->guardSectionOwned((int) $exam['section_id'], $user);

        if ($score > (float) $exam['total_marks']) {
            throw new ApiException('The score cannot be higher than the total marks.', 422);
        }

        $this->submissions->finalise($submissionId, $score, $user['user_id']);

        return $this->submissions->find($submissionId);
    }

    public function requireExam(int $id): array
    {
        $exam = $this->exams->findDetailed($id);

        if ($exam === null) {
            throw new ApiException('Examination not found.', 404);
        }

        return $exam;
    }

    private function replaceQuestions(int $examId, array $questions): void
    {
        $position = 1;

        foreach ($questions as $question) {
            $type = $question['question_type'];

            if (in_array($type, self::AUTO_GRADED_TYPES, true)
                && ($question['correct_answer'] ?? '') === '') {
                throw new ApiException(
                    'A correct answer is required for multiple choice and true or false questions.',
                    422
                );
            }

            $options = $question['options'] ?? [];

            $this->questions->create([
                'exam_id' => $examId,
                'question' => $question['question'],
                'question_type' => $type,
                'marks' => (float) $question['marks'],
                'correct_answer' => $question['correct_answer'] ?? null,
                'options' => $options === [] ? null : json_encode($options),
                'position' => $position,
            ]);

            $position++;
        }

        $this->exams->recalculateTotalMarks($examId);
    }

    private function hideAnswers(array $questions): array
    {
        return array_map(static function (array $question): array {
            unset($question['correct_answer']);

            return $question;
        }, $questions);
    }

    private function guardPublished(array $exam): void
    {
        if ($exam['status'] === 'draft') {
            throw new ApiException('This examination has not been published yet.', 403);
        }
    }

    private function guardPeriod(array $fields): void
    {
        if (strtotime($fields['start_time']) >= strtotime($fields['end_time'])) {
            throw new ApiException('The examination end time must be after the start time.', 422);
        }
    }

    private function guardNoSessions(int $examId, string $message): void
    {
        if ($this->sessions->forExam($examId) !== []) {
            throw new ApiException($message, 409);
        }
    }
}
