<?php

declare(strict_types=1);

namespace App\Models;

class CalendarEvent extends Model
{
    protected string $table = 'CalendarEvent';

    protected string $defaultOrder = 'start_datetime';

    public function forUserInRange(int $userId, string $from, string $to, ?string $eventType): array
    {
        $sql = 'SELECT * FROM CalendarEvent
                WHERE user_id = :user_id
                  AND status != :cancelled
                  AND start_datetime < :to
                  AND end_datetime >= :from';

        $parameters = [
            'user_id' => $userId,
            'cancelled' => 'cancelled',
            'from' => $from,
            'to' => $to,
        ];

        if ($eventType !== null) {
            $sql .= ' AND event_type = :event_type';
            $parameters['event_type'] = $eventType;
        }

        $statement = $this->db->prepare($sql . ' ORDER BY start_datetime');
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM CalendarEvent WHERE id = :id AND user_id = :user_id LIMIT 1'
        );

        $statement->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);

        return $statement->fetch() ?: null;
    }

    public function upsertGenerated(array $fields): bool
    {
        $statement = $this->db->prepare(
            'INSERT INTO CalendarEvent
                (user_id, title, description, event_type, module, reference_id,
                 start_datetime, end_datetime, location, color, is_all_day, reminder_enabled, status)
             VALUES
                (:user_id, :title, :description, :event_type, :module, :reference_id,
                 :start_datetime, :end_datetime, :location, :color, :is_all_day, :reminder_enabled, :status)
             ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                description = VALUES(description),
                end_datetime = VALUES(end_datetime),
                location = VALUES(location),
                color = VALUES(color)'
        );

        return $statement->execute($this->normalise($fields));
    }

    public function deleteGeneratedForUser(int $userId, string $module): bool
    {
        $statement = $this->db->prepare(
            'DELETE FROM CalendarEvent WHERE user_id = :user_id AND module = :module'
        );

        return $statement->execute([
            'user_id' => $userId,
            'module' => $module,
        ]);
    }

    public function countForUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT event_type, COUNT(*) AS total
             FROM CalendarEvent
             WHERE user_id = :user_id AND status != :cancelled
             GROUP BY event_type'
        );

        $statement->execute([
            'user_id' => $userId,
            'cancelled' => 'cancelled',
        ]);

        return $statement->fetchAll();
    }

    public function upcomingForUser(int $userId, int $limit): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM CalendarEvent
             WHERE user_id = :user_id AND status = :active AND start_datetime >= UTC_TIMESTAMP()
             ORDER BY start_datetime
             LIMIT :row_limit'
        );

        $statement->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $statement->bindValue('active', 'active');
        $statement->bindValue('row_limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
