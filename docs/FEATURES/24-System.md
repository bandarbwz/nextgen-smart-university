# System Module

## Purpose

The System Module manages the core services required by the platform.

It includes notifications, system settings, audit logs, file management, announcements, and background jobs.

This module supports all other modules and provides centralized system management.

---

# Objectives

- Manage system settings.
- Store notifications.
- Record audit logs.
- Manage uploaded files.
- Store announcements.
- Support scheduled jobs.
- Monitor system health.

---

# Database Tables

## Notification

Purpose

Stores system notifications.

Columns

- id
- user_id
- title
- message
- notification_type
- related_module
- related_id
- is_read
- sent_at
- created_at

Notification Types

- Academic
- Attendance
- LMS
- Finance
- Food Court
- Events
- Chat
- AI Exam
- System

---

## SystemSetting

Purpose

Stores configurable system settings.

Columns

- id
- setting_key
- setting_value
- description
- updated_by
- updated_at

Examples

- University Name
- Semester Status
- Maximum Credit Hours
- Attendance Radius
- File Upload Limit
- Allowed File Types

---

## AuditLog

Purpose

Stores important system actions.

Columns

- id
- user_id
- module
- action
- old_value
- new_value
- ip_address
- device
- browser
- created_at

---

## UploadedFile

Purpose

Stores uploaded file information.

Columns

- id
- uploaded_by
- module
- file_name
- original_name
- file_type
- file_size
- file_path
- uploaded_at

---

## SystemAnnouncement

Purpose

Stores announcements visible to all users.

Columns

- id
- title
- content
- target_role
- published_by
- published_at
- expires_at
- status

Target Roles

- All Users
- Students
- Lecturers
- Coordinators
- Administrators
- Restaurant Owners
- STAD Staff

---

## BackgroundJob

Purpose

Stores scheduled background jobs.

Columns

- id
- job_name
- status
- started_at
- finished_at
- execution_time
- result

Status

- Pending
- Running
- Completed
- Failed

---

# Relationships

User

↓

Notification

---

User

↓

Audit Log

---

User

↓

Uploaded File

---

Administrator

↓

System Settings

↓

Announcements

↓

Background Jobs

---

# Business Rules

- Every important action should generate an audit log.
- Notifications are delivered based on user roles.
- Only administrators can modify system settings.
- Files are linked to their original module.
- Expired announcements are hidden automatically.

---

# Validation Rules

Notifications

- Title required.
- Message required.

System Settings

- Setting key must be unique.

Uploaded Files

- Allowed file types only.
- Maximum file size enforced.

---

# API Mapping

System APIs

- Get Notifications
- Mark Notification as Read
- View Announcements
- Update Settings
- Upload File
- View Audit Logs

---

# Future Expansion

Future improvements

- Email Notifications
- SMS Notifications
- Push Notifications
- Cloud File Storage
- AI System Monitoring
- Health Dashboard

---

# Notes

The System Module integrates with every module in the platform and provides shared services across the application.