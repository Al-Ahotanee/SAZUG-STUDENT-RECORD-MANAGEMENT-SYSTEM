# SAZUG Student Record Management System (SRMS) v2.0

A production-ready, enterprise-grade Student Record Management System built for SAZUG (Zamfara State polytechnics) with PHP 8.3, MySQL 8, and a world-class multi-role SPA interface.

---

## 🚀 Features

- **Stunning Landing Page** — Hero section, animated carousel, feature grid, security badges, certificate verifier, CTA sections, and footer
- **Admin Dashboard** — World-class collapsible sidebar with complete CRUD for Students, Staff, Faculties, Departments, Programmes, Courses, Sessions, Users, Audit Logs
- **Student Portal** — Professional sidebar with academic profile, certificate viewer, document upload, password management
- **Lecturer Portal** — Sidebar dashboard with student roster, course catalogue, and profile
- **Role-Based Access Control** — 6 roles: Super Administrator, Administrator, Registrar, Department Officer, Lecturer, Student
- **Anti-Forgery Certificates** — QR code-backed graduation certificates with public verification portal
- **Full Audit Logging** — Every action logged with user, IP, timestamp
- **Enterprise Security** — CSRF protection, XSS prevention, bcrypt hashing, account lockout (5 attempts → 30-min lock), secure sessions

---

## 📁 File Structure (12 files)

```
SAZUG-SRMS/
├── index.php          # Landing page + login modal + certificate verification
├── admin_spa.php      # Admin/Registrar/Department Officer portal
├── student_spa.php    # Student self-service portal
├── lecturer_spa.php   # Lecturer portal
├── api.php            # Central AJAX API gateway
├── SystemCore.php     # Core business logic engine
├── schema.sql         # MySQL 8 database schema + seed data
├── install.php        # One-click database installer (delete after use)
├── composer.json      # PHP dependencies
├── Dockerfile         # Docker container config for Render
├── render.yaml        # Render.com deployment config
└── README.md          # This file
```

---

## 🛠️ Setup

### Prerequisites
- PHP 8.3+
- MySQL 8+
- Composer

### Local Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/SAZUG-SRMS.git
   cd SAZUG-SRMS
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Set environment variables** (or create a `.env` loader):
   ```bash
   export DB_HOST=localhost
   export DB_PORT=3306
   export DB_NAME=sazug_srms
   export DB_USER=root
   export DB_PASS=yourpassword
   export DB_SSL=false
   export SESSION_SECURE=false
   ```

4. **Create the database**
   ```sql
   CREATE DATABASE sazug_srms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. **Run the installer** — Navigate to `http://localhost/install.php` and click **"Wipe Database & Run Installer"**

6. **Delete install.php** after successful installation:
   ```bash
   rm install.php
   ```

7. **Visit the portal** — `http://localhost/index.php`

---

### Deploy on Render (Free Tier)

1. Push code to GitHub
2. Create a new **Web Service** on [render.com](https://render.com)
3. Select your repo, choose **Docker** environment
4. Add environment variables: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
5. Deploy — the Dockerfile handles everything automatically
6. Navigate to `https://your-service.onrender.com/install.php` to run the installer
7. **Delete install.php** from your GitHub repository after installation

---

## 🔑 Default Credentials

| Role | Username | Password |
|------|----------|----------|
| Super Administrator | `superadmin` | `Admin@123` |

> **Change the default password immediately after first login.**

---

## 👤 User Roles & Permissions

| Role | Dashboard | Students | Staff | Academics | Certificates | Settings |
|------|-----------|----------|-------|-----------|--------------|----------|
| Super Administrator | ✅ | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ Issue | ✅ |
| Administrator | ✅ | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ Issue | ✅ |
| Registrar | ✅ | ✅ CRUD | View | ✅ CRUD | ✅ Issue | ❌ |
| Department Officer | ✅ | View | ❌ | View | ❌ | ❌ |
| Lecturer | ❌ | View (roster) | ❌ | View courses | ❌ | ❌ |
| Student | ❌ | Own profile | ❌ | ❌ | View own | ❌ |

---

## 🔒 Security Features

- **CSRF Tokens** — Every POST request validated with a secure token
- **Password Hashing** — bcrypt via PHP `password_hash()`
- **Account Lockout** — 5 failed login attempts → 30-minute lock
- **Session Security** — HttpOnly, SameSite=Strict, 30-minute timeout, session regeneration on login
- **XSS Prevention** — All output escaped with `htmlspecialchars()`
- **SQL Injection Prevention** — 100% PDO prepared statements
- **File Upload Validation** — MIME type verification, 5MB limit, secure filenames
- **Audit Logging** — All critical actions logged with user ID, IP address, and timestamp
- **Security Headers** — X-Frame-Options, X-Content-Type-Options, X-XSS-Protection

---

## 🗄️ Database Schema

12 tables, normalized to 3NF:

- `users` — Authentication and RBAC
- `faculties` — Academic faculties
- `departments` — Faculty departments
- `programmes` — Academic programmes
- `sessions` — Academic year/semester sessions
- `staff` — Staff profiles linked to users
- `students` — Student records linked to users
- `courses` — Course catalogue
- `course_registrations` — Student-course enrollment
- `documents` — Uploaded student documents
- `certificates` — Graduation certificates with QR
- `audit_logs` — Complete system activity log

---

## 🏗️ Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.3, PDO |
| Database | MySQL 8 |
| Frontend | Bootstrap 5.3, Vanilla JS ES6, Chart.js, SweetAlert2 |
| UI Icons | Font Awesome 6.5 |
| Fonts | Inter (Google Fonts) |
| Charts | Chart.js 4.4 |
| Tables | HTML tables with built-in filter/search |
| Hosting | Render Free Tier (Docker) |

---

## 📜 License

MIT License — Free to use, modify, and distribute.

---

*Built with ❤️ for Nigerian higher education.*
