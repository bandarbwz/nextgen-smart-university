# Branching Strategy

## Purpose

This document defines the official branching strategy for the NextGen Smart University Platform (NSUP).

It explains how Git branches are organized throughout the project lifecycle to ensure a stable, maintainable, and well-structured development process.

This document complements the Git Workflow documentation.

---

# Objectives

The branching strategy aims to:

- Keep the main branch stable.
- Isolate new features.
- Simplify collaboration.
- Reduce merge conflicts.
- Support safe releases.
- Enable quick hotfixes.

---

# Branch Types

The project uses the following branch types:

- main
- develop
- feature/*
- release/*
- hotfix/*

Each branch has a specific responsibility.

---

# Main Branch

Purpose

- Stores production-ready code.
- Represents the latest stable version.
- Should always be deployable.

Rules

- Protect the branch whenever possible.
- Only merge tested and approved releases.
- Avoid experimental development.

---

# Develop Branch

Purpose

- Integration branch for completed features.
- Serves as the preparation branch for future releases.

Rules

- Merge completed feature branches here.
- Resolve integration issues before creating a release.
- Keep the branch stable.

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

feature/finance

Rules

- One branch for one feature.
- Keep changes focused.
- Delete the branch after merging.

---

# Release Branches

Purpose

Prepare a stable software release.

Naming Convention

release/v1.0.0

release/v1.1.0

Typical Activities

- Final testing
- Bug fixes
- Documentation review
- Version verification

After approval, merge into:

- main
- develop

---

# Hotfix Branches

Purpose

Fix critical production issues.

Naming Convention

hotfix/<issue-name>

Examples

hotfix/login

hotfix/payment

hotfix/security

Rules

- Only critical issues.
- Merge into main.
- Merge back into develop.

---

# Branch Naming Rules

Use:

- Lowercase letters
- Hyphens instead of spaces
- Short descriptive names

Good Examples

feature/student-profile

feature/course-registration

feature/payment-history

feature/attendance-report

Bad Examples

feature/Test

feature/NewFeature

feature/temp

feature/abc

---

# Branch Lifecycle

Feature Branch

↓

Develop Branch

↓

Release Branch

↓

Main Branch

If a production issue occurs:

Main Branch

↓

Hotfix Branch

↓

Main + Develop

---

# Merge Strategy

Before merging any branch:

- Code Review Completed
- Documentation Updated
- Tests Passed
- No Merge Conflicts
- Business Rules Verified

---

# Branch Protection

The following branches should be protected whenever possible:

- main
- develop

Only approved changes should be merged into these branches.

---

# Best Practices

- Keep branches short-lived.
- Sync regularly with develop.
- Resolve conflicts early.
- Delete merged branches.
- Avoid unrelated changes within the same branch.

---

# Final Rule

Every branch must have a clear purpose.

A clean branching strategy simplifies development, testing, maintenance, and future project expansion.