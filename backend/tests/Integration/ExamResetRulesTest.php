<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\ExamResetService;
use App\Services\ExamService;
use App\Services\ExamSessionService;
use Tests\TestCase;

class ExamResetRulesTest extends TestCase
{
    private ExamService $exams;

    private ExamSessionService $sessions;

    private ExamResetService $resets;

    private array $lecturerUser;

    private array $coordinatorUser;

    private array $studentUser;

    private int $studentId;

    private int $examId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exams = new ExamService();
        $this->sessions = new ExamSessionService();
        $this->resets = new ExamResetService();

        $structure = $this->createAcademicStructure();
        $lecturer = $this->createLecturer($structure);
        $student = $this->createStudent($structure);
        $coordinatorId = $this->createUser('Coordinator', 'coordinator@test.edu', 'Test Coordinator');

        $courseId = $this->createCourse($structure['department_id'], 'CS101');
        $sectionId = $this->createSection($courseId, $lecturer['lecturer_id'], $structure['semester_id']);

        $this->enrol($student['student_id'], $sectionId);

        $this->studentId = $student['student_id'];
        $this->lecturerUser = $this->actingAs($lecturer['user_id'], 'Lecturer');
        $this->coordinatorUser = $this->actingAs($coordinatorId, 'Coordinator');
        $this->studentUser = $this->actingAs($student['user_id'], 'Student');

        $this->examId = (int) $this->exams->create($this->lecturerUser, [
            'section_id' => $sectionId,
            'title' => 'Final Examination',
            'duration' => 60,
            'start_time' => gmdate('Y-m-d H:i:s', time() - 600),
            'end_time' => gmdate('Y-m-d H:i:s', time() + 7200),
            'status' => 'published',
        ], [
            [
                'question' => 'Two plus two?',
                'question_type' => 'Multiple Choice',
                'marks' => 10,
                'correct_answer' => 'B',
                'options' => [['label' => 'A', 'text' => '3'], ['label' => 'B', 'text' => '4']],
            ],
        ])['id'];
    }

    public function testAResetCannotBeRequestedForAnExaminationNeverSat(): void
    {
        try {
            $this->resets->request($this->studentUser, $this->examId, 'My laptop died.');

            $this->fail('A reset needs a sitting to reset.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public function testAResetCannotBeRequestedWhileTheSessionIsStillOpen(): void
    {
        $this->sessions->start($this->studentUser, $this->examId, []);

        try {
            $this->resets->request($this->studentUser, $this->examId, 'My laptop died.');

            $this->fail('An open session must be closed first.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public function testAStudentCannotHaveTwoOpenRequestsForOneExamination(): void
    {
        $this->sitAndSubmit();

        $this->resets->request($this->studentUser, $this->examId, 'The power went out.');

        $this->expectException(ApiException::class);

        $this->resets->request($this->studentUser, $this->examId, 'Asking again.');
    }

    public function testARequestIsLoggedOnCreation(): void
    {
        $this->sitAndSubmit();

        $request = $this->resets->request($this->studentUser, $this->examId, 'The power went out.');

        $this->assertSame('Pending', $request['approval_status']);
        $this->assertCount(1, $request['log']);
        $this->assertSame('Requested', $request['log'][0]['action']);
    }

    /**
     * The whole point. Before this module a submitted examination could never
     * be sat again, because the submission row blocked both the start check and
     * the unique key.
     */
    public function testAnApprovedResetLetsTheStudentSitTheExaminationAgain(): void
    {
        $this->sitAndSubmit();

        $request = $this->resets->request($this->studentUser, $this->examId, 'The power went out.');

        // Before the reset the student is refused.
        try {
            $this->sessions->start($this->studentUser, $this->examId, []);

            $this->fail('A submitted examination must be closed until it is reset.');
        } catch (ApiException) {
            // expected
        }

        $this->resets->approve((int) $request['id'], $this->coordinatorUser, 'Verified with IT.');

        $session = $this->sessions->start($this->studentUser, $this->examId, []);

        $this->assertSame('active', $session['status']);
    }

    public function testTheOriginalAttemptSurvivesTheReset(): void
    {
        $this->sitAndSubmit();

        $request = $this->resets->request($this->studentUser, $this->examId, 'The power went out.');
        $this->resets->approve((int) $request['id'], $this->coordinatorUser, null);

        $session = $this->sessions->start($this->studentUser, $this->examId, []);
        $this->sessions->end($this->studentUser, (int) $session['id'], []);

        $rows = (int) $this->scalar('SELECT COUNT(*) FROM ExamSubmission WHERE exam_id = ?', [$this->examId]);
        $attempts = $this->scalar(
            'SELECT GROUP_CONCAT(attempt_number ORDER BY attempt_number) FROM ExamSubmission WHERE exam_id = ?',
            [$this->examId]
        );
        $reset = (int) $this->scalar('SELECT COUNT(*) FROM ExamSubmission WHERE reset_at IS NOT NULL');

        $this->assertSame(2, $rows);
        $this->assertSame('1,2', $attempts);
        $this->assertSame(1, $reset);
    }

    public function testApprovalMarksTheRequestCompletedAndLogsBothSteps(): void
    {
        $this->sitAndSubmit();

        $request = $this->resets->request($this->studentUser, $this->examId, 'The power went out.');
        $approved = $this->resets->approve((int) $request['id'], $this->coordinatorUser, 'Fine.');

        $actions = array_column($approved['log'], 'action');

        $this->assertSame('Completed', $approved['approval_status']);
        $this->assertSame(['Requested', 'Approved', 'Reset Completed'], $actions);
    }

    public function testALecturerCanRecommendBeforeApproval(): void
    {
        $this->sitAndSubmit();

        $request = $this->resets->request($this->studentUser, $this->examId, 'The power went out.');

        $recommended = $this->resets->recommend(
            (int) $request['id'],
            $this->lecturerUser,
            'The student did lose connection.'
        );

        $this->assertSame('Recommended', $recommended['approval_status']);

        $approved = $this->resets->approve((int) $request['id'], $this->coordinatorUser, null);

        $this->assertSame('Completed', $approved['approval_status']);
    }

    public function testARejectedRequestDoesNotResetAnything(): void
    {
        $this->sitAndSubmit();

        $request = $this->resets->request($this->studentUser, $this->examId, 'I want a better mark.');

        $this->resets->reject((int) $request['id'], $this->coordinatorUser, 'Not a valid reason.');

        $reset = (int) $this->scalar('SELECT COUNT(*) FROM ExamSubmission WHERE reset_at IS NOT NULL');

        $this->assertSame(0, $reset);

        $this->expectException(ApiException::class);

        $this->sessions->start($this->studentUser, $this->examId, []);
    }

    public function testACompletedRequestCannotBeDecidedAgain(): void
    {
        $this->sitAndSubmit();

        $request = $this->resets->request($this->studentUser, $this->examId, 'The power went out.');
        $this->resets->approve((int) $request['id'], $this->coordinatorUser, null);

        $this->expectException(ApiException::class);

        $this->resets->reject((int) $request['id'], $this->coordinatorUser, 'Changed my mind.');
    }

    public function testARejectedRequestCannotBeApprovedLater(): void
    {
        $this->sitAndSubmit();

        $request = $this->resets->request($this->studentUser, $this->examId, 'The power went out.');
        $this->resets->reject((int) $request['id'], $this->coordinatorUser, 'No.');

        $this->expectException(ApiException::class);

        $this->resets->approve((int) $request['id'], $this->coordinatorUser, null);
    }

    public function testAResetCanBeRequestedAfterARejection(): void
    {
        $this->sitAndSubmit();

        $first = $this->resets->request($this->studentUser, $this->examId, 'Vague reason.');
        $this->resets->reject((int) $first['id'], $this->coordinatorUser, 'Not enough detail.');

        $second = $this->resets->request(
            $this->studentUser,
            $this->examId,
            'The invigilator confirmed the network failed at 09:14.'
        );

        $this->assertSame('Pending', $second['approval_status']);
        $this->assertNotSame((int) $first['id'], (int) $second['id']);
    }

    public function testAStudentSeesOnlyTheirOwnRequests(): void
    {
        $this->sitAndSubmit();
        $this->resets->request($this->studentUser, $this->examId, 'The power went out.');

        $other = $this->createStudent(
            ['faculty_id' => 1, 'department_id' => 1, 'program_id' => 1],
            'other@test.edu',
            'Other Student'
        );

        $otherUser = $this->actingAs($other['user_id'], 'Student');

        $this->assertCount(1, $this->resets->list($this->studentUser, null));
        $this->assertCount(0, $this->resets->list($otherUser, null));
        $this->assertCount(1, $this->resets->list($this->coordinatorUser, null));
    }

    private function sitAndSubmit(): void
    {
        $session = $this->sessions->start($this->studentUser, $this->examId, []);

        $this->sessions->end($this->studentUser, (int) $session['id'], []);
    }
}
