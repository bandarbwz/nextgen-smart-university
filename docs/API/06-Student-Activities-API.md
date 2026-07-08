# Student Activities API

## Overview

The Student Activities API manages clubs, university events, registrations, certificates, reward points, and student participation.

---

# Base URL

/api/v1/stad

---

# Authentication

Bearer Token (JWT)

---

# Clubs

GET /clubs

GET /clubs/{id}

POST /clubs

PUT /clubs/{id}

DELETE /clubs/{id}

---

# Events

GET /events

GET /events/{id}

POST /events

PUT /events/{id}

DELETE /events/{id}

---

# Registration

POST /events/{id}/register

DELETE /events/{id}/cancel

GET /events/{id}/participants

---

# Attendance

POST /events/{id}/attendance

GET /attendance

---

# Reward Points

GET /reward-points

GET /leaderboard

---

# Certificates

GET /certificates

GET /certificates/{id}

DOWNLOAD /certificates/{id}

---

# Excuse Letters

GET /excuse-letters

DOWNLOAD /excuse-letters/{id}

---

# Reports

GET /reports/events

GET /reports/clubs

GET /reports/participation

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
- QR Validation
- Role Validation

---

# Notes

Integrates with Academic, Attendance, and Notification modules.