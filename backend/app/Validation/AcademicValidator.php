<?php

declare(strict_types=1);

namespace App\Validation;

class AcademicValidator
{
    public function faculty(array $data): array
    {
        return (new Validator())
            ->required($data, 'name', 'Faculty name')
            ->maxLength($data, 'name', 255, 'Faculty name')
            ->maxLength($data, 'dean_name', 255, 'Dean name')
            ->errors();
    }

    public function department(array $data): array
    {
        return (new Validator())
            ->required($data, 'faculty_id', 'Faculty')
            ->integer($data, 'faculty_id', 'Faculty')
            ->required($data, 'name', 'Department name')
            ->maxLength($data, 'name', 255, 'Department name')
            ->errors();
    }

    public function program(array $data): array
    {
        return (new Validator())
            ->required($data, 'department_id', 'Department')
            ->integer($data, 'department_id', 'Department')
            ->required($data, 'name', 'Program name')
            ->maxLength($data, 'name', 255, 'Program name')
            ->required($data, 'degree', 'Degree')
            ->required($data, 'required_credit_hours', 'Required credit hours')
            ->positiveInteger($data, 'required_credit_hours', 'Required credit hours')
            ->errors();
    }

    public function semester(array $data): array
    {
        return (new Validator())
            ->required($data, 'name', 'Semester name')
            ->required($data, 'academic_year', 'Academic year')
            ->required($data, 'start_date', 'Start date')
            ->date($data, 'start_date', 'Start date')
            ->required($data, 'end_date', 'End date')
            ->date($data, 'end_date', 'End date')
            ->inList($data, 'status', ['upcoming', 'active', 'closed'], 'Status')
            ->errors();
    }

    public function course(array $data): array
    {
        return (new Validator())
            ->required($data, 'department_id', 'Department')
            ->integer($data, 'department_id', 'Department')
            ->required($data, 'course_code', 'Course code')
            ->maxLength($data, 'course_code', 20, 'Course code')
            ->required($data, 'course_name', 'Course name')
            ->maxLength($data, 'course_name', 255, 'Course name')
            ->required($data, 'credit_hours', 'Credit hours')
            ->positiveInteger($data, 'credit_hours', 'Credit hours')
            ->required($data, 'course_type', 'Course type')
            ->inList(
                $data,
                'course_type',
                ['Core', 'Elective', 'University Requirement', 'Faculty Requirement'],
                'Course type'
            )
            ->errors();
    }

    public function section(array $data): array
    {
        return (new Validator())
            ->required($data, 'course_id', 'Course')
            ->integer($data, 'course_id', 'Course')
            ->required($data, 'lecturer_id', 'Lecturer')
            ->integer($data, 'lecturer_id', 'Lecturer')
            ->required($data, 'semester_id', 'Semester')
            ->integer($data, 'semester_id', 'Semester')
            ->required($data, 'section_number', 'Section number')
            ->maxLength($data, 'section_number', 20, 'Section number')
            ->required($data, 'capacity', 'Capacity')
            ->positiveInteger($data, 'capacity', 'Capacity')
            ->inList($data, 'delivery_mode', ['Physical', 'Online', 'Hybrid'], 'Delivery mode')
            ->errors();
    }

    public function student(array $data): array
    {
        return (new Validator())
            ->required($data, 'user_id', 'User account')
            ->integer($data, 'user_id', 'User account')
            ->required($data, 'student_number', 'Student number')
            ->maxLength($data, 'student_number', 50, 'Student number')
            ->required($data, 'faculty_id', 'Faculty')
            ->integer($data, 'faculty_id', 'Faculty')
            ->required($data, 'department_id', 'Department')
            ->integer($data, 'department_id', 'Department')
            ->required($data, 'program_id', 'Program')
            ->integer($data, 'program_id', 'Program')
            ->required($data, 'admission_date', 'Admission date')
            ->date($data, 'admission_date', 'Admission date')
            ->inList($data, 'study_mode', ['full_time', 'part_time'], 'Study mode')
            ->errors();
    }

    public function lecturer(array $data): array
    {
        return (new Validator())
            ->required($data, 'user_id', 'User account')
            ->integer($data, 'user_id', 'User account')
            ->required($data, 'faculty_id', 'Faculty')
            ->integer($data, 'faculty_id', 'Faculty')
            ->required($data, 'department_id', 'Department')
            ->integer($data, 'department_id', 'Department')
            ->inList($data, 'employment_status', ['full_time', 'part_time', 'visiting'], 'Employment status')
            ->errors();
    }

    public function registration(array $data): array
    {
        return (new Validator())
            ->required($data, 'section_id', 'Section')
            ->integer($data, 'section_id', 'Section')
            ->errors();
    }

    public function drop(array $data): array
    {
        return (new Validator())
            ->required($data, 'enrollment_id', 'Enrollment')
            ->integer($data, 'enrollment_id', 'Enrollment')
            ->errors();
    }

    public function capacity(array $data): array
    {
        return (new Validator())
            ->required($data, 'capacity', 'Capacity')
            ->positiveInteger($data, 'capacity', 'Capacity')
            ->errors();
    }

    public function lecturerAssignment(array $data): array
    {
        return (new Validator())
            ->required($data, 'lecturer_id', 'Lecturer')
            ->integer($data, 'lecturer_id', 'Lecturer')
            ->errors();
    }
}
