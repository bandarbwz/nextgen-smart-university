<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\AttendanceService;
use App\Services\FaceVerificationService;
use App\Services\GeoService;
use App\Services\QrSessionService;
use App\Services\StudentService;
use App\Validation\AttendanceValidator;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendance = new AttendanceService(),
        private readonly QrSessionService $qrSessions = new QrSessionService(),
        private readonly StudentService $students = new StudentService(),
        private readonly FaceVerificationService $faces = new FaceVerificationService(),
        private readonly GeoService $geo = new GeoService(),
        private readonly AttendanceValidator $validator = new AttendanceValidator()
    ) {
        parent::__construct();
    }

    public function openSession(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->openSession($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $session = $this->run(fn () => $this->qrSessions->open(
            (int) $data['section_id'],
            $user['user_id'],
            [
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'allowed_radius' => isset($data['allowed_radius']) ? (int) $data['allowed_radius'] : null,
            ],
        ));

        Response::success('Attendance session opened.', ['session' => $session], 201);
    }

    public function activeSession(string $sectionId): void
    {
        $this->authenticate();

        $session = $this->run(fn () => $this->qrSessions->activeForSection((int) $sectionId));

        Response::success('Attendance session retrieved.', ['session' => $session]);
    }

    public function closeSession(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $session = $this->run(fn () => $this->qrSessions->close((int) $id, $user['user_id'], $user['role']));

        Response::success('Attendance session closed.', ['session' => $session]);
    }

    public function scan(): void
    {
        $user = $this->authenticateAs(['Student']);

        $data = Request::body();
        $errors = $this->validator->scan($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $record = $this->run(function () use ($user, $data) {
            $student = $this->students->getByUserId($user['user_id']);

            return $this->attendance->scan(
                (int) $student['id'],
                $data['qr_token'],
                isset($data['latitude']) ? (float) $data['latitude'] : null,
                isset($data['longitude']) ? (float) $data['longitude'] : null,
            );
        });

        Response::success('Attendance recorded.', ['attendance' => $record], 201);
    }

    public function verifyLocation(): void
    {
        $this->authenticateAs(['Student']);

        $data = Request::body();
        $errors = $this->validator->verifyLocation($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $result = $this->run(function () use ($data) {
            $session = $this->qrSessions->requireActiveByToken($data['qr_token']);

            if ($session['latitude'] === null) {
                return ['within_range' => true, 'distance_metres' => null];
            }

            $distance = $this->geo->distanceInMetres(
                (float) $data['latitude'],
                (float) $data['longitude'],
                (float) $session['latitude'],
                (float) $session['longitude'],
            );

            return [
                'within_range' => $distance <= (int) $session['allowed_radius'],
                'distance_metres' => round($distance, 1),
                'allowed_radius' => (int) $session['allowed_radius'],
            ];
        });

        Response::success('Location checked.', $result);
    }

    public function verifyFace(): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $errors = $this->validator->verifyFace($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $result = $this->run(fn () => $this->faces->verify($user['user_id'], $data['image']));

        Response::success('Face verification completed.', $result);
    }

    public function manual(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->manual($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $record = $this->run(fn () => $this->attendance->recordManually(
            $user['user_id'],
            $user['role'],
            (int) $data['student_id'],
            (int) $data['section_id'],
            $data['attendance_date'],
            $data['attendance_status'],
            isset($data['remarks']) ? trim((string) $data['remarks']) : null,
        ));

        Response::success('Attendance saved.', ['attendance' => $record]);
    }

    public function update(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->update($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $record = $this->run(fn () => $this->attendance->update(
            (int) $id,
            $user['user_id'],
            $user['role'],
            $data['attendance_status'],
            isset($data['remarks']) ? trim((string) $data['remarks']) : null,
        ));

        Response::success('Attendance updated.', ['attendance' => $record]);
    }

    public function destroy(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $this->run(fn () => $this->attendance->delete((int) $id, $user['user_id'], $user['role']));

        Response::success('Attendance record deleted.');
    }

    public function myAttendance(): void
    {
        $user = $this->authenticateAs(['Student']);

        $result = $this->run(function () use ($user) {
            $student = $this->students->getByUserId($user['user_id']);

            return [
                'attendance' => $this->attendance->forStudent((int) $student['id']),
                'statistics' => $this->attendance->statisticsForStudent((int) $student['id']),
            ];
        });

        Response::success('Attendance retrieved.', $result);
    }

    public function forStudent(string $studentId): void
    {
        $this->authenticateAs(['Lecturer', 'Coordinator']);

        $result = $this->run(fn () => [
            'attendance' => $this->attendance->forStudent((int) $studentId),
            'statistics' => $this->attendance->statisticsForStudent((int) $studentId),
        ]);

        Response::success('Attendance retrieved.', $result);
    }

    public function forSection(string $sectionId): void
    {
        $user = $this->authenticateAs(['Lecturer', 'Coordinator']);

        $records = $this->run(fn () => $this->attendance->forSection(
            (int) $sectionId,
            $this->queryString('date'),
            $user['user_id'],
            $user['role'],
        ));

        Response::success('Attendance retrieved.', ['attendance' => $records]);
    }

    public function forLecturer(string $lecturerId): void
    {
        $this->authenticateAs(['Lecturer', 'Coordinator']);

        $records = $this->run(
            fn () => $this->attendance->forLecturer((int) $lecturerId, $this->queryString('date'))
        );

        Response::success('Attendance retrieved.', ['attendance' => $records]);
    }

    public function dailyReport(): void
    {
        $this->authenticateAs(['Coordinator']);

        $date = $this->queryString('date') ?? gmdate('Y-m-d');

        Response::success('Daily report generated.', [
            'date' => $date,
            'report' => $this->attendance->dailyReport($date),
        ]);
    }

    public function monthlyReport(): void
    {
        $this->authenticateAs(['Coordinator']);

        $year = $this->queryInt('year') ?? (int) gmdate('Y');
        $month = $this->queryInt('month') ?? (int) gmdate('n');

        if ($month < 1 || $month > 12) {
            Response::error('The month must be between 1 and 12.', 422);
        }

        Response::success('Monthly report generated.', [
            'year' => $year,
            'month' => $month,
            'report' => $this->attendance->monthlyReport($year, $month),
        ]);
    }

    public function statistics(): void
    {
        $user = $this->authenticate();

        $studentId = $this->queryInt('student_id');

        $result = $this->run(function () use ($user, $studentId) {
            if ($user['role'] === 'Student') {
                $student = $this->students->getByUserId($user['user_id']);

                return $this->attendance->statisticsForStudent((int) $student['id']);
            }

            if ($studentId === null) {
                throw new \App\Services\ApiException('A student_id query parameter is required.', 400);
            }

            return $this->attendance->statisticsForStudent($studentId);
        });

        Response::success('Statistics generated.', ['statistics' => $result]);
    }
}
