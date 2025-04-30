-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 04, 2023 at 11:51 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tl_commerce_version2_0_1`
--

-- --------------------------------------------------------

--
-- Table structure for table `tl_com_city_translations`
--

CREATE TABLE `tl_com_city_translations` (
  `id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `lang` varchar(11) DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tl_com_country_translations`
--

CREATE TABLE `tl_com_country_translations` (
  `id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `lang` varchar(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tl_com_state_translations`
--

CREATE TABLE `tl_com_state_translations` (
  `id` int(11) NOT NULL,
  `state_id` int(11) NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `lang` varchar(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tl_com_tax_profiles`
--

CREATE TABLE `tl_com_tax_profiles` (
  `id` int(11) NOT NULL,
  `title` varchar(250) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 2,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tl_com_tax_rates`
--

CREATE TABLE `tl_com_tax_rates` (
  `id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `country_id` int(11) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `postal_code` varchar(150) DEFAULT NULL,
  `tax_name` varchar(150) DEFAULT NULL,
  `tax_rate` double NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--
ALTER TABLE `tl_com_products` ADD `shipping_cost` DOUBLE NULL DEFAULT '0' AFTER `attatchment_name`, ADD `is_apply_multiple_qty_shipping_cost` INT(11) NOT NULL DEFAULT '1' AFTER `shipping_cost`, ADD `is_enable_tax` INT(11) NOT NULL DEFAULT '2' AFTER `is_apply_multiple_qty_shipping_cost`, ADD `tax_profile` INT(11) NULL DEFAULT NULL AFTER `is_enable_tax`;

ALTER TABLE `tl_pick_up_points` ADD `country_id` INT(11) NULL DEFAULT NULL AFTER `zone`, ADD `state_id` INT(11) NULL DEFAULT NULL AFTER `country_id`, ADD `city_id` INT(11) NULL DEFAULT NULL AFTER `state_id`;

ALTER TABLE `tl_com_customer_address` CHANGE `country_id` `country_id` INT(11) NULL DEFAULT NULL, CHANGE `state_id` `state_id` INT(11) NULL DEFAULT NULL, CHANGE `city_id` `city_id` INT(11) NULL DEFAULT NULL, CHANGE `postal_code` `postal_code` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
--
-- Indexes for table `tl_com_tax_profiles`
--
ALTER TABLE `tl_com_tax_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tl_com_tax_rates`
--
ALTER TABLE `tl_com_tax_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profile_id` (`profile_id`),
  ADD KEY `country_id` (`country_id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `state_id` (`state_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tl_com_tax_profiles`
--
ALTER TABLE `tl_com_tax_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tl_com_tax_rates`
--
ALTER TABLE `tl_com_tax_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tl_com_tax_rates`
--
ALTER TABLE `tl_com_tax_rates`
  ADD CONSTRAINT `tl_com_tax_rates_ibfk_1` FOREIGN KEY (`profile_id`) REFERENCES `tl_com_tax_profiles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `tl_com_tax_rates_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `tl_countries` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `tl_com_tax_rates_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `tl_com_cities` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `tl_com_tax_rates_ibfk_4` FOREIGN KEY (`state_id`) REFERENCES `tl_com_state` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Indexes for table `tl_com_city_translations`
--
ALTER TABLE `tl_com_city_translations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `tl_com_country_translations`
--
ALTER TABLE `tl_com_country_translations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country_id` (`country_id`);

--
-- Indexes for table `tl_com_state_translations`
--
ALTER TABLE `tl_com_state_translations`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tl_com_city_translations`
--
ALTER TABLE `tl_com_city_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tl_com_country_translations`
--
ALTER TABLE `tl_com_country_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tl_com_state_translations`
--
ALTER TABLE `tl_com_state_translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tl_com_city_translations`
--
ALTER TABLE `tl_com_city_translations`
  ADD CONSTRAINT `tl_com_city_translations_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `tl_com_cities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tl_com_country_translations`
--
ALTER TABLE `tl_com_country_translations`
  ADD CONSTRAINT `tl_com_country_translations_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `tl_countries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
