# Calendar API

## Purpose

This document defines the Calendar REST APIs for the NextGen Smart University Platform.

The Calendar API manages academic schedules, examinations, assignments, meetings, university events, personal events, reminders, and calendar synchronization across all system modules.

---

# Base URL

```
/api/v1/calendar
```

---

# Authentication

All endpoints require:

```
Authorization: Bearer <JWT_TOKEN>
```

---

# Content Type

```
Content-Type: application/json
```

---

# Calendar Event APIs

---

## Get Calendar Events

```
GET /api/v1/calendar/events
```

---

## Get Calendar Event

```
GET /api/v1/calendar/events/{id}
```

---

## Create Calendar Event

```
POST /api/v1/calendar/events
```

---

## Update Calendar Event

```
PUT /api/v1/calendar/events/{id}
```

---

## Delete Calendar Event

```
DELETE /api/v1/calendar/events/{id}
```

---

## Get Daily Schedule

```
GET /api/v1/calendar/events/daily
```

---

## Get Weekly Schedule

```
GET /api/v1/calendar/events/weekly
```

---

## Get Monthly Schedule

```
GET /api/v1/calendar/events/monthly
```

---

# Reminder APIs

---

## Get Reminders

```
GET /api/v1/calendar/reminders
```

---

## Create Reminder

```
POST /api/v1/calendar/reminders
```

---

## Update Reminder

```
PUT /api/v1/calendar/reminders/{id}
```

---

## Delete Reminder

```
DELETE /api/v1/calendar/reminders/{id}
```

---

## Mark Reminder as Completed

```
PUT /api/v1/calendar/reminders/{id}/complete
```

---

# Synchronization APIs

---

## Synchronize Calendar

```
POST /api/v1/calendar/sync
```

---

## Import Calendar

```
POST /api/v1/calendar/import
```

---

## Export Calendar

```
GET /api/v1/calendar/export
```

---

# Validation Rules

Calendar Event

- Title is required.
- Start date is required.
- End date is required.
- End date must be after start date.

Reminder

- Reminder time is required.
- Reminder must be scheduled before the event.

Synchronization

- User account must be authenticated.
- Duplicate events are automatically merged.

---

# Security

- JWT Authentication
- Role-Based Access Control
- User Calendar Isolation
- Input Validation
- Audit Logging

---

# HTTP Status Codes

| Code | Description |
|------|-------------|
|200|OK|
|201|Created|
|400|Bad Request|
|401|Unauthorized|
|403|Forbidden|
|404|Not Found|
|409|Conflict|
|422|Validation Error|
|500|Internal Server Error|

---

# Permissions

Student

- View Calendar
- Create Personal Events
- Manage Personal Reminders

Lecturer

- View Teaching Schedule
- Manage Course Events

Coordinator

- Manage Academic Events

Administrator

- Full Calendar Management

---

# Business Rules

- Academic events are generated automatically from the Academic Module.
- Assignment deadlines are synchronized automatically from the LMS.
- Examination schedules are synchronized automatically from the Assessment and AI Examination modules.
- Personal events are visible only to their owner.
- Reminder notifications are generated automatically before each event.
- Calendar updates are reflected immediately across all connected modules.

---

# Dependencies

This API depends on:

- Authentication API
- Academic API

Related APIs

- LMS API
- Assessment API
- AI Examination API
- Student Activities API
- Notification API

---

# Notes

The Calendar API provides centralized scheduling for academic and personal activities. It automatically synchronizes events from multiple modules to ensure users always have an accurate and up-to-date university calendar.