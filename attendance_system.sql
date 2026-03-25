-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2026 at 08:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attendance_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `absence_comments`
--

CREATE TABLE `absence_comments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attendance_record_id` bigint(20) UNSIGNED NOT NULL,
  `reason` text DEFAULT NULL,
  `excuse_status` enum('excused','unexcused') DEFAULT NULL,
  `commented_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `absence_notifications`
--

CREATE TABLE `absence_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_record_id` bigint(20) UNSIGNED DEFAULT NULL,
  `absence_reason` varchar(255) DEFAULT NULL,
  `absence_status` enum('PENDING','EXCUSED','UNEXCUSED') NOT NULL DEFAULT 'PENDING',
  `comment` text DEFAULT NULL,
  `follow_up_notes` text DEFAULT NULL,
  `reason_submitted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reason_submitted_at` timestamp NULL DEFAULT NULL,
  `status_updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `status_updated_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `current_term` enum('Term1','Term2','Term3','Term4') NOT NULL DEFAULT 'Term1',
  `status` enum('Current','Close') NOT NULL DEFAULT 'Current',
  `start_year` int(11) DEFAULT NULL,
  `end_year` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `name`, `current_term`, `status`, `start_year`, `end_year`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '2026-2027', 'Term1', 'Current', NULL, NULL, '2026-09-01', '2027-08-31', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45'),
(2, '2025-2026', 'Term4', 'Close', NULL, NULL, '2025-09-01', '2026-08-31', 0, '2026-03-21 05:49:45', '2026-03-21 05:49:45'),
(3, '2024-2025', 'Term4', 'Close', NULL, NULL, '2024-09-01', '2025-08-31', 0, '2026-03-21 05:49:45', '2026-03-21 05:49:45');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `action`, `description`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'User login', 'User logged in successfully', '127.0.0.1', '2026-03-21 07:09:31', '2026-03-21 07:09:31'),
(2, 2, NULL, 'User logout', 'User logged out', '127.0.0.1', '2026-03-21 07:11:47', '2026-03-21 07:11:47'),
(3, 3, NULL, 'User login', 'User logged in successfully', '127.0.0.1', '2026-03-21 07:12:43', '2026-03-21 07:12:43');

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `class_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `submitted_by` bigint(20) UNSIGNED NOT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendances`
--

INSERT INTO `attendances` (`id`, `class_id`, `session_id`, `date`, `submitted_by`, `is_locked`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-03-18', 2, 0, '2026-03-21 05:49:41', '2026-03-21 05:49:41'),
(2, 1, 2, '2026-03-18', 2, 0, '2026-03-21 05:49:41', '2026-03-21 05:49:41'),
(3, 1, 3, '2026-03-18', 2, 0, '2026-03-21 05:49:41', '2026-03-21 05:49:41'),
(4, 1, 4, '2026-03-18', 2, 0, '2026-03-21 05:49:41', '2026-03-21 05:49:41'),
(5, 1, 1, '2026-03-04', 2, 0, '2026-03-21 05:49:41', '2026-03-21 05:49:41'),
(6, 1, 2, '2026-03-04', 2, 1, '2026-03-21 05:49:41', '2026-03-21 05:49:41'),
(7, 1, 1, '2026-03-17', 9, 0, '2026-03-21 05:49:46', '2026-03-21 05:49:46');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_follow_ups`
--

CREATE TABLE `attendance_follow_ups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attendance_record_id` bigint(20) UNSIGNED NOT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Not Contacted',
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `is_excused` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `date` date DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `status` enum('PRESENT','ABSENT','LATE') NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `justification` text DEFAULT NULL,
  `justified_at` timestamp NULL DEFAULT NULL,
  `justified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `submitted_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `recorded_at` timestamp NULL DEFAULT NULL,
  `attendance_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_records`
--

INSERT INTO `attendance_records` (`id`, `student_id`, `session_id`, `date`, `attendance_date`, `status`, `location`, `justification`, `justified_at`, `justified_by`, `submitted_by`, `created_at`, `updated_at`, `is_locked`, `recorded_at`, `attendance_id`, `created_by`) VALUES
(1, 1, 1, NULL, '2026-03-20', 'PRESENT', NULL, NULL, NULL, NULL, 6, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 0, NULL, NULL, NULL),
(2, 1, 1, NULL, '2026-03-19', 'LATE', NULL, NULL, NULL, NULL, 6, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 0, NULL, NULL, NULL),
(3, 1, 1, NULL, '2026-03-21', 'PRESENT', NULL, NULL, NULL, NULL, 6, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `biometric_scans`
--

CREATE TABLE `biometric_scans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `scan_type` enum('card','fingerprint') NOT NULL,
  `scan_data` varchar(255) DEFAULT NULL,
  `status` enum('success','failed','duplicate','invalid') NOT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `class_name` varchar(255) DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `academic_year_id` bigint(20) UNSIGNED NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`, `class_name`, `code`, `academic_year_id`, `description`, `is_active`, `created_at`, `updated_at`, `teacher_id`) VALUES
(1, 'WEP A', 'WEP A', 'WEP-A-20262027', 1, 'Web Programming Class A (2026-2027)', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 9),
(2, 'WEP B', 'WEP B', 'WEP-B-20262027', 1, 'Web Programming Class B (2026-2027)', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 10),
(3, 'WEP C', 'WEP C', 'WEP-C-20262027', 1, 'Web Programming Class C (2026-2027)', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 11),
(4, 'WEP A', 'WEP A', 'WEP-A-20252026', 2, 'Web Programming Class A (2025-2026)', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 6),
(5, 'WEP B', 'WEP B', 'WEP-B-20252026', 2, 'Web Programming Class B (2025-2026)', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 7),
(6, 'WEP C', 'WEP C', 'WEP-C-20252026', 2, 'Web Programming Class C (2025-2026)', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 8),
(7, 'WEP A', 'WEP A', 'WEP-A-20242025', 3, 'Web Programming Class A (2024-2025)', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 3),
(8, 'WEP B', 'WEP B', 'WEP-B-20242025', 3, 'Web Programming Class B (2024-2025)', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 4),
(9, 'WEP C', 'WEP C', 'WEP-C-20242025', 3, 'Web Programming Class C (2024-2025)', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 5);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2026_02_25_005305_create_roles_table', 1),
(6, '2026_02_25_024332_add_role_id_and_is_active_to_users_table', 1),
(7, '2026_02_27_040000_create_academic_years_table', 1),
(8, '2026_02_27_050000_create_classes_table', 1),
(9, '2026_02_27_062045_create_students_table', 1),
(10, '2026_02_27_072901_create_students_table', 1),
(11, '2026_02_27_081033_create_sessions_table', 1),
(12, '2026_02_27_081530_create_roles_table', 1),
(13, '2026_02_27_081530_create_users_table', 1),
(14, '2026_02_27_082036_create_classes_table', 1),
(15, '2026_02_27_083735_create_academic_years_table', 1),
(16, '2026_02_27_084622_create_sessions_table', 1),
(17, '2026_02_27_090112_create_attendance_records_table', 1),
(18, '2026_02_27_090910_create_attendances_table', 1),
(19, '2026_02_27_091500_add_foreign_keys_to_core_tables', 1),
(20, '2026_02_27_091910_create_attendance_records_table', 1),
(21, '2026_02_27_134303_add_unique_constraint_to_attendance', 1),
(22, '2026_02_27_134609_add_is_locked_to_attendance_records', 1),
(23, '2026_02_28_053107_create_absence_comments_table', 1),
(24, '2026_02_28_132003_create_activity_logs_table', 1),
(25, '2026_02_28_210000_add_profile_settings_to_users_table', 1),
(26, '2026_02_28_210100_create_attendance_follow_ups_table', 1),
(27, '2026_03_01_000000_add_biometric_fields_to_students_table', 1),
(28, '2026_03_01_000001_create_biometric_scans_table', 1),
(29, '2026_03_02_000002_add_password_to_students_table', 1),
(30, '2026_03_02_100000_add_session_config_fields', 1),
(31, '2026_03_03_000000_update_sessions_to_three_per_day', 1),
(32, '2026_03_03_000001_activate_all_four_sessions', 1),
(33, '2026_03_06_000000_update_attendance_records_status_and_recorded_at', 1),
(34, '2026_03_06_110000_add_user_name_to_activity_logs', 1),
(35, '2026_03_09_000000_drop_unique_constraint_from_attendance_records', 1),
(36, '2026_03_09_100000_add_teacher_id_to_classes_table', 1),
(37, '2026_03_10_000001_add_attendance_id_to_attendance_records_table', 1),
(38, '2026_03_10_000001_add_card_id_to_students_table', 1),
(39, '2026_03_10_000002_add_parent_contact_to_students_table', 1),
(40, '2026_03_10_000003_remove_parent_name_from_students_table', 1),
(41, '2026_03_15_000000_add_missing_columns_to_attendance_records', 1),
(42, '2026_03_16_add_is_active_to_users_table', 1),
(43, '2026_03_17_000000_add_profile_image_to_users_table', 1),
(44, '2026_03_17_000001_add_calendar_id_to_users_table', 1),
(45, '2026_03_17_000001_create_admin_attendance_enriched_view', 1),
(46, '2026_03_17_000002_add_performance_indexes', 1),
(47, '2026_03_17_000003_add_location_to_attendance_records', 1),
(48, '2026_03_17_000004_update_admin_attendance_enriched_view', 1),
(49, '2026_03_17_000005_standardize_attendance_records_columns', 1),
(50, '2026_03_17_000006_add_justification_to_attendance_records', 1),
(51, '2026_03_17_000007_create_absence_notifications_table', 1),
(52, '2026_03_17_000008_create_attendance_follow_ups_table', 1),
(53, '2026_03_17_000009_sync_all_schemas', 1),
(54, '2026_03_17_000010_add_missing_performance_indexes', 1),
(55, '2026_03_17_113948_add_missing_columns_to_students_table', 1),
(56, '2026_03_17_115015_add_start_end_year_to_academic_years_table', 1),
(57, '2026_03_17_115845_add_attendance_date_to_attendance_records_table', 1),
(58, '2026_03_18_000000_add_current_term_and_status_to_academic_years_table', 1),
(59, '2026_03_18_000001_sync_legacy_students_and_sessions_columns', 1),
(60, '2026_03_18_000002_sync_missing_student_profile_columns', 1),
(61, '2026_03_18_000003_create_teacher_activities_table', 1),
(62, '2026_03_18_000004_sync_legacy_class_name_column', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(2, 'App\\Models\\User', 3, 'auth-token', '757610563d944e860a689388af87a66020a37398728a8d35789f5e2d15937dab', '[\"*\"]', '2026-03-21 07:38:01', NULL, '2026-03-21 07:12:43', '2026-03-21 07:38:01');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin', 'Administrator with full access to the system.', '2026-03-21 05:49:37', '2026-03-21 05:49:37'),
(2, 'Teacher', 'teacher', 'Teacher with access to manage classes and students.', '2026-03-21 05:49:37', '2026-03-21 05:49:37'),
(3, 'Education Team', 'education_team', 'Education team member with access to view and manage attendance records.', '2026-03-21 05:49:37', '2026-03-21 05:49:37'),
(4, 'Training Team', 'training_team', 'Training team member with access to view and manage attendance records.', '2026-03-21 05:49:37', '2026-03-21 05:49:37'),
(5, 'Student', 'student', 'Student with access to view attendance records.', '2026-03-21 05:49:37', '2026-03-21 05:49:37');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `order` int(11) NOT NULL DEFAULT 1,
  `late_after_minutes` int(11) NOT NULL DEFAULT 10,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `late_threshold` int(11) NOT NULL DEFAULT 15,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `name`, `start_time`, `end_time`, `order`, `late_after_minutes`, `is_active`, `created_at`, `updated_at`, `late_threshold`, `description`) VALUES
(1, 'Session 1', '07:30:00', '09:00:00', 1, 10, 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 15, NULL),
(2, 'Session 2', '10:00:00', '11:30:00', 2, 10, 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 15, NULL),
(3, 'Session 3', '13:00:00', '14:30:00', 3, 10, 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 15, NULL),
(4, 'Session 4', '15:30:00', '17:00:00', 4, 10, 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 15, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_code` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `generation` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `class_id` bigint(20) UNSIGNED NOT NULL,
  `academic_year_id` bigint(20) UNSIGNED DEFAULT NULL,
  `class` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `face_image` varchar(255) DEFAULT NULL,
  `profile` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `parent_number` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `card_id` varchar(255) DEFAULT NULL,
  `fingerprint_template` text DEFAULT NULL,
  `fingerprint_enrolled` tinyint(1) NOT NULL DEFAULT 0,
  `last_biometric_scan` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_code`, `username`, `first_name`, `last_name`, `fullname`, `email`, `generation`, `gender`, `date_of_birth`, `class_id`, `academic_year_id`, `class`, `qr_code`, `face_image`, `profile`, `contact`, `parent_number`, `is_active`, `created_at`, `updated_at`, `card_id`, `fingerprint_template`, `fingerprint_enrolled`, `last_biometric_scan`, `password`) VALUES
(1, 'TEST-001', 'test001', 'Test', 'Student', 'Test Student', 'test.student@student.passerellesnumeriques.org', '2026-2027', 'Male', '2000-01-01', 1, 1, 'WEP A', 'test-qr.png', 'test-face.jpg', '/studentFaces/test-student.jpg', '+855 98 700 001', '+855 98 700 001', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'TEST-CARD-001', NULL, 0, NULL, NULL),
(2, 'PNC2026-037', 'pnc2026037', 'Serey', 'Phem', 'Serey Phem', 'serey.phem@student.passerellesnumeriques.org', '2026-2027', 'Male', '2006-02-12', 1, 1, 'WEP A', 'qr_pnc2026-037.png', 'faces/pnc2026037.jpg', '/studentFaces/pnc2026-037.jpg', '+855 98 700 037', '+855 98 700 037', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-037', NULL, 0, NULL, NULL),
(3, 'PNC2026-038', 'pnc2026038', 'Vichet', 'Sat', 'Vichet Sat', 'vichet.sat@student.passerellesnumeriques.org', '2026-2027', 'Male', '2007-05-12', 1, 1, 'WEP A', 'qr_pnc2026-038.png', 'faces/pnc2026038.jpg', '/studentFaces/pnc2026-038.jpg', '+855 98 700 038', '+855 98 700 038', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-038', NULL, 0, NULL, NULL),
(4, 'PNC2026-039', 'pnc2026039', 'Sreyroth', 'Sang', 'Sreyroth Sang', 'sreyroth.sang@student.passerellesnumeriques.org', '2026-2027', 'Female', '2007-06-11', 1, 1, 'WEP A', 'qr_pnc2026-039.png', 'faces/pnc2026039.jpg', '/studentFaces/pnc2026-039.jpg', '+855 98 700 039', '+855 98 700 039', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-039', NULL, 0, NULL, NULL),
(5, 'PNC2026-051', 'pnc2026051', 'Mary', 'Sao', 'Mary Sao', 'mary.sao@student.passerellesnumeriques.org', '2026-2027', 'Female', '2006-07-21', 1, 1, 'WEP A', 'qr_pnc2026-051.png', 'faces/pnc2026051.jpg', '/studentFaces/pnc2026-051.jpg', '+855 98 700 051', '+855 98 700 051', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-051', NULL, 0, NULL, NULL),
(6, 'PNC2026-020', 'pnc2026020', 'Vakhim', 'Krean', 'Vakhim Krean', 'vakhim.krean@student.passerellesnumeriques.org', '2026-2027', 'Male', '2004-05-29', 1, 1, 'WEP A', 'qr_pnc2026-020.png', 'faces/pnc2026020.jpg', '/studentFaces/pnc2026-020.jpg', '+855 98 700 020', '+855 98 700 020', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-020', NULL, 0, NULL, NULL),
(7, 'WEPA-001', 'wepa01', 'WEP A', 'Student 1', 'WEP A Student 1', 'wepa.01@student.passerellesnumeriques.org', '2024-2025', 'Male', '2007-03-14', 7, 3, 'WEP A', 'qr_wepa_01.png', 'faces/wepa-01.jpg', '/studentFaces/wepa-01.jpg', '+855 97 700 201', '+855 97 700 101', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-WEPA-001', NULL, 0, NULL, NULL),
(8, 'WEPA-002', 'wepa02', 'WEP A', 'Student 2', 'WEP A Student 2', 'wepa.02@student.passerellesnumeriques.org', '2024-2025', 'Female', '2006-03-07', 7, 3, 'WEP A', 'qr_wepa_02.png', 'faces/wepa-02.jpg', '/studentFaces/wepa-02.jpg', '+855 97 700 202', '+855 97 700 102', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-WEPA-002', NULL, 0, NULL, NULL),
(9, 'WEPA-003', 'wepa03', 'WEP A', 'Student 3', 'WEP A Student 3', 'wepa.03@student.passerellesnumeriques.org', '2024-2025', 'Male', '2005-02-28', 7, 3, 'WEP A', 'qr_wepa_03.png', 'faces/wepa-03.jpg', '/studentFaces/wepa-03.jpg', '+855 97 700 203', '+855 97 700 103', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-WEPA-003', NULL, 0, NULL, NULL),
(10, 'WEPB-001', 'wepb01', 'WEP B', 'Student 1', 'WEP B Student 1', 'wepb.01@student.passerellesnumeriques.org', '2024-2025', 'Male', '2007-03-14', 8, 3, 'WEP B', 'qr_wepb_01.png', 'faces/wepb-01.jpg', '/studentFaces/wepb-01.jpg', '+855 97 700 201', '+855 97 700 101', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-WEPB-001', NULL, 0, NULL, NULL),
(11, 'WEPB-002', 'wepb02', 'WEP B', 'Student 2', 'WEP B Student 2', 'wepb.02@student.passerellesnumeriques.org', '2024-2025', 'Female', '2006-03-07', 8, 3, 'WEP B', 'qr_wepb_02.png', 'faces/wepb-02.jpg', '/studentFaces/wepb-02.jpg', '+855 97 700 202', '+855 97 700 102', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-WEPB-002', NULL, 0, NULL, NULL),
(12, 'WEPB-003', 'wepb03', 'WEP B', 'Student 3', 'WEP B Student 3', 'wepb.03@student.passerellesnumeriques.org', '2024-2025', 'Male', '2005-02-28', 8, 3, 'WEP B', 'qr_wepb_03.png', 'faces/wepb-03.jpg', '/studentFaces/wepb-03.jpg', '+855 97 700 203', '+855 97 700 103', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-WEPB-003', NULL, 0, NULL, NULL),
(13, 'WEPB-004', 'wepb04', 'WEP B', 'Student 4', 'WEP B Student 4', 'wepb.04@student.passerellesnumeriques.org', '2024-2025', 'Female', '2004-02-22', 8, 3, 'WEP B', 'qr_wepb_04.png', 'faces/wepb-04.jpg', '/studentFaces/wepb-04.jpg', '+855 97 700 204', '+855 97 700 104', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-WEPB-004', NULL, 0, NULL, NULL),
(14, 'WEPC-001', 'wepc01', 'WEP C', 'Student 1', 'WEP C Student 1', 'wepc.01@student.passerellesnumeriques.org', '2024-2025', 'Male', '2007-03-14', 9, 3, 'WEP C', 'qr_wepc_01.png', 'faces/wepc-01.jpg', '/studentFaces/wepc-01.jpg', '+855 97 700 201', '+855 97 700 101', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-WEPC-001', NULL, 0, NULL, NULL),
(15, 'WEPC-002', 'wepc02', 'WEP C', 'Student 2', 'WEP C Student 2', 'wepc.02@student.passerellesnumeriques.org', '2024-2025', 'Female', '2006-03-07', 9, 3, 'WEP C', 'qr_wepc_02.png', 'faces/wepc-02.jpg', '/studentFaces/wepc-02.jpg', '+855 97 700 202', '+855 97 700 102', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-WEPC-002', NULL, 0, NULL, NULL),
(16, 'WEPC-003', 'wepc03', 'WEP C', 'Student 3', 'WEP C Student 3', 'wepc.03@student.passerellesnumeriques.org', '2024-2025', 'Male', '2005-02-28', 9, 3, 'WEP C', 'qr_wepc_03.png', 'faces/wepc-03.jpg', '/studentFaces/wepc-03.jpg', '+855 97 700 203', '+855 97 700 103', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-WEPC-003', NULL, 0, NULL, NULL),
(17, 'WEPC-004', 'wepc04', 'WEP C', 'Student 4', 'WEP C Student 4', 'wepc.04@student.passerellesnumeriques.org', '2024-2025', 'Female', '2004-02-22', 9, 3, 'WEP C', 'qr_wepc_04.png', 'faces/wepc-04.jpg', '/studentFaces/wepc-04.jpg', '+855 97 700 204', '+855 97 700 104', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-WEPC-004', NULL, 0, NULL, NULL),
(18, 'WEPA-004', 'wepa04', 'WEP A', 'Student 4', 'WEP A Student 4', 'wepa.04@student.passerellesnumeriques.org', '2024-2025', 'Female', '2004-02-22', 7, 3, 'WEP A', 'qr_wepa_04.png', 'faces/wepa-04.jpg', '/studentFaces/wepa-04.jpg', '+855 97 700 204', '+855 97 700 104', 1, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 'CARD-WEPA-004', NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_activities`
--

CREATE TABLE `teacher_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `theme` varchar(255) NOT NULL DEFAULT 'light',
  `notification_email` tinyint(1) NOT NULL DEFAULT 1,
  `notification_push` tinyint(1) NOT NULL DEFAULT 1,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `profile_image` varchar(255) DEFAULT NULL,
  `calendar_id` varchar(255) DEFAULT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `avatar_url`, `phone`, `bio`, `theme`, `notification_email`, `notification_push`, `password`, `remember_token`, `created_at`, `updated_at`, `role_id`, `is_active`, `profile_image`, `calendar_id`, `student_id`) VALUES
(1, 'Admin User', 'admin@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$UxVyo.KpmESJG3yyXivED.84bv5fS6u1zu/hhJ.n4QLi4it7Rw/Mm', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 1, 1, NULL, NULL, NULL),
(2, 'PNC Admin', 'pnc@admin.passerellesnumeriques.org', 'http://127.0.0.1:8000/storage/avatars/S9BTUiOO1NZnzbwhWU9yXSwG4Hui18RTzxuUGzFY.jpg', NULL, NULL, 'light', 1, 1, '$2y$12$ayRoo.P27Al.yli1PEyukOrfWIeFcjfrcpWW8OX91i8QA/jZ0W/6q', NULL, '2026-03-21 05:49:41', '2026-03-21 07:09:50', 1, 1, NULL, NULL, NULL),
(3, 'Davy', 'davy@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$jswf44l6HQqnvEdYlpBfFeqsACh5nYATTwY.k7nyRppPZU2jVCBIm', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/davy.jpg', 'passerellesnumeriques.org_353233363037333530@resource.calendar.google.com', NULL),
(4, 'Him', 'him@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$GewaojxGt6cW4wwYJfjhgux38p/qNb6.wq3i134BtPXotV/X0BWW2', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/him.jpg', 'passerellesnumeriques.org_343437393530363136@resource.calendar.google.com', NULL),
(5, 'Lavy', 'lavy@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$ntfPHa9tv7X9eJ.udAbUyeayM/fT5a8A8C45Jobq47FiUuopHGZru', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/lavy.jpg', 'passerellesnumeriques.org_2d3331373838323735363330@resource.calendar.google.com', NULL),
(6, 'Mengheang', 'mengheang@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$BVUE5A8K.VPog7IqaQiYXeWIfbYdr4DWBR4OGjDKXUAPWdmZMIvlK', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/mengheang.jpg', 'c_1886h9lqonri4ig0noe2vrfvp8fb8@resource.calendar.google.com', NULL),
(7, 'Ouchi', 'ouchi@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$A3GX2aDM4JJExqLtgwloWu/1Q5JjX7eIF.B/1bMEFhg6TVhqZhL0m', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/ouchi.jpg', 'c_188b20cg9s5uoh12jobk987cfbh2g@resource.calendar.google.com', NULL),
(8, 'Puthy', 'puthy@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$pis9f3JB.7xHWy3e7ZXlseWW.q.4FrlnLwp6DjYcKUrt8D7QmjWRW', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/puthy.jpg', 'passerellesnumeriques.org_3733323437383733383932@resource.calendar.google.com', NULL),
(9, 'Rady', 'rady@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$giR18l3hjWoVStcZgllKXu/6b382.I9XJbLuSCYPlQNb/Hz/TGJ46', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/rady.png', 'passerellesnumeriques.org_2d3132393337373934393735@resource.calendar.google.com', NULL),
(10, 'Savoeurn', 'savoeurn@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$g6USuer6D/kzCCx7HQxzl.RC9QTpz8mfdXzXW1.j6NUkBnylS3U9e', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/savouern.jpg', 'passerellesnumeriques.org_3539373731343733353932@resource.calendar.google.com', NULL),
(11, 'Sim', 'sim@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$cFcJHwo/e1MK0HPhN0eGcuvO/r4COprQ0Sku2Jy86lsVXqyPG/s0W', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/sim.jpg', 'passerellesnumeriques.org_3635313135323433383533@resource.calendar.google.com', NULL),
(12, 'Sokhom', 'sokhom@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$YZ./XIwB0BHklyyv4m8BNehgjGmEnIHIjoRWj08VECTtG7D5K30HS', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/sokhom.jpg', 'passerellesnumeriques.org_2d3633393338303431343434@resource.calendar.google.com', NULL),
(13, 'Somkhan', 'somkhan@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$iWLaTOcpagqCYNRqhi5dFut/jd8XAH4CCKOrJtr4r0eLGl8oHPkre', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/somkhan.jpg', 'passerellesnumeriques.org_3933333731393139373031@resource.calendar.google.com', NULL),
(14, 'Sovanchansreyleap', 'sovanchansreyleap@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$B6t4iv356K3lOFZqV/bDguQCgxGQD79Z69hKhvQJGhiEIW89xldwq', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/sovanchansreyleap.jpg', 'c_1884lpdesdih0irbl36ss1j7vt7aq@resource.calendar.google.com', NULL),
(15, 'Vandy', 'vandy@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$EeJtnEkQhbZ5Rh8I60bN9uPwJrvKBRUV8E6MauX2fmlxuIx3hZRZq', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, '/teacherFaces/sokhom.jpg', 'passerellesnumeriques.org_3331363536313232373638@resource.calendar.google.com', NULL),
(16, 'Yon', 'yon@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$TBTNlCIfLST0MAjbSYKayOOViTuMf0r4NlYXumi8k4wQ3/I0jhcgG', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 2, 1, NULL, 'c_1882ckecmfgb0h7fmha0u9t3rbd5g@resource.calendar.google.com', NULL),
(17, 'Education Team User', 'education@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$icWryB4RwkzdYBUtAsXXr.iQ4agUHc4rT67k5x9xC6atwb9qm2mYq', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 3, 1, NULL, NULL, NULL),
(18, 'Test student', 'test.student@student.passerellesnumeriques.org', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$TH15bSGqvQdnA0X8WkxbSeax5AzwpOxz/T4vqjcFD.3fBN36AI56m', NULL, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 5, 1, NULL, NULL, 1),
(19, 'Training Team User', 'training@pnc.com', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$kFWgSM7d585A5LbU/YJygOAqukP1d6yvJCs4hwVSpD3208qCLD3BK', NULL, '2026-03-21 05:49:41', '2026-03-21 05:49:41', 4, 1, NULL, NULL, NULL),
(20, 'Wep-a student1', 'wep-a.student1@student.passerellesnumeriques.org', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$Bz.E2zH5wElMjYXt3hflm.CeaJaJWkjRa5MiXxaXQIrIi4a0sL5VG', NULL, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 5, 1, NULL, NULL, 7),
(21, 'Wep-b student1', 'wep-b.student1@student.passerellesnumeriques.org', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$vjh.H7VC0hJlI.EJ19sUHujjEJBHYw5RiizzceFa3iY4vk6XvnpNC', NULL, '2026-03-21 05:49:45', '2026-03-21 05:49:45', 5, 1, NULL, NULL, 10),
(22, 'Wep-c student1', 'wep-c.student1@student.passerellesnumeriques.org', NULL, NULL, NULL, 'light', 1, 1, '$2y$12$SMNyY/ZRvgNAo3OrfPgw2.PnzUDTW6aqnN9KvH8S82gr7TQX4IDHy', NULL, '2026-03-21 05:49:46', '2026-03-21 05:49:46', 5, 1, NULL, NULL, 14);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_admin_attendance_enriched`
-- (See below for the actual view)
--
CREATE TABLE `v_admin_attendance_enriched` (
`attendance_id` bigint(20) unsigned
,`student_id` bigint(20) unsigned
,`student_name` varchar(511)
,`class_name` varchar(255)
,`created_time` timestamp
,`status` enum('PRESENT','ABSENT','LATE')
,`attendance_date` date
,`created_at` timestamp
,`session_id` bigint(20) unsigned
,`location` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_attendance_latest_follow_up`
-- (See below for the actual view)
--
CREATE TABLE `v_attendance_latest_follow_up` (
`attendance_record_id` bigint(20) unsigned
,`follow_up_id` bigint(20) unsigned
,`status` varchar(255)
,`comment` text
,`note` text
,`resolved` tinyint(1)
,`is_excused` tinyint(1)
,`reason` varchar(255)
,`follow_up_created_at` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `v_admin_attendance_enriched`
--
DROP TABLE IF EXISTS `v_admin_attendance_enriched`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_admin_attendance_enriched`  AS SELECT `ar`.`id` AS `attendance_id`, `ar`.`student_id` AS `student_id`, trim(concat(coalesce(`s`.`first_name`,''),' ',coalesce(`s`.`last_name`,''))) AS `student_name`, `c`.`name` AS `class_name`, `ar`.`created_at` AS `created_time`, `ar`.`status` AS `status`, `ar`.`date` AS `attendance_date`, `ar`.`created_at` AS `created_at`, `ar`.`session_id` AS `session_id`, `ar`.`location` AS `location` FROM (((`attendance_records` `ar` join `students` `s` on(`ar`.`student_id` = `s`.`id`)) left join `classes` `c` on(`s`.`class_id` = `c`.`id`)) left join `sessions` `sess` on(`ar`.`session_id` = `sess`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_attendance_latest_follow_up`
--
DROP TABLE IF EXISTS `v_attendance_latest_follow_up`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_attendance_latest_follow_up`  AS SELECT `af`.`attendance_record_id` AS `attendance_record_id`, `af`.`id` AS `follow_up_id`, `af`.`status` AS `status`, `af`.`comment` AS `comment`, `af`.`note` AS `note`, `af`.`resolved` AS `resolved`, `af`.`is_excused` AS `is_excused`, `af`.`reason` AS `reason`, `af`.`created_at` AS `follow_up_created_at` FROM (`attendance_follow_ups` `af` join (select `attendance_follow_ups`.`attendance_record_id` AS `attendance_record_id`,max(`attendance_follow_ups`.`id`) AS `latest_id` from `attendance_follow_ups` group by `attendance_follow_ups`.`attendance_record_id`) `latest` on(`latest`.`latest_id` = `af`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absence_comments`
--
ALTER TABLE `absence_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `absence_comments_attendance_record_id_foreign` (`attendance_record_id`),
  ADD KEY `absence_comments_commented_by_foreign` (`commented_by`);

--
-- Indexes for table `absence_notifications`
--
ALTER TABLE `absence_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `absence_notifications_student_id_foreign` (`student_id`),
  ADD KEY `absence_notifications_session_id_foreign` (`session_id`),
  ADD KEY `absence_notifications_attendance_record_id_foreign` (`attendance_record_id`),
  ADD KEY `absence_notifications_reason_submitted_by_foreign` (`reason_submitted_by`),
  ADD KEY `absence_notifications_status_updated_by_foreign` (`status_updated_by`);

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `academic_years_name_unique` (`name`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_logs_user_id_foreign` (`user_id`);

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendances_class_id_session_id_date_unique` (`class_id`,`session_id`,`date`),
  ADD KEY `attendances_session_id_foreign` (`session_id`),
  ADD KEY `attendances_submitted_by_foreign` (`submitted_by`);

--
-- Indexes for table `attendance_follow_ups`
--
ALTER TABLE `attendance_follow_ups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendance_follow_ups_updated_by_foreign` (`updated_by`),
  ADD KEY `attendance_follow_ups_attendance_record_id_created_at_index` (`attendance_record_id`,`created_at`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendance_records_student_id_index` (`student_id`),
  ADD KEY `attendance_records_session_id_index` (`session_id`),
  ADD KEY `attendance_records_submitted_by_index` (`submitted_by`),
  ADD KEY `attendance_records_status_index` (`status`),
  ADD KEY `attendance_records_student_id_session_id_index` (`student_id`,`session_id`),
  ADD KEY `attendance_records_attendance_id_foreign` (`attendance_id`),
  ADD KEY `attendance_records_created_by_foreign` (`created_by`),
  ADD KEY `idx_attendance_status_created` (`status`,`created_at`),
  ADD KEY `idx_attendance_student_date` (`student_id`,`created_at`),
  ADD KEY `idx_attendance_date_status` (`date`,`status`),
  ADD KEY `attendance_records_justified_by_foreign` (`justified_by`),
  ADD KEY `idx_attendance_date` (`attendance_date`),
  ADD KEY `idx_student_date` (`student_id`,`attendance_date`),
  ADD KEY `idx_session_date` (`session_id`,`attendance_date`);

--
-- Indexes for table `biometric_scans`
--
ALTER TABLE `biometric_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `biometric_scans_student_id_index` (`student_id`),
  ADD KEY `biometric_scans_session_id_index` (`session_id`),
  ADD KEY `biometric_scans_scan_type_index` (`scan_type`),
  ADD KEY `biometric_scans_status_index` (`status`),
  ADD KEY `biometric_scans_created_at_index` (`created_at`),
  ADD KEY `biometric_scans_student_id_session_id_scan_type_index` (`student_id`,`session_id`,`scan_type`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `classes_code_unique` (`code`),
  ADD KEY `classes_academic_year_id_foreign` (`academic_year_id`),
  ADD KEY `classes_teacher_id_foreign` (`teacher_id`),
  ADD KEY `classes_class_name_index` (`class_name`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`),
  ADD UNIQUE KEY `roles_slug_unique` (`slug`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sessions_times` (`start_time`,`end_time`),
  ADD KEY `idx_sessions_is_active` (`is_active`),
  ADD KEY `sessions_order_index` (`order`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `students_student_code_unique` (`student_code`),
  ADD UNIQUE KEY `students_card_id_unique` (`card_id`),
  ADD KEY `students_academic_year_id_foreign` (`academic_year_id`),
  ADD KEY `idx_students_class_id` (`class_id`),
  ADD KEY `students_class_index` (`class`);

--
-- Indexes for table `teacher_activities`
--
ALTER TABLE `teacher_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_activities_session_id_foreign` (`session_id`),
  ADD KEY `teacher_activities_created_at_index` (`created_at`),
  ADD KEY `idx_teacher_activities_user_created_at` (`user_id`,`created_at`),
  ADD KEY `idx_teacher_activities_student_created_at` (`student_id`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_id_foreign` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absence_comments`
--
ALTER TABLE `absence_comments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `absence_notifications`
--
ALTER TABLE `absence_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `attendance_follow_ups`
--
ALTER TABLE `attendance_follow_ups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `biometric_scans`
--
ALTER TABLE `biometric_scans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `teacher_activities`
--
ALTER TABLE `teacher_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absence_comments`
--
ALTER TABLE `absence_comments`
  ADD CONSTRAINT `absence_comments_attendance_record_id_foreign` FOREIGN KEY (`attendance_record_id`) REFERENCES `attendance_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `absence_comments_commented_by_foreign` FOREIGN KEY (`commented_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `absence_notifications`
--
ALTER TABLE `absence_notifications`
  ADD CONSTRAINT `absence_notifications_attendance_record_id_foreign` FOREIGN KEY (`attendance_record_id`) REFERENCES `attendance_records` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `absence_notifications_reason_submitted_by_foreign` FOREIGN KEY (`reason_submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `absence_notifications_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `absence_notifications_status_updated_by_foreign` FOREIGN KEY (`status_updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `absence_notifications_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendances`
--
ALTER TABLE `attendances`
  ADD CONSTRAINT `attendances_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_follow_ups`
--
ALTER TABLE `attendance_follow_ups`
  ADD CONSTRAINT `attendance_follow_ups_attendance_record_id_foreign` FOREIGN KEY (`attendance_record_id`) REFERENCES `attendance_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_follow_ups_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `attendance_records_attendance_id_foreign` FOREIGN KEY (`attendance_id`) REFERENCES `attendances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_records_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_records_justified_by_foreign` FOREIGN KEY (`justified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_records_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_records_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `biometric_scans`
--
ALTER TABLE `biometric_scans`
  ADD CONSTRAINT `biometric_scans_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `biometric_scans_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `classes_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `students_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_activities`
--
ALTER TABLE `teacher_activities`
  ADD CONSTRAINT `teacher_activities_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `teacher_activities_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `teacher_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
