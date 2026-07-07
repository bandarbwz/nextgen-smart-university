# Permission Table

## Purpose

Stores all system permissions.

Permissions define what actions each role is allowed to perform.

---

# Table Name

Permission

---

# Columns

| Column | Type | Description |
|---------|------|-------------|
| id | BIGINT | Primary Key |
| name | VARCHAR(100) | Permission Name |
| module | VARCHAR(100) | System Module |
| description | TEXT | Permission Description |
| created_at | TIMESTAMP | Creation Date |
| updated_at | TIMESTAMP | Last Update |

---

# Example Permissions

Authentication

- Login
- Logout

Student

- View Dashboard
- Edit Profile
- Register Course
- Drop Course

Lecturer

- Upload Materials
- Create Assignment
- Grade Assignment
- Take Attendance

Coordinator

- Approve Registration
- Manage Sections

Administrator

- Manage Users
- Manage Roles
- Manage System Settings
- View Reports

Restaurant Owner

- Manage Menu
- Update Orders

STAD Staff

- Create Events
- Approve Event Registration
- Generate Certificates

---

# Relationships

Many Roles can have many Permissions.

This relationship is managed using the RolePermission table.

---

# Rules

- Permission names must be unique.
- Only administrators can create or modify permissions.

---

# Notes

Permissions are used by the RBAC system to control access across the platform.