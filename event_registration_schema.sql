-- Event Registration Module Database Schema
-- SQL dump file for custom database tables

-- Event Configuration Table
CREATE TABLE IF NOT EXISTS `event_registration_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `registration_start_date` VARCHAR(20) NOT NULL COMMENT 'Event registration start date',
  `registration_end_date` VARCHAR(20) NOT NULL COMMENT 'Event registration end date',
  `event_date` VARCHAR(20) NOT NULL COMMENT 'The date of the event',
  `event_name` VARCHAR(255) NOT NULL COMMENT 'Name of the event',
  `event_category` VARCHAR(100) NOT NULL COMMENT 'Category of the event',
  `created` INT NOT NULL DEFAULT 0 COMMENT 'Timestamp when the event was created',
  PRIMARY KEY (`id`),
  INDEX `event_date` (`event_date`),
  INDEX `event_category` (`event_category`),
  INDEX `registration_dates` (`registration_start_date`, `registration_end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores event configuration details';

-- Event Registration Submissions Table
CREATE TABLE IF NOT EXISTS `event_registration_submissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(255) NOT NULL COMMENT 'Full name of the registrant',
  `email` VARCHAR(255) NOT NULL COMMENT 'Email address of the registrant',
  `college_name` VARCHAR(255) NOT NULL COMMENT 'College name of the registrant',
  `department` VARCHAR(255) NOT NULL COMMENT 'Department of the registrant',
  `event_category` VARCHAR(100) NOT NULL COMMENT 'Category of the event',
  `event_config_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to event_registration_config.id',
  `created` INT NOT NULL DEFAULT 0 COMMENT 'Timestamp when the registration was submitted',
  PRIMARY KEY (`id`),
  INDEX `email` (`email`),
  INDEX `event_config_id` (`event_config_id`),
  INDEX `email_event` (`email`, `event_config_id`),
  CONSTRAINT `fk_event_config` FOREIGN KEY (`event_config_id`) 
    REFERENCES `event_registration_config` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores event registration submissions';

-- Sample data for testing (optional)
-- INSERT INTO `event_registration_config` (`registration_start_date`, `registration_end_date`, `event_date`, `event_name`, `event_category`, `created`) VALUES
-- ('2026-01-01', '2026-01-30', '2026-02-15', 'Web Development Workshop', 'Online Workshop', UNIX_TIMESTAMP()),
-- ('2026-01-15', '2026-02-10', '2026-02-20', 'Code Sprint 2026', 'Hackathon', UNIX_TIMESTAMP()),
-- ('2026-01-20', '2026-02-15', '2026-03-01', 'Tech Conference 2026', 'Conference', UNIX_TIMESTAMP());
