# Business Rules

## Purpose

This document defines the official business rules of the NextGen Smart University Platform (NSUP).

These rules describe how the system behaves during real university operations and must be followed throughout development.

---

# Authentication

- Every user must log in using a university account.
- A user may have one or more roles.
- Access permissions are determined by the assigned role or roles.
- Users may access only the modules they are authorized to use.

---

# Course Registration

- Registration is available only during the official registration period.
- Registration is opened by the Coordinator.
- Students may register only for available course sections.
- The system automatically validates schedule clashes before registration.
- Students cannot register for the same course more than once.
- Students cannot exceed the maximum allowed credit hours.
- Students must satisfy all course prerequisites.
- Students with unpaid tuition fees cannot complete registration.
- Registration may require Coordinator approval before becoming active.

---

# Add and Drop

- Students may add or drop courses only during the official Add/Drop period.
- The system automatically updates enrollment records.
- Course chat membership is updated automatically.
- Attendance and LMS access are updated automatically.

---

# Assessment Management

- Lecturers may create multiple assessments.
- Supported assessment types include:
  - Assignment
  - Quiz
  - Midterm
  - Final
  - Project
  - Laboratory
  - Presentation
  - Participation
  - Practical
  - Other
- Assessment deadlines are configurable.
- Students may only submit before the deadline unless late submission is allowed.

---

# Grade Management

- Lecturers submit grades.
- Coordinators review submitted grades.
- Students cannot view grades until approval is completed.
- Approved grades become visible immediately after publication.

---

# Learning Management System (LMS)

- Lecturers upload learning materials.
- Students can access only courses they are enrolled in.
- Assignments have configurable deadlines.
- Late submissions follow lecturer settings.

---

# Course Chat

- Every course has one dedicated chat room.
- Students are automatically added after successful registration.
- Students are automatically removed after dropping the course.
- Lecturers manage announcements within the course chat.

---

# QR Attendance

- Attendance is recorded using QR Codes.
- QR Codes expire after a limited time.
- Students must be within the university campus.
- Duplicate attendance is not allowed.
- Students cannot modify attendance records.

---

# AI Online Examination

- Students must grant camera access before starting an examination.
- Identity verification is required.
- AI monitors:
  - Face Detection
  - Eye Tracking
  - Head Pose
  - Browser Tab Switching
  - Fullscreen Status
- Multiple faces generate a warning.
- Leaving fullscreen generates a warning.
- Browser tab switching generates a warning.
- AI generates examination reports only.
- Final academic decisions are made by lecturers and university staff.

---

# Student Activities

- Students may register for university events.
- QR Codes are used for event attendance.
- Approved participation awards activity points.
- If an approved event conflicts with a scheduled lecture, the system generates an excuse letter automatically.

---

# Food Court

- Restaurants manage their own menus.
- Students place food orders through the platform.
- Orders cannot be modified after preparation begins.
- Restaurant owners update order status.
- Students receive notifications when orders are ready.

---

# Finance

- Students can view tuition fees.
- Students can pay eligible fees online.
- Payment history is permanently stored.
- Receipts and invoices can be downloaded.

---

# Download Center

Students may download:

- Academic Transcript
- Attendance Report
- Grade Report
- Registration Slip
- Student ID
- Certificates
- Excuse Letters
- Tuition Invoice
- Payment Receipt

Downloads are available only to authorized users.

---

# Notification Center

The system automatically sends notifications for:

- Registration Approval
- Assessment Deadlines
- Examination Schedule
- Attendance Confirmation
- Event Reminders
- Food Order Status
- Grade Publication
- Finance Updates

---

# Reports

Reports are generated according to user roles.

Students

- Academic Progress
- Attendance
- Grades
- Finance

Lecturers

- Attendance Reports
- Assessment Reports
- Grade Reports

Coordinators

- Registration Reports
- Grade Approval Reports

Administrators

- User Reports
- Financial Reports
- Academic Reports
- System Reports

---

# Final Rule

Every feature implemented in the project must follow these business rules.

Any modification to these rules requires updating the project documentation before implementation.