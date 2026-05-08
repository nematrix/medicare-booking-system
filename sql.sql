CREATE DATABASE clinic_system;

USE clinic_system;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    national_identication VARCHAR NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,

    role ENUM('patient','doctor','admin') NOT NULL DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


DROP TABLE IF EXISTS `notifications`;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `role` enum('patient','doctor','admin') DEFAULT NULL,
  `appointment_id` int DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('appointment','system') DEFAULT 'appointment',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `role` (`role`),
  KEY `appointment_id` (`appointment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;