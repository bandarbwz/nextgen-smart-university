# Chat Module

## Purpose

The Chat Module provides secure real-time communication between students, lecturers, coordinators, administrators, STAD staff, and restaurant owners.

Each course automatically has its own chat room. Students are automatically added after successful course registration and removed after dropping the course.

The module also supports private messaging, group messaging, announcements, media sharing, and message management.

---

# Objectives

- Provide real-time communication.
- Support course discussions.
- Support private messaging.
- Support group messaging.
- Share files and media securely.
- Improve collaboration between users.
- Maintain communication history.
- Deliver real-time notifications.

---

# Scope

The Chat Module manages all communication features within the platform.

It includes:

- Course Chat
- Private Chat
- Group Chat
- Announcement Channels
- File Sharing
- Image Sharing
- Video Sharing
- Voice Messages
- Read Receipts
- Message Reactions

---

# Actors

- Student
- Lecturer
- Coordinator
- Administrator
- Restaurant Owner
- STAD Staff

---

# Database Tables

## ChatRoom

Purpose

Stores chat rooms.

### Columns

- id
- room_name
- room_type
- course_id
- section_id
- created_by
- created_at
- updated_at

### Room Types

- Course
- Private
- Group
- Announcement

---

## ChatMember

Purpose

Stores room members.

### Columns

- id
- room_id
- user_id
- role
- joined_at

### Roles

- Owner
- Lecturer
- Student
- Moderator

---

## Message

Purpose

Stores chat messages.

### Columns

- id
- room_id
- sender_id
- message_type
- message
- reply_to
- edited
- edited_at
- sent_at

### Message Types

- Text
- Image
- Video
- Voice
- File
- Sticker

---

## MessageAttachment

Purpose

Stores uploaded files.

### Columns

- id
- message_id
- file_name
- file_path
- file_size
- file_type

---

## MessageReaction

Purpose

Stores reactions.

### Columns

- id
- message_id
- user_id
- reaction

### Supported Reactions

- Like
- Love
- Laugh
- Sad
- Angry

---

## MessageRead

Purpose

Stores read receipts.

### Columns

- id
- message_id
- user_id
- read_at

---

# Relationships

ChatRoom

↓

ChatMember

↓

User

---

ChatRoom

↓

Message

↓

MessageAttachment

↓

MessageReaction

↓

MessageRead

---

# Business Rules

- Every course has one chat room.
- Students are automatically added after enrollment.
- Students are automatically removed after dropping the course.
- Only enrolled students can access course chats.
- Messages cannot be permanently deleted by students.
- Lecturers can pin important messages.
- Administrators can moderate all rooms.
- Announcement rooms are read-only for students.

---

# Validation Rules

Messages

- Cannot be empty.
- Maximum message length follows system settings.

Attachments

- Only approved file types.
- Maximum upload size enforced.
- File name required.

Rooms

- Course rooms are created automatically.
- Private rooms require two valid users.

---

# Permissions

## Student

- Send Messages
- Edit Own Messages
- Delete Own Messages
- Upload Files
- React to Messages
- View Course Chats

## Lecturer

- Send Messages
- Pin Messages
- Remove Messages
- Moderate Course Chat
- Upload Files

## Coordinator

- View Department Chats
- Moderate Discussions

## Restaurant Owner

- Reply to Customer Messages
- Manage Restaurant Chat

## STAD Staff

- Manage Club Chats
- Post Announcements

## Administrator

- Full Chat Access

---

# Notifications

Users receive notifications for:

- New Message
- Mention
- Reply
- File Shared
- Lecturer Announcement
- Pinned Message
- Chat Invitation

---

# Security

- Scan uploaded files.
- Validate uploaded file types.
- Validate file size.
- Prevent malicious uploads.
- Store files securely.
- Only room members may access messages.
- Chat history is protected.
- Deleted messages are logged.

---

# File Sharing

Supported file types

- PDF
- DOCX
- PPTX
- XLSX
- ZIP
- JPG
- PNG
- MP4
- MP3

---

# Indexes

ChatRoom

- course_id
- section_id

ChatMember

- room_id
- user_id

Message

- room_id
- sender_id
- sent_at

MessageAttachment

- message_id

---

# Reports

Chat Reports

- Active Chat Rooms
- Daily Messages
- User Activity
- File Sharing Report
- Moderation Report
- Chat Usage Statistics

---

# Performance

- Use Socket.IO for real-time communication.
- Cache active chat rooms.
- Optimize message retrieval.
- Load previous messages using pagination.
- Compress uploaded media.

---

# API Mapping

GET /api/chat/rooms

POST /api/chat/rooms

GET /api/chat/messages

POST /api/chat/messages

PUT /api/chat/messages/{id}

DELETE /api/chat/messages/{id}

POST /api/chat/upload

POST /api/chat/reactions

POST /api/chat/read

---

# UI Pages

- Chat Dashboard
- Course Chat
- Private Chat
- Group Chat
- Shared Files
- Chat Settings
- Message Search

---

# Dependencies

This module depends on:

- Authentication Module
- Academic Module
- Notification Module

The following modules depend on this module:

- Student Portal
- Lecturer Portal
- Restaurant Portal
- STAD Portal

---

# Future Expansion

- Voice Calls
- Video Calls
- Screen Sharing
- AI Message Translation
- AI Chat Assistant
- End-to-End Encryption
- Advanced Message Search

---

# Notes

The Chat Module integrates with the Academic Module, LMS Module, Student Portal, Lecturer Portal, Restaurant Portal, STAD Portal, and Notification Module.

Students are automatically enrolled into course chat rooms after successful course registration and removed when they drop the course.