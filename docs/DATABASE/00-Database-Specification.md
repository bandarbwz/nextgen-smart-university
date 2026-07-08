# Database Specification

## Purpose

This document defines the database standards, architecture, design principles, and implementation guidelines for the NextGen Smart University Platform.

It serves as the primary reference for database development and maintenance.

---

# Database Management System

The project uses:

- MySQL 8.x
- InnoDB Storage Engine
- UTF8MB4 Character Set

---

# Database Principles

The database must be:

- Secure
- Scalable
- Reliable
- Consistent
- Maintainable
- High Performance
- Fully Normalized

---

# Naming Conventions

## Tables

- Singular names
- PascalCase

Examples

- User
- Student
- Course
- Enrollment

---

## Columns

Use lowercase with underscores.

Examples

- first_name
- course_code
- created_at
- updated_at

---

## Primary Keys

Every table must contain

- id

Rules

- BIGINT UNSIGNED
- AUTO_INCREMENT
- PRIMARY KEY

---

## Foreign Keys

Every relationship must use foreign keys.

Examples

- user_id
- student_id
- course_id
- section_id

Foreign keys must enforce referential integrity.

---

# Audit Columns

Every business table should include:

- created_at
- updated_at

Optional

- deleted_at
- created_by
- updated_by

---

# Soft Delete Policy

Business tables should support soft deletion when historical records must be preserved.

Examples

- User
- Course
- Student
- Restaurant
- Event

---

# Character Set

Character Set

- utf8mb4

Collation

- utf8mb4_unicode_ci

This supports:

- English
- Arabic
- Emoji
- Unicode

---

# Date and Time

Store all timestamps using UTC.

Application layer converts timestamps to the user's local timezone.

---

# Data Types

IDs

- BIGINT UNSIGNED

Names

- VARCHAR(255)

Descriptions

- TEXT

Status

- ENUM or VARCHAR(50)

Boolean

- BOOLEAN

Money

- DECIMAL(10,2)

Date

- DATE

Date & Time

- DATETIME

Timestamp

- TIMESTAMP

---

# Constraints

Use:

- PRIMARY KEY
- FOREIGN KEY
- UNIQUE
- NOT NULL
- CHECK
- DEFAULT

where appropriate.

---

# Index Strategy

Create indexes for:

- Primary Keys
- Foreign Keys
- Email
- University ID
- Course Code
- Student Number
- Semester
- Frequently searched columns

Composite indexes should be used when necessary.

---

# Transactions

Use database transactions for operations involving multiple tables.

Examples

- Course Registration
- Grade Publishing
- Payment Processing
- Food Orders
- Examination Submission

---

# Performance Guidelines

- Normalize database tables.
- Avoid duplicate data.
- Optimize JOIN queries.
- Use indexes correctly.
- Return only required columns.
- Avoid unnecessary queries.

---

# Backup Strategy

Daily

- Incremental Backup

Weekly

- Full Backup

Monthly

- Archive Backup

Backups must be encrypted and verified.

---

# Security

The database must implement:

- Least Privilege Access
- Prepared Statements
- Parameterized Queries
- SQL Injection Protection
- Encrypted Connections
- Password Hashing
- Backup Encryption

---

# Database Modules

The database consists of:

- Authentication
- Academic
- Attendance
- Learning Management System (LMS)
- Chat
- Student Activities
- Food Court
- Finance
- AI Examination
- Notification Center
- Calendar
- Assessment System
- Role Management
- Grade Approval
- Reset Examination
- Reports
- Settings
- System
- Download Center

---

# Estimated Database Size

Expected scale:

- 50,000+ Users
- 10,000+ Courses
- Millions of Attendance Records
- Millions of Chat Messages
- Millions of LMS Files
- Millions of Notifications

The database must support future horizontal and vertical scaling.

---

# Documentation Standards

Every table must have:

- Purpose
- Columns
- Relationships
- Indexes
- Business Rules

Every relationship must be documented.

---

# Future Expansion

The database design supports future modules such as:

- Mobile Application
- Parent Portal
- Alumni Portal
- Library System
- Hostel Management
- Multi-Campus Support
- Multi-University Support

---

# Notes

This document defines the database foundation for the entire NextGen Smart University Platform. All SQL scripts, ER diagrams, API development, and backend implementation must follow these specifications.