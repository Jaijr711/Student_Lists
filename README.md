# ISCC Enrollment & Attendance System

## Overview
A web-based system for Student Enrollment and Attendance monitoring for Ilocos Sur Community College (ISCC).

## Features
- **Registrar Portal:**
  - Manage Teacher Accounts
  - Manage Subjects and Classes
  - Register Students
  - Enroll Students in Classes
  - Generate Reports (Class Attendance, Student Summary)
- **Teacher Portal:**
  - View Assigned Schedule
  - View Class Roster
  - Mark Attendance (Present, Absent, Late)

## Setup
1.  **Database:**
    - The system supports MySQL and SQLite.
    - Default configuration in `db.php` tries to connect to MySQL (`localhost`, root, no password).
    - If MySQL fails, it falls back to SQLite (`iscc_system.db`).
    - Run `php init_db.php` to initialize the database schema.
    - Run `php seed.php` to create the default Registrar account.

2.  **Running:**
    - Serve the application using PHP's built-in server:
      ```bash
      php -S localhost:8000
      ```
    - Access via browser at `http://localhost:8000`.

## default Credentials
- **Registrar:**
  - Username: `admin`
  - Password: `admin123`

## Tech Stack
- PHP (Backend API)
- MySQL / SQLite (Database)
- HTML/CSS/JS (Frontend)
- Bootstrap 5 (UI Framework)
