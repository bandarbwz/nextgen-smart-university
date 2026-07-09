# Authentication API

## Purpose

This document defines the Authentication REST APIs for the NextGen Smart University Platform.

The Authentication API is responsible for user authentication, authorization, session management, password recovery, profile management, and account security.

---

# Base URL

```
/api/v1/auth
```

---

# Authentication

Some endpoints are public.

Protected endpoints require:

```
Authorization: Bearer <JWT_TOKEN>
```

---

# Content Type

```
Content-Type: application/json
```

---

# Endpoints

---

## Login

Authenticate a user and generate a JWT token.

### Endpoint

```
POST /api/v1/auth/login
```

### Authentication

Not Required

### Request Body

```json
{
    "email": "student@nextgen.edu",
    "password": "Password123!"
}
```

### Success Response

```json
{
    "success": true,
    "message": "Login successful.",
    "data": {
        "access_token": "...",
        "refresh_token": "...",
        "expires_in": 3600,
        "user": {}
    }
}
```

### Error Responses

- 401 Unauthorized
- 422 Validation Error

---

## Logout

Invalidate the current user session.

### Endpoint

```
POST /api/v1/auth/logout
```

### Authentication

Required

### Success Response

```json
{
    "success": true,
    "message": "Logout successful."
}
```

---

## Refresh Token

Generate a new access token.

### Endpoint

```
POST /api/v1/auth/refresh
```

### Authentication

Required

---

## Forgot Password

Send password reset instructions.

### Endpoint

```
POST /api/v1/auth/forgot-password
```

### Authentication

Not Required

### Request Body

```json
{
    "email": "student@nextgen.edu"
}
```

---

## Reset Password

Reset the account password.

### Endpoint

```
POST /api/v1/auth/reset-password
```

### Authentication

Not Required

### Request Body

```json
{
    "token": "...",
    "password": "Password123!",
    "password_confirmation": "Password123!"
}
```

---

## Change Password

Change the current user's password.

### Endpoint

```
PUT /api/v1/auth/change-password
```

### Authentication

Required

### Request Body

```json
{
    "current_password": "OldPassword123!",
    "new_password": "NewPassword123!",
    "password_confirmation": "NewPassword123!"
}
```

---

## Verify Email

Verify the user's email address.

### Endpoint

```
POST /api/v1/auth/verify-email
```

### Authentication

Required

---

## Resend Verification Email

Resend the email verification link.

### Endpoint

```
POST /api/v1/auth/resend-verification
```

### Authentication

Required

---

## Get Profile

Retrieve the authenticated user's profile.

### Endpoint

```
GET /api/v1/auth/profile
```

### Authentication

Required

---

## Update Profile

Update user profile information.

### Endpoint

```
PUT /api/v1/auth/profile
```

### Authentication

Required

---

## Active Sessions

Retrieve all active login sessions.

### Endpoint

```
GET /api/v1/auth/sessions
```

### Authentication

Required

---

## Revoke Session

Terminate a specific session.

### Endpoint

```
DELETE /api/v1/auth/sessions/{id}
```

### Authentication

Required

---

# Validation Rules

Login

- Email required.
- Password required.

Password

- Minimum 8 characters.
- Uppercase letter.
- Lowercase letter.
- Number.
- Special character.

Email

- Required.
- Valid format.
- Unique.

---

# Security

- JWT Authentication
- Refresh Tokens
- Password Hashing (bcrypt/Argon2)
- HTTPS Only
- Rate Limiting
- Input Validation
- SQL Injection Protection
- XSS Protection
- Account Lockout After Multiple Failed Attempts

---

# HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 409 | Conflict |
| 422 | Validation Error |
| 500 | Internal Server Error |

---

# Permissions

Accessible by:

- Student
- Lecturer
- Coordinator
- Administrator
- STAD Staff
- Restaurant Owner

---

# Business Rules

- Every user must authenticate before accessing protected APIs.
- JWT tokens expire automatically.
- Refresh tokens generate new access tokens.
- Email verification is required before full account access.
- Passwords are never stored in plain text.
- Failed login attempts are logged.
- Sessions can be revoked individually.

---

# Notes

The Authentication API is the security gateway of the NextGen Smart University Platform. All protected modules depend on successful authentication and authorization provided by this API.