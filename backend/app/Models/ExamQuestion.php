<?php

declare(strict_types=1);

namespace App\Models;

class ExamQuestion extends Model
{
    protected string $table = 'ExamQuestion';

    protected string $defaultOrder = 'position, id';

    public function forExam(int $examId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM ExamQuestion WHERE exam_id = :exam_id ORDER BY position, id'
        );

        $statement->execute(['exam_id' => $examId]);

        return array_map(
            static function (array $question): array {
                $question['options'] = $question['options'] === null
                    ? []
                    : (json_decode((string) $question['options'], true) ?: []);

                return $question;
            },
            $statement->fetchAll()
        );
    }

    public function deleteForExam(int $examId): bool
    {
        return $this->db
            ->prepare('DELETE FROM ExamQuestion WHERE exam_id = :exam_id')
            ->execute(['exam_id' => $examId]);
    }
}
