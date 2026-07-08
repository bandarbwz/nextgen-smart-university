# Security Rules

## Purpose

This document defines the official security requirements for the NextGen Smart University Platform (NSUP).

Every developer and AI assistant must follow these security rules throughout development, testing, and deployment.

Security must never be sacrificed for convenience or development speed.

---

# Security Principles

The system follows these security principles:

- Confidentiality
- Integrity
- Availability
- Least Privilege
- Defense in Depth
- Secure by Design

---

# Authentication

The platform uses JWT Authentication.

Requirements:

- Every user must authenticate using a university account.
- Passwords must never be stored in plain text.
- Passwords must be hashed using PHP `password_hash()`.
- Passwords must be verified using `password_verify()`.
- JWT tokens must expire after a secure period.
- Expired tokens require users to log in again.
- Logout invalidates the current session.

---

# Authorization

The system uses Role-Based Access Control (RBAC).

Supported Roles

- Student
- Lecturer
- Coordinator
- Administrator
- STAD Staff
- Restaurant Owner

A user may have one or more roles.

Every protected request must verify that the authenticated user has permission before accessing any resource.

---

# Password Policy

Passwords must contain:

- Minimum 8 characters
- One uppercase letter
- One lowercase letter
- One number
- One special character

Temporary passwords must be changed after the first login.

Passwords must never be returned in API responses.

---

# Input Validation

Every request must be validated.

Validate:

- Required fields
- Email format
- Phone number
- Numeric values
- Dates
- Credit Hours
- File Types
- File Size

Frontend validation improves user experience only.

All security validation must also be performed by the PHP backend.

---

# SQL Injection Protection

Always use:

- PDO Prepared Statements
- Parameter Binding

Never build SQL queries using string concatenation.

---

# Cross-Site Scripting (XSS)

Protect against XSS by:

- Escaping user-generated content
- Sanitizing HTML input
- Validating rich text content

Never render raw user input directly.

---

# Cross-Site Request Forgery (CSRF)

Protect authenticated requests using CSRF protection.

Validate every authenticated request before processing.

---

# File Upload Security

Allowed upload types include:

- PDF
- DOCX
- JPG
- JPEG
- PNG

Every uploaded file must be validated.

Check:

- Extension
- MIME Type
- File Size

Uploaded files must:

- Be renamed using unique filenames.
- Be stored outside the public web root whenever possible.
- Never be executable.

---

# API Security

Every protected endpoint must:

- Validate JWT
- Validate User Role
- Validate Request Data

Return appropriate HTTP status codes.

Never expose internal server errors.

---

# AI Security

The AI service communicates only with the PHP backend.

React must never communicate directly with the AI service.

AI examination reports are accessible only to authorized users.

The AI system generates monitoring reports only.

Academic decisions are always made by authorized university staff.

---

# Logging

Log important security events:

- Login
- Failed Login
- Password Change
- User Creation
- User Deletion
- Permission Changes
- Course Registration
- Grade Submission
- AI Examination Violations

Never log:

- Passwords
- JWT Tokens
- Sensitive Personal Information

---

# Session Security

- Logout invalidates the current session.
- Expired JWT tokens require re-authentication.
- Protect against session hijacking.
- Automatically terminate inactive sessions after the configured timeout.

---

# Data Protection

Protect all sensitive information including:

- Student Records
- Lecturer Records
- Academic Results
- Attendance Records
- Financial Records
- Examination Reports
- Restaurant Orders

Sensitive information must never be exposed to unauthorized users.

---

# Backup and Recovery

Back up the database regularly.

Backups should be encrypted.

Verify that backups can be restored successfully before production deployment.

---

# Security Testing

Before deployment perform:

- Authentication Testing
- Authorization Testing
- SQL Injection Testing
- XSS Testing
- CSRF Testing
- File Upload Testing
- API Security Testing

All critical vulnerabilities must be resolved before release.

---

# Final Rule

Security is mandatory throughout the entire project.

Every feature must comply with these security requirements before it is considered complete.