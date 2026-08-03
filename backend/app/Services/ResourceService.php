<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Resource;

class ResourceService
{
    public function __construct(
        private readonly Resource $resources = new Resource(),
        private readonly CourseAccessService $access = new CourseAccessService()
    ) {
    }

    public function list(array $user, ?int $sectionId): array
    {
        if ($sectionId !== null) {
            $this->access->guardSectionVisible($sectionId, $user);

            return $this->resources->forSections([$sectionId]);
        }

        return $this->resources->forSections($this->access->visibleSectionIds($user));
    }

    public function create(array $user, array $fields): array
    {
        $sectionId = (int) $fields['section_id'];

        $this->access->guardSectionOwned($sectionId, $user);

        $id = $this->resources->create([
            'section_id' => $sectionId,
            'title' => $fields['title'],
            'link' => $fields['link'],
            'resource_type' => $fields['resource_type'],
            'created_by' => $user['user_id'],
        ]);

        return $this->resources->find($id);
    }

    public function delete(int $id, array $user): void
    {
        $resource = $this->resources->find($id);

        if ($resource === null) {
            throw new ApiException('Resource not found.', 404);
        }

        $this->access->guardSectionOwned((int) $resource['section_id'], $user);
        $this->resources->delete($id);
    }
}
