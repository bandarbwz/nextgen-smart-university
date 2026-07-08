# System Architecture

## Purpose

This document describes the overall architecture of the NextGen Smart University Platform (NSUP).

The architecture separates responsibilities into independent layers to improve scalability, maintainability, security, and performance.

---

# Architecture Style

The system follows a Multi-Tier MVC Architecture.

The application is divided into four main layers:

- Frontend Layer
- Backend Layer
- Database Layer
- Artificial Intelligence Layer

Each layer communicates only through defined interfaces.

---

# High-Level Architecture

```
+----------------------------------------------------+
|                  React Frontend                    |
|----------------------------------------------------|
| Dashboards • LMS • Attendance • Finance • Chat     |
+-------------------------▲--------------------------+
                          |
                     REST API (HTTPS)
                          |
+-------------------------▼--------------------------+
|                  PHP Backend (MVC)                 |
|----------------------------------------------------|
| Controllers • Services • Models • Authentication   |
| Validation • Business Logic • File Management      |
+-------------------------▲--------------------------+
                          |
                      PDO (MySQL)
                          |
+-------------------------▼--------------------------+
|                  MySQL Database                    |
|----------------------------------------------------|
| Students • Courses • Attendance • Finance          |
| LMS • Activities • Reports                         |
+-------------------------▲--------------------------+
                          |
                     HTTP REST API
                          |
+-------------------------▼--------------------------+
|            Python AI Service (FastAPI)             |
|----------------------------------------------------|
| Face Detection • Eye Tracking • Head Pose          |
| Browser Monitoring • AI Reports                    |
+----------------------------------------------------+
```

---

# Frontend Layer

Technology

- React 19
- Bootstrap 5
- Axios
- React Router

Responsibilities

- User Interface
- Dashboard
- Forms
- Authentication State
- API Communication
- Client-side Validation

The frontend never communicates directly with the database.

---

# Backend Layer

Technology

- PHP 8.4
- MVC Architecture

Responsibilities

- Authentication
- Authorization
- Business Logic
- REST API
- Validation
- File Management
- PDF Generation
- Email Services

The backend is responsible for processing all requests from the frontend.

---

# Database Layer

Technology

- MySQL 8

Responsibilities

- Store application data
- Maintain relationships
- Enforce constraints
- Preserve data integrity

Only the PHP backend is allowed to access the database.

---

# Artificial Intelligence Layer

Technology

- Python
- FastAPI
- OpenCV
- MediaPipe

Responsibilities

- Identity Verification
- Face Detection
- Eye Tracking
- Head Pose Detection
- Browser Tab Detection
- Fullscreen Detection
- AI Examination Monitoring
- AI Report Generation

The AI service communicates with the PHP backend using secure HTTP APIs.

The AI system does not make academic decisions. It only generates monitoring reports.

---

# File Storage

The system stores:

- Student Photos
- Lecturer Photos
- Assignment Files
- Course Materials
- Event Images
- Restaurant Images
- Generated Reports
- AI Examination Reports

Only file paths are stored in the database.

---

# Communication Flow

Student

↓

React Frontend

↓

REST API

↓

PHP Backend

↓

MySQL Database

↓

Response Returned to React

---

AI Examination Flow

React

↓

PHP Backend

↓

Python FastAPI

↓

AI Analysis

↓

PHP Backend

↓

Store Report in MySQL

↓

Return Result

---

# Security

Authentication

- JWT Authentication

Authorization

- Role-Based Access Control (RBAC)

Password Security

- Password Hashing

Communication

- HTTPS

Validation

- Server-side Validation
- Client-side Validation

---

# Scalability

The architecture supports:

- Multiple Faculties
- Multiple Campuses
- More than 50,000 Users
- Additional Future Modules
- Future Mobile Application Integration

---

# Design Principles

The architecture follows:

- MVC Architecture
- Separation of Concerns
- Modular Design
- Reusability
- Scalability
- Maintainability
- Security by Design

---

# Architecture Summary

Frontend

React + Bootstrap

↓

Backend

PHP MVC + REST API

↓

Database

MySQL

↓

Artificial Intelligence

Python + FastAPI

All communication between components is performed through secure REST APIs.