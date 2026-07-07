# Branching Strategy

## Purpose

This document defines the branching strategy for the NextGen Smart University Platform.

The goal is to keep development organized, stable, and easy to manage.

---

# Branch Types

The project uses five branch types.

- main
- develop
- feature/*
- release/*
- hotfix/*

---

# Main Branch

Purpose

- Production-ready code only.
- Always stable.
- Protected branch.

Rules

- No direct commits.
- Only merge approved releases.

---

# Develop Branch

Purpose

- Integration branch.
- Contains completed features waiting for release.

Rules

- Feature branches merge into develop.
- Must remain stable.

---

# Feature Branches

Purpose

Develop one feature at a time.

Examples

feature/login

feature/student-dashboard

feature/course-registration

feature/attendance

feature/chat

feature/food-court

Rules

- One feature per branch.
- Delete branch after merge.

---

# Release Branch

Purpose

Prepare a production release.

Examples

release/v1.0.0

release/v1.1.0

Tasks

- Final testing
- Bug fixes
- Documentation review

---

# Hotfix Branch

Purpose

Fix critical production issues.

Examples

hotfix/login

hotfix/payment

hotfix/security

Rules

- Merge into main.
- Merge back into develop.

---

# Branch Naming Rules

Use lowercase.

Use hyphens instead of spaces.

Keep names short and descriptive.

Good

feature/student-profile

feature/course-registration

Bad

feature/NewFeature

feature/Test

feature/temp

---

# Merge Strategy

Feature

↓

Develop

↓

Release

↓

Main

---

# Final Rule

Every branch must have a clear purpose and a clean history.