<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ApiException;
use App\Services\ChatService;
use App\Services\CourseChatProvisioner;
use App\Services\EnrollmentService;
use App\Services\SectionService;
use Tests\TestCase;

class ChatMembershipTest extends TestCase
{
    private ChatService $chat;

    private CourseChatProvisioner $provisioner;

    private EnrollmentService $enrollments;

    private array $structure;

    private array $student;

    private array $lecturer;

    private int $courseId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chat = new ChatService();
        $this->provisioner = new CourseChatProvisioner();
        $this->enrollments = new EnrollmentService();

        $this->structure = $this->createAcademicStructure();
        $this->lecturer = $this->createLecturer($this->structure);
        $this->student = $this->createStudent($this->structure);
        $this->courseId = $this->createCourse($this->structure['department_id'], 'CS101');
    }

    public function testCreatingASectionProvisionsACourseRoomWithTheLecturer(): void
    {
        $sections = new SectionService();

        $section = $sections->create([
            'course_id' => $this->courseId,
            'lecturer_id' => $this->lecturer['lecturer_id'],
            'semester_id' => $this->structure['semester_id'],
            'section_number' => '01',
            'capacity' => 30,
            'status' => 'open',
        ], [], $this->lecturer['user_id']);

        $roomId = (int) $this->scalar(
            'SELECT id FROM ChatRoom WHERE section_id = ?',
            [$section['id']]
        );

        $this->assertGreaterThan(0, $roomId, 'A course room should be created with the section.');
        $this->assertSame(
            1,
            (int) $this->scalar('SELECT COUNT(*) FROM ChatMember WHERE room_id = ?', [$roomId]),
            'The lecturer should be the only member at creation time.'
        );
    }

    public function testApprovingAnEnrollmentAddsTheStudentToTheCourseRoom(): void
    {
        $sectionId = $this->sectionWithRoom();

        $enrollment = $this->enrollments->register($this->student['student_id'], $sectionId);

        $this->assertFalse(
            $this->isMember($sectionId, $this->student['user_id']),
            'A pending enrolment must not grant chat access.'
        );

        $this->enrollments->approve((int) $enrollment['id'], $this->lecturer['user_id']);

        $this->assertTrue(
            $this->isMember($sectionId, $this->student['user_id']),
            'Approval should add the student to the course room.'
        );
    }

    public function testDroppingACourseRemovesTheStudentFromTheRoom(): void
    {
        $sectionId = $this->sectionWithRoom();

        $enrollment = $this->enrollments->register($this->student['student_id'], $sectionId);
        $this->enrollments->approve((int) $enrollment['id'], $this->lecturer['user_id']);

        $this->enrollments->drop($this->student['student_id'], (int) $enrollment['id']);

        $this->assertFalse(
            $this->isMember($sectionId, $this->student['user_id']),
            'Dropping the course should remove chat access.'
        );
    }

    public function testANonMemberCannotReadARoom(): void
    {
        $sectionId = $this->sectionWithRoom();
        $roomId = (int) $this->scalar('SELECT id FROM ChatRoom WHERE section_id = ?', [$sectionId]);

        $outsider = $this->createStudent($this->structure, 'outsider@test.edu');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('do not have access');

        $this->chat->room($roomId, $this->actingAs($outsider['user_id'], 'Student'));
    }

    public function testAMessageCannotBeEmpty(): void
    {
        $roomId = $this->roomWithStudent();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('cannot be empty');

        $this->chat->send(
            $this->actingAs($this->student['user_id'], 'Student'),
            ['room_id' => $roomId, 'message' => '   '],
            null
        );
    }

    public function testAStudentCannotDeleteAnotherPersonsMessage(): void
    {
        $roomId = $this->roomWithStudent();

        $lecturerMessage = $this->chat->send(
            $this->actingAs($this->lecturer['user_id'], 'Lecturer'),
            ['room_id' => $roomId, 'message' => 'Welcome to the course.'],
            null
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('only delete your own');

        $this->chat->delete(
            (int) $lecturerMessage['id'],
            $this->actingAs($this->student['user_id'], 'Student')
        );
    }

    public function testDeletingAMessageKeepsTheAuditRowButHidesTheContent(): void
    {
        $roomId = $this->roomWithStudent();
        $studentUser = $this->actingAs($this->student['user_id'], 'Student');

        $message = $this->chat->send(
            $studentUser,
            ['room_id' => $roomId, 'message' => 'Please ignore this.'],
            null
        );

        $this->chat->delete((int) $message['id'], $studentUser);

        $this->assertSame(
            'Please ignore this.',
            $this->scalar('SELECT message FROM Message WHERE id = ?', [$message['id']]),
            'The stored row must survive for audit purposes.'
        );
        $this->assertSame(
            $this->student['user_id'],
            (int) $this->scalar('SELECT deleted_by FROM Message WHERE id = ?', [$message['id']])
        );

        $thread = $this->chat->messages($roomId, $studentUser, null, null, 50);
        $deleted = array_values(array_filter(
            $thread,
            static fn (array $row): bool => (int) $row['id'] === (int) $message['id']
        ));

        $this->assertNull(
            $deleted[0]['message'],
            'The API must withhold the content of a deleted message.'
        );
    }

    public function testPollingWithAnAfterCursorReturnsOnlyNewerMessages(): void
    {
        $roomId = $this->roomWithStudent();
        $studentUser = $this->actingAs($this->student['user_id'], 'Student');
        $lecturerUser = $this->actingAs($this->lecturer['user_id'], 'Lecturer');

        $first = $this->chat->send($lecturerUser, ['room_id' => $roomId, 'message' => 'One'], null);

        $this->assertSame(
            [],
            $this->chat->messages($roomId, $studentUser, (int) $first['id'], null, 50),
            'Nothing newer than the cursor should be returned.'
        );

        $second = $this->chat->send($lecturerUser, ['room_id' => $roomId, 'message' => 'Two'], null);

        $newer = $this->chat->messages($roomId, $studentUser, (int) $first['id'], null, 50);

        $this->assertCount(1, $newer);
        $this->assertSame((int) $second['id'], (int) $newer[0]['id']);
    }

    public function testOnlyAModeratorCanPinAMessage(): void
    {
        $roomId = $this->roomWithStudent();
        $studentUser = $this->actingAs($this->student['user_id'], 'Student');

        $message = $this->chat->send(
            $studentUser,
            ['room_id' => $roomId, 'message' => 'A question'],
            null
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('lecturers and moderators');

        $this->chat->setPinned((int) $message['id'], $studentUser, true);
    }

    public function testCourseRoomMembershipCannotBeLeftManually(): void
    {
        $roomId = $this->roomWithStudent();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('follows your enrolment');

        $this->chat->leave($roomId, $this->actingAs($this->student['user_id'], 'Student'));
    }

    private function sectionWithRoom(): int
    {
        $sectionId = $this->createSection(
            $this->courseId,
            $this->lecturer['lecturer_id'],
            $this->structure['semester_id']
        );

        $this->provisioner->ensureRoomForSection($sectionId, $this->lecturer['user_id']);

        return $sectionId;
    }

    private function roomWithStudent(): int
    {
        $sectionId = $this->sectionWithRoom();

        $enrollment = $this->enrollments->register($this->student['student_id'], $sectionId);
        $this->enrollments->approve((int) $enrollment['id'], $this->lecturer['user_id']);

        return (int) $this->scalar('SELECT id FROM ChatRoom WHERE section_id = ?', [$sectionId]);
    }

    private function isMember(int $sectionId, int $userId): bool
    {
        return (bool) $this->scalar(
            'SELECT COUNT(*) FROM ChatMember m
             JOIN ChatRoom r ON r.id = m.room_id
             WHERE r.section_id = ? AND m.user_id = ?',
            [$sectionId, $userId]
        );
    }
}
