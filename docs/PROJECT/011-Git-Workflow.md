# Git Workflow

## Purpose

This document defines the Git workflow for the NextGen Smart University Platform.

Every developer and AI assistant must follow these rules.

---

# Branches

The project uses the following branches:

- main
- develop
- feature/*
- hotfix/*
- release/*

---

# Main Branch

The main branch contains only stable and tested code.

Direct commits to the main branch are not allowed.

---

# Develop Branch

The develop branch contains the latest completed features.

All feature branches should be merged into develop first.

---

# Feature Branch

Each new feature must have its own branch.

Examples:

feature/authentication

feature/student-dashboard

feature/course-registration

feature/chat-system

---

# Hotfix Branch

Critical production bugs should be fixed using hotfix branches.

Example:

hotfix/login-error

---

# Release Branch

Release branches are created before production deployment.

Example:

release/v1.0.0

---

# Commit Rules

Every commit should represent one logical change.

Examples

Good

- Add login page
- Add student dashboard
- Fix attendance bug
- Update database schema

Bad

- Update files
- Fix stuff
- Changes

---

# Commit Frequency

Commit after:

- Completing one feature
- Fixing one bug
- Updating documentation

Avoid very large commits.

---

# Pull Requests

Every completed feature should be reviewed before merging.

Pull Requests should include:

- Description
- Changed files
- Testing notes

---

# Merge Rules

Merge only after:

- Code review
- Successful testing
- Documentation update

---

# Final Rule

Keep the Git history clean, organized, and easy to understand.