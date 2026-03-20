-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 26, 2026 at 09:48 AM
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
-- Database: `azani_isp`
--

-- --------------------------------------------------------

--
-- Table structure for table `bandwidth_packages`
--

CREATE TABLE `bandwidth_packages` (
  `bandwidth_id` int(11) NOT NULL,
  `bandwidth_mbps` int(11) NOT NULL,
  `monthly_cost` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bandwidth_packages`
--

INSERT INTO `bandwidth_packages` (`bandwidth_id`, `bandwidth_mbps`, `monthly_cost`) VALUES
(1, 4, 1200.00),
(2, 10, 2000.00),
(3, 20, 3500.00),
(4, 25, 4000.00),
(5, 50, 7000.00);

-- --------------------------------------------------------

--
-- Table structure for table `contact_persons`
--

CREATE TABLE `contact_persons` (
  `contact_id` int(11) NOT NULL,
  `institution_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_persons`
--

INSERT INTO `contact_persons` (`contact_id`, `institution_id`, `full_name`, `designation`, `phone`, `email`, `is_primary`) VALUES
(1, 1, 'Peter Wanyama', 'Lecturer', '0722829094', 'pwanyama@jkuat.ac.ke', 1),
(2, 2, 'Ben Wallace', 'teacher', '0720202000', 'benwallace@gmail.com', 1),
(3, 3, 'job', 'tachnician', '0720202000', 'job@gmail.com', 1);

-- --------------------------------------------------------

--
-- Table structure for table `disconnection_history`
--

CREATE TABLE `disconnection_history` (
  `disconnection_id` int(11) NOT NULL,
  `institution_id` int(11) DEFAULT NULL,
  `bill_id` int(11) DEFAULT NULL,
  `disconnection_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `reconnection_date` date DEFAULT NULL,
  `reconnection_fee_paid` tinyint(1) DEFAULT 0,
  `reconnection_fee_amount` decimal(10,2) DEFAULT 1000.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructure_requirements`
--

CREATE TABLE `infrastructure_requirements` (
  `requirement_id` int(11) NOT NULL,
  `institution_id` int(11) DEFAULT NULL,
  `number_of_users` int(11) DEFAULT 0,
  `has_computers` tinyint(1) DEFAULT 0,
  `computers_purchased` int(11) DEFAULT 0,
  `computer_cost_per_unit` decimal(10,2) DEFAULT 40000.00,
  `total_computer_cost` decimal(10,2) DEFAULT 0.00,
  `lan_nodes_required` int(11) DEFAULT 0,
  `lan_pricing_id` int(11) DEFAULT NULL,
  `lan_cost` decimal(10,2) DEFAULT 0.00,
  `infrastructure_ready` tinyint(1) DEFAULT 0,
  `assessment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `infrastructure_requirements`
--

INSERT INTO `infrastructure_requirements` (`requirement_id`, `institution_id`, `number_of_users`, `has_computers`, `computers_purchased`, `computer_cost_per_unit`, `total_computer_cost`, `lan_nodes_required`, `lan_pricing_id`, `lan_cost`, `infrastructure_ready`, `assessment_date`) VALUES
(1, 1, 23, 0, 0, 40000.00, 0.00, 24, 3, 30000.00, 1, '2026-02-26'),
(2, 2, 0, 0, 0, 40000.00, 0.00, 0, NULL, 0.00, 0, NULL),
(3, 3, 0, 0, 0, 40000.00, 0.00, 0, NULL, 0.00, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `institutions`
--

CREATE TABLE `institutions` (
  `institution_id` int(11) NOT NULL,
  `registration_number` varchar(20) NOT NULL,
  `institution_name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `physical_address` text DEFAULT NULL,
  `postal_address` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `registration_date` date,
  `registration_fee_paid` tinyint(1) DEFAULT 0,
  `registration_fee_amount` decimal(10,2) DEFAULT 8500.00,
  `installation_fee_paid` tinyint(1) DEFAULT 0,
  `installation_fee_amount` decimal(10,2) DEFAULT 10000.00,
  `bandwidth_id` int(11) DEFAULT NULL,
  `connection_status` enum('Active','Suspended','Disconnected') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `institutions`
--

INSERT INTO `institutions` (`institution_id`, `registration_number`, `institution_name`, `category_id`, `physical_address`, `postal_address`, `email`, `phone`, `registration_date`, `registration_fee_paid`, `registration_fee_amount`, `installation_fee_paid`, `installation_fee_amount`, `bandwidth_id`, `connection_status`) VALUES
(1, 'AZN20268740', 'JKUAT', 4, 'BOGANI KAREN', '62000 NAIROBI', 'info@jkuat.ac.ke', '0722829094', '2026-02-26', 1, 8500.00, 1, 10000.00, 4, 'Active'),
(2, 'AZN20260411', 'Elimu Bora ', 3, 'Murang\'a', '456 Murang\'a town', 'info@elimubora.ac.ke', '0720202000', '2026-02-26', 0, 8500.00, 0, 10000.00, 3, 'Active'),
(3, 'AZN20267746', 'TANGAZA', 4, 'BOGANI', '323324 LANG\'ATA', 'th@gmail.com', '0722829094', '2026-02-26', 1, 8500.00, 1, 10000.00, 4, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `institution_categories`
--

CREATE TABLE `institution_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `institution_categories`
--

INSERT INTO `institution_categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Primary School', 'Primary education institutions'),
(2, 'Junior School', 'Junior secondary schools'),
(3, 'Senior School', 'Senior secondary schools'),
(4, 'College', 'Higher education colleges');

-- --------------------------------------------------------

--
-- Table structure for table `lan_node_pricing`
--

CREATE TABLE `lan_node_pricing` (
  `price_id` int(11) NOT NULL,
  `min_nodes` int(11) NOT NULL,
  `max_nodes` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lan_node_pricing`
--

INSERT INTO `lan_node_pricing` (`price_id`, `min_nodes`, `max_nodes`, `cost`) VALUES
(1, 2, 10, 10000.00),
(2, 11, 20, 20000.00),
(3, 21, 40, 30000.00),
(4, 41, 100, 40000.00);

-- --------------------------------------------------------

--
-- Table structure for table `monthly_bills`
--

CREATE TABLE `monthly_bills` (
  `bill_id` int(11) NOT NULL,
  `institution_id` int(11) DEFAULT NULL,
  `bill_month` date NOT NULL,
  `bandwidth_cost` decimal(10,2) DEFAULT NULL,
  `overdue_fine` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('Paid','Partial','Unpaid') DEFAULT 'Unpaid',
  `due_date` date DEFAULT NULL,
  `disconnected_date` date DEFAULT NULL,
  `reconnection_fee_paid` tinyint(1) DEFAULT 0,
  `reconnection_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `institution_id` int(11) DEFAULT NULL,
  `payment_type` enum('Registration','Installation','Monthly','Computer','LAN','Reconnection') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `payment_month` date DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `institution_id`, `payment_type`, `amount`, `payment_date`, `payment_month`, `receipt_number`, `notes`) VALUES
(1, 1, 'Registration', 8500.00, '2026-02-26 10:58:24', NULL, 'RCT202602262147', NULL),
(2, 1, 'Installation', 10000.00, '2026-02-26 10:58:34', NULL, 'RCT202602265539', NULL),
(3, 3, 'Registration', 8500.00, '2026-02-26 11:41:00', NULL, 'RCT202602262233', NULL),
(4, 3, 'Installation', 10000.00, '2026-02-26 11:41:35', NULL, 'RCT202602268750', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bandwidth_packages`
--
ALTER TABLE `bandwidth_packages`
  ADD PRIMARY KEY (`bandwidth_id`);

--
-- Indexes for table `contact_persons`
--
ALTER TABLE `contact_persons`
  ADD PRIMARY KEY (`contact_id`),
  ADD KEY `institution_id` (`institution_id`);

--
-- Indexes for table `disconnection_history`
--
ALTER TABLE `disconnection_history`
  ADD PRIMARY KEY (`disconnection_id`),
  ADD KEY `institution_id` (`institution_id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `infrastructure_requirements`
--
ALTER TABLE `infrastructure_requirements`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `institution_id` (`institution_id`),
  ADD KEY `lan_pricing_id` (`lan_pricing_id`);

--
-- Indexes for table `institutions`
--
ALTER TABLE `institutions`
  ADD PRIMARY KEY (`institution_id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `bandwidth_id` (`bandwidth_id`),
  ADD KEY `idx_institution_status` (`connection_status`);

--
-- Indexes for table `institution_categories`
--
ALTER TABLE `institution_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `lan_node_pricing`
--
ALTER TABLE `lan_node_pricing`
  ADD PRIMARY KEY (`price_id`);

--
-- Indexes for table `monthly_bills`
--
ALTER TABLE `monthly_bills`
  ADD PRIMARY KEY (`bill_id`),
  ADD KEY `institution_id` (`institution_id`),
  ADD KEY `idx_bills_status` (`payment_status`,`due_date`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `institution_id` (`institution_id`),
  ADD KEY `idx_payments_date` (`payment_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bandwidth_packages`
--
ALTER TABLE `bandwidth_packages`
  MODIFY `bandwidth_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_persons`
--
ALTER TABLE `contact_persons`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `disconnection_history`
--
ALTER TABLE `disconnection_history`
  MODIFY `disconnection_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `infrastructure_requirements`
--
ALTER TABLE `infrastructure_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `institutions`
--
ALTER TABLE `institutions`
  MODIFY `institution_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `institution_categories`
--
ALTER TABLE `institution_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lan_node_pricing`
--
ALTER TABLE `lan_node_pricing`
  MODIFY `price_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `monthly_bills`
--
ALTER TABLE `monthly_bills`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contact_persons`
--
ALTER TABLE `contact_persons`
  ADD CONSTRAINT `contact_persons_ibfk_1` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`institution_id`) ON DELETE CASCADE;

--
-- Constraints for table `disconnection_history`
--
ALTER TABLE `disconnection_history`
  ADD CONSTRAINT `disconnection_history_ibfk_1` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`institution_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disconnection_history_ibfk_2` FOREIGN KEY (`bill_id`) REFERENCES `monthly_bills` (`bill_id`);

--
-- Constraints for table `infrastructure_requirements`
--
ALTER TABLE `infrastructure_requirements`
  ADD CONSTRAINT `infrastructure_requirements_ibfk_1` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`institution_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `infrastructure_requirements_ibfk_2` FOREIGN KEY (`lan_pricing_id`) REFERENCES `lan_node_pricing` (`price_id`);

--
-- Constraints for table `institutions`
--
ALTER TABLE `institutions`
  ADD CONSTRAINT `institutions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `institution_categories` (`category_id`),
  ADD CONSTRAINT `institutions_ibfk_2` FOREIGN KEY (`bandwidth_id`) REFERENCES `bandwidth_packages` (`bandwidth_id`);

--
-- Constraints for table `monthly_bills`
--
ALTER TABLE `monthly_bills`
  ADD CONSTRAINT `monthly_bills_ibfk_1` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`institution_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`institution_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
