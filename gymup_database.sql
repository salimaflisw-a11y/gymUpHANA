-- ====================================================================
-- GymUp Database Setup Script for phpMyAdmin / MySQL / MariaDB
-- Automatically creates the database `gymup` and all required tables
-- with relationships, constraints, indexes, and comprehensive seed data.
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `gymup` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gymup`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------------------
-- Drop existing tables
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `form_review`;
DROP TABLE IF EXISTS `buddy_match`;
DROP TABLE IF EXISTS `buddy_profile`;
DROP TABLE IF EXISTS `payment`;
DROP TABLE IF EXISTS `subscription`;
DROP TABLE IF EXISTS `plan`;
DROP TABLE IF EXISTS `application`;
DROP TABLE IF EXISTS `job_posting`;
DROP TABLE IF EXISTS `member_badge`;
DROP TABLE IF EXISTS `challenge_participant`;
DROP TABLE IF EXISTS `community_challenge`;
DROP TABLE IF EXISTS `badge`;
DROP TABLE IF EXISTS `equipment_log`;
DROP TABLE IF EXISTS `equipment`;
DROP TABLE IF EXISTS `waitlist_entry`;
DROP TABLE IF EXISTS `booking`;
DROP TABLE IF EXISTS `class_session`;
DROP TABLE IF EXISTS `check_in`;
DROP TABLE IF EXISTS `streak`;
DROP TABLE IF EXISTS `schedule`;
DROP TABLE IF EXISTS `user`;

-- --------------------------------------------------------------------
-- Table: user
-- --------------------------------------------------------------------
CREATE TABLE `user` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `email` VARCHAR(180) NOT NULL,
  `full_name` VARCHAR(150) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(255) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'ACTIVE',
  `created_at` DATETIME NOT NULL,
  UNIQUE INDEX `UNIQ_8D93D649E7927C74` (`email`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: schedule
-- --------------------------------------------------------------------
CREATE TABLE `schedule` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `week_days` JSON NOT NULL,
  `updated_at` DATETIME NOT NULL,
  `member_id` INT NOT NULL,
  UNIQUE INDEX `UNIQ_5A3811FB7597D3FE` (`member_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: streak
-- --------------------------------------------------------------------
CREATE TABLE `streak` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `current_streak` INT DEFAULT 0 NOT NULL,
  `last_success_date` DATE DEFAULT NULL,
  `member_id` INT NOT NULL,
  UNIQUE INDEX `UNIQ_198A1B297597D3FE` (`member_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: check_in
-- --------------------------------------------------------------------
CREATE TABLE `check_in` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `check_in_date` DATE NOT NULL,
  `scanned_at` DATETIME NOT NULL,
  `member_id` INT NOT NULL,
  INDEX `idx_checkin_member_date` (`member_id`, `check_in_date`),
  INDEX `IDX_90466CF97597D3FE` (`member_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: class_session
-- --------------------------------------------------------------------
CREATE TABLE `class_session` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `room` VARCHAR(100) NOT NULL,
  `day_of_week` VARCHAR(20) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `capacity` INT NOT NULL,
  `coach_id` INT NOT NULL,
  INDEX `idx_class_schedule` (`day_of_week`, `start_time`, `end_time`),
  INDEX `IDX_1531B2283C105691` (`coach_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: booking
-- --------------------------------------------------------------------
CREATE TABLE `booking` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `status` VARCHAR(255) NOT NULL,
  `booked_at` DATETIME NOT NULL,
  `spot_number` INT DEFAULT NULL,
  `spot_label` VARCHAR(50) DEFAULT NULL,
  `member_id` INT NOT NULL,
  `class_session_id` INT NOT NULL,
  INDEX `idx_booking_member_status` (`member_id`, `status`),
  INDEX `idx_booking_class_status` (`class_session_id`, `status`),
  INDEX `IDX_E00CEDDE7597D3FE` (`member_id`),
  INDEX `IDX_E00CEDDED7321E7D` (`class_session_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: waitlist_entry
-- --------------------------------------------------------------------
CREATE TABLE `waitlist_entry` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `queue_position` INT NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `offered_at` DATETIME DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `class_session_id` INT NOT NULL,
  `member_id` INT NOT NULL,
  INDEX `idx_waitlist_session_status` (`class_session_id`, `status`),
  INDEX `IDX_67447574D7321E7D` (`class_session_id`),
  INDEX `IDX_674475747597D3FE` (`member_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: equipment
-- --------------------------------------------------------------------
CREATE TABLE `equipment` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `qr_code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(60) NOT NULL,
  `target_muscles` JSON NOT NULL,
  `model_number` VARCHAR(80) DEFAULT NULL,
  `setup_instructions` LONGTEXT DEFAULT NULL,
  `video_tutorial_url` VARCHAR(255) DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  UNIQUE INDEX `UNIQ_D338D5837D8B1FB5` (`qr_code`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: equipment_log
-- --------------------------------------------------------------------
CREATE TABLE `equipment_log` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `weight_kg` DOUBLE PRECISION NOT NULL,
  `reps` INT NOT NULL,
  `sets` INT DEFAULT 1 NOT NULL,
  `seat_setting` VARCHAR(100) DEFAULT NULL,
  `estimated_1rm` DOUBLE PRECISION DEFAULT NULL,
  `is_personal_record` TINYINT DEFAULT 0 NOT NULL,
  `notes` LONGTEXT DEFAULT NULL,
  `logged_at` DATETIME NOT NULL,
  `member_id` INT NOT NULL,
  `equipment_id` INT NOT NULL,
  INDEX `idx_eq_log_member_eq` (`member_id`, `equipment_id`),
  INDEX `IDX_BEBFA0A47597D3FE` (`member_id`),
  INDEX `IDX_BEBFA0A4517FE9FE` (`equipment_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: badge
-- --------------------------------------------------------------------
CREATE TABLE `badge` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `code` VARCHAR(100) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `required_streak` INT DEFAULT 0 NOT NULL,
  `required_visits` INT DEFAULT 0 NOT NULL,
  `perk_reward` VARCHAR(150) DEFAULT NULL,
  UNIQUE INDEX `UNIQ_FEF0481D77153098` (`code`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: community_challenge
-- --------------------------------------------------------------------
CREATE TABLE `community_challenge` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `goal_metric` VARCHAR(50) NOT NULL,
  `target_goal` INT NOT NULL,
  `current_progress` INT DEFAULT 0 NOT NULL,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  `reward_description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: challenge_participant
-- --------------------------------------------------------------------
CREATE TABLE `challenge_participant` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `contributed_amount` INT DEFAULT 0 NOT NULL,
  `joined_at` DATETIME NOT NULL,
  `challenge_id` INT NOT NULL,
  `member_id` INT NOT NULL,
  UNIQUE INDEX `uniq_challenge_member` (`challenge_id`, `member_id`),
  INDEX `IDX_AE23C3AC98A21AC6` (`challenge_id`),
  INDEX `IDX_AE23C3AC7597D3FE` (`member_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: member_badge
-- --------------------------------------------------------------------
CREATE TABLE `member_badge` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `unlocked_at` DATETIME NOT NULL,
  `is_perk_claimed` TINYINT DEFAULT 0 NOT NULL,
  `claimed_at` DATETIME DEFAULT NULL,
  `member_id` INT NOT NULL,
  `badge_id` INT NOT NULL,
  INDEX `IDX_9CD1DDEE7597D3FE` (`member_id`),
  INDEX `IDX_9CD1DDEEF7A2C2FC` (`badge_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: job_posting
-- --------------------------------------------------------------------
CREATE TABLE `job_posting` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `position_type` VARCHAR(255) NOT NULL,
  `status` VARCHAR(255) NOT NULL,
  `opened_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: application
-- --------------------------------------------------------------------
CREATE TABLE `application` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(180) NOT NULL,
  `status` VARCHAR(255) NOT NULL,
  `submitted_at` DATETIME NOT NULL,
  `job_posting_id` INT NOT NULL,
  `cv_path` VARCHAR(255) NOT NULL,
  INDEX `idx_app_job_posting` (`job_posting_id`),
  INDEX `idx_app_status` (`status`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: plan
-- --------------------------------------------------------------------
CREATE TABLE `plan` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `duration_days` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `access_scope` VARCHAR(255) NOT NULL,
  `is_active` TINYINT DEFAULT 1 NOT NULL,
  `max_members` INT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: subscription
-- --------------------------------------------------------------------
CREATE TABLE `subscription` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` VARCHAR(255) NOT NULL,
  `member_id` INT NOT NULL,
  `plan_id` INT NOT NULL,
  INDEX `idx_subscription_member_status` (`member_id`, `status`),
  INDEX `IDX_A3C664D37597D3FE` (`member_id`),
  INDEX `IDX_A3C664D3E899029B` (`plan_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: payment
-- --------------------------------------------------------------------
CREATE TABLE `payment` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `method` VARCHAR(255) NOT NULL,
  `paid_at` DATETIME NOT NULL,
  `member_id` INT NOT NULL,
  `subscription_id` INT NOT NULL,
  `processed_by_id` INT DEFAULT NULL,
  INDEX `idx_payment_paid_at` (`paid_at`),
  INDEX `idx_payment_method` (`method`),
  INDEX `IDX_6D28840D7597D3FE` (`member_id`),
  INDEX `IDX_6D28840D9A1887DC` (`subscription_id`),
  INDEX `IDX_6D28840D2FFD4FD3` (`processed_by_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: buddy_profile
-- --------------------------------------------------------------------
CREATE TABLE `buddy_profile` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `training_style` VARCHAR(50) NOT NULL,
  `fitness_level` VARCHAR(30) NOT NULL,
  `preferred_days` JSON NOT NULL,
  `preferred_time` VARCHAR(30) NOT NULL,
  `looking_for_spotter` TINYINT DEFAULT 1 NOT NULL,
  `bio` LONGTEXT DEFAULT NULL,
  `is_active` TINYINT DEFAULT 1 NOT NULL,
  `member_id` INT NOT NULL,
  UNIQUE INDEX `UNIQ_290E7F567597D3FE` (`member_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: buddy_match
-- --------------------------------------------------------------------
CREATE TABLE `buddy_match` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  `intro_message` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `requester_id` INT NOT NULL,
  `target_member_id` INT NOT NULL,
  UNIQUE INDEX `uniq_requester_target` (`requester_id`, `target_member_id`),
  INDEX `IDX_1E20B3E2ED442CF4` (`requester_id`),
  INDEX `IDX_1E20B3E24123A1D2` (`target_member_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: form_review
-- --------------------------------------------------------------------
CREATE TABLE `form_review` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `exercise_name` VARCHAR(120) NOT NULL,
  `video_url` VARCHAR(255) NOT NULL,
  `member_notes` LONGTEXT DEFAULT NULL,
  `coach_feedback` LONGTEXT DEFAULT NULL,
  `form_rating` INT DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `member_id` INT NOT NULL,
  `coach_id` INT DEFAULT NULL,
  INDEX `IDX_7BAD55687597D3FE` (`member_id`),
  INDEX `IDX_7BAD55683C105691` (`coach_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Foreign Key Constraints
-- --------------------------------------------------------------------
ALTER TABLE `check_in` ADD CONSTRAINT `FK_90466CF97597D3FE` FOREIGN KEY (`member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `schedule` ADD CONSTRAINT `FK_5A3811FB7597D3FE` FOREIGN KEY (`member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `streak` ADD CONSTRAINT `FK_198A1B297597D3FE` FOREIGN KEY (`member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `booking` ADD CONSTRAINT `FK_E00CEDDE7597D3FE` FOREIGN KEY (`member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `booking` ADD CONSTRAINT `FK_E00CEDDED7321E7D` FOREIGN KEY (`class_session_id`) REFERENCES `class_session` (`id`) ON DELETE CASCADE;
ALTER TABLE `class_session` ADD CONSTRAINT `FK_1531B2283C105691` FOREIGN KEY (`coach_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `waitlist_entry` ADD CONSTRAINT `FK_67447574D7321E7D` FOREIGN KEY (`class_session_id`) REFERENCES `class_session` (`id`) ON DELETE CASCADE;
ALTER TABLE `waitlist_entry` ADD CONSTRAINT `FK_674475747597D3FE` FOREIGN KEY (`member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `equipment_log` ADD CONSTRAINT `FK_BEBFA0A47597D3FE` FOREIGN KEY (`member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `equipment_log` ADD CONSTRAINT `FK_BEBFA0A4517FE9FE` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE;
ALTER TABLE `challenge_participant` ADD CONSTRAINT `FK_AE23C3AC98A21AC6` FOREIGN KEY (`challenge_id`) REFERENCES `community_challenge` (`id`) ON DELETE CASCADE;
ALTER TABLE `challenge_participant` ADD CONSTRAINT `FK_AE23C3AC7597D3FE` FOREIGN KEY (`member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `member_badge` ADD CONSTRAINT `FK_9CD1DDEE7597D3FE` FOREIGN KEY (`member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `member_badge` ADD CONSTRAINT `FK_9CD1DDEEF7A2C2FC` FOREIGN KEY (`badge_id`) REFERENCES `badge` (`id`) ON DELETE CASCADE;
ALTER TABLE `application` ADD CONSTRAINT `FK_A45BDDC1F09E15EB` FOREIGN KEY (`job_posting_id`) REFERENCES `job_posting` (`id`) ON DELETE CASCADE;
ALTER TABLE `subscription` ADD CONSTRAINT `FK_A3C664D37597D3FE` FOREIGN KEY (`member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `subscription` ADD CONSTRAINT `FK_A3C664D3E899029B` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`);
ALTER TABLE `payment` ADD CONSTRAINT `FK_6D28840D7597D3FE` FOREIGN KEY (`member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `payment` ADD CONSTRAINT `FK_6D28840D9A1887DC` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`id`) ON DELETE CASCADE;
ALTER TABLE `payment` ADD CONSTRAINT `FK_6D28840D2FFD4FD3` FOREIGN KEY (`processed_by_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;
ALTER TABLE `buddy_match` ADD CONSTRAINT `FK_1E20B3E2ED442CF4` FOREIGN KEY (`requester_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `buddy_match` ADD CONSTRAINT `FK_1E20B3E24123A1D2` FOREIGN KEY (`target_member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `buddy_profile` ADD CONSTRAINT `FK_290E7F567597D3FE` FOREIGN KEY (`member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `form_review` ADD CONSTRAINT `FK_7BAD55687597D3FE` FOREIGN KEY (`member_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `form_review` ADD CONSTRAINT `FK_7BAD55683C105691` FOREIGN KEY (`coach_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

-- --------------------------------------------------------------------
-- Seed Data
-- --------------------------------------------------------------------

-- 1. Users
INSERT INTO `user` (`id`, `email`, `full_name`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'admin@gymup.com', 'GymUp Administrator', '$2y$12$WPhq1BhZPfwcdVVNa904wu/IcFm1e8wToMvjwxF4SpmBRW84it4l.', 'ROLE_ADMIN', 'ACTIVE', '2026-01-01 08:00:00'),
(2, 'coach@gymup.com', 'Alex Stone (Head Coach)', '$2y$12$3D2SDFd4NWjl.UNcsDYsiu0gYsIp6LyYxvpTGfOchl4KASFQ2K.JK', 'ROLE_COACH', 'ACTIVE', '2026-01-01 08:00:00'),
(3, 'receptionist@gymup.com', 'Sarah Jenkins (Front Desk Lead)', '$2y$12$zYtRCM9ZiqbcGWq4FE7F6uiiPpswjbHIJpu8q5Coa9PlVOjRpY7su', 'ROLE_RECEPTIONIST', 'ACTIVE', '2026-01-01 08:00:00'),
(4, 'member@gymup.com', 'John Doe (Member)', '$2y$12$qV6tkY793U4LllXY43qfe.HixzUi2AIrkw7IDccvu1YIUvMstSmqO', 'ROLE_MEMBER', 'ACTIVE', '2026-01-05 10:00:00'),
(5, 'marcus.vance@gymup.com', 'Marcus Vance (Strength Coach)', '$2y$12$3D2SDFd4NWjl.UNcsDYsiu0gYsIp6LyYxvpTGfOchl4KASFQ2K.JK', 'ROLE_COACH', 'ACTIVE', '2026-01-10 09:00:00'),
(6, 'elena.rostova@gymup.com', 'Elena Rostova (Yoga & Mobility)', '$2y$12$3D2SDFd4NWjl.UNcsDYsiu0gYsIp6LyYxvpTGfOchl4KASFQ2K.JK', 'ROLE_COACH', 'ACTIVE', '2026-01-15 09:30:00'),
(7, 'david.kim@gymup.com', 'David Kim (Boxing & Conditioning)', '$2y$12$3D2SDFd4NWjl.UNcsDYsiu0gYsIp6LyYxvpTGfOchl4KASFQ2K.JK', 'ROLE_COACH', 'ACTIVE', '2026-02-01 11:00:00'),
(8, 'leo.martinez@gymup.com', 'Leo Martinez (Front Desk)', '$2y$12$zYtRCM9ZiqbcGWq4FE7F6uiiPpswjbHIJpu8q5Coa9PlVOjRpY7su', 'ROLE_RECEPTIONIST', 'ACTIVE', '2026-02-10 08:30:00'),
(9, 'emily.clark@gmail.com', 'Emily Clark', '$2y$12$qV6tkY793U4LllXY43qfe.HixzUi2AIrkw7IDccvu1YIUvMstSmqO', 'ROLE_MEMBER', 'ACTIVE', '2026-02-15 14:00:00'),
(10, 'michael.brown@yahoo.com', 'Michael Brown', '$2y$12$qV6tkY793U4LllXY43qfe.HixzUi2AIrkw7IDccvu1YIUvMstSmqO', 'ROLE_MEMBER', 'ACTIVE', '2026-02-20 16:30:00'),
(11, 'sophia.taylor@gmail.com', 'Sophia Taylor', '$2y$12$qV6tkY793U4LllXY43qfe.HixzUi2AIrkw7IDccvu1YIUvMstSmqO', 'ROLE_MEMBER', 'ACTIVE', '2026-03-01 09:15:00'),
(12, 'liam.wilson@outlook.com', 'Liam Wilson', '$2y$12$qV6tkY793U4LllXY43qfe.HixzUi2AIrkw7IDccvu1YIUvMstSmqO', 'ROLE_MEMBER', 'ACTIVE', '2026-03-05 12:00:00'),
(13, 'olivia.martin@gmail.com', 'Olivia Martin', '$2y$12$qV6tkY793U4LllXY43qfe.HixzUi2AIrkw7IDccvu1YIUvMstSmqO', 'ROLE_MEMBER', 'ACTIVE', '2026-03-10 17:45:00'),
(14, 'noah.garcia@gmail.com', 'Noah Garcia', '$2y$12$qV6tkY793U4LllXY43qfe.HixzUi2AIrkw7IDccvu1YIUvMstSmqO', 'ROLE_MEMBER', 'ACTIVE', '2026-03-12 11:30:00'),
(15, 'ava.thompson@gmail.com', 'Ava Thompson', '$2y$12$qV6tkY793U4LllXY43qfe.HixzUi2AIrkw7IDccvu1YIUvMstSmqO', 'ROLE_MEMBER', 'ACTIVE', '2026-03-15 10:20:00'),
(16, 'lucas.white@gmail.com', 'Lucas White', '$2y$12$qV6tkY793U4LllXY43qfe.HixzUi2AIrkw7IDccvu1YIUvMstSmqO', 'ROLE_MEMBER', 'ACTIVE', '2026-03-18 15:10:00'),
(17, 'mia.harris@yahoo.com', 'Mia Harris', '$2y$12$qV6tkY793U4LllXY43qfe.HixzUi2AIrkw7IDccvu1YIUvMstSmqO', 'ROLE_MEMBER', 'ACTIVE', '2026-03-22 18:00:00');

-- 2. Plans
INSERT INTO `plan` (`id`, `name`, `duration_days`, `price`, `access_scope`, `is_active`, `max_members`) VALUES
(1, 'Basic Gym Floor', 30, 39.99, 'OPEN_GYM', 1, 150),
(2, 'Studio Enthusiast', 30, 59.99, 'CLASSES', 1, 80),
(3, 'Pro Athlete Pass', 90, 99.99, 'BOTH', 1, 200),
(4, 'VIP Champion Annual', 365, 349.99, 'BOTH', 1, 50),
(5, 'Weekend Warrior', 30, 29.99, 'OPEN_GYM', 1, 100),
(6, 'Seasonal Trial Pass (Archived)', 14, 19.99, 'BOTH', 0, 25);

-- 3. Subscriptions
INSERT INTO `subscription` (`id`, `start_date`, `end_date`, `status`, `member_id`, `plan_id`) VALUES
(1, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 75 DAY), 'ACTIVE', 4, 3),
(2, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'ACTIVE', 9, 2),
(3, DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 305 DAY), 'ACTIVE', 10, 4),
(4, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 85 DAY), 'ACTIVE', 11, 3),
(5, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'ACTIVE', 12, 1),
(6, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 88 DAY), 'ACTIVE', 13, 3),
(7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'PENDING', 14, 1),
(8, DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'EXPIRED', 15, 1),
(9, DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE(), 'CANCELLED', 16, 2),
(10, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 23 DAY), 'ACTIVE', 17, 5);

-- 4. Payments
INSERT INTO `payment` (`id`, `amount`, `method`, `paid_at`, `member_id`, `subscription_id`, `processed_by_id`) VALUES
(1, 99.99, 'ONLINE', DATE_SUB(NOW(), INTERVAL 15 DAY), 4, 1, NULL),
(2, 59.99, 'ONLINE', DATE_SUB(NOW(), INTERVAL 10 DAY), 9, 2, NULL),
(3, 349.99, 'ONLINE', DATE_SUB(NOW(), INTERVAL 60 DAY), 10, 3, NULL),
(4, 99.99, 'CASH', DATE_SUB(NOW(), INTERVAL 5 DAY), 11, 4, 3),
(5, 39.99, 'ONLINE', DATE_SUB(NOW(), INTERVAL 20 DAY), 12, 5, NULL),
(6, 99.99, 'ONLINE', DATE_SUB(NOW(), INTERVAL 2 DAY), 13, 6, NULL),
(7, 39.99, 'CASH', DATE_SUB(NOW(), INTERVAL 45 DAY), 15, 8, 8),
(8, 59.99, 'ONLINE', DATE_SUB(NOW(), INTERVAL 30 DAY), 16, 9, NULL),
(9, 29.99, 'ONLINE', DATE_SUB(NOW(), INTERVAL 7 DAY), 17, 10, NULL),
(10, 99.99, 'ONLINE', DATE_SUB(NOW(), INTERVAL 105 DAY), 4, 1, NULL),
(11, 59.99, 'CASH', DATE_SUB(NOW(), INTERVAL 40 DAY), 9, 2, 3),
(12, 39.99, 'ONLINE', DATE_SUB(NOW(), INTERVAL 50 DAY), 12, 5, NULL),
(13, 349.99, 'ONLINE', DATE_SUB(NOW(), INTERVAL 120 DAY), 10, 3, NULL);

-- 5. Schedules
INSERT INTO `schedule` (`id`, `week_days`, `updated_at`, `member_id`) VALUES
(1, '["MONDAY", "WEDNESDAY", "FRIDAY"]', DATE_SUB(NOW(), INTERVAL 20 DAY), 4),
(2, '["TUESDAY", "THURSDAY", "SATURDAY"]', DATE_SUB(NOW(), INTERVAL 10 DAY), 9),
(3, '["MONDAY", "TUESDAY", "WEDNESDAY", "FRIDAY", "SATURDAY"]', DATE_SUB(NOW(), INTERVAL 40 DAY), 10),
(4, '["MONDAY", "WEDNESDAY", "THURSDAY"]', DATE_SUB(NOW(), INTERVAL 5 DAY), 11),
(5, '["TUESDAY", "THURSDAY"]', DATE_SUB(NOW(), INTERVAL 15 DAY), 12),
(6, '["MONDAY", "WEDNESDAY", "FRIDAY", "SUNDAY"]', DATE_SUB(NOW(), INTERVAL 2 DAY), 13),
(7, '["SATURDAY", "SUNDAY"]', DATE_SUB(NOW(), INTERVAL 7 DAY), 17);

-- 6. Streaks
INSERT INTO `streak` (`id`, `current_streak`, `last_success_date`, `member_id`) VALUES
(1, 7, CURDATE(), 4),
(2, 14, CURDATE(), 9),
(3, 28, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 10),
(4, 5, CURDATE(), 11),
(5, 3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 12),
(6, 12, CURDATE(), 13),
(7, 0, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 15),
(8, 4, CURDATE(), 17);

-- 7. Check-ins
INSERT INTO `check_in` (`id`, `check_in_date`, `scanned_at`, `member_id`) VALUES
(1, CURDATE(), DATE_SUB(NOW(), INTERVAL 2 HOUR), 4),
(2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 50 HOUR), 4),
(3, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 98 HOUR), 4),
(4, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 170 HOUR), 4),
(5, DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 218 HOUR), 4),
(6, DATE_SUB(CURDATE(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 266 HOUR), 4),
(7, DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 338 HOUR), 4),
(8, DATE_SUB(CURDATE(), INTERVAL 16 DAY), DATE_SUB(NOW(), INTERVAL 386 HOUR), 4),
(9, CURDATE(), DATE_SUB(NOW(), INTERVAL 1 HOUR), 9),
(10, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 25 HOUR), 9),
(11, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 73 HOUR), 9),
(12, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 121 HOUR), 9),
(13, DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 193 HOUR), 9),
(14, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 241 HOUR), 9),
(15, DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 289 HOUR), 9),
(16, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 26 HOUR), 10),
(17, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 50 HOUR), 10),
(18, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 74 HOUR), 10),
(19, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 122 HOUR), 10),
(20, DATE_SUB(CURDATE(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 146 HOUR), 10),
(21, DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 194 HOUR), 10),
(22, DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 218 HOUR), 10),
(23, CURDATE(), DATE_SUB(NOW(), INTERVAL 3 HOUR), 11),
(24, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 51 HOUR), 11),
(25, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 99 HOUR), 11),
(26, DATE_SUB(CURDATE(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 147 HOUR), 11),
(27, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 27 HOUR), 12),
(28, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 75 HOUR), 12),
(29, DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 195 HOUR), 12),
(30, CURDATE(), DATE_SUB(NOW(), INTERVAL 4 HOUR), 13),
(31, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 52 HOUR), 13),
(32, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 100 HOUR), 13),
(33, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 172 HOUR), 13),
(34, CURDATE(), DATE_SUB(NOW(), INTERVAL 5 HOUR), 17),
(35, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 29 HOUR), 17),
(36, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 173 HOUR), 17),
(37, DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 197 HOUR), 17);

-- 8. Class Sessions
INSERT INTO `class_session` (`id`, `name`, `room`, `day_of_week`, `start_time`, `end_time`, `capacity`, `coach_id`) VALUES
(1, 'HIIT Beast Mode', 'Studio Alpha', 'MONDAY', '08:30:00', '09:30:00', 20, 2),
(2, 'Morning Power Yoga', 'Studio Zenith', 'MONDAY', '07:00:00', '08:00:00', 25, 6),
(3, 'Cross-Training Blast', 'Iron Cage', 'TUESDAY', '10:00:00', '11:00:00', 15, 5),
(4, 'Boxing Fundamentals', 'Combat Arena', 'TUESDAY', '18:00:00', '19:15:00', 16, 7),
(5, 'Powerlifting Workshop', 'Iron Zone', 'WEDNESDAY', '17:30:00', '19:00:00', 12, 2),
(6, 'Vinyasa Flow & Mindfulness', 'Studio Zenith', 'THURSDAY', '09:00:00', '10:00:00', 25, 6),
(7, 'Kettlebell Strength & Core', 'Functional Hall', 'THURSDAY', '18:30:00', '19:30:00', 15, 5),
(8, 'Functional Mobility & Recovery', 'Studio Beta', 'FRIDAY', '10:00:00', '11:00:00', 25, 2),
(9, 'Fight Club Conditioning', 'Combat Arena', 'FRIDAY', '17:00:00', '18:15:00', 18, 7),
(10, 'Weekend Spartan Bootcamp', 'Outdoor Turf', 'SATURDAY', '09:30:00', '11:00:00', 30, 5),
(11, 'Sunday Restorative Yin Yoga', 'Studio Zenith', 'SUNDAY', '10:30:00', '11:30:00', 20, 6);

-- 9. Bookings
INSERT INTO `booking` (`id`, `status`, `booked_at`, `spot_number`, `spot_label`, `member_id`, `class_session_id`) VALUES
(1, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 3 DAY), 1, 'Spot #1', 4, 1),
(2, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 2 DAY), 2, 'Spot #2', 9, 1),
(3, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 1 DAY), 3, 'Spot #3', 10, 1),
(4, 'CANCELLED', DATE_SUB(NOW(), INTERVAL 4 DAY), NULL, NULL, 12, 1),
(5, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 2 DAY), 1, 'Mat 01', 9, 2),
(6, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 1 DAY), 2, 'Mat 02', 11, 2),
(7, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 1 DAY), 3, 'Mat 03', 13, 2),
(8, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 5 DAY), 1, 'Platform 1', 4, 5),
(9, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 4 DAY), 2, 'Platform 2', 10, 5),
(10, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 3 DAY), 3, 'Platform 3', 11, 5),
(11, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 2 DAY), 1, 'Bag 01', 12, 4),
(12, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 1 DAY), 2, 'Bag 02', 13, 4),
(13, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 3 DAY), 1, 'Station A', 4, 8),
(14, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 2 DAY), 2, 'Station B', 9, 8),
(15, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 1 DAY), 1, 'Drill 01', 10, 10),
(16, 'CONFIRMED', DATE_SUB(NOW(), INTERVAL 1 DAY), 2, 'Drill 02', 17, 10);

-- 10. Waitlist Entries
INSERT INTO `waitlist_entry` (`id`, `queue_position`, `status`, `created_at`, `offered_at`, `expires_at`, `class_session_id`, `member_id`) VALUES
(1, 1, 'WAITING', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, NULL, 5, 12),
(2, 2, 'WAITING', DATE_SUB(NOW(), INTERVAL 12 HOUR), NULL, NULL, 5, 13),
(3, 1, 'OFFERED', DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_ADD(NOW(), INTERVAL 4 HOUR), 1, 17);

-- 11. Equipment
INSERT INTO `equipment` (`id`, `qr_code`, `name`, `category`, `target_muscles`, `model_number`, `setup_instructions`, `video_tutorial_url`, `image_url`) VALUES
(1, 'EQ-BENCH-01', 'Olympic Flat Bench Press', 'Free Weights', '["Chest", "Triceps", "Front Delts"]', 'HAMMER-ST-01', 'Position your eyes directly beneath the bar. Plant feet flat and retract shoulder blades.', 'https://www.youtube.com/watch?v=rT7DgCr-3pg', 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=800&q=80'),
(2, 'EQ-SQUAT-01', 'Power Squat Rack', 'Free Weights', '["Quadriceps", "Glutes", "Hamstrings", "Core"]', 'ROGUE-MR-4', 'Set safety pins at mid-thigh height. Brace core deeply, push hips back, and keep chest upright.', 'https://www.youtube.com/watch?v=bEv6CCg2BC8', 'https://images.unsplash.com/photo-1574680096145-d05b474e2155?auto=format&fit=crop&w=800&q=80'),
(3, 'EQ-CABLE-01', 'Dual Adjustable Pulley Cable Machine', 'Cables', '["Chest", "Shoulders", "Back", "Arms"]', 'LIFE-FIT-DAP', 'Adjust pulley height using the yellow pin lever. Maintain slight elbow bend during fly movements.', 'https://www.youtube.com/watch?v=taI4XduLpTk', 'https://images.unsplash.com/photo-1540497077202-7c8a3999166f?auto=format&fit=crop&w=800&q=80'),
(4, 'EQ-LAT-01', 'Selectorized Lat Pulldown', 'Machines', '["Latissimus Dorsi", "Biceps", "Rhomboids"]', 'CYBEX-LP-9', 'Adjust thigh pad firmly over knees. Pull bar smoothly to clavicle height without swinging.', 'https://www.youtube.com/watch?v=CAwf7n6Luuc', 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?auto=format&fit=crop&w=800&q=80'),
(5, 'EQ-LEGPRESS-01', '45-Degree Incline Leg Press', 'Machines', '["Quadriceps", "Glutes", "Calves"]', 'CYBEX-LP-45', 'Keep lower back glued to the pad. Do not lock knees at full extension.', 'https://www.youtube.com/watch?v=IZxyjW7MPJQ', 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=800&q=80'),
(6, 'EQ-ROWER-01', 'Concept2 Indoor Rower (Model D)', 'Cardio', '["Full Body", "Cardiovascular", "Back", "Legs"]', 'C2-MOD-D', 'Drive with legs first, lean back slightly, then pull handle into lower ribs.', 'https://www.youtube.com/watch?v=zQ82RYIFLN8', 'https://images.unsplash.com/photo-1517963879433-6ad2b056d712?auto=format&fit=crop&w=800&q=80'),
(7, 'EQ-ASSAULT-01', 'Assault AirBike Classic', 'Cardio', '["Full Body", "Metabolic Conditioning", "Shoulders"]', 'AB-CLASSIC', 'Adjust seat height to hip level. Push and pull handlebars rhythmically with pedal strokes.', 'https://www.youtube.com/watch?v=kU_eG0s3e3A', 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=800&q=80'),
(8, 'EQ-SEATEDROW-01', 'Low Cable Seated Row', 'Machines', '["Middle Back", "Lats", "Biceps"]', 'LIFE-FIT-SR', 'Sit upright with knees slightly bent. Pull handle into belly button squeezing shoulder blades.', 'https://www.youtube.com/watch?v=GZbfZ033f74', 'https://images.unsplash.com/photo-1534367507873-d2d7e24c797f?auto=format&fit=crop&w=800&q=80');

-- 12. Equipment Logs (PR Vault)
INSERT INTO `equipment_log` (`id`, `weight_kg`, `reps`, `sets`, `seat_setting`, `estimated_1rm`, `is_personal_record`, `notes`, `logged_at`, `member_id`, `equipment_id`) VALUES
(1, 100.0, 5, 3, 'Pin 3', 116.67, 1, 'New Bench Press PR! Felt solid with good pause at chest.', DATE_SUB(NOW(), INTERVAL 2 DAY), 4, 1),
(2, 95.0, 6, 3, 'Pin 3', 110.83, 0, 'Clean warm-up sets building up to 100kg.', DATE_SUB(NOW(), INTERVAL 9 DAY), 4, 1),
(3, 140.0, 3, 4, 'Hole 8', 154.00, 1, 'Heavy squat triples. Deep hip hinge and smooth lockouts.', DATE_SUB(NOW(), INTERVAL 4 DAY), 4, 2),
(4, 75.0, 8, 3, 'Thigh pad #4', 95.00, 1, 'Wide grip lat pulldown personal best.', DATE_SUB(NOW(), INTERVAL 7 DAY), 4, 4),
(5, 220.0, 10, 4, 'Backrest #2', 293.33, 1, 'Leg press volume set. Felt immense quad pump.', DATE_SUB(NOW(), INTERVAL 11 DAY), 4, 5),
(6, 125.0, 3, 5, 'Pin 4', 137.50, 1, 'Bench press milestone achieved! Clean touch and press.', DATE_SUB(NOW(), INTERVAL 3 DAY), 10, 1),
(7, 180.0, 2, 4, 'Hole 9', 192.00, 1, 'Massive squat double. Depth confirmed below parallel.', DATE_SUB(NOW(), INTERVAL 6 DAY), 10, 2),
(8, 320.0, 8, 4, 'Backrest #3', 405.33, 1, 'Max leg press sled loaded. Controlled eccentric.', DATE_SUB(NOW(), INTERVAL 8 DAY), 10, 5),
(9, 50.0, 8, 3, 'Pin 2', 63.33, 1, 'Bench press progressive overload reaching 50kg.', DATE_SUB(NOW(), INTERVAL 5 DAY), 9, 1),
(10, 85.0, 5, 4, 'Hole 6', 99.17, 1, 'Squat personal record! Focused on knee tracking.', DATE_SUB(NOW(), INTERVAL 1 DAY), 9, 2),
(11, 45.0, 10, 3, 'Thigh pad #2', 60.00, 0, 'Controlled lat pulls focusing on mind-muscle connection.', DATE_SUB(NOW(), INTERVAL 8 DAY), 9, 4),
(12, 60.0, 5, 3, 'Pin 2', 70.00, 1, 'Pushed past 55kg plateau on flat bench press.', DATE_SUB(NOW(), INTERVAL 3 DAY), 11, 1),
(13, 90.0, 6, 3, 'Hole 7', 105.00, 1, 'Solid squat set with great bar speed.', DATE_SUB(NOW(), INTERVAL 6 DAY), 11, 2),
(14, 55.0, 8, 3, 'Thigh pad #3', 69.67, 1, 'Lat pulldowns felt effortless today.', DATE_SUB(NOW(), INTERVAL 2 DAY), 13, 4),
(15, 60.0, 12, 3, 'Setting 5', 84.00, 1, 'Seated cable row personal record.', DATE_SUB(NOW(), INTERVAL 4 DAY), 13, 8);

-- 13. Badges
INSERT INTO `badge` (`id`, `code`, `name`, `description`, `icon`, `category`, `required_streak`, `required_visits`, `perk_reward`) VALUES
(1, 'FIRST_STEP', 'Iron Debut', 'Completed your very first check-in at GymUp.', 'trophy', 'ATTENDANCE', 0, 1, 'Free protein shake voucher at smoothie bar'),
(2, 'SEVEN_DAY_STREAK', 'Week Warrior', 'Maintained a consistent 7-day attendance streak.', 'fire', 'STREAK', 7, 7, '10% discount on gym apparel & merchandise'),
(3, 'MONTH_OF_IRON', 'Centurion of Iron', 'Completed 30 consecutive days of dedicated training.', 'award', 'STREAK', 30, 30, '1 free 1-on-1 Personal Training consultation session'),
(4, 'CENTURION_100', 'Centurion Club', 'Reached 100 lifetime gym check-ins.', 'shield', 'ATTENDANCE', 0, 100, 'Exclusive GymUp Centurion engraved locker plaque'),
(5, 'PR_TITAN', 'PR Titan', 'Logged 5 new Personal Records in the PR Vault.', 'zap', 'STRENGTH', 0, 10, 'Exclusive GymUp Matte-Black Gym Shaker'),
(6, 'EARLY_BIRD', 'Dawn Patrol', 'Completed 5 morning workouts before 8:00 AM.', 'sun', 'SPECIAL', 0, 5, 'Free morning specialty espresso for a week'),
(7, 'COMMUNITY_CHAMPION', 'Community Hero', 'Contributed significantly to gym community challenges.', 'users', 'CHALLENGE', 0, 15, 'GymUp Premium Gold Member Hoodie');

-- 14. Member Badges
INSERT INTO `member_badge` (`id`, `unlocked_at`, `is_perk_claimed`, `claimed_at`, `member_id`, `badge_id`) VALUES
(1, DATE_SUB(NOW(), INTERVAL 14 DAY), 1, DATE_SUB(NOW(), INTERVAL 10 DAY), 4, 1),
(2, DATE_SUB(NOW(), INTERVAL 7 DAY), 0, NULL, 4, 2),
(3, DATE_SUB(NOW(), INTERVAL 2 DAY), 0, NULL, 4, 5),
(4, DATE_SUB(NOW(), INTERVAL 12 DAY), 1, DATE_SUB(NOW(), INTERVAL 9 DAY), 9, 1),
(5, DATE_SUB(NOW(), INTERVAL 5 DAY), 1, DATE_SUB(NOW(), INTERVAL 3 DAY), 9, 2),
(6, DATE_SUB(NOW(), INTERVAL 40 DAY), 1, DATE_SUB(NOW(), INTERVAL 35 DAY), 10, 1),
(7, DATE_SUB(NOW(), INTERVAL 30 DAY), 1, DATE_SUB(NOW(), INTERVAL 25 DAY), 10, 2),
(8, DATE_SUB(NOW(), INTERVAL 15 DAY), 0, NULL, 10, 3),
(9, DATE_SUB(NOW(), INTERVAL 5 DAY), 0, NULL, 10, 5),
(10, DATE_SUB(NOW(), INTERVAL 6 DAY), 1, DATE_SUB(NOW(), INTERVAL 4 DAY), 11, 1),
(11, DATE_SUB(NOW(), INTERVAL 7 DAY), 1, DATE_SUB(NOW(), INTERVAL 5 DAY), 13, 1),
(12, DATE_SUB(NOW(), INTERVAL 2 DAY), 0, NULL, 13, 2);

-- 15. Community Challenges
INSERT INTO `community_challenge` (`id`, `title`, `description`, `goal_metric`, `target_goal`, `current_progress`, `start_date`, `end_date`, `status`, `reward_description`) VALUES
(1, 'Winter Push: 1,000,000 kg Lifted', 'Collective gym community challenge to move 1 million kg in compound lifts this month.', 'KG_LIFTED', 1000000, 482500, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 20 DAY), 'ACTIVE', 'Free GymUp Gold Hoodie for top 10 contributors'),
(2, 'Spring Consistency Quest: 500 Check-ins', 'Team challenge for the community to achieve 500 total check-ins in 30 days.', 'TOTAL_VISITS', 500, 312, DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_ADD(NOW(), INTERVAL 16 DAY), 'ACTIVE', 'Exclusive GymUp Stainless Steel Water Bottle for all participants'),
(3, 'Autumn Century Sprint', 'Community cardio and endurance sprint to kick off the fall season.', 'TOTAL_VISITS', 300, 300, DATE_SUB(NOW(), INTERVAL 45 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY), 'COMPLETED', 'Free Recovery Shake voucher & Gym Towel');

-- 16. Challenge Participants
INSERT INTO `challenge_participant` (`id`, `contributed_amount`, `joined_at`, `challenge_id`, `member_id`) VALUES
(1, 85400, DATE_SUB(NOW(), INTERVAL 9 DAY), 1, 4),
(2, 42100, DATE_SUB(NOW(), INTERVAL 8 DAY), 1, 9),
(3, 195200, DATE_SUB(NOW(), INTERVAL 10 DAY), 1, 10),
(4, 64800, DATE_SUB(NOW(), INTERVAL 7 DAY), 1, 11),
(5, 95000, DATE_SUB(NOW(), INTERVAL 6 DAY), 1, 13),
(6, 12, DATE_SUB(NOW(), INTERVAL 13 DAY), 2, 4),
(7, 14, DATE_SUB(NOW(), INTERVAL 14 DAY), 2, 9),
(8, 22, DATE_SUB(NOW(), INTERVAL 14 DAY), 2, 10),
(9, 8, DATE_SUB(NOW(), INTERVAL 10 DAY), 2, 11),
(10, 10, DATE_SUB(NOW(), INTERVAL 12 DAY), 2, 13),
(11, 6, DATE_SUB(NOW(), INTERVAL 7 DAY), 2, 17);

-- 17. Job Postings
INSERT INTO `job_posting` (`id`, `position_type`, `status`, `opened_at`) VALUES
(1, 'COACH', 'OPEN', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(2, 'RECEPTIONIST', 'OPEN', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(3, 'COACH', 'CLOSED', DATE_SUB(NOW(), INTERVAL 60 DAY));

-- 18. Applications
INSERT INTO `application` (`id`, `full_name`, `email`, `status`, `submitted_at`, `job_posting_id`, `cv_path`) VALUES
(1, 'Gabriel Vance', 'gabriel.vance@gmail.com', 'PENDING', DATE_SUB(NOW(), INTERVAL 8 DAY), 1, 'uploads/cvs/gabriel_vance_cv.pdf'),
(2, 'Clara Oswald', 'clara.oswald@yahoo.com', 'ACCEPTED', DATE_SUB(NOW(), INTERVAL 12 DAY), 1, 'uploads/cvs/clara_oswald_cv.pdf'),
(3, 'Jason Brody', 'jason.brody@gmail.com', 'REJECTED', DATE_SUB(NOW(), INTERVAL 15 DAY), 1, 'uploads/cvs/jason_brody_cv.pdf'),
(4, 'Nadia Thorne', 'nadia.thorne@outlook.com', 'PENDING', DATE_SUB(NOW(), INTERVAL 5 DAY), 2, 'uploads/cvs/nadia_thorne_cv.pdf'),
(5, 'Samir Patel', 'samir.patel@gmail.com', 'ACCEPTED', DATE_SUB(NOW(), INTERVAL 7 DAY), 2, 'uploads/cvs/samir_patel_cv.pdf'),
(6, 'Jessica Miller', 'jessica.m@yahoo.com', 'PENDING', DATE_SUB(NOW(), INTERVAL 2 DAY), 2, 'uploads/cvs/jessica_miller_cv.pdf');

-- 19. Buddy Profiles
INSERT INTO `buddy_profile` (`id`, `training_style`, `fitness_level`, `preferred_days`, `preferred_time`, `looking_for_spotter`, `bio`, `is_active`, `member_id`) VALUES
(1, 'POWERLIFTING', 'ADVANCED', '["MONDAY", "WEDNESDAY", "FRIDAY"]', 'EVENING', 1, 'Passionate about heavy compound movements (SBD). Looking for an experienced spotter for heavy bench & squat days.', 1, 4),
(2, 'FUNCTIONAL_FITNESS', 'INTERMEDIATE', '["TUESDAY", "THURSDAY", "SATURDAY"]', 'MORNING', 1, 'Love HIIT, studio classes, and athletic agility drills. Let us stay accountable together!', 1, 9),
(3, 'BODYBUILDING', 'ADVANCED', '["MONDAY", "TUESDAY", "WEDNESDAY", "FRIDAY", "SATURDAY"]', 'EVENING', 1, 'Push-Pull-Legs split training. Focusing on hypertrophy, strict form, and relentless consistency.', 1, 10),
(4, 'CALISTHENICS', 'INTERMEDIATE', '["MONDAY", "WEDNESDAY", "THURSDAY"]', 'AFTERNOON', 0, 'Working on muscle-ups, handstand push-ups, and core control. Happy to train and share tips.', 1, 11),
(5, 'GENERAL_FITNESS', 'BEGINNER', '["TUESDAY", "THURSDAY"]', 'EVENING', 1, 'Gym newcomer looking for friendly workout partner to learn proper form and build gym confidence.', 1, 12),
(6, 'POWERLIFTING', 'INTERMEDIATE', '["MONDAY", "WEDNESDAY", "FRIDAY"]', 'MORNING', 1, 'Training for my first local push-pull meet! Need someone who can call depth and motivate.', 1, 13),
(7, 'ENDURANCE_CARDIO', 'INTERMEDIATE', '["SATURDAY", "SUNDAY"]', 'MORNING', 0, 'Weekend warrior enjoying rowing, AirBike sprints, and functional conditioning circuits.', 1, 17);

-- 20. Buddy Matches
INSERT INTO `buddy_match` (`id`, `status`, `intro_message`, `created_at`, `requester_id`, `target_member_id`) VALUES
(1, 'ACCEPTED', 'Hey John! Saw you train heavy SBD on Mondays & Fridays. Would love to partner up and spot each other.', DATE_SUB(NOW(), INTERVAL 5 DAY), 10, 4),
(2, 'PENDING', 'Hi Emily! I attend the Tuesday HIIT class as well. Want to pair up for partner drills?', DATE_SUB(NOW(), INTERVAL 2 DAY), 9, 13),
(3, 'ACCEPTED', 'Hey Sophia! I saw your calisthenics routine, super inspiring. Let me know if you want to train core together.', DATE_SUB(NOW(), INTERVAL 4 DAY), 11, 4),
(4, 'DECLINED', 'Hey Liam, let me know if you want to train on Sundays.', DATE_SUB(NOW(), INTERVAL 8 DAY), 17, 12);

-- 21. Form Reviews
INSERT INTO `form_review` (`id`, `exercise_name`, `video_url`, `member_notes`, `coach_feedback`, `form_rating`, `status`, `created_at`, `reviewed_at`, `member_id`, `coach_id`) VALUES
(1, 'Conventional Deadlift', 'https://www.youtube.com/watch?v=op9kVnSso6Q', 'Trying to make sure my lower back is not rounding when lifting 140kg off the floor. Please check hip hinge.', 'Outstanding lift overall, John! Your spinal alignment is neutral throughout. Just make sure to pull the slack out of the barbell before breaking the floor.', 4, 'COMPLETED', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 4, 2),
(2, 'Barbell Back Squat', 'https://www.youtube.com/watch?v=bEv6CCg2BC8', 'Checking if my depth hits parallel and whether my knees are caving in during the concentric phase.', 'Great stance width! Your depth is solidly below parallel. Try pushing your knees out slightly more at the bottom transition to engage glute medius.', 5, 'COMPLETED', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 9, 5),
(3, 'Overhead Barbell Press', 'https://www.youtube.com/watch?v=2yjwXTZQDDI', 'Feeling slight pressure on lower back when pressing overhead. Not sure if I am hyper-extending.', 'Currently reviewing your clip with focus on core bracing and ribcage positioning.', NULL, 'IN_REVIEW', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, 11, 2),
(4, 'Dumbbell Incline Bench Press', 'https://www.youtube.com/watch?v=8iPEnn-ltC8', 'Wondering if my elbow flare angle is around 45 degrees or too wide.', NULL, NULL, 'SUBMITTED', DATE_SUB(NOW(), INTERVAL 6 HOUR), NULL, 13, NULL);

SET FOREIGN_KEY_CHECKS = 1;
