# Attendance API

## Purpose

This document defines the Attendance REST APIs for the NextGen Smart University Platform.

The Attendance API manages lecture attendance, QR attendance, GPS verification, AI face verification, attendance reports, attendance excuses, and attendance statistics.

---

# Base URL

```
/api/v1/attendance
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

# QR Attendance APIs

---

## Generate QR Session

Generate a QR code for a lecture.

### Endpoint

```
POST /api/v1/attendance/qr-session
```

### Permissions

- Lecturer

---

## Get Active QR Session

### Endpoint

```
GET /api/v1/attendance/qr-session/{section_id}
```

---

## Close QR Session

### Endpoint

```
PUT /api/v1/attendance/qr-session/{id}/close
```

---

# Attendance APIs

---

## Scan QR Code

### Endpoint

```
POST /api/v1/attendance/scan
```

### Request Body

```json
{
    "qr_token":"TOKEN",
    "latitude":3.0738,
    "longitude":101.5183
}
```

---

## Verify GPS

### Endpoint

```
POST /api/v1/attendance/verify-location
```

---

## Verify Face

### Endpoint

```
POST /api/v1/attendance/verify-face
```

---

## Record Attendance

### Endpoint

```
POST /api/v1/attendance
```

---

## Manual Attendance

### Endpoint

```
PUT /api/v1/attendance/manual
```

---

## Update Attendance

### Endpoint

```
PUT /api/v1/attendance/{id}
```

---

## Delete Attendance

### Endpoint

```
DELETE /api/v1/attendance/{id}
```

---

# Attendance Excuse APIs

---

## Submit Excuse

### Endpoint

```
POST /api/v1/attendance/excuse
```

---

## Get Excuses

### Endpoint

```
GET /api/v1/attendance/excuse
```

---

## Approve Excuse

### Endpoint

```
PUT /api/v1/attendance/excuse/{id}/approve
```

---

## Reject Excuse

### Endpoint

```
PUT /api/v1/attendance/excuse/{id}/reject
```

---

# Reports APIs

---

## Student Attendance

### Endpoint

```
GET /api/v1/attendance/student/{student_id}
```

---

## Section Attendance

### Endpoint

```
GET /api/v1/attendance/section/{section_id}
```

---

## Lecturer Attendance

### Endpoint

```
GET /api/v1/attendance/lecturer/{lecturer_id}
```

---

## Daily Report

### Endpoint

```
GET /api/v1/attendance/reports/daily
```

---

## Monthly Report

### Endpoint

```
GET /api/v1/attendance/reports/monthly
```

---

## Attendance Statistics

### Endpoint

```
GET /api/v1/attendance/statistics
```

---

# Validation Rules

Attendance

- Student must be enrolled.
- QR session must be active.
- QR token must be valid.
- GPS verification must pass.
- AI verification required for online attendance.
- Duplicate attendance is not allowed.

Attendance Excuse

- Student required.
- Attendance record required.
- Reason required.
- Supporting document required when applicable.

---

# Security

- JWT Authentication
- Role-Based Access Control
- QR Token Validation
- GPS Verification
- AI Face Verification
- Audit Logging
- Rate Limiting

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

- Scan QR
- View Attendance
- Submit Excuse

Lecturer

- Generate QR
- Record Attendance
- Manual Attendance
- Review Excuses

Coordinator

- View Reports

Administrator

- Full Attendance Management

---

# Business Rules

- One attendance record per student per class.
- QR codes expire automatically.
- Students cannot edit attendance.
- Manual attendance is logged.
- GPS must be inside the configured campus radius.
- AI verification is required for online attendance.
- Attendance reports update automatically.

---

# Dependencies

This API depends on:

- Authentication API
- Academic API

Related APIs

- AI Exam API
- Notification API
- Reports API

---

# Notes

The Attendance API provides secure attendance management through QR codes, GPS verification, AI face verification, manual attendance, attendance excuses, and reporting. It integrates directly with the Academic, AI Examination, Notification, and Reporting modules.