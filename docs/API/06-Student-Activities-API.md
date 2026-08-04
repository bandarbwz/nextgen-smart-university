# Student Activities API

## Purpose

This document defines the Student Activities REST APIs for the NextGen Smart University Platform.

The Student Activities API manages university clubs, events, competitions, workshops, seminars, event registration, event attendance and student activity points.

> **This module is not built yet.** This document is the contract to build against, derived from `docs/FEATURES/06-Student-Activities.md`. Nothing here is live. The previous version of this file was a byte for byte copy of the Chat API specification, so the endpoint contract did not exist at all.

---

# Base URL

```
/api/v1/activities
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

# Response Envelope

Every response follows the platform envelope.

Success:

```json
{
    "success": true,
    "message": "Events retrieved.",
    "data": {}
}
```

Failure:

```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "event_name": ["Event name is required."]
    }
}
```

---

# Club APIs

---

## Get Clubs

```
GET /api/v1/activities/clubs
```

Optional query parameters:

| Parameter | Description |
|---|---|
| `category` | Filter by club category |
| `status` | `active` or `inactive`. Students see active clubs only |

### Success Response

```json
{
    "success": true,
    "message": "Clubs retrieved.",
    "data": {
        "clubs": [
            {
                "id": 1,
                "club_name": "Robotics Society",
                "description": "Builds and competes with autonomous robots.",
                "category": "Technology",
                "advisor_name": "Dr. Sami Lecturer",
                "president_name": "Lina Student",
                "status": "active"
            }
        ]
    }
}
```

---

## Get Club

```
GET /api/v1/activities/clubs/{id}
```

Returns the club with its upcoming events.

---

## Create Club

```
POST /api/v1/activities/clubs
```

Permissions: STAD Staff, Administrator

### Request Body

```json
{
    "club_name": "Robotics Society",
    "description": "Builds and competes with autonomous robots.",
    "category": "Technology",
    "advisor_id": 4,
    "president_id": 12
}
```

### Success Response

`201 Created` with the created club.

---

## Update Club

```
PUT /api/v1/activities/clubs/{id}
```

Permissions: STAD Staff, Administrator

---

## Delete Club

```
DELETE /api/v1/activities/clubs/{id}
```

Permissions: Administrator

A club with events is soft deleted, never removed, so its event history survives.

---

# Event APIs

---

## Get Events

```
GET /api/v1/activities/events
```

Optional query parameters:

| Parameter | Description |
|---|---|
| `club_id` | Events for one club |
| `status` | `draft`, `published`, `cancelled`, `completed` |
| `from` | Events on or after this date |
| `to` | Events on or before this date |

Students see published events only. A student's own registration status is included so the client can render the correct action.

### Success Response

```json
{
    "success": true,
    "message": "Events retrieved.",
    "data": {
        "events": [
            {
                "id": 7,
                "club_id": 1,
                "event_name": "Line Follower Competition",
                "description": "Teams of three.",
                "venue": "Engineering Hall B",
                "event_date": "2026-09-18",
                "start_time": "09:00:00",
                "end_time": "13:00:00",
                "registration_deadline": "2026-09-15 23:59:59",
                "maximum_participants": 60,
                "registered_count": 41,
                "seats_remaining": 19,
                "qr_enabled": true,
                "status": "published",
                "my_registration_status": null
            }
        ]
    }
}
```

---

## Get Event

```
GET /api/v1/activities/events/{id}
```

---

## Create Event

```
POST /api/v1/activities/events
```

Permissions: STAD Staff, Administrator

### Request Body

```json
{
    "club_id": 1,
    "event_name": "Line Follower Competition",
    "description": "Teams of three.",
    "venue": "Engineering Hall B",
    "event_date": "2026-09-18",
    "start_time": "09:00:00",
    "end_time": "13:00:00",
    "registration_deadline": "2026-09-15 23:59:59",
    "maximum_participants": 60,
    "qr_enabled": true,
    "status": "draft"
}
```

### Error Responses

| Code | Reason |
|---|---|
| 422 | The registration deadline is not before the event date |
| 422 | Maximum participants is not greater than zero |
| 403 | The caller is not STAD Staff or an Administrator |

---

## Update Event

```
PUT /api/v1/activities/events/{id}
```

Permissions: STAD Staff, Administrator

Reducing `maximum_participants` below the number of approved registrations is rejected with `409`.

---

## Cancel Event

```
PUT /api/v1/activities/events/{id}/cancel
```

Permissions: STAD Staff, Administrator

Sets the status to `cancelled` and notifies every registered student. Cancelling is preferred over deleting so the record and its notifications survive.

---

## Delete Event

```
DELETE /api/v1/activities/events/{id}
```

Permissions: Administrator

An event with registrations cannot be deleted. Cancel it instead. Returns `409`.

---

# Registration APIs

---

## Register for an Event

```
POST /api/v1/activities/register
```

Permissions: Student

### Request Body

```json
{
    "event_id": 7
}
```

The student is taken from the token, never from the request body.

### Success Response

`201 Created`

```json
{
    "success": true,
    "message": "Registration submitted.",
    "data": {
        "registration": {
            "id": 88,
            "event_id": 7,
            "student_id": 12,
            "registration_date": "2026-09-01 10:14:22",
            "status": "Pending"
        }
    }
}
```

### Error Responses

| Code | Reason |
|---|---|
| 409 | The student is already registered for this event |
| 409 | The registration deadline has passed |
| 409 | The event is full |
| 409 | The event is not published, or is cancelled |
| 403 | The student account is not active |

---

## Cancel a Registration

```
PUT /api/v1/activities/registrations/{id}/cancel
```

Permissions: Student, for their own registration only

A registration cannot be cancelled once attendance has been recorded. Returns `409`.

Another student's registration returns `404`, not `403`, so the identifier does not leak.

---

## Get My Registrations

```
GET /api/v1/activities/registrations
```

Permissions: Student

---

## Get Event Registrations

```
GET /api/v1/activities/events/{id}/registrations
```

Permissions: STAD Staff, Administrator

Optional `status` query parameter.

---

## Approve a Registration

```
PUT /api/v1/activities/registrations/{id}/approve
```

Permissions: STAD Staff, Administrator

Approving beyond `maximum_participants` is rejected with `409`. The seat is taken on approval, not on request.

---

## Reject a Registration

```
PUT /api/v1/activities/registrations/{id}/reject
```

Permissions: STAD Staff, Administrator

### Request Body

```json
{
    "reason": "Team already has three members."
}
```

---

# Attendance APIs

Event attendance reuses the QR mechanism from the Attendance module.

---

## Open QR Attendance

```
POST /api/v1/activities/events/{id}/qr
```

Permissions: STAD Staff, Administrator

Issues a short lived token that the organiser displays. The token expires automatically and may only be opened while the event is running.

### Success Response

```json
{
    "success": true,
    "message": "QR attendance opened.",
    "data": {
        "token": "3f9c1a8e5b2d47f0",
        "expires_at": "2026-09-18 09:10:00"
    }
}
```

### Error Responses

| Code | Reason |
|---|---|
| 409 | The event is not currently running |
| 409 | QR attendance is disabled for this event (`qr_enabled` is false) |

---

## Record Attendance

```
POST /api/v1/activities/attendance
```

Permissions: Student

### Request Body

```json
{
    "token": "3f9c1a8e5b2d47f0"
}
```

### Error Responses

| Code | Reason |
|---|---|
| 409 | The token has expired |
| 409 | Attendance has already been recorded |
| 403 | The student has no approved registration for this event |
| 404 | The token is not valid |

---

## Record Attendance Manually

```
POST /api/v1/activities/attendance/manual
```

Permissions: STAD Staff, Administrator

### Request Body

```json
{
    "registration_id": 88,
    "attendance_method": "Manual"
}
```

The verifying user is recorded in `verified_by`. Students can never write attendance for themselves through this endpoint.

---

## Get Event Attendance

```
GET /api/v1/activities/events/{id}/attendance
```

Permissions: STAD Staff, Administrator

---

# Activity Point APIs

---

## Get My Activity Points

```
GET /api/v1/activities/points
```

Permissions: Student

### Success Response

```json
{
    "success": true,
    "message": "Activity points retrieved.",
    "data": {
        "total_points": 45,
        "points": [
            {
                "id": 21,
                "event_id": 7,
                "event_name": "Line Follower Competition",
                "points": 15,
                "awarded_date": "2026-09-18"
            }
        ]
    }
}
```

---

## Get Student Activity Points

```
GET /api/v1/activities/points/{student_id}
```

Permissions: STAD Staff, Administrator

A student requesting another student returns `403`.

---

## Award Activity Points

```
POST /api/v1/activities/points
```

Permissions: STAD Staff, Administrator

### Request Body

```json
{
    "student_id": 12,
    "event_id": 7,
    "points": 15
}
```

### Error Responses

| Code | Reason |
|---|---|
| 409 | The student has no verified attendance for this event |
| 409 | Points have already been awarded for this student and event |
| 422 | Points are not greater than zero |

---

# Report APIs

```
GET /api/v1/activities/reports/attendance
GET /api/v1/activities/reports/membership
GET /api/v1/activities/reports/participation
GET /api/v1/activities/reports/points
GET /api/v1/activities/reports/performance
```

Permissions: STAD Staff, Administrator

Each returns the platform report shape so the existing exporter can render it:

```json
{
    "title": "Event Attendance Report",
    "columns": ["student_number", "student_name", "event_name", "attendance_time"],
    "rows": []
}
```

Export uses the existing Reports endpoint with `csv`, `pdf` or `xlsx`, rather than a second export path in this module.

---

# Validation Rules

Club

- Club name is required.
- Club name must be unique.

Event

- Event name is required.
- Event date is required.
- The registration deadline must be before the event date.
- The start time must be before the end time.
- Maximum participants must be greater than zero.

Registration

- The student must be active.
- Duplicate registrations are not allowed.

Activity Points

- Points must be greater than zero.

---

# Business Rules

- Students may only register once per event.
- Registration closes after the deadline.
- The maximum participant limit cannot be exceeded, and the seat is taken on approval rather than on request.
- QR attendance is available only while the event is running, and the token expires automatically.
- Students earn activity points only after verified attendance.
- Event organisers can approve or reject registrations.
- A cancelled event notifies every registered student.
- An approved event that clashes with a scheduled class may generate an attendance excuse, per `docs/FEATURES/06-Student-Activities.md`.

---

# Security

- JWT authentication on every endpoint.
- Role based access control.
- The student identity always comes from the token, never from the request body.
- Attendance records cannot be created or modified by the student they belong to, other than by scanning a valid QR token.
- QR tokens are short lived and single use per registration.
- Another student's registration returns `404` rather than `403`, so existence is not leaked.
- All approvals, rejections and manual attendance entries are logged with the acting user.

---

# HTTP Status Codes

| Code | Description |
|---|---|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 409 | Conflict |
| 422 | Validation Error |
| 500 | Internal Server Error |

---

# Permissions

Student

- View Clubs
- View Events
- Register for Events
- Cancel Registration
- View Participation History
- View Own Activity Points

STAD Staff

- Create and Manage Clubs
- Create and Manage Events
- Approve and Reject Registrations
- Open QR Attendance
- Record Manual Attendance
- Award Activity Points
- Run Student Activities Reports

Administrator

- Full Student Activities Access

---

# Dependencies

This API depends on:

- Authentication API
- Academic API
- Attendance API

Related APIs:

- Notification API
- Reports API

---

# Notes

The Student Activities API manages the non academic side of university life. It integrates with the Attendance module through QR attendance, with the Notification module for reminders and approval outcomes, and with the Reports module for participation reporting.

Two things an implementer should settle before starting:

1. **The STAD Staff role exists** among the six defined roles, so unlike Finance, this module does not need a new role.
2. **The `EventRegistration` status set is `Pending`, `Approved`, `Rejected`, `Cancelled`** per the feature document. Decide whether registration is approved automatically when a club does not need vetting, or always starts `Pending`. This specification assumes it always starts `Pending`.
