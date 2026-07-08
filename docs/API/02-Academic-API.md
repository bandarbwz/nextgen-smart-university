# Academic API

## Overview

The Academic API manages all academic operations including students, lecturers, courses, sections, enrollment, schedules, and transcripts.

---

# Base URL

/api/v1/academic

---

# Authentication

Bearer Token (JWT)

---

# Student Endpoints

GET /students

GET /students/{id}

PUT /students/{id}

GET /students/{id}/transcript

GET /students/{id}/gpa

GET /students/{id}/schedule

---

# Lecturer Endpoints

GET /lecturers

GET /lecturers/{id}

GET /lecturers/{id}/courses

---

# Course Endpoints

GET /courses

GET /courses/{id}

POST /courses

PUT /courses/{id}

DELETE /courses/{id}

---

# Enrollment

POST /enrollments

DELETE /enrollments/{id}

GET /students/{id}/enrollments

---

# Semester

GET /semesters

GET /current-semester

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

- JWT Required
- Role Validation
- Permission Validation

---

# Notes

Only authorized users can modify academic information.