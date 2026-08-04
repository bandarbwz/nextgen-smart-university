<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\EventService;
use App\Validation\ActivityValidator;

class EventController extends Controller
{
    public function __construct(
        private readonly EventService $events = new EventService(),
        private readonly ActivityValidator $validator = new ActivityValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $user = $this->authenticate();

        $filters = array_filter([
            'club_id' => $this->queryInt('club_id'),
            'status' => $this->queryString('status'),
            'from' => $this->queryString('from'),
            'to' => $this->queryString('to'),
        ], static fn ($value): bool => $value !== null);

        $events = $this->run(fn () => $this->events->list($user, $filters));

        Response::success('Events retrieved.', ['events' => $events]);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();

        $event = $this->run(fn () => $this->events->get((int) $id, $user));

        Response::success('Event retrieved.', ['event' => $event]);
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['STAD Staff']);

        $data = Request::body();
        $errors = $this->validator->event($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $event = $this->run(fn () => $this->events->create($user, $data));

        Response::success('Event created.', ['event' => $event], 201);
    }

    public function update(string $id): void
    {
        $user = $this->authenticateAs(['STAD Staff']);

        $data = Request::body();
        $errors = $this->validator->event($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $event = $this->run(fn () => $this->events->update((int) $id, $user, $data));

        Response::success('Event updated.', ['event' => $event]);
    }

    public function cancel(string $id): void
    {
        $user = $this->authenticateAs(['STAD Staff']);

        $event = $this->run(fn () => $this->events->cancel((int) $id, $user));

        Response::success('Event cancelled.', ['event' => $event]);
    }

    public function destroy(string $id): void
    {
        $this->authenticateAsAdministrator();

        $this->run(fn () => $this->events->delete((int) $id));

        Response::success('Event deleted.');
    }
}
