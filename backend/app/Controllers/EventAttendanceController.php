<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\EventAttendanceService;
use App\Validation\ActivityValidator;

class EventAttendanceController extends Controller
{
    public function __construct(
        private readonly EventAttendanceService $attendance = new EventAttendanceService(),
        private readonly ActivityValidator $validator = new ActivityValidator()
    ) {
        parent::__construct();
    }

    public function openQr(string $id): void
    {
        $user = $this->authenticateAs(['STAD Staff']);

        $session = $this->run(fn () => $this->attendance->openQr((int) $id, $user));

        Response::success('QR attendance opened.', ['session' => $session], 201);
    }

    public function closeQr(string $id): void
    {
        $user = $this->authenticateAs(['STAD Staff']);

        $this->run(fn () => $this->attendance->closeQr((int) $id, $user));

        Response::success('QR attendance closed.');
    }

    public function scan(): void
    {
        $user = $this->authenticateAs(['Student']);

        $data = Request::body();
        $errors = $this->validator->scan($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $attendance = $this->run(fn () => $this->attendance->scan($user, $data['token']));

        Response::success('Attendance recorded.', ['attendance' => $attendance], 201);
    }

    public function storeManual(): void
    {
        $user = $this->authenticateAs(['STAD Staff']);

        $data = Request::body();
        $errors = $this->validator->manualAttendance($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $attendance = $this->run(
            fn () => $this->attendance->recordManually((int) $data['registration_id'], $user)
        );

        Response::success('Attendance recorded.', ['attendance' => $attendance], 201);
    }

    public function forEvent(string $id): void
    {
        $this->authenticateAs(['STAD Staff']);

        $attendance = $this->run(fn () => $this->attendance->forEvent((int) $id));

        Response::success('Attendance retrieved.', ['attendance' => $attendance]);
    }
}
