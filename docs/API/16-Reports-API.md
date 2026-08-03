# Reports API

## Purpose

This document defines the Reports REST APIs for the NextGen Smart University Platform.

The Reports API is the reporting layer over the other modules. It does not store its own operational data; it reads live data from the Academic, Attendance, LMS, Finance and Food Court modules and returns it filtered by the caller's role.

---

# Base URL

```
/api/v1/reports
```

---

# Authentication

All endpoints require:

```
Authorization: Bearer <JWT_TOKEN>
```

---

# Content Type

```
Content-Type: application/json
```

---

# Catalogue

## Get Available Reports

Returns the reports the caller is allowed to run, so a client can build its
menu without hard coding role rules.

```
GET /api/v1/reports
```

### Success Response

```json
{
    "success": true,
    "message": "Available reports retrieved.",
    "data": {
        "reports": [
            { "key": "academic.transcript", "name": "Student Transcript", "category": "Academic" }
        ]
    }
}
```

---

# Academic Reports

---

## Student Transcript

```
GET /api/v1/reports/academic/transcript
```

Optional `student_id` for staff. A student always receives their own.

---

## GPA Report

```
GET /api/v1/reports/academic/gpa
```

---

## Course Enrolment Report

```
GET /api/v1/reports/academic/enrolment
```

### Permissions

- Coordinator
- Administrator

---

# Attendance Reports

---

## Student Attendance

```
GET /api/v1/reports/attendance/student
```

---

## Daily Attendance

```
GET /api/v1/reports/attendance/daily?date=2026-09-07
```

### Permissions

- Coordinator
- Administrator

---

## Monthly Attendance

```
GET /api/v1/reports/attendance/monthly?year=2026&month=9
```

---

# Assessment Reports

---

## Grade Distribution

```
GET /api/v1/reports/assessment/grade-distribution?section_id=1
```

### Permissions

- Lecturer, for their own sections
- Coordinator
- Administrator

---

# Finance Reports

---

## Student Balances

```
GET /api/v1/reports/finance/balances
```

### Permissions

- Administrator

---

## Revenue

```
GET /api/v1/reports/finance/revenue
```

---

## Outstanding Invoices

```
GET /api/v1/reports/finance/outstanding
```

---

# Food Court Reports

---

## Restaurant Sales

```
GET /api/v1/reports/food-court/sales?restaurant_id=1
```

### Permissions

- Restaurant Owner, for their own restaurant
- Administrator

---

# System Reports

---

## User Statistics

```
GET /api/v1/reports/system/users
```

### Permissions

- Administrator

---

## Login History

```
GET /api/v1/reports/system/logins
```

### Permissions

- Administrator

---

# Export

Any report in the catalogue can be exported. The export runs the same report
the caller is authorised to read, so export cannot be used to reach data the
caller could not otherwise see.

```
POST /api/v1/reports/export
```

### Request Body

```json
{
    "report": "finance.outstanding",
    "format": "csv",
    "parameters": {}
}
```

### Supported formats

- `csv`
- `pdf`
- `xlsx`

### Response

The file is streamed as an attachment.

### Error Responses

- 403 The caller may not run the requested report
- 404 The report key is not recognised
- 422 The format is not supported

---

# Validation Rules

- A date range is required where the report takes one.
- Permissions are checked against the report, not only the endpoint.
- The export format must be one of the supported formats.

---

# Security

- JWT Authentication
- Role-Based Access Control per report, not only per endpoint
- Students receive only their own data, and a supplied identifier for another
  student is ignored rather than honoured
- Report generation is written to the report history log

---

# HTTP Status Codes

| Code | Description |
|------|-------------|
|200|OK|
|400|Bad Request|
|401|Unauthorized|
|403|Forbidden|
|404|Not Found|
|422|Validation Error|
|500|Internal Server Error|

---

# Permissions

Student

- Own transcript, own GPA, own attendance, own finance records

Lecturer

- Attendance and grade distribution for their own sections

Coordinator

- Academic, attendance and assessment reports

Restaurant Owner

- Sales reports for their own restaurant

Administrator

- Every report

---

# Business Rules

- Reports read live data rather than a stored copy.
- Generated reports are read only.
- Users can access only the reports their role allows.
- Report generation activity is logged.

---

# Dependencies

This API depends on:

- Authentication API
- Academic API
- Attendance API
- LMS API
- Finance API
- Food Court API

---

# Notes

This specification was written during implementation. The `docs/API` folder
contained no Reports specification, although `docs/FEATURES/22-Reports.md`
describes the module and lists its endpoints under a shorter `/api/reports`
prefix. The `/api/v1` prefix used here matches every other API document and the
implemented modules.

Two tables listed for this module in `docs/DATABASE/01-Tables.md` are not
implemented. `ReportTemplate` belongs to the custom report builder, which the
feature document places under Future Expansion. `GeneratedReport` stores report
artifacts for asynchronous generation; reports here are produced synchronously
from live data, and any artifact a user chooses to keep is stored by the
Download Center instead. `ReportHistory` is implemented as the audit log.
