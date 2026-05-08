# 🎓 EduPro: Laravel LMS & Auto-Graded Quiz System

A robust, server-side rendered **Learning Management System** built with Laravel. This platform automates the entire learning lifecycle—from course enrollment to automated assessment and final certification.

---

## 🌟 Key Features

### 🧠 Intelligent Quiz Engine
* **Auto-Checker:** Instant evaluation of student submissions using backend logic to compare answers against the database.
* **Smart Scoring:** Automated calculation of percentages and pass/fail statuses.
* **Attempt Limits:** Configurable settings to control how many times a student can retake a quiz.

### 📜 Certification System
* **Automatic Completion Certificates:** Upon passing all required quizzes, the system dynamically generates a completion certificate.
* **Unique Validation:** Each certificate is tied to the user's record and completion date.

### 📁 Course Management (LMS)
* **Blade-Powered UI:** A fast, responsive frontend using Laravel Blade templates.
* **Progress Tracking:** Visual indicators showing how much of the course a student has completed.
* **Admin Dashboard:** Full CRUD functionality for courses, lessons, and quiz questions.

---

## 🛠️ Tech Stack

* **Framework:** [Laravel 11.x](https://laravel.com)
* **Frontend:** Blade Templating Engine & [CSS/Bootstrap]
* **Database:** MySQL 
* **PDF Generation:** [Dom Pdf] (used for Certificates)
* **Authentication:** Jason Web Token JWT
