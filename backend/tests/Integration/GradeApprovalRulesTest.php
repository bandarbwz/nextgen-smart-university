<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\AssessmentResultService;
use App\Services\AssessmentService;
use App\Services\GradeApprovalService;
use Tests\TestCase;

class GradeApprovalRulesTest extends TestCase
{
    private AssessmentService $assessments;

    private AssessmentResultService $results;

    private GradeApprovalService $approvals;

    private array $lecturerUser;

    private array $coordinatorUser;

    private int $studentId;

    private int $sectionId;

    private int $courseId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assessments = new AssessmentService();
        $this->results = new AssessmentResultService();
        $this->approvals = new GradeApprovalService();

        $structure = $this->createAcademicStructure();
        $lecturer = $this->createLecturer($structure);
        $student = $this->createStudent($structure);
        $coordinatorId = $this->createUser('Coordinator', 'coordinator@test.edu', 'Test Coordinator');

        $this->courseId = $this->createCourse($structure['department_id'], 'CS101', 'Programming', 3);
        $this->sectionId = $this->createSection(
            $this->courseId,
            $lecturer['lecturer_id'],
            $structure['semester_id']
        );

        $this->enrol($student['student_id'], $this->sectionId);

        $this->studentId = $student['student_id'];
        $this->lecturerUser = $this->actingAs($lecturer['user_id'], 'Lecturer');
        $this->coordinatorUser = $this->actingAs($coordinatorId, 'Coordinator');
    }

    public function testGradesCannotBeSubmittedWhileTheSchemeIsIncomplete(): void
    {
        $this->buildAssessment('Midterm', 'Midterm', 40, 50, 40);

        try {
            $this->approvals->submit($this->sectionId, $this->lecturerUser);

            $this->fail('An incomplete scheme must not be submittable.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public function testGradesCannotBeSubmittedWhileAStudentIsPartlyMarked(): void
    {
        $this->buildAssessment('Midterm', 'Midterm', 40, 50, 40);

        // Second component exists and is weighted, but is never marked.
        $this->assessments->create($this->lecturerUser, [
            'section_id' => $this->sectionId,
            'title' => 'Final',
            'assessment_type' => 'Final',
            'total_marks' => 100,
            'weight_percentage' => 60,
            'status' => 'published',
        ], []);

        try {
            $this->approvals->submit($this->sectionId, $this->lecturerUser);

            $this->fail('A partly marked cohort must not be submittable.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public function testACompleteSchemeCanBeSubmitted(): void
    {
        $this->completeScheme();

        $approval = $this->approvals->submit($this->sectionId, $this->lecturerUser);

        $this->assertSame('Pending', $approval['approval_status']);
        $this->assertSame(1, (int) $approval['student_count']);
        $this->assertCount(1, $approval['log']);
        $this->assertSame('Submitted', $approval['log'][0]['action']);
    }

    public function testTheSameSectionCannotBeSubmittedTwiceWhilePending(): void
    {
        $this->completeScheme();
        $this->approvals->submit($this->sectionId, $this->lecturerUser);

        $this->expectException(ApiException::class);

        $this->approvals->submit($this->sectionId, $this->lecturerUser);
    }

    /**
     * This is the whole reason the module exists. Nothing else in the platform
     * writes a transcript row, so every GPA sat at zero until approval did it.
     */
    public function testApprovalWritesTheTranscriptAndRecalculatesTheGpa(): void
    {
        $this->completeScheme();
        $approval = $this->approvals->submit($this->sectionId, $this->lecturerUser);

        $before = (int) $this->scalar('SELECT COUNT(*) FROM Transcript');

        $this->approvals->approve((int) $approval['id'], $this->coordinatorUser, 'Looks right.');

        $after = (int) $this->scalar('SELECT COUNT(*) FROM Transcript');

        $grade = $this->scalar(
            'SELECT grade FROM Transcript WHERE student_id = ? AND course_id = ?',
            [$this->studentId, $this->courseId]
        );

        $cgpa = $this->scalar('SELECT cumulative_gpa FROM Student WHERE id = ?', [$this->studentId]);

        $this->assertSame(0, $before);
        $this->assertSame(1, $after);
        $this->assertSame('A', $grade);
        $this->assertSame('4.00', $cgpa);
    }

    public function testAFailingGradeEarnsNoCreditHours(): void
    {
        $this->completeScheme(10, 10);
        $approval = $this->approvals->submit($this->sectionId, $this->lecturerUser);

        $this->approvals->approve((int) $approval['id'], $this->coordinatorUser, null);

        $earned = $this->scalar(
            'SELECT earned_credit_hours FROM Transcript WHERE student_id = ?',
            [$this->studentId]
        );

        $this->assertSame(0, (int) $earned);
    }

    public function testApprovalIsLoggedWithThePublishStep(): void
    {
        $this->completeScheme();
        $approval = $this->approvals->submit($this->sectionId, $this->lecturerUser);

        $decided = $this->approvals->approve((int) $approval['id'], $this->coordinatorUser, null);

        $actions = array_column($decided['log'], 'action');

        $this->assertSame(['Submitted', 'Approved', 'Published'], $actions);
    }

    public function testRejectingRequiresRemarksAndBlocksPublication(): void
    {
        $this->completeScheme();
        $approval = $this->approvals->submit($this->sectionId, $this->lecturerUser);

        $rejected = $this->approvals->reject(
            (int) $approval['id'],
            $this->coordinatorUser,
            'The midterm weighting is wrong.'
        );

        $this->assertSame('Rejected', $rejected['approval_status']);
        $this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM Transcript'));
    }

    public function testADecidedRequestCannotBeDecidedAgain(): void
    {
        $this->completeScheme();
        $approval = $this->approvals->submit($this->sectionId, $this->lecturerUser);

        $this->approvals->approve((int) $approval['id'], $this->coordinatorUser, null);

        $this->expectException(ApiException::class);

        $this->approvals->reject((int) $approval['id'], $this->coordinatorUser, 'Changed my mind.');
    }

    public function testReturnedGradesCanBeRevisedAndResubmitted(): void
    {
        $this->completeScheme();
        $approval = $this->approvals->submit($this->sectionId, $this->lecturerUser);

        $this->approvals->returnForRevision(
            (int) $approval['id'],
            $this->coordinatorUser,
            'Please check the final marks.'
        );

        $resubmitted = $this->approvals->submit($this->sectionId, $this->lecturerUser);

        $actions = array_column($resubmitted['log'], 'action');

        $this->assertSame('Pending', $resubmitted['approval_status']);
        $this->assertSame(['Submitted', 'Returned for Revision', 'Resubmitted'], $actions);
    }

    public function testAnApprovedSectionCannotBeSubmittedAgain(): void
    {
        $this->completeScheme();
        $approval = $this->approvals->submit($this->sectionId, $this->lecturerUser);

        $this->approvals->approve((int) $approval['id'], $this->coordinatorUser, null);

        $this->expectException(ApiException::class);

        $this->approvals->submit($this->sectionId, $this->lecturerUser);
    }

    public function testTheDraftGradesShownToTheCoordinatorMatchTheWeighting(): void
    {
        $this->completeScheme();
        $approval = $this->approvals->submit($this->sectionId, $this->lecturerUser);

        $grades = $approval['grades'];

        $this->assertCount(1, $grades);
        $this->assertSame(90.5, $grades[0]['weighted_percentage']);
        $this->assertSame('A', $grades[0]['grade_letter']);
    }

    public function testASectionWithNoStudentsCannotBeSubmitted(): void
    {
        $structure = ['faculty_id' => 1, 'department_id' => 1, 'program_id' => 1, 'semester_id' => 1];
        $lecturer = $this->createLecturer($structure, 'empty@test.edu');
        $courseId = $this->createCourse(1, 'CS999');
        $emptySection = $this->createSection($courseId, $lecturer['lecturer_id'], 1);

        $emptyLecturer = $this->actingAs($lecturer['user_id'], 'Lecturer');

        $this->assessments->create($emptyLecturer, [
            'section_id' => $emptySection,
            'title' => 'Final',
            'assessment_type' => 'Final',
            'total_marks' => 100,
            'weight_percentage' => 100,
            'status' => 'published',
        ], []);

        $this->expectException(ApiException::class);

        $this->approvals->submit($emptySection, $emptyLecturer);
    }

    private function completeScheme(float $midtermMarks = 40, float $finalMarks = 190): void
    {
        $this->buildAssessment('Midterm', 'Midterm', 30, 50, $midtermMarks);
        $this->buildAssessment('Final', 'Final', 70, 200, $finalMarks);
    }

    private function buildAssessment(
        string $title,
        string $type,
        float $weight,
        float $totalMarks,
        float $marks
    ): void {
        $assessment = $this->assessments->create($this->lecturerUser, [
            'section_id' => $this->sectionId,
            'title' => $title,
            'assessment_type' => $type,
            'total_marks' => $totalMarks,
            'weight_percentage' => $weight,
            'status' => 'published',
        ], []);

        $this->results->record((int) $assessment['id'], $this->lecturerUser, [
            'student_id' => $this->studentId,
            'marks' => $marks,
        ]);

        $this->results->publish((int) $assessment['id'], $this->lecturerUser);
    }
}
