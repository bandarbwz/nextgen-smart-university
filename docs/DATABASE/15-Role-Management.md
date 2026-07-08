# Role Management Database

## Purpose

This document defines the database structure for user roles and permissions.

The platform supports multiple roles for a single user.

---

# Overview

Examples

- Student
- Lecturer
- Coordinator
- Administrator
- STAD Staff
- Restaurant Owner

One user may have multiple roles.

Example

Ahmed

- Lecturer
- Coordinator

---

# Tables

## roles

Fields

- id
- name
- description
- created_at

---

## permissions

Fields

- id
- name
- module
- description

---

## role_permissions

Fields

- id
- role_id
- permission_id

---

## user_roles

Fields

- id
- user_id
- role_id
- assigned_by
- assigned_at

---

# Relationships

roles

Many → Many

permissions

using role_permissions

---

users

Many → Many

roles

using user_roles

---

# Business Rules

- Users may have multiple roles.
- Permissions are inherited from assigned roles.
- Only administrators may assign roles.
- Users select a workspace after login when multiple roles exist.

---

# Notes

This structure supports future user roles without database changes.