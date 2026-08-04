<?php

declare(strict_types=1);

namespace App\Models;

class EventAttendance extends Model
{
    protected string $table = 'EventAttendance';

    protected string $defaultOrder = 'attendance_time DESC';

    public function findForRegistration(int $registrationId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM EventAttendance WHERE registration_id = :registration_id LIMIT 1'
        );

        $statement->execute(['registration_id' => $registrationId]);

        return $statement->fetch() ?: null;
    }

    public function forEvent(int $eventId): array
    {
        $statement = $this->db->prepare(
            'SELECT a.*, st.student_number, u.full_name AS student_name, r.student_id
             FROM EventAttendance a
             JOIN EventRegistration r ON r.id = a.registration_id
             JOIN Student st ON st.id = r.student_id
             JOIN User u ON u.id = st.user_id
             WHERE r.event_id = :event_id
             ORDER BY a.attendance_time'
        );

        $statement->execute(['event_id' => $eventId]);

        return $statement->fetchAll();
    }
}
