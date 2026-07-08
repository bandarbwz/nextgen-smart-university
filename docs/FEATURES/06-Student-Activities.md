# Student Activities Module

## Purpose

The Student Activities Module manages university clubs, student organizations, competitions, workshops, seminars, volunteering activities, and university events.

It allows students to participate in extracurricular activities while enabling STAD staff to organize and manage events efficiently.

---

# Objectives

- Manage university clubs.
- Manage student activities.
- Manage university events.
- Support event registration.
- Track student participation.
- Generate participation reports.
- Award participation points.
- Integrate event attendance with QR attendance.

---

# Scope

This module manages all non-academic student activities within the university.

It includes:

- Clubs
- Events
- Competitions
- Workshops
- Seminars
- Student Registrations
- Event Attendance
- Participation Records
- Student Activity Points

---

# Actors

- Student
- STAD Staff
- Administrator

---

# Database Tables

## Club

Purpose

Stores university clubs.

### Columns

- id
- club_name
- description
- category
- advisor_id
- president_id
- status
- created_at
- updated_at

---

## Event

Purpose

Stores university events.

### Columns

- id
- club_id
- event_name
- description
- venue
- event_date
- start_time
- end_time
- registration_deadline
- maximum_participants
- qr_enabled
- status

---

## EventRegistration

Purpose

Stores student registrations.

### Columns

- id
- event_id
- student_id
- registration_date
- status

### Status

- Pending
- Approved
- Rejected
- Cancelled

---

## EventAttendance

Purpose

Stores attendance records.

### Columns

- id
- registration_id
- attendance_time
- attendance_method
- verified_by

---

## ActivityPoint

Purpose

Stores student activity points.

### Columns

- id
- student_id
- event_id
- points
- awarded_date

---

# Relationships

Club

↓

Event

↓

Event Registration

↓

Student

↓

Event Attendance

↓

Activity Points

---

# Business Rules

- Students may only register once per event.
- Registration closes after the deadline.
- Maximum participant limit cannot be exceeded.
- QR attendance is available only during active events.
- Students earn activity points only after verified attendance.
- Event organizers can approve or reject registrations.

---

# Validation Rules

Club

- Club name is required.
- Club name must be unique.

Event

- Event name required.
- Event date required.
- Registration deadline must be before the event date.
- Maximum participants must be greater than zero.

Registration

- Student must be active.
- Duplicate registrations are not allowed.

---

# Permissions

## Student

- View Clubs
- View Events
- Register for Events
- Cancel Registration
- View Participation History

## STAD Staff

- Create Clubs
- Manage Clubs
- Create Events
- Approve Registrations
- Generate QR Attendance
- Award Activity Points

## Administrator

- Full Student Activities Access

---

# Notifications

Students receive notifications for:

- Event Registration Approved
- Event Registration Rejected
- Event Reminder
- Event Cancelled
- Activity Points Awarded

STAD Staff receive notifications for:

- New Registration
- Event Attendance Completed

---

# Security

- Only registered students may attend events.
- QR attendance expires automatically.
- Attendance records cannot be modified by students.
- All approvals are logged.

---

# Indexes

Club

- club_name

Event

- event_date
- club_id

EventRegistration

- student_id
- event_id

ActivityPoint

- student_id

---

# Reports

Student Activities Reports

- Event Attendance Report
- Club Membership Report
- Student Participation Report
- Activity Points Report
- Event Performance Report

---

# Performance

- Cache active events.
- Index event registrations.
- Optimize participation reports.
- Archive completed events.

---

# API Mapping

GET /api/activities/clubs

POST /api/activities/clubs

GET /api/activities/events

POST /api/activities/events

POST /api/activities/register

POST /api/activities/attendance

GET /api/activities/points

GET /api/activities/reports

---

# UI Pages

- Student Activities Dashboard
- Club Directory
- Event List
- Event Details
- My Registrations
- My Activity Points
- STAD Event Management
- Club Management

---

# Dependencies

This module depends on:

- Authentication Module
- Academic Module
- Attendance Module
- Notification Module

The following modules depend on this module:

- Student Portal
- STAD Portal
- Reports Module

---

# Future Expansion

- Online Event Registration
- Digital Student Certificates
- Volunteer Hour Tracking
- Club Elections
- AI Event Recommendation
- Alumni Activities

---

# Notes

The Student Activities Module integrates with the Attendance Module through QR attendance, the Notification Module for reminders, and the Student Portal for student participation management.

If an approved university event conflicts with a student's scheduled class, the system can automatically generate an attendance excuse according to university policy.