# Business Rules

## Purpose

This document defines the business rules of the NextGen Smart University Platform.

These rules describe how the system should behave in real-world university operations.

---

# Authentication

- Every user must log in using a university account.
- Every account belongs to exactly one role.
- Users can only access modules assigned to their role.

---

# Student Registration

- Students can only register during the registration period.
- Students can only register for available course sections.
- Registration requests must be approved by the coordinator if approval is required.
- Students cannot register the same course twice.
- Students cannot exceed the maximum allowed credit hours.
- Students must satisfy course prerequisites.

---

# Course Chat

- Each course has one chat room.
- Students are automatically added after successful registration.
- Students are automatically removed after dropping the course.
- Lecturers manage announcements inside the course chat.

---

# QR Attendance

- Attendance is recorded by scanning a QR code.
- QR codes expire after a short period.
- Students must be inside the university campus to scan the QR code.
- Duplicate attendance is not allowed.
- Attendance records cannot be edited by students.

---

# Learning Management System

- Lecturers upload learning materials.
- Students can only access courses they are enrolled in.
- Assignments have deadlines.
- Late submissions are handled according to lecturer settings.

---

# Smart Exam Monitoring

- Students must enable their camera before starting an online exam.
- The exam cannot start without camera permission.
- AI monitors face position and eye movement.
- Multiple faces trigger a warning.
- Leaving fullscreen triggers a warning.
- Switching browser tabs triggers a warning.
- Repeated violations are reported to the lecturer.
- The lecturer decides whether to cancel the exam.

---

# Student Activities

- Students can register for events.
- QR codes are used for event attendance.
- Event attendance earns activity points.
- If an approved event conflicts with a scheduled class, the system generates an excuse letter automatically.

---

# Food Court

- Restaurants manage their own menus.
- Students place orders through the platform.
- Orders cannot be modified after preparation starts.
- Restaurant owners update order status.
- Students receive notifications when orders are ready.

---

# Notifications

The system automatically sends notifications for:

- Registration approval
- Assignment deadlines
- Exam schedules
- Attendance confirmation
- Event reminders
- Food order updates

---

# Reports

Reports are available according to user roles.

Students:
- Academic progress
- Attendance
- Payments

Lecturers:
- Attendance reports
- Grades
- Course reports

Administrators:
- System reports
- Financial reports
- User reports
- Activity reports

---

# Final Rule

Every feature implemented in the system must follow these business rules.