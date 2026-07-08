# Grade Approval Database

## Purpose

This document defines the grade approval workflow before grades become visible to students.

---

# Workflow

Lecturer

↓

Submit Grades

↓

Coordinator Review

↓

Approve / Reject

↓

Student Notification

↓

Grades Published

---

# Tables

## grade_submissions

Fields

- id
- section_id
- submitted_by
- submitted_at
- status
- remarks

Status

- Draft
- Submitted
- Approved
- Rejected

---

## grade_approval_history

Fields

- id
- submission_id
- coordinator_id
- action
- remarks
- action_date

---

# Relationships

grade_submissions

1 → Many

grade_approval_history

---

sections

1 → Many

grade_submissions

---

# Business Rules

- Students cannot view grades before approval.
- Coordinators may approve or reject submissions.
- Every approval action is logged.
- Grade updates require a new approval process.

---

# Notes

The approval history provides a complete audit trail for academic transparency.