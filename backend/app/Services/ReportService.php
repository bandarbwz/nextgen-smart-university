<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Models\ReportHistory;

/**
 * Reads live data from the other modules rather than keeping its own copy.
 * Every report returns the same shape so the exporters can stay generic:
 * a title, a list of column keys, and a list of rows.
 */
class ReportService
{
    public function __construct(
        private readonly ReportCatalogue $catalogue = new ReportCatalogue(),
        private readonly ReportHistory $history = new ReportHistory(),
        private readonly FinanceService $finance = new FinanceService(),
        private readonly AttendanceService $attendance = new AttendanceService(),
        private readonly FoodCourtService $foodCourt = new FoodCourtService(),
        private readonly TranscriptService $transcripts = new TranscriptService(),
        private readonly GpaService $gpa = new GpaService(),
        private readonly StudentService $students = new StudentService(),
        private readonly CourseAccessService $access = new CourseAccessService()
    ) {
    }

    public function available(array $user): array
    {
        return $this->catalogue->availableTo($user['role']);
    }

    public function run(string $key, array $user, array $parameters, string $format = 'view'): array
    {
        $this->catalogue->guard($key, $user['role']);

        $report = match ($key) {
            'academic.transcript' => $this->transcript($user, $parameters),
            'academic.gpa' => $this->gpaReport($user, $parameters),
            'academic.enrolment' => $this->enrolment(),
            'attendance.student' => $this->studentAttendance($user, $parameters),
            'attendance.daily' => $this->dailyAttendance($parameters),
            'attendance.monthly' => $this->monthlyAttendance($parameters),
            'assessment.grade-distribution' => $this->gradeDistribution($user, $parameters),
            'finance.balances' => $this->financeBalances(),
            'finance.revenue' => $this->financeRevenue($parameters),
            'finance.outstanding' => $this->financeOutstanding(),
            'food-court.sales' => $this->foodCourtSales($user, $parameters),
            'system.users' => $this->systemUsers(),
            'system.logins' => $this->systemLogins(),
            default => throw new ApiException('Unknown report.', 404),
        };

        $this->history->record(
            $user['user_id'],
            $key,
            $format,
            $parameters,
            count($report['rows'])
        );

        return $report + ['key' => $key, 'generated_at' => gmdate('Y-m-d H:i:s')];
    }

    private function transcript(array $user, array $parameters): array
    {
        $studentId = $this->resolveStudentId($user, $parameters);
        $transcript = $this->transcripts->forStudent($studentId);

        $rows = [];

        foreach ($transcript['semesters'] as $semester) {
            foreach ($semester['courses'] as $course) {
                $rows[] = [
                    'semester' => $semester['semester'] . ' ' . $semester['academic_year'],
                    'course_code' => $course['course_code'],
                    'course_name' => $course['course_name'],
                    'credit_hours' => $course['credit_hours'],
                    'grade' => $course['grade'],
                    'grade_points' => $course['grade_points'],
                ];
            }
        }

        return [
            'title' => 'Transcript for ' . $transcript['student']['full_name'],
            'columns' => ['semester', 'course_code', 'course_name', 'credit_hours', 'grade', 'grade_points'],
            'rows' => $rows,
            'summary' => $transcript['summary'],
        ];
    }

    private function gpaReport(array $user, array $parameters): array
    {
        $studentId = $this->resolveStudentId($user, $parameters);

        $current = $this->gpa->currentGpa($studentId);
        $cumulative = $this->gpa->cumulativeGpa($studentId);

        return [
            'title' => 'GPA Report',
            'columns' => ['measure', 'value'],
            'rows' => [
                ['measure' => 'Semester', 'value' => $current['semester']],
                ['measure' => 'Semester GPA', 'value' => $current['gpa']],
                ['measure' => 'Cumulative GPA', 'value' => $cumulative['cgpa']],
                ['measure' => 'Credits earned', 'value' => $cumulative['completed_credit_hours']],
            ],
        ];
    }

    private function enrolment(): array
    {
        $rows = Database::connection()->query(
            "SELECT c.course_code, c.course_name, s.section_number,
                    s.capacity, s.registered_students,
                    SUM(e.enrollment_status = 'Approved') AS approved,
                    SUM(e.enrollment_status = 'Pending') AS pending
             FROM Section s
             JOIN Course c ON c.id = s.course_id
             LEFT JOIN Enrollment e ON e.section_id = s.id
             WHERE s.deleted_at IS NULL
             GROUP BY s.id, c.course_code, c.course_name, s.section_number,
                      s.capacity, s.registered_students
             ORDER BY c.course_code, s.section_number"
        )->fetchAll();

        return [
            'title' => 'Course Enrolment',
            'columns' => ['course_code', 'course_name', 'section_number', 'capacity',
                'registered_students', 'approved', 'pending'],
            'rows' => $rows,
        ];
    }

    private function studentAttendance(array $user, array $parameters): array
    {
        $studentId = $this->resolveStudentId($user, $parameters);

        return [
            'title' => 'Student Attendance',
            'columns' => ['course_code', 'course_name', 'total_sessions', 'attended',
                'excused', 'absent', 'attendance_rate'],
            'rows' => $this->attendance->statisticsForStudent($studentId),
        ];
    }

    private function dailyAttendance(array $parameters): array
    {
        $date = $parameters['date'] ?? gmdate('Y-m-d');

        return [
            'title' => 'Daily Attendance for ' . $date,
            'columns' => ['course_code', 'section_number', 'records', 'present', 'absent', 'late_count'],
            'rows' => $this->attendance->dailyReport($date),
        ];
    }

    private function monthlyAttendance(array $parameters): array
    {
        $year = (int) ($parameters['year'] ?? gmdate('Y'));
        $month = (int) ($parameters['month'] ?? gmdate('n'));

        if ($month < 1 || $month > 12) {
            throw new ApiException('The month must be between 1 and 12.', 422);
        }

        return [
            'title' => sprintf('Monthly Attendance %04d-%02d', $year, $month),
            'columns' => ['attendance_date', 'records', 'present', 'absent'],
            'rows' => $this->attendance->monthlyReport($year, $month),
        ];
    }

    private function gradeDistribution(array $user, array $parameters): array
    {
        $sectionId = isset($parameters['section_id']) ? (int) $parameters['section_id'] : null;

        if ($sectionId === null) {
            throw new ApiException('A section_id parameter is required.', 422);
        }

        $this->access->guardSectionVisible($sectionId, $user);

        $statement = Database::connection()->prepare(
            'SELECT grade_letter,
                    COUNT(*) AS students,
                    ROUND(AVG(marks / total_marks * 100), 1) AS average_percentage
             FROM Grade
             WHERE section_id = :section_id
             GROUP BY grade_letter
             ORDER BY average_percentage DESC'
        );

        $statement->execute(['section_id' => $sectionId]);

        return [
            'title' => 'Grade Distribution',
            'columns' => ['grade_letter', 'students', 'average_percentage'],
            'rows' => $statement->fetchAll(),
        ];
    }

    private function financeBalances(): array
    {
        return [
            'title' => 'Student Balances',
            'columns' => ['student_number', 'full_name', 'invoiced', 'paid', 'balance'],
            'rows' => $this->finance->balanceReport(),
        ];
    }

    private function financeRevenue(array $parameters): array
    {
        $semesterId = isset($parameters['semester_id']) ? (int) $parameters['semester_id'] : null;

        return [
            'title' => 'Revenue',
            'columns' => ['semester_name', 'academic_year', 'payment_count', 'total_collected'],
            'rows' => $this->finance->revenueReport($semesterId),
        ];
    }

    private function financeOutstanding(): array
    {
        return [
            'title' => 'Outstanding Invoices',
            'columns' => ['invoice_number', 'student_number', 'student_name',
                'total_amount', 'paid_amount', 'balance', 'due_date', 'status'],
            'rows' => $this->finance->outstandingReport(),
        ];
    }

    private function foodCourtSales(array $user, array $parameters): array
    {
        $restaurantId = isset($parameters['restaurant_id'])
            ? (int) $parameters['restaurant_id']
            : null;

        if ($restaurantId === null) {
            throw new ApiException('A restaurant_id parameter is required.', 422);
        }

        $report = $this->foodCourt->salesReport(
            $restaurantId,
            $user,
            $parameters['from'] ?? null,
            $parameters['to'] ?? null
        );

        return [
            'title' => 'Restaurant Sales',
            'columns' => ['sales_date', 'order_count', 'revenue'],
            'rows' => $report['sales'],
            'popular_items' => $report['popular_items'],
        ];
    }

    private function systemUsers(): array
    {
        $rows = Database::connection()->query(
            'SELECT r.name AS role,
                    COUNT(u.id) AS total,
                    SUM(u.status = \'active\') AS active,
                    SUM(u.email_verified = 1) AS verified
             FROM Role r
             LEFT JOIN User u ON u.role_id = r.id AND u.deleted_at IS NULL
             GROUP BY r.id, r.name
             ORDER BY r.name'
        )->fetchAll();

        return [
            'title' => 'User Statistics',
            'columns' => ['role', 'total', 'active', 'verified'],
            'rows' => $rows,
        ];
    }

    private function systemLogins(): array
    {
        $rows = Database::connection()->query(
            'SELECT l.action, l.status, l.ip_address, l.created_at,
                    COALESCE(u.full_name, \'unknown\') AS full_name
             FROM AuthenticationLog l
             LEFT JOIN User u ON u.id = l.user_id
             ORDER BY l.created_at DESC
             LIMIT 200'
        )->fetchAll();

        return [
            'title' => 'Login History',
            'columns' => ['created_at', 'full_name', 'action', 'status', 'ip_address'],
            'rows' => $rows,
        ];
    }

    /**
     * A student always gets their own data. A supplied student_id from a student
     * is ignored rather than honoured, so the parameter cannot widen access.
     */
    private function resolveStudentId(array $user, array $parameters): int
    {
        if ($user['role'] === 'Student') {
            return (int) $this->students->getByUserId($user['user_id'])['id'];
        }

        if (!isset($parameters['student_id'])) {
            throw new ApiException('A student_id parameter is required.', 422);
        }

        return (int) $parameters['student_id'];
    }
}
