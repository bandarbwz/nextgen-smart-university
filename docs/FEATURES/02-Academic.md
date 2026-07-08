# Academic Module

## Purpose

The Academic Module is the core of the NextGen Smart University Platform.

It manages all academic operations including students, lecturers, faculties, departments, academic programs, semesters, courses, sections, enrollments, class schedules, transcripts, GPA calculation, and graduation requirements.

Every academic process within the platform depends on this module.

---

# Objectives

- Manage students and lecturers.
- Organize faculties and departments.
- Manage academic programs.
- Manage semesters.
- Manage courses and prerequisites.
- Handle course registration.
- Build student schedules.
- Calculate GPA and CGPA.
- Store academic history.
- Support graduation requirements.

---

# Scope

This module manages all academic entities and academic workflows throughout the university.

It covers:

- Students
- Lecturers
- Coordinators
- Faculties
- Departments
- Programs
- Courses
- Sections
- Enrollments
- Class Schedules
- Academic Records
- GPA Calculation
- Graduation Requirements

---

# Actors

- Student
- Lecturer
- Coordinator
- Administrator

---

# Academic Structure

University

↓

Faculty

↓

Department

↓

Program

↓

Course

↓

Section

↓

Enrollment

↓

Student

---

# Database Tables

## Student

Purpose

Stores all student academic information.

### Columns

- id
- user_id
- student_number
- faculty_id
- department_id
- program_id
- advisor_id
- current_semester_id
- study_mode
- academic_level
- admission_date
- expected_graduation_date
- academic_status
- total_credit_hours
- completed_credit_hours
- current_gpa
- cumulative_gpa
- created_at
- updated_at

---

## Lecturer

Purpose

Stores lecturer information.

### Columns

- id
- user_id
- faculty_id
- department_id
- office
- specialization
- employment_status
- hire_date
- created_at
- updated_at

---

## Coordinator

Purpose

Stores coordinator information.

### Columns

- id
- lecturer_id
- department_id
- assigned_date

---

## Faculty

### Examples

- Faculty of Computing
- Faculty of Engineering
- Faculty of Business

### Columns

- id
- name
- description
- dean_name

---

## Department

### Examples

- Computer Science
- Software Engineering
- Artificial Intelligence

### Columns

- id
- faculty_id
- name
- description

---

## Program

### Examples

- Bachelor of Computer Science
- Bachelor of Software Engineering

### Columns

- id
- department_id
- name
- degree
- required_credit_hours

---

## Semester

### Columns

- id
- name
- academic_year
- start_date
- end_date
- registration_start
- registration_end
- current_semester
- status

---

## Course

### Columns

- id
- department_id
- program_id
- course_code
- course_name
- description
- credit_hours
- course_type
- level
- course_status

### Course Types

- Core
- Elective
- University Requirement
- Faculty Requirement

---

## CoursePrerequisite

### Columns

- id
- course_id
- prerequisite_course_id

---

## Section

### Columns

- id
- course_id
- lecturer_id
- semester_id
- section_number
- classroom
- building
- delivery_mode
- capacity
- registered_students
- waiting_list
- status

### Delivery Modes

- Physical
- Online
- Hybrid

---

## ClassSchedule

### Columns

- id
- section_id
- day_of_week
- start_time
- end_time
- room

---

## Enrollment

### Columns

- id
- student_id
- section_id
- registration_date
- approved_by
- approved_at
- enrollment_status
- final_grade
- grade_points

### Enrollment Status

- Pending
- Approved
- Rejected
- Dropped
- Withdrawn
- Completed

---

## Transcript

### Columns

- id
- student_id
- course_id
- semester_id
- grade
- grade_points
- earned_credit_hours

---

# Relationships

Faculty

↓

Department

↓

Program

↓

Course

↓

Section

↓

Enrollment

↓

Student

---

Course

↓

CoursePrerequisite

↓

Course

---

Lecturer

↓

Section

↓

Class Schedule

---

# Business Rules

## Students

- Cannot exceed maximum credit hours.
- Must satisfy prerequisites.
- Cannot register the same course twice unless repeating.
- Cannot register after the registration deadline.
- Cannot register in full sections unless joining the waiting list.
- Cannot register for courses with timetable conflicts.
- Cannot exceed credit hour limits without coordinator approval.

## Sections

- Capacity cannot be exceeded.
- Every section belongs to one semester.
- Every section has one lecturer.

## Courses

- Every course belongs to one department.
- Course code must be unique.
- Credit hours must be greater than zero.

## Programs

- Graduation requires completing all required credit hours.
- Graduation requires meeting the minimum GPA requirement.

---

# Validation Rules

## Student Number

- Required
- Unique

## Course Code

- Required
- Unique

## Credit Hours

- Positive integer

## GPA

- Between 0.00 and 4.00

## Semester

- Start date must be before end date.

## Registration

- Registration period must be open.

## Section Capacity

- Must be greater than zero.

---

# Academic Status

Student status:

- Active
- Suspended
- Graduated
- Withdrawn
- Deferred

---

# Registration Workflow

Student selects semester.

↓

System verifies registration period.

↓

System validates prerequisites.

↓

System checks timetable conflicts.

↓

System checks section capacity.

↓

Registration submitted.

↓

Coordinator approval (if required).

↓

Enrollment confirmed.

↓

Student added to section.

↓

Student added automatically to LMS.

↓

Student added automatically to Course Chat.

↓

Student schedule updated.

---

# Permissions

## Student

- View Courses
- Register Courses
- Drop Courses
- View Schedule
- View Transcript
- View GPA

## Lecturer

- View Assigned Courses
- View Student Lists

## Coordinator

- Approve Registration
- Manage Sections

## Administrator

- Full Academic Access

---

# Notifications

Students receive notifications for:

- Registration Approved
- Registration Rejected
- Course Added
- Course Dropped
- Schedule Updated
- Graduation Status Updated

---

# Security

- Students can only access their own academic records.
- Lecturers can only access assigned courses.
- Coordinators manage only their departments.
- Administrators have full academic access.

---

# Indexes

## Student

- student_number
- user_id

## Course

- course_code

## Section

- semester_id
- lecturer_id

## Enrollment

- student_id
- section_id

---

# Reports

Academic Reports

- Student Transcript
- GPA Report
- Enrollment Report
- Registration Report
- Graduation Report
- Department Statistics

---

# Performance

- Index searchable columns.
- Cache course catalog.
- Optimize enrollment queries.
- Optimize GPA calculations.

---

# API Mapping

GET /api/academic/student-profile

GET /api/academic/courses

GET /api/academic/sections

POST /api/academic/enrollments

DELETE /api/academic/enrollments/{id}

GET /api/academic/schedule

GET /api/academic/transcript

GET /api/academic/gpa

GET /api/academic/faculties

GET /api/academic/departments

---

# UI Pages

- Student Profile
- Course Catalog
- Course Registration
- My Schedule
- Transcript
- Academic Progress
- Graduation Status

---

# Dependencies

This module depends on:

- Authentication Module
- Notification Module

The following modules depend on this module:

- Attendance
- LMS
- Chat
- Finance
- AI Exam
- Student Portal

---

# Future Expansion

- Double Major
- Minor Programs
- Exchange Students
- Internship Tracking
- Graduation Project Management
- Academic Advising AI
- Degree Audit

---

# Notes

The Academic Module is the academic foundation of the NextGen Smart University Platform.

All academic features and services depend on this module.