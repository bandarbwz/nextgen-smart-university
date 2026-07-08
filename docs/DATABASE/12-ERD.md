# Entity Relationship Diagram (ERD)

## Purpose

This document describes the Entity Relationship Diagram (ERD) of the NextGen Smart University Platform.

The ERD represents how all database entities are connected and interact with each other.

A visual ERD diagram will be created after the database schema is finalized.

---

# Overview

The database consists of the following modules:

- Authentication
- Academic
- Attendance
- Learning Management System (LMS)
- Chat
- Student Activities (STAD)
- Food Court
- AI Exam Monitoring
- Finance
- System

Each module is connected through primary keys and foreign keys to maintain data integrity.

---

# High-Level ERD

```
Authentication
      │
      ▼
    User
      │
      ├──────────────┐
      ▼              ▼
 Student         Lecturer
      │              │
      └──────┬───────┘
             ▼
        Academic Module
             │
             ▼
          Course
             │
             ▼
          Section
             │
      ┌──────┼──────────────┐
      ▼      ▼              ▼
 Attendance LMS          Chat
      │      │              │
      └──────┼──────────────┘
             ▼
         Student Portal

Student
   │
   ├──────────────► Food Court
   │
   ├──────────────► Student Activities
   │
   ├──────────────► Finance
   │
   └──────────────► AI Exam
```

---

# Module Relationships

## Authentication

Role

↓

User

↓

Session

↓

Permissions

---

## Academic

Faculty

↓

Department

↓

Program

↓

Course

↓

Section

↓

Enrollment

↓

Student

---

## Attendance

Student

↓

Attendance

↓

Attendance Reports

---

## LMS

Course

↓

Materials

↓

Assignments

↓

Grades

↓

Announcements

---

## Chat

Course

↓

Chat Room

↓

Messages

↓

Attachments

---

## Student Activities

Club

↓

Event

↓

Registration

↓

Attendance

↓

Certificate

↓

Reward Points

---

## Food Court

Restaurant

↓

Menu

↓

Orders

↓

Payments

↓

Reviews

---

## AI Exam

Exam

↓

Session

↓

Violations

↓

Reports

---

## Finance

Student

↓

Tuition

↓

Transactions

↓

Invoices

↓

Refunds

---

## System

Notifications

Audit Logs

Files

Settings

Announcements

---

# Primary Keys

Every table contains:

- id

as the primary key.

---

# Foreign Keys

Examples

Student.user_id

↓

User.id

---

Course.department_id

↓

Department.id

---

Section.course_id

↓

Course.id

---

Enrollment.student_id

↓

Student.id

---

Enrollment.section_id

↓

Section.id

---

Order.student_id

↓

Student.id

---

Payment.order_id

↓

Order.id

---

Exam.session_id

↓

ExamSession.id

---

# Database Design Principles

- Third Normal Form (3NF)
- Foreign Key Constraints
- Indexed Search Fields
- No Duplicate Data
- Modular Design
- Scalable Structure

---

# Estimated Database Size

Estimated Tables

55+

Estimated Relationships

120+

Estimated Users

50,000+

Estimated Daily Transactions

100,000+

---

# ERD Tools

Recommended tools for maintaining the ERD:

- dbdiagram.io
- Draw.io
- MySQL Workbench
- Lucidchart

---

# Future Expansion

The ERD supports future modules including:

- Library Management
- Hostel Management
- Smart Parking
- Alumni Portal
- Parent Portal
- Mobile Application

---

# Notes

This document serves as the reference for the visual ERD diagram that will be maintained throughout the project lifecycle.