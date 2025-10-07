-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 05, 2025 at 05:07 PM
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
-- Database: `disaster_monitoring`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `active_disasters_summary`
-- (See below for the actual view)
--
CREATE TABLE `active_disasters_summary` (
`disaster_id` int(11)
,`tracking_id` varchar(20)
,`disaster_name` varchar(200)
,`type_name` varchar(100)
,`severity_level` varchar(20)
,`severity_display` varchar(100)
,`city` varchar(100)
,`status` enum('ON GOING','IN PROGRESS','COMPLETED')
,`priority` enum('low','medium','high','critical')
,`reported_at` timestamp
,`escalation_deadline` timestamp
,`assigned_lgu` varchar(100)
,`assigned_user` varchar(101)
,`hours_since_report` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 02:10:36'),
(2, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 02:11:04'),
(3, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 02:17:24'),
(4, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 02:26:08'),
(5, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 02:26:18'),
(6, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 02:27:24'),
(7, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.104.1 Chrome/138.0.7204.235 Electron/37.3.1 Safari/537.36', '2025-09-27 02:55:40'),
(8, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.104.1 Chrome/138.0.7204.235 Electron/37.3.1 Safari/537.36', '2025-09-27 02:55:54'),
(9, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 03:04:42'),
(10, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.104.1 Chrome/138.0.7204.235 Electron/37.3.1 Safari/537.36', '2025-09-27 03:09:06'),
(11, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 03:09:46'),
(12, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 03:14:12'),
(13, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 03:18:54'),
(14, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 03:19:41'),
(15, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 03:50:48'),
(16, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 03:51:54'),
(17, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 03:55:34'),
(18, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 03:56:08'),
(19, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 04:03:37'),
(20, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 04:04:33'),
(21, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:01:35'),
(22, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:27:44'),
(23, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:28:02'),
(24, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:30:53'),
(25, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:31:18'),
(26, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:34:54'),
(27, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:41:01'),
(28, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:40:43'),
(29, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 05:36:37'),
(30, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 05:37:38'),
(31, 2, 'login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-09-28 05:40:47'),
(32, 2, 'login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-09-28 10:55:14'),
(33, 2, 'login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-01 08:54:23'),
(34, 3, 'register', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:44:02'),
(35, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:44:30'),
(36, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:44:42'),
(37, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:44:48'),
(38, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:45:03'),
(39, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:46:56'),
(40, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:47:02'),
(41, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:47:08'),
(42, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:47:11'),
(43, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:50:08'),
(44, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:51:56'),
(45, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:53:44'),
(46, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:55:03'),
(47, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:56:00'),
(48, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:56:03'),
(49, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:56:12'),
(50, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 23:57:59'),
(51, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 00:01:10'),
(52, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 00:07:01'),
(53, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 00:07:34'),
(54, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 00:07:42'),
(55, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 00:07:54'),
(56, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 00:22:04'),
(57, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 00:24:42'),
(58, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 00:26:56'),
(59, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 00:27:19'),
(60, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 00:44:50'),
(61, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 00:50:06'),
(62, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 01:37:21'),
(63, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 01:37:35'),
(64, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 01:51:58'),
(65, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 02:00:58'),
(66, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 02:25:38'),
(67, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 02:37:51'),
(68, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 02:43:13'),
(69, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:16:27'),
(70, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:16:36'),
(71, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:17:10'),
(72, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:22:02'),
(73, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 04:41:05'),
(74, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 04:59:28'),
(75, 1, 'login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-05 05:21:29'),
(76, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 05:51:41'),
(77, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 06:19:54'),
(78, 1, 'login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-05 07:55:17'),
(79, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 09:16:06'),
(80, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 09:21:54'),
(81, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 09:45:13'),
(82, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 10:38:53'),
(83, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 11:11:12'),
(84, 1, 'login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-05 12:02:16'),
(85, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:02:55'),
(86, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:32:52'),
(87, 3, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:52:31'),
(88, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:56:35');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `announcement_type` enum('emergency','warning','info','update','all_clear') NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `target_audience` enum('public','lgus','responders','specific_area') DEFAULT 'public',
  `target_areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_areas`)),
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disasters`
--

CREATE TABLE `disasters` (
  `disaster_id` int(11) NOT NULL,
  `tracking_id` varchar(20) NOT NULL,
  `disaster_name` varchar(200) NOT NULL,
  `type_id` int(11) NOT NULL,
  `severity_level` varchar(20) NOT NULL,
  `severity_display` varchar(100) DEFAULT NULL,
  `severity_score` decimal(3,2) DEFAULT 1.00,
  `address` text NOT NULL,
  `purok` varchar(100) DEFAULT NULL,
  `house_no` varchar(50) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT 'Philippines',
  `state` varchar(100) DEFAULT 'Philippines',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `landmark` varchar(200) DEFAULT NULL,
  `reporter_name` varchar(100) DEFAULT NULL,
  `reporter_phone` varchar(20) NOT NULL,
  `alternate_contact` varchar(20) DEFAULT NULL,
  `description` text NOT NULL,
  `immediate_needs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`immediate_needs`)),
  `current_situation` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('ON GOING','IN PROGRESS','COMPLETED') NOT NULL DEFAULT 'ON GOING',
  `assigned_lgu_id` int(11) DEFAULT NULL,
  `assigned_user_id` int(11) DEFAULT NULL,
  `reported_by_user_id` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `escalation_deadline` timestamp NULL DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `source` enum('web_form','mobile_app','hotline','social_media','official') DEFAULT 'web_form',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disasters`
--

INSERT INTO `disasters` (`disaster_id`, `tracking_id`, `disaster_name`, `type_id`, `severity_level`, `severity_display`, `severity_score`, `address`, `purok`, `house_no`, `city`, `province`, `state`, `latitude`, `longitude`, `landmark`, `reporter_name`, `reporter_phone`, `alternate_contact`, `description`, `immediate_needs`, `current_situation`, `image_path`, `status`, `assigned_lgu_id`, `assigned_user_id`, `reported_by_user_id`, `priority`, `reported_at`, `acknowledged_at`, `resolved_at`, `escalation_deadline`, `is_verified`, `source`, `created_at`, `updated_at`) VALUES
(1, 'DM20250927-23CCE7', '12hr no power', 20, 'red-3', 'Widespread power loss', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046166', '', '12hr no power', '[]', '', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-09-27 03:03:46', NULL, '2025-09-27 03:35:40', NULL, 0, 'web_form', '2025-09-27 03:03:46', '2025-10-05 00:26:27'),
(2, 'DM20250927-75065C', 'Partial Building Collapse on 3rd Floor', 20, 'red-1', 'Critical situations', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046168', '', 'Partial structural collapse reported in residential building. Multiple families affected, immediate evacuation required.', '[\"medical_assistance\",\"rescue\"]', 'some are dead', NULL, 'COMPLETED', 1, 1, NULL, 'high', '2025-09-27 05:03:35', '2025-09-27 05:28:00', '2025-09-28 07:23:28', NULL, 0, 'web_form', '2025-09-27 05:03:35', '2025-10-01 09:54:51'),
(3, 'DM20250927-B8AEE7', 'Im under the lava', 20, 'red-2', 'Heavy devastation', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046169', '', 'Im under the lava', '[\"medical_assistance\",\"shelter\",\"rescue\",\"transportation\",\"security\"]', 'too much ash fall and hard to breath', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-09-27 05:47:55', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 05:47:55', '2025-10-05 00:26:27'),
(4, 'DM20250927-ABA233', 'Test emergency report', 20, 'orange-2', 'Minor structural damage', 1.00, 'Test Location, Quezon City', NULL, NULL, 'Test Location', 'Philippines', 'Philippines', NULL, NULL, '', '', '09123456789', '', 'Test emergency report', '[]', '', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-27 05:54:50', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 05:54:50', '2025-10-01 09:07:14'),
(5, 'DM20250927-CB5364', 'Im toxic', 20, 'red-1', 'Critical situations', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046166', '', 'Im toxic', '[\"medical_assistance\"]', 'Toxic waste pour on my skin and i drink some', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-09-27 05:56:28', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 05:56:28', '2025-10-05 00:26:27'),
(6, 'DM20250927-1694F3', 'Another test emergency report', 20, 'red-2', 'Heavy devastation', 1.00, 'Test Location 2, Manila', NULL, NULL, 'Test Location 2', 'Philippines', 'Philippines', NULL, NULL, '', '', '09876543210', '', 'Another test emergency report', '[]', '', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-27 05:57:21', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 05:57:21', '2025-10-01 09:07:14'),
(7, 'DM20250927-1250E8', 'Im toxic', 20, 'red-1', 'Critical situations', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046166', '', 'Im toxic', '[\"medical_assistance\"]', 'Toxic waste pour on my skin and i drink some', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-09-27 06:00:01', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 06:00:01', '2025-10-05 00:26:27'),
(8, 'DM20250927-2F3E8F', 'Im toxic', 20, 'red-1', 'Critical situations', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046166', '', 'Im toxic', '[\"medical_assistance\"]', 'Toxic waste pour on my skin and i drink some', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-09-27 06:00:02', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 06:00:02', '2025-10-05 00:26:27'),
(9, 'DM20250927-EE6AED', 'Testing auto-track functionality', 20, 'orange-3', 'Partially accessible roads', 1.00, 'Test Auto-Track Location, Quezon City', NULL, NULL, 'Test Auto-Track Location', 'Philippines', 'Philippines', NULL, NULL, '', '', '09111222333', '', 'Testing auto-track functionality', '[]', '', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-27 06:07:26', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 06:07:26', '2025-10-01 09:07:14'),
(10, 'DM20250927-89EA92', 'Testing complete auto-track workflow', 20, 'red-1', 'Critical situations', 1.00, 'Final Test Location, Manila', NULL, NULL, 'Final Test Location', 'Philippines', 'Philippines', NULL, NULL, '', '', '09444555666', '', 'Testing complete auto-track workflow', '[]', '', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-27 06:11:04', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 06:11:04', '2025-10-01 09:07:14'),
(11, 'DM20250927-80C45A', 'Im toxic', 20, 'red-1', 'Critical situations', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046166', '', 'Im toxic', '[\"medical_assistance\"]', 'Toxic waste pour on my skin and i drink some', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-09-27 06:11:52', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 06:11:52', '2025-10-05 00:26:27'),
(12, 'DM20250927-4CD3E6', 'help', 20, 'green-2', 'Intact homes & accessible roads', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046163', '', 'help', '[\"medical_assistance\"]', 'im under the wall', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-27 06:30:44', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 06:30:44', '2025-10-01 09:07:14'),
(13, 'DM20250927-E916FC', 'help', 20, 'green-2', 'Intact homes & accessible roads', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046163', '', 'help', '[\"medical_assistance\"]', 'im under the wall', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-27 06:31:26', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 06:31:26', '2025-10-01 09:07:14'),
(14, 'DM20250927-DDD3FC', 'help', 20, 'green-2', 'Intact homes & accessible roads', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046163', '', 'help', '[\"medical_assistance\"]', 'im under the wall', 'uploads/emergency_images/emergency_1758954861_68d7856ddd40c.png', 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-27 06:34:21', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 06:34:21', '2025-10-01 09:07:14'),
(15, 'DM20250927-E19102', 'help', 20, 'green-2', 'Intact homes & accessible roads', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046163', '', 'help', '[\"medical_assistance\"]', 'im under the wall', 'uploads/emergency_images/emergency_1758954878_68d7857e19112.png', 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-27 06:34:38', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 06:34:38', '2025-10-01 09:07:14'),
(16, 'DM20250927-D7A313', 'help', 20, 'green-2', 'Intact homes & accessible roads', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046163', '', 'help', '[\"medical_assistance\"]', 'im under the wall', 'uploads/emergency_images/emergency_1758954925_68d785ad7a322.png', 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-27 06:35:25', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 06:35:25', '2025-10-01 09:07:14'),
(17, 'DM20250927-1528FF', 'help', 20, 'green-2', 'Intact homes & accessible roads', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', NULL, NULL, 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046163', '', 'help', '[\"medical_assistance\"]', 'im under the wall', 'uploads/emergency_images/emergency_1758955009_68d786015290e.png', 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-27 06:36:49', NULL, NULL, NULL, 0, 'web_form', '2025-09-27 06:36:49', '2025-10-01 09:07:14'),
(18, 'DM20250928-492C85', 'Accessibility', 20, 'red-2', 'Heavy devastation (Red)', 1.00, 'purok 3, Halang, Lipa City, Batangas, Philippines', NULL, NULL, 'Lipa City', 'Batangas', 'CALABARZON', NULL, NULL, NULL, NULL, '09151046166', NULL, 'I am under the water', NULL, NULL, NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-09-28 05:35:00', NULL, NULL, NULL, 0, 'web_form', '2025-09-28 05:35:00', '2025-10-05 00:26:27'),
(19, 'DM20250928-B9A812', 'Transport', 20, 'orange-2', 'Minor structural damage (Orange)', 1.00, 'purok 3, Halang, Lipa City, Batangas, Philippines', NULL, NULL, 'Lipa City', 'Batangas', 'CALABARZON', NULL, NULL, NULL, NULL, '09151046166', NULL, 'Im under the wheel', NULL, NULL, NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-09-28 05:41:31', NULL, NULL, NULL, 0, 'web_form', '2025-09-28 05:41:31', '2025-10-05 00:26:27'),
(20, 'DM20250928-20C3E6', 'Water', 20, 'orange-2', 'Minor structural damage (Orange)', 1.00, 'purok 5, Datu Esmael, Dasmariñas City, Cavite, Philippines', NULL, NULL, 'Dasmariñas City', 'Cavite', 'CALABARZON', NULL, NULL, NULL, NULL, '09151046169', NULL, 'im need water', NULL, NULL, NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-09-28 06:04:50', '2025-09-28 06:06:02', '2025-09-28 07:23:56', NULL, 0, 'web_form', '2025-09-28 06:04:50', '2025-10-05 00:26:27'),
(21, 'DM20250928-5490BA', 'Power', 20, 'orange-2', 'Minor structural damage (Orange)', 1.00, 'purok 2, Santo Domingo, Santa Rosa City, Laguna, Philippines', NULL, NULL, 'Santa Rosa City', 'Laguna', 'CALABARZON', NULL, NULL, NULL, NULL, '09151046169', NULL, 'i cant watch corn', NULL, NULL, NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-09-28 09:53:41', NULL, NULL, NULL, 0, 'web_form', '2025-09-28 09:53:41', '2025-10-05 00:26:27'),
(22, 'DM20250928-C4B14F', 'Power', 20, 'red-2', 'Heavy devastation', 1.00, 'purok 5, Paliparan III, Dasmariñas City, Cavite, Philippines', NULL, NULL, 'Dasmariñas City', 'Cavite', 'CALABARZON', NULL, NULL, 'Near the school', NULL, '09151046167', NULL, 'Im watching corn', NULL, '', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-09-28 09:57:16', NULL, NULL, NULL, 0, 'web_form', '2025-09-28 09:57:16', '2025-10-05 00:26:27'),
(23, 'DM20250928-944CF0', 'Power', 20, 'red-2', 'Heavy devastation', 1.00, 'purok 3, Halang, Lipa City, Batangas, Philippines', NULL, NULL, 'Lipa City', 'Batangas', 'CALABARZON', NULL, NULL, 'Near the school', NULL, '09151046120', NULL, 'im watching anime', '[\"electricity_restoration\"]', '', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-28 10:04:25', NULL, NULL, NULL, 0, 'web_form', '2025-09-28 10:04:25', '2025-10-01 09:07:14'),
(24, 'DM20250928-D1B583', 'Power', 20, 'orange-2', 'Minor structural damage', 1.00, 'purok 2, San Jose, Batangas City, Batangas, Philippines', NULL, NULL, 'Batangas City', 'Batangas', 'CALABARZON', NULL, NULL, 'Near the school', NULL, '09151046120', NULL, 'Im watching corn', '[\"electricity_restoration\"]', 'Im watching corn', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-28 10:08:29', NULL, NULL, NULL, 0, 'web_form', '2025-09-28 10:08:29', '2025-10-01 09:07:14'),
(25, 'DM20250928-5E7824', 'Home state', 20, 'green-2', 'Intact homes & accessible roads', 1.00, 'purok 3, IV-B (Malamig), San Pablo City, Laguna, Philippines', NULL, NULL, 'San Pablo City', 'Laguna', 'CALABARZON', NULL, NULL, 'Near the school', NULL, '09151046120', NULL, 'Im watching corn', '[\"medical_assistance\"]', 'hahha', 'uploads/emergency_images/emergency_1759054357_68d90a15e7832.png', 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-28 10:12:37', NULL, NULL, NULL, 0, 'web_form', '2025-09-28 10:12:37', '2025-10-01 09:07:14'),
(26, 'DM20250928-50A6EE', 'Accessibility', 20, 'orange-2', 'Minor structural damage', 1.00, 'purok 2, Langkiwa, Dasmariñas City, Cavite, Philippines', NULL, NULL, 'Dasmariñas City', 'Cavite', 'CALABARZON', NULL, NULL, 'near the school', NULL, '09151046168', NULL, 'haha', '[\"medical_assistance\"]', 'hehe', 'uploads/emergency_images/emergency_1759054629_68d90b250a6fb.png', 'ON GOING', NULL, NULL, NULL, 'medium', '2025-09-28 10:17:09', NULL, NULL, NULL, 0, 'web_form', '2025-09-28 10:17:09', '2025-10-01 09:07:14'),
(27, 'DM20251001-AB0164', 'Accessibility', 20, 'orange-2', 'Minor structural damage', 1.00, 'purok 2, San Agustin II, Dasmariñas City, Cavite, Philippines', NULL, NULL, 'Dasmariñas City', 'Cavite', 'CALABARZON', NULL, NULL, 'Near the school', NULL, '09151046168', NULL, 'hehe', '[\"food_water\"]', 'haha', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-01 02:33:14', NULL, NULL, NULL, 0, 'web_form', '2025-10-01 02:33:14', '2025-10-01 09:07:14'),
(28, 'DM20251001-512B87', 'Accessibility', 20, 'orange-2', 'Minor structural damage', 1.00, 'purok 2, San Agustin II, Dasmariñas City, Cavite, Philippines', NULL, NULL, 'Dasmariñas City', 'Cavite', 'CALABARZON', NULL, NULL, 'Near the school', NULL, '09151046168', NULL, 'hehe', '[\"food_water\"]', 'haha', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-01 02:35:49', NULL, NULL, NULL, 0, 'web_form', '2025-10-01 02:35:49', '2025-10-01 09:07:14'),
(29, 'DM20251001-BCD38B', 'Accessibility', 20, 'orange-2', 'Minor structural damage', 1.00, 'purok 2, San Agustin II, Dasmariñas City, Cavite, Philippines', NULL, NULL, 'Dasmariñas City', 'Cavite', 'CALABARZON', NULL, NULL, 'Near the school', NULL, '09151046168', NULL, 'hehe', '[\"food_water\"]', 'haha', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-01 02:35:55', NULL, NULL, NULL, 0, 'web_form', '2025-10-01 02:35:55', '2025-10-01 09:07:14'),
(30, 'DM20251001-A9351D', 'Accessibility', 20, 'orange-2', 'Minor structural damage', 1.00, 'purok 2, San Agustin II, Dasmariñas City, Cavite, Philippines', NULL, NULL, 'Dasmariñas City', 'Cavite', 'CALABARZON', NULL, NULL, 'Near the school', NULL, '09151046168', NULL, 'hehe', '[\"food_water\"]', 'haha', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-01 02:37:30', NULL, NULL, NULL, 0, 'web_form', '2025-10-01 02:37:30', '2025-10-01 09:07:14'),
(31, 'DM20251001-DE23DD', 'Accessibility', 20, 'orange-2', 'Minor structural damage', 1.00, 'purok 2, Navarro, General Trias City, Cavite, Philippines', NULL, NULL, 'General Trias City', 'Cavite', 'CALABARZON', NULL, NULL, 'near the school', NULL, '09151046169', NULL, 'hehe', '[\"food_water\"]', 'haha', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-10-01 04:50:53', NULL, NULL, NULL, 0, 'web_form', '2025-10-01 04:50:53', '2025-10-05 00:26:27'),
(32, 'DM20251001-62C937', 'Home state', 20, 'red-2', 'Heavy devastation', 1.00, 'purok 2, Bulacnin, Lipa City, Batangas, Philippines', NULL, NULL, 'Lipa City', 'Batangas', 'CALABARZON', NULL, NULL, 'near the school', NULL, '09151046163', NULL, 'hehe', '[\"food_water\"]', 'haha', 'uploads/emergency_images/emergency_1759295430_68dcb7c62c94a.png', 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-01 05:10:30', NULL, NULL, NULL, 0, 'web_form', '2025-10-01 05:10:30', '2025-10-01 09:07:14'),
(33, 'DM20251001-55047A', 'Home state', 20, 'green-2', 'Intact homes & accessible roads', 1.00, '231, purok 3, Tanzang Luma IV, Imus City, Cavite, CALABARZON, Philippines', 'purok 3', '231', 'Imus City', 'Cavite', 'CALABARZON', NULL, NULL, 'Near the school', NULL, '09151046166', NULL, 'hehe', '[\"internet_connection_services\"]', 'haha', NULL, 'COMPLETED', NULL, NULL, 3, 'medium', '2025-10-01 08:45:09', NULL, '2025-10-01 09:55:21', NULL, 0, 'web_form', '2025-10-01 08:45:09', '2025-10-05 00:26:27'),
(34, 'DM20251001-E09BA2', 'Accessibility', 20, 'red-2', 'Heavy devastation', 1.00, '231, purok 3, III-E (Tejeros), San Pablo City, Laguna, CALABARZON, Philippines', 'purok 3', '231', 'San Pablo City', 'Laguna', 'CALABARZON', NULL, NULL, 'Near the school', NULL, '09151046120', NULL, 'im under the water', '[\"shelter_repair_materials\",\"communication_services\",\"internet_connection_services\"]', 'help', 'uploads/emergency_images/emergency_1759308494_68dceace09bb7.png', 'COMPLETED', NULL, NULL, NULL, 'medium', '2025-10-01 08:48:14', NULL, '2025-10-01 09:55:15', NULL, 0, 'web_form', '2025-10-01 08:48:14', '2025-10-01 09:55:15'),
(35, 'DM20251001-6631E5', 'Accessibility', 20, 'green-2', 'Intact homes & accessible roads', 1.00, '231, purok 3, Dalisay, Alitagtag, Batangas, CALABARZON, Philippines', 'purok 3', '231', 'Alitagtag', 'Batangas', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046166', '09151046255', 'hehe', '[\"food_water\"]', 'haha', 'uploads/emergency_images/emergency_1759308822_68dcec16631f4.png', 'COMPLETED', NULL, NULL, 3, 'medium', '2025-10-01 08:53:42', NULL, '2025-10-01 09:55:06', NULL, 0, 'web_form', '2025-10-01 08:53:42', '2025-10-05 00:25:38'),
(36, 'DM20251001-660C81', 'Accessibility', 20, 'green-2', 'Intact homes & accessible roads', 1.00, '231, purok 3, Halang, Lipa City, Batangas, CALABARZON, Philippines', 'purok 3', '231', 'Lipa City', 'Batangas', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046166', '09151046255', 'how are u', '[\"electricity_restoration\"]', 'hello', 'uploads/emergency_images/emergency_1759309750_68dcefb660c91.png', 'COMPLETED', NULL, NULL, 3, 'medium', '2025-10-01 09:09:10', '2025-10-01 09:10:43', '2025-10-01 09:27:18', NULL, 0, 'web_form', '2025-10-01 09:09:10', '2025-10-05 00:25:38'),
(37, 'DM20251001-86EC40', 'Accessibility', 20, 'green-2', 'Intact homes & accessible roads', 1.00, '231, purok 3, Sampaloc IV, Dasmariñas City, Cavite, CALABARZON, Philippines', 'purok 3', '231', 'Dasmariñas City', 'Cavite', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046167', '09151046255', 'hehe', '[\"food_water\"]', 'haha', 'uploads/emergency_images/emergency_1759316152_68dd08b86ec50.png', 'ON GOING', NULL, NULL, 3, 'medium', '2025-10-01 10:55:52', NULL, NULL, NULL, 0, 'web_form', '2025-10-01 10:55:52', '2025-10-05 00:25:38'),
(38, 'DM20251001-90B5C4', 'Home state', 20, 'orange-2', 'Minor structural damage', 1.00, '231, purok 3, Dalahican, Lucena City, Quezon, CALABARZON, Philippines', 'purok 3', '231', 'Lucena City', 'Quezon', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046169', '09151046255', 'htthth', '[\"electricity_restoration\"]', 'hththth', 'uploads/emergency_images/emergency_1759316633_68dd0a990b5d7.png', 'ON GOING', NULL, NULL, 3, 'medium', '2025-10-01 11:03:53', NULL, NULL, NULL, 0, 'web_form', '2025-10-01 11:03:53', '2025-10-05 00:25:38'),
(39, 'DM20251005-0EEDCA', 'Accessibility', 20, 'orange-2', 'Minor structural damage', 1.00, '231, purok 3, Pooc, Santa Rosa City, Laguna, CALABARZON, Philippines', 'purok 3', '231', 'Santa Rosa City', 'Laguna', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046166', '', 'boi', '[\"electricity_restoration\"]', 'hehe', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-10-05 00:02:09', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 00:02:09', '2025-10-05 00:24:26'),
(40, 'DM20251005-B0E504', 'Home state', 20, 'orange-2', 'Minor structural damage', 1.00, '231, purok 3, Matina, Santa Rosa City, Laguna, CALABARZON, Philippines', 'purok 3', '231', 'Santa Rosa City', 'Laguna', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046166', '09678691260', 'some are injured', '[\"medical_assistance\",\"food_water\"]', 'need help asap', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-05 05:50:51', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 05:50:51', '2025-10-05 05:50:51'),
(41, 'DM20251005-FB7658', 'Accessibility', 20, 'red-2', 'Heavy devastation', 1.00, '231, purok 3, Pooc, Santa Rosa City, Laguna, CALABARZON, Philippines', 'purok 3', '231', 'Santa Rosa City', 'Laguna', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046166', '09678691260', 'help', '[\"medical_assistance\"]', 'help', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-10-05 05:52:31', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 05:52:31', '2025-10-05 05:52:31'),
(42, 'DM20251005-B6B218', 'Power', 20, 'red-2', 'Heavy devastation', 1.00, '231, purok 3, Halang, Lipa City, Batangas, CALABARZON, Philippines', 'purok 3', '231', 'Lipa City', 'Batangas', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046166', '09678691260', 'Help', '[\"electricity_restoration\"]', 'help', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-10-05 06:12:27', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 06:12:27', '2025-10-05 06:12:27'),
(43, 'DM20251005-B843D0', 'Accessibility', 20, 'orange-2', 'Minor structural damage', 1.00, '231, purok 3, Poblacion, Alitagtag, Batangas, CALABARZON, Philippines', 'purok 3', '231', 'Alitagtag', 'Batangas', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046166', '09678691260', 'help', '[\"medical_assistance\"]', 'help', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-10-05 06:16:11', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 06:16:11', '2025-10-05 06:16:11'),
(44, 'DM20251005-ED72CB', 'Home state', 20, 'green-2', 'Intact homes & accessible roads', 1.00, '231, purok 3, Isabang, Tayabas City, Quezon, CALABARZON, Philippines', 'purok 3', '231', 'Tayabas City', 'Quezon', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046166', '', 'help', '[\"medical_assistance\"]', 'help', NULL, 'ON GOING', NULL, NULL, 3, 'medium', '2025-10-05 06:17:34', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 06:17:34', '2025-10-05 06:17:34'),
(45, 'DM20251005-BA621C', 'Accessibility', 20, 'green-2', 'Intact homes & accessible roads', 1.00, '231, purok 3, San Antonio, Biñan City, Laguna, CALABARZON, Philippines', 'purok 3', '231', 'Biñan City', 'Laguna', 'CALABARZON', NULL, NULL, 'Near the school', '', '09151046166', '', 'test', '[\"communication_services\"]', 'test', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-05 06:40:11', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 06:40:11', '2025-10-05 06:40:11'),
(46, 'DM20251005-EBB14D', 'Home state', 20, 'red-2', 'Heavy devastation', 1.00, '231, purok 3, San Juan, Cainta, Rizal, CALABARZON, Philippines', 'purok 3', '231', 'Cainta', 'Rizal', 'CALABARZON', NULL, NULL, 'Near the school', '', '09151046166', '', 'my house is destroyed', '[\"shelter_repair_materials\"]', 'help', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-05 07:54:38', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 07:54:38', '2025-10-05 07:54:38'),
(47, 'DM20251005-342B41', 'Home state', 20, 'green-2', 'Intact homes & accessible roads', 1.00, '231, purok 3, San Juan, Cainta, Rizal, CALABARZON, Philippines', 'purok 3', '231', 'Cainta', 'Rizal', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046166', '09678691260', 'boi', '[\"shelter_repair_materials\"]', 'hehe', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-05 08:09:39', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 08:09:39', '2025-10-05 08:09:39'),
(48, 'DM20251005-77602A', 'Home state', 20, 'green-2', 'Intact homes & accessible roads', 1.00, '231, purok 3, Santa Cruz, Antipolo City, Rizal, CALABARZON, Philippines', 'purok 3', '231', 'Antipolo City', 'Rizal', 'CALABARZON', NULL, NULL, 'near the school', 'jhiro', '09151046166', '09678691260', 'world', '[\"shelter_repair_materials\"]', 'hello', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-05 08:11:19', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 08:11:19', '2025-10-05 08:11:19'),
(49, 'DM20251005-280017', 'Home state', 20, 'red-2', 'Heavy devastation', 1.00, '231, purok 3, Santa Ana, Taytay, Rizal, CALABARZON, Philippines', 'purok 3', '231', 'Taytay', 'Rizal', 'CALABARZON', NULL, NULL, 'Near the school', '', '09151046166', '', 'im under the water', '[\"medical_assistance\",\"shelter_repair_materials\"]', 'help', NULL, 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-05 08:14:10', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 08:14:10', '2025-10-05 08:14:10'),
(50, 'DM20251005-84C524', 'Accessibility', 20, 'red-2', 'Heavy devastation', 1.00, '231, purok 3, San Juan, Cainta, Rizal, CALABARZON, Philippines', 'purok 3', '231', 'Cainta', 'Rizal', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046166', '09151046255', 'me', '[\"communication_services\"]', 'help', 'uploads/emergency_images/emergency_1759652344_68e229f84c796.png', 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-05 08:19:04', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 08:19:04', '2025-10-05 08:19:04'),
(51, 'DM20251005-7D5BE1', 'Home state', 20, 'orange-2', 'Minor structural damage', 1.00, '231, purok 3, Ayaas, Tayabas City, Quezon, CALABARZON, Philippines', 'purok 3', '231', 'Tayabas City', 'Quezon', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046166', '09151046255', 'need one', '[\"shelter_repair_materials\"]', 'help', 'uploads/emergency_images/emergency_1759655431_68e23607d5ece.png', 'ON GOING', NULL, NULL, NULL, 'medium', '2025-10-05 09:10:31', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 09:10:31', '2025-10-05 09:10:31'),
(52, 'DM20251005-4421A3', 'Home state', 20, 'red-2', 'Heavy devastation', 1.00, '231, purok 3, San Isidro, Cainta, Rizal, CALABARZON, Philippines', 'purok 3', '231', 'Cainta', 'Rizal', 'CALABARZON', NULL, NULL, 'Near the school', 'jhiro', '09151046166', '09151046255', 'me', '[\"medical_assistance\",\"food_water\",\"shelter_repair_materials\"]', 'help', 'uploads/emergency_images/emergency_1759656036_68e2386442400.png', 'ON GOING', NULL, NULL, 3, 'medium', '2025-10-05 09:20:36', NULL, NULL, NULL, 0, 'web_form', '2025-10-05 09:20:36', '2025-10-05 09:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `disasters_backup_20250928_140244`
--

CREATE TABLE `disasters_backup_20250928_140244` (
  `disaster_id` int(11) NOT NULL DEFAULT 0,
  `tracking_id` varchar(20) NOT NULL,
  `disaster_name` varchar(200) NOT NULL,
  `type_id` int(11) NOT NULL,
  `severity_level` varchar(20) NOT NULL,
  `severity_display` varchar(100) DEFAULT NULL,
  `severity_score` decimal(3,2) DEFAULT 1.00,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT 'Philippines',
  `state` varchar(100) DEFAULT 'Philippines',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `landmark` varchar(200) DEFAULT NULL,
  `reporter_name` varchar(100) DEFAULT NULL,
  `reporter_phone` varchar(20) NOT NULL,
  `alternate_contact` varchar(20) DEFAULT NULL,
  `description` text NOT NULL,
  `people_affected` varchar(50) DEFAULT NULL,
  `immediate_needs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`immediate_needs`)),
  `current_situation` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','assigned','acknowledged','in_progress','resolved','closed','escalated') DEFAULT 'pending',
  `assigned_lgu_id` int(11) DEFAULT NULL,
  `assigned_user_id` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `escalation_deadline` timestamp NULL DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `source` enum('web_form','mobile_app','hotline','social_media','official') DEFAULT 'web_form',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disasters_backup_20250928_140244`
--

INSERT INTO `disasters_backup_20250928_140244` (`disaster_id`, `tracking_id`, `disaster_name`, `type_id`, `severity_level`, `severity_display`, `severity_score`, `address`, `city`, `province`, `state`, `latitude`, `longitude`, `landmark`, `reporter_name`, `reporter_phone`, `alternate_contact`, `description`, `people_affected`, `immediate_needs`, `current_situation`, `image_path`, `status`, `assigned_lgu_id`, `assigned_user_id`, `priority`, `reported_at`, `acknowledged_at`, `resolved_at`, `escalation_deadline`, `is_anonymous`, `is_verified`, `source`, `created_at`, `updated_at`) VALUES
(1, 'DM20250927-23CCE7', '12hr no power', 34, 'red-3', 'Widespread power loss', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046166', '', '12hr no power', '100+', '[]', '', NULL, 'closed', NULL, NULL, 'medium', '2025-09-27 03:03:46', NULL, '2025-09-27 03:35:40', NULL, 0, 0, 'web_form', '2025-09-27 03:03:46', '2025-09-27 05:51:14'),
(2, 'DM20250927-75065C', 'Partial Building Collapse on 3rd Floor', 23, 'red-1', 'Critical situations', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046168', '', 'Partial structural collapse reported in residential building. Multiple families affected, immediate evacuation required.', '6-10', '[\"medical_assistance\",\"rescue\"]', 'some are dead', NULL, 'in_progress', 1, 1, 'high', '2025-09-27 05:03:35', '2025-09-27 05:28:00', NULL, NULL, 0, 0, 'web_form', '2025-09-27 05:03:35', '2025-09-27 05:40:58'),
(3, 'DM20250927-B8AEE7', 'Im under the lava', 33, 'red-2', 'Heavy devastation', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046169', '', 'Im under the lava', '100+', '[\"medical_assistance\",\"shelter\",\"rescue\",\"transportation\",\"security\"]', 'too much ash fall and hard to breath', NULL, 'pending', NULL, NULL, 'medium', '2025-09-27 05:47:55', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 05:47:55', '2025-09-27 05:51:14'),
(4, 'DM20250927-ABA233', 'Test emergency report', 18, 'orange-2', 'Minor structural damage', 1.00, 'Test Location, Quezon City', 'Test Location', 'Philippines', 'Philippines', NULL, NULL, '', '', '09123456789', '', 'Test emergency report', '', '[]', '', NULL, 'pending', NULL, NULL, 'medium', '2025-09-27 05:54:50', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 05:54:50', '2025-09-27 05:54:50'),
(5, 'DM20250927-CB5364', 'Im toxic', 22, 'red-1', 'Critical situations', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046166', '', 'Im toxic', '11-25', '[\"medical_assistance\"]', 'Toxic waste pour on my skin and i drink some', NULL, 'pending', NULL, NULL, 'medium', '2025-09-27 05:56:28', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 05:56:28', '2025-09-27 05:56:28'),
(6, 'DM20250927-1694F3', 'Another test emergency report', 19, 'red-2', 'Heavy devastation', 1.00, 'Test Location 2, Manila', 'Test Location 2', 'Philippines', 'Philippines', NULL, NULL, '', '', '09876543210', '', 'Another test emergency report', '', '[]', '', NULL, 'pending', NULL, NULL, 'medium', '2025-09-27 05:57:21', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 05:57:21', '2025-09-27 05:57:21'),
(7, 'DM20250927-1250E8', 'Im toxic', 22, 'red-1', 'Critical situations', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046166', '', 'Im toxic', '11-25', '[\"medical_assistance\"]', 'Toxic waste pour on my skin and i drink some', NULL, 'pending', NULL, NULL, 'medium', '2025-09-27 06:00:01', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 06:00:01', '2025-09-27 06:00:01'),
(8, 'DM20250927-2F3E8F', 'Im toxic', 22, 'red-1', 'Critical situations', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046166', '', 'Im toxic', '11-25', '[\"medical_assistance\"]', 'Toxic waste pour on my skin and i drink some', NULL, 'pending', NULL, NULL, 'medium', '2025-09-27 06:00:02', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 06:00:02', '2025-09-27 06:00:02'),
(9, 'DM20250927-EE6AED', 'Testing auto-track functionality', 18, 'orange-3', 'Partially accessible roads', 1.00, 'Test Auto-Track Location, Quezon City', 'Test Auto-Track Location', 'Philippines', 'Philippines', NULL, NULL, '', '', '09111222333', '', 'Testing auto-track functionality', '', '[]', '', NULL, 'pending', NULL, NULL, 'medium', '2025-09-27 06:07:26', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 06:07:26', '2025-09-27 06:07:26'),
(10, 'DM20250927-89EA92', 'Testing complete auto-track workflow', 20, 'red-1', 'Critical situations', 1.00, 'Final Test Location, Manila', 'Final Test Location', 'Philippines', 'Philippines', NULL, NULL, '', '', '09444555666', '', 'Testing complete auto-track workflow', '', '[]', '', NULL, 'pending', NULL, NULL, 'medium', '2025-09-27 06:11:04', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 06:11:04', '2025-09-27 06:11:04'),
(11, 'DM20250927-80C45A', 'Im toxic', 22, 'red-1', 'Critical situations', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046166', '', 'Im toxic', '11-25', '[\"medical_assistance\"]', 'Toxic waste pour on my skin and i drink some', NULL, 'pending', NULL, NULL, 'medium', '2025-09-27 06:11:52', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 06:11:52', '2025-09-27 06:11:52'),
(12, 'DM20250927-4CD3E6', 'help', 23, 'green-2', 'Intact homes & accessible roads', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046163', '', 'help', '1-5', '[\"medical_assistance\"]', 'im under the wall', NULL, 'pending', NULL, NULL, 'medium', '2025-09-27 06:30:44', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 06:30:44', '2025-09-27 06:30:44'),
(13, 'DM20250927-E916FC', 'help', 23, 'green-2', 'Intact homes & accessible roads', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046163', '', 'help', '1-5', '[\"medical_assistance\"]', 'im under the wall', NULL, 'pending', NULL, NULL, 'medium', '2025-09-27 06:31:26', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 06:31:26', '2025-09-27 06:31:26'),
(14, 'DM20250927-DDD3FC', 'help', 23, 'green-2', 'Intact homes & accessible roads', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046163', '', 'help', '1-5', '[\"medical_assistance\"]', 'im under the wall', 'uploads/emergency_images/emergency_1758954861_68d7856ddd40c.png', 'pending', NULL, NULL, 'medium', '2025-09-27 06:34:21', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 06:34:21', '2025-09-27 06:34:21'),
(15, 'DM20250927-E19102', 'help', 23, 'green-2', 'Intact homes & accessible roads', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046163', '', 'help', '1-5', '[\"medical_assistance\"]', 'im under the wall', 'uploads/emergency_images/emergency_1758954878_68d7857e19112.png', 'pending', NULL, NULL, 'medium', '2025-09-27 06:34:38', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 06:34:38', '2025-09-27 06:34:38'),
(16, 'DM20250927-D7A313', 'help', 23, 'green-2', 'Intact homes & accessible roads', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046163', '', 'help', '1-5', '[\"medical_assistance\"]', 'im under the wall', 'uploads/emergency_images/emergency_1758954925_68d785ad7a322.png', 'pending', NULL, NULL, 'medium', '2025-09-27 06:35:25', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 06:35:25', '2025-09-27 06:35:25'),
(17, 'DM20250927-1528FF', 'help', 23, 'green-2', 'Intact homes & accessible roads', 1.00, 'Purok 3, Brgy. Halang, Lipa City, Batangas', 'Purok 3', 'Philippines', 'Philippines', NULL, NULL, 'near the school', '', '09151046163', '', 'help', '1-5', '[\"medical_assistance\"]', 'im under the wall', 'uploads/emergency_images/emergency_1758955009_68d786015290e.png', 'pending', NULL, NULL, 'medium', '2025-09-27 06:36:49', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-27 06:36:49', '2025-09-27 06:36:49'),
(18, 'DM20250928-492C85', 'Accessibility', 23, 'red-2', 'Heavy devastation (Red)', 1.00, 'purok 3, Halang, Lipa City, Batangas, Philippines', 'Lipa City', 'Batangas', 'CALABARZON', NULL, NULL, NULL, NULL, '09151046166', NULL, 'I am under the water', NULL, NULL, NULL, NULL, 'pending', NULL, NULL, 'medium', '2025-09-28 05:35:00', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-28 05:35:00', '2025-09-28 05:35:00'),
(19, 'DM20250928-B9A812', 'Transport', 23, 'orange-2', 'Minor structural damage (Orange)', 1.00, 'purok 3, Halang, Lipa City, Batangas, Philippines', 'Lipa City', 'Batangas', 'CALABARZON', NULL, NULL, NULL, NULL, '09151046166', NULL, 'Im under the wheel', NULL, NULL, NULL, NULL, 'pending', NULL, NULL, 'medium', '2025-09-28 05:41:31', NULL, NULL, NULL, 0, 0, 'web_form', '2025-09-28 05:41:31', '2025-09-28 05:41:31');

-- --------------------------------------------------------

--
-- Table structure for table `disaster_resources`
--

CREATE TABLE `disaster_resources` (
  `resource_id` int(11) NOT NULL,
  `disaster_id` int(11) NOT NULL,
  `resource_type` enum('personnel','vehicle','equipment','supplies','shelter','medical','other') NOT NULL,
  `resource_name` varchar(200) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit` varchar(50) DEFAULT NULL,
  `allocated_by` int(11) DEFAULT NULL,
  `allocated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('allocated','deployed','returned','damaged','consumed') DEFAULT 'allocated',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disaster_resource_deployments`
--

CREATE TABLE `disaster_resource_deployments` (
  `deployment_id` int(11) NOT NULL,
  `disaster_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `quantity_deployed` int(11) NOT NULL DEFAULT 1,
  `deployment_notes` text DEFAULT NULL,
  `status` enum('active','returned','lost','damaged') DEFAULT 'active',
  `deployed_by` int(11) DEFAULT NULL,
  `deployed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `returned_at` timestamp NULL DEFAULT NULL,
  `return_notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disaster_resource_deployments`
--

INSERT INTO `disaster_resource_deployments` (`deployment_id`, `disaster_id`, `resource_id`, `quantity_deployed`, `deployment_notes`, `status`, `deployed_by`, `deployed_at`, `returned_at`, `return_notes`, `updated_at`) VALUES
(1, 1, 1, 1, 'Emergency ambulance deployed for earthquake response', 'active', 1, '2025-09-27 03:57:50', NULL, NULL, '2025-09-27 03:57:50');

-- --------------------------------------------------------

--
-- Table structure for table `disaster_types`
--

CREATE TABLE `disaster_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('natural','man-made','technological','biological') DEFAULT 'natural',
  `severity_weight` decimal(3,2) DEFAULT 1.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disaster_types`
--

INSERT INTO `disaster_types` (`type_id`, `type_name`, `description`, `category`, `severity_weight`, `is_active`, `created_at`, `updated_at`) VALUES
(20, 'Typhoon', 'Tropical cyclone with strong winds and heavy rain', 'natural', 4.80, 1, '2025-09-27 04:10:54', '2025-09-27 04:10:54');

-- --------------------------------------------------------

--
-- Table structure for table `disaster_types_backup_20250928_140244`
--

CREATE TABLE `disaster_types_backup_20250928_140244` (
  `type_id` int(11) NOT NULL DEFAULT 0,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('natural','man-made','technological','biological') DEFAULT 'natural',
  `severity_weight` decimal(3,2) DEFAULT 1.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disaster_types_backup_20250928_140244`
--

INSERT INTO `disaster_types_backup_20250928_140244` (`type_id`, `type_name`, `description`, `category`, `severity_weight`, `is_active`, `created_at`, `updated_at`) VALUES
(18, 'Flood', 'Overflow of water from rivers, lakes, or heavy rainfall causing property damage', 'natural', 3.20, 1, '2025-09-27 04:10:54', '2025-09-27 04:10:54'),
(19, 'Fire', 'Uncontrolled burning that destroys property and threatens life', 'man-made', 4.00, 1, '2025-09-27 04:10:54', '2025-09-27 04:10:54'),
(20, 'Typhoon', 'Tropical cyclone with strong winds and heavy rain', 'natural', 4.80, 1, '2025-09-27 04:10:54', '2025-09-27 04:10:54'),
(21, 'Landslide', 'Movement of rock, earth, or debris down a slope', 'natural', 3.80, 1, '2025-09-27 04:10:54', '2025-09-27 04:10:54'),
(22, 'Chemical Spill', 'Release of hazardous chemicals into the environment', 'technological', 4.20, 1, '2025-09-27 04:10:54', '2025-09-27 04:10:54'),
(23, 'Building Collapse', 'Structural failure of buildings due to various causes', 'man-made', 4.50, 1, '2025-09-27 04:10:54', '2025-09-27 04:10:54'),
(24, 'Disease Outbreak', 'Rapid spread of infectious disease in a community', 'biological', 3.50, 1, '2025-09-27 04:10:54', '2025-09-27 04:10:54'),
(33, 'Volcanic Eruption', 'Volcanic activity, ash fall, and lava flow', 'natural', 4.20, 1, '2025-09-27 05:50:52', '2025-09-27 05:50:52'),
(34, 'Tsunami', 'Large ocean waves caused by seismic or volcanic activity', 'natural', 4.60, 1, '2025-09-27 05:50:52', '2025-09-27 05:50:52'),
(35, 'Storm Surge', 'Abnormal rise of seawater during storms and typhoons', 'natural', 3.40, 1, '2025-09-27 05:50:52', '2025-09-27 05:50:52'),
(36, 'Drought', 'Extended period of deficient rainfall causing water shortage', 'natural', 2.80, 1, '2025-09-27 05:50:52', '2025-09-27 05:50:52'),
(37, 'Industrial Accident', 'Accidents in factories, plants, and industrial facilities', 'technological', 3.60, 1, '2025-09-27 05:50:52', '2025-09-27 05:50:52'),
(38, 'Transportation Accident', 'Vehicle, aircraft, ship, or train accidents', 'man-made', 3.20, 1, '2025-09-27 05:50:52', '2025-09-27 05:50:52'),
(39, 'Explosion', 'Sudden violent release of energy from bombs or gas leaks', 'man-made', 4.40, 1, '2025-09-27 05:50:52', '2025-09-27 05:50:52');

-- --------------------------------------------------------

--
-- Table structure for table `disaster_updates`
--

CREATE TABLE `disaster_updates` (
  `update_id` int(11) NOT NULL,
  `disaster_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `update_type` enum('status_change','progress','escalation','resolution','general') DEFAULT 'general',
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status_from` enum('pending','assigned','acknowledged','in_progress','resolved','closed','escalated') DEFAULT NULL,
  `status_to` enum('pending','assigned','acknowledged','in_progress','resolved','closed','escalated') DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disaster_updates`
--

INSERT INTO `disaster_updates` (`update_id`, `disaster_id`, `user_id`, `update_type`, `title`, `description`, `status_from`, `status_to`, `is_public`, `attachments`, `created_at`) VALUES
(1, 1, 1, 'general', 'Initial Report Submitted', 'Report submitted by: Anonymous', NULL, NULL, 0, NULL, '2025-09-27 03:03:46'),
(2, 1, 2, 'status_change', 'Status updated to Assigned', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-09-27 03:35:21'),
(3, 1, 2, 'status_change', 'Status updated to Resolved', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-09-27 03:35:40'),
(4, 1, 2, 'status_change', 'Status updated to Closed', 'Done', NULL, NULL, 0, NULL, '2025-09-27 03:56:42'),
(5, 1, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 02:00:00'),
(6, 1, 1, 'status_change', 'Report Assigned to LGU', 'Your report has been assigned to Quezon City LGU for immediate response. They will contact you shortly to assess the situation.', NULL, NULL, 1, NULL, '2025-09-27 03:30:00'),
(7, 1, 1, 'status_change', 'LGU Response Team Dispatched', 'Quezon City Emergency Response Team has been dispatched to your location. Expected arrival within 45 minutes.', NULL, NULL, 1, NULL, '2025-09-27 04:15:00'),
(8, 1, 1, 'resolution', 'Emergency Resolved', 'The emergency situation has been successfully resolved. Thank you for using the iMSafe system. If you need further assistance, please contact us.', NULL, NULL, 1, NULL, '2025-09-27 06:30:00'),
(9, 2, 1, 'general', 'Report Received', 'Your building collapse emergency report has been received and is being processed. Emergency response teams will be contacted within 2-4 hours due to the critical nature of structural incidents.', NULL, NULL, 1, NULL, '2025-09-27 05:03:35'),
(10, 2, 1, 'status_change', 'Report Under Review', 'Your building collapse report is being reviewed by structural safety experts and emergency response coordinators. Initial risk assessment is underway.', NULL, NULL, 1, NULL, '2025-09-27 05:15:00'),
(11, 2, 1, 'status_change', 'Assigned to LGU', 'Your report has been assigned to Quezon City Building Safety Office and Emergency Response Team. Structural engineers have been notified for immediate assessment.', NULL, NULL, 1, NULL, '2025-09-27 05:45:00'),
(12, 2, 2, 'status_change', 'Status updated to Acknowledged', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-09-27 05:27:59'),
(13, 2, 2, 'status_change', 'Status updated to In progress', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-09-27 05:40:58'),
(14, 3, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 05:47:55'),
(15, 4, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 05:54:50'),
(16, 5, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 05:56:28'),
(17, 6, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 05:57:21'),
(18, 7, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 06:00:01'),
(19, 8, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 06:00:03'),
(20, 9, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 06:07:26'),
(21, 10, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 06:11:04'),
(22, 11, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 06:11:52'),
(23, 12, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 06:30:44'),
(24, 13, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 06:31:26'),
(25, 14, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 06:34:21'),
(26, 15, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 06:34:38'),
(27, 16, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 06:35:25'),
(28, 17, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-27 06:36:49'),
(29, 18, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-28 05:35:00'),
(30, 19, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-28 05:41:31'),
(31, 20, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-28 06:04:50'),
(32, 20, 2, 'status_change', 'Status updated to Acknowledged', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-09-28 06:06:01'),
(33, 20, 2, 'status_change', 'Status updated to In progress', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-09-28 06:41:55'),
(34, 2, 2, 'status_change', 'Status updated to Closed', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-09-28 07:21:40'),
(35, 2, 2, 'status_change', 'Status updated to Resolved', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-09-28 07:22:01'),
(36, 2, 2, 'status_change', 'Status updated to Resolved', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-09-28 07:23:28'),
(37, 20, 2, 'status_change', 'Status updated to Resolved', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-09-28 07:23:56'),
(38, 20, 2, 'status_change', 'Status updated to Closed', 'done my boy', NULL, NULL, 0, NULL, '2025-09-28 07:24:22'),
(39, 21, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-28 09:53:41'),
(40, 22, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-28 09:57:16'),
(41, 23, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-28 10:04:25'),
(42, 24, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-28 10:08:29'),
(43, 25, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-28 10:12:37'),
(44, 26, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-09-28 10:17:09'),
(45, 27, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-01 02:33:14'),
(46, 28, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-01 02:35:49'),
(47, 29, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-01 02:35:55'),
(48, 30, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-01 02:37:30'),
(49, 31, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-01 04:50:53'),
(50, 32, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-01 05:10:30'),
(51, 33, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-01 08:45:09'),
(52, 34, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-01 08:48:14'),
(53, 35, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-01 08:53:42'),
(54, 35, 2, 'status_change', 'Status updated to In progress', 'hello how are u', NULL, NULL, 0, NULL, '2025-10-01 08:57:28'),
(55, 36, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-01 09:09:10'),
(56, 36, 2, 'status_change', 'Status updated to IN PROGRESS', 'the rescue is on the way', NULL, NULL, 0, NULL, '2025-10-01 09:10:43'),
(57, 36, 2, 'status_change', 'Status updated to COMPLETED', 'done brother', NULL, NULL, 0, NULL, '2025-10-01 09:27:18'),
(58, 2, 2, 'status_change', 'Status updated to COMPLETED', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-10-01 09:54:51'),
(59, 35, 2, 'status_change', 'Status updated to COMPLETED', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-10-01 09:55:06'),
(60, 34, 2, 'status_change', 'Status updated to COMPLETED', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-10-01 09:55:15'),
(61, 33, 2, 'status_change', 'Status updated to COMPLETED', 'Status changed by Admin istrator', NULL, NULL, 0, NULL, '2025-10-01 09:55:21'),
(62, 37, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-01 10:55:52'),
(63, 38, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-01 11:03:53'),
(64, 39, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 00:02:09'),
(65, 40, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 05:50:51'),
(66, 41, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 05:52:31'),
(67, 42, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 06:12:27'),
(68, 43, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 06:16:11'),
(69, 44, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 06:17:34'),
(70, 45, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 06:40:11'),
(71, 46, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 07:54:39'),
(72, 47, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 08:09:39'),
(73, 48, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 08:11:19'),
(74, 49, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 08:14:10'),
(75, 50, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 08:19:04'),
(76, 51, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 09:10:31'),
(77, 52, 1, 'general', 'Report Received', 'Your emergency report has been received and is being processed. You will be contacted within 24-48 hours depending on the severity level.', NULL, NULL, 1, NULL, '2025-10-05 09:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `lgus`
--

CREATE TABLE `lgus` (
  `lgu_id` int(11) NOT NULL,
  `lgu_name` varchar(100) NOT NULL,
  `lgu_type` enum('city','municipality','province','barangay') NOT NULL,
  `region` varchar(50) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `city_municipality` varchar(50) DEFAULT NULL,
  `barangay` varchar(50) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `office_address` text DEFAULT NULL,
  `response_time_hours` int(11) DEFAULT 24,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lgus`
--

INSERT INTO `lgus` (`lgu_id`, `lgu_name`, `lgu_type`, `region`, `province`, `city_municipality`, `barangay`, `contact_person`, `contact_phone`, `contact_email`, `office_address`, `response_time_hours`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Quezon City', 'city', 'NCR', 'Metro Manila', 'Quezon City', NULL, NULL, '+63-2-8988-4242', NULL, NULL, 12, 1, '2025-09-27 01:52:17', '2025-09-27 01:52:17'),
(2, 'Manila City', 'city', 'NCR', 'Metro Manila', 'Manila', NULL, NULL, '+63-2-8527-4034', NULL, NULL, 12, 1, '2025-09-27 01:52:17', '2025-09-27 01:52:17'),
(3, 'Makati City', 'city', 'NCR', 'Metro Manila', 'Makati', NULL, NULL, '+63-2-8870-2444', NULL, NULL, 8, 1, '2025-09-27 01:52:17', '2025-09-27 01:52:17'),
(4, 'Cebu City', 'city', 'Region VII', 'Cebu', 'Cebu City', NULL, NULL, '+63-32-255-8451', NULL, NULL, 24, 1, '2025-09-27 01:52:17', '2025-09-27 01:52:17'),
(5, 'Davao City', 'city', 'Region XI', 'Davao del Sur', 'Davao City', NULL, NULL, '+63-82-227-0001', NULL, NULL, 24, 1, '2025-09-27 01:52:17', '2025-09-27 01:52:17'),
(6, 'Regional Office - NCR', 'province', 'NCR', 'Metro Manila', NULL, NULL, NULL, '+63-2-8925-6741', NULL, NULL, 48, 1, '2025-09-27 01:52:17', '2025-09-27 01:52:17'),
(7, 'Regional Office - Region VII', 'province', 'Region VII', 'Cebu', NULL, NULL, NULL, '+63-32-254-6391', NULL, NULL, 48, 1, '2025-09-27 01:52:17', '2025-09-27 01:52:17'),
(8, 'Quezon City LGU', 'city', 'NCR', 'Metro Manila', 'Quezon City', NULL, 'Maria Santos', '02-8988-4242', 'info@quezoncity.gov.ph', '1 Quezon Ave, Quezon City, Metro Manila', 24, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43'),
(9, 'Manila City LGU', 'city', 'NCR', 'Metro Manila', 'Manila', NULL, 'Juan Dela Cruz', '02-5527-4000', 'contact@manila.gov.ph', 'Manila City Hall, Manila', 24, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43'),
(10, 'Makati City LGU', 'city', 'NCR', 'Metro Manila', 'Makati', NULL, 'Rosa Valdez', '02-8870-1301', 'help@makati.gov.ph', 'Makati City Hall, Makati', 24, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43'),
(11, 'Cebu City LGU', 'city', 'Central Visayas', 'Cebu', 'Cebu City', NULL, 'Pedro Ramirez', '032-255-8441', 'mayor@cebucity.gov.ph', 'Cebu City Hall, Cebu', 24, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43'),
(12, 'Davao City LGU', 'city', 'Davao Region', 'Davao del Sur', 'Davao City', NULL, 'Ana Gutierrez', '082-227-1000', 'info@davaocity.gov.ph', 'Davao City Hall, Davao', 24, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43');

-- --------------------------------------------------------

--
-- Stand-in structure for view `lgu_performance_summary`
-- (See below for the actual view)
--
CREATE TABLE `lgu_performance_summary` (
`lgu_name` varchar(100)
,`total_reports` bigint(21)
,`resolved_reports` bigint(21)
,`acknowledged_reports` bigint(21)
,`avg_response_hours` decimal(24,4)
,`overdue_reports` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('disaster_assigned','status_update','escalation','deadline_warning','system','alert','warning','info') NOT NULL DEFAULT 'info',
  `related_id` int(11) DEFAULT NULL,
  `related_disaster_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  `target_role` enum('admin','reporter') DEFAULT NULL,
  `target_lgu_id` int(11) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `type`, `related_id`, `related_disaster_id`, `is_read`, `created_at`, `read_at`, `target_role`, `target_lgu_id`, `expires_at`, `is_active`, `created_by`) VALUES
(7, 1, 'Welcome to iMSafe', 'Welcome to the iMSafe Disaster Monitoring System admin dashboard.', 'system', NULL, NULL, 1, '2025-09-27 03:29:45', NULL, NULL, NULL, NULL, 1, NULL),
(8, 1, 'Disaster Assigned', 'A disaster report has been assigned to you and requires attention.', 'disaster_assigned', NULL, NULL, 1, '2025-09-27 03:29:45', NULL, NULL, NULL, NULL, 1, NULL),
(9, 1, 'Status Update', 'The status of a disaster report has been updated.', 'status_update', NULL, NULL, 1, '2025-09-27 03:29:45', NULL, NULL, NULL, NULL, 1, NULL),
(10, 1, 'Welcome to Disaster Monitoring System', 'Welcome! Please familiarize yourself with the system features.', 'system', NULL, NULL, 1, '2025-09-27 03:57:27', NULL, 'admin', NULL, NULL, 1, 1),
(11, 2, 'Welcome to Disaster Monitoring System', 'Welcome! Please familiarize yourself with the system features.', 'system', NULL, NULL, 1, '2025-09-27 03:57:27', NULL, '', NULL, NULL, 1, 1),
(13, 1, 'Emergency Response Training', 'All personnel are required to attend emergency response training on Friday at 9 AM.', 'system', NULL, NULL, 1, '2025-09-27 03:57:27', NULL, 'admin', NULL, NULL, 1, 1),
(14, 1, 'Welcome to Disaster Monitoring System', 'Welcome! Please familiarize yourself with the system features and emergency procedures.', 'system', NULL, NULL, 1, '2025-09-27 03:57:50', NULL, NULL, NULL, NULL, 1, 1),
(15, 2, 'Welcome to Disaster Monitoring System', 'Welcome! Please familiarize yourself with the system features and emergency procedures.', 'system', NULL, NULL, 1, '2025-09-27 03:57:50', NULL, NULL, NULL, NULL, 1, 1),
(17, 1, 'System Maintenance Alert', 'Scheduled system maintenance will occur tonight from 10 PM to 2 AM.', 'system', NULL, NULL, 1, '2025-09-27 05:38:22', NULL, 'admin', NULL, NULL, 1, 1),
(18, 1, 'New Disaster Report Assigned', 'A new flood disaster report has been assigned to your LGU for immediate response.', 'disaster_assigned', NULL, NULL, 1, '2025-09-27 05:38:22', NULL, '', NULL, NULL, 1, 1),
(19, 2, 'Status Update Required', 'Please provide a status update on the ongoing earthquake response operations.', 'status_update', NULL, NULL, 1, '2025-09-27 05:38:22', NULL, NULL, NULL, NULL, 1, 1),
(20, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20251001-86EC40', 'info', NULL, 37, 1, '2025-10-01 11:01:27', NULL, NULL, NULL, NULL, 1, NULL),
(21, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20251001-86EC40', 'info', NULL, 37, 1, '2025-10-01 11:01:27', NULL, NULL, NULL, NULL, 1, NULL),
(22, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lipa City, Batangas, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: COMPLETED\nTracking ID: DM20251001-660C81', 'info', NULL, 36, 1, '2025-10-01 11:01:27', NULL, NULL, NULL, NULL, 1, NULL),
(23, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lipa City, Batangas, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: COMPLETED\nTracking ID: DM20251001-660C81', 'info', NULL, 36, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(24, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Alitagtag, Batangas, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: COMPLETED\nTracking ID: DM20251001-6631E5', 'info', NULL, 35, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(25, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Alitagtag, Batangas, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: COMPLETED\nTracking ID: DM20251001-6631E5', 'info', NULL, 35, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(26, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: San Pablo City, Laguna, CALABARZON\nSeverity: Heavy devastation\nStatus: COMPLETED\nTracking ID: DM20251001-E09BA2', 'info', NULL, 34, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(27, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: San Pablo City, Laguna, CALABARZON\nSeverity: Heavy devastation\nStatus: COMPLETED\nTracking ID: DM20251001-E09BA2', 'info', NULL, 34, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(28, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Imus City, Cavite, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: COMPLETED\nTracking ID: DM20251001-55047A', 'info', NULL, 33, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(29, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Imus City, Cavite, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: COMPLETED\nTracking ID: DM20251001-55047A', 'info', NULL, 33, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(30, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lipa City, Batangas, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251001-62C937', 'info', NULL, 32, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(31, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lipa City, Batangas, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251001-62C937', 'info', NULL, 32, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(32, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: General Trias City, Cavite, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251001-DE23DD', 'info', NULL, 31, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(33, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: General Trias City, Cavite, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251001-DE23DD', 'info', NULL, 31, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(34, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251001-A9351D', 'info', NULL, 30, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(35, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251001-A9351D', 'info', NULL, 30, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(36, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251001-BCD38B', 'info', NULL, 29, 1, '2025-10-01 11:01:28', NULL, NULL, NULL, NULL, 1, NULL),
(37, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251001-BCD38B', 'info', NULL, 29, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(38, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251001-512B87', 'info', NULL, 28, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(39, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251001-512B87', 'info', NULL, 28, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(40, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251001-AB0164', 'info', NULL, 27, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(41, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251001-AB0164', 'info', NULL, 27, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(42, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20250928-50A6EE', 'info', NULL, 26, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(43, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20250928-50A6EE', 'info', NULL, 26, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(44, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: San Pablo City, Laguna, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250928-5E7824', 'info', NULL, 25, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(45, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: San Pablo City, Laguna, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250928-5E7824', 'info', NULL, 25, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(46, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Batangas City, Batangas, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20250928-D1B583', 'info', NULL, 24, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(47, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Batangas City, Batangas, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20250928-D1B583', 'info', NULL, 24, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(48, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lipa City, Batangas, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20250928-944CF0', 'info', NULL, 23, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(49, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lipa City, Batangas, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20250928-944CF0', 'info', NULL, 23, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(50, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20250928-C4B14F', 'info', NULL, 22, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(51, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20250928-C4B14F', 'info', NULL, 22, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(52, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Santa Rosa City, Laguna, CALABARZON\nSeverity: Minor structural damage (Orange)\nStatus: ON GOING\nTracking ID: DM20250928-5490BA', 'info', NULL, 21, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(53, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Santa Rosa City, Laguna, CALABARZON\nSeverity: Minor structural damage (Orange)\nStatus: ON GOING\nTracking ID: DM20250928-5490BA', 'info', NULL, 21, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(54, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Minor structural damage (Orange)\nStatus: ON GOING\nTracking ID: DM20250928-20C3E6', 'info', NULL, 20, 1, '2025-10-01 11:01:29', NULL, NULL, NULL, NULL, 1, NULL),
(55, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Dasmariñas City, Cavite, CALABARZON\nSeverity: Minor structural damage (Orange)\nStatus: ON GOING\nTracking ID: DM20250928-20C3E6', 'info', NULL, 20, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(56, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lipa City, Batangas, CALABARZON\nSeverity: Minor structural damage (Orange)\nStatus: ON GOING\nTracking ID: DM20250928-B9A812', 'info', NULL, 19, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(57, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lipa City, Batangas, CALABARZON\nSeverity: Minor structural damage (Orange)\nStatus: ON GOING\nTracking ID: DM20250928-B9A812', 'info', NULL, 19, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(58, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lipa City, Batangas, CALABARZON\nSeverity: Heavy devastation (Red)\nStatus: ON GOING\nTracking ID: DM20250928-492C85', 'info', NULL, 18, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(59, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lipa City, Batangas, CALABARZON\nSeverity: Heavy devastation (Red)\nStatus: ON GOING\nTracking ID: DM20250928-492C85', 'info', NULL, 18, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(60, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-1528FF', 'info', NULL, 17, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(61, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-1528FF', 'info', NULL, 17, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(62, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-D7A313', 'info', NULL, 16, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(63, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-D7A313', 'info', NULL, 16, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(64, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-E19102', 'info', NULL, 15, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(65, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-E19102', 'info', NULL, 15, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(66, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-DDD3FC', 'info', NULL, 14, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(67, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-DDD3FC', 'info', NULL, 14, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(68, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-E916FC', 'info', NULL, 13, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(69, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-E916FC', 'info', NULL, 13, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(70, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-4CD3E6', 'info', NULL, 12, 1, '2025-10-01 11:01:30', NULL, NULL, NULL, NULL, 1, NULL),
(71, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-4CD3E6', 'info', NULL, 12, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(72, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Critical situations\nStatus: ON GOING\nTracking ID: DM20250927-80C45A', 'info', NULL, 11, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(73, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Critical situations\nStatus: ON GOING\nTracking ID: DM20250927-80C45A', 'info', NULL, 11, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(74, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Final Test Location, Philippines, Philippines\nSeverity: Critical situations\nStatus: ON GOING\nTracking ID: DM20250927-89EA92', 'info', NULL, 10, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(75, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Final Test Location, Philippines, Philippines\nSeverity: Critical situations\nStatus: ON GOING\nTracking ID: DM20250927-89EA92', 'info', NULL, 10, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(76, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Test Auto-Track Location, Philippines, Philippines\nSeverity: Partially accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-EE6AED', 'info', NULL, 9, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(77, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Test Auto-Track Location, Philippines, Philippines\nSeverity: Partially accessible roads\nStatus: ON GOING\nTracking ID: DM20250927-EE6AED', 'info', NULL, 9, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(78, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Critical situations\nStatus: ON GOING\nTracking ID: DM20250927-2F3E8F', 'info', NULL, 8, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(79, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Critical situations\nStatus: ON GOING\nTracking ID: DM20250927-2F3E8F', 'info', NULL, 8, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(80, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Critical situations\nStatus: ON GOING\nTracking ID: DM20250927-1250E8', 'info', NULL, 7, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(81, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Critical situations\nStatus: ON GOING\nTracking ID: DM20250927-1250E8', 'info', NULL, 7, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(82, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Test Location 2, Philippines, Philippines\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20250927-1694F3', 'info', NULL, 6, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(83, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Test Location 2, Philippines, Philippines\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20250927-1694F3', 'info', NULL, 6, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(84, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Critical situations\nStatus: ON GOING\nTracking ID: DM20250927-CB5364', 'info', NULL, 5, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(85, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Critical situations\nStatus: ON GOING\nTracking ID: DM20250927-CB5364', 'info', NULL, 5, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(86, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Test Location, Philippines, Philippines\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20250927-ABA233', 'info', NULL, 4, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(87, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Test Location, Philippines, Philippines\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20250927-ABA233', 'info', NULL, 4, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(88, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20250927-B8AEE7', 'info', NULL, 3, 1, '2025-10-01 11:01:31', NULL, NULL, NULL, NULL, 1, NULL),
(89, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20250927-B8AEE7', 'info', NULL, 3, 1, '2025-10-01 11:01:32', NULL, NULL, NULL, NULL, 1, NULL),
(90, 1, 'New High Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Critical situations\nStatus: COMPLETED\nTracking ID: DM20250927-75065C', 'warning', NULL, 2, 1, '2025-10-01 11:01:32', NULL, NULL, NULL, NULL, 1, NULL),
(91, 2, 'New High Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Critical situations\nStatus: COMPLETED\nTracking ID: DM20250927-75065C', 'warning', NULL, 2, 1, '2025-10-01 11:01:32', NULL, NULL, NULL, NULL, 1, NULL),
(92, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Widespread power loss\nStatus: ON GOING\nTracking ID: DM20250927-23CCE7', 'info', NULL, 1, 1, '2025-10-01 11:01:32', NULL, NULL, NULL, NULL, 1, NULL),
(93, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Purok 3, Philippines, Philippines\nSeverity: Widespread power loss\nStatus: ON GOING\nTracking ID: DM20250927-23CCE7', 'info', NULL, 1, 1, '2025-10-01 11:01:32', NULL, NULL, NULL, NULL, 1, NULL),
(94, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lucena City, Quezon, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251001-90B5C4', 'info', NULL, 38, 1, '2025-10-01 11:03:53', NULL, NULL, NULL, NULL, 1, NULL),
(95, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lucena City, Quezon, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251001-90B5C4', 'info', NULL, 38, 1, '2025-10-01 11:03:53', NULL, NULL, NULL, NULL, 1, NULL),
(96, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Santa Rosa City, Laguna, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251005-0EEDCA', 'info', NULL, 39, 1, '2025-10-05 00:02:09', NULL, NULL, NULL, NULL, 1, NULL),
(97, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Santa Rosa City, Laguna, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251005-0EEDCA', 'info', NULL, 39, 0, '2025-10-05 00:02:09', NULL, NULL, NULL, NULL, 1, NULL),
(98, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Santa Rosa City, Laguna, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251005-B0E504', 'info', NULL, 40, 0, '2025-10-05 05:50:51', NULL, NULL, NULL, NULL, 1, NULL),
(99, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Santa Rosa City, Laguna, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251005-B0E504', 'info', NULL, 40, 0, '2025-10-05 05:50:51', NULL, NULL, NULL, NULL, 1, NULL),
(100, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Santa Rosa City, Laguna, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251005-FB7658', 'info', NULL, 41, 0, '2025-10-05 05:52:31', NULL, NULL, NULL, NULL, 1, NULL),
(101, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Santa Rosa City, Laguna, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251005-FB7658', 'info', NULL, 41, 0, '2025-10-05 05:52:31', NULL, NULL, NULL, NULL, 1, NULL),
(102, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lipa City, Batangas, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251005-B6B218', 'info', NULL, 42, 0, '2025-10-05 06:12:27', NULL, NULL, NULL, NULL, 1, NULL),
(103, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Lipa City, Batangas, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251005-B6B218', 'info', NULL, 42, 0, '2025-10-05 06:12:27', NULL, NULL, NULL, NULL, 1, NULL),
(104, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Alitagtag, Batangas, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251005-B843D0', 'info', NULL, 43, 0, '2025-10-05 06:16:11', NULL, NULL, NULL, NULL, 1, NULL),
(105, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Alitagtag, Batangas, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251005-B843D0', 'info', NULL, 43, 0, '2025-10-05 06:16:11', NULL, NULL, NULL, NULL, 1, NULL),
(106, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Tayabas City, Quezon, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20251005-ED72CB', 'info', NULL, 44, 0, '2025-10-05 06:17:35', NULL, NULL, NULL, NULL, 1, NULL),
(107, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Tayabas City, Quezon, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20251005-ED72CB', 'info', NULL, 44, 0, '2025-10-05 06:17:35', NULL, NULL, NULL, NULL, 1, NULL),
(108, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Biñan City, Laguna, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20251005-BA621C', 'info', NULL, 45, 0, '2025-10-05 06:40:11', NULL, NULL, NULL, NULL, 1, NULL),
(109, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Biñan City, Laguna, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20251005-BA621C', 'info', NULL, 45, 0, '2025-10-05 06:40:12', NULL, NULL, NULL, NULL, 1, NULL),
(110, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Cainta, Rizal, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251005-EBB14D', 'info', NULL, 46, 0, '2025-10-05 07:54:39', NULL, NULL, NULL, NULL, 1, NULL),
(111, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Cainta, Rizal, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251005-EBB14D', 'info', NULL, 46, 0, '2025-10-05 07:54:39', NULL, NULL, NULL, NULL, 1, NULL),
(112, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Cainta, Rizal, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20251005-342B41', 'info', NULL, 47, 0, '2025-10-05 08:09:39', NULL, NULL, NULL, NULL, 1, NULL),
(113, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Cainta, Rizal, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20251005-342B41', 'info', NULL, 47, 0, '2025-10-05 08:09:39', NULL, NULL, NULL, NULL, 1, NULL),
(114, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Antipolo City, Rizal, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20251005-77602A', 'info', NULL, 48, 0, '2025-10-05 08:11:19', NULL, NULL, NULL, NULL, 1, NULL),
(115, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Antipolo City, Rizal, CALABARZON\nSeverity: Intact homes & accessible roads\nStatus: ON GOING\nTracking ID: DM20251005-77602A', 'info', NULL, 48, 0, '2025-10-05 08:11:19', NULL, NULL, NULL, NULL, 1, NULL),
(116, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Taytay, Rizal, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251005-280017', 'info', NULL, 49, 0, '2025-10-05 08:14:10', NULL, NULL, NULL, NULL, 1, NULL),
(117, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Taytay, Rizal, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251005-280017', 'info', NULL, 49, 0, '2025-10-05 08:14:10', NULL, NULL, NULL, NULL, 1, NULL),
(118, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Cainta, Rizal, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251005-84C524', 'info', NULL, 50, 0, '2025-10-05 08:19:04', NULL, NULL, NULL, NULL, 1, NULL),
(119, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Cainta, Rizal, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251005-84C524', 'info', NULL, 50, 0, '2025-10-05 08:19:04', NULL, NULL, NULL, NULL, 1, NULL),
(120, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Tayabas City, Quezon, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251005-7D5BE1', 'info', NULL, 51, 0, '2025-10-05 09:10:31', NULL, NULL, NULL, NULL, 1, NULL),
(121, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Tayabas City, Quezon, CALABARZON\nSeverity: Minor structural damage\nStatus: ON GOING\nTracking ID: DM20251005-7D5BE1', 'info', NULL, 51, 0, '2025-10-05 09:10:32', NULL, NULL, NULL, NULL, 1, NULL),
(122, 1, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Cainta, Rizal, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251005-4421A3', 'info', NULL, 52, 0, '2025-10-05 09:20:36', NULL, NULL, NULL, NULL, 1, NULL),
(123, 2, 'New Medium Disaster Report: Typhoon', 'A new disaster report has been submitted.\nType: Typhoon\nLocation: Cainta, Rizal, CALABARZON\nSeverity: Heavy devastation\nStatus: ON GOING\nTracking ID: DM20251005-4421A3', 'info', NULL, 52, 0, '2025-10-05 09:20:36', NULL, NULL, NULL, NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_recipients`
--

CREATE TABLE `notification_recipients` (
  `recipient_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `resource_id` int(11) NOT NULL,
  `resource_name` varchar(255) NOT NULL,
  `resource_type` enum('vehicle','equipment','medical','food','shelter','communication','other') NOT NULL,
  `description` text DEFAULT NULL,
  `quantity_available` int(11) DEFAULT 0,
  `unit` varchar(50) NOT NULL,
  `location` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `availability_status` enum('available','deployed','maintenance','unavailable') DEFAULT 'available',
  `owner_lgu_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`resource_id`, `resource_name`, `resource_type`, `description`, `quantity_available`, `unit`, `location`, `contact_person`, `contact_phone`, `availability_status`, `owner_lgu_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Emergency Ambulance Unit 1', 'vehicle', 'Fully equipped ambulance with medical personnel', 1, 'unit', 'QC Medical Center', 'Dr. Lopez', '0917-123-4567', 'available', 1, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43'),
(2, 'Fire Truck Alpha-1', 'vehicle', 'Heavy rescue fire truck with ladder and pumps', 1, 'unit', 'QC Fire Station 1', 'Chief Garcia', '0918-234-5678', 'available', 1, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43'),
(3, 'Portable Generators', 'equipment', 'Backup power generators for emergency shelters', 5, 'units', 'QC Emergency Warehouse', 'Engr. Tan', '0919-345-6789', 'available', 1, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43'),
(4, 'Emergency Food Packs', 'food', 'Ready-to-eat meal packs for disaster victims', 1000, 'packs', 'QC Relief Goods Center', 'Ms. Reyes', '0920-456-7890', 'available', 1, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43'),
(5, 'Medical Supply Kit', 'medical', 'First aid and emergency medical supplies', 50, 'kits', 'Manila General Hospital', 'Nurse Silva', '0921-567-8901', 'available', 2, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43'),
(6, 'Emergency Tents', 'shelter', 'Waterproof emergency shelter tents', 30, 'tents', 'Manila Relief Center', 'Mr. Cruz', '0922-678-9012', 'available', 2, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43'),
(7, 'Two-way Radios', 'communication', 'Professional emergency communication radios', 20, 'units', 'Makati Emergency Center', 'Comm. Officer Lee', '0923-789-0123', 'available', 3, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43'),
(8, 'Water Purification Tablets', 'medical', 'Water treatment tablets for emergency use', 500, 'bottles', 'Cebu Health Center', 'Dr. Martinez', '0924-890-1234', 'available', 4, 1, '2025-09-27 03:50:43', '2025-09-27 03:50:43'),
(9, 'Fire Truck Unit 1', 'vehicle', 'Red fire truck with ladder and water tank capacity', 1, 'unit', 'Quezon City Fire Station', 'Chief Rodriguez', '+63-2-8988-1234', 'available', 1, 1, '2025-09-27 04:24:43', '2025-09-27 04:24:43'),
(10, 'Emergency Medical Kit', 'medical', 'Complete first aid and medical emergency kit', 25, 'boxes', 'QC Health Department', 'Dr. Santos', '+63-2-8988-5678', 'available', 1, 1, '2025-09-27 04:24:43', '2025-09-27 04:24:43'),
(11, 'Rescue Boat', 'vehicle', 'Inflatable rescue boat for flood operations', 3, 'units', 'Pasig River Station', 'Captain Dela Cruz', '+63-2-8988-9012', 'available', 1, 1, '2025-09-27 04:24:43', '2025-09-27 04:24:43'),
(12, 'Relief Goods Pack', 'food', 'Food and water supplies for disaster victims', 150, 'packs', 'QC Social Services', 'Ms. Garcia', '+63-2-8988-3456', 'available', 1, 1, '2025-09-27 04:24:43', '2025-09-27 04:24:43'),
(13, 'Generator Set', 'equipment', 'Portable power generator for emergency use', 8, 'units', 'QC Engineering Office', 'Engr. Reyes', '+63-2-8988-7890', 'maintenance', 1, 1, '2025-09-27 04:24:43', '2025-09-27 04:24:43'),
(14, 'Communications Radio', 'communication', 'Two-way radio for emergency communications', 20, 'units', 'QC DRRMO Office', 'Mr. Villanueva', '+63-2-8988-2345', 'available', 1, 1, '2025-09-27 04:24:43', '2025-09-27 04:24:43');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`, `updated_by`, `updated_at`) VALUES
(1, 'system_name', 'iMSafe Disaster Monitoring System', 'string', 'Name of the system', 1, 1, '2025-10-05 06:14:56'),
(2, 'default_response_time_hours', '24', 'integer', 'Default response time in hours', 0, NULL, '2025-09-27 01:52:17'),
(3, 'escalation_threshold_hours', '48', 'integer', 'Hours before auto-escalation', 0, NULL, '2025-09-27 01:52:17'),
(4, 'emergency_hotline', '911', 'string', 'Emergency hotline number', 1, 1, '2025-10-05 06:14:56'),
(5, 'support_email', 'support@imsafe.gov.ph', 'string', 'Support email address', 1, NULL, '2025-09-27 01:52:17'),
(6, 'auto_assignment_enabled', 'true', 'boolean', 'Enable automatic LGU assignment', 0, NULL, '2025-09-27 01:52:17'),
(7, 'public_tracking_enabled', 'true', 'boolean', 'Allow public tracking of reports', 1, NULL, '2025-09-27 01:52:17'),
(8, 'anonymous_reporting_enabled', 'true', 'boolean', 'Allow anonymous reports', 1, NULL, '2025-09-27 01:52:17'),
(9, 'sms_notifications_enabled', 'false', 'boolean', 'Enable SMS notifications', 0, NULL, '2025-09-27 01:52:17'),
(10, 'email_notifications_enabled', 'true', 'boolean', 'Enable email notifications', 0, NULL, '2025-09-27 01:52:17'),
(11, 'admin_email', 'admin@imsafe.local', 'string', NULL, 0, 1, '2025-10-05 06:14:56'),
(12, 'response_time_target', '6', 'string', NULL, 0, 1, '2025-10-05 06:14:56'),
(13, 'escalation_hours', '24', 'string', NULL, 0, 1, '2025-10-05 06:14:56'),
(14, 'auto_assignment', '0', 'string', NULL, 0, 1, '2025-10-05 06:14:56'),
(15, 'email_notifications', '1', 'string', NULL, 0, 1, '2025-10-05 06:14:56'),
(16, 'sms_notifications', '1', 'string', NULL, 0, 1, '2025-10-05 06:14:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','reporter') NOT NULL DEFAULT 'reporter',
  `status` enum('I''m fine','Need help') NOT NULL DEFAULT 'I''m fine',
  `lgu_assigned` varchar(100) DEFAULT NULL,
  `lgu_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `status`, `lgu_assigned`, `lgu_id`, `phone`, `is_active`, `email_verified`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'admin', 'admin@imsafe.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', 'I\'m fine', 'Quezon City LGU', 8, NULL, 1, 1, '2025-09-27 01:52:17', '2025-10-05 12:02:16', '2025-10-05 12:02:16'),
(2, 'Admin_01', 'administrator@email.com', '$2y$10$lGAlfpvVUFi1ZGXvmsnQ6OwALrcjpfLFKtAKEI8IeuZ1JzWzfskR6', 'Admin', 'istrator', 'admin', 'I\'m fine', 'Manila City LGU', 9, '', 1, 0, '2025-09-27 02:09:54', '2025-10-04 23:56:00', '2025-10-04 23:56:00'),
(3, 'heheboi', 'jhiroramir@gmail.com', '$2y$10$iQy6EJx5ZDBNQ8XkZwGRb.NyDYZvTloxoPltP3HAZnCyrXtMZi1CO', 'Jhiro', 'Tool', 'reporter', 'Need help', '', NULL, '09151046166', 1, 0, '2025-10-04 23:44:01', '2025-10-05 12:56:34', '2025-10-05 12:56:34');

-- --------------------------------------------------------

--
-- Structure for view `active_disasters_summary`
--
DROP TABLE IF EXISTS `active_disasters_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_disasters_summary`  AS SELECT `d`.`disaster_id` AS `disaster_id`, `d`.`tracking_id` AS `tracking_id`, `d`.`disaster_name` AS `disaster_name`, `dt`.`type_name` AS `type_name`, `d`.`severity_level` AS `severity_level`, `d`.`severity_display` AS `severity_display`, `d`.`city` AS `city`, `d`.`status` AS `status`, `d`.`priority` AS `priority`, `d`.`reported_at` AS `reported_at`, `d`.`escalation_deadline` AS `escalation_deadline`, `lgu`.`lgu_name` AS `assigned_lgu`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `assigned_user`, timestampdiff(HOUR,`d`.`reported_at`,current_timestamp()) AS `hours_since_report` FROM (((`disasters` `d` join `disaster_types` `dt` on(`d`.`type_id` = `dt`.`type_id`)) left join `lgus` `lgu` on(`d`.`assigned_lgu_id` = `lgu`.`lgu_id`)) left join `users` `u` on(`d`.`assigned_user_id` = `u`.`user_id`)) WHERE `d`.`status` not in ('resolved','closed') ORDER BY `d`.`priority` DESC, `d`.`reported_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `lgu_performance_summary`
--
DROP TABLE IF EXISTS `lgu_performance_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `lgu_performance_summary`  AS SELECT `lgu`.`lgu_name` AS `lgu_name`, count(`d`.`disaster_id`) AS `total_reports`, count(case when `d`.`status` = 'resolved' then 1 end) AS `resolved_reports`, count(case when `d`.`acknowledged_at` is not null then 1 end) AS `acknowledged_reports`, avg(timestampdiff(HOUR,`d`.`reported_at`,`d`.`acknowledged_at`)) AS `avg_response_hours`, count(case when `d`.`escalation_deadline` < current_timestamp() and `d`.`status` not in ('resolved','closed') then 1 end) AS `overdue_reports` FROM (`lgus` `lgu` left join `disasters` `d` on(`lgu`.`lgu_id` = `d`.`assigned_lgu_id`)) WHERE `lgu`.`is_active` = 1 GROUP BY `lgu`.`lgu_id`, `lgu`.`lgu_name` ORDER BY `lgu`.`lgu_name` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_table` (`table_name`,`record_id`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_type` (`announcement_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_published` (`published_at`),
  ADD KEY `idx_audience` (`target_audience`);

--
-- Indexes for table `disasters`
--
ALTER TABLE `disasters`
  ADD PRIMARY KEY (`disaster_id`),
  ADD UNIQUE KEY `tracking_id` (`tracking_id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `assigned_user_id` (`assigned_user_id`),
  ADD KEY `idx_tracking` (`tracking_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_severity` (`severity_level`),
  ADD KEY `idx_location` (`city`,`province`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_reported_date` (`reported_at`),
  ADD KEY `idx_assignment` (`assigned_lgu_id`,`assigned_user_id`),
  ADD KEY `idx_escalation` (`escalation_deadline`),
  ADD KEY `idx_reported_by_user` (`reported_by_user_id`);

--
-- Indexes for table `disaster_resources`
--
ALTER TABLE `disaster_resources`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `allocated_by` (`allocated_by`),
  ADD KEY `idx_disaster` (`disaster_id`),
  ADD KEY `idx_type` (`resource_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `disaster_resource_deployments`
--
ALTER TABLE `disaster_resource_deployments`
  ADD PRIMARY KEY (`deployment_id`),
  ADD KEY `deployed_by` (`deployed_by`),
  ADD KEY `idx_disaster` (`disaster_id`),
  ADD KEY `idx_resource` (`resource_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `disaster_types`
--
ALTER TABLE `disaster_types`
  ADD PRIMARY KEY (`type_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `disaster_updates`
--
ALTER TABLE `disaster_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_disaster` (`disaster_id`),
  ADD KEY `idx_type` (`update_type`),
  ADD KEY `idx_public` (`is_public`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `lgus`
--
ALTER TABLE `lgus`
  ADD PRIMARY KEY (`lgu_id`),
  ADD KEY `idx_location` (`province`,`city_municipality`,`barangay`),
  ADD KEY `idx_type` (`lgu_type`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_unread` (`user_id`,`is_read`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_date` (`created_at`),
  ADD KEY `idx_related_disaster` (`related_disaster_id`);

--
-- Indexes for table `notification_recipients`
--
ALTER TABLE `notification_recipients`
  ADD PRIMARY KEY (`recipient_id`),
  ADD UNIQUE KEY `unique_notification_user` (`notification_id`,`user_id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `owner_lgu_id` (`owner_lgu_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_resource_type` (`resource_type`),
  ADD KEY `idx_availability` (`availability_status`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_key` (`setting_key`),
  ADD KEY `idx_public` (`is_public`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_lgu` (`lgu_assigned`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `lgu_id` (`lgu_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disasters`
--
ALTER TABLE `disasters`
  MODIFY `disaster_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `disaster_resources`
--
ALTER TABLE `disaster_resources`
  MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disaster_resource_deployments`
--
ALTER TABLE `disaster_resource_deployments`
  MODIFY `deployment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `disaster_types`
--
ALTER TABLE `disaster_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `disaster_updates`
--
ALTER TABLE `disaster_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `lgus`
--
ALTER TABLE `lgus`
  MODIFY `lgu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `notification_recipients`
--
ALTER TABLE `notification_recipients`
  MODIFY `recipient_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `disasters`
--
ALTER TABLE `disasters`
  ADD CONSTRAINT `disasters_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `disaster_types` (`type_id`),
  ADD CONSTRAINT `disasters_ibfk_2` FOREIGN KEY (`assigned_lgu_id`) REFERENCES `lgus` (`lgu_id`),
  ADD CONSTRAINT `disasters_ibfk_3` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `disaster_resources`
--
ALTER TABLE `disaster_resources`
  ADD CONSTRAINT `disaster_resources_ibfk_1` FOREIGN KEY (`disaster_id`) REFERENCES `disasters` (`disaster_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disaster_resources_ibfk_2` FOREIGN KEY (`allocated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `disaster_resource_deployments`
--
ALTER TABLE `disaster_resource_deployments`
  ADD CONSTRAINT `disaster_resource_deployments_ibfk_1` FOREIGN KEY (`disaster_id`) REFERENCES `disasters` (`disaster_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disaster_resource_deployments_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`resource_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disaster_resource_deployments_ibfk_3` FOREIGN KEY (`deployed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `disaster_updates`
--
ALTER TABLE `disaster_updates`
  ADD CONSTRAINT `disaster_updates_ibfk_1` FOREIGN KEY (`disaster_id`) REFERENCES `disasters` (`disaster_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disaster_updates_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_recipients`
--
ALTER TABLE `notification_recipients`
  ADD CONSTRAINT `notification_recipients_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_recipients_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`owner_lgu_id`) REFERENCES `lgus` (`lgu_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `resources_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`lgu_id`) REFERENCES `lgus` (`lgu_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
