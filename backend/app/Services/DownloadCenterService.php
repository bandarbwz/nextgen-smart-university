<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUpload;
use App\Models\DownloadFile;
use App\Models\DownloadHistory;

class DownloadCenterService
{
    private const VISIBILITY_BY_ROLE = [
        'Student' => ['all', 'students'],
        'Lecturer' => ['all', 'staff'],
        'Coordinator' => ['all', 'staff'],
        'STAD Staff' => ['all', 'staff'],
        'Restaurant Owner' => ['all'],
        'Administrator' => ['all', 'students', 'staff', 'administrators'],
    ];

    public function __construct(
        private readonly DownloadFile $files = new DownloadFile(),
        private readonly DownloadHistory $history = new DownloadHistory(),
        private readonly ReportService $reports = new ReportService(),
        private readonly ReportExporter $exporter = new ReportExporter(),
        private readonly TranscriptService $transcripts = new TranscriptService(),
        private readonly ScheduleService $schedules = new ScheduleService(),
        private readonly StudentService $students = new StudentService(),
        private readonly FinanceService $finance = new FinanceService()
    ) {
    }

    public function files(array $user, ?string $category): array
    {
        return $this->files->visibleTo($this->visibilitiesFor($user['role']), $category);
    }

    public function file(int $id, array $user): array
    {
        $file = $this->files->find($id);

        if ($file === null) {
            throw new ApiException('File not found.', 404);
        }

        if (!in_array($file['visibility'], $this->visibilitiesFor($user['role']), true)) {
            throw new ApiException('File not found.', 404);
        }

        return $file;
    }

    public function upload(array $user, array $fields, array $file): array
    {
        $stored = FileUpload::store($file, 'downloads', FileUpload::PROFILE_COURSE_FILE);

        $id = $this->files->create([
            'title' => $fields['title'],
            'description' => $fields['description'] ?? null,
            'category' => $fields['category'],
            'file_name' => substr((string) $file['name'], 0, 255),
            'file_path' => $stored,
            'file_size' => (int) $file['size'],
            'file_type' => pathinfo($stored, PATHINFO_EXTENSION),
            'uploaded_by' => $user['user_id'],
            'visibility' => $fields['visibility'] ?? 'all',
        ]);

        return $this->files->find($id);
    }

    public function update(int $id, array $fields): array
    {
        if (!$this->files->exists($id)) {
            throw new ApiException('File not found.', 404);
        }

        $this->files->update($id, [
            'title' => $fields['title'],
            'description' => $fields['description'] ?? null,
            'category' => $fields['category'],
            'visibility' => $fields['visibility'] ?? 'all',
        ]);

        return $this->files->find($id);
    }

    public function delete(int $id): void
    {
        if (!$this->files->exists($id)) {
            throw new ApiException('File not found.', 404);
        }

        $this->files->delete($id);
    }

    /**
     * Records the download before returning the path, so the audit trail exists
     * even if delivery is interrupted.
     */
    public function prepareDownload(int $id, array $user, string $ipAddress): array
    {
        $file = $this->file($id, $user);
        $absolute = FileUpload::absolutePath($file['file_path']);

        if (!is_readable($absolute)) {
            throw new ApiException('The stored file is no longer available.', 404);
        }

        $this->history->record((int) $file['id'], $file['title'], $user['user_id'], $ipAddress);
        $this->files->incrementDownloadCount((int) $file['id']);

        return [
            'path' => $absolute,
            'name' => $file['file_name'],
        ];
    }

    public function history(array $user): array
    {
        return $user['role'] === 'Administrator'
            ? $this->history->all()
            : $this->history->forUser($user['user_id']);
    }

    public function exportReport(string $reportKey, array $user, array $parameters, string $format): array
    {
        $report = $this->reports->run($reportKey, $user, $parameters, $format);
        $export = $this->exporter->export($report, $format);

        $this->history->record(null, $report['title'], $user['user_id'], 'export');

        return $export;
    }

    public function transcriptDocument(array $user, ?int $studentId, string $format): array
    {
        $resolved = $this->resolveStudentId($user, $studentId);

        $report = $this->reports->run('academic.transcript', $user, ['student_id' => $resolved], $format);

        return $this->exporter->export($report, $format);
    }

    public function scheduleDocument(array $user, string $format): array
    {
        $studentId = (int) $this->students->getByUserId($user['user_id'])['id'];
        $week = $this->schedules->weekly($studentId, null);

        $rows = [];

        foreach ($week as $day => $slots) {
            foreach ($slots as $slot) {
                $rows[] = [
                    'day' => $day,
                    'course_code' => $slot['course_code'],
                    'course_name' => $slot['course_name'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'room' => trim(($slot['building'] ?? '') . ' ' . ($slot['room'] ?? '')),
                ];
            }
        }

        return $this->exporter->export([
            'title' => 'Class Schedule',
            'columns' => ['day', 'course_code', 'course_name', 'start_time', 'end_time', 'room'],
            'rows' => $rows,
            'generated_at' => gmdate('Y-m-d H:i:s'),
        ], $format);
    }

    public function invoiceDocument(int $invoiceId, array $user, string $format): array
    {
        $invoice = $this->finance->invoice($invoiceId, $user);

        $rows = [];

        foreach ($invoice['payments'] as $payment) {
            $rows[] = [
                'payment_reference' => $payment['payment_reference'],
                'payment_method' => $payment['payment_method'],
                'amount' => $payment['amount'],
                'payment_date' => $payment['payment_date'],
            ];
        }

        return $this->exporter->export([
            'title' => 'Invoice ' . $invoice['invoice_number'],
            'columns' => ['payment_reference', 'payment_method', 'amount', 'payment_date'],
            'rows' => $rows,
            'generated_at' => gmdate('Y-m-d H:i:s'),
        ], $format);
    }

    private function resolveStudentId(array $user, ?int $studentId): int
    {
        if ($user['role'] === 'Student') {
            return (int) $this->students->getByUserId($user['user_id'])['id'];
        }

        if ($studentId === null) {
            throw new ApiException('A student_id parameter is required.', 422);
        }

        return $studentId;
    }

    private function visibilitiesFor(string $role): array
    {
        return self::VISIBILITY_BY_ROLE[$role] ?? ['all'];
    }
}
