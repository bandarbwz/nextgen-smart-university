# Database Tables

## Purpose

This document provides a complete inventory of all database tables used in the NextGen Smart University Platform.

Tables are organized by module to improve maintainability, development, and future scalability.

---

# Database Summary

| Module | Tables |
|---------|-------:|
| Authentication | 6 |
| Academic | 11 |
| Attendance | 3 |
| Learning Management System (LMS) | 8 |
| Chat | 6 |
| Student Activities | 6 |
| Food Court | 8 |
| Finance | 7 |
| AI Examination | 9 |
| Notification Center | 2 |
| Calendar | 2 |
| Assessment System | 3 |
| Role Management | 4 |
| Grade Approval | 2 |
| Reset Examination | 2 |
| Reports | 3 |
| Settings | 2 |
| System | 3 |
| Download Center | 2 |

---

# Authentication Module

| Table |
|-------|
| User |
| Role |
| Permission |
| RolePermission |
| UserSession |
| AuthenticationLog |

---

# Academic Module

| Table |
|-------|
| Student |
| Lecturer |
| Coordinator |
| Faculty |
| Department |
| Program |
| Semester |
| Course |
| CoursePrerequisite |
| Section |
| Enrollment |

---

# Attendance Module

| Table |
|-------|
| Attendance |
| QRSession |
| AttendanceExcuse |

---

# Learning Management System (LMS)

| Table |
|-------|
| CourseMaterial |
| Assignment |
| AssignmentSubmission |
| Quiz |
| QuizQuestion |
| QuizSubmission |
| Grade |
| Announcement |

---

# Chat Module

| Table |
|-------|
| ChatRoom |
| ChatMember |
| Message |
| MessageAttachment |
| MessageReaction |
| MessageRead |

---

# Student Activities Module

| Table |
|-------|
| Club |
| ClubMember |
| Event |
| EventRegistration |
| EventAttendance |
| ActivityPoint |

---

# Food Court Module

| Table |
|-------|
| Restaurant |
| FoodCategory |
| MenuItem |
| Order |
| OrderItem |
| Payment |
| DeliveryStatus |
| RestaurantReview |

---

# Finance Module

| Table |
|-------|
| Invoice |
| PaymentTransaction |
| Scholarship |
| StudentAccount |
| Fine |
| Refund |
| FinancialLog |

---

# AI Examination Module

| Table |
|-------|
| Examination |
| ExaminationSession |
| AIViolation |
| FaceDetection |
| EyeTracking |
| HeadPose |
| BrowserActivity |
| ExamRecording |
| AIReport |

---

# Notification Center

| Table |
|-------|
| Notification |
| NotificationPreference |

---

# Calendar Module

| Table |
|-------|
| CalendarEvent |
| Reminder |

---

# Assessment System

| Table |
|-------|
| Assessment |
| AssessmentResult |
| AssessmentRubric |

---

# Role Management

| Table |
|-------|
| Role |
| Permission |
| RolePermission |
| UserRole |

---

# Grade Approval

| Table |
|-------|
| GradeApproval |
| GradeApprovalLog |

---

# Reset Examination

| Table |
|-------|
| ExamResetRequest |
| ExamResetLog |

---

# Reports

| Table |
|-------|
| GeneratedReport |
| ReportTemplate |
| ReportHistory |

---

# Settings

| Table |
|-------|
| UserSetting |
| SystemSetting |

---

# System

| Table |
|-------|
| SystemConfiguration |
| SystemLog |
| BackupHistory |

---

# Download Center

| Table |
|-------|
| DownloadFile |
| DownloadHistory |

---

# Estimated Database Statistics

- Total Modules: **19**
- Total Tables: **88+**
- Expected Users: **50,000+**
- Supports Millions of Records
- Designed for Horizontal Scaling
- Fully Normalized (3NF)

---

# Naming Standards

Tables use:

- Singular names
- PascalCase

Columns use:

- lowercase_with_underscores

Primary Keys

- id

Foreign Keys

- *_id

---

# Notes

This document is the master inventory of all database tables in the NextGen Smart University Platform. All SQL scripts, ER diagrams, migrations, models, and APIs must reference these table definitions to maintain consistency across the project.