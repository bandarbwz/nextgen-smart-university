# Frontend Architecture

## Overview

The frontend provides a responsive web interface for all platform users.

It is built using React and a custom design token system. Bootstrap was the original choice and was replaced by the supervisor on 2026-08-05; see `docs/PROJECT/004-Technology-Stack.md` for the reasoning.

---

# Technology Stack

- React
- React Router
- Design tokens (custom CSS)
- Axios
- Lucide React

---

# Folder Structure

```
src/

├── components/
├── pages/
├── layouts/
├── hooks/
├── services/
├── context/
├── assets/
├── utils/
└── routes/
```

---

# User Interfaces

The frontend provides interfaces for:

- Students
- Lecturers
- Coordinators
- Administrators
- STAD Staff
- Restaurant Owners

---

# Main Layout

Every portal includes:

- Sidebar
- Top Navigation
- Main Content
- Footer
- Notification Panel

---

# State Management

Frontend state includes:

- Authentication
- User Profile
- Notifications
- Current Semester
- Active Courses
- Theme

---

# Routing

Protected Routes

- Student Portal
- Lecturer Portal
- Coordinator Portal
- Administrator Portal
- STAD Portal
- Restaurant Portal

Public Routes

- Login
- Forgot Password
- Reset Password

---

# API Communication

Frontend communicates using:

- REST API
- Axios
- JWT Authentication

---

# Live Updates

The backend is native PHP and cannot host a persistent socket server, so there is no Socket.IO and no WebSocket connection. Screens that need fresh data poll the REST API instead.

Polling is used by:

- **Chat**, which requests messages after the last identifier it holds, every few seconds
- **Examination Monitor**, which refreshes sessions and violations every ten seconds

Polling pauses while the browser tab is hidden and catches up when it becomes visible again, so a background tab costs nothing.

Everything else loads on navigation or after an action, not on a timer. Describe this behaviour as polling, not as real time.

---

# Responsive Design

Supported devices:

- Desktop
- Laptop
- Tablet
- Mobile Browser

---

# Success Criteria

The frontend is complete when every user can access all platform features through a fast, responsive, and user-friendly interface.