# Testing Strategy

## Purpose

This document defines the official testing strategy for the NextGen Smart University Platform (NSUP).

The objective is to ensure that every module functions correctly, securely, efficiently, and reliably before deployment.

Testing is mandatory throughout the software development lifecycle.

---

# Testing Objectives

The testing process aims to:

- Verify functional correctness.
- Detect defects early.
- Improve software quality.
- Protect user data.
- Validate business rules.
- Ensure system stability.
- Improve user experience.

---

# Testing Levels

The project includes the following testing levels:

- Unit Testing
- Integration Testing
- System Testing
- User Acceptance Testing (UAT)

Each level serves a different purpose and must be completed before deployment.

---

# Unit Testing

Purpose

Verify individual functions, classes, and modules.

Examples

- Login validation
- GPA calculation
- Credit hour validation
- Payment calculation
- Attendance validation

Expected Result

Each unit should work independently without errors.

---

# Integration Testing

Purpose

Verify communication between modules.

Examples

- React ↔ PHP API
- PHP ↔ MySQL
- PHP ↔ Python AI
- Authentication ↔ Dashboard
- Registration ↔ LMS

Expected Result

Modules communicate correctly and exchange valid data.

---

# System Testing

Purpose

Verify the complete application as one integrated system.

Examples

- Student registration workflow
- Assignment submission
- QR attendance
- Food ordering
- Event registration
- Online examination

Expected Result

Complete workflows operate successfully.

---

# User Acceptance Testing (UAT)

Purpose

Ensure the system satisfies user requirements.

Users include:

- Students
- Lecturers
- Coordinators
- Administrators
- STAD Staff
- Restaurant Owners

Expected Result

Users can successfully complete their tasks without issues.

---

# Functional Testing

Verify every feature performs according to the business requirements.

Examples

- User Login
- Course Registration
- QR Attendance
- Assignment Submission
- Online Examination
- Finance
- Food Court
- Event Registration

---

# Validation Testing

Verify all user input.

Examples

- Required Fields
- Email Format
- Password Policy
- File Upload Rules
- Credit Hour Limits
- Course Prerequisites
- Schedule Clash Detection

---

# Security Testing

Verify system security.

Tests include:

- SQL Injection
- Cross-Site Scripting (XSS)
- Cross-Site Request Forgery (CSRF)
- JWT Authentication
- Role-Based Access Control
- Unauthorized Access

---

# Performance Testing

Verify system performance.

Measure:

- Login Response Time
- Dashboard Loading
- Database Query Speed
- API Response Time
- File Upload Performance
- AI Response Time

---

# User Interface Testing

Verify the user interface.

Check:

- Responsive Design
- Navigation
- Forms
- Tables
- Buttons
- Dark Mode
- Light Mode
- Accessibility

---

# AI Testing

Verify AI examination monitoring.

Tests include:

- Face Detection
- Identity Verification
- Eye Tracking
- Head Pose Detection
- Browser Tab Detection
- Fullscreen Detection
- AI Report Generation

The AI must generate reports only and must not make academic decisions.

---

# Regression Testing

Whenever changes are made:

- Retest existing features.
- Verify previous functionality still works.
- Ensure bug fixes do not introduce new issues.

---

# Bug Severity

Critical

- System Crash
- Data Loss
- Security Breach

High

- Core Feature Failure
- Data Corruption

Medium

- Incorrect Validation
- Minor Functional Issues

Low

- UI Issues
- Typographical Errors
- Layout Problems

---

# Testing Environment

Testing should be performed using:

- React Development Build
- PHP 8.4
- MySQL 8
- Python AI Service
- Apache Web Server

Use a dedicated testing environment before deployment.

---

# Testing Checklist

Before completing any feature:

- Functional Testing Passed
- Validation Testing Passed
- Security Testing Passed
- Performance Testing Passed
- UI Testing Passed
- AI Testing Passed (if applicable)
- Documentation Updated

---

# Final Rule

No feature should be marked as complete until all required tests have been successfully completed and verified.