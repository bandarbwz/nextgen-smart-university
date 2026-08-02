<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUpload;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Section;

class AssignmentService
{
    public function __construct(
        private readonly Assignment $assignments = new Assignment(),
        private readonly AssignmentSubmission $submissions = new AssignmentSubmission(),
        private readonly Section $sections = new Section(),
        private readonly CourseAccessService $access = new CourseAccessService()
    ) {
    }

    public function list(array $user, ?int $sectionId): array
    {
        $sectionIds = $sectionId === null
            ? $this->access->visibleSectionIds($user)
            : [$this->visibleSection($sectionId, $user)];

        $assignments = $this->assignments->forSections($sectionIds);

        if ($user['role'] !== 'Student') {
            foreach ($assignments as $index => $assignment) {
                $assignments[$index]['summary'] = $this->assignments->submissionSummary(
                    (int) $assignment['id']
                );
            }

            return $assignments;
        }

        $studentId = $this->access->requireStudentId($user['user_id']);

        foreach ($assignments as $index => $assignment) {
            $assignments[$index]['my_submission'] = $this->submissions->findForStudent(
                (int) $assignment['id'],
                $studentId
            );
        }

        return $assignments;
    }

    public function get(int $id, array $user): array
    {
        $assignment = $this->assignments->findDetailed($id);

        if ($assignment === null) {
            throw new ApiException('Assignment not found.', 404);
        }

        $this->access->guardSectionVisible((int) $assignment['section_id'], $user);

        if ($user['role'] === 'Student') {
            $assignment['my_submission'] = $this->submissions->findForStudent(
                $id,
                $this->access->requireStudentId($user['user_id'])
            );

            return $assignment;
        }

        $assignment['submissions'] = $this->submissions->forAssignment($id);

        return $assignment;
    }

    public function create(array $user, array $fields): array
    {
        $sectionId = (int) $fields['section_id'];

        $this->access->guardSectionOwned($sectionId, $user);

        $section = $this->sections->find($sectionId);

        $id = $this->assignments->create([
            'course_id' => (int) $section['course_id'],
            'section_id' => $sectionId,
            'title' => $fields['title'],
            'description' => $fields['description'] ?? null,
            'total_marks' => (float) $fields['total_marks'],
            'due_date' => $fields['due_date'],
            'allow_late_submission' => (bool) ($fields['allow_late_submission'] ?? false),
            'created_by' => $user['user_id'],
        ]);

        return $this->assignments->findDetailed($id);
    }

    public function update(int $id, array $user, array $fields): array
    {
        $assignment = $this->requireAssignment($id);

        $this->access->guardSectionOwned((int) $assignment['section_id'], $user);

        $this->assignments->update($id, [
            'title' => $fields['title'],
            'description' => $fields['description'] ?? null,
            'total_marks' => (float) $fields['total_marks'],
            'due_date' => $fields['due_date'],
            'allow_late_submission' => (bool) ($fields['allow_late_submission'] ?? false),
        ]);

        return $this->assignments->findDetailed($id);
    }

    public function delete(int $id, array $user): void
    {
        $assignment = $this->requireAssignment($id);

        $this->access->guardSectionOwned((int) $assignment['section_id'], $user);
        $this->assignments->delete($id);
    }

    public function submit(int $assignmentId, array $user, ?array $file, ?string $comment): array
    {
        $assignment = $this->requireAssignment($assignmentId);
        $studentId = $this->access->requireStudentId($user['user_id']);

        $this->access->guardSectionVisible((int) $assignment['section_id'], $user);

        if ($this->submissions->findForStudent($assignmentId, $studentId) !== null) {
            throw new ApiException('You have already submitted this assignment.', 409);
        }

        $isLate = time() > strtotime($assignment['due_date'] . ' UTC');

        if ($isLate && !(bool) $assignment['allow_late_submission']) {
            throw new ApiException('The deadline for this assignment has passed.', 409);
        }

        if ($file === null && ($comment === null || $comment === '')) {
            throw new ApiException('Attach a file or write a comment to submit.', 422);
        }

        $storedPath = null;
        $originalName = null;

        if ($file !== null) {
            $storedPath = FileUpload::store($file, 'submissions', FileUpload::PROFILE_COURSE_FILE);
            $originalName = substr((string) $file['name'], 0, 255);
        }

        $id = $this->submissions->create([
            'assignment_id' => $assignmentId,
            'student_id' => $studentId,
            'file_path' => $storedPath,
            'original_name' => $originalName,
            'comment' => $comment,
            'submitted_at' => gmdate('Y-m-d H:i:s'),
            'submission_status' => $isLate ? 'Late' : 'Submitted',
        ]);

        return $this->submissions->find($id);
    }

    public function viewSubmission(int $id, array $user): array
    {
        $submission = $this->submissions->findDetailed($id);

        if ($submission === null) {
            throw new ApiException('Submission not found.', 404);
        }

        if ($user['role'] === 'Student') {
            $studentId = $this->access->requireStudentId($user['user_id']);

            if ((int) $submission['student_id'] !== $studentId) {
                throw new ApiException('Submission not found.', 404);
            }

            return $submission;
        }

        $this->access->guardSectionVisible((int) $submission['section_id'], $user);

        return $submission;
    }

    public function grade(int $id, array $user, float $marks, ?string $feedback): array
    {
        $submission = $this->submissions->findDetailed($id);

        if ($submission === null) {
            throw new ApiException('Submission not found.', 404);
        }

        $this->access->guardSectionOwned((int) $submission['section_id'], $user);

        if ($marks > (float) $submission['total_marks']) {
            throw new ApiException(
                'The marks cannot exceed the assignment total of ' . $submission['total_marks'] . '.',
                422
            );
        }

        $this->submissions->grade($id, $marks, $feedback, $user['user_id']);

        return $this->submissions->findDetailed($id);
    }

    public function submissionsForStudent(int $studentId): array
    {
        return $this->submissions->forStudent($studentId);
    }

    private function requireAssignment(int $id): array
    {
        $assignment = $this->assignments->findDetailed($id);

        if ($assignment === null) {
            throw new ApiException('Assignment not found.', 404);
        }

        return $assignment;
    }

    private function visibleSection(int $sectionId, array $user): int
    {
        $this->access->guardSectionVisible($sectionId, $user);

        return $sectionId;
    }
}
