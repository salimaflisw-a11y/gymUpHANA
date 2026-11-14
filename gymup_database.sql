-- ====================================================================
-- GymUp Database Setup Script for phpMyAdmin / MySQL / MariaDB
-- Automatically creates the database `gymup` and all required tables
-- with relationships, constraints, indexes, and initial seed data.
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
  `title` VARCHAR(150) NOT NULL,
  `position_type` VARCHAR(255) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `requirements` LONGTEXT NOT NULL,
  `status` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: application
-- --------------------------------------------------------------------
CREATE TABLE `application` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `candidate_name` VARCHAR(150) NOT NULL,
  `candidate_email` VARCHAR(180) NOT NULL,
  `candidate_phone` VARCHAR(30) DEFAULT NULL,
  `cv_filename` VARCHAR(255) DEFAULT NULL,
  `cover_letter` LONGTEXT DEFAULT NULL,
  `status` VARCHAR(255) NOT NULL,
  `submitted_at` DATETIME NOT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `review_notes` LONGTEXT DEFAULT NULL,
  `job_posting_id` INT NOT NULL,
  INDEX `idx_app_job_posting` (`job_posting_id`),
  INDEX `idx_app_status` (`status`),
  INDEX `IDX_A45BDDC1F09E15EB` (`job_posting_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table: plan
-- --------------------------------------------------------------------
CREATE TABLE `plan` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` LONGTEXT DEFAULT NULL,
  `duration_months` INT NOT NULL,
  `price` DOUBLE PRECISION NOT NULL,
  `access_scope` VARCHAR(255) NOT NULL,
  `is_active` TINYINT DEFAULT 1 NOT NULL,
  `created_at` DATETIME NOT NULL,
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
  `created_at` DATETIME NOT NULL,
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
  `amount` DOUBLE PRECISION NOT NULL,
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
-- Initial Seed Data
-- --------------------------------------------------------------------

-- Default Accounts:
-- 1. admin@gymup.com / AdminPassword123!
-- 2. coach@gymup.com / CoachPassword123!
-- 3. receptionist@gymup.com / Receptionist123!
-- 4. member@gymup.com / MemberPassword123!
INSERT INTO `user` (`id`, `email`, `full_name`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'admin@gymup.com', 'GymUp Administrator', '$2y$12$WPhq1BhZPfwcdVVNa904wu/IcFm1e8wToMvjwxF4SpmBRW84it4l.', 'ROLE_ADMIN', 'ACTIVE', NOW()),
(2, 'coach@gymup.com', 'Alex Stone (Head Coach)', '$2y$12$3D2SDFd4NWjl.UNcsDYsiu0gYsIp6LyYxvpTGfOchl4KASFQ2K.JK', 'ROLE_COACH', 'ACTIVE', NOW()),
(3, 'receptionist@gymup.com', 'Sarah Jenkins (Front Desk)', '$2y$12$zYtRCM9ZiqbcGWq4FE7F6uiiPpswjbHIJpu8q5Coa9PlVOjRpY7su', 'ROLE_RECEPTIONIST', 'ACTIVE', NOW()),
(4, 'member@gymup.com', 'John Doe (Member)', '$2y$12$qV6tkY793U4LllXY43qfe.HixzUi2AIrkw7IDccvu1YIUvMstSmqO', 'ROLE_MEMBER', 'ACTIVE', NOW());

-- Initialize Schedule and Streak for default member
INSERT INTO `schedule` (`id`, `week_days`, `updated_at`, `member_id`) VALUES
(1, '["Monday", "Wednesday", "Friday"]', NOW(), 4);

INSERT INTO `streak` (`id`, `current_streak`, `last_success_date`, `member_id`) VALUES
(1, 3, CURDATE(), 4);

-- Membership Plans
INSERT INTO `plan` (`id`, `name`, `description`, `duration_months`, `price`, `access_scope`, `is_active`, `created_at`) VALUES
(1, 'Basic Fitness', 'Full gym floor access during off-peak and standard hours.', 1, 39.99, 'GYM_ONLY', 1, NOW()),
(2, 'Pro Athlete', 'Unlimited gym floor, studio classes, sauna, and recovery zone.', 3, 99.99, 'ALL_ACCESS', 1, NOW()),
(3, 'VIP Champion', 'All-access pass, personal coaching sessions, and nutrition consultation.', 12, 349.99, 'ALL_ACCESS', 1, NOW());

-- Active Subscription for default member
INSERT INTO `subscription` (`id`, `start_date`, `end_date`, `status`, `created_at`, `member_id`, `plan_id`) VALUES
(1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 'ACTIVE', NOW(), 4, 2);

-- Equipment Catalog
INSERT INTO `equipment` (`id`, `qr_code`, `name`, `category`, `target_muscles`, `model_number`, `setup_instructions`, `video_tutorial_url`, `image_url`) VALUES
(1, 'EQ-BENCH-01', 'Olympic Flat Bench Press', 'Free Weights', '["Chest", "Triceps", "Front Delts"]', 'HAMMER-ST-01', 'Position your eyes directly beneath the bar. Keep feet planted flat on the floor.', 'https://www.youtube.com/watch?v=rT7DgCr-3pg', 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=800&q=80'),
(2, 'EQ-SQUAT-01', 'Power Squat Rack', 'Free Weights', '["Quadriceps", "Glutes", "Hamstrings", "Core"]', 'ROGUE-MR-4', 'Set safety pins at mid-thigh height. Brace core and initiate with hips backward.', 'https://www.youtube.com/watch?v=bEv6CCg2BC8', 'https://images.unsplash.com/photo-1574680096145-d05b474e2155?auto=format&fit=crop&w=800&q=80'),
(3, 'EQ-CABLE-01', 'Dual Adjustable Pulley Cable Machine', 'Cables', '["Chest", "Shoulders", "Back", "Arms"]', 'LIFE-FIT-DAP', 'Adjust pulley height using the yellow pin lever. Select balanced pin weight.', 'https://www.youtube.com/watch?v=taI4XduLpTk', 'https://images.unsplash.com/photo-1540497077202-7c8a3999166f?auto=format&fit=crop&w=800&q=80'),
(4, 'EQ-LAT-01', 'Selectorized Lat Pulldown', 'Machines', '["Latissimus Dorsi", "Biceps", "Rhomboids"]', 'CYBEX-LP-9', 'Adjust thigh pad firmly over knees. Pull bar smoothly down to upper chest.', 'https://www.youtube.com/watch?v=CAwf7n6Luuc', 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?auto=format&fit=crop&w=800&q=80');

-- Badges
INSERT INTO `badge` (`id`, `code`, `name`, `description`, `icon`, `category`, `required_streak`, `required_visits`, `perk_reward`) VALUES
(1, 'FIRST_STEP', 'Iron Debut', 'Completed your very first check-in at GymUp.', 'trophy', 'ATTENDANCE', 0, 1, 'Free protein shake voucher'),
(2, 'SEVEN_DAY_STREAK', 'Week Warrior', 'Maintained a consistent 7-day attendance streak.', 'fire', 'STREAK', 7, 7, '10% discount on merchandise'),
(3, 'MONTH_OF_IRON', 'Centurion of Iron', 'Completed 30 consecutive days of dedicated training.', 'award', 'STREAK', 30, 30, '1 free 1-on-1 PT session'),
(4, 'PR_TITAN', 'PR Titan', 'Logged 5 new Personal Records in the PR Vault.', 'zap', 'STRENGTH', 0, 10, 'Exclusive GymUp Gym Shaker');

-- Assign First Badge to Member
INSERT INTO `member_badge` (`id`, `unlocked_at`, `is_perk_claimed`, `claimed_at`, `member_id`, `badge_id`) VALUES
(1, NOW(), 0, NULL, 4, 1);

-- Studio Classes
INSERT INTO `class_session` (`id`, `name`, `room`, `day_of_week`, `start_time`, `end_time`, `capacity`, `coach_id`) VALUES
(1, 'HIIT Beast Mode', 'Studio Alpha', 'Monday', '08:00:00', '09:00:00', 20, 2),
(2, 'Powerlifting Workshop', 'Iron Zone', 'Wednesday', '18:00:00', '19:30:00', 12, 2),
(3, 'Functional Mobility & Recovery', 'Studio Beta', 'Friday', '10:00:00', '11:00:00', 25, 2);

-- Community Challenges
INSERT INTO `community_challenge` (`id`, `title`, `description`, `goal_metric`, `target_goal`, `current_progress`, `start_date`, `end_date`, `status`, `reward_description`) VALUES
(1, 'Winter Push: 1,000,000 kg Lifted', 'Collective gym community challenge to move 1 million kg in compound lifts this month.', 'KG_LIFTED', 1000000, 342500, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'ACTIVE', 'Free GymUp Gold Hoodie for top 10 contributors');

-- Job Postings
INSERT INTO `job_posting` (`id`, `title`, `position_type`, `description`, `requirements`, `status`, `created_at`, `expires_at`) VALUES
(1, 'Certified Strength & Conditioning Coach', 'COACH', 'We are looking for an energetic, experienced trainer with CSCS or equivalent credentials to lead small group powerlifting and athlete development classes.', 'Bachelor in Sports Science or CSCS certification; 2+ years gym coaching experience; CPR/AED certified.', 'OPEN', NOW(), DATE_ADD(CURDATE(), INTERVAL 60 DAY)),
(2, 'Front Desk Member Experience Specialist', 'RECEPTIONIST', 'Join our welcoming community team! You will assist members with access, manage bookings, handle walk-in registrations, and maintain gym floor energy.', 'Customer service experience; friendly and professional communication; fitness enthusiast.', 'OPEN', NOW(), DATE_ADD(CURDATE(), INTERVAL 45 DAY));

SET FOREIGN_KEY_CHECKS = 1;

