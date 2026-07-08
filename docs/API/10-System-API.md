# System API

## Overview

The System API provides platform-wide services including notifications, system settings, file management, audit logs, announcements, and health monitoring.

---

# Base URL

/api/v1/system

---

# Authentication

Bearer Token (JWT)

---

# Notifications

GET /notifications

PUT /notifications/{id}/read

DELETE /notifications/{id}

---

# Announcements

GET /announcements

POST /announcements

PUT /announcements/{id}

DELETE /announcements/{id}

---

# Settings

GET /settings

PUT /settings

---

# Files

POST /files/upload

GET /files/{id}

DELETE /files/{id}

---

# Audit Logs

GET /audit-logs

GET /audit-logs/{id}

---

# System Health

GET /health

GET /status

GET /metrics

---

# Background Jobs

GET /jobs

POST /jobs

GET /jobs/{id}

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
- Administrator Authorization
- File Validation
- Audit Logging

---

# Notes

The System API is used by all platform modules and provides shared system services.