# Security Rules

## Purpose

This document defines the security requirements for the NextGen Smart University Platform.

All developers and AI assistants must follow these rules.

---

# Authentication

- Use JWT Authentication.
- Never store plain text passwords.
- Hash passwords using bcrypt.
- Expire tokens after a secure period.
- Logout must invalidate the session.

---

# Authorization

Use Role-Based Access Control (RBAC).

Roles:

- Student
- Lecturer
- Coordinator
- Administrator
- Restaurant Owner
- STAD Staff

Users must only access resources they are authorized to use.

---

# Password Rules

Password must contain:

- Minimum 8 characters
- One uppercase letter
- One lowercase letter
- One number
- One special character

Passwords must never be returned by the API.

---

# Input Validation

Validate every request.

Validate:

- Required fields
- Email format
- Phone number
- File types
- File size
- Numeric values

Never trust frontend validation alone.

---

# SQL Injection

- Always use prepared statements.
- Never build SQL queries using string concatenation.

---

# XSS Protection

- Escape user-generated content.
- Sanitize all HTML input.
- Validate rich text content.

---

# CSRF Protection

Protect all authenticated requests.

---

# File Upload Security

Allow only approved file types.

Validate:

- Extension
- MIME Type
- File Size

Rename uploaded files.

Store uploads outside the public root when possible.

Never execute uploaded files.

---

# API Security

- Protect private endpoints.
- Return proper HTTP status codes.
- Never expose internal errors.
- Validate JWT on every protected request.

---

# Logging

Log:

- Login attempts
- Failed logins
- Password changes
- User creation
- User deletion
- Permission changes
- AI exam violations

Do not log passwords or sensitive personal data.

---

# Session Security

- Logout invalidates the session.
- Expired tokens require re-login.
- Protect against session hijacking.

---

# Data Protection

Protect:

- Student information
- Lecturer information
- Financial data
- Grades
- Exam records
- Restaurant orders

Sensitive data must never be exposed.

---

# Backup

Back up the database regularly.

Verify backups can be restored successfully.

---

# Final Rule

Security is mandatory.

Never sacrifice security for convenience.