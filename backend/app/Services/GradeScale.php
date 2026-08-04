<?php

declare(strict_types=1);

namespace App\Services;

/**
 * The university letter scale, in one place. It lived privately inside
 * GradeService until the Assessment module needed the same thresholds, and two
 * copies of a grading scale is exactly the kind of thing that silently drifts.
 */
class GradeScale
{
    private const LETTERS = [
        [90, 'A', 4.00],
        [85, 'A-', 3.70],
        [80, 'B+', 3.30],
        [75, 'B', 3.00],
        [70, 'B-', 2.70],
        [65, 'C+', 2.30],
        [60, 'C', 2.00],
        [55, 'C-', 1.70],
        [50, 'D', 1.00],
    ];

    /**
     * @return array{0: string, 1: float}
     */
    public static function forPercentage(float $percentage): array
    {
        foreach (self::LETTERS as [$threshold, $letter, $points]) {
            if ($percentage >= $threshold) {
                return [$letter, $points];
            }
        }

        return ['F', 0.00];
    }

    /**
     * @return array{0: string, 1: float}
     */
    public static function forMarks(float $marks, float $totalMarks): array
    {
        return self::forPercentage($totalMarks <= 0 ? 0 : $marks / $totalMarks * 100);
    }
}
