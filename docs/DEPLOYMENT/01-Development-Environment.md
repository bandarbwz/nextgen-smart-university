# Development Environment

## Overview

This document defines the local development environment for the NextGen Smart University Platform.

---

# Operating Systems

- Windows 11
- macOS
- Ubuntu Linux

---

# Required Software

- PHP 8.4 or later
- Composer
- Node.js (LTS)
- npm
- Git
- Visual Studio Code
- MySQL 8
- Python 3.12
- Postman

Node.js is needed to build and run the React frontend with Vite. It is **not** used by the backend, which runs on PHP.

---

# Frontend

- React
- Design tokens (custom CSS)
- Axios

---

# Backend

- PHP 8.4
- Composer
- PDO
- PHPUnit

Run the API with PHP's built in server during development:

```
php -S 127.0.0.1:8000 -t backend/public backend/public/index.php
```

---

# AI Service

- Python
- FastAPI
- OpenCV
- MediaPipe

---

# Database

MySQL

---

# Version Control

Git + GitHub

---

# Success Criteria

Every developer can run the platform locally using the same environment.