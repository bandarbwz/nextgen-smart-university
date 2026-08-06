# NextGen Smart University Platform

> A modern, integrated university management system that digitalizes academic, administrative, financial, and campus services through a centralized platform.

![React](https://img.shields.io/badge/React-Frontend-61DAFB?logo=react)
![PHP](https://img.shields.io/badge/PHP-Backend-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?logo=mysql)
![Vite](https://img.shields.io/badge/Vite-Build-646CFF?logo=vite)
![License](https://img.shields.io/badge/License-Educational-red)

---

## Overview

The **NextGen Smart University Platform** is a full-stack university management system designed to replace fragmented manual processes with a centralized digital solution.

The platform integrates academic management, finance, attendance, learning management, student services, reporting, and campus activities into one secure system.

---

## Features

### Authentication & Security

- Secure Login System
- Role-Based Access Control (RBAC)
- Session Management
- Password Encryption
- Authorization Middleware

### Student Portal

Students can:

- Register courses
- View timetable
- Check attendance
- View grades
- Download transcripts
- Pay tuition fees
- Join university clubs
- Track activity points

### Lecturer Portal

Lecturers can:

- Manage courses
- Upload learning materials
- Record attendance
- Enter assessment marks
- View teaching schedules

### Coordinator Portal

Coordinators can:

- Approve registrations
- Approve grades
- Manage academic rules
- Authorize repeat examinations

### Administrator Portal

Administrators manage:

- Students
- Lecturers
- Faculties
- Departments
- Courses
- Sections
- User Accounts
- System Configuration

### STAD Module

- Club Management
- Event Management
- Student Activities
- Activity Points

### Food Court Module

- Vendor Management
- Food Menu
- Campus Orders

### Reporting System

Generate reports including:

- Student Reports
- Attendance Reports
- GPA Reports
- Financial Reports
- Course Reports

Export reports to:

- PDF
- CSV
- Excel

---

## System Architecture

The project follows a layered architecture.

```
Frontend (React + Vite)
        │
     REST API
        │
 Backend (PHP MVC)
        │
 Business Logic
        │
 MySQL Database
```

---

## Technology Stack

### Frontend

- React
- Vite
- JavaScript
- HTML5
- CSS3

### Backend

- PHP (MVC)

### Database

- MySQL

### Development Tools

- Git
- GitHub
- Visual Studio Code

---

## Project Structure

```text
nextgen-smart-university/

├── frontend/
│   ├── src/
│   ├── public/
│   └── package.json
│
├── backend/
│   ├── app/
│   ├── controllers/
│   ├── models/
│   ├── services/
│   └── routes/
│
├── database/
├── docs/
├── README.md
└── LICENSE
```

---

## Installation

### Clone the Repository

```bash
git clone https://github.com/yourusername/nextgen-smart-university.git
cd nextgen-smart-university
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

### Backend

1. Configure your PHP server.
2. Import the MySQL database.
3. Update the database configuration.
4. Start the backend server.

---

## User Roles

| Role | Responsibility |
|------|----------------|
| Student | Academic services |
| Lecturer | Teaching and assessment |
| Coordinator | Academic approvals |
| Administrator | System administration |
| STAD Staff | Clubs and activities |
| Food Court Vendor | Food management |

---

## Core Modules

- Authentication
- Student Management
- Lecturer Management
- Academic Management
- Attendance System
- Learning Management System
- Finance
- Reporting
- STAD Management
- Food Court Management
- Notifications

---

## Security

- Role-Based Access Control
- Authentication Middleware
- Password Encryption
- Session Validation
- Protected Routes
- Input Validation

---

## Future Enhancements

- Mobile Application
- AI Academic Advisor
- AI Proctoring
- Push Notifications
- Online Payment Gateway
- QR Attendance
- Real-Time Chat
- Analytics Dashboard

---

## Academic Information

Developed for the **System Analysis and Design** course at **City University**.

---

## Development Team

- Bandar Khaled Salem
- Ali Sharif Abdulkadir Sharif
- Ahmed Mohammed Fadul Mohammed
- Ali Yousef Jalal Abdo
- Ali Ali Isak

---

## License

This project is intended for educational purposes.

---

## Acknowledgements

Special thanks to the course instructor and all team members for their contributions to the development of this project.
