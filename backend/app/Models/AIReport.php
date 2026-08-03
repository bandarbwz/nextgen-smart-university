<?php

declare(strict_types=1);

namespace App\Models;

class AIReport extends Model
{
    protected string $table = 'AIReport';

    protected string $defaultOrder = 'generated_at DESC';

    public function findBySession(int $sessionId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM AIReport WHERE session_id = :session_id LIMIT 1'
        );

        $statement->execute(['session_id' => $sessionId]);

        return $statement->fetch() ?: null;
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, sess.exam_id, sess.student_id, sess.violation_count, sess.status AS session_status,
                    e.title AS exam_title, s.lecturer_id,
                    st.student_number, u.full_name AS student_name,
                    c.course_code, c.course_name
             FROM AIReport r
             JOIN ExamSession sess ON sess.id = r.session_id
             JOIN Exam e ON e.id = sess.exam_id
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             JOIN Student st ON st.id = sess.student_id
             JOIN User u ON u.id = st.user_id
             WHERE r.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }
}
