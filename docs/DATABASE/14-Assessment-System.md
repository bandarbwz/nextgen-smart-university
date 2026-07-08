# Assessment System Database

## Purpose

This document defines the database structure for the flexible assessment system used by the Learning Management System.

Unlike traditional grading systems, this design allows lecturers to create unlimited assessment types for each course.

---

# Overview

The Assessment System supports:

- Assignments
- Quizzes
- Midterm Exams
- Final Exams
- Projects
- Laboratory Assessments
- Presentations
- Participation
- Practical Exams
- Custom Assessment Types

Each course can have its own grading structure.

---

# Tables

## assessment_types

Stores assessment categories.

Fields

- id
- name
- description
- created_at
- updated_at

Example Records

- Assignment
- Quiz
- Midterm
- Final
- Project
- Lab
- Presentation
- Participation
- Practical
- Other

---

## assessments

Stores every assessment created by lecturers.

Fields

- id
- section_id
- assessment_type_id
- title
- description
- total_marks
- weight_percentage
- due_date
- created_by
- status
- created_at
- updated_at

---

## assessment_submissions

Stores student submissions.

Fields

- id
- assessment_id
- student_id
- submitted_file
- submitted_at
- status
- remarks

---

## assessment_grades

Stores awarded grades.

Fields

- id
- assessment_id
- student_id
- obtained_marks
- percentage
- graded_by
- graded_at
- published

---

# Relationships

assessment_types

1 → Many

assessments

---

sections

1 → Many

assessments

---

assessments

1 → Many

assessment_submissions

---

students

1 → Many

assessment_submissions

---

assessments

1 → Many

assessment_grades

---

students

1 → Many

assessment_grades

---

# Business Rules

- Each course may contain unlimited assessments.
- Assessment weights should total 100%.
- Lecturers define assessment distribution.
- Final Exam is created by the Administrator.
- Students cannot modify submissions after the deadline unless allowed.

---

# Notes

This design supports future assessment types without modifying the database schema.