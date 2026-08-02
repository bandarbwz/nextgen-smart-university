<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUpload;
use App\Models\Attendance;
use App\Models\AttendanceExcuse;
use App\Models\Lecturer;

class ExcuseService
{
    public function __construct(
        private readonly AttendanceExcuse $excuses = new AttendanceExcuse(),
        private readonly Attendance $attendance = new Attendance(),
        private readonly Lecturer $lecturers = new Lecturer()
    ) {
    }

    public function submit(int $studentId, array $fields, ?array $document): array
    {
        $record = $this->attendance->find((int) $fields['attendance_id']);

        if ($record === null || (int) $record['student_id'] !== $studentId) {
            throw new ApiException('Attendance record not found.', 404);
        }

        if ($this->excuses->existsForAttendance((int) $record['id'])) {
            throw new ApiException('An excuse has already been submitted for this session.', 409);
        }

        $documentPath = null;

        if ($document !== null) {
            $documentPath = FileUpload::store($document, 'excuses');
        }

        $id = $this->excuses->create([
            'student_id' => $studentId,
            'attendance_id' => (int) $record['id'],
            'excuse_type' => $fields['excuse_type'],
            'reason' => $fields['reason'],
            'document_path' => $documentPath,
            'status' => 'Pending',
        ]);

        return $this->excuses->find($id);
    }

    public function forStudent(int $studentId): array
    {
        return $this->excuses->forStudent($studentId);
    }

    public function forReviewer(int $userId, string $role, ?string $status): array
    {
        if ($role === 'Administrator') {
            return $this->excuses->all();
        }

        $lecturer = $this->lecturers->findByUserId($userId);

        if ($lecturer === null) {
            throw new ApiException('No lecturer record is linked to this account.', 404);
        }

        return $this->excuses->forLecturer((int) $lecturer['id'], $status);
    }

    public function review(int $id, int $reviewerUserId, string $role, string $status, ?string $note): array
    {
        $excuse = $this->excuses->findDetailed($id);

        if ($excuse === null) {
            throw new ApiException('Excuse not found.', 404);
        }

        if ($excuse['status'] !== 'Pending') {
            throw new ApiException('This excuse has already been reviewed.', 409);
        }

        $this->guardReviewer($excuse, $reviewerUserId, $role);

        $this->excuses->review($id, $status, $reviewerUserId, $note);

        if ($status === 'Approved') {
            $this->attendance->updateStatus(
                (int) $excuse['attendance_id'],
                'Excused',
                $reviewerUserId,
                'Excuse approved'
            );
        }

        return $this->excuses->find($id);
    }

    private function guardReviewer(array $excuse, int $userId, string $role): void
    {
        if ($role === 'Administrator') {
            return;
        }

        $lecturer = $this->lecturers->findByUserId($userId);

        if ($lecturer === null || (int) $excuse['lecturer_id'] !== (int) $lecturer['id']) {
            throw new ApiException('You can only review excuses for your own sections.', 403);
        }
    }
}
