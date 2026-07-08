# Attendance API

## Overview

The Attendance API manages attendance records, QR sessions, excuses, and attendance reports.

---

# Base URL

/api/v1/attendance

---

# Authentication

Bearer Token (JWT)

---

# QR Attendance

POST /qr/start

POST /qr/scan

POST /qr/end

---

# Attendance

GET /attendance

GET /attendance/{id}

POST /attendance

PUT /attendance/{id}

---

# Excuses

POST /attendance/excuse

GET /attendance/excuse

PUT /attendance/excuse/{id}

---

# Reports

GET /attendance/report/student

GET /attendance/report/section

GET /attendance/report/course

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

- QR Validation
- GPS Validation
- JWT Authentication

---

# Notes

Attendance APIs integrate with Academic and Student Activities modules.