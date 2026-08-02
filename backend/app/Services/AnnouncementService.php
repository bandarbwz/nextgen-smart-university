<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Announcement;

class AnnouncementService
{
    public function __construct(
        private readonly Announcement $announcements = new Announcement(),
        private readonly CourseAccessService $access = new CourseAccessService()
    ) {
    }

    public function list(array $user, ?int $sectionId): array
    {
        if ($sectionId !== null) {
            $this->access->guardSectionVisible($sectionId, $user);

            return $this->announcements->forSections([$sectionId]);
        }

        return $this->announcements->forSections($this->access->visibleSectionIds($user));
    }

    public function create(array $user, array $fields): array
    {
        $sectionId = (int) $fields['section_id'];
        $lecturerId = $this->access->guardSectionOwned($sectionId, $user);

        $id = $this->announcements->create([
            'section_id' => $sectionId,
            'lecturer_id' => $lecturerId,
            'title' => $fields['title'],
            'content' => $fields['content'],
            'published_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return $this->announcements->find($id);
    }

    public function update(int $id, array $user, array $fields): array
    {
        $announcement = $this->require($id);

        $this->access->guardSectionOwned((int) $announcement['section_id'], $user);

        $this->announcements->update($id, [
            'title' => $fields['title'],
            'content' => $fields['content'],
        ]);

        return $this->announcements->find($id);
    }

    public function delete(int $id, array $user): void
    {
        $announcement = $this->require($id);

        $this->access->guardSectionOwned((int) $announcement['section_id'], $user);
        $this->announcements->delete($id);
    }

    private function require(int $id): array
    {
        $announcement = $this->announcements->find($id);

        if ($announcement === null) {
            throw new ApiException('Announcement not found.', 404);
        }

        return $announcement;
    }
}
