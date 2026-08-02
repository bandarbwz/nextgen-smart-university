<?php

declare(strict_types=1);

namespace App\Models;

class AssignmentSubmission extends Model
{
    protected string $table = 'AssignmentSubmission';

    protected string $defaultOrder = 'submitted_at DESC';

    public function findForStudent(int $assignmentId, int $studentId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM AssignmentSubmission
             WHERE assignment_id = :assignment_id AND student_id = :student_id
             LIMIT 1'
        );

        $statement->execute([
            'assignment_id' => $assignmentId,
            'student_id' => $studentId,
        ]);

        return $statement->fetch() ?: null;
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT sub.*, a.title AS assignment_title, a.total_marks, a.section_id,
                    s.lecturer_id, st.student_number, u.full_name AS student_name
             FROM AssignmentSubmission sub
             JOIN Assignment a ON a.id = sub.assignment_id
             JOIN Section s ON s.id = a.section_id
             JOIN Student st ON st.id = sub.student_id
             JOIN User u ON u.id = st.user_id
             WHERE sub.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function forAssignment(int $assignmentId): array
    {
        $statement = $this->db->prepare(
            'SELECT sub.*, st.student_number, u.full_name AS student_name
             FROM AssignmentSubmission sub
             JOIN Student st ON st.id = sub.student_id
             JOIN User u ON u.id = st.user_id
             WHERE sub.assignment_id = :assignment_id
             ORDER BY st.student_number'
        );

        $statement->execute(['assignment_id' => $assignmentId]);

        return $statement->fetchAll();
    }

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT sub.*, a.title AS assignment_title, a.total_marks, a.due_date,
                    c.course_code, c.course_name
             FROM AssignmentSubmission sub
             JOIN Assignment a ON a.id = sub.assignment_id
             JOIN Section s ON s.id = a.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE sub.student_id = :student_id
             ORDER BY sub.submitted_at DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function grade(int $id, float $marks, ?string $feedback, int $gradedBy): bool
    {
        $statement = $this->db->prepare(
            'UPDATE AssignmentSubmission
             SET marks = :marks,
                 feedback = :feedback,
                 submission_status = :status,
                 graded_by = :graded_by,
                 graded_at = UTC_TIMESTAMP()
             WHERE id = :id'
        );

        return $statement->execute([
            'marks' => $marks,
            'feedback' => $feedback,
            'status' => 'Graded',
            'graded_by' => $gradedBy,
            'id' => $id,
        ]);
    }
}
