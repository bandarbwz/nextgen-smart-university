<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\EventRegistrationService;
use App\Validation\ActivityValidator;

class EventRegistrationController extends Controller
{
    public function __construct(
        private readonly EventRegistrationService $registrations = new EventRegistrationService(),
        private readonly ActivityValidator $validator = new ActivityValidator()
    ) {
        parent::__construct();
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['Student']);

        $data = Request::body();
        $errors = $this->validator->registration($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $registration = $this->run(
            fn () => $this->registrations->register($user, (int) $data['event_id'])
        );

        Response::success('Registration submitted.', ['registration' => $registration], 201);
    }

    public function mine(): void
    {
        $user = $this->authenticateAs(['Student']);

        $registrations = $this->run(fn () => $this->registrations->mine($user));

        Response::success('Registrations retrieved.', ['registrations' => $registrations]);
    }

    public function cancel(string $id): void
    {
        $user = $this->authenticateAs(['Student']);

        $registration = $this->run(fn () => $this->registrations->cancel((int) $id, $user));

        Response::success('Registration cancelled.', ['registration' => $registration]);
    }

    public function forEvent(string $id): void
    {
        $this->authenticateAs(['STAD Staff']);

        $registrations = $this->run(
            fn () => $this->registrations->forEvent((int) $id, $this->queryString('status'))
        );

        Response::success('Registrations retrieved.', ['registrations' => $registrations]);
    }

    public function approve(string $id): void
    {
        $user = $this->authenticateAs(['STAD Staff']);

        $registration = $this->run(fn () => $this->registrations->approve((int) $id, $user));

        Response::success('Registration approved.', ['registration' => $registration]);
    }

    public function reject(string $id): void
    {
        $user = $this->authenticateAs(['STAD Staff']);

        $data = Request::body();
        $errors = $this->validator->rejection($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $registration = $this->run(
            fn () => $this->registrations->reject((int) $id, $user, $data['reason'] ?? null)
        );

        Response::success('Registration rejected.', ['registration' => $registration]);
    }
}
