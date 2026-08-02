<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUpload;
use App\Models\CourseMaterial;
use App\Models\Section;

class MaterialService
{
    public function __construct(
        private readonly CourseMaterial $materials = new CourseMaterial(),
        private readonly Section $sections = new Section(),
        private readonly CourseAccessService $access = new CourseAccessService()
    ) {
    }

    public function list(array $user, ?int $sectionId): array
    {
        $isStaff = $user['role'] !== 'Student';

        if ($sectionId !== null) {
            $this->access->guardSectionVisible($sectionId, $user);

            return $this->materials->forSection($sectionId, $isStaff);
        }

        return $this->materials->forSections($this->access->visibleSectionIds($user), $isStaff);
    }

    public function get(int $id, array $user): array
    {
        $material = $this->materials->find($id);

        if ($material === null) {
            throw new ApiException('Material not found.', 404);
        }

        $this->access->guardSectionVisible((int) $material['section_id'], $user);

        if ($material['visibility'] === 'hidden' && $user['role'] === 'Student') {
            throw new ApiException('Material not found.', 404);
        }

        return $material;
    }

    public function upload(array $user, array $fields, array $file): array
    {
        $sectionId = (int) $fields['section_id'];
        $lecturerId = $this->access->guardSectionOwned($sectionId, $user);

        $section = $this->sections->find($sectionId);
        $stored = FileUpload::store($file, 'materials', FileUpload::PROFILE_COURSE_FILE);

        $id = $this->materials->create([
            'course_id' => (int) $section['course_id'],
            'section_id' => $sectionId,
            'lecturer_id' => $lecturerId,
            'title' => $fields['title'],
            'description' => $fields['description'] ?? null,
            'file_path' => $stored,
            'file_type' => pathinfo($stored, PATHINFO_EXTENSION),
            'file_size' => (int) $file['size'],
            'original_name' => substr((string) $file['name'], 0, 255),
            'visibility' => $fields['visibility'] ?? 'visible',
            'upload_date' => gmdate('Y-m-d H:i:s'),
        ]);

        return $this->materials->find($id);
    }

    public function update(int $id, array $user, array $fields): array
    {
        $material = $this->materials->find($id);

        if ($material === null) {
            throw new ApiException('Material not found.', 404);
        }

        $this->access->guardSectionOwned((int) $material['section_id'], $user);

        $this->materials->update($id, [
            'title' => $fields['title'],
            'description' => $fields['description'] ?? null,
            'visibility' => $fields['visibility'] ?? $material['visibility'],
        ]);

        return $this->materials->find($id);
    }

    public function delete(int $id, array $user): void
    {
        $material = $this->materials->find($id);

        if ($material === null) {
            throw new ApiException('Material not found.', 404);
        }

        $this->access->guardSectionOwned((int) $material['section_id'], $user);
        $this->materials->delete($id);
    }

    public function pathForDownload(int $id, array $user): array
    {
        $material = $this->get($id, $user);
        $absolute = FileUpload::absolutePath($material['file_path']);

        if (!is_readable($absolute)) {
            throw new ApiException('The stored file is no longer available.', 404);
        }

        return [
            'path' => $absolute,
            'name' => $material['original_name'],
        ];
    }
}
