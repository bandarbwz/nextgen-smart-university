<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use App\Models\ChatMember;
use App\Models\ChatRoom;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Student;
use Throwable;

class CourseChatProvisioner
{
    public function __construct(
        private readonly ChatRoom $rooms = new ChatRoom(),
        private readonly ChatMember $members = new ChatMember(),
        private readonly Section $sections = new Section(),
        private readonly Student $students = new Student(),
        private readonly Lecturer $lecturers = new Lecturer()
    ) {
    }

    public function ensureRoomForSection(int $sectionId, int $createdByUserId): ?int
    {
        $existing = $this->rooms->findBySection($sectionId);

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $section = $this->sections->findDetailed($sectionId);

        if ($section === null) {
            return null;
        }

        $roomId = $this->rooms->create([
            'room_name' => $section['course_code'] . ' - ' . $section['section_number'],
            'room_type' => 'Course',
            'course_id' => (int) $section['course_id'],
            'section_id' => $sectionId,
            'created_by' => $createdByUserId,
        ]);

        $lecturer = $this->lecturers->find((int) $section['lecturer_id']);

        if ($lecturer !== null) {
            $this->members->join($roomId, (int) $lecturer['user_id'], 'Lecturer');
        }

        return $roomId;
    }

    public function addStudent(int $sectionId, int $studentId): void
    {
        $this->safely(function () use ($sectionId, $studentId): void {
            $room = $this->rooms->findBySection($sectionId);

            if ($room === null) {
                return;
            }

            $student = $this->students->find($studentId);

            if ($student === null) {
                return;
            }

            $this->members->join((int) $room['id'], (int) $student['user_id'], 'Student');
        });
    }

    public function removeStudent(int $sectionId, int $studentId): void
    {
        $this->safely(function () use ($sectionId, $studentId): void {
            $room = $this->rooms->findBySection($sectionId);

            if ($room === null) {
                return;
            }

            $student = $this->students->find($studentId);

            if ($student === null) {
                return;
            }

            $this->members->leave((int) $room['id'], (int) $student['user_id']);
        });
    }

    private function safely(callable $action): void
    {
        try {
            $action();
        } catch (Throwable $exception) {
            Logger::error('Course chat membership update failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
