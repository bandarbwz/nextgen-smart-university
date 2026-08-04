<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Services\ApiException;
use App\Services\AssessmentResultService;
use App\Services\AssessmentService;
use App\Services\GradeApprovalService;
use Tests\TestCase;

class GradeApprovalAccessTest extends TestCase
{
    private GradeApprovalService $approvals;

    private array $lecturerUser;

    private array $otherLecturerUser;

    private array $coordinatorUser;

    private int $sectionId;

    private array $approval;

    protected function setUp(): void
    {
        parent::setUp();

        $assessments = new AssessmentService();
        $results = new AssessmentResultService();
        $this->approvals = new GradeApprovalService();

        $structure = $this->createAcademicStructure();
        $lecturer = $this->createLecturer($structure);
        $otherLecturer = $this->createLecturer($structure, 'other@test.edu');
        $student = $this->createStudent($structure);
        $coordinatorId = $this->createUser('Coordinator', 'coordinator@test.edu', 'Test Coordinator');

        $courseId = $this->createCourse($structure['department_id'], 'CS101');
        $this->sectionId = $this->createSection($courseId, $lecturer['lecturer_id'], $structure['semester_id']);

        $this->enrol($student['student_id'], $this->sectionId);

        $this->lecturerUser = $this->actingAs($lecturer['user_id'], 'Lecturer');
        $this->otherLecturerUser = $this->actingAs($otherLecturer['user_id'], 'Lecturer');
        $this->coordinatorUser = $this->actingAs($coordinatorId, 'Coordinator');

        $assessment = $assessments->create($this->lecturerUser, [
            'section_id' => $this->sectionId,
            'title' => 'Final',
            'assessment_type' => 'Final',
            'total_marks' => 100,
            'weight_percentage' => 100,
            'status' => 'published',
        ], []);

        $results->record((int) $assessment['id'], $this->lecturerUser, [
            'student_id' => $student['student_id'],
            'marks' => 88,
        ]);

        $results->publish((int) $assessment['id'], $this->lecturerUser);

        $this->approval = $this->approvals->submit($this->sectionId, $this->lecturerUser);
    }

    public function testALecturerCannotSubmitAnotherLecturersSection(): void
    {
        $this->expectException(ApiException::class);

        $this->approvals->submit($this->sectionId, $this->otherLecturerUser);
    }

    public function testAnotherLecturersApprovalLooksMissingRatherThanForbidden(): void
    {
        try {
            $this->approvals->get((int) $this->approval['id'], $this->otherLecturerUser);

            $this->fail('A lecturer must not reach another lecturer approval.');
        } catch (ApiException $exception) {
            $this->assertSame(404, $exception->statusCode());
        }
    }

    public function testALecturerOnlySeesTheirOwnApprovalsInTheList(): void
    {
        $mine = $this->approvals->list($this->lecturerUser, null);
        $theirs = $this->approvals->list($this->otherLecturerUser, null);

        $this->assertCount(1, $mine);
        $this->assertCount(0, $theirs);
    }

    public function testACoordinatorSeesEveryApproval(): void
    {
        $this->assertCount(1, $this->approvals->list($this->coordinatorUser, null));
    }

    /**
     * The business rule says only coordinators approve, and a lecturer
     * approving their own grades would defeat the whole workflow. Role checks
     * live in the controller, so that is where this is asserted.
     */
    public function testEveryDecisionEndpointIsCoordinatorOnly(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/app/Controllers/GradeApprovalController.php'
        );

        foreach (['approve', 'reject', 'returnForRevision'] as $method) {
            $body = substr($source, (int) strpos($source, 'function ' . $method . '('), 200);

            $this->assertStringContainsString(
                "authenticateAs(['Coordinator'])",
                $body,
                $method . ' must be restricted to coordinators.'
            );
        }

        $submit = substr($source, (int) strpos($source, 'function store('), 200);

        $this->assertStringContainsString("authenticateAs(['Lecturer'])", $submit);
    }

    public function testAnApprovedRequestCannotBeReopenedByRejecting(): void
    {
        $this->approvals->approve((int) $this->approval['id'], $this->coordinatorUser, null);

        try {
            $this->approvals->reject(
                (int) $this->approval['id'],
                $this->coordinatorUser,
                'Actually no.'
            );

            $this->fail('An approved request must not be reopened.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public function testTheApprovalLogCannotBeRewritten(): void
    {
        $this->approvals->approve((int) $this->approval['id'], $this->coordinatorUser, 'Fine.');

        $before = (int) $this->scalar('SELECT COUNT(*) FROM GradeApprovalLog');

        try {
            $this->approvals->reject((int) $this->approval['id'], $this->coordinatorUser, 'No.');
        } catch (ApiException) {
            // expected
        }

        $after = (int) $this->scalar('SELECT COUNT(*) FROM GradeApprovalLog');

        $this->assertSame($before, $after);
        $this->assertGreaterThanOrEqual(3, $before);
    }
}
