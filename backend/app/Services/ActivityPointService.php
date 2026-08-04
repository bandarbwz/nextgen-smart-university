<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityPoint;
use App\Models\EventAttendance;
use App\Models\EventRegistration;

class ActivityPointService
{
    public function __construct(
        private readonly ActivityPoint $points = new ActivityPoint(),
        private readonly EventRegistration $registrations = new EventRegistration(),
        private readonly EventAttendance $attendance = new EventAttendance(),
        private readonly EventService $events = new EventService()
    ) {
    }

    public function mine(array $user): array
    {
        return $this->forStudent($this->events->requireStudentId($user['user_id']));
    }

    public function forStudent(int $studentId): array
    {
        return [
            'total_points' => $this->points->totalForStudent($studentId),
            'points' => $this->points->forStudent($studentId),
        ];
    }

    public function guardVisible(int $studentId, array $user): void
    {
        if ($user['role'] !== 'Student') {
            return;
        }

        if ($this->events->requireStudentId($user['user_id']) !== $studentId) {
            throw new ApiException('You can only view your own activity points.', 403);
        }
    }

    /**
     * Points follow verified attendance. Awarding without it would let a
     * student collect points for an event they registered for and skipped.
     */
    public function award(array $user, int $studentId, int $eventId, int $points): array
    {
        $this->events->requireEvent($eventId);

        if ($points <= 0) {
            throw new ApiException('The points must be greater than zero.', 422);
        }

        $registration = $this->registrations->approvedForStudentAndEvent($eventId, $studentId);

        if ($registration === null) {
            throw new ApiException('This student has no approved registration for the event.', 409);
        }

        if ($this->attendance->findForRegistration((int) $registration['id']) === null) {
            throw new ApiException(
                'This student has no verified attendance for the event.',
                409
            );
        }

        if ($this->points->existsFor($studentId, $eventId)) {
            throw new ApiException('Points have already been awarded for this event.', 409);
        }

        $id = $this->points->create([
            'student_id' => $studentId,
            'event_id' => $eventId,
            'points' => $points,
            'awarded_date' => gmdate('Y-m-d'),
            'awarded_by' => $user['user_id'],
        ]);

        return $this->points->find($id);
    }

    public function leaderboard(): array
    {
        return $this->points->leaderboard();
    }
}
