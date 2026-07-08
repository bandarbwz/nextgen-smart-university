# Notification Center Database

## Purpose

This document defines the database structure for the centralized notification system.

---

# Overview

Notifications are generated automatically by all platform modules.

Examples include:

- Assignment Published
- Grade Approved
- Registration Approved
- Attendance Warning
- Food Order Ready
- Event Reminder
- Tuition Reminder
- System Announcement

---

# Tables

## notifications

Fields

- id
- title
- message
- type
- priority
- sender_id
- created_at

---

## user_notifications

Fields

- id
- notification_id
- user_id
- is_read
- read_at
- delivered_at

---

# Priority Levels

- Low
- Normal
- High
- Critical

---

# Notification Types

- Academic
- Attendance
- LMS
- Finance
- Food Court
- Event
- AI Exam
- System

---

# Relationships

notifications

1 → Many

user_notifications

---

users

1 → Many

user_notifications

---

# Business Rules

- Notifications are never deleted automatically.
- Users may mark notifications as read.
- Critical notifications remain pinned until viewed.

---

# Notes

The Notification Center is shared by every module within the platform.