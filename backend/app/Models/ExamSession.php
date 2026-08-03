<?php

declare(strict_types=1);

namespace App\Models;

class ExamSession extends Model
{
    protected string $table = 'ExamSession';

    protected string $defaultOrder = 'session_start DESC';

    public function openForStudent(int $examId, int $studentId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT * FROM ExamSession
             WHERE exam_id = :exam_id AND student_id = :student_id
               AND status IN ('active', 'paused')
             ORDER BY id DESC
             LIMIT 1"
        );

        $statement->execute([
            'exam_id' => $examId,
            'student_id' => $studentId,
        ]);

        return $statement->fetch() ?: null;
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT sess.*, e.title AS exam_title, e.section_id, e.total_marks, e.duration,
                    st.student_number, u.full_name AS student_name, s.lecturer_id
             FROM ExamSession sess
             JOIN Exam e ON e.id = sess.exam_id
             JOIN Section s ON s.id = e.section_id
             JOIN Student st ON st.id = sess.student_id
             JOIN User u ON u.id = st.user_id
             WHERE sess.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function forExam(int $examId): array
    {
        $statement = $this->db->prepare(
            'SELECT sess.*, st.student_number, u.full_name AS student_name
             FROM ExamSession sess
             JOIN Student st ON st.id = sess.student_id
             JOIN User u ON u.id = st.user_id
             WHERE sess.exam_id = :exam_id
             ORDER BY sess.session_start DESC'
        );

        $statement->execute(['exam_id' => $examId]);

        return $statement->fetchAll();
    }

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT sess.*, e.title AS exam_title, c.course_code, c.course_name
             FROM ExamSession sess
             JOIN Exam e ON e.id = sess.exam_id
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE sess.student_id = :student_id
             ORDER BY sess.session_start DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function countViolations(int $id): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM AIViolation WHERE session_id = :session_id'
        );

        $statement->execute(['session_id' => $id]);

        return (int) $statement->fetchColumn();
    }

    public function refreshViolationCount(int $id): bool
    {
        return $this->update($id, ['violation_count' => $this->countViolations($id)]);
    }

    public function close(int $id, string $status, ?string $reason = null): bool
    {
        $statement = $this->db->prepare(
            'UPDATE ExamSession
             SET status = :status, termination_reason = :reason, session_end = UTC_TIMESTAMP()
             WHERE id = :id'
        );

        return $statement->execute([
            'status' => $status,
            'reason' => $reason,
            'id' => $id,
        ]);
    }
}
