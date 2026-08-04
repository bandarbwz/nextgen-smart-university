# Security Architecture

## Overview

The Security Architecture defines how the platform protects user accounts, data, APIs, files, and system resources.

---

# Authentication

- JWT Authentication
- Password Hashing
- Session Timeout

---

# Authorization

Role-Based Access Control (RBAC)

Supported Roles

- Student
- Lecturer
- Coordinator
- Administrator
- STAD Staff
- Restaurant Owner

---

# API Security

- HTTPS
- JWT Validation
- Rate Limiting
- Request Validation

---

# File Security

- File Type Validation
- File Size Limits
- Malware Scanning
- Secure Storage

---

# Database Security

- Prepared Statements
- PDO with emulated prepares disabled
- Foreign Key Constraints
- Escaped LIKE wildcards

Emulation is turned off so values are bound by the database rather than interpolated into the SQL string by the driver.

---

# AI Security

- Secure Camera Access
- Exam Session Validation
- Violation Logging

---

# Monitoring

The platform records:

- Login Activity
- Audit Logs
- Failed Login Attempts
- Security Events

---

# Future Enhancements

- Two-Factor Authentication
- Device Verification
- Biometric Login
- Security Dashboard

---

# Success Criteria

The platform is secure against unauthorized access, common web vulnerabilities, and data breaches.