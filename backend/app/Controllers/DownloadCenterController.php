<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\DownloadCenterService;
use App\Validation\DownloadValidator;

class DownloadCenterController extends Controller
{
    public function __construct(
        private readonly DownloadCenterService $downloads = new DownloadCenterService(),
        private readonly DownloadValidator $validator = new DownloadValidator()
    ) {
        parent::__construct();
    }

    public function files(): void
    {
        $user = $this->authenticate();

        Response::success('Files retrieved.', [
            'files' => $this->downloads->files($user, $this->queryString('category')),
        ]);
    }

    public function file(string $id): void
    {
        $user = $this->authenticate();

        $file = $this->run(fn () => $this->downloads->file((int) $id, $user));

        Response::success('File retrieved.', ['file' => $file]);
    }

    public function store(): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::formOrBody();
        $errors = $this->validator->file($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $upload = $_FILES['file'] ?? null;

        if ($upload === null || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            Response::validationError(['file' => ['A file is required.']]);
        }

        $file = $this->run(fn () => $this->downloads->upload($user, $data, $upload));

        Response::success('File uploaded.', ['file' => $file], 201);
    }

    public function update(string $id): void
    {
        $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->file($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $file = $this->run(fn () => $this->downloads->update((int) $id, $data));

        Response::success('File updated.', ['file' => $file]);
    }

    public function destroy(string $id): void
    {
        $this->authenticateAsAdministrator();

        $this->run(fn () => $this->downloads->delete((int) $id));

        Response::success('File deleted.');
    }

    public function download(string $id): void
    {
        $user = $this->authenticate();

        $file = $this->run(
            fn () => $this->downloads->prepareDownload((int) $id, $user, Request::ipAddress())
        );

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
        header('Content-Length: ' . filesize($file['path']));
        header('X-Content-Type-Options: nosniff');

        readfile($file['path']);

        exit;
    }

    public function history(): void
    {
        $user = $this->authenticate();

        Response::success('Download history retrieved.', [
            'history' => $this->downloads->history($user),
        ]);
    }

    public function transcript(): void
    {
        $user = $this->authenticate();

        $export = $this->run(fn () => $this->downloads->transcriptDocument(
            $user,
            $this->queryInt('student_id'),
            $this->format(),
        ));

        $this->stream($export);
    }

    public function schedule(): void
    {
        $user = $this->authenticateAs(['Student']);

        $export = $this->run(fn () => $this->downloads->scheduleDocument($user, $this->format()));

        $this->stream($export);
    }

    public function invoice(string $id): void
    {
        $user = $this->authenticate();

        $export = $this->run(
            fn () => $this->downloads->invoiceDocument((int) $id, $user, $this->format())
        );

        $this->stream($export);
    }

    private function format(): string
    {
        return $this->queryString('format') ?? 'pdf';
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
