<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Models\CalendarEvent;
use App\Models\Semester;
use PDO;

class CalendarSyncService
{
    private const DAY_NUMBERS = [
        'Sunday' => 0,
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
    ];

    public function __construct(
        private readonly CalendarEvent $events = new CalendarEvent(),
        private readonly Semester $semesters = new Semester(),
        private readonly CourseAccessService $access = new CourseAccessService()
    ) {
    }

    public function synchronise(array $user): array
    {
        $semester = $this->semesters->current();

        if ($semester === null) {
            throw new ApiException('No current semester has been set.', 404);
        }

        $sectionIds = $this->access->visibleSectionIds($user);

        if ($sectionIds === []) {
            return ['classes' => 0, 'assignments' => 0, 'quizzes' => 0];
        }

        return [
            'classes' => $this->syncClasses($user['user_id'], $sectionIds, $semester),
            'assignments' => $this->syncAssignments($user['user_id'], $sectionIds),
            'quizzes' => $this->syncQuizzes($user['user_id'], $sectionIds),
        ];
    }

    private function syncClasses(int $userId, array $sectionIds, array $semester): int
    {
        $rows = $this->fetch(
            'SELECT cs.id AS schedule_id, cs.day_of_week, cs.start_time, cs.end_time, cs.room,
                    c.course_code, c.course_name, s.section_number, s.building
             FROM ClassSchedule cs
             JOIN Section s ON s.id = cs.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE cs.section_id IN (' . $this->placeholders($sectionIds) . ')',
            $sectionIds
        );

        if ($rows === []) {
            return 0;
        }

        $start = strtotime($semester['start_date']);
        $end = strtotime($semester['end_date']);
        $created = 0;

        foreach ($rows as $row) {
            $dayNumber = self::DAY_NUMBERS[$row['day_of_week']];

            for ($day = $start; $day <= $end; $day += 86400) {
                if ((int) gmdate('w', $day) !== $dayNumber) {
                    continue;
                }

                $date = gmdate('Y-m-d', $day);

                $this->events->upsertGenerated([
                    'user_id' => $userId,
                    'title' => $row['course_code'] . ' ' . $row['course_name'],
                    'description' => 'Section ' . $row['section_number'],
                    'event_type' => 'Class',
                    'module' => 'Academic',
                    'reference_id' => (int) $row['schedule_id'],
                    'start_datetime' => $date . ' ' . $row['start_time'],
                    'end_datetime' => $date . ' ' . $row['end_time'],
                    'location' => trim(($row['building'] ?? '') . ' ' . ($row['room'] ?? '')) ?: null,
                    'color' => '#2563eb',
                    'is_all_day' => false,
                    'reminder_enabled' => false,
                    'status' => 'active',
                ]);

                $created++;
            }
        }

        return $created;
    }

    private function syncAssignments(int $userId, array $sectionIds): int
    {
        $rows = $this->fetch(
            'SELECT a.id, a.title, a.due_date, c.course_code
             FROM Assignment a
             JOIN Section s ON s.id = a.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE a.section_id IN (' . $this->placeholders($sectionIds) . ') AND a.deleted_at IS NULL',
            $sectionIds
        );

        foreach ($rows as $row) {
            $this->events->upsertGenerated([
                'user_id' => $userId,
                'title' => $row['course_code'] . ' due: ' . $row['title'],
                'description' => 'Assignment deadline',
                'event_type' => 'Assignment',
                'module' => 'LMS',
                'reference_id' => (int) $row['id'],
                'start_datetime' => $row['due_date'],
                'end_datetime' => $row['due_date'],
                'location' => null,
                'color' => '#b45309',
                'is_all_day' => false,
                'reminder_enabled' => false,
                'status' => 'active',
            ]);
        }

        return count($rows);
    }

    private function syncQuizzes(int $userId, array $sectionIds): int
    {
        $rows = $this->fetch(
            'SELECT q.id, q.title, q.start_time, q.end_time, c.course_code
             FROM Quiz q
             JOIN Section s ON s.id = q.section_id
             JOIN Course c ON c.id = s.course_id
             WHERE q.section_id IN (' . $this->placeholders($sectionIds) . ') AND q.deleted_at IS NULL',
            $sectionIds
        );

        foreach ($rows as $row) {
            $this->events->upsertGenerated([
                'user_id' => $userId,
                'title' => $row['course_code'] . ' quiz: ' . $row['title'],
                'description' => 'Quiz availability window',
                'event_type' => 'Quiz',
                'module' => 'LMS',
                'reference_id' => (int) $row['id'],
                'start_datetime' => $row['start_time'],
                'end_datetime' => $row['end_time'],
                'location' => null,
                'color' => '#7c3aed',
                'is_all_day' => false,
                'reminder_enabled' => false,
                'status' => 'active',
            ]);
        }

        return count($rows);
    }

    private function fetch(string $sql, array $parameters): array
    {
        $statement = Database::connection()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function placeholders(array $values): string
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }
}
