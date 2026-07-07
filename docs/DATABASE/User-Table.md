# User Table

## Purpose

Stores login information for every user in the system.

Every account belongs to exactly one role.

---

# Table Name

User

---

# Columns

| Column | Type | Description |
|---------|------|-------------|
| id | BIGINT | Primary Key |
| role_id | BIGINT | User Role |
| full_name | VARCHAR(150) | Full Name |
| university_id | VARCHAR(30) | Student or Staff ID |
| email | VARCHAR(150) | University Email |
| phone | VARCHAR(20) | Phone Number |
| password | VARCHAR(255) | Hashed Password |
| profile_photo | VARCHAR(255) | Profile Image |
| status | ENUM | Active, Inactive, Suspended |
| last_login | DATETIME | Last Login |
| created_at | TIMESTAMP | Creation Date |
| updated_at | TIMESTAMP | Last Update |

---

# Relationships

User belongs to Role.

One User can have:

- Student Profile
- Lecturer Profile
- Coordinator Profile
- STAD Profile
- Restaurant Owner Profile

---

# Indexes

- email
- university_id
- role_id

---

# Rules

- Email must be unique.
- University ID must be unique.
- Password must be encrypted.
- Deleted users should not lose historical records.

---

# Notes

Every user in the platform must have one record in this table.