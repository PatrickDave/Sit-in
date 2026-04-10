-- Sit-in Monitoring System
-- Complete database import for XAMPP / phpMyAdmin
-- Import this file once on the target machine.

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

INSERT INTO `admins` (`username`, `password`, `name`, `email`, `profile_image`)
VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@ccs.edu', NULL);

COMMIT;

-- Default admin login:
-- username: admin
-- password: password
