<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\AnnouncementService;
use App\Services\GradeService;
use App\Services\ResourceService;
use App\Validation\LmsValidator;

class LmsContentController extends Controller
{
    public function __construct(
        private readonly AnnouncementService $announcements = new AnnouncementService(),
        private readonly ResourceService $resources = new ResourceService(),
        private readonly GradeService $grades = new GradeService(),
        private readonly LmsValidator $validator = new LmsValidator()
    ) {
        parent::__construct();
    }

    public function announcements(): void
    {
        $user = $this->authenticate();

        $announcements = $this->run(
            fn () => $this->announcements->list($user, $this->queryInt('section_id'))
        );

        Response::success('Announcements retrieved.', ['announcements' => $announcements]);
    }

    public function storeAnnouncement(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->announcement($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $announcement = $this->run(fn () => $this->announcements->create($user, [
            'section_id' => $data['section_id'],
            'title' => trim((string) $data['title']),
            'content' => trim((string) $data['content']),
        ]));

        Response::success('Announcement published.', ['announcement' => $announcement], 201);
    }

    public function updateAnnouncement(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->announcementUpdate($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $announcement = $this->run(fn () => $this->announcements->update((int) $id, $user, [
            'title' => trim((string) $data['title']),
            'content' => trim((string) $data['content']),
        ]));

        Response::success('Announcement updated.', ['announcement' => $announcement]);
    }

    public function destroyAnnouncement(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $this->run(fn () => $this->announcements->delete((int) $id, $user));

        Response::success('Announcement deleted.');
    }

    public function resources(): void
    {
        $user = $this->authenticate();

        $resources = $this->run(
            fn () => $this->resources->list($user, $this->queryInt('section_id'))
        );

        Response::success('Resources retrieved.', ['resources' => $resources]);
    }

    public function storeResource(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->resource($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $resource = $this->run(fn () => $this->resources->create($user, [
            'section_id' => $data['section_id'],
            'title' => trim((string) $data['title']),
            'link' => trim((string) $data['link']),
            'resource_type' => $data['resource_type'],
        ]));

        Response::success('Resource added.', ['resource' => $resource], 201);
    }

    public function destroyResource(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $this->run(fn () => $this->resources->delete((int) $id, $user));

        Response::success('Resource deleted.');
    }

    public function grades(): void
    {
        $user = $this->authenticate();

        $grades = $this->run(fn () => $this->grades->list($user, $this->queryInt('section_id')));

        Response::success('Grades retrieved.', ['grades' => $grades]);
    }

    public function storeGrade(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->grade($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $grade = $this->run(fn () => $this->grades->record($user, $data));

        Response::success('Grade recorded.', ['grade' => $grade], 201);
    }

    public function publishGrades(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->publish($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $result = $this->run(fn () => $this->grades->publish((int) $data['section_id'], $user));

        Response::success('Grades published.', $result);
    }
}
