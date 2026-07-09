# Food Court API

## Purpose

This document defines the Food Court REST APIs for the NextGen Smart University Platform.

The Food Court API manages restaurants, menus, food categories, customer orders, payments, order tracking, reviews, and restaurant management.

---

# Base URL

```
/api/v1/food-court
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

# Restaurant APIs

---

## Get Restaurants

```
GET /api/v1/food-court/restaurants
```

---

## Get Restaurant

```
GET /api/v1/food-court/restaurants/{id}
```

---

## Create Restaurant

```
POST /api/v1/food-court/restaurants
```

Permissions

- Administrator

---

## Update Restaurant

```
PUT /api/v1/food-court/restaurants/{id}
```

---

## Delete Restaurant

```
DELETE /api/v1/food-court/restaurants/{id}
```

---

# Food Category APIs

---

## Get Categories

```
GET /api/v1/food-court/categories
```

---

## Create Category

```
POST /api/v1/food-court/categories
```

---

## Update Category

```
PUT /api/v1/food-court/categories/{id}
```

---

## Delete Category

```
DELETE /api/v1/food-court/categories/{id}
```

---

# Menu APIs

---

## Get Menu

```
GET /api/v1/food-court/restaurants/{id}/menu
```

---

## Get Menu Item

```
GET /api/v1/food-court/menu/{id}
```

---

## Create Menu Item

```
POST /api/v1/food-court/menu
```

Permissions

- Restaurant Owner

---

## Update Menu Item

```
PUT /api/v1/food-court/menu/{id}
```

---

## Delete Menu Item

```
DELETE /api/v1/food-court/menu/{id}
```

---

# Order APIs

---

## Create Order

```
POST /api/v1/food-court/orders
```

Permissions

- Student
- Lecturer
- Coordinator
- Administrator

---

## Get Orders

```
GET /api/v1/food-court/orders
```

---

## Get Order

```
GET /api/v1/food-court/orders/{id}
```

---

## Cancel Order

```
PUT /api/v1/food-court/orders/{id}/cancel
```

---

## Update Order Status

```
PUT /api/v1/food-court/orders/{id}/status
```

Permissions

- Restaurant Owner

---

# Payment APIs

---

## Create Payment

```
POST /api/v1/food-court/payments
```

---

## Verify Payment

```
POST /api/v1/food-court/payments/verify
```

---

## Payment History

```
GET /api/v1/food-court/payments/history
```

---

# Review APIs

---

## Submit Review

```
POST /api/v1/food-court/reviews
```

---

## Get Restaurant Reviews

```
GET /api/v1/food-court/restaurants/{id}/reviews
```

---

## Delete Review

```
DELETE /api/v1/food-court/reviews/{id}
```

---

# Validation Rules

Restaurant

- Restaurant name is required.
- Restaurant owner is required.

Menu Item

- Item name required.
- Price must be greater than zero.
- Category required.

Order

- Customer must select at least one item.
- Quantity must be greater than zero.

Payment

- Payment amount must match order total.

---

# Security

- JWT Authentication
- Role-Based Access Control
- Secure Payment Validation
- Input Validation
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

- Browse Restaurants
- Place Orders
- Make Payments
- Submit Reviews

Restaurant Owner

- Manage Menu
- Manage Orders
- View Sales

Administrator

- Manage Restaurants
- Manage Categories
- Full Food Court Management

---

# Business Rules

- Restaurants manage only their own menus.
- Orders cannot be modified after preparation begins.
- Payments must be verified before confirming orders.
- Customers may review completed orders only.
- Order status updates trigger automatic notifications.
- Cancelled orders follow the platform refund policy.

---

# Dependencies

This API depends on:

- Authentication API

Related APIs

- Finance API
- Notification API
- Reports API

---

# Notes

The Food Court API manages restaurants, menus, orders, payments, and customer reviews. It integrates with the Finance, Notification, and Reporting modules to provide a complete campus food ordering experience.