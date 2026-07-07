# RolePermission Table

## Purpose

This table connects roles with permissions.

It allows each role to have multiple permissions, and each permission to belong to multiple roles.

---

# Table Name

RolePermission

---

# Columns

| Column | Type | Description |
|---------|------|-------------|
| id | BIGINT | Primary Key |
| role_id | BIGINT | Foreign Key → Role.id |
| permission_id | BIGINT | Foreign Key → Permission.id |
| created_at | TIMESTAMP | Creation Date |

---

# Relationships

- One Role can have many Permissions.
- One Permission can belong to many Roles.

---

# Rules

- The same permission cannot be assigned twice to the same role.
- Both role_id and permission_id are required.
- Delete related records if a role or permission is permanently removed.

---

# Example

Student

- View Dashboard
- Register Course
- View Grades

Lecturer

- Upload Materials
- Grade Students
- Generate QR Attendance

Administrator

- Full Access

Restaurant Owner

- Manage Menu
- View Orders

STAD Staff

- Create Events
- Manage Clubs

---

# Notes

This table is the core of the Role-Based Access Control (RBAC) system.