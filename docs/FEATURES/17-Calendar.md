# Calendar Module

## Purpose

The Calendar Module provides a centralized scheduling system for all academic and university activities within the NextGen Smart University Platform.

It helps students, lecturers, coordinators, administrators, STAD staff, and restaurant owners organize their schedules and receive reminders for important events.

---

# Objectives

- Manage academic schedules.
- Manage university events.
- Display examinations.
- Display assignment deadlines.
- Display class schedules.
- Display meetings.
- Send event reminders.
- Improve time management.

---

# Scope

The Calendar Module includes:

- Academic Calendar
- Personal Calendar
- Class Schedule
- Examination Schedule
- Assignment Deadlines
- University Events
- Meetings
- Reminder System

---

# Actors

- Student
- Lecturer
- Coordinator
- Administrator
- STAD Staff
- Restaurant Owner

---

# Database Tables

## CalendarEvent

Purpose

Stores calendar events.

### Columns

- id
- user_id
- title
- description
- event_type
- start_datetime
- end_datetime
- location
- reminder_enabled
- created_at
- updated_at

---

## Reminder

Purpose

Stores reminder settings.

### Columns

- id
- event_id
- reminder_time
- reminder_method
- status

### Reminder Methods

- In-App
- Email
- Push Notification

---

# Relationships

User

↓

Calendar Event

↓

Reminder

---

# Event Types

- Class
- Examination
- Assignment
- Quiz
- Meeting
- Student Activity
- Food Court Event
- Holiday
- Personal Event

---

# Business Rules

- Users can manage only their own personal events.
- Academic events are generated automatically.
- Assignment deadlines are synchronized with the LMS.
- Examination schedules are synchronized with the AI Examination Module.
- Class schedules are synchronized with the Academic Module.
- Event reminders are generated automatically.

---

# Validation Rules

Event

- Title required.
- Start date required.
- End date required.
- End date must be after start date.

Reminder

- Reminder time must be before the event starts.

---

# Permissions

## Student

- View Calendar
- Create Personal Events
- Edit Personal Events
- Delete Personal Events

## Lecturer

- View Teaching Schedule
- Create Meetings
- View Academic Calendar

## Coordinator

- View Department Calendar

## Administrator

- Full Calendar Management

## STAD Staff

- Manage Student Activity Events

---

# Notifications

Users receive reminders for:

- Upcoming Classes
- Assignment Deadlines
- Quizzes
- Examinations
- Meetings
- University Events
- Club Activities

---

# Security

- JWT Authentication
- Role-Based Access Control
- User-specific calendar events
- Secure reminder delivery

---

# Indexes

CalendarEvent

- user_id
- start_datetime

Reminder

- event_id

---

# Reports

Calendar Reports

- Daily Schedule
- Weekly Schedule
- Monthly Schedule
- Upcoming Events
- Missed Events

---

# Performance

- Cache upcoming events.
- Load monthly calendars efficiently.
- Optimize reminder scheduling.
- Archive expired events.

---

# API Mapping

GET /api/calendar

GET /api/calendar/events

POST /api/calendar/events

PUT /api/calendar/events/{id}

DELETE /api/calendar/events/{id}

GET /api/calendar/reminders

---

# UI Pages

- Calendar Dashboard
- Monthly Calendar
- Weekly Calendar
- Daily Calendar
- Event Details
- Reminder Settings

---

# Dependencies

This module depends on:

- Authentication Module
- Academic Module
- LMS Module
- AI Examination Module
- Student Activities Module
- Notification Module

The following modules depend on this module:

- Student Portal
- Lecturer Portal
- Coordinator Portal
- Administrator Portal
- STAD Portal

---

# Future Expansion

- Google Calendar Integration
- Microsoft Outlook Integration
- AI Schedule Assistant
- Smart Conflict Detection
- Shared Calendars

---

# Notes

The Calendar Module acts as the central scheduling service for the entire NextGen Smart University Platform. It automatically synchronizes academic schedules, examinations, assignments, meetings, and student activities from all integrated modules while allowing users to manage their personal events securely.