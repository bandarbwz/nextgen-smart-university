<?php

declare(strict_types=1);

namespace App\Models;

class QuizSubmission extends Model
{
    protected string $table = 'QuizSubmission';

    protected string $defaultOrder = 'submitted_at DESC';

    public function attemptCount(int $quizId, int $studentId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM QuizSubmission WHERE quiz_id = :quiz_id AND student_id = :student_id'
        );

        $statement->execute([
            'quiz_id' => $quizId,
            'student_id' => $studentId,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function quizHasSubmissions(int $quizId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM QuizSubmission WHERE quiz_id = :quiz_id LIMIT 1'
        );

        $statement->execute(['quiz_id' => $quizId]);

        return $statement->fetchColumn() !== false;
    }

    public function forQuiz(int $quizId): array
    {
        $statement = $this->db->prepare(
            'SELECT sub.*, st.student_number, u.full_name AS student_name
             FROM QuizSubmission sub
             JOIN Student st ON st.id = sub.student_id
             JOIN User u ON u.id = st.user_id
             WHERE sub.quiz_id = :quiz_id
             ORDER BY st.student_number, sub.attempt_number'
        );

        $statement->execute(['quiz_id' => $quizId]);

        return $statement->fetchAll();
    }

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT sub.*, q.title AS quiz_title, q.total_marks, c.course_code, c.course_name
             FROM QuizSubmission sub
             JOIN Quiz q ON q.id = sub.quiz_id
             JOIN Section s ON s.id = q.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE sub.student_id = :student_id
             ORDER BY sub.submitted_at DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function answers(int $submissionId): array
    {
        $statement = $this->db->prepare(
            'SELECT a.*, q.question, q.question_type, q.marks
             FROM QuizAnswer a
             JOIN QuizQuestion q ON q.id = a.question_id
             WHERE a.submission_id = :submission_id
             ORDER BY q.position, q.id'
        );

        $statement->execute(['submission_id' => $submissionId]);

        return $statement->fetchAll();
    }

    public function saveAnswer(
        int $submissionId,
        int $questionId,
        ?string $answerText,
        ?float $awardedMarks,
        ?bool $isCorrect
    ): bool {
        $statement = $this->db->prepare(
            'INSERT INTO QuizAnswer (submission_id, question_id, answer_text, awarded_marks, is_correct)
             VALUES (:submission_id, :question_id, :answer_text, :awarded_marks, :is_correct)'
        );

        return $statement->execute([
            'submission_id' => $submissionId,
            'question_id' => $questionId,
            'answer_text' => $answerText,
            'awarded_marks' => $awardedMarks,
            'is_correct' => $isCorrect === null ? null : (int) $isCorrect,
        ]);
    }

    public function finalise(int $id, float $score, int $gradedBy): bool
    {
        $statement = $this->db->prepare(
            'UPDATE QuizSubmission
             SET score = :score, status = :status, graded_by = :graded_by, graded_at = UTC_TIMESTAMP()
             WHERE id = :id'
        );

        return $statement->execute([
            'score' => $score,
            'status' => 'Graded',
            'graded_by' => $gradedBy,
            'id' => $id,
        ]);
    }

    public function hasManualQuestions(int $submissionId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM QuizAnswer WHERE submission_id = :submission_id AND awarded_marks IS NULL LIMIT 1'
        );

        $statement->execute(['submission_id' => $submissionId]);

        return $statement->fetchColumn() !== false;
    }
}
