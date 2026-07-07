# Role Table

## Purpose

Stores all user roles in the system.

---

# Table Name

Role

---

# Columns

| Column | Type | Description |
|---------|------|-------------|
| id | BIGINT | Primary Key |
| name | VARCHAR(100) | Role Name |
| description | TEXT | Role Description |
| created_at | TIMESTAMP | Creation Date |
| updated_at | TIMESTAMP | Last Update |

---

# Default Roles

- Student
- Lecturer
- Coordinator
- Administrator
- STAD Staff
- Restaurant Owner

---

# Relationships

One Role can have many Users.

---

# Rules

- Role name must be unique.
- Roles cannot be deleted if assigned to users.
- Only administrators can create or modify roles.

---

# Notes

All users must belong to exactly one role.