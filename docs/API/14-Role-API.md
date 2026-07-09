# Role Management API

## Purpose

This document defines the Role Management REST APIs for the NextGen Smart University Platform.

The Role Management API manages user roles, permissions, role assignments, access control, and authorization using Role-Based Access Control (RBAC).

---

# Base URL

```
/api/v1/roles
```

---

# Authentication

All endpoints require:

```
Authorization: Bearer <JWT_TOKEN>
```

Administrator privileges are required unless otherwise specified.

---

# Content Type

```
Content-Type: application/json
```

---

# Role APIs

---

## Get Roles

```
GET /api/v1/roles
```

---

## Get Role

```
GET /api/v1/roles/{id}
```

---

## Create Role

```
POST /api/v1/roles
```

Permissions

- Administrator

---

## Update Role

```
PUT /api/v1/roles/{id}
```

---

## Delete Role

```
DELETE /api/v1/roles/{id}
```

---

# Permission APIs

---

## Get Permissions

```
GET /api/v1/roles/permissions
```

---

## Get Permission

```
GET /api/v1/roles/permissions/{id}
```

---

## Create Permission

```
POST /api/v1/roles/permissions
```

---

## Update Permission

```
PUT /api/v1/roles/permissions/{id}
```

---

## Delete Permission

```
DELETE /api/v1/roles/permissions/{id}
```

---

# Role Assignment APIs

---

## Assign Role

```
POST /api/v1/roles/assign
```

### Request Body

```json
{
    "user_id": 25,
    "role_id": 3
}
```

---

## Remove Role

```
DELETE /api/v1/roles/remove
```

---

## Get User Role

```
GET /api/v1/roles/user/{user_id}
```

---

## Get Users By Role

```
GET /api/v1/roles/{id}/users
```

---

# Role Permission APIs

---

## Assign Permission

```
POST /api/v1/roles/{id}/permissions
```

---

## Remove Permission

```
DELETE /api/v1/roles/{id}/permissions/{permission_id}
```

---

## Get Role Permissions

```
GET /api/v1/roles/{id}/permissions
```

---

# Validation Rules

Role

- Name is required.
- Name must be unique.
- Description is optional.

Permission

- Module is required.
- Permission name is required.
- Permission name must be unique.

Assignment

- User must exist.
- Role must exist.
- Duplicate assignments are not allowed.

---

# Security

- JWT Authentication
- Role-Based Access Control
- Permission Validation
- Audit Logging
- Access Verification

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

Administrator

- Create Roles
- Update Roles
- Delete Roles
- Manage Permissions
- Assign User Roles

Other Users

- View own assigned role only

---

# Business Rules

- Every user must have at least one role.
- Roles determine system access.
- Permissions are assigned through roles.
- Changes take effect immediately after assignment.
- Built-in system roles cannot be deleted.
- Every role modification is recorded in the audit log.

---

# Dependencies

This API depends on:

- Authentication API

Related APIs

- System API
- Reports API
- Notification API

---

# Notes

The Role Management API implements Role-Based Access Control (RBAC) across the NextGen Smart University Platform. It ensures secure authorization by managing roles, permissions, and user-role assignments while maintaining complete auditability of access control changes.