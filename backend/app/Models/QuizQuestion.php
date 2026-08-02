<?php

declare(strict_types=1);

namespace App\Models;

class QuizQuestion extends Model
{
    protected string $table = 'QuizQuestion';

    protected string $defaultOrder = 'position';

    public function forQuiz(int $quizId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM QuizQuestion WHERE quiz_id = :quiz_id ORDER BY position, id'
        );

        $statement->execute(['quiz_id' => $quizId]);

        $questions = $statement->fetchAll();

        if ($questions === []) {
            return [];
        }

        $options = $this->optionsForQuiz($quizId);

        foreach ($questions as $index => $question) {
            $questions[$index]['options'] = $options[(int) $question['id']] ?? [];
        }

        return $questions;
    }

    public function deleteForQuiz(int $quizId): bool
    {
        $statement = $this->db->prepare('DELETE FROM QuizQuestion WHERE quiz_id = :quiz_id');

        return $statement->execute(['quiz_id' => $quizId]);
    }

    public function addOption(int $questionId, string $label, string $text, int $position): bool
    {
        $statement = $this->db->prepare(
            'INSERT INTO QuizOption (question_id, option_label, option_text, position)
             VALUES (:question_id, :option_label, :option_text, :position)'
        );

        return $statement->execute([
            'question_id' => $questionId,
            'option_label' => $label,
            'option_text' => $text,
            'position' => $position,
        ]);
    }

    private function optionsForQuiz(int $quizId): array
    {
        $statement = $this->db->prepare(
            'SELECT o.question_id, o.option_label, o.option_text
             FROM QuizOption o
             JOIN QuizQuestion q ON q.id = o.question_id
             WHERE q.quiz_id = :quiz_id
             ORDER BY o.position, o.id'
        );

        $statement->execute(['quiz_id' => $quizId]);

        $grouped = [];

        foreach ($statement->fetchAll() as $row) {
            $grouped[(int) $row['question_id']][] = [
                'label' => $row['option_label'],
                'text' => $row['option_text'],
            ];
        }

        return $grouped;
    }
}
