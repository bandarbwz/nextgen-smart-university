# Food Court Module

## Purpose

The Food Court Module manages all restaurants, menus, food ordering, payments, order tracking, and customer reviews within the university.

Students can browse menus, place orders, make payments, and receive notifications when their orders are ready.

Restaurant owners can manage menus, receive orders, update order status, and view sales reports.

---

# Objectives

- Manage restaurants.
- Manage menus.
- Process food orders.
- Support online payments.
- Notify students.
- Track order status.
- Generate sales reports.
- Collect customer reviews.

---

# Database Tables

## Restaurant

Purpose

Stores restaurant information.

Columns

- id
- owner_id
- name
- description
- logo
- location
- opening_time
- closing_time
- phone
- email
- payment_methods
- status
- created_at
- updated_at

---

## FoodCategory

Purpose

Stores menu categories.

Examples

- Meals
- Drinks
- Desserts
- Snacks

Columns

- id
- restaurant_id
- name
- description

---

## MenuItem

Purpose

Stores menu items.

Columns

- id
- restaurant_id
- category_id
- name
- description
- image
- price
- availability
- preparation_time
- created_at
- updated_at

---

## Order

Purpose

Stores student orders.

Columns

- id
- student_id
- restaurant_id
- order_number
- total_amount
- payment_method
- payment_status
- order_status
- pickup_time
- created_at

Payment Status

- Pending
- Paid
- Refunded

Order Status

- Pending
- Accepted
- Preparing
- Ready
- Completed
- Cancelled

---

## OrderItem

Purpose

Stores ordered menu items.

Columns

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

Columns

- id
- order_id
- payment_reference
- payment_method
- amount
- payment_status
- paid_at

---

## RestaurantReview

Purpose

Stores customer reviews.

Columns

- id
- restaurant_id
- student_id
- rating
- review
- created_at

Rating

- 1
- 2
- 3
- 4
- 5

---

## FavoriteRestaurant

Purpose

Stores students' favorite restaurants.

Columns

- id
- student_id
- restaurant_id
- created_at

---

# Relationships

Restaurant

↓

FoodCategory

↓

MenuItem

↓

OrderItem

↓

Order

↓

Payment

---

Restaurant

↓

RestaurantReview

↓

Student

---

Restaurant

↓

FavoriteRestaurant

↓

Student

---

# Business Rules

- Every restaurant must register before selling.
- Restaurant owners manage only their own restaurant.
- Menu items can be enabled or disabled.
- Orders cannot be modified after preparation starts.
- Students receive notifications for every status update.
- Reviews are allowed only after completed orders.

---

# Payment Methods

Supported methods

- Cash
- Credit Card
- Debit Card
- Online Banking
- E-Wallet

---

# Notifications

Students receive notifications for:

- Order Accepted
- Order Preparing
- Order Ready
- Order Completed
- Order Cancelled

Restaurant owners receive notifications for:

- New Order
- Payment Received
- Order Cancelled

---

# Validation Rules

Restaurant

- Name required
- Opening time required

Menu Item

- Name required
- Price greater than zero

Order

- At least one menu item required

Review

- Rating between 1 and 5

---

# API Mapping

Food Court APIs

- View Restaurants
- View Menu
- Place Order
- Cancel Order
- Track Order
- Pay Order
- Submit Review
- Manage Restaurant
- Manage Menu

---

# Future Expansion

Future improvements

- QR Pickup
- AI Food Recommendation
- Delivery Robot Integration
- Loyalty Rewards
- Discount Coupons
- Meal Subscription Plans

---

# Notes

The Food Court Module integrates with:

- Student Dashboard
- Restaurant Dashboard
- Notification Module
- Payment Module