<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\DownloadCenterService;
use Tests\TestCase;

class DownloadCenterTest extends TestCase
{
    private DownloadCenterService $downloads;

    private array $structure;

    private array $student;

    private array $lecturer;

    private array $adminUser;

    private array $studentUser;

    private array $lecturerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->downloads = new DownloadCenterService();

        $this->structure = $this->createAcademicStructure();
        $this->lecturer = $this->createLecturer($this->structure);
        $this->student = $this->createStudent($this->structure);

        $adminId = $this->createUser('Administrator', 'admin@test.edu', 'Test Admin');

        $this->adminUser = $this->actingAs($adminId, 'Administrator');
        $this->studentUser = $this->actingAs($this->student['user_id'], 'Student');
        $this->lecturerUser = $this->actingAs($this->lecturer['user_id'], 'Lecturer');
    }

    public function testAStudentSeesFilesForEveryoneAndForStudents(): void
    {
        $this->addFile('Handbook', 'all');
        $this->addFile('Student Form', 'students');
        $this->addFile('Staff Memo', 'staff');
        $this->addFile('Internal Policy', 'administrators');

        $titles = array_column($this->downloads->files($this->studentUser, null), 'title');

        $this->assertContains('Handbook', $titles);
        $this->assertContains('Student Form', $titles);
        $this->assertNotContains('Staff Memo', $titles);
        $this->assertNotContains('Internal Policy', $titles);
    }

    public function testALecturerSeesStaffFilesButNotStudentOnlyFiles(): void
    {
        $this->addFile('Student Form', 'students');
        $this->addFile('Staff Memo', 'staff');

        $titles = array_column($this->downloads->files($this->lecturerUser, null), 'title');

        $this->assertContains('Staff Memo', $titles);
        $this->assertNotContains('Student Form', $titles);
    }

    public function testAnAdministratorSeesEverything(): void
    {
        $this->addFile('Handbook', 'all');
        $this->addFile('Internal Policy', 'administrators');

        $this->assertCount(2, $this->downloads->files($this->adminUser, null));
    }

    public function testAStudentCannotOpenAStaffFileById(): void
    {
        $file = $this->addFile('Staff Memo', 'staff');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not found');

        $this->downloads->file((int) $file['id'], $this->studentUser);
    }

    public function testFilesCanBeFilteredByCategory(): void
    {
        $this->addFile('Handbook', 'all', 'Policies');
        $this->addFile('Exam Timetable', 'all', 'Examination Documents');

        $titles = array_column(
            $this->downloads->files($this->studentUser, 'Policies'),
            'title'
        );

        $this->assertSame(['Handbook'], $titles);
    }

    public function testDownloadingWritesToTheHistoryAndIncrementsTheCounter(): void
    {
        $file = $this->addFileOnDisk('Handbook', 'all');

        $this->downloads->prepareDownload((int) $file['id'], $this->studentUser, '127.0.0.1');

        $this->assertSame(
            1,
            (int) $this->scalar('SELECT COUNT(*) FROM DownloadHistory WHERE file_id = ?', [$file['id']])
        );
        $this->assertSame(
            1,
            (int) $this->scalar('SELECT download_count FROM DownloadFile WHERE id = ?', [$file['id']])
        );
    }

    public function testHistorySurvivesTheFileBeingDeleted(): void
    {
        $file = $this->addFileOnDisk('Handbook', 'all');

        $this->downloads->prepareDownload((int) $file['id'], $this->studentUser, '127.0.0.1');
        $this->downloads->delete((int) $file['id']);

        $rows = $this->downloads->history($this->studentUser);

        $this->assertCount(1, $rows);
        $this->assertSame(
            'Handbook',
            $rows[0]['file_title'],
            'The title is copied onto the history row so the audit trail survives deletion.'
        );
    }

    public function testAStudentOnlySeesTheirOwnDownloadHistory(): void
    {
        $file = $this->addFileOnDisk('Handbook', 'all');

        $otherStudent = $this->createStudent($this->structure, 'other@test.edu');
        $otherUser = $this->actingAs($otherStudent['user_id'], 'Student');

        $this->downloads->prepareDownload((int) $file['id'], $this->studentUser, '127.0.0.1');
        $this->downloads->prepareDownload((int) $file['id'], $otherUser, '127.0.0.1');

        $this->assertCount(1, $this->downloads->history($this->studentUser));
        $this->assertCount(2, $this->downloads->history($this->adminUser));
    }

    public function testDownloadingAFileMissingFromDiskFailsClearly(): void
    {
        $file = $this->addFile('Ghost', 'all');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('no longer available');

        $this->downloads->prepareDownload((int) $file['id'], $this->studentUser, '127.0.0.1');
    }

    public function testAStudentCanDownloadTheirOwnTranscriptAsPdf(): void
    {
        $export = $this->downloads->transcriptDocument($this->studentUser, null, 'pdf');

        $this->assertStringStartsWith('%PDF-', $export['contents']);
        $this->assertSame('application/pdf', $export['mime_type']);
    }

    public function testAStudentCannotPullAnotherStudentsTranscript(): void
    {
        $otherStudent = $this->createStudent($this->structure, 'rival@test.edu', 'Rival Student');

        $export = $this->downloads->transcriptDocument(
            $this->studentUser,
            $otherStudent['student_id'],
            'csv'
        );

        $this->assertStringNotContainsString(
            'rival',
            $export['file_name'],
            'A student_id supplied by a student must be ignored, not honoured.'
        );
        $this->assertStringContainsString('test-student', $export['file_name']);

        $this->assertSame(
            1,
            (int) $this->scalar(
                'SELECT COUNT(*) FROM ReportHistory WHERE user_id = ?',
                [$this->student['user_id']]
            )
        );
    }

    public function testAStudentCanDownloadTheirScheduleAsCsv(): void
    {
        $export = $this->downloads->scheduleDocument($this->studentUser, 'csv');

        $this->assertStringContainsString('Day,"Course Code"', $export['contents']);
    }

    public function testExportingAReportIsRecordedInDownloadHistory(): void
    {
        $before = (int) $this->scalar('SELECT COUNT(*) FROM DownloadHistory');

        $this->downloads->exportReport('system.users', $this->adminUser, [], 'csv');

        $this->assertSame(
            $before + 1,
            (int) $this->scalar('SELECT COUNT(*) FROM DownloadHistory')
        );
    }

    public function testExportingAReportTheCallerCannotRunIsRefused(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('do not have permission');

        $this->downloads->exportReport('finance.balances', $this->studentUser, [], 'csv');
    }

    private function addFile(string $title, string $visibility, string $category = 'Policies'): array
    {
        $id = (int) $this->db->prepare(
            'INSERT INTO DownloadFile
                (title, category, file_name, file_path, file_size, file_type, uploaded_by, visibility)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $title,
            $category,
            $title . '.pdf',
            'downloads/missing-' . bin2hex(random_bytes(4)) . '.pdf',
            1024,
            'pdf',
            $this->adminUser['user_id'],
            $visibility,
        ]) ? $this->db->lastInsertId() : 0;

        return ['id' => $id, 'title' => $title];
    }

    private function addFileOnDisk(string $title, string $visibility): array
    {
        $relative = 'downloads/test-' . bin2hex(random_bytes(4)) . '.pdf';
        $absolute = dirname(__DIR__, 2) . '/storage/uploads/' . $relative;

        if (!is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0755, true);
        }

        file_put_contents($absolute, "%PDF-1.4\ntest\n");

        $this->db->prepare(
            'INSERT INTO DownloadFile
                (title, category, file_name, file_path, file_size, file_type, uploaded_by, visibility)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $title,
            'Policies',
            $title . '.pdf',
            $relative,
            filesize($absolute),
            'pdf',
            $this->adminUser['user_id'],
            $visibility,
        ]);

        return ['id' => (int) $this->db->lastInsertId(), 'title' => $title, 'path' => $absolute];
    }
}
