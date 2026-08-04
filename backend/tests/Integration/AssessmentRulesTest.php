<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\AssessmentResultService;
use App\Services\AssessmentService;
use Tests\TestCase;

class AssessmentRulesTest extends TestCase
{
    private AssessmentService $assessments;

    private AssessmentResultService $results;

    private array $lecturerUser;

    private array $studentUser;

    private int $studentId;

    private int $outsiderId;

    private int $sectionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assessments = new AssessmentService();
        $this->results = new AssessmentResultService();

        $structure = $this->createAcademicStructure();
        $lecturer = $this->createLecturer($structure);
        $student = $this->createStudent($structure);
        $outsider = $this->createStudent($structure, 'outsider@test.edu', 'Outside Student');

        $courseId = $this->createCourse($structure['department_id'], 'CS101');
        $this->sectionId = $this->createSection($courseId, $lecturer['lecturer_id'], $structure['semester_id']);

        $this->enrol($student['student_id'], $this->sectionId);

        $this->studentId = $student['student_id'];
        $this->outsiderId = $outsider['student_id'];

        $this->lecturerUser = $this->actingAs($lecturer['user_id'], 'Lecturer');
        $this->studentUser = $this->actingAs($student['user_id'], 'Student');
    }

    public function testWeightsCannotExceedOneHundredPercent(): void
    {
        $this->createAssessment('Midterm', 'Midterm', 60);

        try {
            $this->createAssessment('Final', 'Final', 50);

            $this->fail('A section cannot be weighted past 100 per cent.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->statusCode());
        }
    }

    public function testWeightsCanReachExactlyOneHundredPercent(): void
    {
        $this->createAssessment('Midterm', 'Midterm', 40);
        $this->createAssessment('Final', 'Final', 60);

        $summary = $this->assessments->weightSummary($this->sectionId, $this->lecturerUser);

        $this->assertSame(100.0, $summary['weight_used']);
        $this->assertSame(0.0, $summary['weight_remaining']);
        $this->assertTrue($summary['is_complete']);
    }

    public function testEditingAnAssessmentDoesNotCountItsOwnOldWeight(): void
    {
        $midterm = $this->createAssessment('Midterm', 'Midterm', 40);
        $this->createAssessment('Final', 'Final', 50);

        $updated = $this->assessments->update((int) $midterm['id'], $this->lecturerUser, [
            'title' => 'Midterm',
            'assessment_type' => 'Midterm',
            'total_marks' => 100,
            'weight_percentage' => 50,
        ], []);

        $this->assertSame('50.00', $updated['weight_percentage']);
    }

    public function testARubricMustAddUpToTheAssessmentTotal(): void
    {
        try {
            $this->assessments->create($this->lecturerUser, [
                'section_id' => $this->sectionId,
                'title' => 'Project',
                'assessment_type' => 'Project',
                'total_marks' => 100,
                'weight_percentage' => 20,
            ], [
                ['criterion' => 'Design', 'maximum_marks' => 40],
                ['criterion' => 'Implementation', 'maximum_marks' => 40],
            ]);

            $this->fail('A rubric that does not add up must be refused.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->statusCode());
        }
    }

    public function testAValidRubricIsStored(): void
    {
        $assessment = $this->assessments->create($this->lecturerUser, [
            'section_id' => $this->sectionId,
            'title' => 'Project',
            'assessment_type' => 'Project',
            'total_marks' => 100,
            'weight_percentage' => 20,
        ], [
            ['criterion' => 'Design', 'maximum_marks' => 40],
            ['criterion' => 'Implementation', 'maximum_marks' => 60],
        ]);

        $this->assertCount(2, $assessment['rubric']);
        $this->assertSame('Design', $assessment['rubric'][0]['criterion']);
    }

    public function testMarksCannotExceedTheAssessmentTotal(): void
    {
        $assessment = $this->createAssessment('Midterm', 'Midterm', 30, 50);

        try {
            $this->results->record((int) $assessment['id'], $this->lecturerUser, [
                'student_id' => $this->studentId,
                'marks' => 51,
            ]);

            $this->fail('Marks above the total must be refused.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->statusCode());
        }
    }

    public function testAStudentWhoIsNotEnrolledCannotBeGraded(): void
    {
        $assessment = $this->createAssessment('Midterm', 'Midterm', 30, 50);

        try {
            $this->results->record((int) $assessment['id'], $this->lecturerUser, [
                'student_id' => $this->outsiderId,
                'marks' => 40,
            ]);

            $this->fail('A student who is not enrolled must not be graded.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public function testRecordingAResultDerivesThePercentageAndLetter(): void
    {
        $assessment = $this->createAssessment('Midterm', 'Midterm', 30, 50);

        $result = $this->results->record((int) $assessment['id'], $this->lecturerUser, [
            'student_id' => $this->studentId,
            'marks' => 45,
        ]);

        $this->assertSame('90.00', $result['percentage']);
        $this->assertSame('A', $result['grade']);
    }

    public function testAResultCanBeCorrectedBeforePublishing(): void
    {
        $assessment = $this->createAssessment('Midterm', 'Midterm', 30, 50);

        $this->results->record((int) $assessment['id'], $this->lecturerUser, [
            'student_id' => $this->studentId,
            'marks' => 20,
        ]);

        $corrected = $this->results->record((int) $assessment['id'], $this->lecturerUser, [
            'student_id' => $this->studentId,
            'marks' => 45,
        ]);

        $this->assertSame('45.00', $corrected['marks']);
        $this->assertCount(1, $this->results->forAssessment((int) $assessment['id'], $this->lecturerUser));
    }

    public function testAPublishedResultIsLocked(): void
    {
        $assessment = $this->createAssessment('Midterm', 'Midterm', 30, 50);

        $this->results->record((int) $assessment['id'], $this->lecturerUser, [
            'student_id' => $this->studentId,
            'marks' => 40,
        ]);

        $this->results->publish((int) $assessment['id'], $this->lecturerUser);

        try {
            $this->results->record((int) $assessment['id'], $this->lecturerUser, [
                'student_id' => $this->studentId,
                'marks' => 50,
            ]);

            $this->fail('A published result must not be silently changed.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->statusCode());
        }
    }

    public function testAStudentOnlySeesAResultOncePublished(): void
    {
        $assessment = $this->createAssessment('Midterm', 'Midterm', 30, 50);

        $this->results->record((int) $assessment['id'], $this->lecturerUser, [
            'student_id' => $this->studentId,
            'marks' => 40,
        ]);

        $before = $this->assessments->get((int) $assessment['id'], $this->studentUser);

        $this->results->publish((int) $assessment['id'], $this->lecturerUser);

        $after = $this->assessments->get((int) $assessment['id'], $this->studentUser);

        $this->assertNull($before['my_result']);
        $this->assertSame('40.00', $after['my_result']['marks']);
    }

    /**
     * The whole point of the module. A midterm worth thirty per cent counts for
     * thirty per cent of the course whatever its mark total happens to be.
     */
    public function testTheCourseResultIsWeightedNotAveraged(): void
    {
        $midterm = $this->createAssessment('Midterm', 'Midterm', 30, 50);
        $final = $this->createAssessment('Final', 'Final', 70, 200);

        // 40/50 is 80 per cent, 190/200 is 95 per cent.
        $this->gradeAndPublish($midterm, 40);
        $this->gradeAndPublish($final, 190);

        $result = $this->results->courseResult(
            $this->studentId,
            $this->sectionId,
            $this->lecturerUser
        );

        // 80 * 0.3 + 95 * 0.7 = 24 + 66.5 = 90.5, not the 87.5 a plain average gives.
        $this->assertSame(90.5, $result['weighted_percentage']);
        $this->assertSame('A', $result['grade_letter']);
        $this->assertTrue($result['is_complete']);
    }

    public function testTheCourseResultReportsIncompleteWeighting(): void
    {
        $midterm = $this->createAssessment('Midterm', 'Midterm', 30, 50);

        $this->gradeAndPublish($midterm, 40);

        $result = $this->results->courseResult(
            $this->studentId,
            $this->sectionId,
            $this->lecturerUser
        );

        $this->assertSame(30.0, $result['weight_counted']);
        $this->assertFalse($result['is_complete']);
    }

    public function testUnpublishedResultsDoNotCountTowardsTheCourseTotal(): void
    {
        $midterm = $this->createAssessment('Midterm', 'Midterm', 30, 50);
        $final = $this->createAssessment('Final', 'Final', 70, 200);

        $this->gradeAndPublish($midterm, 40);

        $this->results->record((int) $final['id'], $this->lecturerUser, [
            'student_id' => $this->studentId,
            'marks' => 190,
        ]);

        $result = $this->results->courseResult(
            $this->studentId,
            $this->sectionId,
            $this->lecturerUser
        );

        $this->assertSame(30.0, $result['weight_counted']);
        $this->assertCount(1, $result['components']);
    }

    public function testAnAssessmentWithResultsCannotBeDeleted(): void
    {
        $assessment = $this->createAssessment('Midterm', 'Midterm', 30, 50);

        $this->results->record((int) $assessment['id'], $this->lecturerUser, [
            'student_id' => $this->studentId,
            'marks' => 40,
        ]);

        $this->expectException(ApiException::class);

        $this->assessments->delete((int) $assessment['id'], $this->lecturerUser);
    }

    public function testADraftAssessmentIsHiddenFromStudents(): void
    {
        $assessment = $this->assessments->create($this->lecturerUser, [
            'section_id' => $this->sectionId,
            'title' => 'Not ready',
            'assessment_type' => 'Quiz',
            'total_marks' => 20,
            'weight_percentage' => 10,
            'status' => 'draft',
        ], []);

        try {
            $this->assessments->get((int) $assessment['id'], $this->studentUser);

            $this->fail('A draft assessment must not be visible to a student.');
        } catch (ApiException $exception) {
            $this->assertSame(404, $exception->statusCode());
        }
    }

    private function createAssessment(
        string $title,
        string $type,
        float $weight,
        float $totalMarks = 100
    ): array {
        return $this->assessments->create($this->lecturerUser, [
            'section_id' => $this->sectionId,
            'title' => $title,
            'assessment_type' => $type,
            'total_marks' => $totalMarks,
            'weight_percentage' => $weight,
            'status' => 'published',
        ], []);
    }

    private function gradeAndPublish(array $assessment, float $marks): void
    {
        $this->results->record((int) $assessment['id'], $this->lecturerUser, [
            'student_id' => $this->studentId,
            'marks' => $marks,
        ]);

        $this->results->publish((int) $assessment['id'], $this->lecturerUser);
    }
}
