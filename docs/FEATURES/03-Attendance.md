# Attendance Module

## Purpose

The Attendance Module manages student attendance for lectures, laboratories, tutorials, university events, and online classes.

The system supports QR Code attendance, GPS verification, AI verification, and attendance reporting.

---

# Objectives

- Record student attendance accurately.
- Prevent attendance fraud.
- Support QR attendance.
- Verify student location.
- Generate attendance reports.
- Integrate attendance with events and online classes.

---

# Attendance Methods

The system supports:

- QR Code Attendance
- GPS Verification
- AI Face Verification
- Manual Attendance
- Online Attendance

---

# Database Tables

## Attendance

Purpose

Stores attendance records for every class session.

Columns

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

Stores generated QR codes for each lecture.

Columns

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

Stores approved excuses.

Columns

- id
- student_id
- attendance_id
- excuse_type
- document_path
- approved_by
- approval_date
- status

---

# Attendance Status

Possible values

- Present
- Late
- Absent
- Excused
- Online
- Pending

---

# Attendance Methods

Possible values

- QR Code
- GPS
- AI Face Recognition
- Manual
- Online

---

# QR Attendance Workflow

Lecturer starts attendance session.

↓

QR Code generated.

↓

Students scan QR Code.

↓

GPS location verified.

↓

Attendance recorded.

↓

QR expires automatically.

---

# GPS Verification

Rules

- Student must be inside campus.
- GPS coordinates are validated.
- Radius can be configured by administrator.
- Fake GPS detection should be supported in future versions.

---

# AI Verification

Online attendance can verify:

- Face detected
- Single face only
- Identity matches student
- Camera enabled

---

# Manual Attendance

Lecturers may manually:

- Mark Present
- Mark Late
- Mark Excused

All manual changes are logged.

---

# Event Attendance

Students attending approved university events:

- Scan Event QR Code.
- Attendance is recorded.
- If the event conflicts with a scheduled class, an automatic excuse letter is generated.

---

# Attendance Reports

Students

- Attendance Percentage
- Absent Classes
- Excused Classes

Lecturers

- Section Attendance
- Daily Attendance
- Monthly Reports

Administrators

- University Attendance Statistics
- Faculty Reports
- Department Reports

---

# Business Rules

- QR Codes expire automatically.
- One attendance per student per session.
- Duplicate attendance is not allowed.
- Attendance cannot be modified by students.
- Manual edits require lecturer permission.
- Attendance contributes to course statistics.

---

# Validation Rules

Attendance

- Student must be enrolled.
- Session must be active.
- QR must be valid.
- GPS must match campus.

---

# API Mapping

Attendance Module provides APIs for:

- Generate QR
- Scan QR
- Record Attendance
- Manual Attendance
- Submit Excuse
- View Attendance Report

---

# Future Expansion

Future improvements

- NFC Attendance
- Bluetooth Verification
- Smart Classroom Detection
- Offline Attendance Sync

---

# Notes

Attendance integrates with:

- Academic Module
- AI Exam Module
- STAD Events
- Student Dashboard
- Lecturer Dashboard