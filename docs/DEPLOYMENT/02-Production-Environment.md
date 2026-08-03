# Production Environment

## Overview

Defines the production environment used to host the platform.

---

# Server

- Ubuntu Server

---

# Web Server

- Nginx

---

# Backend

- PHP 8.4
- PHP-FPM
- Composer

Nginx passes PHP requests to PHP-FPM. The document root is `backend/public`, so the application code, the uploaded files and the logs all sit outside the web root and cannot be requested directly.

---

# Database

- MySQL

---

# AI Server

- Python
- FastAPI

---

# Storage

- Uploaded Files
- Assignment Files
- AI Recordings

---

# SSL

HTTPS Required

---

# Monitoring

- CPU
- Memory
- Disk
- Database
- API

---

# Success Criteria

Production environment supports continuous operation with high availability.