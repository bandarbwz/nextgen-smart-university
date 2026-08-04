<?php

declare(strict_types=1);

namespace App\Models;

class ExamResetRequest extends Model
{
    protected string $table = 'ExamResetRequest';

    protected string $defaultOrder = 'request_date DESC';

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, e.title AS exam_title, e.section_id, c.course_code, c.course_name,
                    s.lecturer_id, st.student_number, u.full_name AS student_name,
                    requester.full_name AS requested_by_name,
                    approver.full_name AS approved_by_name
             FROM ExamResetRequest r
             JOIN Exam e ON e.id = r.exam_id
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             JOIN Student st ON st.id = r.student_id
             JOIN User u ON u.id = st.user_id
             JOIN User requester ON requester.id = r.requested_by
             LEFT JOIN User approver ON approver.id = r.approved_by
             WHERE r.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function openForStudentAndExam(int $examId, int $studentId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT * FROM ExamResetRequest
             WHERE exam_id = :exam_id AND student_id = :student_id
               AND approval_status IN ('Pending', 'Recommended', 'Approved')
             ORDER BY id DESC
             LIMIT 1"
        );

        $statement->execute([
            'exam_id' => $examId,
            'student_id' => $studentId,
        ]);

        return $statement->fetch() ?: null;
    }

    public function listing(array $filters): array
    {
        $conditions = ['1 = 1'];
        $parameters = [];

        if (isset($filters['status'])) {
            $conditions[] = 'r.approval_status = :status';
            $parameters['status'] = $filters['status'];
        }

        if (isset($filters['student_id'])) {
            $conditions[] = 'r.student_id = :student_id';
            $parameters['student_id'] = $filters['student_id'];
        }

        if (isset($filters['lecturer_id'])) {
            $conditions[] = 's.lecturer_id = :lecturer_id';
            $parameters['lecturer_id'] = $filters['lecturer_id'];
        }

        $statement = $this->db->prepare(
            'SELECT r.*, e.title AS exam_title, c.course_code, st.student_number,
                    u.full_name AS student_name
             FROM ExamResetRequest r
             JOIN Exam e ON e.id = r.exam_id
             JOIN Section s ON s.id = e.section_id
             JOIN Course c ON c.id = s.course_id
             JOIN Student st ON st.id = r.student_id
             JOIN User u ON u.id = st.user_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY r.request_date DESC'
        );

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function decide(int $id, string $status, array $fields): bool
    {
        $assignments = ['approval_status = :status'];
        $parameters = ['status' => $status, 'id' => $id];

        foreach ($fields as $column => $value) {
            $assignments[] = $column . ' = :' . $column;
            $parameters[$column] = $value;
        }

        $statement = $this->db->prepare(
            'UPDATE ExamResetRequest SET ' . implode(', ', $assignments) . ' WHERE id = :id'
        );

        return $statement->execute($parameters);
    }
}
