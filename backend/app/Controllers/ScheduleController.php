<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\ScheduleService;
use App\Services\StudentService;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleService $schedules = new ScheduleService(),
        private readonly StudentService $students = new StudentService()
    ) {
        parent::__construct();
    }

    public function weekly(): void
    {
        $user = $this->authenticateAs(['Student']);

        $schedule = $this->run(function () use ($user) {
            $student = $this->students->getByUserId($user['user_id']);

            return $this->schedules->weekly((int) $student['id'], $this->queryInt('semester_id'));
        });

        Response::success('Schedule retrieved successfully.', ['schedule' => $schedule]);
    }

    public function daily(): void
    {
        $user = $this->authenticateAs(['Student']);

        $day = $this->queryString('day') ?? gmdate('l');

        $schedule = $this->run(function () use ($user, $day) {
            $student = $this->students->getByUserId($user['user_id']);

            return $this->schedules->daily((int) $student['id'], $day);
        });

        Response::success('Daily schedule retrieved successfully.', [
            'day' => $day,
            'schedule' => $schedule,
        ]);
    }
}
