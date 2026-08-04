<?php

declare(strict_types=1);

namespace App\Models;

class GradeApprovalLog extends Model
{
    protected string $table = 'GradeApprovalLog';

    protected string $defaultOrder = 'created_at';

    public function forApproval(int $approvalId): array
    {
        $statement = $this->db->prepare(
            'SELECT gal.*, u.full_name AS performed_by_name, r.name AS performed_by_role
             FROM GradeApprovalLog gal
             JOIN User u ON u.id = gal.performed_by
             JOIN Role r ON r.id = u.role_id
             WHERE gal.grade_approval_id = :approval_id
             ORDER BY gal.created_at, gal.id'
        );

        $statement->execute(['approval_id' => $approvalId]);

        return $statement->fetchAll();
    }

    public function history(?int $departmentId): array
    {
        $sql = 'SELECT gal.*, u.full_name AS performed_by_name,
                       c.course_code, s.section_number
                FROM GradeApprovalLog gal
                JOIN GradeApproval ga ON ga.id = gal.grade_approval_id
                JOIN Section s ON s.id = ga.section_id
                JOIN Course c ON c.id = s.course_id
                JOIN User u ON u.id = gal.performed_by';

        $parameters = [];

        if ($departmentId !== null) {
            $sql .= ' WHERE c.department_id = :department_id';
            $parameters['department_id'] = $departmentId;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY gal.created_at DESC LIMIT 200');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function record(int $approvalId, string $action, int $userId, ?string $remarks): int
    {
        return $this->create([
            'grade_approval_id' => $approvalId,
            'action' => $action,
            'performed_by' => $userId,
            'remarks' => $remarks,
        ]);
    }
}
