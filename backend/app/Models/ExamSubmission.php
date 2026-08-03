<?php

declare(strict_types=1);

namespace App\Models;

class ExamSubmission extends Model
{
    protected string $table = 'ExamSubmission';

    protected string $defaultOrder = 'submitted_at DESC';

    public function findForStudent(int $examId, int $studentId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM ExamSubmission
             WHERE exam_id = :exam_id AND student_id = :student_id
             LIMIT 1'
        );

        $statement->execute([
            'exam_id' => $examId,
            'student_id' => $studentId,
        ]);

        return $statement->fetch() ?: null;
    }

    public function forExam(int $examId): array
    {
        $statement = $this->db->prepare(
            'SELECT sub.*, st.student_number, u.full_name AS student_name,
                    sess.violation_count, sess.identity_verified
             FROM ExamSubmission sub
             JOIN Student st ON st.id = sub.student_id
             JOIN User u ON u.id = st.user_id
             LEFT JOIN ExamSession sess ON sess.id = sub.session_id
             WHERE sub.exam_id = :exam_id
             ORDER BY st.student_number'
        );

        $statement->execute(['exam_id' => $examId]);

        return $statement->fetchAll();
    }

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT sub.*, e.title AS exam_title, e.total_marks, e.passing_marks,
                    c.course_code, c.course_name
             FROM ExamSubmission sub
             JOIN Exam e ON e.id = sub.exam_id
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE sub.student_id = :student_id
             ORDER BY sub.submitted_at DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function finalise(int $id, float $score, int $gradedBy): bool
    {
        $statement = $this->db->prepare(
            'UPDATE ExamSubmission
             SET score = :score, submission_status = :status,
                 graded_by = :graded_by, graded_at = UTC_TIMESTAMP()
             WHERE id = :id'
        );

        return $statement->execute([
            'score' => $score,
            'status' => 'Graded',
            'graded_by' => $gradedBy,
            'id' => $id,
        ]);
    }
}
