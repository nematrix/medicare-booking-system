-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 08, 2026 at 11:22 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clinic_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `appointment_date` datetime NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_date`, `reason`, `status`, `created_at`) VALUES
(1, 6, 3, '2026-05-12 13:00:00', 'am not feeling', 'completed', '2026-05-02 15:12:27'),
(2, 6, 3, '2026-05-12 15:00:00', 'ffff', 'completed', '2026-05-02 15:36:40'),
(3, 5, 7, '2026-05-04 08:04:00', 'ffdgtTJY5TGNH', 'completed', '2026-05-02 18:35:41'),
(4, 6, 7, '2026-05-11 14:37:00', 'hgjtdfgjklkyyjhnfgjy', 'completed', '2026-05-02 19:37:44'),
(5, 5, 3, '2026-05-05 14:00:00', 'ghfnhf,hkltygfxc', 'completed', '2026-05-02 20:17:13'),
(6, 5, 7, '2026-05-25 14:29:00', 'ffgtt', 'completed', '2026-05-02 20:29:47'),
(7, 2, 7, '2026-05-06 07:00:00', 'am not feeling oky on my chest', 'approved', '2026-05-04 14:46:15'),
(8, 2, 7, '2026-05-02 04:19:00', 'dddddddddd', 'approved', '2026-05-05 09:17:11'),
(9, 10, 1, '2026-05-20 14:57:00', 'heart faiture', 'approved', '2026-05-05 21:58:19'),
(10, 12, 1, '2026-05-26 15:01:00', 'heart faiture', 'approved', '2026-05-05 22:02:04'),
(11, 18, 17, '2026-05-19 07:52:00', 'f', 'approved', '2026-05-08 08:52:46'),
(12, 18, 17, '2026-05-08 07:56:00', 'h', 'approved', '2026-05-08 08:57:06'),
(13, 19, 7, '2026-05-15 07:21:00', 'fsdfs', 'approved', '2026-05-08 09:18:53');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `specialization` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_number` (`license_number`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `specialization`, `license_number`, `phone`, `consultation_fee`) VALUES
(1, 3, 'maso', '67865', '0996567538', 34.00),
(2, 17, 'maso', '678624', '0999654335', 0.01),
(3, 20, 'maso', '', '0995467538', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

DROP TABLE IF EXISTS `doctor_schedules`;
CREATE TABLE IF NOT EXISTS `doctor_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int DEFAULT '30',
  `is_available` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `doctor_id` (`doctor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`, `slot_duration`, `is_available`, `created_at`) VALUES
(1, 3, 'Monday', '00:39:00', '02:41:00', 33, 1, '2026-05-02 15:35:33'),
(2, 3, 'Friday', '08:21:00', '16:27:00', 30, 1, '2026-05-02 17:21:43'),
(3, 7, 'Monday', '07:00:00', '17:01:00', 30, 1, '2026-05-02 18:25:02'),
(4, 7, 'Tuesday', '05:00:00', '19:30:00', 232, 1, '2026-05-02 20:40:26'),
(5, 7, 'Wednesday', '07:00:00', '17:00:00', 143, 1, '2026-05-04 14:04:53'),
(6, 7, 'Tuesday', '07:04:00', '17:24:00', 30, 1, '2026-05-05 09:25:09'),
(7, 17, 'Monday', '07:01:00', '17:00:00', 32, 1, '2026-05-08 08:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `role` enum('patient','doctor','admin') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `appointment_id` int DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('appointment','system') COLLATE utf8mb4_unicode_ci DEFAULT 'appointment',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `role`, `appointment_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 5, 'patient', 6, 'Your appointment has been APPROVED', 'appointment', 1, '2026-05-02 20:35:23'),
(2, 6, 'patient', 4, 'Your appointment has been APPROVED', 'appointment', 1, '2026-05-02 20:35:26'),
(3, 5, 'patient', 3, 'Your appointment has been APPROVED', 'appointment', 1, '2026-05-02 20:35:28'),
(4, 5, 'patient', 6, 'Your appointment has been COMPLETED', 'appointment', 1, '2026-05-02 20:40:55'),
(5, 5, 'patient', 6, 'Your appointment has been COMPLETED', 'appointment', 1, '2026-05-02 20:40:58'),
(6, 6, 'patient', 4, 'Your appointment has been COMPLETED', 'appointment', 1, '2026-05-02 20:40:59'),
(7, 5, 'patient', 3, 'Your appointment has been COMPLETED', 'appointment', 1, '2026-05-02 20:41:02'),
(8, 5, 'patient', 5, 'Your appointment has been APPROVED', 'appointment', 1, '2026-05-03 10:06:22'),
(9, 7, 'doctor', 7, 'Patrick Matemba booked an appointment on May 06, 2026 07:00 AM', 'appointment', 0, '2026-05-04 14:46:15'),
(10, 2, 'patient', 7, 'Your appointment was booked successfully.', 'appointment', 1, '2026-05-04 14:46:15'),
(11, 1, 'admin', 7, 'New appointment booked by Patrick Matemba', 'appointment', 0, '2026-05-04 14:46:15'),
(12, 2, 'patient', 7, 'Your appointment has been APPROVED', 'appointment', 1, '2026-05-04 14:47:27'),
(13, 7, 'doctor', 8, 'Patrick Matemba booked an appointment on May 02, 2026 04:19 AM', 'appointment', 0, '2026-05-05 09:17:11'),
(14, 2, 'patient', 8, 'Your appointment was booked successfully.', 'appointment', 1, '2026-05-05 09:17:11'),
(15, 1, 'admin', 8, 'New appointment booked by Patrick Matemba', 'appointment', 0, '2026-05-05 09:17:11'),
(16, 2, 'patient', 8, 'Your appointment has been APPROVED', 'appointment', 1, '2026-05-05 09:23:17'),
(17, 5, 'patient', 5, 'Your appointment has been COMPLETED', 'appointment', 1, '2026-05-06 12:40:37'),
(18, 17, 'doctor', 12, 'ULEMU KAMPEZENI booked an appointment on May 08, 2026 07:56 AM', 'appointment', 0, '2026-05-08 08:57:06'),
(19, 18, 'patient', 12, 'Your appointment was booked successfully.', 'appointment', 0, '2026-05-08 08:57:06'),
(20, 1, 'admin', 12, 'New appointment booked by ULEMU KAMPEZENI', 'appointment', 0, '2026-05-08 08:57:06'),
(21, 18, 'patient', 11, 'Your appointment has been APPROVED', 'appointment', 0, '2026-05-08 08:58:35'),
(22, 18, 'patient', 12, 'Your appointment has been APPROVED', 'appointment', 0, '2026-05-08 08:58:36'),
(23, 7, 'doctor', 13, 'New appointment from LOVENESS CHAKALAMBA', 'appointment', 0, '2026-05-08 09:18:53'),
(24, 19, 'patient', 13, 'Your appointment has been APPROVED', 'appointment', 0, '2026-05-08 09:20:03');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
CREATE TABLE IF NOT EXISTS `patients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `blood_group` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `phone`, `gender`, `date_of_birth`, `address`, `blood_group`) VALUES
(1, 9, '0995520450', 'male', '1992-04-21', 'Chigumula market Blantyre Malawi', 'A+'),
(2, 10, '0882128681', 'female', '2005-11-26', 'Chigumula market Blantyre Malawi', NULL),
(3, 12, '087654345664', 'female', '1991-11-26', 'Chigumula market Blantyre Malawi', NULL),
(4, 18, '0882128213', 'male', '1997-07-27', 'Chigumula market Blantyre Malawi', 'O'),
(5, 19, '0999654335', 'female', '1999-06-08', 'Chigumula market Blantyre Malawi', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `national_identification` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('patient','doctor','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'patient',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `national_identification` (`national_identification`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `national_identification`, `password`, `role`, `created_at`, `status`, `profile_image`) VALUES
(1, 'effie magombo', 'effiemagombo@gmail.com', '', '$2y$10$ovqDhM6m4JFSnb56kAADDuMq2rUqTW7piL2TJLTz.7vD3jUxILeoO', 'admin', '2026-05-01 10:19:56', 'active', 'uploads/admins/1778229549_1e6116a844cc4149aa318ef4093aabc4.jpg'),
(2, 'Patrick Matemba', 'patrickmatemba919@gmail.com', '22222', '$2y$10$9R.at9AkDRtPqycCAnmHHO24Wqd7ObLL6.YZTj9/CpCFE17.YHqvq', 'patient', '2026-05-01 11:06:16', 'active', NULL),
(3, 'beauty matemba', 'beautymatemba@gmail.com', '43454546', '$2y$10$TzO1h/Vm1ynUVTuJHxoTiuh.6bGX3pwxACbw7qkK8W5DZ2r4M2jz2', 'doctor', '2026-05-01 11:20:24', 'active', 'uploads/doctors/1778236471_1e6116a844cc4149aa318ef4093aabc4.jpg'),
(5, 'samuel chimbuka', 'samuelchimbuka@gmail.com', '222222222', '$2y$10$5aECuopG3MDSwrmI5k7//epHIUSeEDOk7/csL7N4N2c6MwlvSS74y', 'patient', '2026-05-01 13:18:07', 'active', NULL),
(6, 'josophine namponya', 'josophinenamponya@gmail.com', '56565656', '$2y$10$NJfvo2o8lAg.09IXRqX2N.D4ei6jR/G0hkZCQHGudnBnMG1ED8U6a', 'patient', '2026-05-02 13:20:06', 'active', 'uploads/patients/1778228700_1e6116a844cc4149aa318ef4093aabc4.jpg'),
(7, 'vincent chinomba', 'vincentchinomba@gmail.com', '333333333', '$2y$10$.OxKynFfSfjyXTyPviX.Tep7t9Sl8avKrUA/mj0XPrefXvkoNA7PS', 'doctor', '2026-05-02 18:21:15', 'active', NULL),
(8, 'peter amos', 'peteramos@gmail.com', '5674wQT', '$2y$10$eGp/OcBCGqHqe9TsLyEVbe3hQ69hTRfOEbIWaTxp5VktbLTNdeElG', 'patient', '2026-05-04 15:01:52', 'active', NULL),
(9, 'Jesca Chinomba', 'Jescachinomba@gmail.com', 'QWEDT45T', '$2y$10$XsB7Cls1cwHMB3Z/IXrgze8p6Jk23e5ykUdeAvu1H9URYAAf2tmKW', 'patient', '2026-05-05 19:22:22', 'active', 'uploads/patients/1778228167_1e6116a844cc4149aa318ef4093aabc4.jpg'),
(10, 'Cynthia Magombo', 'Cythiamagombo@gmail.com', 'QWERTY45', '$2y$10$jZP/EgyJxCDA2E43J9gSMOXjGyIYJPWpyNMER40eEkqKrddmMMZ92', 'patient', '2026-05-05 21:58:19', 'active', NULL),
(12, 'Martha Chakalamba', 'marthachalamba@gmail.com', 'QWERTY789', '$2y$10$GZeaH1vfKMpy/Si7dg/eve2DgkhY9Zq///j5xKyZyWtPEwEmYuIDm', 'patient', '2026-05-05 22:02:04', 'active', NULL),
(17, 'Takondwa kapyola', 'takondwakapyola@gmail.com', 'QWEDT81T', '$2y$10$AGPPuG0DzHrLSDHzcyrRpexr1vWXzV1eJ.t/EgnNXOUTUOYqnBoTq', 'doctor', '2026-05-08 07:03:10', 'active', 'uploads/doctors/1778228078_IMG-20251124-WA0002.jpg'),
(18, 'ULEMU KAMPEZENI', 'ulemukampezeni@gmail.com', 'QWEDTJKT', '$2y$10$iOuHbnva4X4ZXgsgzIsE1eBhSDEWc3J8QK7IlycYLD32coW1NbPbq', 'patient', '2026-05-08 08:29:12', 'active', 'uploads/patients/1778229013_1e6116a844cc4149aa318ef4093aabc4.jpg'),
(19, 'LOVENESS CHAKALAMBA', 'christina Matemba', 'QWERTY533', '$2y$10$lOCwTJB8ZwllXmpE5y61Mukag1Q07J.wbG5ubc8S8EqYsfN3gLW5e', 'patient', '2026-05-08 09:18:53', 'active', NULL),
(20, 'mphukira Banda', 'mphukirabanda@gmail.com', '889849', '$2y$10$s0I1Hd2IRc/F.3T08IYglucOlqJ6kHXboPNxVk1JVj2RSc1LQZI5W', 'doctor', '2026-05-08 09:41:01', 'active', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
