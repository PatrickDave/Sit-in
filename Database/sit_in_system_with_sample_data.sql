-- Sit-in Monitoring System
-- Complete database import with sample data for lab/demo use

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `sit_in_system`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `sit_in_system`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `feedback_reports`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `sit_in_history`;
DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admins`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `admins` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `profile_image` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `student_id` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(150) NOT NULL,
  `year_level` INT NOT NULL,
  `course` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `address` TEXT NOT NULL,
  `profile_picture` VARCHAR(255) DEFAULT 'default.png',
  `sessions_remaining` INT DEFAULT 30,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_student_id` (`student_id`),
  UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reservations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `laboratory` VARCHAR(20) NOT NULL,
  `reservation_date` DATE NOT NULL,
  `time_in` TIME NOT NULL,
  `time_out` TIME NOT NULL,
  `pc_number` INT NOT NULL,
  `purpose` VARCHAR(255) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `admin_note` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reservation_user` (`user_id`),
  KEY `idx_reservation_status` (`status`),
  KEY `idx_reservation_date` (`reservation_date`),
  CONSTRAINT `fk_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sit_in_history` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `reservation_id` INT DEFAULT NULL,
  `laboratory` VARCHAR(100) NOT NULL,
  `purpose` VARCHAR(255) NOT NULL,
  `time_in` DATETIME NOT NULL,
  `time_out` DATETIME DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'successful',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_status_time` (`user_id`, `status`, `time_in`),
  KEY `idx_sitin_reservation_id` (`reservation_id`),
  CONSTRAINT `fk_sit_in_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `feedback_reports` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sit_in_id` INT DEFAULT NULL,
  `user_id` INT NOT NULL,
  `laboratory` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `report_date` DATE NOT NULL DEFAULT (CURRENT_DATE),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_feedback_date` (`report_date`),
  KEY `idx_feedback_user` (`user_id`),
  KEY `idx_feedback_sitin` (`sit_in_id`),
  CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `announcements` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `message` TEXT NOT NULL,
  `posted_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ann_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admins` (`id`, `username`, `password`, `name`, `email`, `profile_image`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@ccs.edu', NULL, CURRENT_TIMESTAMP);

INSERT INTO `users` (`id`, `student_id`, `last_name`, `first_name`, `middle_name`, `email`, `year_level`, `course`, `password`, `address`, `profile_picture`, `sessions_remaining`, `created_at`) VALUES
(1, '23833353', 'Neri', 'Sergio', NULL, 'skwish@gmail.com', 1, 'College of Nursing', '$2y$10$NgupAq.ZeviK1Vq6p0Eev.qWJRdwR/T1kfm2f9soKhov58.byShyu', 'Pluto', 'default.png', 30, '2026-03-17 20:32:40'),
(3, '23833354', 'Ner', 'Ser', NULL, 'Ners@gmail.com', 2, 'College of Nursing', '$2y$10$SvOE8NTtlfED/.oyWnBmWOv9pQgV4t8K2pAmURbG1a9GIb5qLnMUy', 'Mars', 'default.png', 30, '2026-03-17 20:43:31'),
(4, '23833355', 'Neri', 'Sergio', NULL, 'gioneri1022@gmail.com', 3, 'College of Hospitality Management', '$2y$10$GTkVpOVqGo9i.Lxh7/6x/OliKJmd0CNFjYbkaBp0w4ktKNW800mA6', 'Pluto', 'default.png', 30, '2026-03-17 21:30:41'),
(6, '23833356', 'Cagas', 'Patrick', '', 'Pats@gmail.com', 3, 'College of Criminal Justice', '$2y$10$JmouXmd454csTLdrMQUiRuZbvPbLErwGbW5Z/nDrqqbc5E8ImEu6C', 'Planet Namek', 'user_6_1773788031.png', 30, '2026-03-17 22:34:41');

ALTER TABLE `admins` AUTO_INCREMENT = 2;
ALTER TABLE `users` AUTO_INCREMENT = 7;

COMMIT;

-- Demo admin login:
-- username: admin
-- password: password
--
-- Demo student IDs already included:
-- 23833353
-- 23833354
-- 23833355
-- 23833356
