-- Applications Table for Scholarship Applications
CREATE TABLE `scholarship_applications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `academic_level` varchar(50) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `age` int(3) DEFAULT NULL,
  `cellphone_number` varchar(20) DEFAULT NULL,
  `sex` enum('Male','Female','Other') DEFAULT NULL,
  `mothers_maiden_name` varchar(100) DEFAULT NULL,
  `mothers_occupation` varchar(100) DEFAULT NULL,
  `fathers_name` varchar(100) DEFAULT NULL,
  `fathers_occupation` varchar(100) DEFAULT NULL,
  `street_address` varchar(255) DEFAULT NULL,
  `house_number` varchar(50) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT 'San Luis',
  `cor_coe_file` varchar(255) DEFAULT NULL,
  `cert_grades_file` varchar(255) DEFAULT NULL,
  `barangay_indigency_file` varchar(255) DEFAULT NULL,
  `voters_cert_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','incomplete') NOT NULL DEFAULT 'pending',
  `submission_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add primary key
ALTER TABLE `scholarship_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_applications_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_submission_date` (`submission_date`);

-- Add foreign key constraint
ALTER TABLE `scholarship_applications`
  ADD CONSTRAINT `fk_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Set AUTO_INCREMENT
ALTER TABLE `scholarship_applications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
