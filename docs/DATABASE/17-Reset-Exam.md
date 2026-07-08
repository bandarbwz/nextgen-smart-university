# Reset Exam Database

## Purpose

This document defines the database structure for the Reset Examination System.

The Reset Examination allows eligible students to request a second examination during the official reset period after final results are released.

---

# Workflow

Final Results Released

↓

Student Failed

↓

Student Applies for Reset Exam

↓

Coordinator Reviews Request

↓

Approve / Reject

↓

Administrator Creates Reset Exam

↓

Student Takes Reset Exam

↓

Lecturer Grades Exam

↓

Coordinator Approves Grade

↓

Final Grade Published

---

# Tables

## reset_exam_requests

Fields

- id
- student_id
- section_id
- reason
- status
- requested_at
- reviewed_by
- reviewed_at
- remarks

Status

- Pending
- Approved
- Rejected
- Completed

---

## reset_exams

Fields

- id
- request_id
- exam_date
- start_time
- end_time
- location
- created_by
- created_at

---

## reset_exam_results

Fields

- id
- reset_exam_id
- student_id
- marks
- final_grade
- graded_by
- graded_at
- approved

---

# Relationships

students

1 → Many

reset_exam_requests

---

reset_exam_requests

1 → One

reset_exams

---

reset_exams

1 → Many

reset_exam_results

---

# Business Rules

- Requests are only allowed during the official reset period.
- Only failed students may apply.
- Coordinator approval is required.
- Final grades follow the normal approval workflow.

---

# Notes

All reset examination records are permanently stored for auditing purposes.