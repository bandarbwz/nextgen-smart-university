<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ApiException;
use App\Services\IcsService;
use PHPUnit\Framework\TestCase;

class IcsServiceTest extends TestCase
{
    private IcsService $ics;

    protected function setUp(): void
    {
        $this->ics = new IcsService();
    }

    public function testExportProducesAWellFormedCalendar(): void
    {
        $output = $this->ics->export([$this->sampleEvent()], 'Test Calendar');

        $this->assertStringStartsWith('BEGIN:VCALENDAR', $output);
        $this->assertStringContainsString('VERSION:2.0', $output);
        $this->assertStringContainsString('BEGIN:VEVENT', $output);
        $this->assertStringContainsString('SUMMARY:Lecture', $output);
        $this->assertStringContainsString('DTSTART:20260907T090000Z', $output);
        $this->assertStringContainsString('DTEND:20260907T110000Z', $output);
        $this->assertStringContainsString('END:VCALENDAR', $output);
    }

    public function testExportUsesCarriageReturnLineEndingsAsTheStandardRequires(): void
    {
        $output = $this->ics->export([$this->sampleEvent()], 'Test');

        $this->assertStringContainsString("\r\n", $output);
    }

    public function testExportEscapesCharactersThatWouldBreakTheFormat(): void
    {
        $event = $this->sampleEvent();
        $event['title'] = 'Exam; room A, block B';

        $output = $this->ics->export([$event], 'Test');

        $this->assertStringContainsString('SUMMARY:Exam\; room A\, block B', $output);
    }

    public function testParseReadsEventsBackOut(): void
    {
        $events = $this->ics->parse($this->ics->export([$this->sampleEvent()], 'Test'));

        $this->assertCount(1, $events);
        $this->assertSame('Lecture', $events[0]['title']);
        $this->assertSame('2026-09-07 09:00:00', $events[0]['start_datetime']);
        $this->assertSame('2026-09-07 11:00:00', $events[0]['end_datetime']);
    }

    public function testRoundTripPreservesEscapedPunctuation(): void
    {
        $event = $this->sampleEvent();
        $event['title'] = 'Exam; room A, block B';

        $events = $this->ics->parse($this->ics->export([$event], 'Test'));

        $this->assertSame('Exam; room A, block B', $events[0]['title']);
    }

    public function testParseRejectsAFileWithNoEvents(): void
    {
        $this->expectException(ApiException::class);

        $this->ics->parse('this is not a calendar at all');
    }

    public function testParseSkipsEventsWhereTheEndIsNotAfterTheStart(): void
    {
        $calendar = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:Broken\r\n"
            . "DTSTART:20260907T110000Z\r\nDTEND:20260907T090000Z\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $this->expectException(ApiException::class);

        $this->ics->parse($calendar);
    }

    public function testParseHandlesFoldedLines(): void
    {
        $calendar = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:A very long lecture\r\n  title\r\n"
            . "DTSTART:20260907T090000Z\r\nDTEND:20260907T110000Z\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $events = $this->ics->parse($calendar);

        $this->assertSame('A very long lecture title', $events[0]['title']);
    }

    private function sampleEvent(): array
    {
        return [
            'id' => 1,
            'title' => 'Lecture',
            'description' => 'Weekly class',
            'location' => 'A101',
            'event_type' => 'Class',
            'start_datetime' => '2026-09-07 09:00:00',
            'end_datetime' => '2026-09-07 11:00:00',
        ];
    }
}
