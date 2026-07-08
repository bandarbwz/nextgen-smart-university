# Assessment System Module

## Purpose

The Assessment System manages all academic assessments within the NextGen Smart University Platform.

It allows lecturers to create assessments, evaluate student performance, calculate grades, and publish academic results while ensuring fairness, accuracy, and academic integrity.

---

# Objectives

- Manage assessments.
- Create grading components.
- Calculate student marks.
- Publish grades.
- Support continuous assessment.
- Generate assessment reports.
- Improve academic evaluation.

---

# Scope

The Assessment System includes:

- Assessment Management
- Assignment Assessment
- Quiz Assessment
- Midterm Assessment
- Final Examination Assessment
- Participation Assessment
- Grade Calculation
- Assessment Reports

---

# Actors

- Student
- Lecturer
- Coordinator
- Administrator

---

# Database Tables

## Assessment

Purpose

Stores assessment information.

### Columns

- id
- section_id
- title
- assessment_type
- total_marks
- weight_percentage
- due_date
- status
- created_at
- updated_at

### Assessment Types

- Assignment
- Quiz
- Midterm
- Final
- Project
- Participation

---

## AssessmentResult

Purpose

Stores student assessment results.

### Columns

- id
- assessment_id
- student_id
- marks
- percentage
- grade
- feedback
- graded_by
- graded_at

---

## AssessmentRubric

Purpose

Stores grading criteria.

### Columns

- id
- assessment_id
- criterion
- maximum_marks
- description

---

# Relationships

Assessment

↓

Assessment Result

↓

Student

---

Assessment

↓

Assessment Rubric

---

# Business Rules

- Every assessment belongs to one course section.
- Weight percentages must follow university policy.
- Marks cannot exceed total marks.
- Published grades cannot be modified without approval.
- Assessment history is permanently recorded.

---

# Validation Rules

Assessment

- Title required.
- Total marks greater than zero.
- Weight percentage between 0 and 100.

Assessment Result

- Marks cannot exceed total marks.
- Student must be enrolled.

---

# Permissions

## Student

- View Assessment Results
- View Feedback

## Lecturer

- Create Assessments
- Grade Students
- Publish Results

## Coordinator

- Review Assessment Results

## Administrator

- Full Assessment Management

---

# Notifications

Students receive notifications for:

- New Assessment
- Assessment Deadline
- Grade Published
- Feedback Available

Lecturers receive notifications for:

- Submission Received
- Assessment Published

---

# Security

- JWT Authentication
- Role-Based Access Control
- Audit Logging
- Grade Protection
- Secure Assessment Records

---

# Indexes

Assessment

- section_id

AssessmentResult

- assessment_id
- student_id

AssessmentRubric

- assessment_id

---

# Reports

Assessment Reports

- Assessment Summary
- Grade Distribution
- Student Performance
- Lecturer Assessment Report
- Course Assessment Statistics

---

# Performance

- Optimize grade calculations.
- Cache assessment summaries.
- Index assessment results.
- Archive completed assessments.

---

# API Mapping

GET /api/assessments

POST /api/assessments

PUT /api/assessments/{id}

DELETE /api/assessments/{id}

GET /api/assessments/results

POST /api/assessments/results

GET /api/assessments/reports

---

# UI Pages

- Assessment Dashboard
- Assessment List
- Assessment Details
- Student Results
- Grade Entry
- Assessment Reports

---

# Dependencies

This module depends on:

- Authentication Module
- Academic Module
- LMS Module

The following modules depend on this module:

- Lecturer Portal
- Coordinator Portal
- Student Portal
- Reports Module

---

# Future Expansion

- AI Auto Grading
- AI Feedback Generation
- Rubric Templates
- Outcome-Based Assessment
- Learning Analytics

---

# Notes

The Assessment System integrates with the LMS Module for assignment and quiz management, the Academic Module for student enrollment, and the Reports Module for assessment analytics.