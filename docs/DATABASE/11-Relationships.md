# Database Relationships

## Purpose

This document defines the relationships between all database tables in the NextGen Smart University Platform.

The goal is to maintain data integrity, prevent duplication, and provide a scalable database structure.

---

# Authentication Module

Role

1 → Many

User

---

User

1 → Many

UserSession

---

Role

Many → Many

Permission

using

RolePermission

---

# Academic Module

Faculty

1 → Many

Department

---

Department

1 → Many

Program

---

Program

1 → Many

Course

---

Course

1 → Many

Section

---

Semester

1 → Many

Section

---

Lecturer

1 → Many

Section

---

Section

1 → Many

ClassSchedule

---

Student

Many → Many

Section

using

Enrollment

---

Course

Many → Many

Course

using

CoursePrerequisite

---

Student

1 → Many

Transcript

---

# Attendance Module

Student

1 → Many

Attendance

---

Section

1 → Many

Attendance

---

Section

1 → Many

QRSession

---

Attendance

1 → Many

AttendanceExcuse

---

# LMS Module

Course

1 → Many

CourseMaterial

---

Section

1 → Many

Assignment

---

Assignment

1 → Many

AssignmentSubmission

---

Quiz

1 → Many

QuizQuestion

---

Quiz

1 → Many

QuizSubmission

---

Student

1 → Many

Grade

---

Section

1 → Many

Announcement

---

# Chat Module

ChatRoom

1 → Many

ChatMember

---

ChatRoom

1 → Many

Message

---

Message

1 → Many

MessageAttachment

---

Message

1 → Many

MessageReaction

---

Message

1 → Many

MessageRead

---

# Student Activities

Club

1 → Many

ClubMember

---

Club

1 → Many

Event

---

Event

1 → Many

EventRegistration

---

Event

1 → Many

EventAttendance

---

Student

1 → Many

RewardPoint

---

Student

1 → Many

Certificate

---

Student

1 → Many

ExcuseLetter

---

# Food Court

Restaurant

1 → Many

FoodCategory

---

FoodCategory

1 → Many

MenuItem

---

Restaurant

1 → Many

Order

---

Order

1 → Many

OrderItem

---

Order

1 → One

Payment

---

Restaurant

1 → Many

RestaurantReview

---

Student

1 → Many

FavoriteRestaurant

---

# AI Exam

Exam

1 → Many

ExamSession

---

ExamSession

1 → Many

AIViolation

---

ExamSession

1 → One

AIReport

---

ExamSession

1 → Many

ExamRecording

---

# Finance

Student

1 → Many

TuitionFee

---

Student

1 → Many

PaymentTransaction

---

PaymentTransaction

1 → One

Invoice

---

Student

1 → Many

Scholarship

---

PaymentTransaction

1 → Many

Refund

---

# System

User

1 → Many

Notification

---

User

1 → Many

AuditLog

---

User

1 → Many

UploadedFile

---

Administrator

1 → Many

SystemAnnouncement

---

Administrator

1 → Many

SystemSetting

---

# Design Rules

- Every table must have a Primary Key.
- Foreign Keys must enforce referential integrity.
- Cascade delete should only be used when appropriate.
- Indexed columns should be used for frequently searched data.
- Many-to-Many relationships should always use junction tables.

---

# Database Integrity

The database is designed to maintain:

- Data Consistency
- Referential Integrity
- Scalability
- Performance
- Security

---

# Notes

All future modules must follow the same relationship standards defined in this document.