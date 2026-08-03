<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected PDO $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = Database::connection();

        TestDatabase::truncateBusinessTables($this->db);
        $this->seedRoles();
    }

    protected function actingAs(int $userId, string $role, array $permissions = []): array
    {
        return [
            'user_id' => $userId,
            'session_id' => 1,
            'role' => $role,
            'permissions' => $permissions,
        ];
    }

    protected function createUser(string $role, string $email, string $fullName): int
    {
        $roleId = $this->scalar('SELECT id FROM Role WHERE name = ?', [$role]);

        $statement = $this->db->prepare(
            'INSERT INTO User (role_id, full_name, university_id, email, password, status, email_verified)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        $statement->execute([
            $roleId,
            $fullName,
            strtoupper(substr($role, 0, 3)) . random_int(1000, 9999),
            $email,
            password_hash('Password123!', PASSWORD_BCRYPT),
            'active',
            1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    protected function createAcademicStructure(): array
    {
        $this->db->exec("INSERT INTO Faculty (name) VALUES ('Faculty of Computing')");
        $facultyId = (int) $this->db->lastInsertId();

        $this->db->exec(
            "INSERT INTO Department (faculty_id, name) VALUES ($facultyId, 'Computer Science')"
        );
        $departmentId = (int) $this->db->lastInsertId();

        $this->db->exec(
            "INSERT INTO Program (department_id, name, degree, required_credit_hours)
             VALUES ($departmentId, 'BSc Computer Science', 'Bachelor', 132)"
        );
        $programId = (int) $this->db->lastInsertId();

        $this->db->exec(
            "INSERT INTO Semester
                (name, academic_year, start_date, end_date, registration_start, registration_end,
                 current_semester, status)
             VALUES
                ('Semester 1', '2026/2027', '2026-09-01', '2027-01-15',
                 DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY),
                 TRUE, 'active')"
        );
        $semesterId = (int) $this->db->lastInsertId();

        return [
            'faculty_id' => $facultyId,
            'department_id' => $departmentId,
            'program_id' => $programId,
            'semester_id' => $semesterId,
        ];
    }

    protected function createCourse(
        int $departmentId,
        string $code,
        string $name = 'Test Course',
        int $creditHours = 3
    ): int {
        $statement = $this->db->prepare(
            'INSERT INTO Course (department_id, course_code, course_name, credit_hours, course_type)
             VALUES (?, ?, ?, ?, ?)'
        );

        $statement->execute([$departmentId, $code, $name, $creditHours, 'Core']);

        return (int) $this->db->lastInsertId();
    }

    protected function createLecturer(array $structure, string $email = 'lecturer@test.edu'): array
    {
        $userId = $this->createUser('Lecturer', $email, 'Test Lecturer');

        $statement = $this->db->prepare(
            'INSERT INTO Lecturer (user_id, faculty_id, department_id) VALUES (?, ?, ?)'
        );

        $statement->execute([$userId, $structure['faculty_id'], $structure['department_id']]);

        return ['user_id' => $userId, 'lecturer_id' => (int) $this->db->lastInsertId()];
    }

    protected function createStudent(
        array $structure,
        string $email = 'student@test.edu',
        string $fullName = 'Test Student'
    ): array {
        $userId = $this->createUser('Student', $email, $fullName);

        $statement = $this->db->prepare(
            'INSERT INTO Student
                (user_id, student_number, faculty_id, department_id, program_id, admission_date)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $statement->execute([
            $userId,
            'STU' . random_int(10000, 99999),
            $structure['faculty_id'],
            $structure['department_id'],
            $structure['program_id'],
            '2026-09-01',
        ]);

        return ['user_id' => $userId, 'student_id' => (int) $this->db->lastInsertId()];
    }

    protected function createSection(
        int $courseId,
        int $lecturerId,
        int $semesterId,
        int $capacity = 30,
        string $sectionNumber = '01'
    ): int {
        $statement = $this->db->prepare(
            'INSERT INTO Section (course_id, lecturer_id, semester_id, section_number, capacity, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $statement->execute([$courseId, $lecturerId, $semesterId, $sectionNumber, $capacity, 'open']);

        return (int) $this->db->lastInsertId();
    }

    protected function addSchedule(
        int $sectionId,
        string $day,
        string $start,
        string $end
    ): int {
        $statement = $this->db->prepare(
            'INSERT INTO ClassSchedule (section_id, day_of_week, start_time, end_time)
             VALUES (?, ?, ?, ?)'
        );

        $statement->execute([$sectionId, $day, $start, $end]);

        return (int) $this->db->lastInsertId();
    }

    protected function enrol(int $studentId, int $sectionId, string $status = 'Approved'): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO Enrollment (student_id, section_id, registration_date, enrollment_status)
             VALUES (?, ?, UTC_TIMESTAMP(), ?)'
        );

        $statement->execute([$studentId, $sectionId, $status]);

        return (int) $this->db->lastInsertId();
    }

    protected function scalar(string $sql, array $parameters = []): mixed
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn();
    }

    private function seedRoles(): void
    {
        $roles = ['Student', 'Lecturer', 'Coordinator', 'Administrator', 'Restaurant Owner', 'STAD Staff'];

        $statement = $this->db->prepare('INSERT IGNORE INTO Role (name) VALUES (?)');

        foreach ($roles as $role) {
            $statement->execute([$role]);
        }
    }
}
