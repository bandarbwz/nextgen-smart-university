# Learning Management System (LMS)

## Purpose

The Learning Management System (LMS) manages all learning activities within the platform.

It allows lecturers to publish course materials, assignments, quizzes, online exams, announcements, grades, and course resources.

Students can access all learning content from a single portal.

---

# Objectives

- Manage learning materials.
- Manage assignments.
- Manage quizzes.
- Manage online exams.
- Store grades.
- Share announcements.
- Organize course resources.
- Support online learning.

---

# Database Tables

## CourseMaterial

Purpose

Stores learning materials uploaded by lecturers.

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

Purpose

Stores assignments.

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

Purpose

Stores student submissions.

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

Purpose

Stores quizzes.

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
- True/False
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

Purpose

Stores grades.

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

Assessment Types

- Assignment
- Quiz
- Midterm
- Final
- Participation

---

## Announcement

Purpose

Stores course announcements.

Columns

- id
- section_id
- lecturer_id
- title
- content
- published_at

---

## Resource

Purpose

Stores additional learning resources.

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

Assignments

↓

Submissions

↓

Grades

---

Course

↓

Materials

↓

Resources

↓

Announcements

---

Quiz

↓

Questions

↓

Student Submission

↓

Grades

---

# Business Rules

- Only lecturers can upload materials.
- Students can only access enrolled courses.
- Assignments have deadlines.
- Late submissions follow lecturer settings.
- Grades cannot be edited after final approval.
- Announcements are visible immediately after publishing.

---

# Validation Rules

Assignments

- Due date required.
- Total marks must be greater than zero.

Materials

- File type must be allowed.
- File size must be within system limit.

Grades

- Marks cannot exceed total marks.
- Grade points calculated automatically.

---

# File Types

Supported files

- PDF
- DOCX
- PPTX
- XLSX
- ZIP
- JPG
- PNG
- MP4

---

# Notifications

Students receive notifications for:

- New Assignment
- New Material
- New Quiz
- New Grade
- New Announcement
- Assignment Deadline

---

# API Mapping

LMS provides APIs for:

- Upload Material
- Create Assignment
- Submit Assignment
- Create Quiz
- Submit Quiz
- Publish Grade
- Publish Announcement
- Download Resources

---

# Future Expansion

Future improvements

- AI Assignment Feedback
- AI Study Assistant
- AI Quiz Generator
- Live Online Classes
- Automatic Lecture Recording
- Learning Analytics

---

# Notes

The LMS integrates with:

- Academic Module
- Attendance Module
- AI Exam Module
- Student Dashboard
- Lecturer Dashboard