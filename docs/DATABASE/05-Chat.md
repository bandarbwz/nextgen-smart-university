# Chat Module

## Purpose

The Chat Module provides secure real-time communication between students, lecturers, coordinators, administrators, STAD staff, and restaurant owners.

Each course automatically has its own chat room. Students are automatically added after successful course registration and removed after dropping the course.

---

# Objectives

- Real-time messaging.
- Course chat rooms.
- Private messaging.
- Group messaging.
- File sharing.
- Image sharing.
- Video sharing.
- Voice messages.
- Message notifications.

---

# Database Tables

## ChatRoom

Purpose

Stores chat rooms.

Columns

- id
- room_name
- room_type
- course_id
- section_id
- created_by
- created_at
- updated_at

Room Types

- Course
- Private
- Group
- Announcement

---

## ChatMember

Purpose

Stores room members.

Columns

- id
- room_id
- user_id
- joined_at
- role

Roles

- Owner
- Lecturer
- Student
- Moderator

---

## Message

Purpose

Stores chat messages.

Columns

- id
- room_id
- sender_id
- message_type
- message
- reply_to
- edited
- edited_at
- sent_at

Message Types

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

Columns

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

Columns

- id
- message_id
- user_id
- reaction

Examples

- Like
- Love
- Laugh
- Sad

---

## MessageRead

Purpose

Stores read receipts.

Columns

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

---

# File Sharing

Supported files

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

# Notifications

Users receive notifications for

- New Message
- Mention
- Reply
- File Shared
- Lecturer Announcement

---

# Security Rules

- Scan uploaded files.
- Validate file size.
- Validate file type.
- Prevent malicious uploads.
- Store files securely.

---

# API Mapping

Chat APIs

- Create Room
- Send Message
- Edit Message
- Delete Message
- Upload File
- Download File
- React to Message
- Mark as Read

---

# Future Expansion

Future improvements

- Video Calls
- Voice Calls
- Screen Sharing
- AI Message Translation
- AI Chat Assistant
- Message Search
- Message Encryption

---

# Notes

The Chat Module integrates with:

- Academic Module
- LMS Module
- Student Dashboard
- Lecturer Dashboard
- Notification Module