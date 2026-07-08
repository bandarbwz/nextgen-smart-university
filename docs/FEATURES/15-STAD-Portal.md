# STAD Portal Module

## Purpose

The STAD Portal enables the Student Affairs Department (STAD) to manage university clubs, student organizations, events, competitions, volunteer programs, student participation, and extracurricular activities.

It serves as the administrative platform for planning, organizing, approving, and monitoring all student affairs activities.

---

# Objectives

- Manage student clubs.
- Organize university events.
- Manage competitions.
- Track student participation.
- Award activity points.
- Generate student affairs reports.
- Improve student engagement.

---

# Scope

The STAD Portal includes:

- Dashboard
- Club Management
- Event Management
- Competition Management
- Student Registration
- Event Attendance
- Activity Points
- Reports
- Notifications
- Calendar
- Settings

---

# Actors

- STAD Staff

---

# Features

STAD Staff can:

- View Dashboard
- Create Clubs
- Manage Clubs
- Create Events
- Edit Events
- Cancel Events
- Approve Student Registrations
- Generate Event QR Codes
- Record Attendance
- Award Activity Points
- Generate Reports
- Publish Announcements

---

# Dashboard Widgets

The dashboard displays:

- Active Clubs
- Upcoming Events
- Pending Registrations
- Event Attendance
- Student Participation
- Activity Points Awarded
- Notifications
- Recent Activities

---

# Student Affairs Services

## Club Management

- Create Clubs
- Update Club Information
- Assign Club Advisors
- Manage Club Members

---

## Event Management

- Create Events
- Edit Events
- Publish Events
- Cancel Events
- Generate QR Attendance

---

## Student Registration

- View Registrations
- Approve Registrations
- Reject Registrations
- Attendance Verification

---

## Reports

- Club Report
- Event Report
- Student Participation Report
- Activity Points Report

---

# Business Rules

- Only STAD Staff can manage clubs and events.
- Students may register only once per event.
- QR attendance is valid only during active events.
- Activity points are awarded after verified attendance.
- Cancelled events notify all registered students automatically.

---

# Validation Rules

Club

- Club name required.
- Club advisor required.

Event

- Event date required.
- Registration deadline required.
- Maximum participants greater than zero.

Registration

- Student must have an active account.
- Duplicate registrations are not allowed.

---

# Permissions

STAD Staff permissions include:

- Manage Clubs
- Manage Events
- Approve Registrations
- Record Attendance
- Award Activity Points
- Publish Announcements
- Generate Reports

---

# Notifications

STAD Staff receive notifications for:

- New Event Registration
- Event Attendance Completed
- Event Cancellation
- System Announcements

Students receive notifications for:

- Registration Approved
- Registration Rejected
- Event Reminder
- Activity Points Awarded

---

# Security

- JWT Authentication
- Role-Based Access Control
- Secure QR Attendance
- Audit Logging
- Secure Student Records

---

# Performance

- Cache active events.
- Optimize participant lists.
- Index attendance records.
- Archive completed events.

---

# API Mapping

GET /api/stad/dashboard

GET /api/stad/clubs

POST /api/stad/clubs

GET /api/stad/events

POST /api/stad/events

PUT /api/stad/events/{id}

POST /api/stad/attendance

GET /api/stad/reports

---

# UI Pages

- Dashboard
- Club Management
- Event Management
- Registrations
- Attendance
- Reports
- Notifications
- Calendar
- Settings

---

# Dependencies

This module depends on:

- Authentication Module
- Student Activities Module
- Attendance Module
- Notification Module

The following modules depend on this module:

- Student Portal
- Reports Module

---

# Future Expansion

- AI Event Recommendation
- Volunteer Hour Tracking
- Digital Certificates
- Club Election Management
- Alumni Activities

---

# Notes

The STAD Portal integrates with the Student Activities Module, Attendance Module, Notification Module, and Student Portal to provide complete management of extracurricular activities and student engagement.