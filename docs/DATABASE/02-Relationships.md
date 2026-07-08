# Database Relationships

## Purpose

This document defines all database relationships used in the NextGen Smart University Platform.

It serves as the reference for designing foreign keys, enforcing referential integrity, and maintaining data consistency across all modules.

---

# Relationship Types

The database uses the following relationship types:

- One-to-One (1:1)
- One-to-Many (1:N)
- Many-to-Many (N:M)

---

# One-to-One Relationships

## User → Student

One User account belongs to one Student profile.

```
User
│
└── Student
```

---

## User → Lecturer

One User account belongs to one Lecturer profile.

```
User
│
└── Lecturer
```

---

## User → Restaurant Owner

One User account belongs to one Restaurant profile.

---

## User → STAD Staff

One User account belongs to one STAD Staff profile.

---

# One-to-Many Relationships

## Faculty → Department

One Faculty has many Departments.

```
Faculty
│
├── Department
├── Department
└── Department
```

---

## Department → Program

One Department has many Programs.

---

## Program → Course

One Program contains many Courses.

---

## Course → Section

One Course can have many Sections.

---

## Semester → Section

One Semester contains many Sections.

---

## Lecturer → Section

One Lecturer teaches many Sections.

---

## Section → Attendance

One Section contains many Attendance records.

---

## Section → Assignment

One Section contains many Assignments.

---

## Section → Quiz

One Section contains many Quizzes.

---

## Section → Announcement

One Section contains many Announcements.

---

## ChatRoom → Message

One Chat Room contains many Messages.

---

## Restaurant → MenuItem

One Restaurant contains many Menu Items.

---

## Restaurant → Order

One Restaurant receives many Orders.

---

## Student → Enrollment

One Student has many Enrollments.

---

## Student → Transcript

One Student has many Transcript records.

---

## Student → Notification

One Student receives many Notifications.

---

## Student → CalendarEvent

One Student has many Calendar Events.

---

# Many-to-Many Relationships

## Student ↔ Section

Implemented using:

Enrollment

```
Student
    │
Enrollment
    │
Section
```

---

## Role ↔ Permission

Implemented using:

RolePermission

```
Role
    │
RolePermission
    │
Permission
```

---

## ChatRoom ↔ User

Implemented using:

ChatMember

---

## Club ↔ Student

Implemented using:

ClubMember

---

## Event ↔ Student

Implemented using:

EventRegistration

---

# Core Database Relationships

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

Attendance

```
Section
 │
Attendance
 │
AttendanceExcuse
```

---

LMS

```
Section
 │
Assignment
 │
Submission
 │
Grade
```

---

Chat

```
ChatRoom
 │
Message
 │
Attachment
```

---

Food Court

```
Restaurant
 │
Menu
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
AI Report
 │
Violations
```

---

# Cascade Rules

Use:

- ON UPDATE CASCADE
- ON DELETE RESTRICT

Soft delete should be used for business data whenever possible.

---

# Referential Integrity Rules

- Every foreign key must reference an existing primary key.
- Orphan records are not allowed.
- Required relationships cannot contain NULL values.
- Optional relationships may allow NULL values where appropriate.

---

# Index Strategy

Indexes should be created on:

- Primary Keys
- Foreign Keys
- Frequently searched columns
- Composite relationship keys

Examples

- student_id
- course_id
- section_id
- semester_id
- role_id

---

# Relationship Summary

| Relationship Type | Examples |
|-------------------|----------|
| One-to-One | User → Student, User → Lecturer |
| One-to-Many | Faculty → Department, Course → Section |
| Many-to-Many | Student ↔ Section, Role ↔ Permission, Club ↔ Student |

---

# Design Principles

- Maintain Third Normal Form (3NF).
- Avoid duplicate relationships.
- Enforce referential integrity.
- Use foreign key constraints.
- Optimize joins using indexes.

---

# Notes

This document defines the complete relationship structure of the NextGen Smart University Platform. All ER diagrams, SQL migrations, ORM models, and APIs must follow these relationships to ensure consistency, scalability, and data integrity.