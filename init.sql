-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2025 at 01:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE
= "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone
= "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `first_web`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users`
(
  `id` int
(11) NOT NULL,
  `email` varchar
(50) NOT NULL,
  `password` varchar
(255) NOT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp
(),
  `role` enum
('admin','client') NOT NULL DEFAULT 'client',
  `first_name` varchar
(100) DEFAULT NULL,
  `last_name` varchar
(100) DEFAULT NULL,
  `phone` varchar
(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`
id`,
`email
`, `password`, `timestamp`, `role`, `first_name`, `last_name`, `phone`, `address`) VALUES
(1, 'vokhoinguyen2017@gmail.com', '$2y$10$XKWa2R1Jr1M.h.wTmscEEu4SA/nuqbgBuh9//Pj77AACApUlouUfC', '2025-05-09 04:21:57', 'admin', 'Nguyên', 'Võ', '0915538518', '22a/6 Đ. Thống Nhất, Đông Hoà, Dĩ An, Bình Dương'),
(9, 'admin@vpf.com', '$2y$10$Q3Q0nvZtFnvDL6W9.pOOPuSVHrnTLReSoTWE1X/71nTxWHpgwacq2', '2025-06-06 07:32:32', 'admin', NULL, NULL, NULL, NULL),
(10, 'customer1@test.com', '$2y$10$dU6ktKz8399MQnI3tDKgDumO.LL806qxW38tgmhNyemBkEPfwPcQi', '2025-06-06 07:32:32', 'client', NULL, NULL, NULL, NULL),
(11, 'customer2@test.com', '$2y$10$XAmQA2PO7nLuA1oWyv4kqOOOFMsfUFCbADCbMBBEsyxGpio4ez/1i', '2025-06-06 07:32:32', 'client', NULL, NULL, NULL, NULL),
(12, 'Nguyenvo10092004@gmail.com', '$2y$10$HYZBFGxz8y3xkG4wWIajzeXs1VcKwB9Vx6L7hv/JNjdR8tKgxjAym', '2025-06-06 18:23:31', 'client', NULL, NULL, NULL, NULL),
(13, 'danghophuonganh@gmail.com', '$2y$10$qfngz9VDRPj2xsgsu/P5Cu4pBD.9GzsiyFtqb3jNJksucAqXSVgp6', '2025-06-07 18:05:18', 'client', NULL, NULL, NULL, NULL),
(14, '12i3hni@gmail.com', '$2y$10$5J9mVMKlT3wWBWtn9yAtUelj6f/AI59SLYFrKTB2WwTAc6S4Al1am', '2025-06-10 15:49:13', 'client', 'Nguyen', NULL, NULL, NULL),
(15, 'nguyentrihainam2k5@gmail.com', '$2y$10$HQxo3MWtwGPa.UBUrLNGOepstINFKgZv9X1Ig1sj9m16o4O68GfcG', '2025-06-11 13:44:24', 'client', 'hainam', NULL, NULL, NULL),
(17, '23520981@gm.uit.edu.vn', '$2y$10$TTpdn0iIb5Hz2/LSxLnqaOaWiTFwlZkO3M2xR0rQRS2bfJ1pOGDVy', '2025-06-11 17:08:51', 'admin', 'hainam', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
ADD PRIMARY KEY
(`id`),
ADD UNIQUE KEY `username`
(`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int
(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
