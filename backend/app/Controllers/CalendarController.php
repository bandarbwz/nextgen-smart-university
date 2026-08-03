<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\CalendarService;
use App\Services\CalendarSyncService;
use App\Services\IcsService;
use App\Validation\CalendarValidator;

class CalendarController extends Controller
{
    public function __construct(
        private readonly CalendarService $calendar = new CalendarService(),
        private readonly CalendarSyncService $sync = new CalendarSyncService(),
        private readonly IcsService $ics = new IcsService(),
        private readonly CalendarValidator $validator = new CalendarValidator()
    ) {
        parent::__construct();
    }

    public function overview(): void
    {
        $user = $this->authenticate();

        Response::success('Calendar overview retrieved.', $this->calendar->overview($user['user_id']));
    }

    public function events(): void
    {
        $user = $this->authenticate();

        $from = $this->queryString('from') ?? gmdate('Y-m-01 00:00:00');
        $to = $this->queryString('to') ?? gmdate('Y-m-d 23:59:59', strtotime('+60 days'));

        $events = $this->calendar->range($user['user_id'], $from, $to, $this->queryString('event_type'));

        Response::success('Calendar events retrieved.', ['events' => $events]);
    }

    public function daily(): void
    {
        $user = $this->authenticate();

        $date = $this->queryString('date') ?? gmdate('Y-m-d');

        Response::success('Daily schedule retrieved.', [
            'date' => $date,
            'events' => $this->calendar->daily($user['user_id'], $date),
        ]);
    }

    public function weekly(): void
    {
        $user = $this->authenticate();

        $start = $this->queryString('start') ?? gmdate('Y-m-d');

        Response::success('Weekly schedule retrieved.', [
            'start' => $start,
            'events' => $this->calendar->weekly($user['user_id'], $start),
        ]);
    }

    public function monthly(): void
    {
        $user = $this->authenticate();

        $year = $this->queryInt('year') ?? (int) gmdate('Y');
        $month = $this->queryInt('month') ?? (int) gmdate('n');

        if ($month < 1 || $month > 12) {
            Response::error('The month must be between 1 and 12.', 422);
        }

        Response::success('Monthly schedule retrieved.', [
            'year' => $year,
            'month' => $month,
            'events' => $this->calendar->monthly($user['user_id'], $year, $month),
        ]);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();

        $event = $this->run(fn () => $this->calendar->get((int) $id, $user['user_id']));

        Response::success('Calendar event retrieved.', ['event' => $event]);
    }

    public function store(): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $errors = $this->validator->event($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $event = $this->run(fn () => $this->calendar->create($user['user_id'], $data));

        Response::success('Calendar event created.', ['event' => $event], 201);
    }

    public function update(string $id): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $errors = $this->validator->event($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $event = $this->run(fn () => $this->calendar->update((int) $id, $user['user_id'], $data));

        Response::success('Calendar event updated.', ['event' => $event]);
    }

    public function destroy(string $id): void
    {
        $user = $this->authenticate();

        $this->run(fn () => $this->calendar->delete((int) $id, $user['user_id']));

        Response::success('Calendar event deleted.');
    }

    public function reminders(): void
    {
        $user = $this->authenticate();

        $reminders = $this->calendar->reminders($user['user_id'], $this->queryString('status'));

        Response::success('Reminders retrieved.', ['reminders' => $reminders]);
    }

    public function storeReminder(): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $errors = $this->validator->reminder($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $reminder = $this->run(fn () => $this->calendar->createReminder($user['user_id'], $data));

        Response::success('Reminder created.', ['reminder' => $reminder], 201);
    }

    public function updateReminder(string $id): void
    {
        $user = $this->authenticate();

        $data = Request::body();
        $errors = $this->validator->reminderUpdate($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $reminder = $this->run(
            fn () => $this->calendar->updateReminder((int) $id, $user['user_id'], $data)
        );

        Response::success('Reminder updated.', ['reminder' => $reminder]);
    }

    public function completeReminder(string $id): void
    {
        $user = $this->authenticate();

        $reminder = $this->run(
            fn () => $this->calendar->completeReminder((int) $id, $user['user_id'])
        );

        Response::success('Reminder marked as completed.', ['reminder' => $reminder]);
    }

    public function destroyReminder(string $id): void
    {
        $user = $this->authenticate();

        $this->run(fn () => $this->calendar->deleteReminder((int) $id, $user['user_id']));

        Response::success('Reminder deleted.');
    }

    public function synchronise(): void
    {
        $user = $this->authenticate();

        $result = $this->run(fn () => $this->sync->synchronise($user));

        Response::success('Calendar synchronised.', $result);
    }

    public function export(): void
    {
        $user = $this->authenticate();

        $from = $this->queryString('from') ?? gmdate('Y-01-01 00:00:00');
        $to = $this->queryString('to') ?? gmdate('Y-12-31 23:59:59');

        $events = $this->calendar->range($user['user_id'], $from, $to, null);
        $calendar = $this->ics->export($events, 'NextGen Smart University');

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="nextgen-calendar.ics"');
        header('Content-Length: ' . strlen($calendar));

        echo $calendar;

        exit;
    }

    public function import(): void
    {
        $user = $this->authenticate();

        $file = $_FILES['file'] ?? null;

        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::validationError(['file' => ['A calendar file is required.']]);
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            Response::error('The calendar file must not be larger than 2 MB.', 422);
        }

        $contents = file_get_contents($file['tmp_name']);

        $imported = $this->run(function () use ($contents, $user) {
            $events = $this->ics->parse((string) $contents);
            $count = 0;

            foreach ($events as $event) {
                $this->calendar->create($user['user_id'], $event + ['event_type' => 'Personal Event']);
                $count++;
            }

            return $count;
        });

        Response::success('Calendar imported.', ['imported' => $imported], 201);
    }
}
