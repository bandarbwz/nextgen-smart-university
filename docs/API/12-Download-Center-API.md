# Download Center API

## Purpose

This document defines the Download Center REST APIs for the NextGen Smart University Platform.

The Download Center API manages downloadable documents, academic reports, transcripts, certificates, receipts, learning materials, and user-generated exports.

---

# Base URL

```
/api/v1/download-center
```

---

# Authentication

All endpoints require:

```
Authorization: Bearer <JWT_TOKEN>
```

---

# Content Type

Downloads

```
application/octet-stream
```

Requests

```
application/json
```

---

# Download APIs

---

## Get Available Downloads

```
GET /api/v1/download-center/files
```

---

## Get Download Details

```
GET /api/v1/download-center/files/{id}
```

---

## Download File

```
GET /api/v1/download-center/files/{id}/download
```

---

## Delete File

```
DELETE /api/v1/download-center/files/{id}
```

Permissions

- Administrator

---

# Academic Document APIs

---

## Download Transcript

```
GET /api/v1/download-center/transcript
```

---

## Download Academic Schedule

```
GET /api/v1/download-center/schedule
```

---

## Download Grade Report

```
GET /api/v1/download-center/grades
```

---

## Download Enrollment Certificate

```
GET /api/v1/download-center/enrollment-certificate
```

---

## Download Graduation Certificate

```
GET /api/v1/download-center/graduation-certificate
```

---

# Finance Document APIs

---

## Download Invoice

```
GET /api/v1/download-center/invoices/{id}
```

---

## Download Payment Receipt

```
GET /api/v1/download-center/receipts/{id}
```

---

# LMS Document APIs

---

## Download Assignment

```
GET /api/v1/download-center/assignments/{id}
```

---

## Download Learning Material

```
GET /api/v1/download-center/materials/{id}
```

---

## Download Submission

```
GET /api/v1/download-center/submissions/{id}
```

---

# Report Export APIs

---

## Export PDF

```
POST /api/v1/download-center/export/pdf
```

---

## Export Excel

```
POST /api/v1/download-center/export/excel
```

---

## Export CSV

```
POST /api/v1/download-center/export/csv
```

---

# Validation Rules

Download

- File must exist.
- User must have permission.

Export

- Export type required.
- Requested data must exist.

Documents

- Student can only download personal academic documents.
- Financial documents require authorized access.

---

# Security

- JWT Authentication
- Role-Based Access Control
- Secure File Storage
- Temporary Download URLs
- Download Audit Logging

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
|422|Validation Error|
|500|Internal Server Error|

---

# Permissions

Student

- Download Transcript
- Download Schedule
- Download Receipts
- Download Learning Materials

Lecturer

- Download Course Reports
- Download Student Submissions

Administrator

- Full Download Center Management

---

# Business Rules

- Users may download only documents they are authorized to access.
- Every download is recorded in the audit log.
- Temporary download links expire automatically.
- Generated reports are available only after successful processing.
- Exported reports include the generation timestamp.

---

# Dependencies

This API depends on:

- Authentication API

Related APIs

- Academic API
- Finance API
- LMS API
- Reports API

---

# Notes

The Download Center API provides centralized access to downloadable academic, financial, and administrative documents. It ensures secure file delivery, export functionality, and complete audit tracking for all download activities.