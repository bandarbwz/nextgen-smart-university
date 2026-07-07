# Coding Standards

## Purpose

This document defines the coding standards for the NextGen Smart University Platform.

Every developer and AI assistant must follow these standards.

---

# General Principles

- Write clean code.
- Keep code simple.
- Avoid duplicate code.
- Use meaningful names.
- Write reusable code.
- Keep files organized.

---

# Clean Code

Always follow:

- SOLID Principles
- DRY (Don't Repeat Yourself)
- KISS (Keep It Simple)
- Separation of Concerns

---

# Naming Conventions

## Variables

Use camelCase.

Example:

studentName

courseCode

totalCredits

---

## Functions

Use camelCase.

Function names must describe the action.

Examples:

getStudent()

createCourse()

calculateGPA()

sendNotification()

---

## Classes

Use PascalCase.

Examples:

StudentController

CourseService

AttendanceModel

RestaurantRepository

---

## Files

Use PascalCase for React components.

Examples:

StudentCard.jsx

DashboardLayout.jsx

AttendanceTable.jsx

Use lowercase for configuration files.

---

# React Standards

- One component per file.
- Keep components small.
- Use reusable components.
- Avoid duplicate UI.
- Keep state organized.
- Use functional components only.
- Use hooks.

---

# PHP Standards

- MVC Architecture
- Controllers should stay thin.
- Business logic belongs in Services.
- Database access belongs in Models.
- Validate all requests.
- Return JSON responses only.

---

# Python Standards

- Modular scripts.
- One responsibility per module.
- Keep AI logic separated from backend.
- Document AI models.
- Handle exceptions.

---

# SQL Standards

- Use clear table names.
- Use singular table names.
- Use foreign keys.
- Index searchable columns.
- Avoid duplicate data.

---

# Comments

Write comments only when necessary.

Do not explain obvious code.

---

# Error Handling

Always handle:

- Invalid input
- Database errors
- API errors
- File upload errors
- Authentication errors

---

# Logging

Log important events.

Examples:

- Login
- Logout
- User creation
- Payment
- Attendance
- Exam violations

---

# Performance

- Optimize database queries.
- Use pagination.
- Avoid unnecessary API calls.
- Reuse components.
- Cache reusable data when appropriate.

---

# Final Rule

Always write production-ready code.

Never generate demo code or placeholder implementations.