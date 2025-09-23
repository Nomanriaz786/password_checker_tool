-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 22, 2025 at 05:58 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `password_checker`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_user_id` int(11) NOT NULL,
  `target_user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` varchar(100) DEFAULT NULL,
  `new_value` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `common_passwords`
--

CREATE TABLE `common_passwords` (
  `id` int(11) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `common_passwords`
--

INSERT INTO `common_passwords` (`id`, `password`) VALUES
(25, '000000'),
(44, '1111'),
(9, '111111'),
(10, '123123'),
(17, '1234'),
(5, '12345'),
(2, '123456'),
(6, '1234567'),
(4, '12345678'),
(3, '123456789'),
(23, '123qwe'),
(45, '2222'),
(46, '3333'),
(47, '7777'),
(8, 'abc123'),
(41, 'access'),
(11, 'admin'),
(32, 'admin123'),
(22, 'adobe123'),
(24, 'azerty'),
(38, 'baseball'),
(39, 'basketball'),
(49, 'changeme'),
(16, 'dragon'),
(37, 'football'),
(27, 'guest'),
(30, 'hello'),
(19, 'iloveyou'),
(14, 'letmein'),
(35, 'login'),
(34, 'master'),
(15, 'monkey'),
(36, 'passw0rd'),
(1, 'password'),
(26, 'password1'),
(13, 'password123'),
(21, 'princess'),
(7, 'qwerty'),
(28, 'qwerty123'),
(33, 'root'),
(42, 'shadow'),
(18, 'sunshine'),
(40, 'superman'),
(48, 'temp'),
(43, 'test'),
(20, 'trustno1'),
(12, 'welcome'),
(31, 'welcome123'),
(29, 'zxcvbnm');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `attempt_type` enum('success','failure','locked','otp_request','verification_success','verification_failed','registration','unverified','blocked','logout') DEFAULT 'failure',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `user_id`, `username`, `ip_address`, `user_agent`, `attempt_type`, `created_at`) VALUES
(1, NULL, 'test_repair_script', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '', '2025-09-22 14:46:29'),
(2, NULL, 'test_repair_script', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '', '2025-09-22 14:46:50'),
(3, 2, 'ss6642437', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'registration', '2025-09-22 14:48:07'),
(4, 2, 'ss6642437', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'verification_failed', '2025-09-22 14:48:28'),
(5, 2, 'ss6642437', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'verification_failed', '2025-09-22 14:48:40'),
(6, 2, 'ss6642437', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'verification_success', '2025-09-22 14:54:43'),
(7, NULL, 'ss6642437', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'failure', '2025-09-22 18:16:17'),
(8, NULL, 'muhammadnomanriaz599', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'failure', '2025-09-22 18:17:09'),
(9, 3, 'muhammadnomanriaz599', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'registration', '2025-09-22 18:17:36'),
(10, 3, 'muhammadnomanriaz599', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'verification_success', '2025-09-22 18:17:54'),
(11, 3, 'muhammadnomanriaz599', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'logout', '2025-09-22 18:33:45'),
(12, 3, 'muhammadnomanriaz599', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'success', '2025-09-22 18:33:47'),
(13, 3, 'muhammadnomanriaz599', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '', '2025-09-22 18:54:17'),
(14, 3, 'muhammadnomanriaz599', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'success', '2025-09-22 20:50:18');

-- --------------------------------------------------------

--
-- Table structure for table `password_checks`
--

CREATE TABLE `password_checks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `password_strength` decimal(3,2) NOT NULL,
  `strength_category` enum('very_weak','weak','medium','strong','very_strong') NOT NULL,
  `has_common_pattern` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_evaluations`
--

CREATE TABLE `password_evaluations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `password_length` int(11) NOT NULL,
  `has_uppercase` tinyint(1) DEFAULT 0,
  `has_lowercase` tinyint(1) DEFAULT 0,
  `has_digits` tinyint(1) DEFAULT 0,
  `has_symbols` tinyint(1) DEFAULT 0,
  `strength_score` decimal(3,2) NOT NULL,
  `strength_category` enum('very_weak','weak','medium','strong','very_strong') NOT NULL,
  `feedback` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`feedback`)),
  `ip_address` varchar(45) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_evaluations`
--

INSERT INTO `password_evaluations` (`id`, `user_id`, `password_length`, `has_uppercase`, `has_lowercase`, `has_digits`, `has_symbols`, `strength_score`, `strength_category`, `feedback`, `ip_address`, `created_at`) VALUES
(1, 3, 12, 1, 1, 1, 1, 0.60, 'strong', NULL, '::1', '2025-09-22 18:28:09'),
(2, 3, 16, 1, 1, 1, 1, 0.65, 'strong', NULL, '::1', '2025-09-22 18:28:10'),
(3, 3, 33, 1, 1, 1, 1, 0.70, 'strong', NULL, '::1', '2025-09-22 18:28:11'),
(4, 3, 33, 1, 1, 1, 1, 0.70, 'strong', NULL, '::1', '2025-09-22 18:28:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `otp_secret` varchar(32) DEFAULT NULL,
  `is_2fa_enabled` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_code` varchar(255) DEFAULT NULL,
  `verification_code_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `otp_secret`, `is_2fa_enabled`, `is_active`, `is_verified`, `verification_code`, `verification_code_expires`, `created_at`, `last_login`, `failed_login_attempts`, `locked_until`) VALUES
(3, 'muhammadnomanriaz599', 'muhammadnomanriaz599@gmail.com', '$2y$10$AjmxMXrlFAVIxO9HmZAJW.pd04.yX5BOoFKUAlUhM2uUDS2AxjTHG', 'admin', NULL, 1, 1, 1, NULL, NULL, '2025-09-22 13:17:36', '2025-09-22 15:50:18', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_user` (`admin_user_id`),
  ADD KEY `idx_target_user` (`target_user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `common_passwords`
--
ALTER TABLE `common_passwords`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password` (`password`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_attempt_type` (`attempt_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `password_checks`
--
ALTER TABLE `password_checks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_strength` (`strength_category`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `password_evaluations`
--
ALTER TABLE `password_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_strength` (`strength_category`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `common_passwords`
--
ALTER TABLE `common_passwords`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `password_checks`
--
ALTER TABLE `password_checks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_evaluations`
--
ALTER TABLE `password_evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
