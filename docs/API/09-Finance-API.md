# Finance API

## Purpose

This document defines the Finance REST APIs for the NextGen Smart University Platform.

The Finance API manages tuition fee structures, student invoices, payments, scholarships, financial holds, and financial reporting.

---

# Base URL

```
/api/v1/finance
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

# Tuition Fee APIs

---

## Get Tuition Fees

```
GET /api/v1/finance/tuition-fees
```

Optional filters: `program_id`, `semester_id`

### Permissions

- Administrator

---

## Create Tuition Fee

```
POST /api/v1/finance/tuition-fees
```

### Request Body

```json
{
    "program_id": 1,
    "semester_id": 1,
    "fee_type": "Tuition",
    "amount": 4500.00
}
```

### Permissions

- Administrator

---

## Update Tuition Fee

```
PUT /api/v1/finance/tuition-fees/{id}
```

---

## Delete Tuition Fee

```
DELETE /api/v1/finance/tuition-fees/{id}
```

---

# Invoice APIs

---

## Get Invoices

```
GET /api/v1/finance/invoices
```

A student receives only their own invoices. Administrators may pass `student_id`.

---

## Get Invoice

```
GET /api/v1/finance/invoices/{id}
```

Returns the invoice with its payment history.

---

## Generate Invoice

Generates an invoice for a student for a semester from the tuition fee structure of their programme, less any active scholarship.

```
POST /api/v1/finance/invoices/generate
```

### Request Body

```json
{
    "student_id": 1,
    "semester_id": 1,
    "due_date": "2026-10-01"
}
```

### Permissions

- Administrator

### Error Responses

- 404 No tuition fees configured for the programme and semester
- 409 An invoice already exists for that student and semester

---

## Cancel Invoice

```
PUT /api/v1/finance/invoices/{id}/cancel
```

An invoice that has received a payment cannot be cancelled.

### Permissions

- Administrator

---

# Payment APIs

---

## Get Payments

```
GET /api/v1/finance/payments
```

---

## Record Payment

```
POST /api/v1/finance/payments
```

### Request Body

```json
{
    "invoice_id": 1,
    "payment_reference": "TXN-2026-0001",
    "payment_method": "Online Banking",
    "amount": 1500.00
}
```

### Permissions

- Administrator

### Error Responses

- 409 The payment exceeds the outstanding balance
- 409 The payment reference has already been used
- 409 The invoice is cancelled

---

## Get Receipt

```
GET /api/v1/finance/payments/{id}
```

---

# Scholarship APIs

---

## Get Scholarships

```
GET /api/v1/finance/scholarships
```

---

## Award Scholarship

```
POST /api/v1/finance/scholarships
```

### Request Body

```json
{
    "student_id": 1,
    "scholarship_name": "Merit Award",
    "amount": 1000.00,
    "start_date": "2026-09-01",
    "end_date": "2027-01-15"
}
```

### Permissions

- Administrator

---

## Update Scholarship

```
PUT /api/v1/finance/scholarships/{id}
```

---

## Revoke Scholarship

```
PUT /api/v1/finance/scholarships/{id}/revoke
```

---

# Financial Hold APIs

---

## Get Holds

```
GET /api/v1/finance/holds
```

---

## Apply Hold

```
POST /api/v1/finance/holds
```

### Request Body

```json
{
    "student_id": 1,
    "reason": "Outstanding tuition balance"
}
```

### Permissions

- Administrator

---

## Release Hold

```
PUT /api/v1/finance/holds/{id}/release
```

---

## Check My Standing

Returns whether the authenticated student is currently blocked from registration.

```
GET /api/v1/finance/standing
```

---

# Report APIs

---

## Student Balance Report

```
GET /api/v1/finance/reports/balances
```

---

## Revenue Report

```
GET /api/v1/finance/reports/revenue
```

Optional filters: `semester_id`

---

## Outstanding Invoice Report

```
GET /api/v1/finance/reports/outstanding
```

---

# Validation Rules

Invoice

- Amount must be greater than zero.
- Due date is required.

Payment

- Amount must be greater than zero.
- Payment reference must be unique.
- Payment method must be one of the supported methods.

Scholarship

- Amount must be greater than zero.
- Amount cannot exceed the tuition fees for the programme.
- End date must be after the start date.

Financial Hold

- Reason is required.

---

# Security

- JWT Authentication
- Role-Based Access Control
- Students may read only their own financial records
- Payment records are never deleted
- All financial changes are logged

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

- View own invoices
- View own payment history
- View own scholarships
- View own financial standing

Administrator

- Full finance management

---

# Business Rules

- Every invoice belongs to one student and one semester.
- An invoice is generated from the tuition fee structure of the student's
  programme for that semester, less any active scholarship.
- Payments cannot exceed the outstanding balance of the invoice.
- A payment reference is unique across the platform.
- Invoice status moves from Pending to Partially Paid to Paid automatically as
  payments are recorded, and to Overdue once the due date passes while a
  balance remains.
- Payment history is never deleted.
- Scholarships reduce the invoiced amount at generation time.
- Students with an active financial hold cannot register for courses.

---

# Dependencies

This API depends on:

- Authentication API
- Academic API

Related APIs

- Reports API
- Notification API

---

# Notes

This specification was reconstructed from `docs/FEATURES/09-Finance.md`. The
original file at this path was a duplicate of the AI Examination API and
contained no finance content.

Endpoint paths use the `/api/v1` prefix used by every other API document. The
feature document's API mapping section uses a shorter `/api/finance` form, which
is not the convention the implemented modules follow.

The feature document lists a "Finance Staff" actor, but there is no such role in
`docs/FEATURES/01-Authentication.md`, which defines six roles: Student,
Lecturer, Coordinator, Administrator, Restaurant Owner and STAD Staff. Finance
management is therefore assigned to Administrator until a Finance Staff role is
added to the authentication module.
