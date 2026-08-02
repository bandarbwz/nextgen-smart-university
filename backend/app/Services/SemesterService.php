<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Semester;

class SemesterService
{
    public function __construct(private readonly Semester $semesters = new Semester())
    {
    }

    public function list(): array
    {
        return $this->semesters->all();
    }

    public function get(int $id): array
    {
        $semester = $this->semesters->find($id);

        if ($semester === null) {
            throw new ApiException('Semester not found.', 404);
        }

        return $semester;
    }

    public function current(): array
    {
        $semester = $this->semesters->current();

        if ($semester === null) {
            throw new ApiException('No current semester has been set.', 404);
        }

        return $semester;
    }

    public function create(array $fields): array
    {
        $this->guardDates($fields);

        $makeCurrent = (bool) ($fields['current_semester'] ?? false);

        if ($makeCurrent) {
            $this->semesters->clearCurrentFlag();
        }

        return $this->get($this->semesters->create($fields));
    }

    public function update(int $id, array $fields): array
    {
        $this->get($id);
        $this->guardDates($fields);

        if ((bool) ($fields['current_semester'] ?? false)) {
            $this->semesters->clearCurrentFlag();
        }

        $this->semesters->update($id, $fields);

        return $this->get($id);
    }

    public function delete(int $id): void
    {
        $this->get($id);

        if ($this->semesters->hasSections($id)) {
            throw new ApiException('This semester still has sections and cannot be deleted.', 409);
        }

        $this->semesters->delete($id);
    }

    private function guardDates(array $fields): void
    {
        if (strtotime($fields['start_date']) >= strtotime($fields['end_date'])) {
            throw new ApiException('The start date must be before the end date.', 422);
        }

        $registrationStart = $fields['registration_start'] ?? null;
        $registrationEnd = $fields['registration_end'] ?? null;

        if ($registrationStart !== null && $registrationEnd !== null
            && strtotime($registrationStart) >= strtotime($registrationEnd)) {
            throw new ApiException('The registration start must be before the registration end.', 422);
        }
    }
}
