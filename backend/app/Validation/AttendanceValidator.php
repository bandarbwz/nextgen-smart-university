<?php

declare(strict_types=1);

namespace App\Validation;

class AttendanceValidator
{
    private const STATUSES = ['Present', 'Late', 'Absent', 'Excused', 'Online', 'Pending'];

    public function openSession(array $data): array
    {
        return (new Validator())
            ->required($data, 'section_id', 'Section')
            ->integer($data, 'section_id', 'Section')
            ->latitude($data, 'latitude', 'Latitude')
            ->longitude($data, 'longitude', 'Longitude')
            ->errors();
    }

    public function scan(array $data): array
    {
        return (new Validator())
            ->required($data, 'qr_token', 'QR token')
            ->latitude($data, 'latitude', 'Latitude')
            ->longitude($data, 'longitude', 'Longitude')
            ->errors();
    }

    public function manual(array $data): array
    {
        return (new Validator())
            ->required($data, 'student_id', 'Student')
            ->integer($data, 'student_id', 'Student')
            ->required($data, 'section_id', 'Section')
            ->integer($data, 'section_id', 'Section')
            ->required($data, 'attendance_date', 'Attendance date')
            ->date($data, 'attendance_date', 'Attendance date')
            ->required($data, 'attendance_status', 'Attendance status')
            ->inList($data, 'attendance_status', self::STATUSES, 'Attendance status')
            ->maxLength($data, 'remarks', 255, 'Remarks')
            ->errors();
    }

    public function update(array $data): array
    {
        return (new Validator())
            ->required($data, 'attendance_status', 'Attendance status')
            ->inList($data, 'attendance_status', self::STATUSES, 'Attendance status')
            ->maxLength($data, 'remarks', 255, 'Remarks')
            ->errors();
    }

    public function excuse(array $data): array
    {
        return (new Validator())
            ->required($data, 'attendance_id', 'Attendance record')
            ->integer($data, 'attendance_id', 'Attendance record')
            ->required($data, 'excuse_type', 'Excuse type')
            ->inList($data, 'excuse_type', ['Medical', 'Family', 'Official', 'Other'], 'Excuse type')
            ->required($data, 'reason', 'Reason')
            ->maxLength($data, 'reason', 500, 'Reason')
            ->errors();
    }

    public function review(array $data): array
    {
        return (new Validator())
            ->maxLength($data, 'review_note', 255, 'Review note')
            ->errors();
    }

    public function verifyFace(array $data): array
    {
        return (new Validator())
            ->required($data, 'image', 'Image')
            ->errors();
    }

    public function verifyLocation(array $data): array
    {
        return (new Validator())
            ->required($data, 'qr_token', 'QR token')
            ->required($data, 'latitude', 'Latitude')
            ->latitude($data, 'latitude', 'Latitude')
            ->required($data, 'longitude', 'Longitude')
            ->longitude($data, 'longitude', 'Longitude')
            ->errors();
    }
}
