-- ============================================================
-- SAZUG Student Record Management System
-- Database Schema v2.0 — MySQL 8 | Normalized to 3NF
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
    `type` ENUM('Diploma','ND','HND','Degree','Masters','PhD') NOT NULL,
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

-- 10. DOCUMENTS TABLE
CREATE TABLE IF NOT EXISTS `documents` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `type` ENUM('Passport','Certificate','Admission','Transcript','Other') NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. CERTIFICATES TABLE
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

-- 12. AUDIT LOGS TABLE
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

-- Sample Programmes
INSERT IGNORE INTO `programmes` (`id`, `department_id`, `name`, `type`, `duration_years`) VALUES
(1, 1, 'Computer Engineering', 'HND', 2),
(2, 1, 'Computer Engineering', 'ND', 2),
(3, 2, 'Electrical/Electronics Engineering', 'HND', 2),
(4, 2, 'Electrical/Electronics Engineering', 'ND', 2),
(5, 4, 'Business Administration & Management', 'HND', 2),
(6, 4, 'Business Administration & Management', 'ND', 2),
(7, 5, 'Accountancy', 'HND', 2),
(8, 5, 'Accountancy', 'ND', 2),
(9, 6, 'Computer Science', 'Degree', 4),
(10, 3, 'Civil Engineering', 'ND', 2);

-- Academic Session
INSERT IGNORE INTO `sessions` (`id`, `name`, `semester`, `is_active`, `start_date`, `end_date`) VALUES
(1, '2024/2025', 'First', 0, '2024-09-01', '2025-01-31'),
(2, '2024/2025', 'Second', 0, '2025-02-01', '2025-06-30'),
(3, '2025/2026', 'First', 0, '2025-09-01', '2026-01-31'),
(4, '2026/2027', 'First', 1, '2026-09-01', '2027-01-31');

SET FOREIGN_KEY_CHECKS = 1;
