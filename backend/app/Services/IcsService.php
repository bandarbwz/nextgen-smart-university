<?php

declare(strict_types=1);

namespace App\Services;

class IcsService
{
    private const LINE_BREAK = "\r\n";

    public function export(array $events, string $calendarName): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//NextGen Smart University//Calendar//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . $this->escape($calendarName),
        ];

        foreach ($events as $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:nsu-event-' . $event['id'] . '@nextgen.edu';
            $lines[] = 'DTSTAMP:' . $this->toUtcStamp(gmdate('Y-m-d H:i:s'));
            $lines[] = 'DTSTART:' . $this->toUtcStamp($event['start_datetime']);
            $lines[] = 'DTEND:' . $this->toUtcStamp($event['end_datetime']);
            $lines[] = 'SUMMARY:' . $this->escape($event['title']);

            if (!empty($event['description'])) {
                $lines[] = 'DESCRIPTION:' . $this->escape($event['description']);
            }

            if (!empty($event['location'])) {
                $lines[] = 'LOCATION:' . $this->escape($event['location']);
            }

            $lines[] = 'CATEGORIES:' . $this->escape($event['event_type']);
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode(self::LINE_BREAK, $lines) . self::LINE_BREAK;
    }

    public function parse(string $contents): array
    {
        if (!str_contains($contents, 'BEGIN:VEVENT')) {
            throw new ApiException('The file does not contain any calendar events.', 422);
        }

        $unfolded = preg_replace('/\r?\n[ \t]/', '', $contents);
        $events = [];
        $current = null;

        foreach (preg_split('/\r?\n/', (string) $unfolded) as $line) {
            $line = trim($line);

            if ($line === 'BEGIN:VEVENT') {
                $current = [];

                continue;
            }

            if ($line === 'END:VEVENT') {
                if ($current !== null && $this->isComplete($current)) {
                    $events[] = $current;
                }

                $current = null;

                continue;
            }

            if ($current === null || !str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $property = strtoupper(explode(';', $name)[0]);

            match ($property) {
                'SUMMARY' => $current['title'] = $this->unescape($value),
                'DESCRIPTION' => $current['description'] = $this->unescape($value),
                'LOCATION' => $current['location'] = $this->unescape($value),
                'DTSTART' => $current['start_datetime'] = $this->fromStamp($value),
                'DTEND' => $current['end_datetime'] = $this->fromStamp($value),
                default => null,
            };
        }

        if ($events === []) {
            throw new ApiException('No complete events could be read from the file.', 422);
        }

        return $events;
    }

    private function isComplete(array $event): bool
    {
        return isset($event['title'], $event['start_datetime'], $event['end_datetime'])
            && $event['start_datetime'] !== null
            && $event['end_datetime'] !== null
            && strtotime($event['end_datetime']) > strtotime($event['start_datetime']);
    }

    private function toUtcStamp(string $dateTime): string
    {
        return gmdate('Ymd\THis\Z', strtotime($dateTime . ' UTC'));
    }

    private function fromStamp(string $value): ?string
    {
        $clean = trim($value);

        if (preg_match('/^(\d{8})T(\d{6})Z?$/', $clean, $matches) === 1) {
            return gmdate('Y-m-d H:i:s', strtotime($matches[1] . 'T' . $matches[2] . ' UTC'));
        }

        if (preg_match('/^(\d{8})$/', $clean) === 1) {
            return gmdate('Y-m-d 00:00:00', strtotime($clean . ' UTC'));
        }

        return null;
    }

    private function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\n", "\r"],
            ['\\\\', '\\;', '\\,', '\\n', ''],
            $value
        );
    }

    private function unescape(string $value): string
    {
        return str_replace(
            ['\\n', '\\N', '\\,', '\\;', '\\\\'],
            ["\n", "\n", ',', ';', '\\'],
            $value
        );
    }
}
