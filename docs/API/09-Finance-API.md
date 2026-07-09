# AI Examination API

## Purpose

This document defines the AI Examination REST APIs for the NextGen Smart University Platform.

The AI Examination API manages online examinations, AI monitoring, face detection, eye tracking, head pose detection, browser monitoring, violation detection, examination recordings, and AI-generated reports.

---

# Base URL

```
/api/v1/ai-exam
```

---

# Authentication

All endpoints require:

```
Authorization: Bearer <JWT_TOKEN>
```

---

# Content Type

Standard Requests

```
application/json
```

Media Upload

```
multipart/form-data
```

---

# Examination APIs

---

## Get Examinations

```
GET /api/v1/ai-exam/examinations
```

---

## Get Examination

```
GET /api/v1/ai-exam/examinations/{id}
```

---

## Create Examination

```
POST /api/v1/ai-exam/examinations
```

Permissions

- Lecturer

---

## Update Examination

```
PUT /api/v1/ai-exam/examinations/{id}
```

---

## Delete Examination

```
DELETE /api/v1/ai-exam/examinations/{id}
```

---

# Examination Session APIs

---

## Start Examination

```
POST /api/v1/ai-exam/session/start
```

Permissions

- Student

---

## Finish Examination

```
POST /api/v1/ai-exam/session/end
```

Permissions

- Student

---

## Pause Examination

```
PUT /api/v1/ai-exam/session/pause
```

---

## Resume Examination

```
PUT /api/v1/ai-exam/session/resume
```

---

# AI Verification APIs

---

## Face Verification

```
POST /api/v1/ai-exam/verify-face
```

---

## Eye Tracking

```
POST /api/v1/ai-exam/eye-tracking
```

---

## Head Pose Detection

```
POST /api/v1/ai-exam/head-pose
```

---

## Browser Monitoring

```
POST /api/v1/ai-exam/browser-monitor
```

---

## Device Monitoring

```
POST /api/v1/ai-exam/device-monitor
```

---

# Violation APIs

---

## Report Violation

```
POST /api/v1/ai-exam/violations
```

---

## Get Violations

```
GET /api/v1/ai-exam/violations
```

---

## Get Student Violations

```
GET /api/v1/ai-exam/violations/{student_id}
```

---

# Recording APIs

---

## Upload Recording

```
POST /api/v1/ai-exam/recordings
```

---

## View Recording

```
GET /api/v1/ai-exam/recordings/{id}
```

---

# AI Report APIs

---

## Generate AI Report

```
POST /api/v1/ai-exam/reports/generate
```

---

## Get AI Report

```
GET /api/v1/ai-exam/reports/{id}
```

---

## Download AI Report

```
GET /api/v1/ai-exam/reports/{id}/download
```

---

# Validation Rules

Examination

- Examination must be active.
- Student must be enrolled.
- Examination time must be valid.

AI Verification

- Camera access required.
- Single face detected.
- Face must match registered student.
- Eye tracking must remain active.

Violation

- Violation type required.
- Timestamp required.
- AI confidence score required.

---

# Security

- JWT Authentication
- Role-Based Access Control
- HTTPS Only
- Camera Verification
- Browser Lockdown
- Device Validation
- Secure Video Storage
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

- Start Examination
- Submit Examination
- View Personal AI Report

Lecturer

- Create Examination
- View AI Reports
- Review Violations

Coordinator

- Review Examination Reports

Administrator

- Full AI Examination Management

---

# Business Rules

- Only enrolled students may access examinations.
- AI verification must succeed before the examination begins.
- Camera must remain active throughout the examination.
- Browser tab switching is recorded as a violation.
- Multiple faces trigger an immediate violation.
- AI violations are automatically included in the examination report.
- Examination sessions are automatically terminated when critical security policies are violated.

---

# Dependencies

This API depends on:

- Authentication API
- Academic API

Related APIs

- Attendance API
- Assessment API
- Notification API
- Reports API
- Reset Examination API

---

# Notes

The AI Examination API provides secure online examination management using artificial intelligence. It integrates face verification, eye tracking, head pose detection, browser monitoring, and automated violation reporting to maintain academic integrity while supporting large-scale online examinations.