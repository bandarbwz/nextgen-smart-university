<?php

declare(strict_types=1);

namespace App\Models;

class ActivityPoint extends Model
{
    protected string $table = 'ActivityPoint';

    protected string $defaultOrder = 'awarded_date DESC';

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT p.*, e.event_name, e.event_date, c.club_name
             FROM ActivityPoint p
             JOIN Event e ON e.id = p.event_id
             LEFT JOIN Club c ON c.id = e.club_id
             WHERE p.student_id = :student_id
             ORDER BY p.awarded_date DESC'
        );

        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function totalForStudent(int $studentId): int
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(points), 0) FROM ActivityPoint WHERE student_id = :student_id'
        );

        $statement->execute(['student_id' => $studentId]);

        return (int) $statement->fetchColumn();
    }

    public function existsFor(int $studentId, int $eventId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM ActivityPoint
             WHERE student_id = :student_id AND event_id = :event_id
             LIMIT 1'
        );

        $statement->execute([
            'student_id' => $studentId,
            'event_id' => $eventId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function leaderboard(): array
    {
        return $this->db
            ->query(
                'SELECT p.student_id, st.student_number, u.full_name AS student_name,
                        SUM(p.points) AS total_points, COUNT(*) AS events_attended
                 FROM ActivityPoint p
                 JOIN Student st ON st.id = p.student_id
                 JOIN User u ON u.id = st.user_id
                 GROUP BY p.student_id, st.student_number, u.full_name
                 ORDER BY total_points DESC, u.full_name'
            )
            ->fetchAll();
    }
}
