# Chat API

## Overview

The Chat API provides real-time messaging services for students, lecturers, coordinators, administrators, restaurant owners, and STAD staff.

It supports course chat rooms, private messaging, group chats, media sharing, and message management.

---

# Base URL

/api/v1/chat

---

# Authentication

Bearer Token (JWT)

---

# Chat Rooms

GET /rooms

GET /rooms/{id}

POST /rooms

PUT /rooms/{id}

DELETE /rooms/{id}

---

# Messages

GET /rooms/{id}/messages

POST /rooms/{id}/messages

PUT /messages/{id}

DELETE /messages/{id}

---

# Attachments

POST /messages/{id}/attachments

GET /attachments/{id}

DELETE /attachments/{id}

---

# Reactions

POST /messages/{id}/reaction

DELETE /messages/{id}/reaction

---

# Read Receipts

POST /messages/{id}/read

GET /messages/{id}/reads

---

# Search

GET /search

Supports:

- Message
- Sender
- Date
- File Name

---

# Response Codes

200

201

400

401

403

404

422

500

---

# Security

- JWT Authentication
- Room Permission Validation
- File Validation
- Malware Scan
- Rate Limiting

---

# Notes

Supports WebSocket / Socket.IO for real-time communication.