<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizSubmission;
use Throwable;

class QuizService
{
    private const AUTO_GRADED_TYPES = ['Multiple Choice', 'True / False'];

    public function __construct(
        private readonly Quiz $quizzes = new Quiz(),
        private readonly QuizQuestion $questions = new QuizQuestion(),
        private readonly QuizSubmission $submissions = new QuizSubmission(),
        private readonly CourseAccessService $access = new CourseAccessService()
    ) {
    }

    public function list(array $user, ?int $sectionId): array
    {
        if ($sectionId !== null) {
            $this->access->guardSectionVisible($sectionId, $user);
        }

        $sectionIds = $sectionId === null ? $this->access->visibleSectionIds($user) : [$sectionId];

        $quizzes = $this->quizzes->forSections($sectionIds);

        if ($user['role'] !== 'Student') {
            return $quizzes;
        }

        $studentId = $this->access->requireStudentId($user['user_id']);

        foreach ($quizzes as $index => $quiz) {
            $quizzes[$index]['attempts_used'] = $this->submissions->attemptCount(
                (int) $quiz['id'],
                $studentId
            );
        }

        return $quizzes;
    }

    public function get(int $id, array $user): array
    {
        $quiz = $this->requireQuiz($id);

        $this->access->guardSectionVisible((int) $quiz['section_id'], $user);

        $questions = $this->questions->forQuiz($id);

        $quiz['questions'] = $user['role'] === 'Student'
            ? $this->hideAnswers($questions)
            : $questions;

        if ($user['role'] === 'Student') {
            $quiz['attempts_used'] = $this->submissions->attemptCount(
                $id,
                $this->access->requireStudentId($user['user_id'])
            );
        }

        return $quiz;
    }

    public function create(array $user, array $fields, array $questions): array
    {
        $this->access->guardSectionOwned((int) $fields['section_id'], $user);
        $this->guardPeriod($fields);

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $id = $this->quizzes->create([
                'section_id' => (int) $fields['section_id'],
                'title' => $fields['title'],
                'description' => $fields['description'] ?? null,
                'duration' => (int) $fields['duration'],
                'start_time' => $fields['start_time'],
                'end_time' => $fields['end_time'],
                'attempts' => (int) ($fields['attempts'] ?? 1),
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
        $quiz = $this->requireQuiz($id);

        $this->access->guardSectionOwned((int) $quiz['section_id'], $user);
        $this->guardPeriod($fields);

        if ($this->submissions->quizHasSubmissions($id)) {
            throw new ApiException('This quiz already has submissions and cannot be changed.', 409);
        }

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $this->quizzes->update($id, [
                'title' => $fields['title'],
                'description' => $fields['description'] ?? null,
                'duration' => (int) $fields['duration'],
                'start_time' => $fields['start_time'],
                'end_time' => $fields['end_time'],
                'attempts' => (int) ($fields['attempts'] ?? 1),
            ]);

            if ($questions !== []) {
                $this->questions->deleteForQuiz($id);
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
        $quiz = $this->requireQuiz($id);

        $this->access->guardSectionOwned((int) $quiz['section_id'], $user);
        $this->quizzes->delete($id);
    }

    public function submit(int $id, array $user, array $answers): array
    {
        $quiz = $this->requireQuiz($id);
        $studentId = $this->access->requireStudentId($user['user_id']);

        $this->access->guardSectionVisible((int) $quiz['section_id'], $user);
        $this->guardAvailability($quiz);

        $used = $this->submissions->attemptCount($id, $studentId);

        if ($used >= (int) $quiz['attempts']) {
            throw new ApiException('You have used all attempts for this quiz.', 409);
        }

        $questions = $this->questions->forQuiz($id);

        if ($questions === []) {
            throw new ApiException('This quiz has no questions yet.', 409);
        }

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $submissionId = $this->submissions->create([
                'quiz_id' => $id,
                'student_id' => $studentId,
                'attempt_number' => $used + 1,
                'submitted_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'Submitted',
            ]);

            $autoScore = $this->recordAnswers($submissionId, $questions, $answers);

            $this->submissions->update($submissionId, ['auto_scored_marks' => $autoScore]);

            if (!$this->submissions->hasManualQuestions($submissionId)) {
                $this->submissions->finalise($submissionId, $autoScore, $user['user_id']);
            }

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        $submission = $this->submissions->find($submissionId);
        $submission['answers'] = $this->submissions->answers($submissionId);

        return $submission;
    }

    public function submissions(int $quizId, array $user): array
    {
        $quiz = $this->requireQuiz($quizId);

        $this->access->guardSectionOwned((int) $quiz['section_id'], $user);

        return $this->submissions->forQuiz($quizId);
    }

    private function recordAnswers(int $submissionId, array $questions, array $answers): float
    {
        $autoScore = 0.0;

        foreach ($questions as $question) {
            $questionId = (int) $question['id'];
            $given = $answers[$questionId] ?? $answers[(string) $questionId] ?? null;
            $given = is_string($given) ? trim($given) : null;

            $isAutoGraded = in_array($question['question_type'], self::AUTO_GRADED_TYPES, true);

            $awarded = null;
            $isCorrect = null;

            if ($isAutoGraded) {
                $isCorrect = $given !== null
                    && strcasecmp($given, (string) $question['correct_answer']) === 0;
                $awarded = $isCorrect ? (float) $question['marks'] : 0.0;
                $autoScore += $awarded;
            }

            $this->submissions->saveAnswer($submissionId, $questionId, $given, $awarded, $isCorrect);
        }

        return $autoScore;
    }

    private function replaceQuestions(int $quizId, array $questions): void
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

            $questionId = $this->questions->create([
                'quiz_id' => $quizId,
                'question' => $question['question'],
                'question_type' => $type,
                'marks' => (float) $question['marks'],
                'correct_answer' => $question['correct_answer'] ?? null,
                'position' => $position,
            ]);

            $optionPosition = 1;

            foreach ($question['options'] ?? [] as $option) {
                $this->questions->addOption(
                    $questionId,
                    $option['label'],
                    $option['text'],
                    $optionPosition,
                );

                $optionPosition++;
            }

            $position++;
        }

        $this->quizzes->recalculateTotalMarks($quizId);
    }

    private function hideAnswers(array $questions): array
    {
        return array_map(static function (array $question): array {
            unset($question['correct_answer']);

            return $question;
        }, $questions);
    }

    private function guardPeriod(array $fields): void
    {
        if (strtotime($fields['start_time']) >= strtotime($fields['end_time'])) {
            throw new ApiException('The quiz end time must be after the start time.', 422);
        }
    }

    private function guardAvailability(array $quiz): void
    {
        $now = time();

        if ($now < strtotime($quiz['start_time'] . ' UTC')) {
            throw new ApiException('This quiz is not open yet.', 409);
        }

        if ($now > strtotime($quiz['end_time'] . ' UTC')) {
            throw new ApiException('This quiz has closed.', 409);
        }
    }

    private function requireQuiz(int $id): array
    {
        $quiz = $this->quizzes->findDetailed($id);

        if ($quiz === null) {
            throw new ApiException('Quiz not found.', 404);
        }

        return $quiz;
    }
}
