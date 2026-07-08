# AI Development Instructions

## Purpose

This document defines how the AI assistant must behave while developing this project.

The AI must follow these instructions before generating any code.

---

# General Rules

- Read the project documentation before writing code.
- Never skip any phase.
- Never assume missing requirements.
- Never rename existing files, folders, tables, or APIs.
- Never remove existing features unless requested.
- Always generate production-ready code.
- Never generate placeholder code.
- Complete one task before moving to the next.

---

# Development Order

Always follow this order.

1. Read README.md
2. Read Project Rules
3. Read the current feature documentation
4. Analyze dependencies
5. Generate the required code
6. Review generated code
7. Wait for approval before continuing

Never skip any step.

---

# Documentation Rules

Before implementing any feature:

- Read its documentation.
- Understand the business requirements.
- Understand database dependencies.
- Understand API dependencies.

If documentation is missing,

STOP

and ask for clarification.

---

# Code Generation Rules

Always generate

- Frontend
- Backend
- Database changes
- API endpoints
- Validation
- Error handling

if required by the current task.

Never generate incomplete features.

---

# Architecture Rules

Always keep modules independent.

Frontend must communicate only through REST APIs.

Business logic belongs to the PHP backend.

React is responsible only for the user interface and API communication.

Never place SQL queries inside React components.

Never duplicate business logic.

---

# Database Rules

Do not modify database tables unless required.

Use foreign keys.

Use indexes.

Maintain data integrity.

Never duplicate data.

---

# Security Rules

Always validate user input.

Hash passwords.

Use JWT authentication.

Protect against

- SQL Injection
- XSS
- CSRF

Never expose sensitive information.

---

# UI Rules

Create responsive pages.

Use Bootstrap 5.

Keep UI consistent.

Use reusable components.

Provide loading states and error messages.

---

# AI Review

After completing every task:

Review the code.

Check for

- Bugs
- Missing validation
- Security issues
- Performance issues

Fix problems before continuing.

---

# Task Completion

When a task is completed:

Do not continue automatically.

Wait for the next task.