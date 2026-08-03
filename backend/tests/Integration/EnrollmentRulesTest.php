<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\EnrollmentService;
use Tests\TestCase;

class EnrollmentRulesTest extends TestCase
{
    private EnrollmentService $enrollments;

    private array $structure;

    private array $student;

    private array $lecturer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enrollments = new EnrollmentService();
        $this->structure = $this->createAcademicStructure();
        $this->lecturer = $this->createLecturer($this->structure);
        $this->student = $this->createStudent($this->structure);
    }

    public function testStudentCanRegisterForAnOpenSection(): void
    {
        $sectionId = $this->openSection('CS101');

        $enrollment = $this->enrollments->register($this->student['student_id'], $sectionId);

        $this->assertSame('Pending', $enrollment['enrollment_status']);
        $this->assertSame(
            1,
            (int) $this->scalar('SELECT registered_students FROM Section WHERE id = ?', [$sectionId]),
            'Registering must increment the seat count.'
        );
    }

    public function testRegisteringTwiceForTheSameCourseIsRejected(): void
    {
        $sectionId = $this->openSection('CS101');

        $this->enrollments->register($this->student['student_id'], $sectionId);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('already registered');

        $this->enrollments->register($this->student['student_id'], $sectionId);
    }

    public function testRegistrationIsRejectedWhenPrerequisitesAreNotCompleted(): void
    {
        $basicsId = $this->createCourse($this->structure['department_id'], 'CS101');
        $advancedId = $this->createCourse($this->structure['department_id'], 'CS201');

        $this->db->prepare(
            'INSERT INTO CoursePrerequisite (course_id, prerequisite_course_id) VALUES (?, ?)'
        )->execute([$advancedId, $basicsId]);

        $sectionId = $this->createSection(
            $advancedId,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id']
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('prerequisites');

        $this->enrollments->register($this->student['student_id'], $sectionId);
    }

    public function testRegistrationSucceedsOncePrerequisiteIsCompleted(): void
    {
        $basicsId = $this->createCourse($this->structure['department_id'], 'CS101');
        $advancedId = $this->createCourse($this->structure['department_id'], 'CS201');

        $this->db->prepare(
            'INSERT INTO CoursePrerequisite (course_id, prerequisite_course_id) VALUES (?, ?)'
        )->execute([$advancedId, $basicsId]);

        $basicsSection = $this->createSection(
            $basicsId,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id']
        );
        $this->enrol($this->student['student_id'], $basicsSection, 'Completed');

        $advancedSection = $this->createSection(
            $advancedId,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id'],
            30,
            '02'
        );

        $enrollment = $this->enrollments->register($this->student['student_id'], $advancedSection);

        $this->assertSame('Pending', $enrollment['enrollment_status']);
    }

    public function testRegistrationIsRejectedWhenTheSectionIsFull(): void
    {
        $courseId = $this->createCourse($this->structure['department_id'], 'CS101');
        $sectionId = $this->createSection(
            $courseId,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id'],
            1
        );

        $other = $this->createStudent($this->structure, 'other.student@test.edu');
        $this->enrollments->register($other['student_id'], $sectionId);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('full');

        $this->enrollments->register($this->student['student_id'], $sectionId);
    }

    public function testRegistrationIsRejectedWhenItWouldExceedTheCreditLimit(): void
    {
        for ($index = 1; $index <= 7; $index++) {
            $courseId = $this->createCourse($this->structure['department_id'], 'CS10' . $index);
            $sectionId = $this->createSection(
                $courseId,
                $this->lecturer['lecturer_id'],
                $this->structure['semester_id'],
                30,
                (string) $index
            );

            $this->enrol($this->student['student_id'], $sectionId, 'Approved');
        }

        $extraCourse = $this->createCourse($this->structure['department_id'], 'CS999');
        $extraSection = $this->createSection(
            $extraCourse,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id'],
            30,
            '99'
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('credit hours');

        $this->enrollments->register($this->student['student_id'], $extraSection);
    }

    public function testRegistrationIsRejectedWhenTheTimetableClashes(): void
    {
        $morningCourse = $this->createCourse($this->structure['department_id'], 'CS101');
        $morningSection = $this->createSection(
            $morningCourse,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id']
        );
        $this->addSchedule($morningSection, 'Monday', '09:00:00', '11:00:00');
        $this->enrol($this->student['student_id'], $morningSection, 'Approved');

        $overlappingCourse = $this->createCourse($this->structure['department_id'], 'CS210');
        $overlappingSection = $this->createSection(
            $overlappingCourse,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id'],
            30,
            '02'
        );
        $this->addSchedule($overlappingSection, 'Monday', '10:00:00', '12:00:00');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('clashes');

        $this->enrollments->register($this->student['student_id'], $overlappingSection);
    }

    public function testBackToBackClassesOnTheSameDayDoNotCountAsAClash(): void
    {
        $firstCourse = $this->createCourse($this->structure['department_id'], 'CS101');
        $firstSection = $this->createSection(
            $firstCourse,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id']
        );
        $this->addSchedule($firstSection, 'Monday', '09:00:00', '11:00:00');
        $this->enrol($this->student['student_id'], $firstSection, 'Approved');

        $secondCourse = $this->createCourse($this->structure['department_id'], 'CS210');
        $secondSection = $this->createSection(
            $secondCourse,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id'],
            30,
            '02'
        );
        $this->addSchedule($secondSection, 'Monday', '11:00:00', '13:00:00');

        $enrollment = $this->enrollments->register($this->student['student_id'], $secondSection);

        $this->assertSame(
            'Pending',
            $enrollment['enrollment_status'],
            'A class starting exactly when another ends is not an overlap.'
        );
    }

    public function testSameTimeOnADifferentDayIsNotAClash(): void
    {
        $mondayCourse = $this->createCourse($this->structure['department_id'], 'CS101');
        $mondaySection = $this->createSection(
            $mondayCourse,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id']
        );
        $this->addSchedule($mondaySection, 'Monday', '09:00:00', '11:00:00');
        $this->enrol($this->student['student_id'], $mondaySection, 'Approved');

        $tuesdayCourse = $this->createCourse($this->structure['department_id'], 'CS210');
        $tuesdaySection = $this->createSection(
            $tuesdayCourse,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id'],
            30,
            '02'
        );
        $this->addSchedule($tuesdaySection, 'Tuesday', '09:00:00', '11:00:00');

        $enrollment = $this->enrollments->register($this->student['student_id'], $tuesdaySection);

        $this->assertSame('Pending', $enrollment['enrollment_status']);
    }

    public function testRegistrationIsRejectedWhenTheRegistrationPeriodIsClosed(): void
    {
        $this->db->exec(
            'UPDATE Semester SET registration_end = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)'
        );

        $sectionId = $this->openSection('CS101');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('registration period is closed');

        $this->enrollments->register($this->student['student_id'], $sectionId);
    }

    public function testDroppingACourseReleasesTheSeat(): void
    {
        $sectionId = $this->openSection('CS101');

        $enrollment = $this->enrollments->register($this->student['student_id'], $sectionId);

        $this->enrollments->drop($this->student['student_id'], (int) $enrollment['id']);

        $this->assertSame(
            'Dropped',
            $this->scalar('SELECT enrollment_status FROM Enrollment WHERE id = ?', [$enrollment['id']])
        );
        $this->assertSame(
            0,
            (int) $this->scalar('SELECT registered_students FROM Section WHERE id = ?', [$sectionId])
        );
    }

    public function testAStudentCannotDropAnotherStudentsEnrollment(): void
    {
        $sectionId = $this->openSection('CS101');
        $enrollment = $this->enrollments->register($this->student['student_id'], $sectionId);

        $intruder = $this->createStudent($this->structure, 'intruder@test.edu');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not found');

        $this->enrollments->drop($intruder['student_id'], (int) $enrollment['id']);
    }

    public function testRejectingAnEnrollmentReleasesTheSeat(): void
    {
        $sectionId = $this->openSection('CS101');
        $enrollment = $this->enrollments->register($this->student['student_id'], $sectionId);

        $this->enrollments->reject((int) $enrollment['id'], $this->lecturer['user_id']);

        $this->assertSame(
            0,
            (int) $this->scalar('SELECT registered_students FROM Section WHERE id = ?', [$sectionId])
        );
    }

    public function testAnEnrollmentCannotBeReviewedTwice(): void
    {
        $sectionId = $this->openSection('CS101');
        $enrollment = $this->enrollments->register($this->student['student_id'], $sectionId);

        $this->enrollments->approve((int) $enrollment['id'], $this->lecturer['user_id']);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('already been reviewed');

        $this->enrollments->approve((int) $enrollment['id'], $this->lecturer['user_id']);
    }

    private function openSection(string $courseCode): int
    {
        $courseId = $this->createCourse($this->structure['department_id'], $courseCode);

        return $this->createSection(
            $courseId,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id']
        );
    }
}
