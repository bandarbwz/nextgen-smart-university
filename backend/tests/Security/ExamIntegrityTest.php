<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Services\ApiException;
use App\Services\ExamReportService;
use App\Services\ExamService;
use App\Services\ExamSessionService;
use App\Services\ProctoringService;
use Tests\TestCase;

class ExamIntegrityTest extends TestCase
{
    private ExamService $exams;

    private ExamSessionService $sessions;

    private ProctoringService $proctoring;

    private ExamReportService $reports;

    private array $lecturerUser;

    private array $otherLecturerUser;

    private array $studentUser;

    private array $classmateUser;

    private int $sectionId;

    private int $examId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exams = new ExamService();
        $this->sessions = new ExamSessionService();
        $this->proctoring = new ProctoringService();
        $this->reports = new ExamReportService();

        $structure = $this->createAcademicStructure();
        $lecturer = $this->createLecturer($structure);
        $otherLecturer = $this->createLecturer($structure, 'other@test.edu');
        $student = $this->createStudent($structure);
        $classmate = $this->createStudent($structure, 'classmate@test.edu', 'Classmate');

        $courseId = $this->createCourse($structure['department_id'], 'CS101');
        $this->sectionId = $this->createSection($courseId, $lecturer['lecturer_id'], $structure['semester_id']);

        $this->enrol($student['student_id'], $this->sectionId);
        $this->enrol($classmate['student_id'], $this->sectionId);

        $this->lecturerUser = $this->actingAs($lecturer['user_id'], 'Lecturer');
        $this->otherLecturerUser = $this->actingAs($otherLecturer['user_id'], 'Lecturer');
        $this->studentUser = $this->actingAs($student['user_id'], 'Student');
        $this->classmateUser = $this->actingAs($classmate['user_id'], 'Student');

        $this->examId = (int) $this->exams->create($this->lecturerUser, [
            'section_id' => $this->sectionId,
            'title' => 'Final Examination',
            'duration' => 60,
            'start_time' => gmdate('Y-m-d H:i:s', time() - 600),
            'end_time' => gmdate('Y-m-d H:i:s', time() + 7200),
            'status' => 'published',
        ], [
            [
                'question' => 'Which structure is last in, first out?',
                'question_type' => 'Multiple Choice',
                'marks' => 5,
                'correct_answer' => 'B',
                'options' => [['label' => 'A', 'text' => 'Queue'], ['label' => 'B', 'text' => 'Stack']],
            ],
        ])['id'];
    }

    public function testTheAnswerKeyNeverReachesAStudent(): void
    {
        $exam = $this->exams->get($this->examId, $this->studentUser);

        foreach ($exam['questions'] as $question) {
            $this->assertArrayNotHasKey('correct_answer', $question);
        }

        $this->assertStringNotContainsString('correct_answer', json_encode($exam));
    }

    public function testTheAnswerKeyIsStillVisibleToTheLecturer(): void
    {
        $exam = $this->exams->get($this->examId, $this->lecturerUser);

        $this->assertArrayHasKey('correct_answer', $exam['questions'][0]);
        $this->assertSame('B', $exam['questions'][0]['correct_answer']);
    }

    public function testAStudentCannotPostTelemetryIntoAnotherStudentsSession(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);

        $this->expectException(ApiException::class);

        $this->proctoring->recordBrowserActivity(
            $this->classmateUser,
            (int) $session['id'],
            'tab_hidden',
            null
        );
    }

    public function testAStudentCannotEndAnotherStudentsSession(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);

        $this->expectException(ApiException::class);

        $this->sessions->end($this->classmateUser, (int) $session['id'], []);
    }

    public function testAnotherStudentsSessionLooksMissingRatherThanForbidden(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);

        try {
            $this->sessions->requireOwnSession((int) $session['id'], $this->classmateUser);

            $this->fail('A classmate must not reach another student session.');
        } catch (ApiException $exception) {
            $this->assertSame(404, $exception->statusCode());
        }
    }

    public function testAStudentCannotPauseTheirOwnSessionToBuyTime(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);

        $this->expectException(ApiException::class);

        $this->sessions->pause($this->studentUser, (int) $session['id']);
    }

    public function testAStudentCannotReadAnotherStudentsViolations(): void
    {
        $classmateStudentId = (int) $this->scalar(
            'SELECT id FROM Student WHERE user_id = ?',
            [$this->classmateUser['user_id']]
        );

        $this->expectException(ApiException::class);

        $this->proctoring->violationsForStudent($classmateStudentId, $this->studentUser);
    }

    public function testALecturerCannotReachAnotherLecturersExamination(): void
    {
        $this->expectException(ApiException::class);

        $this->exams->submissions($this->examId, $this->otherLecturerUser);
    }

    public function testALecturerCannotGenerateAReportForAnotherLecturersSession(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);

        $this->expectException(ApiException::class);

        $this->reports->generate((int) $session['id'], $this->otherLecturerUser);
    }

    public function testAStudentCannotReadAnotherStudentsAiReport(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $sessionId = (int) $session['id'];

        $this->sessions->end($this->studentUser, $sessionId, []);
        $report = $this->reports->generate($sessionId, $this->lecturerUser);

        $this->expectException(ApiException::class);

        $this->reports->get((int) $report['id'], $this->classmateUser);
    }

    public function testAStudentSeesTheirOwnReportWithoutTheRawTelemetry(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $sessionId = (int) $session['id'];

        $this->sessions->end($this->studentUser, $sessionId, []);
        $generated = $this->reports->generate($sessionId, $this->lecturerUser);

        $own = $this->reports->get((int) $generated['id'], $this->studentUser);

        $this->assertSame((int) $generated['id'], (int) $own['id']);
        $this->assertArrayNotHasKey('telemetry', $own);
    }

    public function testTheIntegrityScoreCannotBeSetByTheClient(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $sessionId = (int) $session['id'];

        $this->proctoring->recordBrowserActivity($this->studentUser, $sessionId, 'tab_hidden', null);
        $this->sessions->end($this->studentUser, $sessionId, []);

        $report = $this->reports->generate($sessionId, $this->lecturerUser);

        $this->assertLessThan(100, (int) $report['integrity_score']);
    }
}
