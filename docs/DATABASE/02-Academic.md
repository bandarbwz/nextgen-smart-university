# Academic Module

## Purpose

The Academic Module is the core of the NextGen Smart University Platform.

It manages all academic operations including students, lecturers, faculties, departments, academic programs, courses, sections, enrollment, class schedules, transcripts, and graduation requirements.

Every academic process in the system depends on this module.

---

# Objectives

- Manage students and lecturers.
- Organize faculties and departments.
- Manage academic programs.
- Manage courses and prerequisites.
- Handle course registration.
- Build student schedules.
- Calculate GPA and CGPA.
- Store academic history.
- Support graduation requirements.

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

Student Enrollment

---

# Database Tables

## Student

Purpose

Stores all student academic information.

Columns

- id
- user_id
- student_number
- faculty_id
- department_id
- program_id
- advisor_id
- current_semester_id
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

Columns

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

Columns

- id
- lecturer_id
- department_id
- assigned_date

---

## Faculty

Examples

- Faculty of Computing
- Faculty of Engineering
- Faculty of Business

Columns

- id
- name
- description
- dean_name

---

## Department

Examples

- Computer Science
- Software Engineering
- Artificial Intelligence

Columns

- id
- faculty_id
- name
- description

---

## Program

Examples

- Bachelor of Computer Science
- Bachelor of Software Engineering

Columns

- id
- department_id
- name
- degree
- required_credit_hours

---

## Semester

Examples

- Fall 2026
- Spring 2027
- Summer 2027

Columns

- id
- name
- academic_year
- start_date
- end_date
- registration_start
- registration_end
- status

---

## Course

Columns

- id
- department_id
- program_id
- course_code
- course_name
- description
- credit_hours
- course_type
- level

Course Types

- Core
- Elective
- University Requirement
- Faculty Requirement

---

## CoursePrerequisite

Purpose

Stores prerequisite relationships.

Columns

- id
- course_id
- prerequisite_course_id

---

## Section

Purpose

Represents one offering of a course.

Columns

- id
- course_id
- lecturer_id
- semester_id
- section_number
- classroom
- building
- capacity
- registered_students
- waiting_list
- status

---

## ClassSchedule

Columns

- id
- section_id
- day_of_week
- start_time
- end_time
- room

---

## Enrollment

Purpose

Stores student registrations.

Columns

- id
- student_id
- section_id
- registration_date
- enrollment_status
- final_grade
- grade_points

Enrollment Status

- Pending
- Approved
- Rejected
- Dropped
- Withdrawn
- Completed

---

## Transcript

Purpose

Stores completed courses.

Columns

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

Students

- Cannot exceed maximum credit hours.
- Must satisfy prerequisites.
- Cannot register the same course twice unless repeating.
- Cannot register after the registration deadline.
- Cannot register in full sections unless joining the waiting list.

Sections

- Capacity cannot be exceeded.
- Every section belongs to one semester.
- Every section has one lecturer.

Courses

- Every course belongs to one department.
- Credit hours cannot be negative.
- Course code must be unique.

Programs

- Graduation requires completing all required credit hours.
- Required GPA must meet university policy.

---

# Validation Rules

Student Number

- Required
- Unique

Course Code

- Required
- Unique

Credit Hours

- Positive integer

GPA

- Between 0.00 and 4.00

Semester Dates

- Start date must be before end date.

---

# Academic Status

Student status can be:

- Active
- Suspended
- Graduated
- Withdrawn
- Deferred

---

# Registration Workflow

Student selects semester.

↓

System checks registration period.

↓

System validates prerequisites.

↓

System checks timetable conflicts.

↓

System checks section capacity.

↓

Registration is submitted.

↓

Coordinator approval (if required).

↓

Enrollment confirmed.

↓

Student added to section.

↓

Student added automatically to Course Chat and LMS.

---

# Indexes

Student

- student_number
- user_id

Course

- course_code

Section

- semester_id
- lecturer_id

Enrollment

- student_id
- section_id

---

# API Mapping

Academic Module provides APIs for:

- Student Profile
- Course Catalog
- Course Registration
- Drop Course
- View Schedule
- View Transcript
- View GPA
- Faculty Management
- Department Management

---

# Future Expansion

Future versions may include:

- Double Major
- Minor Programs
- Exchange Students
- Internship Tracking
- Graduation Project Management
- Academic Advising AI
- Degree Audit

---

# Notes

This module is the academic foundation of the entire platform.

All academic features depend on this design.