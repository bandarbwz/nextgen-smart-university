# Git Workflow

## Purpose

This document defines the official Git workflow for the NextGen Smart University Platform (NSUP).

The objective is to maintain a clean, organized, and traceable version history throughout the development lifecycle.

All developers and AI assistants must follow this workflow.

---

# Workflow Overview

The project follows a simplified Git workflow suitable for both individual and team development.

Main workflow:

Feature Development

↓

Testing

↓

Commit

↓

Push

↓

Review

↓

Merge

↓

Release

---

# Branch Strategy

The project uses the following branch types:

- main
- develop
- feature/*
- release/*
- hotfix/*

---

# Main Branch

Purpose

- Contains stable production-ready code.
- Represents the latest approved release.
- Should always remain deployable.

Rules

- Avoid direct commits during team development.
- For this academic project, direct commits are allowed when working individually.
- Only completed and tested features should exist on this branch.

---

# Develop Branch

Purpose

- Integration branch for completed features.
- Used before creating production releases.

Rules

- Merge feature branches into develop.
- Keep develop stable.
- Fix integration issues before release.

---

# Feature Branches

Purpose

Develop one feature independently.

Naming Convention

feature/<feature-name>

Examples

feature/authentication

feature/student-dashboard

feature/course-registration

feature/attendance

feature/chat

feature/food-court

Rules

- One feature per branch.
- Keep branches focused.
- Delete the branch after merging.

---

# Release Branch

Purpose

Prepare a stable production release.

Naming Convention

release/v1.0.0

release/v1.1.0

Tasks

- Final testing
- Documentation review
- Bug fixing
- Version verification

---

# Hotfix Branch

Purpose

Fix critical production issues.

Naming Convention

hotfix/login

hotfix/payment

hotfix/security

Rules

- Fix only critical bugs.
- Merge into main.
- Merge back into develop.

---

# Commit Rules

Every commit should represent one logical change.

Good Examples

- Add authentication module
- Implement QR attendance
- Add course registration validation
- Fix payment calculation
- Update API documentation

Bad Examples

- Update files
- Fix stuff
- Changes
- Work
- Test

---

# Commit Frequency

Create commits after:

- Completing one feature
- Fixing one bug
- Updating documentation
- Completing database changes
- Finishing API endpoints

Avoid large commits containing unrelated changes.

---

# Commit Message Format

Use imperative, concise, and descriptive messages.

Examples

- Add student dashboard
- Create attendance API
- Fix login validation
- Refactor finance service
- Update business rules

---

# Pull Requests

When working in a team, every completed feature should be reviewed before merging.

A Pull Request should include:

- Summary
- Changed Files
- Testing Notes
- Related Issue (if applicable)

---

# Merge Rules

Merge only after:

- Code Review
- Successful Testing
- Documentation Update
- Approval

Never merge incomplete features.

---

# Version Tags

Create Git tags for every official release.

Examples

v1.0.0

v1.1.0

v2.0.0

---

# Best Practices

- Commit frequently.
- Keep commits small.
- Keep commit messages meaningful.
- Push regularly.
- Never commit passwords or secrets.
- Never commit generated files unnecessarily.
- Keep Git history clean.

---

# Final Rule

Git history is part of the project documentation.

Every commit should clearly describe the work completed and maintain a clean, organized development history.