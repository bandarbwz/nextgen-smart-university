<?php

declare(strict_types=1);

namespace App\Models;

class GradeApproval extends Model
{
    protected string $table = 'GradeApproval';

    protected string $defaultOrder = 'submitted_at DESC';

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT ga.*, c.course_code, c.course_name, c.credit_hours, s.section_number,
                    s.semester_id, s.course_id, u.full_name AS lecturer_name,
                    reviewer.full_name AS coordinator_name
             FROM GradeApproval ga
             JOIN Section s ON s.id = ga.section_id
             JOIN Course c ON c.id = s.course_id
             JOIN Lecturer l ON l.id = ga.lecturer_id
             JOIN User u ON u.id = l.user_id
             LEFT JOIN User reviewer ON reviewer.id = ga.coordinator_id
             WHERE ga.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function openForSection(int $sectionId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT * FROM GradeApproval
             WHERE section_id = :section_id
               AND approval_status IN ('Pending', 'Returned for Revision')
             ORDER BY id DESC
             LIMIT 1"
        );

        $statement->execute(['section_id' => $sectionId]);

        return $statement->fetch() ?: null;
    }

    public function approvedForSection(int $sectionId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT * FROM GradeApproval
             WHERE section_id = :section_id AND approval_status = 'Approved'
             ORDER BY id DESC
             LIMIT 1"
        );

        $statement->execute(['section_id' => $sectionId]);

        return $statement->fetch() ?: null;
    }

    public function listing(array $filters): array
    {
        $conditions = ['1 = 1'];
        $parameters = [];

        if (isset($filters['status'])) {
            $conditions[] = 'ga.approval_status = :status';
            $parameters['status'] = $filters['status'];
        }

        if (isset($filters['lecturer_id'])) {
            $conditions[] = 'ga.lecturer_id = :lecturer_id';
            $parameters['lecturer_id'] = $filters['lecturer_id'];
        }

        if (isset($filters['department_id'])) {
            $conditions[] = 'c.department_id = :department_id';
            $parameters['department_id'] = $filters['department_id'];
        }

        $statement = $this->db->prepare(
            'SELECT ga.*, c.course_code, c.course_name, s.section_number,
                    u.full_name AS lecturer_name
             FROM GradeApproval ga
             JOIN Section s ON s.id = ga.section_id
             JOIN Course c ON c.id = s.course_id
             JOIN Lecturer l ON l.id = ga.lecturer_id
             JOIN User u ON u.id = l.user_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY ga.submitted_at DESC'
        );

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function decide(int $id, string $status, int $coordinatorUserId, ?string $remarks): bool
    {
        $statement = $this->db->prepare(
            'UPDATE GradeApproval
             SET approval_status = :status, coordinator_id = :coordinator_id,
                 remarks = :remarks, reviewed_at = UTC_TIMESTAMP()
             WHERE id = :id'
        );

        return $statement->execute([
            'status' => $status,
            'coordinator_id' => $coordinatorUserId,
            'remarks' => $remarks,
            'id' => $id,
        ]);
    }
}
