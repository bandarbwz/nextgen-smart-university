<?php

declare(strict_types=1);

namespace App\Models;

class SystemLog extends Model
{
    protected string $table = 'SystemLog';

    protected string $defaultOrder = 'created_at DESC';

    public function recent(array $filters): array
    {
        $conditions = ['1 = 1'];
        $parameters = [];

        if (isset($filters['severity'])) {
            $conditions[] = 'l.severity = :severity';
            $parameters['severity'] = $filters['severity'];
        }

        if (isset($filters['module'])) {
            $conditions[] = 'l.module = :module';
            $parameters['module'] = $filters['module'];
        }

        $statement = $this->db->prepare(
            'SELECT l.*, u.full_name AS created_by_name
             FROM SystemLog l
             LEFT JOIN User u ON u.id = l.created_by
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY l.created_at DESC
             LIMIT 200'
        );

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function counts(): array
    {
        return $this->db
            ->query(
                'SELECT severity, COUNT(*) AS total
                 FROM SystemLog
                 GROUP BY severity'
            )
            ->fetchAll();
    }

    public function record(
        string $module,
        string $action,
        string $severity,
        string $message,
        ?int $userId
    ): int {
        return $this->create([
            'module' => $module,
            'action' => $action,
            'severity' => $severity,
            'message' => $message,
            'created_by' => $userId,
        ]);
    }
}
