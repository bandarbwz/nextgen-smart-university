<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\AssignmentService;
use App\Services\GradeService;
use App\Services\QuizService;
use Tests\TestCase;

class LmsRulesTest extends TestCase
{
    private AssignmentService $assignments;

    private QuizService $quizzes;

    private GradeService $grades;

    private array $structure;

    private array $student;

    private array $lecturer;

    private int $sectionId;

    private array $lecturerUser;

    private array $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assignments = new AssignmentService();
        $this->quizzes = new QuizService();
        $this->grades = new GradeService();

        $this->structure = $this->createAcademicStructure();
        $this->lecturer = $this->createLecturer($this->structure);
        $this->student = $this->createStudent($this->structure);

        $courseId = $this->createCourse($this->structure['department_id'], 'CS101');
        $this->sectionId = $this->createSection(
            $courseId,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id']
        );

        $this->enrol($this->student['student_id'], $this->sectionId, 'Approved');

        $this->lecturerUser = $this->actingAs($this->lecturer['user_id'], 'Lecturer');
        $this->studentUser = $this->actingAs($this->student['user_id'], 'Student');
    }

    public function testAnAssignmentCanBeCreatedWithLateSubmissionDisabled(): void
    {
        $assignment = $this->assignments->create($this->lecturerUser, $this->assignmentFields(false));

        $this->assertSame(
            0,
            (int) $assignment['allow_late_submission'],
            'PDO turns PHP false into an empty string unless the model normalises it, '
            . 'which previously made this insert fail outright.'
        );
    }

    public function testSubmittingAfterTheDeadlineIsRejectedWhenLateSubmissionIsOff(): void
    {
        $assignment = $this->assignments->create(
            $this->lecturerUser,
            $this->assignmentFields(false, '2026-01-01 00:00:00')
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('deadline');

        $this->assignments->submit((int) $assignment['id'], $this->studentUser, null, 'late work');
    }

    public function testSubmittingAfterTheDeadlineIsFlaggedLateWhenAllowed(): void
    {
        $assignment = $this->assignments->create(
            $this->lecturerUser,
            $this->assignmentFields(true, '2026-01-01 00:00:00')
        );

        $submission = $this->assignments->submit(
            (int) $assignment['id'],
            $this->studentUser,
            null,
            'late work'
        );

        $this->assertSame('Late', $submission['submission_status']);
    }

    public function testSubmittingBeforeTheDeadlineIsNotFlaggedLate(): void
    {
        $assignment = $this->assignments->create(
            $this->lecturerUser,
            $this->assignmentFields(false, '2099-01-01 00:00:00')
        );

        $submission = $this->assignments->submit(
            (int) $assignment['id'],
            $this->studentUser,
            null,
            'on time'
        );

        $this->assertSame('Submitted', $submission['submission_status']);
    }

    public function testAStudentCannotSubmitTwice(): void
    {
        $assignment = $this->assignments->create(
            $this->lecturerUser,
            $this->assignmentFields(false, '2099-01-01 00:00:00')
        );

        $this->assignments->submit((int) $assignment['id'], $this->studentUser, null, 'first');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('already submitted');

        $this->assignments->submit((int) $assignment['id'], $this->studentUser, null, 'second');
    }

    public function testAnEmptySubmissionIsRejected(): void
    {
        $assignment = $this->assignments->create(
            $this->lecturerUser,
            $this->assignmentFields(false, '2099-01-01 00:00:00')
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Attach a file or write a comment');

        $this->assignments->submit((int) $assignment['id'], $this->studentUser, null, '');
    }

    public function testMarksAboveTheAssignmentTotalAreRejected(): void
    {
        $assignment = $this->assignments->create(
            $this->lecturerUser,
            $this->assignmentFields(false, '2099-01-01 00:00:00')
        );

        $submission = $this->assignments->submit(
            (int) $assignment['id'],
            $this->studentUser,
            null,
            'work'
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('cannot exceed');

        $this->assignments->grade((int) $submission['id'], $this->lecturerUser, 101, null);
    }

    public function testGradingASubmissionStoresMarksAndMovesItToGraded(): void
    {
        $assignment = $this->assignments->create(
            $this->lecturerUser,
            $this->assignmentFields(false, '2099-01-01 00:00:00')
        );

        $submission = $this->assignments->submit(
            (int) $assignment['id'],
            $this->studentUser,
            null,
            'work'
        );

        $graded = $this->assignments->grade((int) $submission['id'], $this->lecturerUser, 85, 'Good');

        $this->assertSame('85.00', $graded['marks']);
        $this->assertSame('Graded', $graded['submission_status']);
    }

    public function testGradesAreHiddenFromStudentsUntilPublished(): void
    {
        $this->recordGrade(80);

        $this->assertCount(
            0,
            $this->grades->list($this->studentUser, null),
            'An unpublished grade must not be visible to the student.'
        );
    }

    public function testGradesBecomeVisibleOncePublished(): void
    {
        $this->recordGrade(80);

        $this->grades->publish($this->sectionId, $this->lecturerUser);

        $visible = $this->grades->list($this->studentUser, null);

        $this->assertCount(1, $visible);
        $this->assertSame('B+', $visible[0]['grade_letter']);
    }

    public function testAPublishedGradeCannotBeChanged(): void
    {
        $this->recordGrade(80);
        $this->grades->publish($this->sectionId, $this->lecturerUser);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('published');

        $this->recordGrade(95);
    }

    public function testAGradeAboveTheTotalIsRejected(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('cannot exceed');

        $this->recordGrade(120);
    }

    public function testObjectiveQuestionsAreMarkedAutomaticallyWhileEssaysWait(): void
    {
        $quiz = $this->createQuizWithMixedQuestions();

        $questions = $this->db
            ->query('SELECT id, question_type FROM QuizQuestion ORDER BY position')
            ->fetchAll();

        $answers = [
            (string) $questions[0]['id'] => 'B',
            (string) $questions[1]['id'] => 'True',
            (string) $questions[2]['id'] => 'Recursion is a function calling itself.',
        ];

        $submission = $this->quizzes->submit((int) $quiz['id'], $this->studentUser, $answers);

        $this->assertSame(
            '5.00',
            $submission['auto_scored_marks'],
            'Only the correct multiple choice answer should score automatically.'
        );
        $this->assertNull(
            $submission['score'],
            'The final score must stay unset while an essay awaits a human.'
        );
        $this->assertSame('Submitted', $submission['status']);
    }

    public function testAStudentCannotExceedTheAllowedQuizAttempts(): void
    {
        $quiz = $this->createQuizWithMixedQuestions();
        $questionId = (string) $this->scalar('SELECT id FROM QuizQuestion ORDER BY position LIMIT 1');

        $this->quizzes->submit((int) $quiz['id'], $this->studentUser, [$questionId => 'B']);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('all attempts');

        $this->quizzes->submit((int) $quiz['id'], $this->studentUser, [$questionId => 'B']);
    }

    public function testAQuizWithSubmissionsCannotBeEdited(): void
    {
        $quiz = $this->createQuizWithMixedQuestions();
        $questionId = (string) $this->scalar('SELECT id FROM QuizQuestion ORDER BY position LIMIT 1');

        $this->quizzes->submit((int) $quiz['id'], $this->studentUser, [$questionId => 'B']);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('already has submissions');

        $this->quizzes->update((int) $quiz['id'], $this->lecturerUser, [
            'section_id' => $this->sectionId,
            'title' => 'Changed',
            'duration' => 30,
            'start_time' => '2026-01-01 00:00:00',
            'end_time' => '2099-01-01 00:00:00',
        ], []);
    }

    public function testAQuizThatHasNotOpenedYetCannotBeSubmitted(): void
    {
        $quiz = $this->quizzes->create($this->lecturerUser, [
            'section_id' => $this->sectionId,
            'title' => 'Future Quiz',
            'duration' => 30,
            'start_time' => '2099-01-01 00:00:00',
            'end_time' => '2099-02-01 00:00:00',
            'attempts' => 1,
        ], [
            [
                'question' => 'Ready?',
                'question_type' => 'True / False',
                'marks' => 5,
                'correct_answer' => 'True',
            ],
        ]);

        $questionId = (string) $this->scalar('SELECT id FROM QuizQuestion LIMIT 1');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not open yet');

        $this->quizzes->submit((int) $quiz['id'], $this->studentUser, [$questionId => 'True']);
    }

    public function testQuizEndTimeMustBeAfterTheStartTime(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('after the start');

        $this->quizzes->create($this->lecturerUser, [
            'section_id' => $this->sectionId,
            'title' => 'Backwards',
            'duration' => 30,
            'start_time' => '2026-06-01 12:00:00',
            'end_time' => '2026-06-01 09:00:00',
            'attempts' => 1,
        ], [
            [
                'question' => 'Ready?',
                'question_type' => 'True / False',
                'marks' => 5,
                'correct_answer' => 'True',
            ],
        ]);
    }

    private function assignmentFields(bool $allowLate, string $dueDate = '2099-01-01 00:00:00'): array
    {
        return [
            'section_id' => $this->sectionId,
            'title' => 'Assignment One',
            'description' => null,
            'total_marks' => 100,
            'due_date' => $dueDate,
            'allow_late_submission' => $allowLate,
        ];
    }

    private function recordGrade(float $marks): array
    {
        return $this->grades->record($this->lecturerUser, [
            'student_id' => $this->student['student_id'],
            'section_id' => $this->sectionId,
            'assessment_type' => 'Midterm',
            'title' => 'Midterm Exam',
            'marks' => $marks,
            'total_marks' => 100,
        ]);
    }

    private function createQuizWithMixedQuestions(): array
    {
        return $this->quizzes->create($this->lecturerUser, [
            'section_id' => $this->sectionId,
            'title' => 'Week 1 Quiz',
            'duration' => 30,
            'start_time' => '2026-01-01 00:00:00',
            'end_time' => '2099-01-01 00:00:00',
            'attempts' => 1,
        ], [
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
            [
                'question' => 'PHP is compiled.',
                'question_type' => 'True / False',
                'marks' => 5,
                'correct_answer' => 'False',
            ],
            [
                'question' => 'Explain recursion.',
                'question_type' => 'Essay',
                'marks' => 10,
            ],
        ]);
    }
}
