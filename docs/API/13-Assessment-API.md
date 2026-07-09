# Assessment API

## Purpose

This document defines the Assessment REST APIs for the NextGen Smart University Platform.

The Assessment API manages examinations, quizzes, assignments, grading, rubrics, assessment results, grade publication, and student performance evaluation.

---

# Base URL

```
/api/v1/assessment
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

# Examination APIs

---

## Get Examinations

```
GET /api/v1/assessment/examinations
```

---

## Get Examination

```
GET /api/v1/assessment/examinations/{id}
```

---

## Create Examination

```
POST /api/v1/assessment/examinations
```

Permissions

- Lecturer

---

## Update Examination

```
PUT /api/v1/assessment/examinations/{id}
```

---

## Delete Examination

```
DELETE /api/v1/assessment/examinations/{id}
```

---

## Publish Examination

```
PUT /api/v1/assessment/examinations/{id}/publish
```

---

# Assignment APIs

---

## Get Assignments

```
GET /api/v1/assessment/assignments
```

---

## Create Assignment

```
POST /api/v1/assessment/assignments
```

---

## Update Assignment

```
PUT /api/v1/assessment/assignments/{id}
```

---

## Delete Assignment

```
DELETE /api/v1/assessment/assignments/{id}
```

---

# Quiz APIs

---

## Get Quizzes

```
GET /api/v1/assessment/quizzes
```

---

## Create Quiz

```
POST /api/v1/assessment/quizzes
```

---

## Update Quiz

```
PUT /api/v1/assessment/quizzes/{id}
```

---

## Delete Quiz

```
DELETE /api/v1/assessment/quizzes/{id}
```

---

# Grade APIs

---

## Get Grades

```
GET /api/v1/assessment/grades
```

---

## Get Student Grades

```
GET /api/v1/assessment/grades/{student_id}
```

---

## Submit Grades

```
POST /api/v1/assessment/grades
```

Permissions

- Lecturer

---

## Update Grades

```
PUT /api/v1/assessment/grades/{id}
```

---

## Publish Grades

```
PUT /api/v1/assessment/grades/publish
```

---

# Rubric APIs

---

## Get Rubrics

```
GET /api/v1/assessment/rubrics
```

---

## Create Rubric

```
POST /api/v1/assessment/rubrics
```

---

## Update Rubric

```
PUT /api/v1/assessment/rubrics/{id}
```

---

## Delete Rubric

```
DELETE /api/v1/assessment/rubrics/{id}
```

---

# Assessment Report APIs

---

## Student Performance Report

```
GET /api/v1/assessment/reports/student
```

---

## Course Assessment Report

```
GET /api/v1/assessment/reports/course
```

---

## Grade Distribution Report

```
GET /api/v1/assessment/reports/distribution
```

---

# Validation Rules

Assessment

- Title required.
- Course required.
- Total marks must be greater than zero.
- Due date required.

Grades

- Marks cannot exceed total marks.
- Student must be enrolled.

Rubrics

- At least one evaluation criterion is required.

---

# Security

- JWT Authentication
- Role-Based Access Control
- Input Validation
- Audit Logging
- Grade Change Tracking

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

- View Assessments
- View Grades

Lecturer

- Manage Assessments
- Grade Students
- Publish Grades

Coordinator

- Review Assessment Results

Administrator

- Full Assessment Management

---

# Business Rules

- Only lecturers assigned to a section can create assessments.
- Students may only access assessments for enrolled courses.
- Grades become read-only after publication unless reopened.
- All grade changes are logged for auditing.
- Assessment results automatically update GPA calculations after approval.

---

# Dependencies

This API depends on:

- Authentication API
- Academic API
- LMS API

Related APIs

- AI Examination API
- Grade Approval API
- Reports API
- Notification API

---

# Notes

The Assessment API manages examinations, assignments, quizzes, grading, rubrics, and academic evaluation. It integrates with the LMS, Academic, AI Examination, Grade Approval, and Reporting modules to provide a complete assessment management solution.