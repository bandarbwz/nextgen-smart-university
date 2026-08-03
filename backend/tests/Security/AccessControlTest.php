<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Services\ApiException;
use App\Services\CalendarService;
use App\Services\CourseService;
use App\Services\MaterialService;
use App\Services\QuizService;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    private QuizService $quizzes;

    private MaterialService $materials;

    private CalendarService $calendar;

    private array $structure;

    private array $lecturer;

    private array $enrolledStudent;

    private array $outsideStudent;

    private int $sectionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quizzes = new QuizService();
        $this->materials = new MaterialService();
        $this->calendar = new CalendarService();

        $this->structure = $this->createAcademicStructure();
        $this->lecturer = $this->createLecturer($this->structure);
        $this->enrolledStudent = $this->createStudent($this->structure, 'enrolled@test.edu');
        $this->outsideStudent = $this->createStudent($this->structure, 'outside@test.edu');

        $courseId = $this->createCourse($this->structure['department_id'], 'CS101');
        $this->sectionId = $this->createSection(
            $courseId,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id']
        );

        $this->enrol($this->enrolledStudent['student_id'], $this->sectionId, 'Approved');
    }

    public function testQuizAnswersAreNeverSentToAStudent(): void
    {
        $quiz = $this->createQuiz();

        $asStudent = $this->quizzes->get(
            (int) $quiz['id'],
            $this->actingAs($this->enrolledStudent['user_id'], 'Student')
        );

        foreach ($asStudent['questions'] as $question) {
            $this->assertArrayNotHasKey(
                'correct_answer',
                $question,
                'A student payload must not contain the answer key under any circumstances.'
            );
        }

        $this->assertStringNotContainsString(
            'correct_answer',
            json_encode($asStudent, JSON_THROW_ON_ERROR),
            'The serialised response must not mention the answer key anywhere.'
        );
    }

    public function testQuizAnswersRemainVisibleToTheLecturer(): void
    {
        $quiz = $this->createQuiz();

        $asLecturer = $this->quizzes->get(
            (int) $quiz['id'],
            $this->actingAs($this->lecturer['user_id'], 'Lecturer')
        );

        $this->assertSame('B', $asLecturer['questions'][0]['correct_answer']);
    }

    public function testMultipleChoiceOptionsAreStillSentToTheStudent(): void
    {
        $quiz = $this->createQuiz();

        $asStudent = $this->quizzes->get(
            (int) $quiz['id'],
            $this->actingAs($this->enrolledStudent['user_id'], 'Student')
        );

        $this->assertCount(
            2,
            $asStudent['questions'][0]['options'],
            'Hiding the answer must not also hide the options the student has to choose from.'
        );
    }

    public function testAStudentCannotReadContentFromASectionTheyAreNotEnrolledIn(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('do not have access');

        $this->materials->list(
            $this->actingAs($this->outsideStudent['user_id'], 'Student'),
            $this->sectionId
        );
    }

    public function testAStudentsContentFeedOnlyContainsTheirOwnSections(): void
    {
        $otherCourse = $this->createCourse($this->structure['department_id'], 'CS999');
        $otherSection = $this->createSection(
            $otherCourse,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id'],
            30,
            '02'
        );

        $this->insertMaterial($this->sectionId, 'Visible notes');
        $this->insertMaterial($otherSection, 'Other course notes');

        $feed = $this->materials->list(
            $this->actingAs($this->enrolledStudent['user_id'], 'Student'),
            null
        );

        $titles = array_column($feed, 'title');

        $this->assertContains('Visible notes', $titles);
        $this->assertNotContains('Other course notes', $titles);
    }

    public function testHiddenMaterialIsInvisibleToStudentsButVisibleToStaff(): void
    {
        $this->insertMaterial($this->sectionId, 'Draft notes', 'hidden');
        $this->insertMaterial($this->sectionId, 'Published notes', 'visible');

        $studentTitles = array_column(
            $this->materials->list($this->actingAs($this->enrolledStudent['user_id'], 'Student'), null),
            'title'
        );

        $lecturerTitles = array_column(
            $this->materials->list($this->actingAs($this->lecturer['user_id'], 'Lecturer'), null),
            'title'
        );

        $this->assertNotContains('Draft notes', $studentTitles);
        $this->assertContains('Draft notes', $lecturerTitles);
    }

    public function testOneLecturerCannotPublishIntoAnotherLecturersSection(): void
    {
        $otherLecturer = $this->createLecturer($this->structure, 'other.lecturer@test.edu');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('your own sections');

        $this->quizzes->create(
            $this->actingAs($otherLecturer['user_id'], 'Lecturer'),
            [
                'section_id' => $this->sectionId,
                'title' => 'Injected quiz',
                'duration' => 30,
                'start_time' => '2026-01-01 00:00:00',
                'end_time' => '2099-01-01 00:00:00',
            ],
            [
                [
                    'question' => 'Whose section is this?',
                    'question_type' => 'True / False',
                    'marks' => 1,
                    'correct_answer' => 'False',
                ],
            ]
        );
    }

    public function testAnotherUsersCalendarEventReportsNotFoundRatherThanForbidden(): void
    {
        $event = $this->calendar->create($this->enrolledStudent['user_id'], [
            'title' => 'Private study',
            'start_datetime' => '2026-09-10 14:00:00',
            'end_datetime' => '2026-09-10 16:00:00',
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not found');

        $this->calendar->get((int) $event['id'], $this->outsideStudent['user_id']);
    }

    public function testCourseSearchIsNotVulnerableToSqlInjection(): void
    {
        $courses = new CourseService();

        $results = $courses->search("' OR '1'='1", null, null);

        $this->assertSame(
            [],
            $results,
            'The payload must be treated as a literal search term, not as SQL.'
        );

        $this->assertGreaterThan(
            0,
            (int) $this->scalar('SELECT COUNT(*) FROM Course'),
            'The Course table must still exist and be populated after the attempt.'
        );
    }

    public function testASearchTermContainingWildcardsIsTreatedAsText(): void
    {
        $courses = new CourseService();

        $this->assertSame([], $courses->search('%%%', null, null));
    }

    private function createQuiz(): array
    {
        return $this->quizzes->create(
            $this->actingAs($this->lecturer['user_id'], 'Lecturer'),
            [
                'section_id' => $this->sectionId,
                'title' => 'Week 1 Quiz',
                'duration' => 30,
                'start_time' => '2026-01-01 00:00:00',
                'end_time' => '2099-01-01 00:00:00',
                'attempts' => 1,
            ],
            [
                [
                    'question' => 'What does CPU stand for?',
                    'question_type' => 'Multiple Choice',
                    'marks' => 5,
                    'correct_answer' => 'B',
                    'options' => [
                        ['label' => 'A', 'text' => 'Central Program Unit'],
                        ['label' => 'B', 'text' => 'Central Processing Unit'],
                    ],
                ],
            ]
        );
    }

    private function insertMaterial(int $sectionId, string $title, string $visibility = 'visible'): void
    {
        $courseId = (int) $this->scalar('SELECT course_id FROM Section WHERE id = ?', [$sectionId]);

        $this->db->prepare(
            'INSERT INTO CourseMaterial
                (course_id, section_id, lecturer_id, title, file_path, file_type, original_name,
                 visibility, upload_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())'
        )->execute([
            $courseId,
            $sectionId,
            $this->lecturer['lecturer_id'],
            $title,
            'materials/test.pdf',
            'pdf',
            'test.pdf',
            $visibility,
        ]);
    }
}
