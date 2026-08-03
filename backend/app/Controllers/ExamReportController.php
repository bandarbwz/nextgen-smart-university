<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\ExamReportService;
use App\Validation\ExamValidator;

class ExamReportController extends Controller
{
    public function __construct(
        private readonly ExamReportService $reports = new ExamReportService(),
        private readonly ExamValidator $validator = new ExamValidator()
    ) {
        parent::__construct();
    }

    public function generate(): void
    {
        $user = $this->authenticateAs(['Lecturer', 'Coordinator']);

        $data = Request::body();
        $errors = $this->validator->session($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $report = $this->run(fn () => $this->reports->generate((int) $data['session_id'], $user));

        Response::success('AI report generated.', ['report' => $report], 201);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();

        $report = $this->run(fn () => $this->reports->get((int) $id, $user));

        Response::success('AI report retrieved.', ['report' => $report]);
    }

    public function download(string $id): void
    {
        $user = $this->authenticate();

        $format = $this->queryString('format') ?? 'pdf';

        $export = $this->run(fn () => $this->reports->download((int) $id, $user, $format));

        header('Content-Type: ' . $export['mime_type']);
        header('Content-Disposition: attachment; filename="' . $export['file_name'] . '"');
        header('Content-Length: ' . strlen($export['contents']));
        header('X-Content-Type-Options: nosniff');

        echo $export['contents'];

        exit;
    }
}
