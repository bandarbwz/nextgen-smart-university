# Attendance Module

## Purpose

The Attendance Module manages attendance for lectures, laboratories, tutorials, university events, and online classes.

It supports QR Code attendance, GPS verification, AI face verification, manual attendance, and attendance reporting.

---

# Objectives

- Record attendance accurately.
- Prevent attendance fraud.
- Support QR attendance.
- Verify student location.
- Generate attendance reports.
- Integrate attendance with university events and online classes.

---

# Scope

This module manages attendance for all academic activities.

It includes:

- QR Attendance
- GPS Verification
- AI Face Verification
- Manual Attendance
- Online Attendance
- Attendance Excuses
- Attendance Reports

---

# Actors

- Student
- Lecturer
- Coordinator
- Administrator

---

# Attendance Methods

- QR Code
- GPS Verification
- AI Face Verification
- Manual Attendance
- Online Attendance

---

# Database Tables

## Attendance

Purpose

Stores attendance records.

### Columns

- id
- student_id
- section_id
- attendance_date
- attendance_time
- attendance_status
- attendance_method
- verified_by
- remarks
- created_at
- updated_at

---

## QRSession

Purpose

Stores QR sessions.

### Columns

- id
- section_id
- qr_token
- generated_at
- expires_at
- latitude
- longitude
- allowed_radius

---

## AttendanceExcuse

Purpose

Stores attendance excuses.

### Columns

- id
- student_id
- attendance_id
- excuse_type
- document_path
- approved_by
- approval_date
- status

---

# Relationships

Section

↓

Attendance

↓

Student

---

Attendance

↓

Attendance Excuse

---

# Attendance Status

- Present
- Late
- Absent
- Excused
- Online
- Pending

---

# Attendance Workflow

Lecturer starts attendance session.

↓

QR Code generated.

↓

Student scans QR Code.

↓

GPS location verified.

↓

AI verification (if required).

↓

Attendance recorded.

↓

Attendance notification sent.

↓

Attendance report updated.

---

# Business Rules

Students

- Must be enrolled in the section.
- Only one attendance per session.
- Cannot modify attendance.
- Cannot scan expired QR codes.

Lecturers

- Can manually update attendance.
- Can approve attendance excuses.

System

- QR Codes expire automatically.
- GPS must match the allowed campus area.
- AI verification is required for online attendance.
- All manual changes are logged.

---

# Validation Rules

Attendance

- Student must be enrolled.
- Session must be active.
- QR Code must be valid.
- GPS location must match campus.
- Attendance cannot be duplicated.

Attendance Excuse

- Supporting document required.
- Approval required before status changes.

---

# Permissions

## Student

- Scan QR
- View Attendance
- Submit Excuse

## Lecturer

- Generate QR
- Record Attendance
- Edit Attendance
- Approve Excuses

## Coordinator

- View Attendance Reports

## Administrator

- Full Attendance Access

---

# Notifications

Students receive notifications for:

- Attendance Recorded
- Attendance Updated
- Excuse Approved
- Excuse Rejected
- Attendance Warning

Lecturers receive notifications for:

- Attendance Session Started
- Excuse Submitted

---

# Security

- QR Tokens expire automatically.
- GPS verification required.
- AI verification for online classes.
- Attendance cannot be modified by students.
- All manual edits are logged.

---

# Indexes

Attendance

- student_id
- section_id
- attendance_date

QRSession

- qr_token

AttendanceExcuse

- student_id

---

# Reports

Attendance Reports

- Daily Attendance
- Weekly Attendance
- Monthly Attendance
- Student Attendance
- Section Attendance
- Faculty Attendance
- Attendance Percentage
- Excuse Report

---

# Performance

- Cache active QR sessions.
- Index attendance records.
- Optimize attendance reports.
- Automatically archive old attendance sessions.

---

# API Mapping

POST /api/attendance/generate-qr

POST /api/attendance/scan-qr

POST /api/attendance/manual

POST /api/attendance/excuse

PUT /api/attendance/excuse/{id}

GET /api/attendance/student

GET /api/attendance/section

GET /api/attendance/report

---

# UI Pages

- Attendance Dashboard
- QR Scanner
- Attendance History
- Attendance Report
- Attendance Excuse
- Lecturer Attendance
- Attendance Analytics

---

# Dependencies

This module depends on:

- Authentication Module
- Academic Module
- Notification Module
- AI Exam Module

The following modules depend on this module:

- Student Portal
- Lecturer Portal
- Reports Module

---

# Future Expansion

- NFC Attendance
- Bluetooth Attendance
- Offline Attendance Sync
- Smart Classroom Detection
- Biometric Attendance

---

# Notes

The Attendance Module integrates with the Academic Module, Student Activities Module, AI Exam Module, Student Portal, Lecturer Portal, and Notification Module.