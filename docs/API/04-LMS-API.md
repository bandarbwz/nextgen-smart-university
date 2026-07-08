# LMS API

## Overview

The LMS API manages learning materials, assignments, quizzes, grades, announcements, and online learning resources.

---

# Base URL

/api/v1/lms

---

# Authentication

Bearer Token (JWT)

---

# Materials

GET /materials

POST /materials

PUT /materials/{id}

DELETE /materials/{id}

---

# Assignments

GET /assignments

POST /assignments

PUT /assignments/{id}

DELETE /assignments/{id}

---

# Assignment Submission

POST /assignments/{id}/submit

GET /assignments/{id}/submissions

---

# Quiz

GET /quizzes

POST /quizzes

PUT /quizzes/{id}

DELETE /quizzes/{id}

POST /quizzes/{id}/submit

---

# Grades

GET /grades

GET /grades/{studentId}

POST /grades

PUT /grades/{id}

---

# Announcements

GET /announcements

POST /announcements

PUT /announcements/{id}

DELETE /announcements/{id}

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
- Course Permission Validation
- Secure File Upload

---

# Notes

The LMS API integrates with the Academic Module, AI Exam Module, and Notification System.