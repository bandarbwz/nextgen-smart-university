<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\ReportCatalogue;
use App\Services\ReportExporter;
use App\Services\ReportService;
use Tests\TestCase;

class ReportAccessTest extends TestCase
{
    private ReportService $reports;

    private ReportCatalogue $catalogue;

    private ReportExporter $exporter;

    private array $structure;

    private array $student;

    private array $adminUser;

    private array $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reports = new ReportService();
        $this->catalogue = new ReportCatalogue();
        $this->exporter = new ReportExporter();

        $this->structure = $this->createAcademicStructure();
        $this->createLecturer($this->structure);
        $this->student = $this->createStudent($this->structure);

        $adminId = $this->createUser('Administrator', 'admin@test.edu', 'Test Admin');

        $this->adminUser = $this->actingAs($adminId, 'Administrator');
        $this->studentUser = $this->actingAs($this->student['user_id'], 'Student');
    }

    public function testTheCatalogueOnlyOffersReportsTheRoleMayRun(): void
    {
        $studentKeys = array_column($this->catalogue->availableTo('Student'), 'key');

        $this->assertContains('academic.transcript', $studentKeys);
        $this->assertNotContains('finance.balances', $studentKeys);
        $this->assertNotContains('system.logins', $studentKeys);
    }

    public function testAnAdministratorSeesEveryReport(): void
    {
        $this->assertGreaterThan(
            count($this->catalogue->availableTo('Student')),
            count($this->catalogue->availableTo('Administrator'))
        );
    }

    public function testAStudentCannotRunAFinanceReport(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('do not have permission');

        $this->reports->run('finance.balances', $this->studentUser, []);
    }

    public function testAStudentCannotRunASystemReport(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('do not have permission');

        $this->reports->run('system.logins', $this->studentUser, []);
    }

    public function testAnUnknownReportKeyIsRejected(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Unknown report');

        $this->reports->run('finance.secret-backdoor', $this->adminUser, []);
    }

    public function testAStudentCannotReadAnotherStudentsTranscriptByPassingAnId(): void
    {
        $otherStudent = $this->createStudent($this->structure, 'other@test.edu');

        $report = $this->reports->run(
            'academic.transcript',
            $this->studentUser,
            ['student_id' => $otherStudent['student_id']]
        );

        $this->assertStringContainsString(
            'Test Student',
            $report['title'],
            'A student_id supplied by a student must be ignored, not honoured.'
        );
    }

    public function testAdministratorReportsReadLiveData(): void
    {
        $report = $this->reports->run('system.users', $this->adminUser, []);

        $roles = array_column($report['rows'], 'role');

        $this->assertContains('Student', $roles);
        $this->assertContains('Administrator', $roles);
    }

    public function testRunningAReportIsWrittenToTheHistoryLog(): void
    {
        $before = (int) $this->scalar('SELECT COUNT(*) FROM ReportHistory');

        $this->reports->run('system.users', $this->adminUser, []);

        $this->assertSame(
            $before + 1,
            (int) $this->scalar('SELECT COUNT(*) FROM ReportHistory')
        );
        $this->assertSame(
            'system.users',
            $this->scalar('SELECT report_key FROM ReportHistory ORDER BY id DESC LIMIT 1')
        );
    }

    public function testAFailedPermissionCheckIsNotLogged(): void
    {
        $before = (int) $this->scalar('SELECT COUNT(*) FROM ReportHistory');

        try {
            $this->reports->run('finance.balances', $this->studentUser, []);
        } catch (ApiException) {
            // expected
        }

        $this->assertSame(
            $before,
            (int) $this->scalar('SELECT COUNT(*) FROM ReportHistory'),
            'A refused report must not appear in the history as though it ran.'
        );
    }

    public function testCsvExportContainsHeadingsAndRows(): void
    {
        $report = $this->reports->run('system.users', $this->adminUser, []);
        $export = $this->exporter->export($report, 'csv');

        $this->assertSame('text/csv; charset=utf-8', $export['mime_type']);
        $this->assertStringStartsWith('Role,Total,Active,Verified', $export['contents']);
        $this->assertStringContainsString('Student', $export['contents']);
    }

    public function testPdfExportProducesARealPdf(): void
    {
        $report = $this->reports->run('system.users', $this->adminUser, []);
        $export = $this->exporter->export($report, 'pdf');

        $this->assertSame('application/pdf', $export['mime_type']);
        $this->assertStringStartsWith(
            '%PDF-',
            $export['contents'],
            'The output must carry the PDF magic bytes.'
        );
    }

    public function testXlsxExportProducesARealSpreadsheet(): void
    {
        $report = $this->reports->run('system.users', $this->adminUser, []);
        $export = $this->exporter->export($report, 'xlsx');

        $this->assertStringStartsWith(
            'PK',
            $export['contents'],
            'An xlsx file is a zip archive and starts with the zip magic bytes.'
        );
        $this->assertStringEndsWith('.xlsx', $export['file_name']);
    }

    public function testAnUnsupportedExportFormatIsRejected(): void
    {
        $report = $this->reports->run('system.users', $this->adminUser, []);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('must be one of');

        $this->exporter->export($report, 'docx');
    }

    public function testExportOfAnEmptyReportStillProducesAFile(): void
    {
        $report = $this->reports->run('finance.outstanding', $this->adminUser, []);

        $this->assertSame([], $report['rows']);

        $csv = $this->exporter->export($report, 'csv');

        $this->assertStringContainsString('Invoice Number', $csv['contents']);
    }

    public function testAMonthOutsideTheValidRangeIsRejected(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('between 1 and 12');

        $this->reports->run('attendance.monthly', $this->adminUser, ['year' => 2026, 'month' => 13]);
    }
}
