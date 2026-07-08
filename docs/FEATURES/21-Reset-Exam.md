# Reset Examination Module

## Purpose

The Reset Examination Module manages requests to reset or reopen online examinations under approved academic circumstances.

It provides a secure approval workflow while maintaining examination integrity, complete audit logs, and controlled access.

---

# Objectives

- Manage exam reset requests.
- Support controlled exam reopening.
- Prevent unauthorized resets.
- Record approval history.
- Maintain examination integrity.
- Provide audit tracking.

---

# Scope

The Reset Examination Module includes:

- Reset Requests
- Reset Approval
- Reset History
- Reset Notifications
- Audit Logs

---

# Actors

- Student
- Lecturer
- Coordinator
- Administrator

---

# Database Tables

## ExamResetRequest

Purpose

Stores examination reset requests.

### Columns

- id
- exam_id
- student_id
- requested_by
- request_reason
- request_date
- approval_status
- approved_by
- approved_at
- remarks

### Approval Status

- Pending
- Approved
- Rejected
- Completed

---

## ExamResetLog

Purpose

Stores reset history.

### Columns

- id
- reset_request_id
- action
- performed_by
- created_at
- remarks

---

# Relationships

Student

↓

Exam Reset Request

↓

Administrator

↓

Reset Log

---

# Reset Workflow

Student submits reset request.

↓

Lecturer reviews request.

↓

Coordinator or Administrator approves request.

↓

Exam session is reset.

↓

Student receives notification.

↓

All actions are logged.

---

# Business Rules

- Reset requests require a valid reason.
- Only authorized staff may approve resets.
- Approved requests create one new examination session.
- Completed reset requests cannot be modified.
- Every reset action is permanently logged.

---

# Validation Rules

Reset Request

- Student required.
- Exam required.
- Reason required.

Approval

- Approver required.
- Status required.

---

# Permissions

## Student

- Submit Reset Request
- View Request Status

## Lecturer

- Review Requests
- Recommend Approval

## Coordinator

- Review Requests
- Approve Requests

## Administrator

- Full Reset Management
- Override Decisions
- View Reset Logs

---

# Notifications

Students receive:

- Request Submitted
- Request Approved
- Request Rejected
- Exam Reset Completed

Lecturers receive:

- New Reset Request

Administrators receive:

- Pending Reset Approval

---

# Security

- JWT Authentication
- Role-Based Access Control
- Approval Verification
- Audit Logging
- Secure Examination Records

---

# Indexes

ExamResetRequest

- student_id
- exam_id
- approval_status

ExamResetLog

- reset_request_id

---

# Reports

Reset Examination Reports

- Pending Requests
- Approved Requests
- Rejected Requests
- Reset History
- Student Reset Statistics

---

# Performance

- Cache request status.
- Optimize approval queries.
- Index reset history.
- Archive completed requests.

---

# API Mapping

GET /api/exam-reset

POST /api/exam-reset

PUT /api/exam-reset/{id}/approve

PUT /api/exam-reset/{id}/reject

GET /api/exam-reset/history

---

# UI Pages

- Reset Request
- My Requests
- Pending Approvals
- Reset History

---

# Dependencies

This module depends on:

- Authentication Module
- AI Examination Module
- Notification Module

The following modules depend on this module:

- Student Portal
- Lecturer Portal
- Administrator Portal

---

# Future Expansion

- AI Reset Recommendation
- Automatic Technical Issue Detection
- Multi-Level Approval Workflow
- Digital Approval Signature

---

# Notes

The Reset Examination Module provides a secure and auditable process for reopening examinations while protecting academic integrity and ensuring that every reset is properly reviewed, approved, and recorded.