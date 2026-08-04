<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\ActivityPointService;
use App\Validation\ActivityValidator;

class ActivityPointController extends Controller
{
    public function __construct(
        private readonly ActivityPointService $points = new ActivityPointService(),
        private readonly ActivityValidator $validator = new ActivityValidator()
    ) {
        parent::__construct();
    }

    public function mine(): void
    {
        $user = $this->authenticateAs(['Student']);

        $points = $this->run(fn () => $this->points->mine($user));

        Response::success('Activity points retrieved.', $points);
    }

    public function forStudent(string $studentId): void
    {
        $user = $this->authenticate();

        $points = $this->run(function () use ($studentId, $user): array {
            $this->points->guardVisible((int) $studentId, $user);

            return $this->points->forStudent((int) $studentId);
        });

        Response::success('Activity points retrieved.', $points);
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['STAD Staff']);

        $data = Request::body();
        $errors = $this->validator->award($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $point = $this->run(fn () => $this->points->award(
            $user,
            (int) $data['student_id'],
            (int) $data['event_id'],
            (int) $data['points']
        ));

        Response::success('Activity points awarded.', ['point' => $point], 201);
    }

    public function leaderboard(): void
    {
        $this->authenticateAs(['STAD Staff']);

        $leaderboard = $this->run(fn () => $this->points->leaderboard());

        Response::success('Leaderboard retrieved.', ['leaderboard' => $leaderboard]);
    }
}
