<?php

declare(strict_types=1);

namespace App\Models;

class ExamResetLog extends Model
{
    protected string $table = 'ExamResetLog';

    protected string $defaultOrder = 'created_at';

    public function forRequest(int $requestId): array
    {
        $statement = $this->db->prepare(
            'SELECT l.*, u.full_name AS performed_by_name, r.name AS performed_by_role
             FROM ExamResetLog l
             JOIN User u ON u.id = l.performed_by
             JOIN Role r ON r.id = u.role_id
             WHERE l.reset_request_id = :request_id
             ORDER BY l.created_at, l.id'
        );

        $statement->execute(['request_id' => $requestId]);

        return $statement->fetchAll();
    }

    public function history(): array
    {
        return $this->db
            ->query(
                'SELECT l.*, u.full_name AS performed_by_name, e.title AS exam_title,
                        st.student_number
                 FROM ExamResetLog l
                 JOIN ExamResetRequest r ON r.id = l.reset_request_id
                 JOIN Exam e ON e.id = r.exam_id
                 JOIN Student st ON st.id = r.student_id
                 JOIN User u ON u.id = l.performed_by
                 ORDER BY l.created_at DESC
                 LIMIT 200'
            )
            ->fetchAll();
    }

    public function record(int $requestId, string $action, int $userId, ?string $remarks): int
    {
        return $this->create([
            'reset_request_id' => $requestId,
            'action' => $action,
            'performed_by' => $userId,
            'remarks' => $remarks,
        ]);
    }
}
