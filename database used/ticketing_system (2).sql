-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2025 at 12:18 PM
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
-- Database: `ticketing_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `cash`
--

CREATE TABLE `cash` (
  `cash_id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `cash_in` decimal(10,2) NOT NULL,
  `cash_out` decimal(10,2) NOT NULL,
  `cash_on_hand` decimal(10,2) NOT NULL,
  `shift_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_log`
--

CREATE TABLE `cash_log` (
  `id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `cash_in` decimal(10,2) NOT NULL,
  `cash_out` decimal(10,2) DEFAULT NULL,
  `cash_on_hand` decimal(10,2) NOT NULL,
  `log_date` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash_log`
--

INSERT INTO `cash_log` (`id`, `cashier_id`, `cash_in`, `cash_out`, `cash_on_hand`, `log_date`) VALUES
(3, 2, 7000.00, NULL, 7043.96, '2024-12-20'),
(4, 2, 7000.00, NULL, 7246.00, '2024-12-21'),
(6, 2, 1000.00, NULL, 1000.00, '2024-12-22'),
(7, 2, 1000.00, 1000.00, -3860.00, '2024-12-23'),
(8, 2, 1000.00, NULL, 1370.00, '2024-12-24'),
(9, 2, 2000.00, NULL, 2080.00, '2024-12-26'),
(10, 2, 2000.00, NULL, 2140.00, '2025-01-12');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `movie_name` varchar(255) NOT NULL,
  `ticket_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`id`, `movie_name`, `ticket_price`) VALUES
(8, 'Call Me by Your Name', 10.00),
(9, 'Stranger', 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `movie_name` varchar(100) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `total_sale` decimal(10,2) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `cashier_id`, `movie_name`, `quantity_sold`, `total_sale`, `sale_date`) VALUES
(1, 2, 'up', 2, 20.00, '2024-12-19 12:02:07'),
(2, 2, 'up', 2, 20.00, '2024-12-19 12:02:17'),
(3, 2, 'up', 2, 20.00, '2024-12-19 12:07:52'),
(4, 2, 'UP', 2, 20.00, '2024-12-19 12:08:25'),
(5, 2, 'hello', 3, 30.00, '2024-12-19 12:08:41'),
(6, 2, 'hello', 3, 30.00, '2024-12-19 12:11:56'),
(7, 2, 'hello', 3, 30.00, '2024-12-19 12:12:02'),
(8, 2, 'Hello, love, goodbye', 2, 20.00, '2024-12-19 12:12:28'),
(9, 2, 'Call Me by Your Name', 2, 20.00, '2024-12-20 11:33:54'),
(10, 2, 'Movie Title', 2, 21.98, '2024-12-20 12:28:32'),
(11, 2, 'Movie Title', 2, 21.98, '2024-12-20 12:29:51'),
(12, 2, 'Stranger', 12, 216.00, '2024-12-21 02:50:24'),
(13, 2, 'Call Me by Your Name', 1, 10.00, '2024-12-21 09:40:41'),
(14, 2, 'Call Me by Your Name', 2, 20.00, '2024-12-21 14:53:04');

-- --------------------------------------------------------

--
-- Table structure for table `showtimes`
--

CREATE TABLE `showtimes` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `showtime_start` datetime NOT NULL,
  `showtime_end` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `showtimes`
--

INSERT INTO `showtimes` (`id`, `movie_id`, `showtime_start`, `showtime_end`) VALUES
(1, 8, '2025-01-12 09:30:00', '2024-12-23 11:30:00'),
(2, 8, '2025-01-12 09:30:00', '2024-12-23 11:30:00'),
(3, 8, '2025-01-12 09:30:00', '2024-12-23 11:30:00'),
(4, 8, '2025-01-12 09:30:00', '2024-12-23 11:30:00'),
(5, 8, '2025-01-12 09:30:00', '2024-12-23 11:30:00'),
(6, 9, '2025-01-12 09:49:00', '2025-01-12 12:00:00'),
(7, 9, '2025-01-12 09:49:00', '2025-01-12 12:00:00'),
(8, 9, '2025-01-12 09:49:00', '2025-01-12 12:00:00'),
(9, 9, '2025-01-12 09:49:00', '2025-01-12 12:00:00'),
(10, 9, '2025-01-12 09:49:00', '2025-01-12 12:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_stock`
--

CREATE TABLE `ticket_stock` (
  `id` int(11) NOT NULL,
  `tickets_available` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_stock`
--

INSERT INTO `ticket_stock` (`id`, `tickets_available`, `movie_id`) VALUES
(8, 41, 8),
(9, 86, 9);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin1', '25e4ee4e9229397b6b17776bfceaf8e7', 'admin', '2024-12-17 09:13:24'),
(2, 'cashier1', 'b9c2192d721617da7c3cd13da1dcf3e7', 'cashier', '2024-12-17 09:13:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cash`
--
ALTER TABLE `cash`
  ADD PRIMARY KEY (`cash_id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `cash_log`
--
ALTER TABLE `cash_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `showtimes`
--
ALTER TABLE `showtimes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Indexes for table `ticket_stock`
--
ALTER TABLE `ticket_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_movie_id` (`movie_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cash`
--
ALTER TABLE `cash`
  MODIFY `cash_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_log`
--
ALTER TABLE `cash_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `showtimes`
--
ALTER TABLE `showtimes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `ticket_stock`
--
ALTER TABLE `ticket_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cash`
--
ALTER TABLE `cash`
  ADD CONSTRAINT `cash_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `cash_log`
--
ALTER TABLE `cash_log`
  ADD CONSTRAINT `cash_log_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `showtimes`
--
ALTER TABLE `showtimes`
  ADD CONSTRAINT `showtimes_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`);

--
-- Constraints for table `ticket_stock`
--
ALTER TABLE `ticket_stock`
  ADD CONSTRAINT `fk_movie_id` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
