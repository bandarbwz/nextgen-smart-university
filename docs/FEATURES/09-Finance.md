# Finance Module

## Purpose

The Finance Module manages all financial operations within the NextGen Smart University Platform.

It handles tuition fees, student payments, restaurant payments, invoices, refunds, scholarships, financial reports, and transaction history.

The module provides secure and transparent financial management for students, administrators, and restaurant owners.

---

# Objectives

- Manage tuition fees.
- Process student payments.
- Process restaurant payments.
- Generate invoices.
- Record transactions.
- Manage scholarships.
- Handle refunds.
- Generate financial reports.

---

# Database Tables

## TuitionFee

Purpose

Stores tuition fee information.

Columns

- id
- student_id
- semester_id
- total_amount
- paid_amount
- remaining_amount
- payment_status
- due_date
- created_at
- updated_at

Payment Status

- Pending
- Partially Paid
- Paid
- Overdue

---

## PaymentTransaction

Purpose

Stores every payment transaction.

Columns

- id
- student_id
- order_id
- transaction_reference
- payment_type
- payment_method
- amount
- currency
- payment_status
- transaction_date

Payment Types

- Tuition
- Food Court
- Event
- Other

Payment Status

- Pending
- Successful
- Failed
- Refunded

---

## Invoice

Purpose

Stores generated invoices.

Columns

- id
- invoice_number
- student_id
- transaction_id
- invoice_type
- total_amount
- issue_date
- due_date
- pdf_path

Invoice Types

- Tuition
- Food
- Event
- Miscellaneous

---

## Scholarship

Purpose

Stores scholarship information.

Columns

- id
- student_id
- scholarship_name
- sponsor
- amount
- semester_id
- status
- approved_at

Status

- Pending
- Approved
- Rejected

---

## Refund

Purpose

Stores refund requests.

Columns

- id
- transaction_id
- student_id
- reason
- refund_amount
- refund_status
- requested_at
- processed_at

Refund Status

- Pending
- Approved
- Rejected
- Completed

---

# Relationships

Student

↓

Tuition Fee

↓

Payment Transaction

↓

Invoice

---

Student

↓

Scholarship

---

Payment Transaction

↓

Refund

---

# Business Rules

- Every payment generates a transaction.
- Every successful payment generates an invoice.
- Students cannot register for new courses if unpaid tuition exceeds university policy.
- Refunds require administrator approval.
- Scholarships reduce tuition automatically.

---

# Payment Methods

Supported methods

- Cash
- Credit Card
- Debit Card
- Online Banking
- E-Wallet

---

# Financial Reports

Students

- Tuition Summary
- Payment History
- Outstanding Balance

Administrators

- Daily Revenue
- Monthly Revenue
- Tuition Collection
- Restaurant Sales
- Refund Reports

Restaurant Owners

- Daily Orders
- Revenue
- Payment History

---

# Validation Rules

Payments

- Amount must be greater than zero.
- Payment reference must be unique.

Invoices

- Invoice number must be unique.

Scholarships

- Amount cannot exceed tuition fees.

---

# API Mapping

Finance APIs

- View Tuition
- Pay Tuition
- View Invoice
- Download Invoice
- Request Refund
- View Payment History
- Manage Scholarships

---

# Future Expansion

Future improvements

- Installment Plans
- Automatic Payment Reminders
- Multiple Currency Support
- Financial Analytics Dashboard
- AI Financial Assistant

---

# Notes

The Finance Module integrates with:

- Academic Module
- Food Court Module
- Student Dashboard
- Administrator Dashboard
- Notification Module