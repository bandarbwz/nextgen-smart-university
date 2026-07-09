# Learning Management System (LMS) API

## Purpose

This document defines the Learning Management System (LMS) REST APIs for the NextGen Smart University Platform.

The LMS API manages course materials, assignments, quizzes, submissions, grades, announcements, learning resources, and online learning activities.

---

# Base URL

```
/api/v1/lms
```

---

# Authentication

All endpoints require:

```
Authorization: Bearer <JWT_TOKEN>
```

---

# Content Type

Standard Requests

```
application/json
```

File Upload

```
multipart/form-data
```

---

# Course Material APIs

---

## Get Course Materials

```
GET /api/v1/lms/materials
```

---

## Get Material

```
GET /api/v1/lms/materials/{id}
```

---

## Upload Material

```
POST /api/v1/lms/materials
```

Permissions

- Lecturer

---

## Update Material

```
PUT /api/v1/lms/materials/{id}
```

---

## Delete Material

```
DELETE /api/v1/lms/materials/{id}
```

---

## Download Material

```
GET /api/v1/lms/materials/{id}/download
```

---

# Assignment APIs

---

## Get Assignments

```
GET /api/v1/lms/assignments
```

---

## Get Assignment

```
GET /api/v1/lms/assignments/{id}
```

---

## Create Assignment

```
POST /api/v1/lms/assignments
```

---

## Update Assignment

```
PUT /api/v1/lms/assignments/{id}
```

---

## Delete Assignment

```
DELETE /api/v1/lms/assignments/{id}
```

---

# Assignment Submission APIs

---

## Submit Assignment

```
POST /api/v1/lms/assignments/{id}/submit
```

Permissions

- Student

---

## Update Submission

```
PUT /api/v1/lms/submissions/{id}
```

---

## View Submission

```
GET /api/v1/lms/submissions/{id}
```

---

## Grade Submission

```
PUT /api/v1/lms/submissions/{id}/grade
```

Permissions

- Lecturer

---

# Quiz APIs

---

## Get Quizzes

```
GET /api/v1/lms/quizzes
```

---

## Get Quiz

```
GET /api/v1/lms/quizzes/{id}
```

---

## Create Quiz

```
POST /api/v1/lms/quizzes
```

---

## Update Quiz

```
PUT /api/v1/lms/quizzes/{id}
```

---

## Delete Quiz

```
DELETE /api/v1/lms/quizzes/{id}
```

---

## Submit Quiz

```
POST /api/v1/lms/quizzes/{id}/submit
```

Permissions

- Student

---

# Grade APIs

---

## Get Grades

```
GET /api/v1/lms/grades
```

---

## Publish Grades

```
POST /api/v1/lms/grades/publish
```

Permissions

- Lecturer

---

# Announcement APIs

---

## Get Announcements

```
GET /api/v1/lms/announcements
```

---

## Create Announcement

```
POST /api/v1/lms/announcements
```

---

## Update Announcement

```
PUT /api/v1/lms/announcements/{id}
```

---

## Delete Announcement

```
DELETE /api/v1/lms/announcements/{id}
```

---

# Resource APIs

---

## Get Resources

```
GET /api/v1/lms/resources
```

---

## Upload Resource

```
POST /api/v1/lms/resources
```

---

## Delete Resource

```
DELETE /api/v1/lms/resources/{id}
```

---

# Validation Rules

Materials

- Supported file type only.
- Maximum upload size must follow system configuration.

Assignments

- Due date required.
- Total marks must be greater than zero.

Submissions

- Student must be enrolled.
- Submission must be before deadline unless late submission is enabled.

Quizzes

- Duration must be greater than zero.
- Availability period must be valid.

Grades

- Marks cannot exceed total marks.

---

# Security

- JWT Authentication
- Role-Based Access Control
- Secure File Upload
- File Type Validation
- Virus Scan
- Audit Logging

---

# HTTP Status Codes

| Code | Description |
|------|-------------|
|200|OK|
|201|Created|
|400|Bad Request|
|401|Unauthorized|
|403|Forbidden|
|404|Not Found|
|409|Conflict|
|422|Validation Error|
|500|Internal Server Error|

---

# Permissions

Student

- View Materials
- Submit Assignments
- Submit Quizzes
- View Grades

Lecturer

- Upload Materials
- Create Assignments
- Create Quizzes
- Grade Students
- Publish Grades
- Manage Announcements

Coordinator

- View LMS Reports

Administrator

- Full LMS Management

---

# Business Rules

- Students can access only enrolled courses.
- Lecturers manage only their assigned sections.
- Assignment deadlines are enforced.
- Late submissions follow lecturer settings.
- Published grades become read-only after approval.
- Learning materials remain available until archived.

---

# Dependencies

This API depends on:

- Authentication API
- Academic API

Related APIs

- Assessment API
- Grade Approval API
- Notification API
- Reports API

---

# Notes

The LMS API manages all online learning activities within the platform, including materials, assignments, quizzes, grading, announcements, and resources. It integrates closely with the Academic, Assessment, Notification, and Reporting modules.