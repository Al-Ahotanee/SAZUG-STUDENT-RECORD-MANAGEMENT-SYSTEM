-- SAZUG Student Record Management System - Database Schema
-- Compatible with MySQL 5.7+ and MariaDB 10.3+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table: settings
-- ----------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `institution_name` VARCHAR(255) NOT NULL DEFAULT 'SAZUG University',
  `institution_address` TEXT,
  `institution_email` VARCHAR(255),
  `institution_phone` VARCHAR(50),
  `institution_website` VARCHAR(255),
  `institution_logo` VARCHAR(500),
  `institution_motto` VARCHAR(255),
  `academic_year_format` VARCHAR(50) DEFAULT '%Y/%Y',
  `currency_symbol` VARCHAR(10) DEFAULT '₦',
  `enable_certificate` TINYINT(1) DEFAULT 1,
  `enable_document_upload` TINYINT(1) DEFAULT 1,
  `max_upload_size_mb` INT DEFAULT 5,
  `allowed_file_types` VARCHAR(500) DEFAULT 'pdf,jpg,jpeg,png,doc,docx',
  `session_prefix` VARCHAR(20) DEFAULT 'SAZUG',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: users
-- ----------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `role` ENUM('superadmin','admin','lecturer','student') NOT NULL DEFAULT 'student',
  `phone` VARCHAR(50),
  `avatar` VARCHAR(500),
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` DATETIME,
  `password_reset_token` VARCHAR(255),
  `password_reset_expires` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: faculties
-- ----------------------------
CREATE TABLE IF NOT EXISTS `faculties` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `dean_name` VARCHAR(255),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_faculties_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: departments
-- ----------------------------
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `head_name` VARCHAR(255),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`faculty_id`) REFERENCES `faculties`(`id`) ON DELETE CASCADE,
  INDEX `idx_depts_faculty` (`faculty_id`),
  INDEX `idx_depts_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: programmes
-- ----------------------------
CREATE TABLE IF NOT EXISTS `programmes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `department_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `type` ENUM('Diploma','ND','HND','Degree','Masters') NOT NULL,
  `duration_years` INT DEFAULT 4,
  `description` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
  INDEX `idx_prog_dept` (`department_id`),
  INDEX `idx_prog_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: courses
-- ----------------------------
CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `department_id` INT NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `credit_units` INT DEFAULT 2,
  `semester` ENUM('First','Second','Both') DEFAULT 'First',
  `level` INT DEFAULT 100,
  `is_elective` TINYINT(1) DEFAULT 0,
  `prerequisite_id` INT,
  `lecturer_id` INT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`prerequisite_id`) REFERENCES `courses`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`lecturer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_courses_dept` (`department_id`),
  INDEX `idx_courses_code` (`code`),
  INDEX `idx_courses_lecturer` (`lecturer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: sessions
-- ----------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `start_date` DATE,
  `end_date` DATE,
  `is_current` TINYINT(1) DEFAULT 0,
  `semester` ENUM('First','Second') DEFAULT 'First',
  `status` ENUM('upcoming','active','completed') DEFAULT 'upcoming',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: students
-- ----------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `student_id_number` VARCHAR(50) NOT NULL UNIQUE,
  `faculty_id` INT,
  `department_id` INT,
  `programme_id` INT,
  `session_id` INT,
  `level` INT DEFAULT 100,
  `programme_type` ENUM('Diploma','ND','HND','Degree','Masters') DEFAULT 'Degree',
  `date_of_birth` DATE,
  `gender` ENUM('Male','Female') NOT NULL,
  `nationality` VARCHAR(100) DEFAULT 'Nigerian',
  `state_of_origin` VARCHAR(100),
  `lga` VARCHAR(100),
  `home_address` TEXT,
  `guardian_name` VARCHAR(255),
  `guardian_phone` VARCHAR(50),
  `enrollment_date` DATE,
  `expected_graduation` DATE,
  `status` ENUM('active','suspended','withdrawn','graduated','expelled') DEFAULT 'active',
  `cgpa` DECIMAL(5,2) DEFAULT 0.00,
  `certificate_number` VARCHAR(100),
  `certificate_issued_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`faculty_id`) REFERENCES `faculties`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`programme_id`) REFERENCES `programmes`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE SET NULL,
  INDEX `idx_students_faculty` (`faculty_id`),
  INDEX `idx_students_dept` (`department_id`),
  INDEX `idx_students_prog` (`programme_id`),
  INDEX `idx_students_status` (`status`),
  INDEX `idx_students_number` (`student_id_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: student_documents
-- ----------------------------
CREATE TABLE IF NOT EXISTS `student_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `document_type` VARCHAR(100) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` INT DEFAULT 0,
  `mime_type` VARCHAR(100),
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  INDEX `idx_docs_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: audit_log
-- ----------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `user_name` VARCHAR(255),
  `action` VARCHAR(255) NOT NULL,
  `details` TEXT,
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_user` (`user_id`),
  INDEX `idx_audit_action` (`action`),
  INDEX `idx_audit_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Default data
-- ----------------------------
INSERT INTO `settings` (`institution_name`, `institution_motto`, `institution_email`) VALUES
('SAZUG University', 'Excellence in Education', 'info@sazug.edu.ng');

SET FOREIGN_KEY_CHECKS = 1;
