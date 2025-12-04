-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 05, 2025 at 12:15 AM
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
(75, 2, 'mikeadrian', 'superadmin', 'otp_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 21:53:26'),
(76, 2, 'mikeadrian', 'superadmin', 'otp_verified', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 21:53:48'),
(77, 2, 'mikeadrian', 'superadmin', 'invalid_credentials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 21:56:24'),
(78, 7, 'jen', 'admin', 'otp_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 21:56:38'),
(79, 7, 'jen', NULL, 'otp_invalid', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 21:56:56'),
(80, 7, 'jen', 'admin', 'otp_verified', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 21:57:07'),
(81, 2, 'mikeadrian', 'superadmin', 'otp_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 22:06:03'),
(82, 2, 'mikeadrian', 'superadmin', 'otp_verified', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 22:06:22'),
(83, 2, 'mikeadrian', 'superadmin', 'otp_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 22:07:43'),
(84, 2, 'mikeadrian', 'superadmin', 'otp_verified', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-04 22:10:57');

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
(35, 2, 'mikeadriandelacruz2004@gmail.com', '$2y$10$ogSafmX4IKv62Q7x.mykvO1zpnI3/RffWfaIwSK2U5MNH75HBl8Fu', '2025-12-04 21:53:26', '2025-12-04 22:58:26', 1, '::1', 0),
(36, 7, 'restricted111111@gmail.com', '$2y$10$LMhNtq2KXPiI5k1o/exAAeKWNSdvwvU.LHkuCg/FXRBf0.pr5fOvC', '2025-12-04 21:56:38', '2025-12-04 23:01:38', 1, '::1', 1),
(37, 2, 'mikeadriandelacruz2004@gmail.com', '$2y$10$5WE2nOHj1tnGk4t1ocwUJO2BxKacZA333FU0kDM63h1oOodCsO78y', '2025-12-04 22:06:03', '2025-12-04 23:11:03', 1, '::1', 0),
(38, 2, 'mikeadriandelacruz2004@gmail.com', '$2y$10$ofaL3Gnac4jWchr9YaCO6uNpirF/Rt.UQkPntpzibjXrqc3MxsjWm', '2025-12-04 22:07:43', '2025-12-04 23:12:43', 0, '::1', 0),
(39, 2, 'mikeadriandelacruz2004@gmail.com', '$2y$10$R5ReFCOZu.JvaA3gAsmZBOJZl9tS45LuQgXw00vmcolAFTaxt4SXW', '2025-12-04 22:10:13', '2025-12-04 23:15:13', 1, '::1', 0);

-- --------------------------------------------------------

--
-- Table structure for table `pending_registrations`
--

CREATE TABLE `pending_registrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `otp` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `attempt_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pending_registrations`
--

INSERT INTO `pending_registrations` (`id`, `username`, `email`, `password_hash`, `otp`, `expires_at`, `is_used`, `attempt_count`, `ip_address`, `created_at`) VALUES
(2, 'restricted111111@gmail.com', 'restricted111111@gmail.com', '$2y$10$hFLERmnOWQg4sbHCHrwy.OijVhF/fnLIZMXy5aMPv.m6tdXZ2FQZC', '$2y$10$JhDfTqgkvC16hJPp7pC0p.T3mhDX9TBozkw.8SAJnwq4OjzZXEZiy', '2025-12-04 22:43:52', 1, 0, '::1', '2025-12-04 20:47:39'),
(3, 'restricted1111111@gmail.com', 'restricted1111111@gmail.com', '$2y$10$IkrFmO8HIDb3vpAsRUcCD.oa5hd.9lXx0yhRIpnQc6FvfQGvW83ci', '$2y$10$/GyNJini7dGdb2d5J4AFPeQBDEQqgxck81X/3xbN34bSoU4kt95Bm', '2025-12-04 21:36:53', 0, 0, '::1', '2025-12-04 20:31:53');

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

INSERT INTO `sessions` (`id`, `user_id`, `data`, `created_at`, `updated_at`, `last_activity`, `expires_at`, `ip_address`, `user_agent`) VALUES
('123f979r8hqcmfar7k7mgua61t', NULL, 0x7b2276223a312c226976223a22616c4f77666b5164596e565936515c2f52222c22746167223a223131715a70777849504443376d6145326241616c48673d3d222c226374223a2251372b6c477154483157776a4953764e3556354d427742314b4a7a6b227d, '2025-12-04 22:06:26', NULL, '2025-12-04 23:06:26', '2025-12-04 23:36:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
('1pdaupkm4d3h3irqtkmftdklc7', NULL, 0x7b2276223a312c226976223a2271534d4e6c625456683777425838555a222c22746167223a22535232526f624948507a5c2f4743415967686d575566773d3d222c226374223a222b433166386c517452316f3877675163437a5a6c713041555149575278375a6b36486b71736f6f497950556b4b5c2f50507854704576456267526361794a49686e4d354257702b566d585a702b50423935655533746f6f37584137636f533939626e4550627a504d772b6c63456641445863455735426d6f7261736964326e4236675a5a4a557739687938673d227d, '2025-12-04 22:06:31', NULL, '2025-12-04 23:06:31', '2025-12-04 23:36:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
('f33m4lsq3tnl6m5v2oierrdjkj', 7, 0x7b2276223a312c226976223a224134446a4455726379544a4d7a4a4b48222c22746167223a22526a55496b72356473376a52415273655132375654513d3d222c226374223a227a417050594a624c4e71334847346e4f5a7a3871587352367a65567162774f5261727a304d4e74774738496e6c6b516a39365368547734554934706b70305a757a694d63706941654b6a6868436b3731646c4c51722b546261764a412b53594b5c2f396548734839425c2f574e554c524a63523731386e38707147674a4b4d6b52554532344956484777665848494e4b573662794c704c2b313752666f4442776c707750733d227d, '2025-12-04 21:57:07', '2025-12-04 21:57:15', '2025-12-04 22:57:15', '2025-12-04 23:27:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
('moens39jdafgna92fous4r1vqe', NULL, 0x7b2276223a312c226976223a22586732724c4d4131714a535261675c2f37222c22746167223a224e5a384f38674f51436f5861474357367a61744b49673d3d222c226374223a2237436d6e4a4636355038532b415a454d7a79756e474c634b45344156227d, '2025-12-04 22:05:44', NULL, '2025-12-04 23:05:44', '2025-12-04 23:35:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
('r8qvg17vei9lp4ir91eak71kma', NULL, 0x7b2276223a312c226976223a225c2f457661424d544d4c6e316d5a316c73222c22746167223a224c4f444476706f7844364f545248644b565c2f336d6f513d3d222c226374223a22356b6f6665426f633038546b68676d33625a48487338773033434943227d, '2025-12-04 22:07:27', NULL, '2025-12-04 23:07:27', '2025-12-04 23:37:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
('u3uhaps94s1ncd2tfcg3hhder9', 2, 0x7b2276223a312c226976223a224a414b346549705679564d325a65482b222c22746167223a2236514f4f31467a495c2f4c4f7976422b5c2f79672b4267513d3d222c226374223a22504a5a6e6b69484f77636f484b6d6d70306f6d4e57523464664b5774436b662b326c687262454a6d43744b545941684956356e6732316b6d62797244384a6e4f676157565163333333415033727870726a3361796c6473386e74747259365543763939724a346f4332736c4173507046684e3149787a6d575936344f6e4e676b3955674d6b46345571684779365436334249505c2f3356594c2b5a42536765366c474e6e4f516a71674a5c2f6f3148546d3671763135654e6e442b4455526554655c2f6e415967227d, '2025-12-04 22:10:57', '2025-12-04 23:15:30', '2025-12-05 00:15:30', '2025-12-05 00:45:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36');

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
(2, 'mikeadrian', 'mikeadriandelacruz2004@gmail.com', '$2y$10$KWkYIfhpqcQvOdhGpyjEo..ORyZXAXIQCJDyScPf0aw8Vfl3pcIjC', 'superadmin', 1, '2025-10-23 08:46:54'),
(4, 'mike', 'mikeadrian123456@gmail.com', '$2y$10$GzAjOgtdpOd0X5FmGR65Dud4UyeGepIhiZH.idjI9GhsnnePZ2DvG', 'superadmin', 1, '2025-10-26 07:38:37'),
(7, 'jen', 'restricted111111@gmail.com', '$2y$10$nCyQ8Ie4pI6.mbQWwiUvFe1HKQTLaFlyOGS1gRRuYVQDl7ESdW/wS', 'admin', 1, '2025-12-04 21:56:00');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for table `pending_registrations`
--
ALTER TABLE `pending_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_pending_email` (`email`),
  ADD UNIQUE KEY `uniq_pending_username` (`username`);

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
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `login_audit`
--
ALTER TABLE `login_audit`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `login_otp`
--
ALTER TABLE `login_otp`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `pending_registrations`
--
ALTER TABLE `pending_registrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
