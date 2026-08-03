<?php

declare(strict_types=1);

namespace App\Models;

class ExamRecording extends Model
{
    protected string $table = 'ExamRecording';

    protected string $defaultOrder = 'recorded_at';

    public function forSession(int $sessionId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM ExamRecording WHERE session_id = :session_id ORDER BY recorded_at'
        );

        $statement->execute(['session_id' => $sessionId]);

        return $statement->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, sess.exam_id, sess.student_id, s.lecturer_id
             FROM ExamRecording r
             JOIN ExamSession sess ON sess.id = r.session_id
             JOIN Exam e ON e.id = sess.exam_id
             JOIN Section s ON s.id = e.section_id
             WHERE r.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }
}
