# System Architecture

## Purpose

This document describes the overall architecture of the NextGen Smart University Platform.

---

# Architecture Style

The system follows a modular multi-tier architecture.

The application is divided into independent layers.

- Frontend
- Backend
- Database
- AI Services
- Realtime Services

---

# Frontend Layer

Technology

- React
- Bootstrap 5
- Axios

Responsibilities

- User Interface
- Forms
- Dashboard
- API Requests
- Authentication State

The frontend never communicates directly with the database.

---

# Backend Layer

Technology

- PHP 8
- MVC Architecture

Responsibilities

- Business Logic
- Authentication
- Validation
- REST APIs
- File Management

---

# Database Layer

Technology

- MySQL 8

Responsibilities

- Store all system data
- Relationships
- Constraints
- Data Integrity

---

# AI Layer

Technology

- Python
- OpenCV
- MediaPipe
- Flask

Responsibilities

- Face Detection
- Eye Tracking
- AI Exam Monitoring
- AI Reports

---

# Realtime Layer

Technology

- Node.js
- Socket.IO

Responsibilities

- Course Chat
- Notifications

---

# File Storage

Stores

- Student Photos
- Lecturer Photos
- Assignment Files
- Chat Files
- Event Images
- Restaurant Images

Only file paths are stored inside the database.

---

# Communication Flow

React

↓

REST API

↓

PHP Backend

↓

MySQL Database

Python AI Services communicate with the backend through HTTP APIs.

Node.js communicates with React using Socket.IO.

---

# Security

Authentication

JWT

Authorization

Role-Based Access Control

Password Hashing

HTTPS

Input Validation

---

# Scalability

The system should support

- Multiple campuses
- Multiple faculties
- More than 50,000 users
- Future cloud deployment

---

# Architecture Goals

- Scalability
- Security
- Performance
- Maintainability
- Modularity
- Reusability