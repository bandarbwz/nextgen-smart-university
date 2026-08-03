<?php

declare(strict_types=1);

namespace App\Models;

class ReportHistory extends Model
{
    protected string $table = 'ReportHistory';

    protected string $defaultOrder = 'generated_at DESC';

    public function record(
        int $userId,
        string $reportKey,
        string $format,
        array $parameters,
        int $rowCount
    ): int {
        return $this->create([
            'user_id' => $userId,
            'report_key' => $reportKey,
            'export_format' => $format,
            'parameters' => $parameters === []
                ? null
                : substr((string) json_encode($parameters), 0, 500),
            'row_count' => $rowCount,
            'generated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function recent(int $limit = 100): array
    {
        $statement = $this->db->prepare(
            'SELECT h.*, u.full_name
             FROM ReportHistory h
             JOIN User u ON u.id = h.user_id
             ORDER BY h.generated_at DESC
             LIMIT :row_limit'
        );

        $statement->bindValue('row_limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
