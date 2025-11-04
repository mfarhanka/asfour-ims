-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2025 at 12:26 PM
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
-- Database: `asfour-ims`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_investments`
--

DROP TABLE IF EXISTS `client_investments`;
CREATE TABLE `client_investments` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `investment_id` int(11) NOT NULL,
  `invested_amount` decimal(15,2) NOT NULL,
  `total_paid` decimal(15,2) DEFAULT 0.00 COMMENT 'Total amount paid so far',
  `remaining_amount` decimal(15,2) DEFAULT 0.00 COMMENT 'Remaining amount to be paid',
  `is_fully_paid` tinyint(1) DEFAULT 0 COMMENT '1 if fully paid, 0 otherwise',
  `investment_date` date NOT NULL,
  `agreement_document` varchar(255) DEFAULT NULL,
  `agreement_uploaded` tinyint(1) DEFAULT 0 COMMENT '1 if agreement uploaded, 0 otherwise',
  `payment_proof` varchar(255) DEFAULT NULL COMMENT 'Payment proof document filename',
  `payment_proof_uploaded_at` timestamp NULL DEFAULT NULL COMMENT 'When client uploaded payment proof',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT 'When admin approved the investment request',
  `approved_by` int(11) DEFAULT NULL COMMENT 'Admin ID who approved',
  `payment_verified_at` timestamp NULL DEFAULT NULL COMMENT 'When payment proof was verified',
  `payment_verified_by` int(11) DEFAULT NULL COMMENT 'Admin ID who verified payment',
  `rejection_reason` text DEFAULT NULL COMMENT 'Reason for rejection if status is rejected',
  `status` enum('pending','approved','payment_pending','payment_partial','rejected','active','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `investments`
--

DROP TABLE IF EXISTS `investments`;
CREATE TABLE `investments` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `total_goal` decimal(15,2) NOT NULL,
  `profit_percent` decimal(5,2) NOT NULL,
  `profit_percent_min` decimal(5,2) DEFAULT NULL COMMENT 'Minimum profit percentage',
  `profit_percent_max` decimal(5,2) DEFAULT NULL COMMENT 'Maximum profit percentage',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `agreement_required` tinyint(1) DEFAULT 1 COMMENT '1 if agreement is required, 0 otherwise',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

DROP TABLE IF EXISTS `payment_transactions`;
CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `client_investment_id` int(11) NOT NULL,
  `payment_amount` decimal(15,2) NOT NULL,
  `payment_proof` varchar(255) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_notes` text DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_investment_payment_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `v_investment_payment_summary`;
CREATE TABLE `v_investment_payment_summary` (
`id` int(11)
,`client_id` int(11)
,`investment_id` int(11)
,`invested_amount` decimal(15,2)
,`total_paid` decimal(15,2)
,`remaining_amount` decimal(15,2)
,`is_fully_paid` tinyint(1)
,`agreement_uploaded` tinyint(1)
,`status` enum('pending','approved','payment_pending','payment_partial','rejected','active','completed')
,`client_name` varchar(100)
,`investment_title` varchar(255)
,`payment_count` bigint(21)
,`verified_payments` decimal(37,2)
,`pending_payments` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_investment_payment_summary`
--
DROP TABLE IF EXISTS `v_investment_payment_summary`;

DROP VIEW IF EXISTS `v_investment_payment_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_investment_payment_summary`  AS SELECT `ci`.`id` AS `id`, `ci`.`client_id` AS `client_id`, `ci`.`investment_id` AS `investment_id`, `ci`.`invested_amount` AS `invested_amount`, `ci`.`total_paid` AS `total_paid`, `ci`.`remaining_amount` AS `remaining_amount`, `ci`.`is_fully_paid` AS `is_fully_paid`, `ci`.`agreement_uploaded` AS `agreement_uploaded`, `ci`.`status` AS `status`, `c`.`name` AS `client_name`, `i`.`title` AS `investment_title`, count(`pt`.`id`) AS `payment_count`, sum(case when `pt`.`status` = 'verified' then `pt`.`payment_amount` else 0 end) AS `verified_payments`, sum(case when `pt`.`status` = 'pending' then `pt`.`payment_amount` else 0 end) AS `pending_payments` FROM (((`client_investments` `ci` left join `clients` `c` on(`ci`.`client_id` = `c`.`id`)) left join `investments` `i` on(`ci`.`investment_id` = `i`.`id`)) left join `payment_transactions` `pt` on(`ci`.`id` = `pt`.`client_investment_id`)) GROUP BY `ci`.`id` ;

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

DROP TABLE IF EXISTS `withdrawals`;
CREATE TABLE `withdrawals` (
  `withdrawal_id` int(11) NOT NULL,
  `client_investment_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `investment_id` int(11) NOT NULL,
  `withdrawal_amount` decimal(15,2) NOT NULL,
  `request_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','completed','rejected') NOT NULL DEFAULT 'pending',
  `withdrawal_proof` varchar(255) DEFAULT NULL COMMENT 'Filename of uploaded transfer proof',
  `processed_date` datetime DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who processed',
  `admin_notes` text DEFAULT NULL,
  `client_notes` text DEFAULT NULL COMMENT 'Client bank details or withdrawal instructions',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores client profit withdrawal requests and admin processing';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `client_investments`
--
ALTER TABLE `client_investments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_approved_by` (`approved_by`),
  ADD KEY `fk_payment_verified_by` (`payment_verified_by`);

--
-- Indexes for table `investments`
--
ALTER TABLE `investments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_investment_id` (`client_investment_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`withdrawal_id`),
  ADD KEY `idx_client_investment` (`client_investment_id`),
  ADD KEY `idx_client_id` (`client_id`),
  ADD KEY `idx_investment_id` (`investment_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_request_date` (`request_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_investments`
--
ALTER TABLE `client_investments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `investments`
--
ALTER TABLE `investments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `withdrawal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `client_investments`
--
ALTER TABLE `client_investments`
  ADD CONSTRAINT `fk_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payment_verified_by` FOREIGN KEY (`payment_verified_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `fk_payment_transaction_investment` FOREIGN KEY (`client_investment_id`) REFERENCES `client_investments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payment_transaction_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD CONSTRAINT `fk_withdrawal_client_investment` FOREIGN KEY (`client_investment_id`) REFERENCES `client_investments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_withdrawal_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_withdrawal_investment` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
