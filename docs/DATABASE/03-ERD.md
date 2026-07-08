# Entity Relationship Diagram (ERD)

## Purpose

This document describes the Entity Relationship Diagram (ERD) for the NextGen Smart University Platform.

The ERD provides a high-level overview of the database structure and illustrates how entities are connected throughout the system.

---

# Objectives

- Visualize database structure.
- Define entity relationships.
- Support database development.
- Improve system understanding.
- Serve as the reference for SQL implementation.

---

# ERD Overview

The database is divided into the following logical modules:

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

Each module contains related entities while remaining connected through foreign key relationships.

---

# Core Entities

The primary entities in the system include:

- User
- Student
- Lecturer
- Coordinator
- Faculty
- Department
- Program
- Course
- Section
- Enrollment
- Attendance
- Assignment
- Quiz
- ChatRoom
- Restaurant
- Examination
- Notification
- CalendarEvent

These entities form the backbone of the platform.

---

# Core Relationship Flow

Authentication

```
Role
 │
User
 │
UserSession
 │
AuthenticationLog
```

---

Academic

```
Faculty
 │
Department
 │
Program
 │
Course
 │
Section
 │
Enrollment
 │
Student
```

---

Learning Management System

```
Section
 │
Course Material
 │
Assignment
 │
Submission
 │
Grade
```

---

Attendance

```
Section
 │
Attendance
 │
Attendance Excuse
```

---

Chat

```
Chat Room
 │
Message
 │
Attachment
 │
Reaction
```

---

Food Court

```
Restaurant
 │
Menu Item
 │
Order
 │
Payment
```

---

AI Examination

```
Examination
 │
Session
 │
Violation
 │
AI Report
```

---

Notification Center

```
User
 │
Notification
 │
Notification Preference
```

---

# Relationship Summary

| Type | Examples |
|------|----------|
| One-to-One | User → Student |
| One-to-Many | Course → Section |
| Many-to-Many | Student ↔ Section (Enrollment) |

---

# Database Normalization

The database follows Third Normal Form (3NF).

Design principles:

- No duplicate data.
- Every table has a primary key.
- Foreign keys enforce relationships.
- Business data is separated into logical modules.
- Lookup data is normalized.

---

# Primary Keys

Every table uses:

```
id
```

Type

```
BIGINT UNSIGNED
AUTO_INCREMENT
```

---

# Foreign Keys

Relationships use foreign keys such as:

- user_id
- student_id
- lecturer_id
- faculty_id
- department_id
- course_id
- section_id
- semester_id
- role_id

---

# Referential Integrity

The database enforces:

- Foreign Key Constraints
- ON UPDATE CASCADE
- ON DELETE RESTRICT

Business records use soft deletes where appropriate.

---

# Index Strategy

Indexes are created for:

- Primary Keys
- Foreign Keys
- Email
- University ID
- Student Number
- Course Code
- Frequently searched columns

Composite indexes are added where necessary.

---

# Scalability

The ERD is designed to support:

- 50,000+ Users
- Millions of Attendance Records
- Millions of Chat Messages
- Millions of Notifications
- Large LMS Storage
- Future Multi-Campus Expansion

---

# ERD File

The graphical ERD is stored separately in the project.

Reference files:

- docs/DATABASE/ERD.png
- docs/DATABASE/ERD.drawio
- docs/DATABASE/ERD.pdf

(Use the file(s) available in the repository.)

---

# Design Principles

- Modular Architecture
- High Cohesion
- Low Coupling
- Referential Integrity
- Scalable Design
- Secure Data Storage
- Maintainable Relationships

---

# Notes

The Entity Relationship Diagram is the master reference for the physical database design. All SQL schemas, migrations, ORM models, APIs, and backend services must follow the relationships defined in this document to ensure consistency across the NextGen Smart University Platform.