-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: sit_in_system
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;



--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `name` varchar(100) DEFAULT 'Admin',
  `email` varchar(100) DEFAULT 'admin@ccs.edu',
  `profile_image` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','$2y$10$3eMIBJagy12urofke9WPBu5/r1xd7p5NHVpdiJ2pbRfEXXPNTYthq','2026-03-22 09:29:47','Admin User','admin@ccs.edu',NULL);
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ann_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,'Attention! The founding titan has returned! Thou shall bow before your KING.',1,'2026-04-03 16:19:37'),(2,'Attention mga Kigwahon! Walay klase Abril 9 - Adlaw sa mga Isog. Pag amping mo permi og ayaw kalimot og ampo, Salamat sa tanan! Glory to God!',1,'2026-04-03 16:37:32'),(3,'Uncle Nestor! Asa na among allowance, wala nami mga bawn. Luoy na ang bata, wala\'y kaon.',1,'2026-04-09 17:46:04'),(4,'Attention! Mga gwapo diha, Meeting ta ron sabado April 11, 2026 sa may Plaza Independencia. Ato istoryahan asa ta nagkulang. Sa mga gwapo ra ha, again, mga gwapo ra karong sabado.',1,'2026-04-10 16:48:00');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_reports`
--

DROP TABLE IF EXISTS `feedback_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sit_in_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `laboratory` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `report_date` date NOT NULL DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_feedback_date` (`report_date`),
  KEY `idx_feedback_user` (`user_id`),
  KEY `idx_feedback_sitin` (`sit_in_id`),
  CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_reports`
--

LOCK TABLES `feedback_reports` WRITE;
/*!40000 ALTER TABLE `feedback_reports` DISABLE KEYS */;
INSERT INTO `feedback_reports` VALUES (1,6,10,'540','TEST! \r\nTEST!\r\nGreat Service!','2026-04-03','2026-04-03 15:43:39'),(2,5,12,'540','Thank you for the service!','2026-04-03','2026-04-05 14:45:09'),(3,4,12,'526','Thank you for the experience!','2026-04-03','2026-04-09 17:25:57'),(4,8,14,'530','Thank you so much!','2026-04-10','2026-04-09 17:38:02'),(5,10,15,'540','Great Service! Thank you everyone.','2026-04-10','2026-04-10 15:38:08'),(6,12,10,'540','Thank you Darling!','2026-04-11','2026-04-10 16:06:43');
/*!40000 ALTER TABLE `feedback_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `laboratory` varchar(20) NOT NULL,
  `reservation_date` date NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `pc_number` int(11) NOT NULL DEFAULT 1,
  `reservation_time` time NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `admin_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reservation_user` (`user_id`),
  KEY `idx_reservation_status` (`status`),
  KEY `idx_reservation_date` (`reservation_date`),
  CONSTRAINT `fk_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (1,12,'530','2026-04-13','13:20:00','15:20:00',1,'13:20:00','Activity','approved',NULL,'2026-04-10 15:18:21','2026-04-10 15:28:36'),(2,12,'544','2026-04-13','13:30:00','14:30:00',10,'00:00:00','Taking Exam','approved',NULL,'2026-04-10 15:29:39','2026-04-10 15:30:24'),(3,15,'540','2026-04-14','16:00:00','17:00:00',5,'00:00:00','Taking Major Exam','approved',NULL,'2026-04-10 15:37:39','2026-04-10 15:40:19'),(4,12,'524','2026-04-15','13:50:00','14:50:00',9,'00:00:00','Lab Activity','approved',NULL,'2026-04-10 15:43:34','2026-04-10 15:44:29'),(5,14,'530','2026-04-10','13:50:00','14:50:00',17,'00:00:00','Duwa ko Deadshot.io','denied',NULL,'2026-04-10 15:50:30','2026-04-10 16:08:20'),(6,10,'530','2026-04-10','16:00:00','17:00:00',6,'00:00:00','Continuation of Activity','approved',NULL,'2026-04-10 15:56:10','2026-04-10 16:00:19'),(7,15,'540','2026-04-17','15:10:00','16:00:00',2,'00:00:00','Exam','approved',NULL,'2026-04-10 16:33:54','2026-04-10 16:42:39'),(8,10,'544','2026-04-17','13:50:00','15:00:00',11,'00:00:00','Tiwas Activity','pending',NULL,'2026-04-10 16:49:14','2026-04-10 16:49:14');
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sit_in_history`
--

DROP TABLE IF EXISTS `sit_in_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sit_in_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `laboratory` varchar(100) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'successful',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_status_time` (`user_id`,`status`,`time_in`),
  CONSTRAINT `fk_sit_in_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sit_in_history`
--

LOCK TABLES `sit_in_history` WRITE;
/*!40000 ALTER TABLE `sit_in_history` DISABLE KEYS */;
INSERT INTO `sit_in_history` VALUES (1,1,NULL,'Lab 1','Programming Practice','2026-03-30 18:37:40','2026-03-30 19:37:40','successful','2026-03-30 12:37:40','2026-03-30 12:37:40'),(3,7,NULL,'526','C-Programming','2026-04-03 22:41:51','2026-04-03 22:43:59','completed','2026-04-03 14:41:51','2026-04-03 14:43:59'),(4,12,NULL,'526','Academic Sit-in','2026-04-03 22:48:49','2026-04-03 23:38:06','completed','2026-04-03 14:48:49','2026-04-03 15:38:06'),(5,12,NULL,'540','Academic Sit-in','2026-04-03 23:38:23',NULL,'active','2026-04-03 15:38:23','2026-04-03 15:38:23'),(6,10,NULL,'540','Academic Sit-in','2026-04-03 23:43:19','2026-04-11 00:02:03','completed','2026-04-03 15:43:19','2026-04-10 16:02:03'),(7,13,NULL,'540','Programming Practice','2026-04-04 00:27:50',NULL,'active','2026-04-03 16:27:50','2026-04-03 16:27:50'),(8,14,NULL,'530','Academic Sit-in','2026-04-10 01:34:55','2026-04-10 01:37:31','completed','2026-04-09 17:34:55','2026-04-09 17:37:31'),(9,14,NULL,'526','Academic Sit-in','2026-04-10 01:37:41','2026-04-10 23:45:10','completed','2026-04-09 17:37:41','2026-04-10 15:45:10'),(10,15,NULL,'540','Programming Practice','2026-04-10 23:36:50','2026-04-10 23:45:07','completed','2026-04-10 15:36:50','2026-04-10 15:45:07'),(11,14,NULL,'530','Academic Sit-in','2026-04-10 23:49:57','2026-04-11 00:46:48','completed','2026-04-10 15:49:57','2026-04-10 16:46:48'),(12,10,NULL,'540','Academic Sit-in','2026-04-11 00:02:19','2026-04-11 00:04:40','completed','2026-04-10 16:02:19','2026-04-10 16:04:40'),(13,10,NULL,'540','Academic Sit-in','2026-04-11 00:04:49','2026-04-11 00:46:44','completed','2026-04-10 16:04:49','2026-04-10 16:46:44'),(14,10,NULL,'524','Academic Sit-in','2026-04-11 00:48:13',NULL,'active','2026-04-10 16:48:13','2026-04-10 16:48:13');
/*!40000 ALTER TABLE `sit_in_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'session_timeout','1','2026-03-22 11:12:56'),(2,'max_daily_sessions','3','2026-03-22 11:12:33'),(3,'lab_open_time','07:00','2026-03-22 11:12:33'),(4,'lab_close_time','18:00','2026-03-22 11:12:33');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'23833353','Neri','Sergio',NULL,'skwish@gmail.com',1,'College of Nursing','$2y$10$NgupAq.ZeviK1Vq6p0Eev.qWJRdwR/T1kfm2f9soKhov58.byShyu','Pluto','default.png',30,'2026-03-17 20:32:40'),(3,'23833354','Ner','Ser',NULL,'Ners@gmail.com',2,'College of Nursing','$2y$10$SvOE8NTtlfED/.oyWnBmWOv9pQgV4t8K2pAmURbG1a9GIb5qLnMUy','Mars','default.png',30,'2026-03-17 20:43:31'),(4,'23833355','Neri','Sergio',NULL,'gioneri1022@gmail.com',3,'College of Hospitality Management','$2y$10$GTkVpOVqGo9i.Lxh7/6x/OliKJmd0CNFjYbkaBp0w4ktKNW800mA6','Pluto','default.png',30,'2026-03-17 21:30:41'),(6,'23833356','Cagas','Patrick','','Pats@gmail.com',3,'College of Criminal Justice','$2y$10$JmouXmd454csTLdrMQUiRuZbvPbLErwGbW5Z/nDrqqbc5E8ImEu6C','Planet Namek','user_6_1773788031.png',30,'2026-03-17 22:34:41'),(7,'23844954','ALMINAZA','CHRISTIAN','F.','christianexqueilalminaza@gmail.com',3,'College of Computer Studies','$2y$10$YM2rzGnjWy3bztlyKqAx1udp19ZqeTRkfvmc1ybENcxXfoZ7oRm/C','MANDAUE','default.png',29,'2026-03-22 06:52:28'),(8,'23844971','ALMINAZA','CHRISTIAN','F.','christian@gmail.com',3,'College of Nursing','$2y$10$O5ZzPMVgxowraFPAzn2b0e3kjhk61.DCk2.ifxy2trCScFocNvVGK','MANDAUE','user_8_1774162475.jpeg',30,'2026-03-22 06:53:53'),(9,'23833352','Neri','Sergio','','gioneri123@gmail.com',3,'College of Arts and Sciences','$2y$10$nHGGbo66cgU/tyEew5yjZ.z7OnOxJ8LmUPucv/xQtl5yeSKVlhd2C','LEYTE','default.png',30,'2026-03-22 07:58:38'),(10,'23826514','CAGAS','PATRICK DAVE','FEROLINO','patrickcagas123@gmail.com',3,'College of Computer Studies','$2y$10$p76ar.IsUBg75.AHPJTTZel9W9PcYrPYZSkB265SWqNUuYjMgumY.','382 - A JONES AVENUE ST. SAMBAG 2, CEBU CITY','default.png',27,'2026-03-22 09:21:07'),(11,'23826511','Montecillo','Shaw','D.','patrickcagas@gmail.com',4,'College of Criminal Justice','$2y$10$F1INEa33wvw44WLmWAfcBe6RX9/Zhf5yS/XlHPpaCKTIX6B3QluQS','Mandaue','default.png',30,'2026-04-03 14:03:53'),(12,'23826512','Shawn','Dave','D.','daveshawn@gmail.com',3,'College of Social Work','$2y$10$qvUPbxYxik914eYvBhe5GejwJTtQIRP80bTWOMUpvYzwxyhjWnRvG','Guadalupe','default.png',29,'2026-04-03 14:48:43'),(13,'23826513','Davis','Shawn','D.','shawndavis@gmail.com',3,'College of Hospitality Management','$2y$10$o6oDPuiftgy4/C1MvXjuO.PVQeDvXy0fenv38Hft9k.4KnYK1eCgO','Labangon','default.png',30,'2026-04-03 16:27:44'),(14,'23826516','Aurelius','Marcus','D.','test@gmail.com',2,'College of Business and Accountancy','$2y$10$UcT48yTjAPX9fO6T3TpxqOenD8D9obG09r.lxbiMdbap9Kw/YEAJ.','ROMAN','default.png',27,'2026-04-09 17:34:48'),(15,'23826519','Aurelious','Maximus','C.','Maximus@gmail.com',4,'BSIT','$2y$10$8zBTlbzsHv/8VBuHKEtoSeAUHAk568RSuMBWKeKmnQrDR1AXo3eIK','ROMAN EMPIRE','default.png',29,'2026-04-10 15:36:33');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-11  1:01:56
