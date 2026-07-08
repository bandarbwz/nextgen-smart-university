# Finance API

## Overview

The Finance API manages tuition fees, payments, invoices, refunds, scholarships, and financial reports.

---

# Base URL

/api/v1/finance

---

# Authentication

Bearer Token (JWT)

---

# Tuition

GET /tuition

GET /tuition/{id}

---

# Payments

POST /payments

GET /payments

GET /payments/{id}

---

# Invoices

GET /invoices

GET /invoices/{id}

DOWNLOAD /invoices/{id}

---

# Refunds

POST /refunds

GET /refunds

PUT /refunds/{id}

---

# Scholarships

GET /scholarships

POST /scholarships

PUT /scholarships/{id}

DELETE /scholarships/{id}

---

# Financial Reports

GET /reports/payments

GET /reports/revenue

GET /reports/refunds

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
- Payment Validation
- Role Validation

---

# Notes

Integrates with Academic, Food Court, Notification, and Student Portal.