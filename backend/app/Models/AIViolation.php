<?php

declare(strict_types=1);

namespace App\Models;

class AIViolation extends Model
{
    protected string $table = 'AIViolation';

    protected string $defaultOrder = 'detected_at DESC';

    public function forSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM AIViolation WHERE session_id = :session_id ORDER BY detected_at'
        );

        $statement->execute(['session_id' => $sessionId]);

        return $statement->fetchAll();
    }

    public function forExam(int $examId): array
    {
        $statement = $this->db->prepare(
            'SELECT v.*, st.student_number, u.full_name AS student_name
             FROM AIViolation v
             JOIN ExamSession sess ON sess.id = v.session_id
             JOIN Student st ON st.id = sess.student_id
             JOIN User u ON u.id = st.user_id
             WHERE sess.exam_id = :exam_id
             ORDER BY v.detected_at DESC'
        );

        $statement->execute(['exam_id' => $examId]);

        return $statement->fetchAll();
    }

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT v.*, e.title AS exam_title, c.course_code
             FROM AIViolation v
             JOIN ExamSession sess ON sess.id = v.session_id
             JOIN Exam e ON e.id = sess.exam_id
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE sess.student_id = :student_id
             ORDER BY v.detected_at DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function countBySeverity(int $sessionId, string $severity): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM AIViolation
             WHERE session_id = :session_id AND severity = :severity'
        );

        $statement->execute([
            'session_id' => $sessionId,
            'severity' => $severity,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function summaryForSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            'SELECT violation_type, severity, COUNT(*) AS occurrences
             FROM AIViolation
             WHERE session_id = :session_id
             GROUP BY violation_type, severity
             ORDER BY occurrences DESC'
        );

        $statement->execute(['session_id' => $sessionId]);

        return $statement->fetchAll();
    }
}
