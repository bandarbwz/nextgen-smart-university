<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\AssessmentResultService;
use App\Services\AssessmentService;
use App\Validation\AssessmentValidator;

class AssessmentController extends Controller
{
    public function __construct(
        private readonly AssessmentService $assessments = new AssessmentService(),
        private readonly AssessmentResultService $results = new AssessmentResultService(),
        private readonly AssessmentValidator $validator = new AssessmentValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $user = $this->authenticate();

        $assessments = $this->run(
            fn () => $this->assessments->list($user, $this->queryInt('section_id'))
        );

        Response::success('Assessments retrieved.', ['assessments' => $assessments]);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();

        $assessment = $this->run(fn () => $this->assessments->get((int) $id, $user));

        Response::success('Assessment retrieved.', ['assessment' => $assessment]);
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $rubric = is_array($data['rubric'] ?? null) ? $data['rubric'] : [];

        $errors = $this->validator->assessment($data) + $this->validator->rubric($rubric);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $assessment = $this->run(fn () => $this->assessments->create($user, $data, $rubric));

        Response::success('Assessment created.', ['assessment' => $assessment], 201);
    }

    public function update(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $rubric = is_array($data['rubric'] ?? null) ? $data['rubric'] : [];

        $errors = $this->validator->assessmentUpdate($data);

        if ($rubric !== []) {
            $errors += $this->validator->rubric($rubric);
        }

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $assessment = $this->run(fn () => $this->assessments->update((int) $id, $user, $data, $rubric));

        Response::success('Assessment updated.', ['assessment' => $assessment]);
    }

    public function destroy(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $this->run(fn () => $this->assessments->delete((int) $id, $user));

        Response::success('Assessment deleted.');
    }

    public function weights(string $sectionId): void
    {
        $user = $this->authenticate();

        $summary = $this->run(fn () => $this->assessments->weightSummary((int) $sectionId, $user));

        Response::success('Assessment weights retrieved.', $summary);
    }

    public function results(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer', 'Coordinator']);

        $results = $this->run(fn () => $this->results->forAssessment((int) $id, $user));

        Response::success('Results retrieved.', ['results' => $results]);
    }

    public function storeResult(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->result($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $result = $this->run(fn () => $this->results->record((int) $id, $user, $data));

        Response::success('Result recorded.', ['result' => $result], 201);
    }

    public function publish(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $outcome = $this->run(fn () => $this->results->publish((int) $id, $user));

        Response::success('Results published.', $outcome);
    }

    public function myResults(): void
    {
        $user = $this->authenticateAs(['Student']);

        $results = $this->run(fn () => $this->results->mine($user));

        Response::success('Results retrieved.', ['results' => $results]);
    }

    public function courseResult(string $sectionId): void
    {
        $user = $this->authenticate();

        $studentId = $this->queryInt('student_id');

        $result = $this->run(function () use ($sectionId, $studentId, $user): array {
            $resolved = $studentId ?? $this->resolveStudentId($user);

            return $this->results->courseResult($resolved, (int) $sectionId, $user);
        });

        Response::success('Course result calculated.', ['result' => $result]);
    }

    private function resolveStudentId(array $user): int
    {
        return (new \App\Services\CourseAccessService())->requireStudentId($user['user_id']);
    }
}
