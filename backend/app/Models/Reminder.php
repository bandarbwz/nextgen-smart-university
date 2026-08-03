<?php

declare(strict_types=1);

namespace App\Models;

class Reminder extends Model
{
    protected string $table = 'Reminder';

    protected string $defaultOrder = 'reminder_time';

    public function forUser(int $userId, ?string $status): array
    {
        $sql = 'SELECT r.*, e.title, e.start_datetime, e.event_type
                FROM Reminder r
                JOIN CalendarEvent e ON e.id = r.calendar_event_id
                WHERE e.user_id = :user_id';

        $parameters = ['user_id' => $userId];

        if ($status !== null) {
            $sql .= ' AND r.reminder_status = :status';
            $parameters['status'] = $status;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY r.reminder_time');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function dueForUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, e.title, e.start_datetime, e.event_type
             FROM Reminder r
             JOIN CalendarEvent e ON e.id = r.calendar_event_id
             WHERE e.user_id = :user_id
               AND r.reminder_status = :pending
               AND r.reminder_time <= UTC_TIMESTAMP()
               AND e.start_datetime >= UTC_TIMESTAMP()
             ORDER BY r.reminder_time'
        );

        $statement->execute([
            'user_id' => $userId,
            'pending' => 'pending',
        ]);

        return $statement->fetchAll();
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, e.user_id, e.start_datetime
             FROM Reminder r
             JOIN CalendarEvent e ON e.id = r.calendar_event_id
             WHERE r.id = :id AND e.user_id = :user_id
             LIMIT 1'
        );

        $statement->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);

        return $statement->fetch() ?: null;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $statement = $this->db->prepare(
            'UPDATE Reminder SET reminder_status = :status WHERE id = :id'
        );

        return $statement->execute([
            'status' => $status,
            'id' => $id,
        ]);
    }
}
