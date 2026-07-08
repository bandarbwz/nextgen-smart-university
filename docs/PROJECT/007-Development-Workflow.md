# Development Workflow

## Purpose

This document defines the official development workflow for the NextGen Smart University Platform (NSUP).

Every developer and AI assistant must follow this workflow to ensure consistent development, maintainable code, and high software quality.

---

# Development Lifecycle

The project is developed in the following phases:

1. Documentation
2. Database Design
3. Backend Development
4. Frontend Development
5. AI Development
6. System Integration
7. Testing
8. Deployment

Each phase must be completed before moving to the next.

---

# Phase 1 – Documentation

Before writing any code:

- Read README.md
- Read AI Development Instructions
- Read Project Rules
- Read Business Rules
- Read Feature Documentation
- Read Database Documentation
- Read API Documentation

Understand the complete feature before implementation.

No code should be written until the required documentation is complete.

---

# Phase 2 – Database Design

Database development begins before backend implementation.

Tasks include:

- Design the database schema.
- Create tables.
- Define primary keys.
- Define foreign keys.
- Create indexes.
- Verify relationships.
- Normalize tables.

Review and approve the database before continuing.

---

# Phase 3 – Backend Development

Develop one feature at a time.

Each backend feature should include:

- Models
- Controllers
- Services
- REST API Endpoints
- Validation
- Authentication
- Authorization
- Error Handling

Every API endpoint must be tested before moving to the next feature.

---

# Phase 4 – Frontend Development

Frontend development begins after backend APIs are available.

Each page should include:

- Responsive Design
- Form Validation
- Loading Indicators
- Success Messages
- Error Messages
- API Integration

React communicates only with REST APIs.

React must never communicate directly with the database.

---

# Phase 5 – AI Development

Develop AI features after backend integration.

Tasks include:

- Identity Verification
- Face Detection
- Eye Tracking
- Head Pose Detection
- Browser Tab Detection
- Fullscreen Detection
- AI Examination Report Generation

The AI service communicates only with the PHP backend using secure REST APIs.

---

# Phase 6 – System Integration

Connect all project modules.

Verify:

- Authentication
- API Communication
- Database Operations
- File Upload
- AI Communication
- Notification Delivery
- Report Generation

Resolve integration issues before testing.

---

# Phase 7 – Testing

Every completed feature must be tested.

Testing includes:

- Functional Testing
- Integration Testing
- Validation Testing
- Security Testing
- Performance Testing
- User Interface Testing

Critical issues must be resolved before deployment.

---

# Phase 8 – Deployment

Deployment tasks include:

- Configure Apache
- Configure PHP
- Configure MySQL
- Configure AI Service
- Configure HTTPS
- Restore Initial Database
- Verify System Functionality

Deployment is complete only after all modules have been verified.

---

# AI Development Workflow

Before generating any code:

1. Read documentation.
2. Analyze requirements.
3. Identify dependencies.
4. Generate implementation.
5. Review generated code.
6. Fix detected issues.
7. Wait for approval before continuing.

Never skip any step.

---

# Git Workflow

For every completed task:

```bash
git add .
git commit -m "Meaningful commit message"
git push
```

Commit only completed and tested work.

Never commit unfinished features.

---

# Code Review Checklist

Before completing any feature, verify:

- Documentation followed
- Business rules implemented
- Validation completed
- Security verified
- Error handling completed
- Database updated correctly
- APIs tested
- Responsive UI completed
- Code reviewed

---

# Final Rule

Develop one feature at a time.

Complete, test, review, and obtain approval before starting the next feature.