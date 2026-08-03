<?php

declare(strict_types=1);

namespace Tests;

use PDO;
use RuntimeException;

class TestDatabase
{
    private const SCHEMA_FILES = [
        '01-authentication.sql',
        '02-academic.sql',
        '03-attendance.sql',
        '04-lms.sql',
        '05-calendar.sql',
        '06-chat.sql',
        '07-finance.sql',
        '08-food-court.sql',
        '09-reports-download-center.sql',
    ];

    private const SEED_FILES = [
        '01-authentication.sql',
    ];

    public static function rebuild(): void
    {
        $backendPath = dirname(__DIR__);
        $config = require $backendPath . '/config/database.php';
        $database = $config['database'];

        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d', $config['host'], $config['port']),
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $pdo->exec('DROP DATABASE IF EXISTS `' . $database . '`');
        $pdo->exec(
            'CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $pdo->exec('USE `' . $database . '`');

        $projectPath = dirname($backendPath);

        foreach (self::SCHEMA_FILES as $file) {
            $pdo->exec(self::read($projectPath . '/database/schema/' . $file));
        }

        foreach (self::SEED_FILES as $file) {
            $pdo->exec(self::read($projectPath . '/database/seed/' . $file));
        }
    }

    public static function truncateBusinessTables(PDO $pdo): void
    {
        $tables = [
            'DownloadHistory', 'DownloadFile', 'ReportHistory',
            'RestaurantReview', 'OrderPayment', 'OrderItem', 'FoodOrder',
            'MenuItem', 'FoodCategory', 'Restaurant',
            'Payment', 'Invoice', 'Scholarship', 'FinancialHold', 'TuitionFee',
            'MessageRead', 'MessageReaction', 'MessageAttachment', 'Message',
            'ChatMember', 'ChatRoom',
            'Reminder', 'CalendarEvent',
            'QuizAnswer', 'QuizSubmission', 'QuizOption', 'QuizQuestion', 'Quiz',
            'AssignmentSubmission', 'Assignment', 'CourseMaterial', 'Grade',
            'Announcement', 'Resource',
            'AttendanceExcuse', 'Attendance', 'QRSession',
            'Transcript', 'Enrollment', 'ClassSchedule', 'Section',
            'Coordinator', 'Student', 'Lecturer',
            'CoursePrerequisite', 'Course', 'Program', 'Department', 'Faculty',
            'Semester',
            'AuthenticationLog', 'UserSession', 'User',
        ];

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $table) {
            $pdo->exec('TRUNCATE TABLE `' . $table . '`');
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    private static function read(string $path): string
    {
        $sql = file_get_contents($path);

        if ($sql === false) {
            throw new RuntimeException('Cannot read SQL file: ' . $path);
        }

        $withoutCreate = preg_replace('/^\s*CREATE DATABASE[^;]+;/im', '', $sql) ?? $sql;

        return preg_replace('/^\s*USE\s+[^;]+;/im', '', $withoutCreate) ?? $withoutCreate;
    }
}
