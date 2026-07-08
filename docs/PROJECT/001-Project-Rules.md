# Project Rules

## Purpose

This document defines the mandatory rules for developing the NextGen Smart University Platform.

Every developer and AI assistant must follow these rules throughout the project lifecycle.

---

# General Rules

- Follow the project documentation.
- Do not skip development phases.
- Complete one task before starting another.
- Keep the project organized.
- Write clean, readable, and maintainable code.
- Do not implement undocumented features.

---

# Coding Rules

- Use meaningful variable names.
- Use meaningful function names.
- Keep functions small and focused.
- Avoid duplicate code.
- Follow consistent formatting.
- Add comments only when necessary.
- Follow the established folder structure.

---

# Architecture Rules

- Keep frontend and backend separated.
- Use REST APIs for communication.
- Keep business logic inside the backend.
- Follow MVC architecture for the PHP backend.
- Reuse components whenever possible.
- Do not access the database directly from the frontend.

---

# Database Rules

- Use foreign keys where appropriate.
- Keep relationships normalized.
- Never duplicate data.
- Use clear table names.
- Use indexes where necessary.
- Never delete production data without authorization.

---

# Security Rules

- Validate all user input.
- Hash passwords before storing them.
- Protect APIs using JWT Authentication.
- Prevent SQL Injection.
- Prevent Cross-Site Scripting (XSS).
- Prevent Cross-Site Request Forgery (CSRF).
- Never expose sensitive information in API responses.

---

# Business Rules

- Registration must be opened by the Coordinator.
- Students cannot register outside the registration period.
- The system must validate schedule clashes automatically.
- Students with unpaid tuition cannot complete course registration.
- Final examinations are created by the Administrator.
- Grades must be approved by the Coordinator before students can view them.
- The AI Examination System only monitors exams and generates reports. Final academic decisions are made by authorized university staff.
- A single user may have multiple roles.

---

# UI Rules

- Build responsive pages.
- Use Bootstrap 5.
- Follow the project design system.
- Use Lucide React icons.
- Keep navigation consistent across all portals.
- Display loading indicators.
- Display user-friendly error messages.

---

# Git Rules

- Commit after completing a logical task.
- Use clear and meaningful commit messages.
- Push changes regularly.
- Never commit passwords, API keys, or sensitive information.

---

# Documentation Rules

- Update documentation before implementing major features.
- Keep documentation synchronized with the project.
- Documentation must match the latest approved project design.

---

# Quality Rules

- Test every feature before marking it complete.
- Fix bugs before adding new features.
- Keep the project maintainable.
- Follow coding standards across the project.

---

# Final Rule

Always prioritize quality, security, maintainability, and consistency over development speed.