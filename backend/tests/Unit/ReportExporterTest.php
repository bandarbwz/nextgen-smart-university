<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ReportExporter;
use PHPUnit\Framework\TestCase;

class ReportExporterTest extends TestCase
{
    private ReportExporter $exporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exporter = new ReportExporter();
    }

    public function testAPdfCarriesThePdfMagicBytes(): void
    {
        $export = $this->exporter->export($this->report(), 'pdf');

        $this->assertSame('application/pdf', $export['mime_type']);
        $this->assertStringStartsWith('%PDF-', $export['contents']);
    }

    /**
     * The transcript report carries a GPA array under "summary". A report level
     * note must never assume that key holds a string.
     */
    public function testAReportWhoseSummaryIsAnArrayStillExports(): void
    {
        $report = $this->report();
        $report['summary'] = ['cumulative_gpa' => 3.4, 'credits' => 96];

        $export = $this->exporter->export($report, 'pdf');

        $this->assertStringStartsWith('%PDF-', $export['contents']);
    }

    public function testAStringNoteIsAccepted(): void
    {
        $report = $this->report();
        $report['note'] = 'Identity was NOT verified.';

        $export = $this->exporter->export($report, 'pdf');

        $this->assertStringStartsWith('%PDF-', $export['contents']);
    }

    public function testACsvHasAHeadingRowAndTheDataRows(): void
    {
        $export = $this->exporter->export($this->report(), 'csv');

        $lines = array_filter(explode("\n", trim($export['contents'])));

        $this->assertCount(2, $lines);
        $this->assertStringContainsString('Violation Type', $lines[0]);
        $this->assertStringContainsString('Tab Switching', $lines[1]);
    }

    public function testAnUnknownFormatIsRefused(): void
    {
        $this->expectException(\App\Services\ApiException::class);

        $this->exporter->export($this->report(), 'docx');
    }

    private function report(): array
    {
        return [
            'title' => 'AI Examination Report',
            'columns' => ['detected_at', 'violation_type', 'severity'],
            'rows' => [
                [
                    'detected_at' => '2026-08-04 09:00:00',
                    'violation_type' => 'Tab Switching',
                    'severity' => 'critical',
                ],
            ],
        ];
    }
}
