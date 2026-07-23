-- SAZUG Student Record Management System - Database Schema (MySQL 8)
-- Normalized to 3NF, Optimized for Performance

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- 1. USERS TABLE (RBAC Base)
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('Super Administrator', 'Administrator', 'Registrar', 'Department Officer', 'Lecturer', 'Student') NOT NULL,
    `status` ENUM('Active', 'Suspended', 'Locked') DEFAULT 'Active',
    `failed_attempts` INT DEFAULT 0,
    `locked_until` DATETIME NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. FACULTIES TABLE
CREATE TABLE IF NOT EXISTS `faculties` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `code` VARCHAR(10) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. DEPARTMENTS TABLE
CREATE TABLE IF NOT EXISTS `departments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `faculty_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `code` VARCHAR(10) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`faculty_id`) REFERENCES `faculties`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PROGRAMMES TABLE
CREATE TABLE IF NOT EXISTS `programmes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `department_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('Diploma', 'ND', 'HND', 'Degree', 'Masters') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `dept_prog` (`department_id`, `name`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. SESSIONS TABLE
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(20) NOT NULL UNIQUE,
    `semester` ENUM('First', 'Second', 'Third') NOT NULL,
    `is_active` BOOLEAN DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. STAFF TABLE
CREATE TABLE IF NOT EXISTS `staff` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. STUDENTS TABLE
CREATE TABLE IF NOT EXISTS `students` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `admission_number` VARCHAR(30) NOT NULL UNIQUE,
    `matric_number` VARCHAR(30) NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `dob` DATE NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `phone` VARCHAR(20) NULL,
    `address` TEXT NULL,
    `department_id` BIGINT UNSIGNED NOT NULL,
    `faculty_id` BIGINT UNSIGNED NOT NULL,
    `programme_id` BIGINT UNSIGNED NOT NULL,
    `level` INT NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('Active', 'Suspended', 'Withdrawn', 'Graduated') DEFAULT 'Active',
    `admission_date` DATE NOT NULL,
    `graduation_date` DATE NULL,
    `state` VARCHAR(50) NOT NULL,
    `lga` VARCHAR(100) NOT NULL,
    `nationality` VARCHAR(50) DEFAULT 'Nigerian',
    `guardian_name` VARCHAR(150) NULL,
    `guardian_phone` VARCHAR(20) NULL,
    `guardian_address` TEXT NULL,
    `passport_path` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
    FOREIGN KEY (`faculty_id`) REFERENCES `faculties`(`id`),
    FOREIGN KEY (`programme_id`) REFERENCES `programmes`(`id`),
    FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`),
    INDEX `idx_students_department` (`department_id`),
    INDEX `idx_students_programme` (`programme_id`),
    INDEX `idx_students_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. COURSES TABLE
CREATE TABLE IF NOT EXISTS `courses` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `department_id` BIGINT UNSIGNED NOT NULL,
    `programme_id` BIGINT UNSIGNED NOT NULL,
    `course_code` VARCHAR(20) NOT NULL UNIQUE,
    `title` VARCHAR(150) NOT NULL,
    `units` INT NOT NULL,
    `semester` ENUM('First', 'Second', 'Third') NOT NULL,
    `prerequisites` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`programme_id`) REFERENCES `programmes`(`id`) ON DELETE CASCADE,
    INDEX `idx_courses_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. COURSE LECTURER PIVOT
CREATE TABLE IF NOT EXISTS `course_lecturer` (
    `course_id` BIGINT UNSIGNED NOT NULL,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`course_id`, `staff_id`, `session_id`),
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. DOCUMENTS TABLE
CREATE TABLE IF NOT EXISTS `documents` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `type` ENUM('Passport', 'Certificate', 'Admission') NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. CERTIFICATES TABLE
CREATE TABLE IF NOT EXISTS `certificates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `certificate_number` VARCHAR(100) NOT NULL UNIQUE,
    `qr_code_path` VARCHAR(255) NOT NULL,
    `issue_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    INDEX `idx_cert_number` (`certificate_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. AUDIT LOGS TABLE
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(255) NOT NULL,
    `entity` VARCHAR(50) NULL,
    `entity_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_audit_logs_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SEED DATA (Idempotent using INSERT IGNORE)

-- Default Super Administrator (Password: Admin@123)
INSERT IGNORE INTO `users` (`id`, `username`, `password_hash`, `role`, `status`) VALUES
(1, 'superadmin', '$2y$10$wO2Z1FqP3yJ3bXzW3hF4euXgR/aD0k.fT0s1Vf4xG5z3E2v3F2f7i', 'Super Administrator', 'Active');

INSERT IGNORE INTO `staff` (`user_id`, `full_name`, `phone`) VALUES
(1, 'System Super Administrator', '08000000000');

-- Default Academic Session
INSERT IGNORE INTO `sessions` (`id`, `name`, `semester`, `is_active`) VALUES 
(1, '2026/2027', 'First', 1);

SET FOREIGN_KEY_CHECKS = 1;
