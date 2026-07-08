# AI Exam API

## Overview

The AI Exam API manages online examinations, AI monitoring, violation detection, exam recordings, and AI-generated reports.

---

# Base URL

/api/v1/ai-exam

---

# Authentication

Bearer Token (JWT)

---

# Exams

GET /exams

GET /exams/{id}

POST /exams

PUT /exams/{id}

DELETE /exams/{id}

---

# Exam Sessions

POST /sessions/start

POST /sessions/end

GET /sessions

GET /sessions/{id}

---

# AI Monitoring

POST /monitor/start

POST /monitor/stop

GET /monitor/status

---

# Violations

GET /violations

GET /violations/{id}

POST /violations

PUT /violations/{id}/review

---

# Reports

GET /reports

GET /reports/{id}

DOWNLOAD /reports/{id}

---

# Recordings

GET /recordings

GET /recordings/{id}

DELETE /recordings/{id}

---

# Response Codes

200

201

400

401

403

404

422

500

---

# Security

- JWT Authentication
- Camera Validation
- Session Validation
- AI Permission Validation

---

# Notes

Integrates with LMS, Academic, Notification, and Lecturer Portal.