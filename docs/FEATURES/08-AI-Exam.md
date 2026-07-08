# AI Examination Module

## Purpose

The AI Examination Module provides secure online examinations using Artificial Intelligence to monitor student behavior, detect cheating attempts, and generate examination reports.

The module enhances academic integrity by combining AI monitoring with traditional examination management.

---

# Objectives

- Conduct secure online examinations.
- Monitor students using AI.
- Detect cheating behavior.
- Generate AI violation reports.
- Protect examination integrity.
- Record examination sessions.
- Support automatic evidence collection.

---

# Scope

The module manages:

- Online Exams
- Exam Sessions
- AI Monitoring
- Face Detection
- Eye Tracking
- Head Pose Detection
- Tab Switching Detection
- Fullscreen Monitoring
- AI Violations
- Examination Reports

---

# Actors

- Student
- Lecturer
- Administrator

---

# Database Tables

## Exam

Columns

- id
- section_id
- title
- description
- total_marks
- duration
- start_time
- end_time
- passing_marks
- status

---

## ExamQuestion

Columns

- id
- exam_id
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

## ExamSubmission

Columns

- id
- exam_id
- student_id
- score
- submitted_at
- submission_status

Submission Status

- Submitted
- Auto Submitted
- Pending Review

---

## ExamSession

Columns

- id
- exam_id
- student_id
- session_start
- session_end
- ip_address
- browser
- device
- status

---

## AIViolation

Columns

- id
- session_id
- violation_type
- confidence_score
- evidence_path
- detected_at

Violation Types

- Multiple Faces
- Face Not Detected
- Looking Away
- Head Pose Warning
- Tab Switching
- Fullscreen Exit
- Camera Disabled

---

# Relationships

Exam

↓

Questions

↓

Student Submission

↓

Exam Session

↓

AI Violations

---

# Business Rules

- Students can start only during the examination period.
- Only one active session is allowed.
- Camera permission is mandatory.
- Microphone permission follows university policy.
- AI monitoring starts automatically.
- Exams are automatically submitted when time expires.
- AI violations are permanently recorded.

---

# Validation Rules

Exam

- Duration must be greater than zero.
- Start time must be before end time.

Submission

- Student must be enrolled.
- Only one submission allowed.

Session

- Camera required.
- Browser compatibility verified.

---

# AI Monitoring

The AI system monitors:

- Face Detection
- Eye Tracking
- Head Pose
- Camera Status
- Browser Focus
- Fullscreen Mode
- Multiple Face Detection

---

# Permissions

## Student

- Start Exam
- Submit Exam
- View Own Results

## Lecturer

- Create Exam
- Publish Exam
- Review AI Reports
- Publish Grades

## Administrator

- Full Examination Access

---

# Notifications

Students receive:

- Exam Available
- Exam Reminder
- Submission Successful

Lecturers receive:

- Exam Completed
- AI Violations Detected

---

# Security

- JWT Authentication
- Secure Browser Session
- AI Monitoring
- HTTPS Only
- Session Timeout
- Prevent Multiple Sessions

---

# Indexes

Exam

- section_id

ExamSubmission

- student_id
- exam_id

ExamSession

- student_id

AIViolation

- session_id

---

# Reports

AI Examination Reports

- Student Results
- AI Violation Report
- Exam Statistics
- Submission Report
- Session Report

---

# Performance

- AI runs independently from the main API.
- Process only active sessions.
- Optimize video processing.
- Archive completed sessions.

---

# API Mapping

GET /api/exams

POST /api/exams

GET /api/exams/{id}

POST /api/exams/start

POST /api/exams/submit

GET /api/exams/results

GET /api/exams/violations

---

# UI Pages

- Exam Dashboard
- Start Examination
- Examination Screen
- Results
- AI Violation Report
- Lecturer Examination Dashboard

---

# Dependencies

This module depends on:

- Authentication Module
- Academic Module
- LMS Module

The following modules depend on this module:

- Student Portal
- Lecturer Portal
- Reports Module

---

# Future Expansion

- Voice Analysis
- Keyboard Behavior Analysis
- AI Identity Verification
- Mobile Exam Monitoring
- AI Exam Analytics

---

# Notes

The AI Examination Module integrates with the LMS Module for exam management, the Academic Module for student enrollment, the Authentication Module for secure access, and the Reports Module for examination analytics.