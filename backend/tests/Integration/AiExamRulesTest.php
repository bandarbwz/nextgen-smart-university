<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\ExamReportService;
use App\Services\ExamService;
use App\Services\ExamSessionService;
use App\Services\ProctoringService;
use Tests\TestCase;

class AiExamRulesTest extends TestCase
{
    private ExamService $exams;

    private ExamSessionService $sessions;

    private ProctoringService $proctoring;

    private ExamReportService $reports;

    private array $lecturerUser;

    private array $studentUser;

    private array $outsiderUser;

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
        $student = $this->createStudent($structure);
        $outsider = $this->createStudent($structure, 'outsider@test.edu', 'Outside Student');

        $courseId = $this->createCourse($structure['department_id'], 'CS101');
        $this->sectionId = $this->createSection($courseId, $lecturer['lecturer_id'], $structure['semester_id']);

        $this->enrol($student['student_id'], $this->sectionId);

        $this->lecturerUser = $this->actingAs($lecturer['user_id'], 'Lecturer');
        $this->studentUser = $this->actingAs($student['user_id'], 'Student');
        $this->outsiderUser = $this->actingAs($outsider['user_id'], 'Student');

        $this->examId = (int) $this->exams->create(
            $this->lecturerUser,
            $this->examFields(),
            $this->questions()
        )['id'];
    }

    public function testTotalMarksAreSummedFromTheQuestions(): void
    {
        $exam = $this->exams->get($this->examId, $this->lecturerUser);

        $this->assertSame('15.00', $exam['total_marks']);
    }

    public function testDraftExaminationIsHiddenFromStudents(): void
    {
        $draftId = (int) $this->exams->create(
            $this->lecturerUser,
            ['status' => 'draft'] + $this->examFields(),
            $this->questions()
        )['id'];

        $this->expectException(ApiException::class);

        $this->exams->get($draftId, $this->studentUser);
    }

    public function testStudentWhoIsNotEnrolledCannotOpenTheExamination(): void
    {
        $this->expectException(ApiException::class);

        $this->exams->get($this->examId, $this->outsiderUser);
    }

    public function testSessionStartsUnverifiedWhenTheAiServiceIsNotConfigured(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);

        $this->assertSame(0, (int) $session['identity_verified']);
        $this->assertStringContainsString('not configured', $session['verification_note']);
    }

    /**
     * A real browser sends a user agent far longer than the column allows.
     */
    public function testALongUserAgentIsStoredInsteadOfBreakingTheSession(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, [
            'browser' => str_repeat('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) ', 6),
            'device' => str_repeat('desktop-', 40),
            'ip_address' => str_repeat('2001:0db8:85a3:0000:0000:8a2e:0370:7334', 3),
        ]);

        $this->assertSame(100, mb_strlen($session['browser']));
        $this->assertSame(100, mb_strlen($session['device']));
        $this->assertSame(45, mb_strlen($session['ip_address']));
    }

    public function testStartingTwiceReturnsTheSameSessionInsteadOfANewTimer(): void
    {
        $first = $this->sessions->start($this->studentUser, $this->examId, []);
        $second = $this->sessions->start($this->studentUser, $this->examId, []);

        $this->assertSame((int) $first['id'], (int) $second['id']);
    }

    public function testObjectiveAnswersAreMarkedAndEssaysWaitForAHuman(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $questions = $this->exams->get($this->examId, $this->lecturerUser)['questions'];

        $submission = $this->sessions->end($this->studentUser, (int) $session['id'], [
            (int) $questions[0]['id'] => 'B',
            (int) $questions[1]['id'] => 'True',
            (int) $questions[2]['id'] => 'A long written answer.',
        ]);

        $this->assertSame('10.00', $submission['auto_scored_marks']);
        $this->assertNull($submission['score']);
        $this->assertSame('Pending Review', $submission['submission_status']);
    }

    public function testCaseDoesNotChangeAnAutomaticMark(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $questions = $this->exams->get($this->examId, $this->lecturerUser)['questions'];

        $submission = $this->sessions->end($this->studentUser, (int) $session['id'], [
            (int) $questions[1]['id'] => 'true',
        ]);

        $answers = json_decode($submission['answers'], true);

        $this->assertTrue($answers[1]['is_correct']);
    }

    public function testAStudentCannotSitTheSameExaminationTwice(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $this->sessions->end($this->studentUser, (int) $session['id'], []);

        $this->expectException(ApiException::class);

        $this->sessions->start($this->studentUser, $this->examId, []);
    }

    public function testSubmittingASessionTwiceIsRejected(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $this->sessions->end($this->studentUser, (int) $session['id'], []);

        $this->expectException(ApiException::class);

        $this->sessions->end($this->studentUser, (int) $session['id'], []);
    }

    public function testTabSwitchingIsRecordedAsACriticalViolation(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);

        $this->proctoring->recordBrowserActivity(
            $this->studentUser,
            (int) $session['id'],
            'tab_hidden',
            null
        );

        $violations = $this->proctoring->violationsForSession((int) $session['id'], $this->lecturerUser);

        $this->assertCount(1, $violations);
        $this->assertSame('Tab Switching', $violations[0]['violation_type']);
        $this->assertSame('critical', $violations[0]['severity']);
    }

    public function testHarmlessBrowserActivityIsLoggedWithoutAViolation(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);

        $this->proctoring->recordBrowserActivity(
            $this->studentUser,
            (int) $session['id'],
            'fullscreen_enter',
            null
        );

        $this->assertSame(
            [],
            $this->proctoring->violationsForSession((int) $session['id'], $this->lecturerUser)
        );
    }

    public function testThreeCriticalViolationsTerminateTheSession(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $sessionId = (int) $session['id'];

        foreach (['tab_hidden', 'fullscreen_exit', 'tab_hidden'] as $activity) {
            $this->proctoring->recordBrowserActivity($this->studentUser, $sessionId, $activity, null);
        }

        $status = $this->scalar('SELECT status FROM ExamSession WHERE id = ?', [$sessionId]);

        $this->assertSame('terminated', $status);
    }

    public function testTheViolationCountOnTheSessionKeepsUp(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $sessionId = (int) $session['id'];

        $this->proctoring->recordBrowserActivity($this->studentUser, $sessionId, 'tab_hidden', null);
        $this->proctoring->recordBrowserActivity($this->studentUser, $sessionId, 'window_blur', null);

        $count = $this->scalar('SELECT violation_count FROM ExamSession WHERE id = ?', [$sessionId]);

        $this->assertSame(1, (int) $count);
    }

    public function testProctoringIsRefusedOnceTheSessionIsClosed(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $this->sessions->end($this->studentUser, (int) $session['id'], []);

        $this->expectException(ApiException::class);

        $this->proctoring->recordBrowserActivity(
            $this->studentUser,
            (int) $session['id'],
            'tab_hidden',
            null
        );
    }

    public function testFaceCheckFailsHonestlyWhenTheAiServiceIsMissing(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);

        try {
            $this->proctoring->verifyFace($this->studentUser, (int) $session['id'], 'base64-image');

            $this->fail('A missing AI service must not be treated as a passed check.');
        } catch (ApiException $exception) {
            $this->assertSame(503, $exception->statusCode());
        }
    }

    public function testTheReportPenalisesViolationsAndTheMissingIdentityCheck(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $sessionId = (int) $session['id'];

        $this->proctoring->recordBrowserActivity($this->studentUser, $sessionId, 'tab_hidden', null);
        $this->sessions->end($this->studentUser, $sessionId, []);

        $report = $this->reports->generate($sessionId, $this->lecturerUser);

        $this->assertSame(60, (int) $report['integrity_score']);
        $this->assertSame(1, (int) $report['total_violations']);
        $this->assertSame(0, (int) $report['identity_verified']);
        $this->assertStringContainsString('Identity was NOT verified', $report['summary']);
    }

    public function testACleanSessionScoresFullIntegrityOnceVerified(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $sessionId = (int) $session['id'];

        $this->db->prepare('UPDATE ExamSession SET identity_verified = 1 WHERE id = ?')
            ->execute([$sessionId]);

        $this->sessions->end($this->studentUser, $sessionId, []);

        $report = $this->reports->generate($sessionId, $this->lecturerUser);

        $this->assertSame(100, (int) $report['integrity_score']);
        $this->assertStringContainsString('No integrity violations', $report['summary']);
    }

    public function testGeneratingTheReportTwiceUpdatesTheSameRow(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $sessionId = (int) $session['id'];

        $this->sessions->end($this->studentUser, $sessionId, []);

        $first = $this->reports->generate($sessionId, $this->lecturerUser);
        $second = $this->reports->generate($sessionId, $this->lecturerUser);

        $this->assertSame((int) $first['id'], (int) $second['id']);
        $this->assertSame(1, (int) $this->scalar('SELECT COUNT(*) FROM AIReport'));
    }

    public function testTheReportDownloadsAsARealPdf(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $sessionId = (int) $session['id'];

        $this->sessions->end($this->studentUser, $sessionId, []);

        $report = $this->reports->generate($sessionId, $this->lecturerUser);
        $export = $this->reports->download((int) $report['id'], $this->lecturerUser, 'pdf');

        $this->assertSame('application/pdf', $export['mime_type']);
        $this->assertStringStartsWith('%PDF-', $export['contents']);
    }

    public function testQuestionsCannotChangeOnceStudentsHaveStarted(): void
    {
        $this->sessions->start($this->studentUser, $this->examId, []);

        $this->expectException(ApiException::class);

        $this->exams->update($this->examId, $this->lecturerUser, $this->examFields(), $this->questions());
    }

    public function testGradingAboveTheTotalMarksIsRejected(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $submission = $this->sessions->end($this->studentUser, (int) $session['id'], []);

        $this->expectException(ApiException::class);

        $this->exams->grade((int) $submission['id'], $this->lecturerUser, 99.0);
    }

    public function testGradingAnEssayFinalisesTheSubmission(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $submission = $this->sessions->end($this->studentUser, (int) $session['id'], []);

        $graded = $this->exams->grade((int) $submission['id'], $this->lecturerUser, 12.0);

        $this->assertSame('12.00', $graded['score']);
        $this->assertSame('Graded', $graded['submission_status']);
    }

    private function examFields(): array
    {
        return [
            'section_id' => $this->sectionId,
            'title' => 'Final Examination',
            'duration' => 60,
            'start_time' => gmdate('Y-m-d H:i:s', time() - 600),
            'end_time' => gmdate('Y-m-d H:i:s', time() + 7200),
            'passing_marks' => 8,
            'status' => 'published',
        ];
    }

    private function questions(): array
    {
        return [
            [
                'question' => 'Which structure is last in, first out?',
                'question_type' => 'Multiple Choice',
                'marks' => 5,
                'correct_answer' => 'B',
                'options' => [['label' => 'A', 'text' => 'Queue'], ['label' => 'B', 'text' => 'Stack']],
            ],
            [
                'question' => 'A binary search needs sorted input.',
                'question_type' => 'True / False',
                'marks' => 5,
                'correct_answer' => 'True',
            ],
            [
                'question' => 'Explain the cost of a hash collision.',
                'question_type' => 'Essay',
                'marks' => 5,
            ],
        ];
    }
}
