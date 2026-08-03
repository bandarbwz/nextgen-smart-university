<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\DownloadCenterService;
use App\Services\ReportExporter;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports = new ReportService(),
        private readonly DownloadCenterService $downloads = new DownloadCenterService()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $user = $this->authenticate();

        Response::success('Available reports retrieved.', [
            'reports' => $this->reports->available($user),
        ]);
    }

    public function generate(string $category, string $name): void
    {
        $user = $this->authenticate();

        $report = $this->run(
            fn () => $this->reports->run($category . '.' . $name, $user, $_GET)
        );

        Response::success('Report generated.', ['report' => $report]);
    }

    public function export(): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $reportKey = (string) ($data['report'] ?? '');
        $format = (string) ($data['format'] ?? 'csv');

        if ($reportKey === '') {
            Response::validationError(['report' => ['A report key is required.']]);
        }

        if (!in_array($format, ReportExporter::FORMATS, true)) {
            Response::validationError([
                'format' => ['The format must be one of: ' . implode(', ', ReportExporter::FORMATS) . '.'],
            ]);
        }

        $parameters = is_array($data['parameters'] ?? null) ? $data['parameters'] : [];

        $export = $this->run(
            fn () => $this->downloads->exportReport($reportKey, $user, $parameters, $format)
        );

        $this->stream($export);
    }

    private function stream(array $export): void
    {
        header('Content-Type: ' . $export['mime_type']);
        header('Content-Disposition: attachment; filename="' . $export['file_name'] . '"');
        header('Content-Length: ' . strlen($export['contents']));
        header('X-Content-Type-Options: nosniff');

        echo $export['contents'];

        exit;
    }
}
