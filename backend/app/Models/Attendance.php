<?php

declare(strict_types=1);

namespace App\Models;

class Attendance extends Model
{
    protected string $table = 'Attendance';

    protected string $defaultOrder = 'attendance_date DESC';

    public function existsForDate(int $studentId, int $sectionId, string $date): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM Attendance
             WHERE student_id = :student_id AND section_id = :section_id AND attendance_date = :date
             LIMIT 1'
        );

        $statement->execute([
            'student_id' => $studentId,
            'section_id' => $sectionId,
            'date' => $date,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function findForStudentAndDate(int $studentId, int $sectionId, string $date): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM Attendance
             WHERE student_id = :student_id AND section_id = :section_id AND attendance_date = :date
             LIMIT 1'
        );

        $statement->execute([
            'student_id' => $studentId,
            'section_id' => $sectionId,
            'date' => $date,
        ]);

        return $statement->fetch() ?: null;
    }

    public function forStudent(int $studentId, ?int $sectionId = null): array
    {
        $sql = 'SELECT a.*, c.course_code, c.course_name, s.section_number
                FROM Attendance a
                JOIN Section s ON s.id = a.section_id
                JOIN Course c ON c.id = s.course_id
                WHERE a.student_id = :student_id';

        $parameters = ['student_id' => $studentId];

        if ($sectionId !== null) {
            $sql .= ' AND a.section_id = :section_id';
            $parameters['section_id'] = $sectionId;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY a.attendance_date DESC, c.course_code');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function forSection(int $sectionId, ?string $date = null): array
    {
        $sql = 'SELECT a.*, st.student_number, u.full_name AS student_name
                FROM Attendance a
                JOIN Student st ON st.id = a.student_id
                JOIN User u ON u.id = st.user_id
                WHERE a.section_id = :section_id';

        $parameters = ['section_id' => $sectionId];

        if ($date !== null) {
            $sql .= ' AND a.attendance_date = :date';
            $parameters['date'] = $date;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY a.attendance_date DESC, st.student_number');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function forLecturer(int $lecturerId, ?string $date = null): array
    {
        $sql = 'SELECT a.*, c.course_code, s.section_number, st.student_number,
                       u.full_name AS student_name
                FROM Attendance a
                JOIN Section s ON s.id = a.section_id
                JOIN Course c ON c.id = s.course_id
                JOIN Student st ON st.id = a.student_id
                JOIN User u ON u.id = st.user_id
                WHERE s.lecturer_id = :lecturer_id';

        $parameters = ['lecturer_id' => $lecturerId];

        if ($date !== null) {
            $sql .= ' AND a.attendance_date = :date';
            $parameters['date'] = $date;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY a.attendance_date DESC, c.course_code');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function statisticsForStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT c.course_code, c.course_name, s.id AS section_id,
                    COUNT(*) AS total_sessions,
                    SUM(a.attendance_status IN (:present, :late, :online)) AS attended,
                    SUM(a.attendance_status = :excused) AS excused,
                    SUM(a.attendance_status = :absent) AS absent
             FROM Attendance a
             JOIN Section s ON s.id = a.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE a.student_id = :student_id
             GROUP BY s.id, c.course_code, c.course_name
             ORDER BY c.course_code'
        );

        $statement->execute([
            'student_id' => $studentId,
            'present' => 'Present',
            'late' => 'Late',
            'online' => 'Online',
            'excused' => 'Excused',
            'absent' => 'Absent',
        ]);

        return $statement->fetchAll();
    }

    public function dailySummary(string $date): array
    {
        $statement = $this->db->prepare(
            'SELECT c.course_code, s.section_number,
                    COUNT(*) AS records,
                    SUM(a.attendance_status = :present) AS present,
                    SUM(a.attendance_status = :absent) AS absent,
                    SUM(a.attendance_status = :late) AS late_count
             FROM Attendance a
             JOIN Section s ON s.id = a.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE a.attendance_date = :date
             GROUP BY s.id, c.course_code, s.section_number
             ORDER BY c.course_code'
        );

        $statement->execute([
            'date' => $date,
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
        ]);

        return $statement->fetchAll();
    }

    public function monthlySummary(int $year, int $month): array
    {
        $statement = $this->db->prepare(
            'SELECT a.attendance_date,
                    COUNT(*) AS records,
                    SUM(a.attendance_status = :present) AS present,
                    SUM(a.attendance_status = :absent) AS absent
             FROM Attendance a
             WHERE YEAR(a.attendance_date) = :year AND MONTH(a.attendance_date) = :month
             GROUP BY a.attendance_date
             ORDER BY a.attendance_date'
        );

        $statement->execute([
            'year' => $year,
            'month' => $month,
            'present' => 'Present',
            'absent' => 'Absent',
        ]);

        return $statement->fetchAll();
    }

    public function updateStatus(int $id, string $status, ?int $verifiedBy, ?string $remarks): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Attendance
             SET attendance_status = :status, verified_by = :verified_by, remarks = :remarks
             WHERE id = :id'
        );

        return $statement->execute([
            'status' => $status,
            'verified_by' => $verifiedBy,
            'remarks' => $remarks,
            'id' => $id,
        ]);
    }
}
