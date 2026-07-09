# Chat API

## Purpose

This document defines the Chat REST APIs for the NextGen Smart University Platform.

The Chat API provides secure real-time communication between students, lecturers, coordinators, administrators, STAD staff, and restaurant owners. It supports private messaging, course chat rooms, file sharing, media sharing, message reactions, and read receipts.

---

# Base URL

```
/api/v1/chat
```

---

# Authentication

All endpoints require:

```
Authorization: Bearer <JWT_TOKEN>
```

---

# Content Type

Standard Requests

```
application/json
```

File Upload

```
multipart/form-data
```

---

# Chat Room APIs

---

## Get Chat Rooms

```
GET /api/v1/chat/rooms
```

---

## Get Chat Room

```
GET /api/v1/chat/rooms/{id}
```

---

## Create Chat Room

```
POST /api/v1/chat/rooms
```

Permissions

- Lecturer
- Administrator

---

## Update Chat Room

```
PUT /api/v1/chat/rooms/{id}
```

---

## Delete Chat Room

```
DELETE /api/v1/chat/rooms/{id}
```

---

## Join Chat Room

```
POST /api/v1/chat/rooms/{id}/join
```

---

## Leave Chat Room

```
POST /api/v1/chat/rooms/{id}/leave
```

---

## Get Room Members

```
GET /api/v1/chat/rooms/{id}/members
```

---

# Message APIs

---

## Get Messages

```
GET /api/v1/chat/rooms/{id}/messages
```

---

## Send Message

```
POST /api/v1/chat/messages
```

---

## Edit Message

```
PUT /api/v1/chat/messages/{id}
```

---

## Delete Message

```
DELETE /api/v1/chat/messages/{id}
```

---

## Reply to Message

```
POST /api/v1/chat/messages/{id}/reply
```

---

## Forward Message

```
POST /api/v1/chat/messages/{id}/forward
```

---

## Pin Message

```
PUT /api/v1/chat/messages/{id}/pin
```

Permissions

- Lecturer
- Administrator

---

# Attachment APIs

---

## Upload Attachment

```
POST /api/v1/chat/attachments
```

---

## Download Attachment

```
GET /api/v1/chat/attachments/{id}
```

---

## Delete Attachment

```
DELETE /api/v1/chat/attachments/{id}
```

---

# Reaction APIs

---

## Add Reaction

```
POST /api/v1/chat/messages/{id}/reaction
```

---

## Remove Reaction

```
DELETE /api/v1/chat/messages/{id}/reaction
```

---

# Read Receipt APIs

---

## Mark Message as Read

```
PUT /api/v1/chat/messages/{id}/read
```

---

## Get Read Receipts

```
GET /api/v1/chat/messages/{id}/read
```

---

# Search APIs

---

## Search Messages

```
GET /api/v1/chat/search
```

Example

```
GET /api/v1/chat/search?keyword=assignment
```

---

# Validation Rules

Messages

- Message cannot be empty.
- Maximum message length follows system settings.

Attachments

- File type must be supported.
- File size must not exceed the upload limit.

Chat Rooms

- User must be a room member.
- Course chat membership is managed automatically.

---

# Security

- JWT Authentication
- Role-Based Access Control
- Secure File Upload
- File Validation
- Malware Scanning
- Audit Logging

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

Student

- View Chat Rooms
- Send Messages
- Upload Files
- React to Messages

Lecturer

- Manage Course Chats
- Pin Messages
- Moderate Discussions

Coordinator

- View Course Chats

Administrator

- Full Chat Management

---

# Business Rules

- Students are automatically added to course chat rooms after enrollment.
- Students are automatically removed after dropping a course.
- Messages are permanently logged.
- Deleted messages are soft deleted for audit purposes.
- Attachments are securely stored.
- Read receipts update automatically.
- Course chat rooms are created automatically when a section is created.

---

# Dependencies

This API depends on:

- Authentication API
- Academic API

Related APIs

- Notification API
- Download Center API

---

# Notes

The Chat API provides secure real-time communication for the NextGen Smart University Platform. It supports course discussions, private messaging, media sharing, reactions, read receipts, and integrates with the Notification and Academic modules.