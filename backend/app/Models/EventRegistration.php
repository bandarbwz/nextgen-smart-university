<?php

declare(strict_types=1);

namespace App\Models;

class EventRegistration extends Model
{
    protected string $table = 'EventRegistration';

    protected string $defaultOrder = 'registration_date DESC';

    public function findForStudent(int $eventId, int $studentId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM EventRegistration
             WHERE event_id = :event_id AND student_id = :student_id
             LIMIT 1'
        );

        $statement->execute([
            'event_id' => $eventId,
            'student_id' => $studentId,
        ]);

        return $statement->fetch() ?: null;
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, e.event_name, e.event_date, e.start_time, e.end_time, e.award_points,
                    e.maximum_participants, e.status AS event_status,
                    st.student_number, u.full_name AS student_name,
                    a.id AS attendance_id, a.attendance_time, a.attendance_method
             FROM EventRegistration r
             JOIN Event e ON e.id = r.event_id
             JOIN Student st ON st.id = r.student_id
             JOIN User u ON u.id = st.user_id
             LEFT JOIN EventAttendance a ON a.registration_id = r.id
             WHERE r.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function forEvent(int $eventId, ?string $status): array
    {
        $sql = 'SELECT r.*, st.student_number, u.full_name AS student_name, u.email,
                       a.attendance_time, a.attendance_method
                FROM EventRegistration r
                JOIN Student st ON st.id = r.student_id
                JOIN User u ON u.id = st.user_id
                LEFT JOIN EventAttendance a ON a.registration_id = r.id
                WHERE r.event_id = :event_id';

        $parameters = ['event_id' => $eventId];

        if ($status !== null) {
            $sql .= ' AND r.status = :status';
            $parameters['status'] = $status;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY st.student_number');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, e.event_name, e.event_date, e.start_time, e.venue, e.status AS event_status,
                    e.award_points, c.club_name, a.attendance_time
             FROM EventRegistration r
             JOIN Event e ON e.id = r.event_id
             LEFT JOIN Club c ON c.id = e.club_id
             LEFT JOIN EventAttendance a ON a.registration_id = r.id
             WHERE r.student_id = :student_id
             ORDER BY e.event_date DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function approvedCount(int $eventId): int
    {
        $statement = $this->db->prepare(
            "SELECT COUNT(*) FROM EventRegistration
             WHERE event_id = :event_id AND status = 'Approved'"
        );

        $statement->execute(['event_id' => $eventId]);

        return (int) $statement->fetchColumn();
    }

    public function approvedForStudentAndEvent(int $eventId, int $studentId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT * FROM EventRegistration
             WHERE event_id = :event_id AND student_id = :student_id AND status = 'Approved'
             LIMIT 1"
        );

        $statement->execute([
            'event_id' => $eventId,
            'student_id' => $studentId,
        ]);

        return $statement->fetch() ?: null;
    }

    public function decide(int $id, string $status, int $decidedBy, ?string $reason): bool
    {
        $statement = $this->db->prepare(
            'UPDATE EventRegistration
             SET status = :status, decision_reason = :reason,
                 decided_by = :decided_by, decided_at = UTC_TIMESTAMP()
             WHERE id = :id'
        );

        return $statement->execute([
            'status' => $status,
            'reason' => $reason,
            'decided_by' => $decidedBy,
            'id' => $id,
        ]);
    }
}
