# Academic API

## Purpose

This document defines the Academic REST APIs for the NextGen Smart University Platform.

The Academic API manages students, lecturers, faculties, departments, academic programs, courses, semesters, sections, enrollments, schedules, transcripts, and GPA calculations.

---

# Base URL

```
/api/v1
```

---

# Authentication

All endpoints require:

```
Authorization: Bearer <JWT_TOKEN>
```

---

# Content Type

```
Content-Type: application/json
```

---

# Student APIs

---

## Get All Students

Retrieve a list of students.

### Endpoint

```
GET /api/v1/students
```

### Authentication

Required

### Permissions

- Administrator
- Coordinator

---

## Get Student By ID

Retrieve a student profile.

### Endpoint

```
GET /api/v1/students/{id}
```

### Authentication

Required

### Permissions

- Student (Own Profile)
- Lecturer
- Coordinator
- Administrator

---

## Create Student

Create a new student record.

### Endpoint

```
POST /api/v1/students
```

### Permissions

- Administrator

---

## Update Student

### Endpoint

```
PUT /api/v1/students/{id}
```

### Permissions

- Administrator

---

## Delete Student

### Endpoint

```
DELETE /api/v1/students/{id}
```

### Permissions

- Administrator

---

# Lecturer APIs

---

## Get All Lecturers

### Endpoint

```
GET /api/v1/lecturers
```

---

## Get Lecturer

### Endpoint

```
GET /api/v1/lecturers/{id}
```

---

## Create Lecturer

### Endpoint

```
POST /api/v1/lecturers
```

---

## Update Lecturer

### Endpoint

```
PUT /api/v1/lecturers/{id}
```

---

## Delete Lecturer

### Endpoint

```
DELETE /api/v1/lecturers/{id}
```

---

# Faculty APIs

---

## Get Faculties

### Endpoint

```
GET /api/v1/faculties
```

---

## Get Faculty

### Endpoint

```
GET /api/v1/faculties/{id}
```

---

## Create Faculty

### Endpoint

```
POST /api/v1/faculties
```

---

## Update Faculty

### Endpoint

```
PUT /api/v1/faculties/{id}
```

---

## Delete Faculty

### Endpoint

```
DELETE /api/v1/faculties/{id}
```

---

# Department APIs

---

## Get Departments

### Endpoint

```
GET /api/v1/departments
```

---

## Get Department

### Endpoint

```
GET /api/v1/departments/{id}
```

---

## Get Departments By Faculty

### Endpoint

```
GET /api/v1/faculties/{faculty_id}/departments
```

---

## Create Department

### Endpoint

```
POST /api/v1/departments
```

---

## Update Department

### Endpoint

```
PUT /api/v1/departments/{id}
```

---

## Delete Department

### Endpoint

```
DELETE /api/v1/departments/{id}
```

---

# Standard Success Response

```json
{
    "success": true,
    "message": "Request completed successfully.",
    "data": {}
}
```

---

# Standard Error Response

```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {}
}
```

---

# Program APIs

---

## Get All Programs

### Endpoint

```
GET /api/v1/programs
```

### Permissions

- Administrator
- Coordinator
- Lecturer

---

## Get Program By ID

### Endpoint

```
GET /api/v1/programs/{id}
```

---

## Create Program

### Endpoint

```
POST /api/v1/programs
```

### Permissions

- Administrator

---

## Update Program

### Endpoint

```
PUT /api/v1/programs/{id}
```

---

## Delete Program

### Endpoint

```
DELETE /api/v1/programs/{id}
```

---

# Course APIs

---

## Get All Courses

### Endpoint

```
GET /api/v1/courses
```

---

## Search Courses

### Endpoint

```
GET /api/v1/courses?search=computer
```

---

## Get Course By ID

### Endpoint

```
GET /api/v1/courses/{id}
```

---

## Get Course Prerequisites

### Endpoint

```
GET /api/v1/courses/{id}/prerequisites
```

---

## Create Course

### Endpoint

```
POST /api/v1/courses
```

---

## Update Course

### Endpoint

```
PUT /api/v1/courses/{id}
```

---

## Delete Course

### Endpoint

```
DELETE /api/v1/courses/{id}
```

---

# Semester APIs

---

## Get All Semesters

### Endpoint

```
GET /api/v1/semesters
```

---

## Get Current Semester

### Endpoint

```
GET /api/v1/semesters/current
```

---

## Get Semester By ID

### Endpoint

```
GET /api/v1/semesters/{id}
```

---

## Create Semester

### Endpoint

```
POST /api/v1/semesters
```

---

## Update Semester

### Endpoint

```
PUT /api/v1/semesters/{id}
```

---

## Delete Semester

### Endpoint

```
DELETE /api/v1/semesters/{id}
```

---

# Section APIs

---

## Get All Sections

### Endpoint

```
GET /api/v1/sections
```

---

## Get Section By ID

### Endpoint

```
GET /api/v1/sections/{id}
```

---

## Get Sections By Course

### Endpoint

```
GET /api/v1/courses/{course_id}/sections
```

---

## Get Students In Section

### Endpoint

```
GET /api/v1/sections/{id}/students
```

---

## Create Section

### Endpoint

```
POST /api/v1/sections
```

---

## Update Section

### Endpoint

```
PUT /api/v1/sections/{id}
```

---

## Delete Section

### Endpoint

```
DELETE /api/v1/sections/{id}
```

---

## Open Registration

### Endpoint

```
POST /api/v1/sections/{id}/open-registration
```

---

## Close Registration

### Endpoint

```
POST /api/v1/sections/{id}/close-registration
```

---

## Update Section Capacity

### Endpoint

```
PUT /api/v1/sections/{id}/capacity
```

---

## Assign Lecturer

### Endpoint

```
PUT /api/v1/sections/{id}/lecturer
```

---

## Change Classroom

### Endpoint

```
PUT /api/v1/sections/{id}/classroom
```

---

# Enrollment APIs

---

## Register Course

Registers a student in a course section.

### Endpoint

```
POST /api/v1/enrollments/register
```

### Permissions

- Student

---

## Drop Course

Drops a registered course.

### Endpoint

```
POST /api/v1/enrollments/drop
```

### Permissions

- Student

---

## Get Current Enrollments

### Endpoint

```
GET /api/v1/enrollments/current
```

### Permissions

- Student

---

## Get Enrollment History

### Endpoint

```
GET /api/v1/enrollments/history
```

### Permissions

- Student

---

## Approve Enrollment

### Endpoint

```
PUT /api/v1/enrollments/{id}/approve
```

### Permissions

- Coordinator

---

## Reject Enrollment

### Endpoint

```
PUT /api/v1/enrollments/{id}/reject
```

### Permissions

- Coordinator

---

# Transcript APIs

---

## Get Student Transcript

### Endpoint

```
GET /api/v1/transcript
```

### Permissions

- Student

---

## Get Transcript By Student

### Endpoint

```
GET /api/v1/transcript/{student_id}
```

### Permissions

- Lecturer
- Coordinator
- Administrator

---

## Download Transcript

### Endpoint

```
GET /api/v1/transcript/{student_id}/download
```

---

# Schedule APIs

---

## Get Weekly Schedule

### Endpoint

```
GET /api/v1/schedule
```

---

## Get Daily Schedule

### Endpoint

```
GET /api/v1/schedule/daily
```

---

## Get Semester Schedule

### Endpoint

```
GET /api/v1/schedule/semester
```

---

# GPA APIs

---

## Get Current GPA

### Endpoint

```
GET /api/v1/gpa
```

---

## Get CGPA

### Endpoint

```
GET /api/v1/cgpa
```

---

## Calculate GPA

### Endpoint

```
POST /api/v1/gpa/calculate
```

### Permissions

- Administrator

---

# Validation Rules

Students

- Student number must be unique.
- Student must belong to a valid program.

Courses

- Course code must be unique.
- Credit hours must be greater than zero.

Enrollment

- Student must satisfy prerequisites.
- Student cannot exceed maximum credit hours.
- Registration period must be open.
- Section must have available seats.
- No timetable conflicts are allowed.

Semester

- Registration dates must be valid.
- Start date must be before end date.

---

# Security

- JWT Authentication
- Role-Based Access Control (RBAC)
- Input Validation
- SQL Injection Protection
- XSS Protection
- Audit Logging

---

# HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 409 | Conflict |
| 422 | Validation Error |
| 500 | Internal Server Error |

---

# Business Rules

- Every student must belong to one academic program.
- Students cannot register outside the registration period.
- Prerequisites must be completed before enrollment.
- Duplicate enrollments are not allowed.
- Students are automatically added to the Course Chat after successful enrollment.
- Students are automatically enrolled in the LMS after registration.
- GPA and CGPA are calculated automatically after grade publication.
- Dropping a course updates the student's schedule and enrollment records.
- Every academic transaction is logged for auditing.

---

# Dependencies

This API depends on:

- Authentication API

Related APIs:

- Attendance API
- LMS API
- Chat API
- Assessment API
- Notification API
- Reports API

---

# Notes

The Academic API is the central API of the NextGen Smart University Platform. It manages all academic entities, registration workflows, scheduling, transcripts, and GPA calculations while integrating with Attendance, LMS, Chat, Assessment, Notification, and Reporting services.