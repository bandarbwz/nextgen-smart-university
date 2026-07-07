# Database Specification

## Purpose

This document defines the overall database design of the NextGen Smart University Platform.

The database is designed to support more than 50,000 users while maintaining performance, security, and scalability.

---

# Database Engine

MySQL 8

Storage Engine

InnoDB

Character Set

UTF8MB4

---

# Database Design Principles

- Normalize data to Third Normal Form (3NF)
- Use foreign keys
- Prevent duplicate data
- Keep tables modular
- Use indexes for searchable columns
- Support future expansion
- Store file paths instead of file content

---

# Main Modules

The database is divided into independent modules.

## Authentication

- Users
- Roles
- Permissions
- User Sessions

---

## Academic

- Students
- Lecturers
- Coordinators
- Faculties
- Departments
- Courses
- Sections
- Registration

---

## Attendance

- Attendance
- QR Sessions

---

## Learning Management System

- Course Materials
- Assignments
- Assignment Submissions
- Grades
- Announcements

---

## Communication

- Course Chats
- Messages
- Attachments
- Notifications

---

## Student Activities

- Clubs
- Club Members
- Events
- Event Registration
- Event Attendance
- Reward Points
- Certificates

---

## Food Court

- Restaurants
- Restaurant Menus
- Food Categories
- Orders
- Order Items
- Payments

---

## Finance

- Tuition Fees
- Payments
- Transactions

---

## AI Services

- Exam Sessions
- AI Violations
- AI Reports

---

# General Rules

Every table should include:

- id
- created_at
- updated_at

Use soft delete only when necessary.

All relationships must use foreign keys.

Indexes should be added to searchable columns.

---

# Naming Convention

Use singular table names.

Examples:

Student

Course

Department

Restaurant

Order

Payment

---

# Future Expansion

The database must support future modules without changing the existing structure.

Possible future modules:

- Library
- Hostel
- Transportation
- Mobile Application