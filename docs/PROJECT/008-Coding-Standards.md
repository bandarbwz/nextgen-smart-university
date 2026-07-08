# Coding Standards

## Purpose

This document defines the official coding standards for the NextGen Smart University Platform (NSUP).

All developers and AI assistants must follow these standards to ensure consistency, readability, maintainability, security, and high code quality.

---

# General Principles

Always write code that is:

- Clean
- Readable
- Simple
- Reusable
- Maintainable
- Secure

Avoid duplicate code whenever possible.

---

# Software Engineering Principles

The project follows these principles:

- SOLID
- DRY (Don't Repeat Yourself)
- KISS (Keep It Simple)
- Separation of Concerns
- Single Responsibility Principle

---

# Naming Conventions

## Variables

Use camelCase.

Examples

```php
studentName
courseCode
totalCredits
attendancePercentage
```

---

## Functions

Use camelCase.

Function names must clearly describe the performed action.

Examples

```php
loginUser()

registerCourse()

calculateGPA()

generateTranscript()

submitAssignment()
```

---

## Classes

Use PascalCase.

Examples

```php
StudentController

CourseService

AttendanceModel

FinanceController

ChatService
```

---

## Files

### React Components

Use PascalCase.

Examples

```text
Dashboard.jsx

StudentCard.jsx

AttendanceTable.jsx

FinancePage.jsx
```

---

### PHP Files

Use PascalCase.

Examples

```text
StudentController.php

CourseService.php

AttendanceModel.php

FinanceController.php
```

---

### Configuration Files

Use lowercase.

Examples

```text
config.php

database.php

routes.php
```

---

# Folder Organization

Keep project files organized by module.

Example

```text
controllers/
models/
services/
middleware/
routes/
config/
helpers/
validation/
uploads/
```

Do not duplicate files or business logic.

---

# React Standards

- One component per file.
- Use Functional Components only.
- Use React Hooks.
- Keep components small.
- Create reusable UI components.
- Avoid duplicated layouts.
- Organize state properly.
- Validate forms before submission.

React communicates only with REST APIs.

React must never communicate directly with the database.

---

# PHP Standards

The backend follows MVC Architecture.

Controllers

- Receive requests.
- Validate requests.
- Call services.
- Return JSON responses.

Controllers should remain lightweight.

---

Models

Responsible for:

- Database Operations
- Data Relationships
- Data Persistence

---

Services

Responsible for:

- Business Logic
- Calculations
- Reusable Operations
- Complex Processing

Business logic must never be placed inside Controllers.

---

Routes

Each endpoint should include:

- Authentication
- Authorization
- Validation

---

# Database Standards

Always use:

- Primary Keys
- Foreign Keys
- Indexes

Table names should be meaningful.

Maintain referential integrity.

Avoid duplicate data.

---

# SQL Standards

- Use PDO Prepared Statements.
- Never concatenate SQL queries.
- Validate all user input.
- Optimize database queries.
- Return only required data.

---

# Artificial Intelligence Standards

The AI module must remain independent from the PHP backend.

Responsibilities include:

- Identity Verification
- Face Detection
- Eye Tracking
- Head Pose Detection
- Browser Tab Detection
- Fullscreen Detection
- AI Report Generation

Communication between PHP and AI must occur through secure REST APIs.

---

# Error Handling

Always handle:

- Invalid Input
- Authentication Errors
- Authorization Errors
- Database Errors
- API Errors
- File Upload Errors
- AI Service Errors

Return meaningful error messages.

Never expose internal server errors.

---

# Logging

Log important events:

- User Login
- User Logout
- Password Change
- Course Registration
- Attendance
- Grade Submission
- Finance Transactions
- AI Examination Violations

Sensitive information must never be logged.

---

# Performance

Improve performance by:

- Optimizing SQL queries
- Using pagination
- Returning only required API data
- Reusing React components
- Minimizing unnecessary API requests

---

# Documentation

Document public APIs before implementation.

Document complex functions when necessary.

Keep documentation synchronized with implementation.

---

# Final Rule

Every source file committed to the project must be production-ready.

Do not commit incomplete implementations, temporary code, or placeholder logic.