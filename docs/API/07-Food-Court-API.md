# Food Court API

## Overview

The Food Court API manages restaurants, menus, orders, payments, customer reviews, and order tracking.

---

# Base URL

/api/v1/food-court

---

# Authentication

Bearer Token (JWT)

---

# Restaurants

GET /restaurants

GET /restaurants/{id}

POST /restaurants

PUT /restaurants/{id}

DELETE /restaurants/{id}

---

# Menu

GET /restaurants/{id}/menu

POST /menu

PUT /menu/{id}

DELETE /menu/{id}

---

# Orders

POST /orders

GET /orders

GET /orders/{id}

PUT /orders/{id}

DELETE /orders/{id}

---

# Payments

POST /payments

GET /payments/{id}

GET /payments/history

---

# Reviews

POST /reviews

GET /reviews

PUT /reviews/{id}

DELETE /reviews/{id}

---

# Favorites

POST /favorites

GET /favorites

DELETE /favorites/{id}

---

# Reports

GET /reports/sales

GET /reports/orders

GET /reports/revenue

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
- Permission Validation

---

# Notes

Integrates with Finance Module, Restaurant Portal, Notification Module, and Student Portal.