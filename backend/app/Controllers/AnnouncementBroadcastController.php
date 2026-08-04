<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\AnnouncementBroadcastService;
use App\Validation\NotificationValidator;

class AnnouncementBroadcastController extends Controller
{
    public function __construct(
        private readonly AnnouncementBroadcastService $announcements = new AnnouncementBroadcastService(),
        private readonly NotificationValidator $validator = new NotificationValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $user = $this->authenticate();

        $announcements = $this->run(fn () => $this->announcements->list($user));

        Response::success('Announcements retrieved.', ['announcements' => $announcements]);
    }

    public function store(): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->announcement($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $announcement = $this->run(fn () => $this->announcements->create($user, $data));

        Response::success('Announcement created.', ['announcement' => $announcement], 201);
    }

    public function update(string $id): void
    {
        $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->announcement($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $announcement = $this->run(fn () => $this->announcements->update((int) $id, $data));

        Response::success('Announcement updated.', ['announcement' => $announcement]);
    }

    public function destroy(string $id): void
    {
        $this->authenticateAsAdministrator();

        $this->run(fn () => $this->announcements->delete((int) $id));

        Response::success('Announcement deleted.');
    }

    public function broadcast(): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::body();
        $errors = $this->validator->broadcast($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $recipients = $this->run(fn () => $this->announcements->broadcast($user, $data));

        Response::success('Broadcast sent.', ['recipients' => $recipients], 201);
    }
}
