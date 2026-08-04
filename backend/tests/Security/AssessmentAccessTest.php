<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Services\ApiException;
use App\Services\AssessmentResultService;
use App\Services\AssessmentService;
use Tests\TestCase;

class AssessmentAccessTest extends TestCase
{
    private AssessmentService $assessments;

    private AssessmentResultService $results;

    private array $lecturerUser;

    private array $otherLecturerUser;

    private array $studentUser;

    private array $classmateUser;

    private int $studentId;

    private int $classmateId;

    private int $sectionId;

    private array $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assessments = new AssessmentService();
        $this->results = new AssessmentResultService();

        $structure = $this->createAcademicStructure();
        $lecturer = $this->createLecturer($structure);
        $otherLecturer = $this->createLecturer($structure, 'other@test.edu');
        $student = $this->createStudent($structure);
        $classmate = $this->createStudent($structure, 'classmate@test.edu', 'Classmate');

        $courseId = $this->createCourse($structure['department_id'], 'CS101');
        $this->sectionId = $this->createSection($courseId, $lecturer['lecturer_id'], $structure['semester_id']);

        $this->enrol($student['student_id'], $this->sectionId);
        $this->enrol($classmate['student_id'], $this->sectionId);

        $this->studentId = $student['student_id'];
        $this->classmateId = $classmate['student_id'];

        $this->lecturerUser = $this->actingAs($lecturer['user_id'], 'Lecturer');
        $this->otherLecturerUser = $this->actingAs($otherLecturer['user_id'], 'Lecturer');
        $this->studentUser = $this->actingAs($student['user_id'], 'Student');
        $this->classmateUser = $this->actingAs($classmate['user_id'], 'Student');

        $this->assessment = $this->assessments->create($this->lecturerUser, [
            'section_id' => $this->sectionId,
            'title' => 'Midterm',
            'assessment_type' => 'Midterm',
            'total_marks' => 50,
            'weight_percentage' => 100,
            'status' => 'published',
        ], []);
    }

    public function testALecturerCannotGradeAnotherLecturersAssessment(): void
    {
        $this->expectException(ApiException::class);

        $this->results->record((int) $this->assessment['id'], $this->otherLecturerUser, [
            'student_id' => $this->studentId,
            'marks' => 40,
        ]);
    }

    public function testALecturerCannotReadAnotherLecturersResults(): void
    {
        $this->expectException(ApiException::class);

        $this->results->forAssessment((int) $this->assessment['id'], $this->otherLecturerUser);
    }

    public function testALecturerCannotPublishAnotherLecturersAssessment(): void
    {
        $this->expectException(ApiException::class);

        $this->results->publish((int) $this->assessment['id'], $this->otherLecturerUser);
    }

    public function testAStudentNeverSeesAnotherStudentsMarksInTheAssessment(): void
    {
        $this->results->record((int) $this->assessment['id'], $this->lecturerUser, [
            'student_id' => $this->classmateId,
            'marks' => 49,
        ]);

        $this->results->publish((int) $this->assessment['id'], $this->lecturerUser);

        $seen = $this->assessments->get((int) $this->assessment['id'], $this->studentUser);

        $this->assertArrayNotHasKey('results', $seen);
        $this->assertArrayNotHasKey('statistics', $seen);
        $this->assertStringNotContainsString('49.00', json_encode($seen));
    }

    public function testALecturerDoesSeeTheWholeCohort(): void
    {
        $this->results->record((int) $this->assessment['id'], $this->lecturerUser, [
            'student_id' => $this->classmateId,
            'marks' => 49,
        ]);

        $seen = $this->assessments->get((int) $this->assessment['id'], $this->lecturerUser);

        $this->assertArrayHasKey('results', $seen);
        $this->assertCount(1, $seen['results']);
    }

    public function testAStudentCannotReadAnotherStudentsCourseResult(): void
    {
        try {
            $this->results->courseResult($this->classmateId, $this->sectionId, $this->studentUser);

            $this->fail('A student must not read another student course result.');
        } catch (ApiException $exception) {
            $this->assertSame(403, $exception->statusCode());
        }
    }

    public function testAStudentCanReadTheirOwnCourseResult(): void
    {
        $result = $this->results->courseResult(
            $this->studentId,
            $this->sectionId,
            $this->studentUser
        );

        $this->assertSame($this->studentId, $result['student_id']);
    }

    public function testAStudentCannotReachAssessmentsForASectionTheyAreNotIn(): void
    {
        $outsider = $this->createStudent(
            ['faculty_id' => 1, 'department_id' => 1, 'program_id' => 1],
            'outsider@test.edu',
            'Outside Student'
        );

        $outsiderUser = $this->actingAs($outsider['user_id'], 'Student');

        $this->expectException(ApiException::class);

        $this->assessments->get((int) $this->assessment['id'], $outsiderUser);
    }

    public function testAStudentCannotCreateAnAssessment(): void
    {
        $this->expectException(ApiException::class);

        $this->assessments->create($this->studentUser, [
            'section_id' => $this->sectionId,
            'title' => 'Free marks',
            'assessment_type' => 'Quiz',
            'total_marks' => 10,
            'weight_percentage' => 0,
        ], []);
    }
}
