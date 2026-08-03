<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\GpaService;
use App\Services\StudentService;
use App\Services\TranscriptService;

class TranscriptController extends Controller
{
    public function __construct(
        private readonly TranscriptService $transcripts = new TranscriptService(),
        private readonly StudentService $students = new StudentService(),
        private readonly GpaService $gpa = new GpaService()
    ) {
        parent::__construct();
    }

    public function own(): void
    {
        $user = $this->authenticateAs(['Student']);

        $transcript = $this->run(function () use ($user) {
            $student = $this->students->getByUserId($user['user_id']);

            return $this->transcripts->forStudent((int) $student['id']);
        });

        Response::success('Transcript retrieved successfully.', ['transcript' => $transcript]);
    }

    public function forStudent(string $studentId): void
    {
        $this->authenticateAs(['Lecturer', 'Coordinator']);

        $transcript = $this->run(fn () => $this->transcripts->forStudent((int) $studentId));

        Response::success('Transcript retrieved successfully.', ['transcript' => $transcript]);
    }

    public function currentGpa(): void
    {
        $user = $this->authenticateAs(['Student']);

        $gpa = $this->run(function () use ($user) {
            $student = $this->students->getByUserId($user['user_id']);

            return $this->gpa->currentGpa((int) $student['id']);
        });

        Response::success('GPA retrieved successfully.', $gpa);
    }

    public function cumulativeGpa(): void
    {
        $user = $this->authenticateAs(['Student']);

        $cgpa = $this->run(function () use ($user) {
            $student = $this->students->getByUserId($user['user_id']);

            return $this->gpa->cumulativeGpa((int) $student['id']);
        });

        Response::success('CGPA retrieved successfully.', $cgpa);
    }

    public function recalculate(string $studentId): void
    {
        $this->authenticateAs(['Administrator']);

        $result = $this->run(fn () => $this->gpa->recalculate((int) $studentId));

        Response::success('GPA recalculated successfully.', $result);
    }
}
