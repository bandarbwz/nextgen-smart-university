# Backend Architecture

## Overview

The backend provides business logic, API services, authentication, database access, file management, and integration between all platform modules.

It is built as a native PHP MVC application with no framework, following `docs/PROJECT/004-Technology-Stack.md`.

---

# Technology Stack

- PHP 8.4
- MySQL 8
- PDO
- JWT
- Composer
- PHPMailer
- DomPDF
- PhpSpreadsheet

---

# Folder Structure

```
backend/

├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   ├── Middleware/
│   ├── Validation/
│   └── Helpers/
├── config/
├── routes/
├── public/
├── storage/
│   ├── uploads/
│   ├── reports/
│   ├── temp/
│   └── logs/
├── tests/
└── vendor/
```

`public/index.php` is the only entry point. Everything else sits outside the web root, including uploaded files.

---

# Backend Layers

Request

↓

Router

↓

Middleware

↓

Controller

↓

Service

↓

Model

↓

Database

↓

Response

Each layer has one job. Controllers validate input and delegate. Services hold all business rules. Models perform data access through PDO. A controller never contains a business rule, and a model never contains one either.

---

# Core Services

- Authentication
- Academic
- Attendance
- LMS
- Calendar
- Chat
- Finance
- Food Court
- Reports
- Download Center
- AI Examination

---

# Middleware

- Authentication
- Authorization
- Validation
- Error Handling
- Logging

---

# Database Access

Data access uses **PDO with native prepared statements**. Emulated prepares are disabled, so every value is bound by the database rather than interpolated into the SQL string.

All models extend a shared base model that provides `find`, `all`, `create`, `update`, `delete` and `exists`, plus soft delete support and LIKE wildcard escaping. Multi statement operations that must succeed or fail together run inside a transaction.

There is no ORM. Queries are written as SQL.

---

# Real Time Communication

Native PHP cannot host a persistent socket server, so the platform does **not** use Socket.IO.

Chat uses short polling. The client requests messages after a known identifier every few seconds, pauses while the browser tab is hidden, and catches up when the tab becomes visible again. This is polling and should be described as such, not as real time.

---

# File Upload

Uploads are handled by PHP's native upload handling. Files are stored outside the web root and are served only through an authenticated endpoint.

The upload type is decided by **sniffing the file contents**, not by trusting the extension or the client supplied MIME type. Each upload profile sets its own size limit and accepted types.

Supported files:

- PDF
- Images
- Videos
- Office Documents

---

# Artificial Intelligence Integration

Computer vision runs in a separate Python service, not in PHP. The backend calls it over HTTP at a configurable `AI_SERVICE_URL`.

When that service is not configured or cannot be reached, the affected endpoints return **503 Service Unavailable**. They never return a synthetic result. A missing proctor is never recorded as a passed check.

---

# Security

- JWT Authentication
- Password Hashing
- Role Validation
- Login Attempt Lockout
- Input Validation

Session and refresh tokens are stored hashed, never in plain text.

Repeated failed logins lock the account for a configured period. This is a lockout on authentication, not general request rate limiting across the API, which is **not implemented** and would belong at the web server or reverse proxy.

---

# Testing

The backend is covered by PHPUnit across three suites:

- **Unit** for pure logic
- **Integration** for business rules against a real database
- **Security** for access control and isolation between users

The suite builds a separate test database from the schema files on every run, so development data is never touched.

---

# Success Criteria

The backend is complete when all business logic is separated into services and every feature is accessible through secure REST APIs.
