-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2025 at 05:47 PM
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
-- Database: `scholar`
--

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `failed_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_failed_at` datetime DEFAULT NULL,
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`user_id`, `failed_count`, `last_failed_at`, `locked_until`) VALUES
(4, 5, '2025-12-04 23:54:32', '2025-12-04 17:09:32');

-- --------------------------------------------------------

--
-- Table structure for table `login_audit`
--

CREATE TABLE `login_audit` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `username` varchar(64) NOT NULL,
  `role` varchar(32) DEFAULT NULL,
  `outcome` varchar(32) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_audit`
--

INSERT INTO `login_audit` (`id`, `user_id`, `username`, `role`, `outcome`, `ip_address`, `user_agent`, `created_at`) VALUES
(39, 2, 'mikeadrian', 'student', 'otp_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:10:45'),
(40, 2, '', 'student', 'otp_verified', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:11:53'),
(41, 2, 'mikeadrian', 'student', 'invalid_credentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:15:25'),
(42, 2, 'mikeadrian', 'student', 'otp_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:15:31'),
(43, 2, '', 'student', 'otp_verified', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:16:05'),
(44, 2, 'mikeadrian', 'student', 'invalid_credentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:49:58'),
(45, 2, 'mikeadrian', 'student', 'otp_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:50:07'),
(46, 2, '', 'student', 'otp_verified', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:50:25'),
(47, 4, 'mike', 'student', 'invalid_credentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:53:28'),
(48, 2, 'mikeadrian', 'student', 'invalid_credentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:53:36'),
(49, 4, 'mike', 'student', 'invalid_credentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:53:48'),
(50, 4, 'mike', 'superadmin', 'invalid_credentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:54:17'),
(51, 4, 'mike', 'superadmin', 'invalid_credentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:54:25'),
(52, 4, 'mike', 'superadmin', 'invalid_credentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:54:32'),
(53, 2, 'mikeadrian', 'student', 'invalid_credentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 15:56:08'),
(54, 2, 'mikeadrian', 'superadmin', 'otp_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 16:08:50'),
(55, 2, 'mikeadrian', 'superadmin', 'otp_verified', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 16:09:32'),
(56, 2, 'mikeadrian', 'student', 'otp_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 16:43:05'),
(57, 2, '', 'student', 'otp_verified', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 16:43:40');

-- --------------------------------------------------------

--
-- Table structure for table `login_otp`
--

CREATE TABLE `login_otp` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_count` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_otp`
--

INSERT INTO `login_otp` (`id`, `user_id`, `email`, `otp`, `created_at`, `expires_at`, `is_used`, `ip_address`, `attempt_count`) VALUES
(25, 2, 'mikeadriandelacruz2004@gmail.com', '$2y$10$xhsuNGdzcaNOF2FVXpGt5O0ugMdtX76hEd.efRGOcrfUZ50TqyYkm', '2025-12-04 15:10:45', '2025-12-04 16:15:45', 1, '::1', 0),
(26, 2, 'mikeadriandelacruz2004@gmail.com', '$2y$10$Vfc1bhOElu2uo/CKOV4ryO0OaUMjjiX2n3f/bZc2LS23K4rWV.Lw.', '2025-12-04 15:15:31', '2025-12-04 16:20:31', 1, '::1', 0),
(27, 2, 'mikeadriandelacruz2004@gmail.com', '$2y$10$OGkRkmqAnFSNC7.sun6xX.Mf9ymBZoVPq6mCvymRKE1NGDyv3jLUC', '2025-12-04 15:50:07', '2025-12-04 16:55:07', 1, '::1', 0),
(28, 2, 'mikeadriandelacruz2004@gmail.com', '$2y$10$A.d.Z2J2wr7Lig/Cz/NcnuxMRJfkNGFYkNXuuNkTWZzi6ShXwGbX2', '2025-12-04 16:08:50', '2025-12-04 17:13:50', 1, '::1', 0),
(29, 2, 'mikeadriandelacruz2004@gmail.com', '$2y$10$2baqzY/eBSUiV6ree6kZ.OdKgtNthjlxBLVXxobdCq0ElpJrkOgDy', '2025-12-04 16:43:05', '2025-12-04 17:48:05', 1, '::1', 0);

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `selector` char(16) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `data` longblob NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `last_activity` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin','superadmin') NOT NULL DEFAULT 'student',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `is_active`, `created_at`) VALUES
(2, 'mikeadrian', 'mikeadriandelacruz2004@gmail.com', '$2y$10$KWkYIfhpqcQvOdhGpyjEo..ORyZXAXIQCJDyScPf0aw8Vfl3pcIjC', 'student', 1, '2025-10-23 08:46:54'),
(4, 'mike', 'mikeadrian123456@gmail.com', '$2y$10$GzAjOgtdpOd0X5FmGR65Dud4UyeGepIhiZH.idjI9GhsnnePZ2DvG', 'superadmin', 1, '2025-10-26 07:38:37');

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`user_id`, `first_name`, `last_name`, `address`, `photo_path`, `is_completed`, `created_at`, `updated_at`) VALUES
(2, 'MIKE', 'CRUZ', '1982', 'storage/uploads/students/2/profile.png', 1, '2025-12-04 05:44:36', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_locked_until` (`locked_until`);

--
-- Indexes for table `login_audit`
--
ALTER TABLE `login_audit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_otp`
--
ALTER TABLE `login_otp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_email` (`user_id`,`email`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_selector` (`selector`),
  ADD KEY `fk_remember_user` (`user_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `fk_sessions_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_username` (`username`),
  ADD UNIQUE KEY `uniq_email` (`email`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `login_audit`
--
ALTER TABLE `login_audit`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `login_otp`
--
ALTER TABLE `login_otp`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD CONSTRAINT `fk_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `login_otp`
--
ALTER TABLE `login_otp`
  ADD CONSTRAINT `fk_login_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
