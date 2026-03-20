-- Optimized Attendance System Database Schema v2.0
-- Designed for 4 Dashboards: Admin/Teacher/Education/Student
-- High Performance: 1000+ concurrent users, biometric scale
-- Generated: BLACKBOXAI - Matches Laravel migrations + frontend services

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Core Tables
CREATE DATABASE IF NOT EXISTS `attendance_system_v2` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `attendance_system_v2`;

-- Roles
CREATE TABLE `roles` (
  `id` bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(50) UNIQUE NOT NULL,
  `slug` varchar(50) UNIQUE NOT NULL, 
  `description` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`name`, `slug`, `description`) VALUES
('Admin', 'admin', 'Full system access'),
('Teacher', 'teacher', 'Class/attendance management'),
('Education Team', 'education_team', 'Attendance reports/follow-ups'),
('Training Team', 'training_team', 'Training attendance tracking'),
('Student', 'student', 'Self attendance check-in');

-- Users
CREATE TABLE `users` (
  `id` bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) UNIQUE NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `theme` enum('light','dark') DEFAULT 'light',
  `notification_email` boolean DEFAULT true,
  `notification_push` boolean DEFAULT true,
  `password` varchar(255) NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL,
  `is_active` boolean DEFAULT true,
  `student_id` bigint UNSIGNED NULL, -- Link to students for student users
  `calendar_id` varchar(255) DEFAULT NULL, -- Google Calendar integration
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role_active` (role_id, is_active),
  INDEX `idx_users_email` (email),
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academic Years
CREATE TABLE `academic_years` (
  `id` bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(50) UNIQUE NOT NULL,
  `start_year` year NOT NULL,
  `end_year` year NOT NULL, 
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `current_term` enum('Term1','Term2','Term3','Term4') DEFAULT 'Term1',
  `status` enum('Current','Closed','Archived') DEFAULT 'Current',
  `is_active` boolean DEFAULT false,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_academic_years_active` (is_active, status),
  INDEX `idx_academic_years_dates` (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `academic_years` (`name`, `start_year`, `end_year`, `start_date`, `end_date`, `is_active`) VALUES
('2025-2026', 2025, 2026, '2025-09-01', '2026-06-30', true);

-- Classes
CREATE TABLE `classes` (
  `id` bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) UNIQUE NOT NULL,
  `class_name` varchar(100) DEFAULT NULL,
  `academic_year_id` bigint UNSIGNED NOT NULL,
  `teacher_id` bigint UNSIGNED NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` boolean DEFAULT true,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_classes_code_active` (code, is_active),
  INDEX `idx_classes_year` (academic_year_id),
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students
CREATE TABLE `students` (
  `id` bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_code` varchar(20) UNIQUE NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `fullname` varchar(100) GENERATED ALWAYS AS (CONCAT(first_name,' ',last_name)) STORED,
  `email` varchar(100) UNIQUE DEFAULT NULL,
  `username` varchar(50) UNIQUE DEFAULT NULL,
  `generation` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `class_id` bigint UNSIGNED NOT NULL,
  `academic_year_id` bigint UNSIGNED DEFAULT NULL,
  `profile` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `parent_number` varchar(20) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `is_active` boolean DEFAULT true,
  `card_id` varchar(50) UNIQUE DEFAULT NULL,
  `fingerprint_enrolled` boolean DEFAULT false,
  `last_biometric_scan` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL, -- For student login
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_students_code_active` (student_code, is_active),
  INDEX `idx_students_class_active` (class_id, is_active),
  INDEX `idx_students_card_id` (card_id),
  INDEX `idx_students_biometric` (fingerprint_enrolled, last_biometric_scan),
  INDEX `idx_students_generation` (generation),
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions
CREATE TABLE `sessions` (
  `id` bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `order` smallint UNSIGNED NOT NULL DEFAULT 1,
  `late_threshold` smallint UNSIGNED NOT NULL DEFAULT 15,
  `is_active` boolean DEFAULT true,
  `description` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_session_order` (order),
  INDEX `idx_sessions_time_active` (start_time, end_time, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sessions` VALUES (1,'Morning Session','07:30:00','09:00:00',1,15,1,NULL,NOW(),NOW());
INSERT INTO `sessions` VALUES (2,'Mid Morning','10:00:00','11:30:00',2,15,1,NULL,NOW(),NOW());
INSERT INTO `sessions` VALUES (3,'Afternoon','13:30:00','15:00:00',3,15,1,NULL,NOW(),NOW());
INSERT INTO `sessions` VALUES (4,'Late Afternoon','15:30:00','17:00:00',4,15,1,NULL,NOW(),NOW());

-- Attendance Records (Core)
CREATE TABLE `attendance_records` (
  `id` bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` bigint UNSIGNED NOT NULL,
  `session_id` bigint UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('PRESENT','ABSENT','LATE','EXCUSED') DEFAULT 'PRESENT',
  `check_in_time` time NULL,
  `location` varchar(100) DEFAULT NULL,
  `justification` text DEFAULT NULL,
  `justified_by` bigint UNSIGNED NULL,
  `justified_at` timestamp NULL,
  `is_locked` boolean DEFAULT false,
  `recorded_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_attendance_date_status` (attendance_date, status),
  INDEX `idx_attendance_student_session` (student_id, session_id, attendance_date),
  INDEX `idx_attendance_session_student` (session_id, student_id),
  INDEX `idx_attendance_status_date` (status, attendance_date),
  INDEX `idx_attendance_locked` (is_locked),
  UNIQUE KEY `unique_student_session_date` (student_id, session_id, attendance_date),
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`justified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Biometric Scans (High Volume)
CREATE TABLE `biometric_scans` (
  `id` bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` bigint UNSIGNED NOT NULL,
  `session_id` bigint UNSIGNED NULL,
  `scan_type` enum('card','fingerprint','facial') NOT NULL,
  `scan_data` varchar(255) NOT NULL, -- Card ID or template hash
  `status` enum('success','failed','duplicate','invalid') DEFAULT 'success',
  `failure_reason` varchar(255) DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_biometric_student_type` (student_id, scan_type),
  INDEX `idx_biometric_session_status` (session_id, status),
  INDEX `idx_biometric_created_status` (created_at, status),
  INDEX `idx_biometric_scan_data` (scan_data(64)),
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE SET NULL
) PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
  PARTITION p0 VALUES LESS THAN (UNIX_TIMESTAMP('2026-01-01 00:00:00')),
  PARTITION p1 VALUES LESS THAN (UNIX_TIMESTAMP('2026-07-01 00:00:00')), 
  PARTITION p2 VALUES LESS THAN (UNIX_TIMESTAMP('2027-01-01 00:00:00')),
  PARTITION p_future VALUES LESS THAN MAXVALUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Absence Management
CREATE TABLE `absence_notifications` (
  `id` bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` bigint UNSIGNED NOT NULL,
  `session_id` bigint UNSIGNED NOT NULL,
  `attendance_record_id` bigint UNSIGNED NULL,
  `absence_reason` varchar(500) DEFAULT NULL,
  `absence_status` enum('PENDING','EXCUSED','UNEXCUSED') DEFAULT 'PENDING',
  `reason_submitted_by` bigint UNSIGNED NULL,
  `reason_submitted_at` timestamp NULL,
  `status_updated_by` bigint UNSIGNED NULL,
  `status_updated_at` timestamp NULL,
  `comment` text DEFAULT NULL,
  `follow_up_notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_absence_student_session` (student_id, session_id),
  INDEX `idx_absence_status_date` (absence_status, created_at),
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`attendance_record_id`) REFERENCES `attendance_records` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`reason_submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`status_updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Logs
CREATE TABLE `activity_logs` (
  `id` bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` bigint UNSIGNED NOT NULL,
  `user_name` varchar(100) DEFAULT NULL, -- Denormalized for fast queries
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_logs_user_action` (user_id, action),
  INDEX `idx_logs_action_date` (action, created_at),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teacher Activities (Teacher Dashboard)
CREATE TABLE `teacher_activities` (
  `id` bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` bigint UNSIGNED NULL, -- Teacher ID
  `student_id` bigint UNSIGNED NULL,
  `session_id` bigint UNSIGNED NULL,
  `action` varchar(100) NOT NULL, -- 'checkin', 'override', 'followup'
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_teacher_user_student` (user_id, student_id),
  INDEX `idx_teacher_action_date` (action, created_at),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Views (Dashboard Queries)
CREATE OR REPLACE VIEW `v_admin_attendance_enriched` AS
SELECT 
  ar.id as attendance_id,
  ar.student_id,
  CONCAT(s.first_name, ' ', s.last_name) as student_name,
  c.name as class_name,
  ar.created_at as created_time,
  ar.status,
  ar.attendance_date,
  ar.session_id,
  sess.name as session_name,
  ar.location,
  ar.is_locked,
  u.name as recorded_by
FROM attendance_records ar
JOIN students s ON ar.student_id = s.id
LEFT JOIN classes c ON s.class_id = c.id
LEFT JOIN sessions sess ON ar.session_id = sess.id
LEFT JOIN users u ON ar.recorded_by = u.id
ORDER BY ar.created_at DESC;

CREATE OR REPLACE VIEW `v_attendance_latest_follow_up` AS
SELECT 
  af.attendance_record_id,
  af.id as follow_up_id,
  af.status,
  af.comment,
  af.note,
  af.resolved,
  af.is_excused,
  af.reason,
  af.created_at as follow_up_created_at
FROM attendance_follow_ups af
JOIN (
  SELECT attendance_record_id, MAX(id) as latest_id 
  FROM attendance_follow_ups 
  GROUP BY attendance_record_id
) latest ON latest.latest_id = af.id;

-- Sample Data
INSERT INTO `users` (`name`, `email`, `password`, `role_id`) VALUES 
('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('Teacher', 'teacher@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);

-- Usage: Run this SQL in phpMyAdmin/MySQL Workbench
-- DROP DATABASE IF EXISTS attendance_system_v2; then execute
