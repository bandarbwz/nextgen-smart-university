# Authentication Module

## Overview

The Authentication Module is responsible for user identity, access control, session management, and system security.

Every person using the platform must authenticate before accessing any system resources.

This module provides secure authentication and authorization using JWT and Role-Based Access Control (RBAC).

---

# Objectives

- Secure user authentication.
- Role-based authorization.
- Session management.
- Account security.
- Password management.
- Login history.
- Device tracking.

---

# Modules

This module contains:

- User Management
- Roles
- Permissions
- Role Permissions
- Sessions
- Authentication Logs

---

# Database Tables

## User

Stores all user accounts.

Columns

- id
- role_id
- full_name
- university_id
- email
- phone
- password
- profile_photo
- status
- email_verified
- last_login
- created_at
- updated_at

---

## Role

Stores all system roles.

Default Roles

- Student
- Lecturer
- Coordinator
- Administrator
- STAD Staff
- Restaurant Owner

Columns

- id
- name
- description
- created_at
- updated_at

---

## Permission

Stores every available permission.

Examples

Student

- View Dashboard
- Register Course
- View Grades

Lecturer

- Upload Assignment
- Grade Students
- Manage Attendance

Administrator

- Manage Users
- Manage Roles
- Manage System

Restaurant

- Manage Menu
- Accept Orders

STAD

- Manage Clubs
- Manage Events

Columns

- id
- module
- name
- description

---

## RolePermission

Links Roles with Permissions.

Columns

- id
- role_id
- permission_id

---

## UserSession

Stores active login sessions.

Columns

- id
- user_id
- jwt_token
- device_name
- browser
- operating_system
- ip_address
- login_time
- logout_time
- expires_at
- status

---

## AuthenticationLog

Stores security activities.

Columns

- id
- user_id
- action
- ip_address
- device
- created_at

Examples

- Login
- Logout
- Failed Login
- Password Changed
- Email Changed
- Profile Updated

---

# Relationships

Role

↓

User

↓

UserSession

↓

AuthenticationLog

Role

↓

RolePermission

↓

Permission

---

# Authentication Flow

User enters email and password.

↓

System validates credentials.

↓

Password is verified.

↓

JWT Token is generated.

↓

User session is created.

↓

Permissions are loaded.

↓

Dashboard is displayed.

---

# Business Rules

- Every user has exactly one role.
- Email must be unique.
- University ID must be unique.
- Passwords are hashed.
- Sessions expire automatically.
- Users cannot access unauthorized modules.

---

# Validation Rules

Email

- Required
- Valid format
- Unique

Password

- Minimum 8 characters
- Uppercase
- Lowercase
- Number
- Special Character

Phone

- Valid format

University ID

- Unique

---

# Security Rules

- JWT Authentication
- Password Hashing
- HTTPS
- Rate Limiting
- Input Validation
- SQL Injection Protection
- XSS Protection
- CSRF Protection

---

# Indexes

User

- email
- university_id
- role_id

UserSession

- token
- user_id

AuthenticationLog

- user_id

---

# Performance

- Cache permissions.
- Index login columns.
- Keep JWT lightweight.
- Expire inactive sessions.

---

# API Mapping

Authentication APIs

POST /login

POST /logout

POST /forgot-password

POST /reset-password

GET /profile

PUT /profile

PUT /change-password

---

# UI Pages

Login

Forgot Password

Reset Password

Profile

Change Password

---

# Future Expansion

- Two-Factor Authentication
- Google Login
- Microsoft Login
- Face Login
- Fingerprint Login

---

# Notes

This module is the security foundation of the entire platform.