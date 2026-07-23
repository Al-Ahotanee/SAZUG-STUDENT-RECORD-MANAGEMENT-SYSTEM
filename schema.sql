-- ============================================================
-- SAZUG Student Record Management System
-- Database Schema v2.1 — MySQL 8 | Normalized to 3NF
-- Sa'adu Zungur University Gadau, Bauchi State, Nigeria
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- 1. USERS TABLE (RBAC Base)
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(150) NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('Super Administrator','Administrator','Registrar','Department Officer','Lecturer','Student') NOT NULL,
    `status` ENUM('Active','Suspended','Locked') DEFAULT 'Active',
    `failed_attempts` INT DEFAULT 0,
    `locked_until` DATETIME NULL,
    `remember_token` VARCHAR(100) NULL,
    `reset_token` VARCHAR(100) NULL,
    `reset_token_expires` DATETIME NULL,
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. FACULTIES TABLE
CREATE TABLE IF NOT EXISTS `faculties` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `code` VARCHAR(10) NOT NULL UNIQUE,
    `hod_name` VARCHAR(150) NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. DEPARTMENTS TABLE
CREATE TABLE IF NOT EXISTS `departments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `faculty_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `code` VARCHAR(10) NOT NULL UNIQUE,
    `hod_name` VARCHAR(150) NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`faculty_id`) REFERENCES `faculties`(`id`) ON DELETE CASCADE,
    INDEX `idx_dept_faculty` (`faculty_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PROGRAMMES TABLE
CREATE TABLE IF NOT EXISTS `programmes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `department_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('Undergraduate','Postgraduate','Masters','PhD') NOT NULL,
    `duration_years` INT DEFAULT 2,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `dept_prog` (`department_id`, `name`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. SESSIONS TABLE
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(20) NOT NULL UNIQUE,
    `semester` ENUM('First','Second','Third') NOT NULL,
    `is_active` BOOLEAN DEFAULT 0,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. STAFF TABLE
CREATE TABLE IF NOT EXISTS `staff` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `staff_id` VARCHAR(30) NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(20) NULL,
    `department_id` BIGINT UNSIGNED NULL,
    `qualification` VARCHAR(100) NULL,
    `specialization` VARCHAR(150) NULL,
    `gender` ENUM('Male','Female','Other') NULL,
    `address` TEXT NULL,
    `status` ENUM('Active','Inactive') DEFAULT 'Active',
    `date_joined` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. STUDENTS TABLE
CREATE TABLE IF NOT EXISTS `students` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `admission_number` VARCHAR(30) NOT NULL UNIQUE,
    `matric_number` VARCHAR(30) NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `dob` DATE NOT NULL,
    `gender` ENUM('Male','Female','Other') NOT NULL,
    `phone` VARCHAR(20) NULL,
    `address` TEXT NULL,
    `department_id` BIGINT UNSIGNED NOT NULL,
    `faculty_id` BIGINT UNSIGNED NOT NULL,
    `programme_id` BIGINT UNSIGNED NOT NULL,
    `level` INT NOT NULL DEFAULT 100,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('Active','Suspended','Withdrawn','Graduated') DEFAULT 'Active',
    `admission_date` DATE NOT NULL,
    `graduation_date` DATE NULL,
    `state` VARCHAR(50) NOT NULL DEFAULT 'Not Set',
    `lga` VARCHAR(100) NULL,
    `nationality` VARCHAR(50) DEFAULT 'Nigerian',
    `guardian_name` VARCHAR(150) NULL,
    `guardian_phone` VARCHAR(20) NULL,
    `guardian_address` TEXT NULL,
    `passport_path` VARCHAR(255) NULL,
    `religion` VARCHAR(50) NULL,
    `blood_group` VARCHAR(10) NULL,
    `marital_status` ENUM('Single','Married','Divorced','Widowed') NULL,
    `place_of_birth` VARCHAR(100) NULL,
    `home_town` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`faculty_id`) REFERENCES `faculties`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`programme_id`) REFERENCES `programmes`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE RESTRICT,
    INDEX `idx_students_status` (`status`),
    INDEX `idx_students_dept` (`department_id`),
    INDEX `idx_students_faculty` (`faculty_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. COURSES TABLE
CREATE TABLE IF NOT EXISTS `courses` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `programme_id` BIGINT UNSIGNED NOT NULL,
    `department_id` BIGINT UNSIGNED NOT NULL,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `title` VARCHAR(150) NOT NULL,
    `credit_units` INT NOT NULL DEFAULT 3,
    `level` INT NOT NULL DEFAULT 100,
    `semester` ENUM('First','Second') NOT NULL DEFAULT 'First',
    `is_compulsory` BOOLEAN DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`programme_id`) REFERENCES `programmes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. COURSE REGISTRATIONS TABLE
CREATE TABLE IF NOT EXISTS `course_registrations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `course_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `lecturer_id` BIGINT UNSIGNED NULL,
    `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `student_course_session` (`student_id`,`course_id`,`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. REGISTRATION REQUESTS TABLE
CREATE TABLE IF NOT EXISTS `registration_requests` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `request_type` ENUM('Student','Lecturer') NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `gender` ENUM('Male','Female','Other') NULL,
    `department_id` BIGINT UNSIGNED NULL,
    `faculty_id` BIGINT UNSIGNED NULL,
    `programme_id` BIGINT UNSIGNED NULL,
    `qualification` VARCHAR(100) NULL,
    `specialization` VARCHAR(150) NULL,
    `staff_id` VARCHAR(30) NULL,
    `additional_data` JSON NULL,
    `status` ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    `reviewed_by` BIGINT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `rejection_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`faculty_id`) REFERENCES `faculties`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`programme_id`) REFERENCES `programmes`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_reg_status` (`status`),
    INDEX `idx_reg_type` (`request_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. DOCUMENTS TABLE
CREATE TABLE IF NOT EXISTS `documents` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `type` ENUM('Passport','Certificate','Admission','Transcript','Other') NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. CERTIFICATES TABLE
CREATE TABLE IF NOT EXISTS `certificates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `certificate_number` VARCHAR(100) NOT NULL UNIQUE,
    `qr_code_path` VARCHAR(255) NULL,
    `issue_date` DATE NOT NULL,
    `issued_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`issued_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_cert_number` (`certificate_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. AUDIT LOGS TABLE
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(255) NOT NULL,
    `entity` VARCHAR(50) NULL,
    `entity_id` BIGINT UNSIGNED NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default Super Administrator (Password: Admin@123)
INSERT IGNORE INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 'superadmin', 'admin@sazug.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'Active');

INSERT IGNORE INTO `staff` (`user_id`, `full_name`, `phone`, `status`) VALUES
(1, 'System Super Administrator', '08000000000', 'Active');

-- Sample Faculties
INSERT IGNORE INTO `faculties` (`id`, `name`, `code`, `description`) VALUES
(1, 'Faculty of Engineering & Technology', 'FET', 'Engineering and applied technology programmes'),
(2, 'Faculty of Business & Management', 'FBM', 'Business, commerce, and management science'),
(3, 'Faculty of Science & Computing', 'FSC', 'Pure sciences and computing programmes'),
(4, 'Faculty of Environmental Studies', 'FES', 'Built environment and environmental management');

-- Sample Departments
INSERT IGNORE INTO `departments` (`id`, `faculty_id`, `name`, `code`) VALUES
(1, 1, 'Computer Engineering', 'CEN'),
(2, 1, 'Electrical/Electronics Engineering', 'EEE'),
(3, 1, 'Civil Engineering', 'CVE'),
(4, 2, 'Business Administration', 'BAD'),
(5, 2, 'Accountancy', 'ACC'),
(6, 3, 'Computer Science', 'CSC'),
(7, 3, 'Statistics & Mathematics', 'STA'),
(8, 4, 'Architecture', 'ARC'),
(9, 4, 'Estate Management', 'EST');

-- Sample Programmes (University Degrees — no ND/HND)
INSERT IGNORE INTO `programmes` (`id`, `department_id`, `name`, `type`, `duration_years`) VALUES
(1, 1, 'Computer Engineering', 'Undergraduate', 5),
(2, 2, 'Electrical/Electronics Engineering', 'Undergraduate', 5),
(3, 3, 'Civil Engineering', 'Undergraduate', 5),
(4, 4, 'Business Administration', 'Undergraduate', 4),
(5, 5, 'Accountancy', 'Undergraduate', 4),
(6, 6, 'Computer Science', 'Undergraduate', 4),
(7, 7, 'Statistics & Mathematics', 'Undergraduate', 4),
(8, 8, 'Architecture', 'Undergraduate', 5),
(9, 9, 'Estate Management', 'Undergraduate', 4),
(10, 6, 'Computer Science', 'Postgraduate', 2),
(11, 4, 'Business Administration', 'Masters', 2),
(12, 6, 'Computer Science', 'Masters', 2);

-- Academic Session
INSERT IGNORE INTO `sessions` (`id`, `name`, `semester`, `is_active`, `start_date`, `end_date`) VALUES
(1, '2024/2025', 'First', 0, '2024-09-01', '2025-01-31'),
(2, '2024/2025', 'Second', 0, '2025-02-01', '2025-06-30'),
(3, '2025/2026', 'First', 0, '2025-09-01', '2026-01-31'),
(4, '2026/2027', 'First', 1, '2026-09-01', '2027-01-31');

-- Sample Courses for Computer Science Undergraduate (programme_id=6, department_id=6)
-- Level 100 — First Semester
INSERT IGNORE INTO `courses` (`id`, `programme_id`, `department_id`, `code`, `title`, `credit_units`, `level`, `semester`, `is_compulsory`) VALUES
(1, 6, 6, 'CSC101', 'Introduction to Computer Science', 3, 100, 'First', 1),
(2, 6, 6, 'CSC102', 'Introduction to Programming (C)', 3, 100, 'First', 1),
(3, 6, 6, 'CSC103', 'Computer Hardware Fundamentals', 2, 100, 'First', 1),
(4, 6, 6, 'MAT101', 'General Mathematics I', 3, 100, 'First', 1),
(5, 6, 6, 'GST101', 'Use of English I', 2, 100, 'First', 1),
-- Level 100 — Second Semester
(6, 6, 6, 'CSC104', 'Introduction to Programming II (C++)', 3, 100, 'Second', 1),
(7, 6, 6, 'CSC105', 'Digital Logic Design', 3, 100, 'Second', 1),
(8, 6, 6, 'MAT102', 'General Mathematics II', 3, 100, 'Second', 1),
(9, 6, 6, 'GST102', 'Use of English II', 2, 100, 'Second', 1),
(10, 6, 6, 'PHY101', 'General Physics I', 3, 100, 'Second', 1),
-- Level 200 — First Semester
(11, 6, 6, 'CSC201', 'Data Structures & Algorithms', 3, 200, 'First', 1),
(12, 6, 6, 'CSC202', 'Object-Oriented Programming (Java)', 3, 200, 'First', 1),
(13, 6, 6, 'CSC203', 'Discrete Mathematics', 3, 200, 'First', 1),
(14, 6, 6, 'CSC204', 'Computer Architecture', 3, 200, 'First', 1),
(15, 6, 6, 'MAT201', 'Linear Algebra', 3, 200, 'First', 1),
-- Level 200 — Second Semester
(16, 6, 6, 'CSC205', 'Database Management Systems', 3, 200, 'Second', 1),
(17, 6, 6, 'CSC206', 'Operating Systems I', 3, 200, 'Second', 1),
(18, 6, 6, 'CSC207', 'Web Technologies (HTML/CSS/JS)', 3, 200, 'Second', 1),
(19, 6, 6, 'CSC208', 'Probability & Statistics', 3, 200, 'Second', 1),
(20, 6, 6, 'GST201', 'Nigerian Peoples & Culture', 2, 200, 'Second', 1),
-- Level 300 — First Semester
(21, 6, 6, 'CSC301', 'Software Engineering', 3, 300, 'First', 1),
(22, 6, 6, 'CSC302', 'Computer Networks', 3, 300, 'First', 1),
(23, 6, 6, 'CSC303', 'Design & Analysis of Algorithms', 3, 300, 'First', 1),
(24, 6, 6, 'CSC304', 'Theory of Computation', 3, 300, 'First', 1),
(25, 6, 6, 'MAT301', 'Numerical Methods', 3, 300, 'First', 1),
-- Level 300 — Second Semester
(26, 6, 6, 'CSC305', 'Operating Systems II', 3, 300, 'Second', 1),
(27, 6, 6, 'CSC306', 'Compiler Construction', 3, 300, 'Second', 1),
(28, 6, 6, 'CSC307', 'Artificial Intelligence', 3, 300, 'Second', 1),
(29, 6, 6, 'CSC308', 'Web Programming (PHP/MySQL)', 3, 300, 'Second', 1),
(30, 6, 6, 'CSC309', 'Technical Report Writing', 2, 300, 'Second', 1),
-- Level 400 — First Semester
(31, 6, 6, 'CSC401', 'Machine Learning', 3, 400, 'First', 1),
(32, 6, 6, 'CSC402', 'Information Security', 3, 400, 'First', 1),
(33, 6, 6, 'CSC403', 'Distributed Systems', 3, 400, 'First', 1),
(34, 6, 6, 'CSC404', 'Cloud Computing', 2, 400, 'First', 1),
(35, 6, 6, 'CSC405', 'Project Part I', 4, 400, 'First', 1),
-- Level 400 — Second Semester
(36, 6, 6, 'CSC406', 'Mobile Application Development', 3, 400, 'Second', 1),
(37, 6, 6, 'CSC407', 'Data Science & Big Data', 3, 400, 'Second', 1),
(38, 6, 6, 'CSC408', 'Entrepreneurship in IT', 2, 400, 'Second', 1),
(39, 6, 6, 'CSC409', 'Project Part II', 6, 400, 'Second', 1),
(40, 6, 6, 'CSC410', 'Seminar Presentation', 1, 400, 'Second', 1);

SET FOREIGN_KEY_CHECKS = 1;
