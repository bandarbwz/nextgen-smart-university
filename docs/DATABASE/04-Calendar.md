# Calendar Database Module

## Purpose

This document defines the database design for the Calendar Module of the NextGen Smart University Platform.

The Calendar Module centralizes academic schedules, examinations, assignments, meetings, university events, and personal events while providing reminder functionality for all users.

---

# Objectives

- Store calendar events.
- Manage reminders.
- Organize academic schedules.
- Support personal calendars.
- Synchronize events across modules.
- Improve scheduling and time management.

---

# Database Tables

## CalendarEvent

Purpose

Stores all calendar events.

### Columns

- id
- user_id
- title
- description
- event_type
- module
- reference_id
- start_datetime
- end_datetime
- location
- color
- is_all_day
- reminder_enabled
- status
- created_at
- updated_at

---

## Reminder

Purpose

Stores reminder information for calendar events.

### Columns

- id
- calendar_event_id
- reminder_time
- reminder_method
- reminder_status
- created_at

---

# Event Types

Supported event types:

- Class
- Assignment
- Quiz
- Examination
- Meeting
- Student Activity
- Food Order Pickup
- Payment Deadline
- Holiday
- Personal Event

---

# Reminder Methods

Supported reminder methods:

- In-App Notification
- Email
- Push Notification

---

# Relationships

User

↓

CalendarEvent

↓

Reminder

---

CalendarEvent

↓

Related Module

Examples:

- Course
- Assignment
- Quiz
- Examination
- Event

---

# Business Rules

- Every event belongs to one user or one system module.
- Academic events are created automatically.
- Personal events are created by users.
- Reminder time must be earlier than the event.
- Completed events remain in history.
- Cancelled events are marked inactive instead of deleted.

---

# Validation Rules

Calendar Event

- Title required.
- Event type required.
- Start date required.
- End date required.
- End date must be after start date.

Reminder

- Reminder time required.
- Reminder method required.

---

# Indexes

CalendarEvent

- user_id
- event_type
- module
- start_datetime
- end_datetime

Reminder

- calendar_event_id

---

# Synchronization

The Calendar Module synchronizes automatically with:

- Academic Module
- LMS Module
- Assessment System
- AI Examination Module
- Student Activities Module
- Food Court Module
- Finance Module

---

# Notifications

Automatic reminders are generated for:

- Upcoming Classes
- Assignment Deadlines
- Quiz Schedule
- Examination Schedule
- Meetings
- University Events
- Tuition Payment Deadlines
- Food Order Pickup

---

# Performance

- Cache upcoming events.
- Index calendar searches.
- Archive expired reminders.
- Load events by date range.
- Optimize monthly calendar queries.

---

# Security

- JWT Authentication
- Role-Based Access Control
- User-specific calendar access
- Audit logging
- Secure reminder delivery

---

# API Mapping

GET /api/calendar

GET /api/calendar/events

POST /api/calendar/events

PUT /api/calendar/events/{id}

DELETE /api/calendar/events/{id}

GET /api/calendar/reminders

PUT /api/calendar/reminders/{id}

---

# Future Expansion

- Google Calendar Integration
- Microsoft Outlook Integration
- Apple Calendar Integration
- AI Schedule Assistant
- Automatic Conflict Detection
- Shared Calendars

---

# Notes

The Calendar database is the scheduling backbone of the NextGen Smart University Platform. It integrates with all major modules to provide a unified academic and personal scheduling experience while maintaining secure, scalable, and efficient event management.