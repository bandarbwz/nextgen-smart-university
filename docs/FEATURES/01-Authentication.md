# Authentication Module

## Purpose

The Authentication Module is responsible for user identity, authentication, authorization, session management, and overall platform security.

Every user must authenticate before accessing any protected resources within the NextGen Smart University Platform.

This module uses JWT Authentication and Role-Based Access Control (RBAC) to ensure secure access to the system.

---

# Objectives

- Secure user authentication
- Role-based authorization
- Session management
- Password management
- Account protection
- Login history
- Device tracking

---

# Modules

This module contains:

- User Management
- Roles
- Permissions
- Role Permissions
- User Sessions
- Authentication Logs
- Password Reset
- Email Verification

---

# Database Tables

## User

Stores all user accounts.

### Columns

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
- failed_login_attempts
- locked_until
- last_password_change
- created_at
- updated_at

---

## Role

Stores all system roles.

### Default Roles

- Student
- Lecturer
- Coordinator
- Administrator
- Restaurant Owner
- STAD Staff

### Columns

- id
- name
- description
- created_at
- updated_at

---

## Permission

Stores all available system permissions.

### Example Permissions

Student

- View Dashboard
- Register Courses
- View Grades

Lecturer

- Manage Attendance
- Upload Materials
- Grade Students

Administrator

- Manage Users
- Manage Roles
- Manage System Settings

Restaurant Owner

- Manage Menu
- Accept Orders

STAD Staff

- Manage Clubs
- Manage Events

### Columns

- id
- module
- name
- description

---

## RolePermission

Links roles with permissions.

### Columns

- id
- role_id
- permission_id

---

## UserSession

Stores active user sessions.

### Columns

- id
- user_id
- jwt_token
- refresh_token
- device_name
- browser
- operating_system
- ip_address
- login_time
- last_activity
- logout_time
- expires_at
- status

---

## AuthenticationLog

Stores authentication and security events.

### Columns

- id
- user_id
- action
- status
- ip_address
- device
- created_at

### Logged Events

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

User enters email and password

↓

Validate Request

↓

Validate Credentials

↓

Verify Password

↓

Generate JWT Token

↓

Load User Role

↓

Load Permissions

↓

Create User Session

↓

Return Authentication Response

↓

Display Dashboard

---

# Business Rules

- Every user has exactly one role.
- Email must be unique.
- University ID must be unique.
- Passwords must be securely hashed.
- Sessions expire automatically.
- Unauthorized access is prohibited.
- Inactive accounts cannot log in.
- Locked accounts require administrator action or automatic unlock.

---

# Validation Rules

### Email

- Required
- Valid email format
- Unique

### Password

- Minimum 8 characters
- One uppercase letter
- One lowercase letter
- One number
- One special character

### Phone

- Valid phone number format

### University ID

- Required
- Unique

---

# Permissions

## Student

- Login
- View Profile
- Update Profile
- Change Password

## Lecturer

- Login
- View Profile
- Update Profile
- Change Password

## Coordinator

- Login
- View Profile
- Update Profile
- Change Password

## Restaurant Owner

- Login
- View Profile
- Change Password

## STAD Staff

- Login
- View Profile
- Change Password

## Administrator

- Full Access

---

# Notifications

The system sends notifications for:

- Successful Login
- Password Changed
- Password Reset
- Email Verification
- Account Locked
- Suspicious Login Activity

---

# Security Rules

- JWT Authentication
- Role-Based Access Control (RBAC)
- Password Hashing using bcrypt
- HTTPS Only
- Rate Limiting
- Input Validation
- SQL Injection Protection
- Cross-Site Scripting (XSS) Protection
- Cross-Site Request Forgery (CSRF) Protection

---

# Indexes

## User

- email
- university_id
- role_id

## UserSession

- jwt_token
- user_id

## AuthenticationLog

- user_id

---

# Reports

Authentication Reports

- Login History
- Failed Login Attempts
- Active Sessions
- Locked Accounts
- Password Reset History

---

# Performance

- Cache user permissions
- Keep JWT lightweight
- Expire inactive sessions automatically
- Optimize authentication queries
- Index frequently searched columns

---

# API Mapping

## Authentication APIs

POST /api/auth/login

POST /api/auth/logout

POST /api/auth/forgot-password

POST /api/auth/reset-password

GET /api/auth/profile

PUT /api/auth/profile

PUT /api/auth/change-password

POST /api/auth/verify-email

POST /api/auth/refresh-token

---

# UI Pages

- Login
- Forgot Password
- Reset Password
- Email Verification
- Profile
- Edit Profile
- Change Password
- Unauthorized
- Forbidden

---

# Dependencies

This module is required by:

- Academic Module
- Attendance Module
- LMS Module
- Chat Module
- Finance Module
- Student Activities Module
- Food Court Module
- AI Exam Module
- Notification Module
- All User Portals

---

# Future Expansion

- Two-Factor Authentication (2FA)
- Google Login
- Microsoft Login
- Face Recognition Login
- Fingerprint Login
- Passwordless Authentication

---

# Notes

The Authentication Module is the security foundation of the NextGen Smart University Platform.

Every protected module depends on this module for authentication and authorization.