# Learning Management System (LMS)

## Purpose

The Learning Management System (LMS) manages all teaching and learning activities within the NextGen Smart University Platform.

It enables lecturers to publish course materials, assignments, quizzes, announcements, grades, and learning resources while allowing students to access all course content from a single platform.

---

# Objectives

- Manage course materials.
- Manage assignments.
- Manage quizzes.
- Manage online learning.
- Manage announcements.
- Publish grades.
- Support student submissions.
- Organize learning resources.

---

# Scope

The LMS manages all digital learning activities including:

- Course Materials
- Assignments
- Assignment Submissions
- Quizzes
- Quiz Questions
- Quiz Submissions
- Grades
- Announcements
- Learning Resources

---

# Actors

- Student
- Lecturer
- Coordinator
- Administrator

---

# Database Tables

## CourseMaterial

Stores course learning materials.

Columns

- id
- course_id
- section_id
- lecturer_id
- title
- description
- file_path
- file_type
- visibility
- upload_date
- created_at
- updated_at

---

## Assignment

Columns

- id
- course_id
- section_id
- title
- description
- total_marks
- due_date
- allow_late_submission
- created_by
- created_at
- updated_at

---

## AssignmentSubmission

Columns

- id
- assignment_id
- student_id
- file_path
- submitted_at
- submission_status
- marks
- feedback

Submission Status

- Submitted
- Late
- Missing
- Graded

---

## Quiz

Columns

- id
- section_id
- title
- description
- total_marks
- duration
- start_time
- end_time
- attempts

---

## QuizQuestion

Columns

- id
- quiz_id
- question
- question_type
- marks
- correct_answer

Question Types

- Multiple Choice
- True / False
- Short Answer
- Essay

---

## QuizSubmission

Columns

- id
- quiz_id
- student_id
- score
- submitted_at

---

## Grade

Columns

- id
- student_id
- section_id
- assessment_type
- assessment_id
- marks
- total_marks
- grade_letter
- grade_points
- published_at

---

## Announcement

Columns

- id
- section_id
- lecturer_id
- title
- content
- published_at

---

## Resource

Columns

- id
- section_id
- title
- link
- resource_type

Resource Types

- PDF
- Video
- Website
- Document
- External Link

---

# Relationships

Course

↓

Section

↓

Materials

↓

Assignments

↓

Submissions

↓

Grades

---

Course

↓

Quizzes

↓

Questions

↓

Submissions

---

Course

↓

Announcements

↓

Resources

---

# Business Rules

- Only lecturers can upload learning materials.
- Students can access only enrolled courses.
- Assignment deadlines are enforced.
- Late submissions follow lecturer settings.
- Grades cannot be modified after final publication.
- Announcements become visible immediately after publishing.
- Students may attempt quizzes only within the allowed period.

---

# Validation Rules

Assignments

- Due date required.
- Total marks must be greater than zero.

Materials

- Allowed file types only.
- Maximum upload size enforced.

Grades

- Marks cannot exceed total marks.

Quiz

- End time must be after start time.

---

# Permissions

## Student

- View Materials
- Download Resources
- Submit Assignments
- Take Quizzes
- View Grades

## Lecturer

- Upload Materials
- Create Assignments
- Create Quizzes
- Publish Grades
- Publish Announcements

## Coordinator

- View Course Content
- Monitor Teaching Activities

## Administrator

- Full LMS Access

---

# Notifications

Students receive notifications for:

- New Material
- New Assignment
- Assignment Deadline
- New Quiz
- Quiz Reminder
- Grade Published
- New Announcement

Lecturers receive notifications for:

- Assignment Submitted
- Quiz Completed

---

# Security

- Only enrolled students can access course content.
- Uploaded files are validated.
- File types are restricted.
- Virus scanning should be supported.
- Grades are protected from unauthorized modification.

---

# Indexes

CourseMaterial

- section_id

Assignment

- section_id

AssignmentSubmission

- assignment_id
- student_id

Quiz

- section_id

Grade

- student_id
- section_id

---

# Reports

LMS Reports

- Assignment Submission Report
- Quiz Performance
- Student Progress
- Grade Distribution
- Course Activity Report
- Lecturer Activity Report

---

# Performance

- Cache course materials.
- Optimize file downloads.
- Index assignment submissions.
- Optimize grade retrieval.
- Archive old course materials.

---

# API Mapping

GET /api/lms/materials

POST /api/lms/materials

POST /api/lms/assignments

POST /api/lms/submissions

POST /api/lms/quizzes

POST /api/lms/quiz-submissions

GET /api/lms/grades

POST /api/lms/announcements

GET /api/lms/resources

---

# UI Pages

- LMS Dashboard
- Course Materials
- Assignments
- Assignment Details
- Quiz Center
- Grades
- Announcements
- Resources

---

# Dependencies

This module depends on:

- Authentication Module
- Academic Module
- Notification Module

The following modules depend on this module:

- Student Portal
- Lecturer Portal
- Reports Module

---

# Future Expansion

- Live Online Classes
- AI Study Assistant
- AI Quiz Generator
- AI Assignment Feedback
- Automatic Lecture Recording
- Learning Analytics

---

# Notes

The LMS integrates with the Academic Module, Attendance Module, Assessment Module, Student Portal, Lecturer Portal, and Notification Module.