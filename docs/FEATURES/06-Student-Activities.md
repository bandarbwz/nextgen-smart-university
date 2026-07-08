# Student Activities Module

## Purpose

The Student Activities Module manages all extracurricular activities within the university.

It allows students to join clubs, register for events, earn reward points, receive certificates, and automatically generate attendance excuses when university events conflict with scheduled classes.

---

# Objectives

- Manage student clubs.
- Organize university events.
- Register students for events.
- Track event attendance.
- Reward active students.
- Generate certificates.
- Generate automatic class excuses.
- Support STAD administration.

---

# Database Tables

## Club

Purpose

Stores university clubs.

Columns

- id
- name
- category
- description
- logo
- president_id
- advisor_id
- status
- created_at
- updated_at

---

## ClubMember

Purpose

Stores club memberships.

Columns

- id
- club_id
- student_id
- position
- joined_at
- status

Positions

- Member
- Vice President
- President
- Committee

---

## Event

Purpose

Stores university events.

Columns

- id
- club_id
- title
- description
- event_type
- location
- start_datetime
- end_datetime
- capacity
- qr_code
- points
- certificate_available
- created_by
- created_at

---

## EventRegistration

Purpose

Stores student registrations.

Columns

- id
- event_id
- student_id
- registration_date
- status

Status

- Registered
- Cancelled
- Attended
- Absent

---

## EventAttendance

Purpose

Stores QR attendance records.

Columns

- id
- event_id
- student_id
- attendance_time
- attendance_method
- verified_by

---

## RewardPoint

Purpose

Stores student reward points.

Columns

- id
- student_id
- event_id
- points
- earned_at

---

## Certificate

Purpose

Stores issued certificates.

Columns

- id
- student_id
- event_id
- certificate_number
- file_path
- issued_at

---

## ExcuseLetter

Purpose

Stores automatically generated excuse letters.

Columns

- id
- student_id
- event_id
- section_id
- generated_at
- approved_by
- file_path
- status

---

# Relationships

Club

↓

ClubMember

↓

Student

---

Club

↓

Event

↓

Event Registration

↓

Event Attendance

↓

Reward Points

↓

Certificate

---

Event

↓

Excuse Letter

↓

Attendance Module

---

# Business Rules

- Students must register before attending.
- QR attendance is required.
- One attendance per student.
- Reward points are added automatically.
- Certificates are generated automatically when eligible.
- Excuse letters are generated automatically if an event overlaps with a scheduled class.
- Only approved events can generate excuses.

---

# Reward System

Students earn points for:

- Attending events
- Organizing events
- Volunteering
- Winning competitions
- Leading clubs

---

# Certificate Rules

Certificates are available only when:

- Event attendance is confirmed.
- Minimum participation requirements are met.

---

# Excuse Letter Rules

The system checks:

- Student timetable.
- Event timing.
- Event approval.
- Attendance confirmation.

If all conditions are satisfied, an excuse letter is generated automatically.

---

# Validation Rules

- Student must be registered.
- Event capacity must not be exceeded.
- Event dates must be valid.
- QR code must be active.

---

# API Mapping

Student Activities APIs

- View Clubs
- Join Club
- Leave Club
- Register Event
- Cancel Registration
- Scan Event QR
- View Points
- Download Certificate
- Download Excuse Letter

---

# Future Expansion

Future improvements

- Club Elections
- Volunteer Hours
- Community Service Tracking
- Digital Badges
- Student Ranking
- AI Event Recommendations

---

# Notes

The Student Activities Module integrates with:

- Academic Module
- Attendance Module
- Student Dashboard
- STAD Dashboard
- Notification Module