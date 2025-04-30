-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 03, 2023 at 06:08 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
INSERT INTO `tl_com_payment_methods` (`id`, `name`, `logo`, `docs`, `status`, `created_at`, `updated_at`) VALUES (NULL, 'Gpay',NULL, NULL, 1,current_timestamp(), NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tl_pages`
--
ALTER TABLE tl_pages
  ADD COLUMN is_home BOOLEAN DEFAULT 0,
  ADD COLUMN page_type VARCHAR(250) COLLATE utf8mb4_general_ci DEFAULT 'default';

--
-- AUTO_INCREMENT for dumped tables
--
COMMIT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
