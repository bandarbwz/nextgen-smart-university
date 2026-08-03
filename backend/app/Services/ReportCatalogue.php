<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Single source of truth for which roles may run which report. The export
 * endpoint checks against this same table, so exporting cannot reach data the
 * caller could not read directly.
 */
class ReportCatalogue
{
    private const REPORTS = [
        'academic.transcript' => [
            'name' => 'Student Transcript',
            'category' => 'Academic',
            'roles' => ['Student', 'Lecturer', 'Coordinator', 'Administrator'],
        ],
        'academic.gpa' => [
            'name' => 'GPA Report',
            'category' => 'Academic',
            'roles' => ['Student', 'Coordinator', 'Administrator'],
        ],
        'academic.enrolment' => [
            'name' => 'Course Enrolment',
            'category' => 'Academic',
            'roles' => ['Coordinator', 'Administrator'],
        ],
        'attendance.student' => [
            'name' => 'Student Attendance',
            'category' => 'Attendance',
            'roles' => ['Student', 'Lecturer', 'Coordinator', 'Administrator'],
        ],
        'attendance.daily' => [
            'name' => 'Daily Attendance',
            'category' => 'Attendance',
            'roles' => ['Coordinator', 'Administrator'],
        ],
        'attendance.monthly' => [
            'name' => 'Monthly Attendance',
            'category' => 'Attendance',
            'roles' => ['Coordinator', 'Administrator'],
        ],
        'assessment.grade-distribution' => [
            'name' => 'Grade Distribution',
            'category' => 'Assessment',
            'roles' => ['Lecturer', 'Coordinator', 'Administrator'],
        ],
        'finance.balances' => [
            'name' => 'Student Balances',
            'category' => 'Finance',
            'roles' => ['Administrator'],
        ],
        'finance.revenue' => [
            'name' => 'Revenue',
            'category' => 'Finance',
            'roles' => ['Administrator'],
        ],
        'finance.outstanding' => [
            'name' => 'Outstanding Invoices',
            'category' => 'Finance',
            'roles' => ['Administrator'],
        ],
        'food-court.sales' => [
            'name' => 'Restaurant Sales',
            'category' => 'Food Court',
            'roles' => ['Restaurant Owner', 'Administrator'],
        ],
        'system.users' => [
            'name' => 'User Statistics',
            'category' => 'System',
            'roles' => ['Administrator'],
        ],
        'system.logins' => [
            'name' => 'Login History',
            'category' => 'System',
            'roles' => ['Administrator'],
        ],
    ];

    public function availableTo(string $role): array
    {
        $available = [];

        foreach (self::REPORTS as $key => $report) {
            if ($this->allows($key, $role)) {
                $available[] = [
                    'key' => $key,
                    'name' => $report['name'],
                    'category' => $report['category'],
                ];
            }
        }

        return $available;
    }

    public function exists(string $key): bool
    {
        return isset(self::REPORTS[$key]);
    }

    public function allows(string $key, string $role): bool
    {
        if (!$this->exists($key)) {
            return false;
        }

        return $role === 'Administrator'
            || in_array($role, self::REPORTS[$key]['roles'], true);
    }

    public function guard(string $key, string $role): void
    {
        if (!$this->exists($key)) {
            throw new ApiException('Unknown report.', 404);
        }

        if (!$this->allows($key, $role)) {
            throw new ApiException('You do not have permission to run this report.', 403);
        }
    }

    public function name(string $key): string
    {
        return self::REPORTS[$key]['name'] ?? $key;
    }
}
