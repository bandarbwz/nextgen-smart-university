<?php

declare(strict_types=1);

namespace App\Models;

class AssessmentResult extends Model
{
    protected string $table = 'AssessmentResult';

    protected string $defaultOrder = 'graded_at DESC';

    public function findForStudent(int $assessmentId, int $studentId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM AssessmentResult
             WHERE assessment_id = :assessment_id AND student_id = :student_id
             LIMIT 1'
        );

        $statement->execute([
            'assessment_id' => $assessmentId,
            'student_id' => $studentId,
        ]);

        return $statement->fetch() ?: null;
    }

    public function forAssessment(int $assessmentId): array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, st.student_number, u.full_name AS student_name
             FROM AssessmentResult r
             JOIN Student st ON st.id = r.student_id
             JOIN User u ON u.id = st.user_id
             WHERE r.assessment_id = :assessment_id
             ORDER BY st.student_number'
        );

        $statement->execute(['assessment_id' => $assessmentId]);

        return $statement->fetchAll();
    }

    /**
     * Every published result a student holds in one section, with the weight of
     * the assessment it belongs to, which is what the course total is built from.
     */
    public function publishedForStudentInSection(int $studentId, int $sectionId): array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, a.title, a.assessment_type, a.total_marks, a.weight_percentage
             FROM AssessmentResult r
             JOIN Assessment a ON a.id = r.assessment_id
             WHERE r.student_id = :student_id AND a.section_id = :section_id
               AND a.deleted_at IS NULL AND r.published_at IS NOT NULL
             ORDER BY a.due_date, a.id'
        );

        $statement->execute([
            'student_id' => $studentId,
            'section_id' => $sectionId,
        ]);

        return $statement->fetchAll();
    }

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, a.title, a.assessment_type, a.total_marks, a.weight_percentage,
                    a.section_id, c.course_code, c.course_name, s.section_number
             FROM AssessmentResult r
             JOIN Assessment a ON a.id = r.assessment_id
             JOIN Section s ON s.id = a.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE r.student_id = :student_id AND a.deleted_at IS NULL
               AND r.published_at IS NOT NULL
             ORDER BY r.graded_at DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function publishForAssessment(int $assessmentId): int
    {
        $statement = $this->db->prepare(
            'UPDATE AssessmentResult SET published_at = UTC_TIMESTAMP()
             WHERE assessment_id = :assessment_id AND published_at IS NULL'
        );

        $statement->execute(['assessment_id' => $assessmentId]);

        return $statement->rowCount();
    }

    public function statistics(int $assessmentId): array
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) AS graded, AVG(percentage) AS average,
                    MIN(percentage) AS lowest, MAX(percentage) AS highest
             FROM AssessmentResult
             WHERE assessment_id = :assessment_id'
        );

        $statement->execute(['assessment_id' => $assessmentId]);

        $row = $statement->fetch() ?: [];

        return [
            'graded' => (int) ($row['graded'] ?? 0),
            'average' => $row['average'] === null ? null : round((float) $row['average'], 2),
            'lowest' => $row['lowest'] === null ? null : round((float) $row['lowest'], 2),
            'highest' => $row['highest'] === null ? null : round((float) $row['highest'], 2),
        ];
    }
}
