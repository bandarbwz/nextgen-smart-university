# Backend Architecture

## Overview

The backend provides business logic, API services, authentication, database access, file management, and integration between all platform modules.

---

# Technology Stack

- Node.js
- Express.js
- MySQL
- Prisma ORM
- JWT
- Socket.IO
- Multer

---

# Folder Structure

```
server/

├── controllers/
├── routes/
├── services/
├── middleware/
├── models/
├── prisma/
├── uploads/
├── sockets/
├── utils/
└── config/
```

---

# Backend Layers

Request

↓

Route

↓

Middleware

↓

Controller

↓

Service

↓

Database

↓

Response

---

# Core Services

- Authentication
- Academic
- Attendance
- LMS
- Chat
- Food Court
- Finance
- AI Exam
- Notifications

---

# Middleware

- Authentication
- Authorization
- Validation
- Error Handling
- Logging

---

# File Upload

Supported files:

- PDF
- Images
- Videos
- Office Documents

---

# Security

- JWT Authentication
- Password Hashing
- Role Validation
- Rate Limiting
- Input Validation

---

# Success Criteria

The backend is complete when all business logic is separated into services and every feature is accessible through secure REST APIs.