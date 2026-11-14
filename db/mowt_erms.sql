-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 25, 2025 at 08:56 PM
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
-- Database: `mowt_erms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `admin_user` text NOT NULL,
  `email` varchar(255) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `admin_status` varchar(50) NOT NULL DEFAULT 'Active',
  `role_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `admin_user`, `email`, `admin_password`, `admin_status`, `role_id`, `department_id`, `last_login`) VALUES
(2, 'Sarah Nantege', 's.nantege', 's.nantege@mowt.go.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', 2, 4, '2025-07-25 18:44:23'),
(8, 'Alice Kisakye', 'a.kisakye', 'a.kisakye@mowt.go.ug', '$2y$10$QaMH6lso9snwovCTbPh0KeZ9grSLLLCO2FrSrRDR6UIEUgnVAMpeu', 'Active', 4, 4, '2025-07-25 14:44:08');

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(512) NOT NULL,
  `file_size` int(11) NOT NULL COMMENT 'Size in bytes',
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `download_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attachments`
--

INSERT INTO `attachments` (`id`, `message_id`, `file_name`, `file_path`, `file_size`, `mime_type`, `uploaded_at`, `download_count`) VALUES
(1, 4, 'Resignation_Letter.pdf', 'uploads/attachments/686418d679a9d.pdf', 4589, 'application/pdf', '2025-07-01 17:20:22', 0),
(2, 9, 'Resignation_Letter.pdf', 'uploads/attachments/6864ca81440db.pdf', 4589, 'application/pdf', '2025-07-02 05:58:25', 0);

-- --------------------------------------------------------

--
-- Table structure for table `attachment_downloads`
--

CREATE TABLE `attachment_downloads` (
  `id` int(11) NOT NULL,
  `attachment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','employee') NOT NULL,
  `downloaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attachment_downloads`
--

INSERT INTO `attachment_downloads` (`id`, `attachment_id`, `user_id`, `user_type`, `downloaded_at`, `ip_address`) VALUES
(1, 2, 2, 'admin', '2025-07-02 06:09:26', NULL),
(2, 2, 2, 'admin', '2025-07-02 06:09:26', NULL),
(3, 2, 2, 'admin', '2025-07-02 06:09:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `action_type` enum('Create','Edit','Delete','View','Download') NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `user_id`, `target_user_id`, `action_type`, `document_id`, `ip_address`, `timestamp`) VALUES
(1, 1, NULL, 'Create', NULL, '192.168.1.10', '2023-06-15 10:30:45'),
(2, 2, NULL, 'View', NULL, '192.168.1.15', '2023-06-16 11:20:15'),
(3, 3, NULL, 'Download', NULL, '192.168.1.20', '2023-04-12 14:15:30'),
(4, 1, NULL, 'Edit', NULL, '192.168.1.10', '2023-07-23 09:45:10'),
(5, 4, NULL, 'View', NULL, '192.168.2.10', '2023-01-06 16:20:55'),
(6, 5, NULL, 'Create', NULL, '192.168.2.05', '2023-08-14 15:30:20'),
(7, 2, NULL, 'Download', NULL, '192.168.1.15', '2023-08-15 10:15:40'),
(8, 2, NULL, 'View', NULL, '::1', '2025-06-30 14:26:34'),
(9, 2, NULL, 'View', NULL, '::1', '2025-06-30 16:42:52'),
(10, 2, NULL, 'View', NULL, '::1', '2025-06-30 17:18:41'),
(11, 2, NULL, 'View', NULL, '::1', '2025-06-30 17:18:59'),
(12, 2, NULL, 'View', NULL, '::1', '2025-06-30 20:54:06'),
(13, 2, NULL, 'Edit', NULL, '::1', '2025-06-30 21:36:47'),
(14, 2, NULL, 'View', NULL, '::1', '2025-06-30 21:36:56'),
(15, 2, NULL, 'Edit', NULL, '::1', '2025-06-30 21:37:38'),
(16, 2, NULL, 'View', NULL, '::1', '2025-06-30 21:37:44'),
(17, 2, NULL, 'View', NULL, '::1', '2025-06-30 21:37:55'),
(18, 5, NULL, 'View', NULL, '::1', '2025-06-30 21:50:15'),
(19, 2, NULL, 'View', NULL, '::1', '2025-06-30 23:20:27'),
(20, 2, NULL, 'View', NULL, '::1', '2025-06-30 23:20:31'),
(21, 2, NULL, 'View', NULL, '::1', '2025-06-30 23:20:48'),
(22, 2, NULL, 'View', NULL, '::1', '2025-06-30 23:41:17'),
(23, 2, NULL, 'View', NULL, '::1', '2025-06-30 23:41:33'),
(24, 2, NULL, 'View', NULL, '::1', '2025-06-30 23:42:40'),
(25, 2, NULL, 'View', NULL, '::1', '2025-07-01 10:27:31'),
(26, 2, NULL, 'View', NULL, '::1', '2025-07-01 13:25:12'),
(27, 2, NULL, 'View', NULL, '::1', '2025-07-01 13:51:31'),
(28, 2, NULL, 'Edit', NULL, '::1', '2025-07-01 13:57:58'),
(29, 1, NULL, 'View', NULL, '::1', '2025-07-01 15:03:03'),
(30, 2, NULL, 'View', NULL, '::1', '2025-07-01 16:44:18'),
(31, 2, NULL, 'View', NULL, '::1', '2025-07-01 21:29:18'),
(32, 1, NULL, 'View', NULL, '::1', '2025-07-02 11:29:03'),
(33, 1, NULL, 'View', NULL, '::1', '2025-07-02 11:29:53'),
(34, 2, NULL, 'View', NULL, '::1', '2025-07-02 12:04:49'),
(35, 2, NULL, 'View', NULL, '::1', '2025-07-02 12:51:39'),
(36, 1, NULL, 'View', NULL, '::1', '2025-07-02 14:54:40'),
(37, 1, NULL, 'View', NULL, '::1', '2025-07-02 23:52:03'),
(38, 3, NULL, 'View', NULL, '::1', '2025-07-03 09:07:05'),
(39, 2, NULL, 'Edit', NULL, '::1', '2025-07-03 09:44:13'),
(40, 2, NULL, 'View', NULL, '::1', '2025-07-03 09:44:59'),
(41, 1, NULL, 'View', NULL, '::1', '2025-07-03 19:35:15'),
(42, 1, NULL, 'View', NULL, '::1', '2025-07-03 19:37:01'),
(43, 1, NULL, 'View', NULL, '::1', '2025-07-03 21:53:20'),
(45, 2, NULL, 'View', NULL, '::1', '2025-07-04 11:19:48'),
(46, 2, NULL, 'Edit', NULL, '::1', '2025-07-04 11:23:54'),
(47, 2, NULL, 'View', NULL, '::1', '2025-07-04 11:46:29'),
(48, 2, NULL, 'Delete', NULL, '::1', '2025-07-04 12:05:35'),
(49, 2, NULL, 'Delete', NULL, '::1', '2025-07-04 12:06:53'),
(50, 2, NULL, 'View', NULL, '::1', '2025-07-04 12:07:10'),
(51, 2, NULL, 'Delete', NULL, '::1', '2025-07-04 12:07:15'),
(52, 2, NULL, 'Delete', NULL, '::1', '2025-07-04 12:09:10'),
(53, 2, NULL, 'View', NULL, '::1', '2025-07-04 12:13:17'),
(54, 1, NULL, 'View', NULL, '::1', '2025-07-04 13:39:05'),
(55, 2, NULL, 'View', NULL, '::1', '2025-07-04 14:13:59'),
(56, 2, NULL, 'View', NULL, '::1', '2025-07-04 14:14:27'),
(57, 2, NULL, 'Delete', NULL, '::1', '2025-07-04 14:14:35'),
(58, 1, NULL, 'View', NULL, '::1', '2025-07-04 15:18:53'),
(59, 1, NULL, 'View', NULL, '::1', '2025-07-04 16:21:46'),
(60, 1, NULL, 'View', NULL, '::1', '2025-07-04 16:25:21'),
(61, 2, 4, '', NULL, '::1', '2025-07-10 21:48:01'),
(62, 2, NULL, '', NULL, '::1', '2025-07-10 22:15:30'),
(65, 6, NULL, 'Delete', NULL, '::1', '2025-07-10 22:44:12'),
(66, 6, NULL, 'Delete', NULL, '::1', '2025-07-10 22:45:23'),
(67, 6, NULL, '', NULL, '::1', '2025-07-10 22:47:18'),
(69, 2, NULL, '', NULL, '::1', '2025-07-10 22:59:57'),
(70, 2, NULL, '', NULL, '::1', '2025-07-10 23:27:18'),
(71, 12, NULL, 'View', NULL, '::1', '2025-07-10 23:29:47'),
(72, 12, NULL, 'View', NULL, '::1', '2025-07-10 23:29:58'),
(73, 2, NULL, 'View', NULL, '::1', '2025-07-11 21:30:08'),
(74, 9, NULL, 'View', NULL, '::1', '2025-07-11 23:59:08'),
(75, 2, NULL, 'Delete', NULL, '::1', '2025-07-12 00:04:23'),
(76, 4, NULL, 'View', NULL, '::1', '2025-07-12 00:36:52'),
(77, 4, NULL, 'View', NULL, '::1', '2025-07-12 00:38:32'),
(78, 12, NULL, 'View', NULL, '::1', '2025-07-12 21:31:28'),
(79, 2, NULL, 'View', NULL, '::1', '2025-07-24 17:55:59'),
(80, 2, NULL, 'View', NULL, '::1', '2025-07-25 13:53:22'),
(81, 2, NULL, 'View', NULL, '::1', '2025-07-25 13:56:58'),
(82, 2, NULL, 'View', NULL, '::1', '2025-07-25 13:57:00'),
(83, 2, NULL, 'View', NULL, '::1', '2025-07-25 13:58:23'),
(84, 2, NULL, 'View', NULL, '::1', '2025-07-25 13:59:05'),
(85, 2, NULL, 'View', NULL, '::1', '2025-07-25 13:59:58'),
(86, 2, NULL, 'View', NULL, '::1', '2025-07-25 14:07:48'),
(87, 2, NULL, 'Edit', NULL, '::1', '2025-07-25 14:13:30'),
(88, 2, NULL, '', NULL, '::1', '2025-07-25 14:30:05'),
(89, 13, NULL, 'View', 21, '::1', '2025-07-25 14:54:42'),
(90, 13, NULL, 'View', 21, '::1', '2025-07-25 14:56:50'),
(91, 13, NULL, 'View', 21, '::1', '2025-07-25 14:57:26'),
(92, 13, NULL, 'View', 21, '::1', '2025-07-25 14:57:27'),
(93, 13, NULL, 'View', 21, '::1', '2025-07-25 14:57:28'),
(94, 13, NULL, 'View', 21, '::1', '2025-07-25 14:57:28'),
(95, 13, NULL, 'View', 21, '::1', '2025-07-25 14:57:28'),
(96, 13, NULL, 'View', 21, '::1', '2025-07-25 14:57:28'),
(97, 13, NULL, 'View', 21, '::1', '2025-07-25 14:57:50'),
(98, 13, NULL, 'View', 21, '::1', '2025-07-25 14:58:03'),
(99, 2, NULL, 'View', 21, '::1', '2025-07-25 15:01:03'),
(100, 2, NULL, '', NULL, '::1', '2025-07-25 16:50:56'),
(101, 13, NULL, 'View', 21, '::1', '2025-07-25 16:58:21'),
(102, 2, NULL, '', NULL, '::1', '2025-07-25 16:59:24'),
(103, 8, NULL, '', NULL, '::1', '2025-07-25 17:21:55'),
(104, 15, NULL, 'View', 22, '::1', '2025-07-25 17:26:06'),
(105, 8, NULL, '', NULL, '::1', '2025-07-25 17:29:30'),
(106, 8, NULL, '', NULL, '::1', '2025-07-25 17:30:47'),
(107, 8, NULL, '', NULL, '::1', '2025-07-25 17:31:37'),
(108, 19, NULL, 'View', 26, '::1', '2025-07-25 21:40:02'),
(109, 2, NULL, 'View', 22, '::1', '2025-07-25 21:52:23');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_message_at` timestamp NULL DEFAULT NULL,
  `is_group` tinyint(1) DEFAULT 0,
  `group_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `created_at`, `last_message_at`, `is_group`, `group_name`) VALUES
(1, '2025-07-01 14:47:30', '2025-07-01 17:20:22', 0, NULL),
(2, '2025-07-01 14:49:32', '2025-07-01 14:49:32', 0, NULL),
(3, '2025-07-01 14:49:32', '2025-07-01 14:49:32', 0, NULL),
(4, '2025-07-01 18:48:42', '2025-07-01 18:48:42', 0, NULL),
(5, '2025-07-02 05:50:17', '2025-07-04 18:34:16', 0, NULL),
(6, '2025-07-03 05:36:38', '2025-07-03 05:42:15', 0, NULL),
(7, '2025-07-25 13:00:28', '2025-07-25 13:00:28', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','employee') NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversation_participants`
--

INSERT INTO `conversation_participants` (`id`, `conversation_id`, `user_id`, `user_type`, `joined_at`) VALUES
(1, 1, 2, 'admin', '2025-07-01 14:47:30'),
(2, 1, 6, 'employee', '2025-07-01 14:47:30'),
(3, 2, 2, 'admin', '2025-07-01 14:49:32'),
(4, 2, 3, 'admin', '2025-07-01 14:49:32'),
(5, 3, 2, 'admin', '2025-07-01 14:49:32'),
(6, 3, 3, 'employee', '2025-07-01 14:49:32'),
(7, 4, 2, 'admin', '2025-07-01 18:48:42'),
(8, 4, 1, 'admin', '2025-07-01 18:48:42'),
(9, 5, 1, 'employee', '2025-07-02 05:50:17'),
(10, 5, 2, 'admin', '2025-07-02 05:50:17'),
(11, 6, 5, 'employee', '2025-07-03 05:36:38'),
(12, 6, 3, 'admin', '2025-07-03 05:36:38'),
(13, 7, 2, 'admin', '2025-07-25 13:00:28'),
(14, 7, 13, 'employee', '2025-07-25 13:00:28');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `description`, `location`, `contact_email`) VALUES
(1, 'Human Resources', 'Personnel records management', 'Headquarters, Floor 1', 'hr@mowt.go.ug'),
(2, 'Accounts Department', 'Financial records and accounting', 'Headquarters, Floor 2', 'accounts@mowt.go.ug'),
(3, 'UNRA', 'Uganda National Roads Authority', 'Block A, Main Building', 'unra@mowt.go.ug'),
(4, 'Registry', 'Central records management department', 'Headquarters, Floor 3', 'registry@mowt.go.ug');

-- --------------------------------------------------------

--
-- Table structure for table `disposition_types`
--

CREATE TABLE `disposition_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `retention_period_months` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disposition_types`
--

INSERT INTO `disposition_types` (`id`, `name`, `description`, `retention_period_months`, `is_active`, `created_at`) VALUES
(1, 'Standard Retention', 'Standard document retention policy', 60, 1, '2025-07-01 10:12:36'),
(2, 'Financial Records', 'Financial documents retention', 84, 1, '2025-07-01 10:12:36'),
(3, 'Personnel Files', 'Employee personnel files', 120, 1, '2025-07-01 10:12:36'),
(4, 'Legal Documents', 'Legal contracts and agreements', 240, 1, '2025-07-01 10:12:36'),
(5, 'Temporary Files', 'Short-term temporary documents', 6, 1, '2025-07-01 10:12:36');

-- --------------------------------------------------------

--
-- Table structure for table `document_detentions`
--

CREATE TABLE `document_detentions` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `detained_by` int(11) NOT NULL,
  `detention_date` date NOT NULL,
  `reason` text NOT NULL,
  `expected_release_date` date DEFAULT NULL,
  `actual_release_date` date DEFAULT NULL,
  `status` enum('Active','Released','Extended') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_detentions`
--

INSERT INTO `document_detentions` (`id`, `document_id`, `detained_by`, `detention_date`, `reason`, `expected_release_date`, `actual_release_date`, `status`) VALUES
(1, 7, 2, '2025-07-01', 'N/A', '2025-07-02', '2025-07-01', 'Released'),
(2, 7, 2, '2025-07-01', 'N/A', '2025-07-02', '2025-07-01', 'Released'),
(3, 0, 2, '2025-07-02', 'N/A', '2025-07-03', '2025-07-03', 'Released'),
(4, 7, 2, '2025-07-04', 'N/A', '2025-07-05', '2025-07-04', 'Released'),
(5, 7, 2, '2025-07-04', 'N/A', '2025-07-05', '2025-07-04', 'Released'),
(6, 13, 2, '2025-07-10', 'N/A', '2025-07-11', '2025-07-10', 'Released'),
(7, 13, 2, '2025-07-11', 'NB', '2025-07-12', '2025-07-12', 'Released');

-- --------------------------------------------------------

--
-- Table structure for table `document_disposals`
--

CREATE TABLE `document_disposals` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `disposition_type_id` int(11) NOT NULL,
  `disposed_by` int(11) NOT NULL,
  `disposal_date` date NOT NULL,
  `method` enum('Shredding','Digital Erasure','Physical Destruction','Transfer','Other') NOT NULL,
  `notes` text DEFAULT NULL,
  `verification_officer` int(11) DEFAULT NULL,
  `verification_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_user`
--

CREATE TABLE `employee_user` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `user_password` varchar(255) NOT NULL,
  `user_status` enum('Active','Inactive') DEFAULT 'Active',
  `department_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_user`
--

INSERT INTO `employee_user` (`id`, `name`, `email_address`, `user_password`, `user_status`, `department_id`, `role_id`, `last_login`) VALUES
(13, 'Robert Mugabe', 'r.mugabe@mowt.go.ug', '$2y$10$OCQkEKJhfC1hDgVlYhRKiuo4tSpnFj7BdCWf0T/4dJJFlVMa286wS', 'Active', 1, 4, '2025-07-25 13:07:02'),
(14, 'Mary Katushabe', 'm.katushabe@mowt.go.ug', '$2y$10$WfaYKNZAIMz3/7S8/ZWoAu7VTMqVPIHHY6NKrNX6Fz0X64oKVk.mG', 'Active', 3, 4, '2025-07-25 17:06:42'),
(15, 'David Okello', 'd.okello@mowt.go.ug', '$2y$10$yYmjsgjN1aPh.KO03xtpOOo7TCi4C97wVuFVpgtbj6sarkLl31S2y', 'Active', 2, 4, '2025-07-25 14:25:45'),
(17, 'Grace Nakimera', 'g.nakimera@mowt.go.ug', '$2y$10$u3TkGum.3lo3QTuXHDZoAOuxTGWZ0koCsqaXfoveXCM9pORIFpFq6', 'Active', 2, 4, '2025-07-25 18:43:54'),
(18, 'Joseph Sebagala', 'j.sebagala@mowt.go.ug', '$2y$10$K41bPBotHM625uBE5YMoluCVfwOJlw2YnFjWc0L2RfrOO1R5c1ca.', 'Active', 1, 4, NULL),
(19, 'Gorret Nyana', 'g.nyana@mowt.go.ug', '$2y$10$i3F.37fzL6fKw5EdgKJ4yOfvHf2kz533EuFPgFtZezYAUtmlacPlS', 'Active', 3, 4, '2025-07-25 18:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `history_log`
--

CREATE TABLE `history_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('admin','employee') NOT NULL,
  `email_address` text DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `ip` text DEFAULT NULL,
  `host` text DEFAULT NULL,
  `login_time` datetime DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `history_log`
--

INSERT INTO `history_log` (`log_id`, `user_id`, `user_type`, `email_address`, `action`, `ip`, `host`, `login_time`, `logout_time`) VALUES
(758, 8, 'admin', 'a.kisakye@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:23:58', '2025-07-25 17:31:52'),
(759, 15, 'employee', 'd.okello@mowt.go.ug', 'Login', '::1', 'ACACACACA', '2025-07-25 17:25:45', NULL),
(760, 15, 'employee', 'd.okello@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:25:45', NULL),
(761, 15, 'employee', 'd.okello@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:25:58', NULL),
(762, 15, 'employee', 'd.okello@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:26:03', NULL),
(763, 15, 'employee', 'd.okello@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:26:18', NULL),
(764, 15, 'employee', 'd.okello@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:26:45', NULL),
(765, 15, 'employee', 'd.okello@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:29:39', NULL),
(766, 15, 'employee', 'd.okello@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:29:48', NULL),
(767, 15, 'employee', 'd.okello@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:29:53', '2025-07-25 17:43:43'),
(768, 2, 'admin', 's.nantege@mowt.go.ug', 'Login', '::1', 'ACACACACA', '2025-07-25 17:32:09', NULL),
(769, 2, 'admin', 's.nantege@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:32:09', NULL),
(770, 2, 'admin', 's.nantege@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:41:22', NULL),
(771, 2, 'admin', 's.nantege@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:41:29', NULL),
(772, 2, 'admin', 's.nantege@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:42:29', NULL),
(773, 2, 'admin', 's.nantege@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:42:54', '2025-07-25 17:43:37'),
(774, 8, 'admin', 'a.kisakye@mowt.go.ug', 'Login', '::1', 'ACACACACA', '2025-07-25 17:44:08', NULL),
(775, 8, 'admin', 'a.kisakye@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:44:08', '2025-07-25 17:46:52'),
(776, 14, 'employee', 'm.katushabe@mowt.go.ug', 'Login', '::1', 'ACACACACA', '2025-07-25 17:46:57', NULL),
(777, 14, 'employee', 'm.katushabe@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:46:57', NULL),
(778, 14, 'employee', 'm.katushabe@mowt.go.ug', 'Login', '::1', 'ACACACACA', '2025-07-25 17:48:02', NULL),
(779, 14, 'employee', 'm.katushabe@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:48:02', NULL),
(780, 14, 'employee', 'm.katushabe@mowt.go.ug', 'Login', '::1', 'ACACACACA', '2025-07-25 17:53:40', NULL),
(781, 14, 'employee', 'm.katushabe@mowt.go.ug', 'Dashboard Access', '::1', 'ACACACACA', '2025-07-25 17:53:40', NULL),
(782, 14, 'employee', 'm.katushabe@mowt.go.ug', 'Login', '::1', 'DESKTOP-FDV879F', '2025-07-25 20:06:42', NULL),
(783, 14, 'employee', 'm.katushabe@mowt.go.ug', 'Dashboard Access', '::1', 'DESKTOP-FDV879F', '2025-07-25 20:06:43', NULL),
(784, 14, 'employee', 'm.katushabe@mowt.go.ug', 'Dashboard Access', '::1', 'DESKTOP-FDV879F', '2025-07-25 20:06:57', NULL),
(785, 14, 'employee', 'm.katushabe@mowt.go.ug', 'Dashboard Access', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:21:12', '2025-07-25 21:21:25'),
(786, 2, 'admin', 's.nantege@mowt.go.ug', 'Login', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:21:38', NULL),
(787, 2, 'admin', 's.nantege@mowt.go.ug', 'Dashboard Access', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:21:38', '2025-07-25 21:23:16'),
(788, 19, 'employee', 'g.nyana@mowt.go.ug', 'Login', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:23:26', NULL),
(789, 19, 'employee', 'g.nyana@mowt.go.ug', 'Dashboard Access', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:23:26', '2025-07-25 21:42:18'),
(790, 2, 'admin', 's.nantege@mowt.go.ug', 'Login', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:42:28', NULL),
(791, 2, 'admin', 's.nantege@mowt.go.ug', 'Dashboard Access', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:42:28', NULL),
(792, 2, 'admin', 's.nantege@mowt.go.ug', 'Dashboard Access', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:43:12', '2025-07-25 21:43:45'),
(793, 17, 'employee', 'g.nakimera@mowt.go.ug', 'Login', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:43:54', NULL),
(794, 17, 'employee', 'g.nakimera@mowt.go.ug', 'Dashboard Access', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:43:54', NULL),
(795, 17, 'employee', 'g.nakimera@mowt.go.ug', 'Dashboard Access', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:44:01', '2025-07-25 21:44:13'),
(796, 2, 'admin', 's.nantege@mowt.go.ug', 'Login', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:44:23', NULL),
(797, 2, 'admin', 's.nantege@mowt.go.ug', 'Dashboard Access', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:44:23', NULL),
(798, 2, 'admin', 's.nantege@mowt.go.ug', 'Dashboard Access', '::1', 'DESKTOP-FDV879F', '2025-07-25 21:50:06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('admin','employee') NOT NULL,
  `content` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','delivered','read') DEFAULT 'sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `sender_type`, `content`, `sent_at`, `status`) VALUES
(1, 1, 2, 'admin', 'hi', '2025-07-01 14:47:30', 'sent'),
(2, 2, 2, 'admin', 'hello', '2025-07-01 14:49:32', 'sent'),
(3, 3, 2, 'admin', 'hello', '2025-07-01 14:49:32', 'sent'),
(4, 1, 2, 'admin', 'here', '2025-07-01 17:20:22', 'sent'),
(5, 4, 2, 'admin', 'hi', '2025-07-01 18:48:42', 'sent'),
(6, 5, 1, 'employee', 'Hello, good morning', '2025-07-02 05:50:17', 'sent'),
(7, 5, 1, 'employee', 'Hope your doing well', '2025-07-02 05:52:19', 'sent'),
(8, 5, 2, 'admin', 'yes am good', '2025-07-02 05:55:03', 'sent'),
(9, 5, 1, 'employee', 'Receive this document', '2025-07-02 05:58:25', 'sent'),
(10, 5, 2, 'admin', 'thanks', '2025-07-02 06:01:12', 'sent'),
(11, 5, 1, 'employee', 'welcome', '2025-07-02 06:06:38', 'sent'),
(12, 6, 5, 'employee', 'Hello', '2025-07-03 05:36:38', 'sent'),
(13, 6, 3, 'admin', 'hello Joseph', '2025-07-03 05:42:15', 'sent'),
(14, 5, 2, 'admin', 'okay', '2025-07-04 18:34:16', 'sent'),
(15, 7, 2, 'admin', 'hi', '2025-07-25 13:00:28', 'sent');

-- --------------------------------------------------------

--
-- Table structure for table `message_status`
--

CREATE TABLE `message_status` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','employee') NOT NULL,
  `status` enum('delivered','read') NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message_status`
--

INSERT INTO `message_status` (`id`, `message_id`, `user_id`, `user_type`, `status`, `updated_at`) VALUES
(1, 1, 6, 'employee', 'delivered', '2025-07-01 14:47:30'),
(2, 2, 3, 'admin', 'delivered', '2025-07-01 14:49:32'),
(3, 3, 3, 'employee', 'delivered', '2025-07-01 14:49:32'),
(4, 4, 6, 'employee', 'delivered', '2025-07-01 17:20:22'),
(5, 5, 1, 'admin', 'delivered', '2025-07-01 18:48:42'),
(6, 6, 2, 'admin', 'read', '2025-07-02 05:53:02'),
(7, 7, 2, 'admin', 'read', '2025-07-02 05:53:02'),
(8, 6, 2, 'admin', 'read', '2025-07-02 05:55:03'),
(9, 7, 2, 'admin', 'read', '2025-07-02 05:55:03'),
(10, 8, 1, 'employee', 'read', '2025-07-02 05:58:25'),
(11, 8, 1, 'employee', 'read', '2025-07-02 05:58:25'),
(12, 6, 2, 'admin', 'read', '2025-07-02 06:01:12'),
(13, 7, 2, 'admin', 'read', '2025-07-02 06:01:12'),
(14, 8, 1, 'employee', 'read', '2025-07-02 05:58:25'),
(15, 6, 2, 'admin', 'read', '2025-07-02 06:01:12'),
(16, 7, 2, 'admin', 'read', '2025-07-02 06:01:12'),
(17, 9, 2, 'admin', 'read', '2025-07-02 06:01:12'),
(18, 8, 1, 'employee', 'read', '2025-07-02 06:04:38'),
(19, 9, 2, 'admin', 'read', '2025-07-02 06:01:12'),
(20, 6, 2, 'admin', 'read', '2025-07-02 06:01:12'),
(21, 7, 2, 'admin', 'read', '2025-07-02 06:01:12'),
(22, 9, 2, 'admin', 'read', '2025-07-02 06:01:12'),
(23, 8, 1, 'employee', 'read', '2025-07-02 06:04:38'),
(24, 10, 1, 'employee', 'read', '2025-07-02 06:04:38'),
(25, 10, 1, 'employee', 'read', '2025-07-02 06:04:38'),
(26, 6, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(27, 7, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(28, 9, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(29, 6, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(30, 7, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(31, 9, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(32, 8, 1, 'employee', 'read', '2025-07-02 06:06:38'),
(33, 10, 1, 'employee', 'read', '2025-07-02 06:06:38'),
(34, 6, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(35, 7, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(36, 9, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(37, 11, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(38, 8, 1, 'employee', 'read', '2025-07-02 06:08:49'),
(39, 10, 1, 'employee', 'read', '2025-07-02 06:08:49'),
(40, 11, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(41, 6, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(42, 7, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(43, 9, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(44, 11, 2, 'admin', 'read', '2025-07-02 07:56:02'),
(45, 8, 1, 'employee', 'read', '2025-07-02 07:54:56'),
(46, 10, 1, 'employee', 'read', '2025-07-02 07:54:56'),
(47, 8, 1, 'employee', 'delivered', '2025-07-02 07:54:56'),
(48, 10, 1, 'employee', 'delivered', '2025-07-02 07:54:56'),
(49, 6, 2, 'admin', 'read', '2025-07-03 07:54:39'),
(50, 7, 2, 'admin', 'read', '2025-07-03 07:54:39'),
(51, 9, 2, 'admin', 'read', '2025-07-03 07:54:39'),
(52, 11, 2, 'admin', 'read', '2025-07-03 07:54:39'),
(53, 8, 1, 'employee', 'delivered', '2025-07-02 07:56:59'),
(54, 10, 1, 'employee', 'delivered', '2025-07-02 07:56:59'),
(55, 12, 3, 'admin', 'read', '2025-07-03 05:40:15'),
(56, 12, 3, 'admin', 'read', '2025-07-03 05:42:15'),
(57, 13, 5, 'employee', 'delivered', '2025-07-03 05:42:15'),
(58, 12, 3, 'admin', 'delivered', '2025-07-03 05:42:15'),
(59, 12, 3, 'admin', 'delivered', '2025-07-03 05:44:19'),
(60, 6, 2, 'admin', 'read', '2025-07-04 18:32:15'),
(61, 7, 2, 'admin', 'read', '2025-07-04 18:32:15'),
(62, 9, 2, 'admin', 'read', '2025-07-04 18:32:15'),
(63, 11, 2, 'admin', 'read', '2025-07-04 18:32:15'),
(64, 6, 2, 'admin', 'read', '2025-07-04 18:34:18'),
(65, 7, 2, 'admin', 'read', '2025-07-04 18:34:18'),
(66, 9, 2, 'admin', 'read', '2025-07-04 18:34:18'),
(67, 11, 2, 'admin', 'read', '2025-07-04 18:34:18'),
(68, 14, 1, 'employee', 'delivered', '2025-07-04 18:34:16'),
(69, 6, 2, 'admin', 'delivered', '2025-07-04 18:34:18'),
(70, 7, 2, 'admin', 'delivered', '2025-07-04 18:34:18'),
(71, 9, 2, 'admin', 'delivered', '2025-07-04 18:34:18'),
(72, 11, 2, 'admin', 'delivered', '2025-07-04 18:34:18'),
(73, 6, 2, 'admin', 'delivered', '2025-07-04 18:36:22'),
(74, 7, 2, 'admin', 'delivered', '2025-07-04 18:36:22'),
(75, 9, 2, 'admin', 'delivered', '2025-07-04 18:36:22'),
(76, 11, 2, 'admin', 'delivered', '2025-07-04 18:36:22'),
(77, 6, 2, 'admin', 'delivered', '2025-07-04 18:38:25'),
(78, 7, 2, 'admin', 'delivered', '2025-07-04 18:38:25'),
(79, 9, 2, 'admin', 'delivered', '2025-07-04 18:38:25'),
(80, 11, 2, 'admin', 'delivered', '2025-07-04 18:38:25'),
(81, 6, 2, 'admin', 'delivered', '2025-07-04 18:40:29'),
(82, 7, 2, 'admin', 'delivered', '2025-07-04 18:40:29'),
(83, 9, 2, 'admin', 'delivered', '2025-07-04 18:40:29'),
(84, 11, 2, 'admin', 'delivered', '2025-07-04 18:40:29'),
(85, 6, 2, 'admin', 'delivered', '2025-07-04 18:42:33'),
(86, 7, 2, 'admin', 'delivered', '2025-07-04 18:42:33'),
(87, 9, 2, 'admin', 'delivered', '2025-07-04 18:42:33'),
(88, 11, 2, 'admin', 'delivered', '2025-07-04 18:42:33'),
(89, 6, 2, 'admin', 'delivered', '2025-07-04 18:44:36'),
(90, 7, 2, 'admin', 'delivered', '2025-07-04 18:44:36'),
(91, 9, 2, 'admin', 'delivered', '2025-07-04 18:44:36'),
(92, 11, 2, 'admin', 'delivered', '2025-07-04 18:44:36'),
(93, 6, 2, 'admin', 'delivered', '2025-07-04 18:46:39'),
(94, 7, 2, 'admin', 'delivered', '2025-07-04 18:46:39'),
(95, 9, 2, 'admin', 'delivered', '2025-07-04 18:46:39'),
(96, 11, 2, 'admin', 'delivered', '2025-07-04 18:46:39'),
(97, 6, 2, 'admin', 'delivered', '2025-07-04 18:48:43'),
(98, 7, 2, 'admin', 'delivered', '2025-07-04 18:48:43'),
(99, 9, 2, 'admin', 'delivered', '2025-07-04 18:48:43'),
(100, 11, 2, 'admin', 'delivered', '2025-07-04 18:48:43'),
(101, 6, 2, 'admin', 'delivered', '2025-07-04 18:50:46'),
(102, 7, 2, 'admin', 'delivered', '2025-07-04 18:50:46'),
(103, 9, 2, 'admin', 'delivered', '2025-07-04 18:50:46'),
(104, 11, 2, 'admin', 'delivered', '2025-07-04 18:50:46'),
(105, 6, 2, 'admin', 'delivered', '2025-07-04 18:52:49'),
(106, 7, 2, 'admin', 'delivered', '2025-07-04 18:52:49'),
(107, 9, 2, 'admin', 'delivered', '2025-07-04 18:52:49'),
(108, 11, 2, 'admin', 'delivered', '2025-07-04 18:52:49'),
(109, 6, 2, 'admin', 'delivered', '2025-07-04 18:54:53'),
(110, 7, 2, 'admin', 'delivered', '2025-07-04 18:54:53'),
(111, 9, 2, 'admin', 'delivered', '2025-07-04 18:54:53'),
(112, 11, 2, 'admin', 'delivered', '2025-07-04 18:54:53'),
(113, 6, 2, 'admin', 'delivered', '2025-07-04 18:56:56'),
(114, 7, 2, 'admin', 'delivered', '2025-07-04 18:56:56'),
(115, 9, 2, 'admin', 'delivered', '2025-07-04 18:56:56'),
(116, 11, 2, 'admin', 'delivered', '2025-07-04 18:56:56'),
(117, 6, 2, 'admin', 'delivered', '2025-07-04 18:59:00'),
(118, 7, 2, 'admin', 'delivered', '2025-07-04 18:59:00'),
(119, 9, 2, 'admin', 'delivered', '2025-07-04 18:59:00'),
(120, 11, 2, 'admin', 'delivered', '2025-07-04 18:59:00'),
(121, 6, 2, 'admin', 'delivered', '2025-07-04 19:01:04'),
(122, 7, 2, 'admin', 'delivered', '2025-07-04 19:01:04'),
(123, 9, 2, 'admin', 'delivered', '2025-07-04 19:01:04'),
(124, 11, 2, 'admin', 'delivered', '2025-07-04 19:01:04'),
(125, 6, 2, 'admin', 'delivered', '2025-07-04 19:03:08'),
(126, 7, 2, 'admin', 'delivered', '2025-07-04 19:03:08'),
(127, 9, 2, 'admin', 'delivered', '2025-07-04 19:03:08'),
(128, 11, 2, 'admin', 'delivered', '2025-07-04 19:03:08'),
(129, 6, 2, 'admin', 'delivered', '2025-07-04 19:05:11'),
(130, 7, 2, 'admin', 'delivered', '2025-07-04 19:05:11'),
(131, 9, 2, 'admin', 'delivered', '2025-07-04 19:05:11'),
(132, 11, 2, 'admin', 'delivered', '2025-07-04 19:05:11'),
(133, 6, 2, 'admin', 'delivered', '2025-07-04 19:07:14'),
(134, 7, 2, 'admin', 'delivered', '2025-07-04 19:07:14'),
(135, 9, 2, 'admin', 'delivered', '2025-07-04 19:07:14'),
(136, 11, 2, 'admin', 'delivered', '2025-07-04 19:07:14'),
(137, 6, 2, 'admin', 'delivered', '2025-07-04 19:09:18'),
(138, 7, 2, 'admin', 'delivered', '2025-07-04 19:09:18'),
(139, 9, 2, 'admin', 'delivered', '2025-07-04 19:09:18'),
(140, 11, 2, 'admin', 'delivered', '2025-07-04 19:09:18'),
(141, 6, 2, 'admin', 'delivered', '2025-07-04 19:11:21'),
(142, 7, 2, 'admin', 'delivered', '2025-07-04 19:11:21'),
(143, 9, 2, 'admin', 'delivered', '2025-07-04 19:11:21'),
(144, 11, 2, 'admin', 'delivered', '2025-07-04 19:11:21'),
(145, 6, 2, 'admin', 'delivered', '2025-07-04 19:13:25'),
(146, 7, 2, 'admin', 'delivered', '2025-07-04 19:13:25'),
(147, 9, 2, 'admin', 'delivered', '2025-07-04 19:13:25'),
(148, 11, 2, 'admin', 'delivered', '2025-07-04 19:13:25'),
(149, 6, 2, 'admin', 'delivered', '2025-07-04 19:15:28'),
(150, 7, 2, 'admin', 'delivered', '2025-07-04 19:15:28'),
(151, 9, 2, 'admin', 'delivered', '2025-07-04 19:15:28'),
(152, 11, 2, 'admin', 'delivered', '2025-07-04 19:15:28'),
(153, 6, 2, 'admin', 'delivered', '2025-07-04 19:17:31'),
(154, 7, 2, 'admin', 'delivered', '2025-07-04 19:17:31'),
(155, 9, 2, 'admin', 'delivered', '2025-07-04 19:17:31'),
(156, 11, 2, 'admin', 'delivered', '2025-07-04 19:17:31'),
(157, 6, 2, 'admin', 'delivered', '2025-07-04 19:19:34'),
(158, 7, 2, 'admin', 'delivered', '2025-07-04 19:19:34'),
(159, 9, 2, 'admin', 'delivered', '2025-07-04 19:19:34'),
(160, 11, 2, 'admin', 'delivered', '2025-07-04 19:19:34'),
(161, 6, 2, 'admin', 'delivered', '2025-07-04 19:21:38'),
(162, 7, 2, 'admin', 'delivered', '2025-07-04 19:21:38'),
(163, 9, 2, 'admin', 'delivered', '2025-07-04 19:21:38'),
(164, 11, 2, 'admin', 'delivered', '2025-07-04 19:21:38'),
(165, 6, 2, 'admin', 'delivered', '2025-07-04 19:23:41'),
(166, 7, 2, 'admin', 'delivered', '2025-07-04 19:23:41'),
(167, 9, 2, 'admin', 'delivered', '2025-07-04 19:23:41'),
(168, 11, 2, 'admin', 'delivered', '2025-07-04 19:23:41'),
(169, 6, 2, 'admin', 'delivered', '2025-07-04 19:25:45'),
(170, 7, 2, 'admin', 'delivered', '2025-07-04 19:25:45'),
(171, 9, 2, 'admin', 'delivered', '2025-07-04 19:25:45'),
(172, 11, 2, 'admin', 'delivered', '2025-07-04 19:25:45'),
(173, 6, 2, 'admin', 'delivered', '2025-07-04 19:27:48'),
(174, 7, 2, 'admin', 'delivered', '2025-07-04 19:27:48'),
(175, 9, 2, 'admin', 'delivered', '2025-07-04 19:27:48'),
(176, 11, 2, 'admin', 'delivered', '2025-07-04 19:27:48'),
(177, 6, 2, 'admin', 'delivered', '2025-07-04 19:29:52'),
(178, 7, 2, 'admin', 'delivered', '2025-07-04 19:29:52'),
(179, 9, 2, 'admin', 'delivered', '2025-07-04 19:29:52'),
(180, 11, 2, 'admin', 'delivered', '2025-07-04 19:29:52'),
(181, 6, 2, 'admin', 'delivered', '2025-07-04 19:31:55'),
(182, 7, 2, 'admin', 'delivered', '2025-07-04 19:31:55'),
(183, 9, 2, 'admin', 'delivered', '2025-07-04 19:31:55'),
(184, 11, 2, 'admin', 'delivered', '2025-07-04 19:31:55'),
(185, 6, 2, 'admin', 'delivered', '2025-07-04 19:33:58'),
(186, 7, 2, 'admin', 'delivered', '2025-07-04 19:33:58'),
(187, 9, 2, 'admin', 'delivered', '2025-07-04 19:33:58'),
(188, 11, 2, 'admin', 'delivered', '2025-07-04 19:33:58'),
(189, 6, 2, 'admin', 'delivered', '2025-07-04 19:36:02'),
(190, 7, 2, 'admin', 'delivered', '2025-07-04 19:36:02'),
(191, 9, 2, 'admin', 'delivered', '2025-07-04 19:36:02'),
(192, 11, 2, 'admin', 'delivered', '2025-07-04 19:36:02'),
(193, 6, 2, 'admin', 'delivered', '2025-07-04 19:38:05'),
(194, 7, 2, 'admin', 'delivered', '2025-07-04 19:38:05'),
(195, 9, 2, 'admin', 'delivered', '2025-07-04 19:38:05'),
(196, 11, 2, 'admin', 'delivered', '2025-07-04 19:38:05'),
(197, 6, 2, 'admin', 'delivered', '2025-07-04 19:40:08'),
(198, 7, 2, 'admin', 'delivered', '2025-07-04 19:40:08'),
(199, 9, 2, 'admin', 'delivered', '2025-07-04 19:40:08'),
(200, 11, 2, 'admin', 'delivered', '2025-07-04 19:40:08'),
(201, 6, 2, 'admin', 'delivered', '2025-07-04 19:42:12'),
(202, 7, 2, 'admin', 'delivered', '2025-07-04 19:42:12'),
(203, 9, 2, 'admin', 'delivered', '2025-07-04 19:42:12'),
(204, 11, 2, 'admin', 'delivered', '2025-07-04 19:42:12'),
(205, 6, 2, 'admin', 'delivered', '2025-07-04 19:44:15'),
(206, 7, 2, 'admin', 'delivered', '2025-07-04 19:44:15'),
(207, 9, 2, 'admin', 'delivered', '2025-07-04 19:44:15'),
(208, 11, 2, 'admin', 'delivered', '2025-07-04 19:44:15'),
(209, 6, 2, 'admin', 'delivered', '2025-07-04 19:46:19'),
(210, 7, 2, 'admin', 'delivered', '2025-07-04 19:46:19'),
(211, 9, 2, 'admin', 'delivered', '2025-07-04 19:46:19'),
(212, 11, 2, 'admin', 'delivered', '2025-07-04 19:46:19'),
(213, 6, 2, 'admin', 'delivered', '2025-07-04 19:48:22'),
(214, 7, 2, 'admin', 'delivered', '2025-07-04 19:48:22'),
(215, 9, 2, 'admin', 'delivered', '2025-07-04 19:48:22'),
(216, 11, 2, 'admin', 'delivered', '2025-07-04 19:48:22'),
(217, 6, 2, 'admin', 'delivered', '2025-07-04 19:50:25'),
(218, 7, 2, 'admin', 'delivered', '2025-07-04 19:50:25'),
(219, 9, 2, 'admin', 'delivered', '2025-07-04 19:50:25'),
(220, 11, 2, 'admin', 'delivered', '2025-07-04 19:50:25'),
(221, 6, 2, 'admin', 'delivered', '2025-07-04 19:52:29'),
(222, 7, 2, 'admin', 'delivered', '2025-07-04 19:52:29'),
(223, 9, 2, 'admin', 'delivered', '2025-07-04 19:52:29'),
(224, 11, 2, 'admin', 'delivered', '2025-07-04 19:52:29'),
(225, 6, 2, 'admin', 'delivered', '2025-07-04 19:54:32'),
(226, 7, 2, 'admin', 'delivered', '2025-07-04 19:54:32'),
(227, 9, 2, 'admin', 'delivered', '2025-07-04 19:54:32'),
(228, 11, 2, 'admin', 'delivered', '2025-07-04 19:54:32'),
(229, 6, 2, 'admin', 'delivered', '2025-07-04 19:56:36'),
(230, 7, 2, 'admin', 'delivered', '2025-07-04 19:56:36'),
(231, 9, 2, 'admin', 'delivered', '2025-07-04 19:56:36'),
(232, 11, 2, 'admin', 'delivered', '2025-07-04 19:56:36'),
(233, 6, 2, 'admin', 'delivered', '2025-07-04 19:58:39'),
(234, 7, 2, 'admin', 'delivered', '2025-07-04 19:58:39'),
(235, 9, 2, 'admin', 'delivered', '2025-07-04 19:58:39'),
(236, 11, 2, 'admin', 'delivered', '2025-07-04 19:58:39'),
(237, 6, 2, 'admin', 'delivered', '2025-07-04 20:00:42'),
(238, 7, 2, 'admin', 'delivered', '2025-07-04 20:00:42'),
(239, 9, 2, 'admin', 'delivered', '2025-07-04 20:00:42'),
(240, 11, 2, 'admin', 'delivered', '2025-07-04 20:00:42'),
(241, 6, 2, 'admin', 'delivered', '2025-07-04 20:02:46'),
(242, 7, 2, 'admin', 'delivered', '2025-07-04 20:02:46'),
(243, 9, 2, 'admin', 'delivered', '2025-07-04 20:02:46'),
(244, 11, 2, 'admin', 'delivered', '2025-07-04 20:02:46'),
(245, 6, 2, 'admin', 'delivered', '2025-07-04 20:04:49'),
(246, 7, 2, 'admin', 'delivered', '2025-07-04 20:04:49'),
(247, 9, 2, 'admin', 'delivered', '2025-07-04 20:04:49'),
(248, 11, 2, 'admin', 'delivered', '2025-07-04 20:04:49'),
(249, 6, 2, 'admin', 'delivered', '2025-07-04 20:06:53'),
(250, 7, 2, 'admin', 'delivered', '2025-07-04 20:06:53'),
(251, 9, 2, 'admin', 'delivered', '2025-07-04 20:06:53'),
(252, 11, 2, 'admin', 'delivered', '2025-07-04 20:06:53'),
(253, 6, 2, 'admin', 'delivered', '2025-07-04 20:08:56'),
(254, 7, 2, 'admin', 'delivered', '2025-07-04 20:08:56'),
(255, 9, 2, 'admin', 'delivered', '2025-07-04 20:08:56'),
(256, 11, 2, 'admin', 'delivered', '2025-07-04 20:08:56'),
(257, 6, 2, 'admin', 'delivered', '2025-07-04 20:11:00'),
(258, 7, 2, 'admin', 'delivered', '2025-07-04 20:11:00'),
(259, 9, 2, 'admin', 'delivered', '2025-07-04 20:11:00'),
(260, 11, 2, 'admin', 'delivered', '2025-07-04 20:11:00'),
(261, 6, 2, 'admin', 'delivered', '2025-07-04 20:13:05'),
(262, 7, 2, 'admin', 'delivered', '2025-07-04 20:13:05'),
(263, 9, 2, 'admin', 'delivered', '2025-07-04 20:13:05'),
(264, 11, 2, 'admin', 'delivered', '2025-07-04 20:13:05'),
(265, 6, 2, 'admin', 'delivered', '2025-07-04 20:15:08'),
(266, 7, 2, 'admin', 'delivered', '2025-07-04 20:15:08'),
(267, 9, 2, 'admin', 'delivered', '2025-07-04 20:15:08'),
(268, 11, 2, 'admin', 'delivered', '2025-07-04 20:15:08'),
(269, 6, 2, 'admin', 'delivered', '2025-07-04 20:17:12'),
(270, 7, 2, 'admin', 'delivered', '2025-07-04 20:17:12'),
(271, 9, 2, 'admin', 'delivered', '2025-07-04 20:17:12'),
(272, 11, 2, 'admin', 'delivered', '2025-07-04 20:17:12'),
(273, 6, 2, 'admin', 'delivered', '2025-07-04 20:19:16'),
(274, 7, 2, 'admin', 'delivered', '2025-07-04 20:19:16'),
(275, 9, 2, 'admin', 'delivered', '2025-07-04 20:19:16'),
(276, 11, 2, 'admin', 'delivered', '2025-07-04 20:19:16'),
(277, 15, 13, 'employee', 'delivered', '2025-07-25 13:00:28');

-- --------------------------------------------------------

--
-- Table structure for table `record_categories`
--

CREATE TABLE `record_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `record_categories`
--

INSERT INTO `record_categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Administrative', 'General office administration documents'),
(2, 'Financial', 'Accounting and financial records'),
(3, 'Personnel', 'Employee records and HR documents'),
(4, 'Legal', 'Contracts and legal agreements'),
(5, 'Project', 'Engineering and construction projects'),
(6, 'Historical', 'Archival and historical documents');

-- --------------------------------------------------------

--
-- Table structure for table `upload_files`
--

CREATE TABLE `upload_files` (
  `id` int(11) NOT NULL,
  `doc_no` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `upload_date` date NOT NULL,
  `creation_date` date NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author` text NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_hash` varchar(32) DEFAULT NULL,
  `uploaded_by_type` enum('admin','employee') DEFAULT NULL,
  `retention_years` int(11) DEFAULT 7,
  `status` varchar(20) DEFAULT 'Active',
  `department_id` int(11) DEFAULT NULL,
  `uploaded_by_admin` int(11) DEFAULT NULL,
  `uploaded_by_employee` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `upload_files`
--

INSERT INTO `upload_files` (`id`, `doc_no`, `title`, `upload_date`, `creation_date`, `category_id`, `author`, `file_path`, `file_hash`, `uploaded_by_type`, `retention_years`, `status`, `department_id`, `uploaded_by_admin`, `uploaded_by_employee`) VALUES
(21, 'HU-001', 'Staff Promotion Policy 2023', '2025-07-25', '2025-07-25', NULL, 'Sarah', 'uploads/68836d2eb6347_UMAR 19TH JULY 2025 (1).xlsx', 'bbcae8a0e015413d2563fc63f05d8af0', 'admin', 5, 'Active', 1, NULL, NULL),
(22, 'AC-001', 'Q3 Financial Expenditure Report', '2025-07-25', '2025-07-25', NULL, 'Accounts Team', 'uploads/6883728692f3c_Attendance list-report.xls', '01053ae09173d78b1f0acc84ca2fd7a4', 'admin', 5, 'Active', 2, NULL, NULL),
(23, 'AC-002', 'Annual Budget Allocation Report', '2025-07-25', '2025-07-25', NULL, 'Alice', 'uploads/688398c3294ed_LONG XIANG Truck Balancing 222222 (1) (3).xlsx', 'd46ef9ee2e27bc10191ac9ad10720137', 'admin', 5, 'Active', 2, NULL, NULL),
(26, 'UN-001', 'Kampala-Masaka Highway Construction Plan', '2025-07-25', '2025-07-25', NULL, 'UNRA Engineering', 'uploads/6883cafbd73fb.pdf', '0', '', 5, 'Active', 3, NULL, 14),
(27, 'RE-001', 'Incoming Correspondence Register', '2025-07-25', '2025-07-25', NULL, 'Registry Office', 'uploads/6883cb7aa1634.pdf', '8950', '', 5, 'Active', 4, 2, NULL),
(28, 'UN-002', 'Ministry Organizational Structure 1995', '2025-07-25', '2025-07-25', NULL, 'UNRA Engineering', 'uploads/6883cdda37bc7.pdf', '8', '', 5, 'Active', 3, NULL, 19);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`role_id`, `role_name`, `permissions`) VALUES
(1, 'System Administrator', '{\"users\": [\"create\", \"read\", \"update\", \"delete\"], \"records\": [\"create\", \"read\", \"update\", \"delete\"], \"system\": [\"configure\"], \"messages\": [\"create\", \"read\", \"update\", \"delete\", \"manage\"]}'),
(2, 'Records Manager', '{\"records\": [\"create\", \"read\", \"update\", \"delete\"], \"reports\": true, \"messages\": [\"create\", \"read\", \"update\", \"delete\"], \"audit_log\": true}'),
(3, 'Department Head', '{\"records\": [\"create\", \"read\", \"update\"], \"reports\": [\"generate\"], \"messages\": [\"create\", \"read\", \"update\"]}'),
(4, 'Regular User', '{\"records\": [\"create\", \"read\"], \"messages\": [\"create\", \"read\"], \"reports\": true, \"audit_log\": true}'),
(5, 'Auditor', '{\"records\": [\"read\"], \"audit\": [\"read\"], \"messages\": [\"read\"]}');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `admin_user` (`admin_user`) USING HASH,
  ADD KEY `role_id` (`role_id`),
  ADD KEY `fk_admin_department` (`department_id`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`);

--
-- Indexes for table `attachment_downloads`
--
ALTER TABLE `attachment_downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attachment` (`attachment_id`),
  ADD KEY `idx_user` (`user_id`,`user_type`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `audit_log_ibfk_2` (`document_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `conversation_id` (`conversation_id`,`user_id`,`user_type`),
  ADD KEY `idx_user_participation` (`user_id`,`user_type`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `disposition_types`
--
ALTER TABLE `disposition_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_detentions`
--
ALTER TABLE `document_detentions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`),
  ADD KEY `detained_by` (`detained_by`);

--
-- Indexes for table `document_disposals`
--
ALTER TABLE `document_disposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disposition_type_id` (`disposition_type_id`),
  ADD KEY `disposed_by` (`disposed_by`),
  ADD KEY `verification_officer` (`verification_officer`),
  ADD KEY `document_disposals_ibfk_1` (`document_id`);

--
-- Indexes for table `employee_user`
--
ALTER TABLE `employee_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_address` (`email_address`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `history_log`
--
ALTER TABLE `history_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation_messages` (`conversation_id`);

--
-- Indexes for table `message_status`
--
ALTER TABLE `message_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`),
  ADD KEY `idx_user_message_status` (`user_id`,`user_type`);

--
-- Indexes for table `record_categories`
--
ALTER TABLE `record_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `upload_files`
--
ALTER TABLE `upload_files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doc_no` (`doc_no`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `fk_upload_files_department` (`department_id`),
  ADD KEY `fk_uploaded_by_admin` (`uploaded_by_admin`),
  ADD KEY `fk_uploaded_by_employee` (`uploaded_by_employee`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attachment_downloads`
--
ALTER TABLE `attachment_downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `disposition_types`
--
ALTER TABLE `disposition_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `document_detentions`
--
ALTER TABLE `document_detentions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `document_disposals`
--
ALTER TABLE `document_disposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `employee_user`
--
ALTER TABLE `employee_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `history_log`
--
ALTER TABLE `history_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=799;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `message_status`
--
ALTER TABLE `message_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=278;

--
-- AUTO_INCREMENT for table `record_categories`
--
ALTER TABLE `record_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `upload_files`
--
ALTER TABLE `upload_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`),
  ADD CONSTRAINT `fk_admin_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attachment_downloads`
--
ALTER TABLE `attachment_downloads`
  ADD CONSTRAINT `attachment_downloads_ibfk_1` FOREIGN KEY (`attachment_id`) REFERENCES `attachments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_2` FOREIGN KEY (`document_id`) REFERENCES `upload_files` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_detentions`
--
ALTER TABLE `document_detentions`
  ADD CONSTRAINT `document_detentions_ibfk_2` FOREIGN KEY (`detained_by`) REFERENCES `admin` (`id`);

--
-- Constraints for table `document_disposals`
--
ALTER TABLE `document_disposals`
  ADD CONSTRAINT `document_disposals_ibfk_2` FOREIGN KEY (`disposition_type_id`) REFERENCES `disposition_types` (`id`),
  ADD CONSTRAINT `document_disposals_ibfk_3` FOREIGN KEY (`disposed_by`) REFERENCES `admin` (`id`),
  ADD CONSTRAINT `document_disposals_ibfk_4` FOREIGN KEY (`verification_officer`) REFERENCES `admin` (`id`);

--
-- Constraints for table `employee_user`
--
ALTER TABLE `employee_user`
  ADD CONSTRAINT `employee_user_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `employee_user_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_status`
--
ALTER TABLE `message_status`
  ADD CONSTRAINT `message_status_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `upload_files`
--
ALTER TABLE `upload_files`
  ADD CONSTRAINT `fk_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_upload_files_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_uploaded_by_admin` FOREIGN KEY (`uploaded_by_admin`) REFERENCES `admin` (`id`),
  ADD CONSTRAINT `fk_uploaded_by_employee` FOREIGN KEY (`uploaded_by_employee`) REFERENCES `employee_user` (`id`),
  ADD CONSTRAINT `upload_files_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `record_categories` (`category_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
