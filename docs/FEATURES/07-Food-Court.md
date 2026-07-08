# Food Court Module

## Purpose

The Food Court Module manages restaurants, menus, food ordering, payments, and order tracking within the NextGen Smart University Platform.

It enables students to order food online while allowing restaurant owners to efficiently manage menus, orders, and daily operations.

---

# Objectives

- Manage restaurants.
- Manage food menus.
- Support online food ordering.
- Track order status.
- Manage payments.
- Generate sales reports.
- Improve food service efficiency.

---

# Scope

This module includes:

- Restaurant Management
- Menu Management
- Food Categories
- Online Ordering
- Order Tracking
- Payment Management
- Sales Reports

---

# Actors

- Student
- Restaurant Owner
- Administrator

---

# Database Tables

## Restaurant

Purpose

Stores restaurant information.

### Columns

- id
- owner_id
- restaurant_name
- description
- location
- phone
- opening_time
- closing_time
- status
- created_at
- updated_at

---

## FoodCategory

Purpose

Stores menu categories.

### Columns

- id
- restaurant_id
- category_name
- description

---

## MenuItem

Purpose

Stores food menu items.

### Columns

- id
- restaurant_id
- category_id
- item_name
- description
- price
- image_path
- availability
- preparation_time

---

## Order

Purpose

Stores food orders.

### Columns

- id
- student_id
- restaurant_id
- total_amount
- payment_method
- payment_status
- order_status
- ordered_at
- completed_at

---

## OrderItem

Purpose

Stores ordered menu items.

### Columns

- id
- order_id
- menu_item_id
- quantity
- unit_price
- subtotal

---

## Payment

Purpose

Stores payment information.

### Columns

- id
- order_id
- payment_reference
- payment_method
- payment_status
- paid_at

---

# Relationships

Restaurant

↓

Food Category

↓

Menu Item

---

Student

↓

Order

↓

Order Item

↓

Menu Item

---

Order

↓

Payment

---

# Business Rules

- Students may order only from active restaurants.
- Orders cannot contain unavailable items.
- Menu prices must be greater than zero.
- Restaurant owners can update menu availability.
- Completed orders cannot be edited.
- Cancelled orders are recorded in the order history.

---

# Validation Rules

Restaurant

- Restaurant name required.
- Phone number required.

Menu Item

- Name required.
- Price must be greater than zero.
- Category required.

Order

- At least one item required.
- Restaurant must be active.

Payment

- Valid payment method required.
- Payment status updated automatically.

---

# Permissions

## Student

- View Restaurants
- View Menus
- Place Orders
- Cancel Orders
- View Order History

## Restaurant Owner

- Manage Restaurant
- Manage Menu
- Accept Orders
- Reject Orders
- Update Order Status
- View Sales Reports

## Administrator

- Full Food Court Access

---

# Order Workflow

Student selects restaurant.

↓

Student selects menu items.

↓

Order is submitted.

↓

Restaurant accepts the order.

↓

Payment verified.

↓

Restaurant prepares food.

↓

Order marked as Ready.

↓

Student collects the order.

↓

Order completed.

---

# Order Status

- Pending
- Accepted
- Preparing
- Ready
- Completed
- Cancelled

---

# Payment Methods

- Cash
- Online Banking
- Credit Card
- Debit Card
- E-Wallet

---

# Notifications

Students receive notifications for:

- Order Confirmed
- Order Accepted
- Order Ready
- Order Completed
- Order Cancelled

Restaurant Owners receive notifications for:

- New Order
- Order Cancelled

---

# Security

- Validate all payments.
- Protect customer information.
- Validate uploaded menu images.
- Restrict restaurant management to owners.
- Log all payment transactions.

---

# Indexes

Restaurant

- owner_id

MenuItem

- restaurant_id
- category_id

Order

- student_id
- restaurant_id

Payment

- order_id

---

# Reports

Food Court Reports

- Daily Sales
- Monthly Revenue
- Popular Menu Items
- Restaurant Performance
- Order Statistics
- Payment Report

---

# Performance

- Cache restaurant menus.
- Optimize order retrieval.
- Index order history.
- Compress uploaded food images.

---

# API Mapping

GET /api/restaurants

GET /api/restaurants/{id}

GET /api/menu

POST /api/orders

PUT /api/orders/{id}

GET /api/orders/history

POST /api/payments

GET /api/reports/food-court

---

# UI Pages

- Restaurant List
- Restaurant Details
- Menu
- Shopping Cart
- Checkout
- Order History
- Restaurant Dashboard
- Sales Reports

---

# Dependencies

This module depends on:

- Authentication Module
- Notification Module
- Finance Module

The following modules depend on this module:

- Student Portal
- Restaurant Portal
- Reports Module

---

# Future Expansion

- AI Food Recommendation
- Table Reservation
- Delivery Tracking
- Loyalty Points
- Promotional Coupons
- Nutrition Information

---

# Notes

The Food Court Module integrates with the Finance Module for payment processing, the Notification Module for order updates, the Student Portal for ordering, and the Restaurant Portal for restaurant management.