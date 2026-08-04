<?php

declare(strict_types=1);

namespace App\Models;

class Event extends Model
{
    protected string $table = 'Event';

    protected bool $softDeletes = true;

    protected string $defaultOrder = 'event_date DESC, start_time';

    public function listing(array $filters, bool $publishedOnly): array
    {
        $conditions = ['e.deleted_at IS NULL'];
        $parameters = [];

        if ($publishedOnly) {
            $conditions[] = "e.status IN ('published', 'completed')";
        }

        if (isset($filters['club_id'])) {
            $conditions[] = 'e.club_id = :club_id';
            $parameters['club_id'] = $filters['club_id'];
        }

        if (isset($filters['status'])) {
            $conditions[] = 'e.status = :status';
            $parameters['status'] = $filters['status'];
        }

        if (isset($filters['from'])) {
            $conditions[] = 'e.event_date >= :from';
            $parameters['from'] = $filters['from'];
        }

        if (isset($filters['to'])) {
            $conditions[] = 'e.event_date <= :to';
            $parameters['to'] = $filters['to'];
        }

        $statement = $this->db->prepare(
            'SELECT e.*, c.club_name, ' . $this->approvedCountExpression() . ' AS registered_count
             FROM Event e
             LEFT JOIN Club c ON c.id = e.club_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY e.event_date, e.start_time'
        );

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT e.*, c.club_name, ' . $this->approvedCountExpression() . ' AS registered_count
             FROM Event e
             LEFT JOIN Club c ON c.id = e.club_id
             WHERE e.id = :id AND e.deleted_at IS NULL
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function upcomingForClub(int $clubId): array
    {
        $statement = $this->db->prepare(
            "SELECT * FROM Event
             WHERE club_id = :club_id AND deleted_at IS NULL AND status = 'published'
               AND event_date >= UTC_DATE()
             ORDER BY event_date, start_time"
        );

        $statement->execute(['club_id' => $clubId]);

        return $statement->fetchAll();
    }

    /**
     * A seat is held by an approved registration only. Pending requests do not
     * reserve a place, so a full event is one with enough approvals.
     */
    private function approvedCountExpression(): string
    {
        return "(SELECT COUNT(*) FROM EventRegistration r
                 WHERE r.event_id = e.id AND r.status = 'Approved')";
    }
}
