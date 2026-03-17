-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 17, 2026 at 11:54 PM
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
-- Database: `sit_in_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `year_level` int(11) NOT NULL,
  `course` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `profile_picture` varchar(255) DEFAULT 'default.png',
  `sessions_remaining` int(11) DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `student_id`, `last_name`, `first_name`, `middle_name`, `email`, `year_level`, `course`, `password`, `address`, `profile_picture`, `sessions_remaining`, `created_at`) VALUES
(1, '23833353', 'Neri', 'Sergio', NULL, 'skwish@gmail.com', 1, 'College of Nursing', '$2y$10$NgupAq.ZeviK1Vq6p0Eev.qWJRdwR/T1kfm2f9soKhov58.byShyu', 'Pluto', 'default.png', 30, '2026-03-17 20:32:40'),
(3, '23833354', 'Ner', 'Ser', NULL, 'Ners@gmail.com', 2, 'College of Nursing', '$2y$10$SvOE8NTtlfED/.oyWnBmWOv9pQgV4t8K2pAmURbG1a9GIb5qLnMUy', 'Mars', 'default.png', 30, '2026-03-17 20:43:31'),
(4, '23833355', 'Neri', 'Sergio', NULL, 'gioneri1022@gmail.com', 3, 'College of Hospitality Management', '$2y$10$GTkVpOVqGo9i.Lxh7/6x/OliKJmd0CNFjYbkaBp0w4ktKNW800mA6', 'Pluto', 'default.png', 30, '2026-03-17 21:30:41'),
(6, '23833356', 'Cagas', 'Patrick', '', 'Pats@gmail.com', 3, 'College of Criminal Justice', '$2y$10$JmouXmd454csTLdrMQUiRuZbvPbLErwGbW5Z/nDrqqbc5E8ImEu6C', 'Planet Namek', 'user_6_1773788031.png', 30, '2026-03-17 22:34:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
