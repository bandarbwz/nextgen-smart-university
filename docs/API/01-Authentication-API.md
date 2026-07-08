# Authentication API

## Overview

The Authentication API provides secure authentication and authorization services for the NextGen Smart University Platform.

All protected APIs require a valid JWT access token.

---

# Base URL

/api/v1/auth

---

# Authentication

Authentication Method

Bearer Token (JWT)

Example

Authorization: Bearer <access_token>

---

# Endpoints

## Login

POST /login

Description

Authenticate a user and return an access token.

Request

{
    "email": "student@university.edu",
    "password": "********"
}

Response

{
    "success": true,
    "access_token": "...",
    "refresh_token": "...",
    "expires_in": 3600,
    "user": {}
}

---

## Logout

POST /logout

Description

Terminate the current user session.

Authentication

Required

---

## Refresh Token

POST /refresh

Description

Generate a new access token using a refresh token.

Authentication

Refresh Token Required

---

## Forgot Password

POST /forgot-password

Description

Send a password reset email.

Request

{
    "email":"student@university.edu"
}

---

## Reset Password

POST /reset-password

Description

Reset user password.

---

## Change Password

PUT /change-password

Authentication

Required

---

## Get Profile

GET /profile

Authentication

Required

Returns

- User Information
- Role
- Faculty
- Department

---

## Update Profile

PUT /profile

Authentication

Required

Editable Fields

- Phone
- Photo
- Emergency Contact

---

## Active Sessions

GET /sessions

Authentication

Required

Returns

All active login sessions.

---

## Logout All Devices

POST /logout-all

Authentication

Required

Terminates all active sessions.

---

# Response Codes

200 OK

201 Created

400 Bad Request

401 Unauthorized

403 Forbidden

404 Not Found

422 Validation Error

500 Internal Server Error

---

# Security

- JWT Authentication
- Password Hashing
- Session Timeout
- Role Validation
- Rate Limiting

---

# Future APIs

- Google Login
- Microsoft Login
- Face Login
- Two-Factor Authentication

---

# Notes

All protected endpoints require a valid JWT access token.