CSE3101 - Project - Student Management System (SMS) - XAMPP Setup & Run Guide
========================================================

This document explains how to set up and run our
groups Student Management System (SMS) application
using XAMPP (Apache + MySQL) on Windows.

--------------------------------------------------------
1) Requirements
--------------------------------------------------------
- XAMPP (Apache + MySQL) installed (Windows)
- A web browser (Chrome/Edge)
- (Optional) Postman for API testing
- Project folder (this repository / zip)

--------------------------------------------------------
2) Project Folder Placement
--------------------------------------------------------
1. Open your XAMPP installation folder:
   C:\xampp\htdocs\

2. Copy the project folder into htdocs.
   Example:
   C:\xampp\htdocs\PHP-Student-Management-System\

After copying, you should have paths similar to:
- C:\xampp\htdocs\PHP-Student-Management-System\sms\
- C:\xampp\htdocs\PHP-Student-Management-System\sms-api\

Notes:
- “sms” is the frontend (pages).
- “sms-api” is the backend API (routes/controllers/models).

--------------------------------------------------------
3) Start XAMPP Services
--------------------------------------------------------
1. Open XAMPP Control Panel.
2. Start:
   - Apache
   - MySQL

Make sure both show “Running”.

--------------------------------------------------------
4) Create the Database (phpMyAdmin)
--------------------------------------------------------
1. Open phpMyAdmin:
   http://localhost/phpmyadmin

2. Create a new database:
   - Database name: sms_db

3. Import the SQL schema + seed data:
   - Find the file named database.sql in the project folder
   - In phpMyAdmin:
     a) Click the sms_db database
     b) Click the “Import” tab
     c) Choose the database.sql file
     d) Click “Go”

Expected seeded data:
- Grades: 1–6
- Classes: 1A–1D, 2A–2D, ... 6A–6D
- Subjects: Math, Grammar, Science, Social Studies
- Users:
  - admin@admin.com (admin)
  - teacher@teacher.com (teacher assigned to class 1A)
- Students:
  - 30 students in class 1A
- Scores:
  - Random padding term scores for subjects across the three terms

--------------------------------------------------------
5) Configure Database Connection
--------------------------------------------------------
The API uses PDO and reads its connection settings in:
  sms-api/config/db.php

Default settings used:
- host: localhost
- database: sms_db
- user: root
- password: (empty)

--------------------------------------------------------
6) Ensure Correct Base Paths / URLs
--------------------------------------------------------
All API routes are under:
  /PHP-Student-Management-System/sms-api/

Examples:
- Health Check (confirm healthy API):
  http://localhost/PHP-Student-Management-System/sms-api/health
- Login:
  http://localhost/PHP-Student-Management-System/sms-api/auth/login

Our frontend pages are under:
  /PHP-Student-Management-System/sms/

Example:
  http://localhost/PHP-Student-Management-System/sms/

--------------------------------------------------------
7) Run the Application
--------------------------------------------------------
1. Open the frontend in your browser:
   http://localhost/PHP-Student-Management-System/sms/

2. Log in using one of the seeded accounts:

Admin login:
- Email: admin@admin.com
- Password: iamtheadmin123#

Teacher login:
- Email: teacher@teacher.com
- Password: iamtheteacher123#

Role behavior:
- Admin: can view grades, classes, reports for any class/student
- Teacher: restricted to assigned class (e.g., 1A) and its students

--------------------------------------------------------
8) Our available API Routes (Quick Reference)
--------------------------------------------------------
Public (no login required):
- GET  /health
- POST /auth/login
- POST /auth/logout
- GET  /auth/me

Protected (login required):
Admin only:
- GET /grades
- GET /get-classes?grade_id={id}
- GET /class-report?class_id={id}&year={yyyy optional}
- GET /student-report?student_id={id}&year={yyyy optional}

Teacher only:
- GET /teacher/class-report?year={yyyy optional}
- GET /teacher/student-report?student_id={id}&year={yyyy optional}

Year default rule:
- If year is omitted, API uses the most recent scoring year available (2025).

--------------------------------------------------------
9) Notes (TLDR)
--------------------------------------------------------
- Backend code: sms-api/
  - controllers/, models/, helpers/, config/
- Frontend code: sms/
  - pages, JS fetch calls, UI

Recommended workflow:
1) Confirm /health works.
2) Login via frontend or Postman.
3) Test /auth/me to confirm session.

[ END ]
