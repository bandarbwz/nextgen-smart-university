# Folder Structure

## Purpose

This document defines the official folder structure for the NextGen Smart University Platform (NSUP).

All developers and AI assistants must follow this structure to maintain a clean, organized, and scalable project.

---

# Project Structure

```
nextgen-smart-university/
│
├── frontend/
│   ├── public/
│   ├── src/
│   │   ├── assets/
│   │   ├── components/
│   │   ├── layouts/
│   │   ├── pages/
│   │   ├── hooks/
│   │   ├── services/
│   │   ├── contexts/
│   │   ├── routes/
│   │   ├── utils/
│   │   ├── styles/
│   │   ├── App.jsx
│   │   └── main.jsx
│   ├── package.json
│   └── vite.config.js
│
├── backend/
│   ├── app/
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Middleware/
│   │   ├── Helpers/
│   │   └── Validation/
│   │
│   ├── config/
│   ├── routes/
│   ├── storage/
│   │   ├── uploads/
│   │   ├── reports/
│   │   └── temp/
│   ├── public/
│   ├── vendor/
│   ├── composer.json
│   └── index.php
│
├── ai/
│   ├── api/
│   ├── models/
│   ├── services/
│   ├── utils/
│   ├── requirements.txt
│   └── main.py
│
├── database/
│   ├── schema/
│   ├── migrations/
│   ├── seed/
│   └── backups/
│
├── docs/
│   ├── PROJECT/
│   ├── FEATURES/
│   ├── DATABASE/
│   ├── API/
│   ├── ARCHITECTURE/
│   ├── DEPLOYMENT/
│   └── TASKS/
│
├── .gitignore
├── README.md
└── LICENSE
```

---

# Frontend Structure

The frontend contains:

- Pages
- Components
- Layouts
- Assets
- Routes
- Services
- Contexts
- Hooks
- Utilities
- Styles

React components must remain reusable.

---

# Backend Structure

The backend follows the MVC architecture.

Controllers

- Receive HTTP requests.
- Validate requests.
- Call Services.
- Return API responses.

Models

- Handle database operations.

Services

- Contain business logic.

Middleware

- Authentication
- Authorization
- Request Filtering

Validation

- Request validation rules.

Helpers

- Reusable utility functions.

---

# AI Structure

The AI module is completely independent.

It contains:

- FastAPI APIs
- AI Models
- Detection Services
- Utility Functions

The AI communicates only with the PHP backend.

---

# Database Structure

Database files include:

- SQL Schema
- Migrations
- Seed Data
- Backups

Database scripts must never be placed inside the frontend.

---

# Documentation Structure

Documentation is organized into:

- PROJECT
- FEATURES
- DATABASE
- API
- ARCHITECTURE
- DEPLOYMENT
- TASKS

Every new feature must have documentation before implementation.

---

# Storage

The storage directory contains:

- Uploaded Files
- Generated Reports
- Temporary Files

Sensitive files should not be publicly accessible.

---

# Folder Rules

- Keep related files together.
- Do not create unnecessary folders.
- Do not duplicate functionality.
- Use clear folder names.
- Follow the project architecture.

---

# Final Rule

Every developer must follow this folder structure throughout the project.

Any structural changes must be documented before implementation.