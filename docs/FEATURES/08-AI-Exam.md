# AI Exam Monitoring Module

## Purpose

The AI Exam Monitoring Module provides intelligent online exam supervision using computer vision and AI technologies.

The module helps lecturers monitor students during online exams by detecting suspicious behavior, recording violations, and generating AI reports.

The AI assists lecturers in making decisions but does not replace academic judgment.

---

# Objectives

- Monitor online exams.
- Verify student identity.
- Detect cheating attempts.
- Track eye movement.
- Detect multiple faces.
- Detect fullscreen exit.
- Detect browser tab switching.
- Generate AI reports.
- Record violations.
- Support lecturer review.

---

# Database Tables

## Exam

Purpose

Stores online examinations.

Columns

- id
- course_id
- section_id
- title
- description
- duration
- total_marks
- start_time
- end_time
- ai_monitoring_enabled
- created_by
- created_at

---

## ExamSession

Purpose

Stores each student's exam session.

Columns

- id
- exam_id
- student_id
- session_token
- login_time
- exam_start
- exam_end
- camera_status
- microphone_status
- fullscreen_status
- overall_status

Overall Status

- Active
- Completed
- Cancelled
- Interrupted

---

## AIViolation

Purpose

Stores AI-detected violations.

Columns

- id
- session_id
- violation_type
- confidence_score
- detected_at
- screenshot_path
- reviewed
- lecturer_action

Violation Types

- Face Not Detected
- Multiple Faces
- Looking Away
- Fullscreen Exit
- Tab Switching
- Camera Disabled
- Microphone Disabled
- Suspicious Activity

---

## AIReport

Purpose

Stores final AI reports.

Columns

- id
- session_id
- total_violations
- risk_level
- lecturer_review
- recommendation
- generated_at

Risk Levels

- Low
- Medium
- High
- Critical

---

## ExamRecording

Purpose

Stores recorded exam media.

Columns

- id
- session_id
- recording_type
- file_path
- recording_start
- recording_end

Recording Types

- Camera
- Screen
- Audio

---

# Relationships

Exam

↓

Exam Session

↓

AI Violations

↓

AI Report

↓

Lecturer Review

---

Exam Session

↓

Exam Recording

---

# AI Detection Features

The system supports:

- Face Detection
- Face Verification
- Eye Tracking
- Head Pose Detection
- Multiple Face Detection
- Camera Status Monitoring
- Fullscreen Monitoring
- Browser Tab Detection
- Idle Detection

---

# Business Rules

- Students must enable the camera before starting the exam.
- Students must remain in fullscreen mode.
- Only one face is allowed during the exam.
- Students cannot open multiple exam sessions.
- AI records all detected violations.
- Lecturers review AI reports before taking disciplinary action.

---

# Lecturer Review

Lecturers can:

- View violation timeline.
- View screenshots.
- Watch recordings.
- Ignore violations.
- Mark violations as confirmed.
- Decide whether to invalidate an exam.

---

# Validation Rules

Exam

- Duration must be greater than zero.
- Start time must be before end time.

Session

- Student must be enrolled.
- One active session per student.

Violation

- Confidence score between 0 and 100.

---

# Notifications

Notify lecturers when:

- Multiple faces detected.
- Camera disabled.
- Fullscreen exited.
- High-risk behavior detected.

Notify students when:

- Camera disconnected.
- Fullscreen exited.
- Internet connection lost.

---

# API Mapping

AI Exam APIs

- Start Exam
- End Exam
- Start Monitoring
- Record Violation
- Upload Screenshot
- Generate Report
- Review Report
- Download Report

---

# Future Expansion

Future improvements

- Mobile Phone Detection
- Voice Analysis
- Identity Verification using Face Recognition
- AI Behavioral Analysis
- AI Exam Risk Prediction
- Automatic Exam Recording Analysis

---

# Notes

The AI Exam Module integrates with:

- Academic Module
- LMS Module
- Student Dashboard
- Lecturer Dashboard
- Notification Module
- Authentication Module