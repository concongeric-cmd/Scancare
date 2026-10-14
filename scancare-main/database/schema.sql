SET NAMES utf8mb4;
SET time_zone = '+08:00';

CREATE TABLE IF NOT EXISTS `qr_code` (
    `qr_id` VARCHAR(32) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`qr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resident` (
    `resident_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NULL,
    `middle_initial` VARCHAR(10) NULL,
    `last_name` VARCHAR(100) NULL,
    `user_name` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `date_of_birth` DATE NULL,
    `sex` VARCHAR(20) NULL,
    `address` VARCHAR(500) NULL,
    `barangay` VARCHAR(150) NULL,
    `city` VARCHAR(150) NULL,
    `province` VARCHAR(150) NULL,
    `postal_code` VARCHAR(20) NULL,
    `contact_number` VARCHAR(30) NULL,
    `phone` VARCHAR(30) NULL,
    `is_senior_citizen` TINYINT(1) NOT NULL DEFAULT 0,
    `qr_code_id` VARCHAR(32) NULL,
    `qr_code_token` VARCHAR(64) NULL,
    `patient_code` CHAR(6) NULL,
    `profile_picture` VARCHAR(500) NULL,
    `approval_status` VARCHAR(20) NOT NULL DEFAULT 'approved',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` DATETIME NULL,
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`resident_id`),
    UNIQUE KEY `uk_resident_email` (`email`),
    UNIQUE KEY `uk_resident_qr_code` (`qr_code_id`),
    UNIQUE KEY `uk_resident_qr_token` (`qr_code_token`),
    UNIQUE KEY `uk_resident_patient_code` (`patient_code`),
    KEY `idx_resident_contact` (`contact_number`),
    KEY `idx_resident_active` (`is_active`),
    CONSTRAINT `fk_resident_qr_code` FOREIGN KEY (`qr_code_id`) REFERENCES `qr_code` (`qr_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `health_worker` (
    `worker_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NULL,
    `middle_initial` VARCHAR(10) NULL,
    `last_name` VARCHAR(100) NULL,
    `email` VARCHAR(255) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'bhw',
    `contact_number` VARCHAR(30) NULL,
    `phone` VARCHAR(30) NULL,
    `profile_picture` VARCHAR(500) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` DATETIME NULL,
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`worker_id`),
    UNIQUE KEY `uk_health_worker_email` (`email`),
    KEY `idx_health_worker_contact` (`contact_number`),
    KEY `idx_health_worker_role` (`role`),
    KEY `idx_health_worker_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `health_announcements` (
    `announcement_id` INT NOT NULL AUTO_INCREMENT,
    `worker_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `service_date` DATE NULL,
    `location` VARCHAR(150) NULL,
    `state` VARCHAR(20) NOT NULL DEFAULT 'upcoming',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`announcement_id`),
    KEY `idx_health_announcement_state` (`state`, `created_at`),
    KEY `idx_health_announcement_worker` (`worker_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `system_admins` (
    `admin_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'admin',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`admin_id`),
    UNIQUE KEY `uk_system_admin_username` (`username`),
    UNIQUE KEY `uk_system_admin_email` (`email`),
    KEY `idx_system_admin_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `services` (
    `service_id` INT NOT NULL AUTO_INCREMENT,
    `service_name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `category` VARCHAR(50) NULL,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`service_id`),
    UNIQUE KEY `uk_service_name` (`service_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `appointment` (
    `appointment_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resident_id` BIGINT UNSIGNED NOT NULL,
    `health_worker_id` BIGINT UNSIGNED NULL,
    `service_id` INT NULL,
    `appointment_date` DATE NOT NULL,
    `appointment_time` TIME NOT NULL,
    `purpose` VARCHAR(500) NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`appointment_id`),
    KEY `idx_appointment_resident` (`resident_id`),
    KEY `idx_appointment_worker` (`health_worker_id`),
    KEY `idx_appointment_service` (`service_id`),
    KEY `idx_appointment_date_status` (`appointment_date`, `status`),
    CONSTRAINT `fk_appointment_resident` FOREIGN KEY (`resident_id`) REFERENCES `resident` (`resident_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_appointment_worker` FOREIGN KEY (`health_worker_id`) REFERENCES `health_worker` (`worker_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_appointment_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `file_requests` (
    `request_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resident_id` BIGINT UNSIGNED NOT NULL,
    `health_worker_id` BIGINT UNSIGNED NULL,
    `requested_file` VARCHAR(100) NOT NULL DEFAULT 'Medical Record',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `response_note` TEXT NULL,
    `processed_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`request_id`),
    KEY `idx_file_request_resident_status` (`resident_id`, `status`),
    KEY `idx_file_request_worker_status` (`health_worker_id`, `status`),
    CONSTRAINT `fk_file_request_resident` FOREIGN KEY (`resident_id`) REFERENCES `resident` (`resident_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_file_request_worker` FOREIGN KEY (`health_worker_id`) REFERENCES `health_worker` (`worker_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `patient_record` (
    `record_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resident_id` BIGINT UNSIGNED NOT NULL,
    `health_worker_id` BIGINT UNSIGNED NULL,
    `visit_date` DATETIME NOT NULL,
    `diagnosis` TEXT NULL,
    `treatment` TEXT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`record_id`),
    KEY `idx_patient_record_resident_date` (`resident_id`, `visit_date`),
    KEY `idx_patient_record_worker` (`health_worker_id`),
    CONSTRAINT `fk_patient_record_resident` FOREIGN KEY (`resident_id`) REFERENCES `resident` (`resident_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_patient_record_worker` FOREIGN KEY (`health_worker_id`) REFERENCES `health_worker` (`worker_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `medical_history` (
    `history_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `appointment_id` BIGINT UNSIGNED NULL,
    `visit_date` DATETIME NOT NULL,
    `consulted_at` DATETIME NULL,
    `visit_type` VARCHAR(255) NULL,
    `bhw_name` VARCHAR(255) NULL,
    `diagnosis` TEXT NULL,
    `treatment` TEXT NULL,
    `notes` TEXT NULL,
    `status` VARCHAR(50) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`history_id`),
    KEY `idx_medical_history_user_date` (`user_id`, `visit_date`),
    KEY `idx_medical_history_appointment` (`appointment_id`),
    CONSTRAINT `fk_medical_history_resident` FOREIGN KEY (`user_id`) REFERENCES `resident` (`resident_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `medicine` (
    `medicine_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `medicine_name` VARCHAR(255) NULL,
    `dosage` VARCHAR(100) NULL,
    `description` TEXT NULL,
    `stock_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
    `quantity_stock` INT UNSIGNED NOT NULL DEFAULT 0,
    `available` INT UNSIGNED NOT NULL DEFAULT 0,
    `dispensed` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Available',
    `condition` VARCHAR(50) NULL,
    `expiry_date` DATE NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`medicine_id`),
    KEY `idx_medicine_name` (`name`),
    KEY `idx_medicine_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `medicine_dispensing` (
    `dispense_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resident_id` BIGINT UNSIGNED NOT NULL,
    `medicine_id` BIGINT UNSIGNED NOT NULL,
    `health_worker_id` BIGINT UNSIGNED NULL,
    `quantity_given` INT UNSIGNED NOT NULL DEFAULT 1,
    `quantity_dispensed` INT UNSIGNED NOT NULL DEFAULT 1,
    `dispense_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `dispensing_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT NULL,
    PRIMARY KEY (`dispense_id`),
    KEY `idx_dispensing_resident` (`resident_id`),
    KEY `idx_dispensing_medicine` (`medicine_id`),
    KEY `idx_dispensing_worker` (`health_worker_id`),
    KEY `idx_dispensing_date` (`dispensing_date`),
    CONSTRAINT `fk_dispensing_resident` FOREIGN KEY (`resident_id`) REFERENCES `resident` (`resident_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dispensing_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicine` (`medicine_id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_dispensing_worker` FOREIGN KEY (`health_worker_id`) REFERENCES `health_worker` (`worker_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `surveillance_metrics` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `worker_id` BIGINT UNSIGNED NOT NULL,
    `reporting_period` VARCHAR(255) NULL,
    `dengue_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `dengue_trend` VARCHAR(255) NULL,
    `dengue_meta` VARCHAR(255) NULL,
    `dengue_progress` INT UNSIGNED NOT NULL DEFAULT 0,
    `rabies_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `rabies_trend` VARCHAR(255) NULL,
    `rabies_meta` VARCHAR(255) NULL,
    `rabies_progress` INT UNSIGNED NOT NULL DEFAULT 0,
    `respiratory_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `respiratory_trend` VARCHAR(255) NULL,
    `respiratory_meta` VARCHAR(255) NULL,
    `respiratory_progress` INT UNSIGNED NOT NULL DEFAULT 0,
    `nutrition_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `nutrition_trend` VARCHAR(255) NULL,
    `nutrition_meta` VARCHAR(255) NULL,
    `nutrition_progress` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_surveillance_worker` (`worker_id`),
    CONSTRAINT `fk_surveillance_worker` FOREIGN KEY (`worker_id`) REFERENCES `health_worker` (`worker_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_login_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `user_type` VARCHAR(50) NOT NULL,
    `session_id` VARCHAR(255) NOT NULL,
    `device_id` VARCHAR(255) NULL,
    `device_token` VARCHAR(255) NULL,
    `location` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `temp_token` VARCHAR(255) NULL,
    `status` VARCHAR(25) NOT NULL,
    `trusted` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_session` (`user_id`, `user_type`, `session_id`),
    UNIQUE KEY `uk_temp_token` (`temp_token`),
    KEY `idx_login_user_status` (`user_id`, `user_type`, `status`),
    KEY `idx_login_device_token` (`device_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_logs` (
    `log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `user_role_snapshot` VARCHAR(50) NULL,
    `action_type` VARCHAR(100) NULL,
    `action_description` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    KEY `idx_activity_user` (`user_id`),
    KEY `idx_activity_action` (`action_type`),
    KEY `idx_activity_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `account_requests` (
    `request_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resident_id` BIGINT UNSIGNED NOT NULL,
    `request_type` VARCHAR(50) NOT NULL,
    `requested_by` BIGINT UNSIGNED NOT NULL,
    `requested_by_role` VARCHAR(50) NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `remarks` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`request_id`),
    KEY `idx_account_request_resident` (`resident_id`),
    KEY `idx_account_request_status` (`status`),
    CONSTRAINT `fk_account_request_resident` FOREIGN KEY (`resident_id`) REFERENCES `resident` (`resident_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_log` (
    `sms_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bhw_id` BIGINT UNSIGNED NULL,
    `recipient` VARCHAR(30) NOT NULL,
    `message` TEXT NOT NULL,
    `status` VARCHAR(50) NULL,
    `network` VARCHAR(50) NULL,
    `message_id` VARCHAR(255) NULL,
    `mode` VARCHAR(50) NULL,
    `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`sms_id`),
    KEY `idx_sms_bhw` (`bhw_id`),
    KEY `idx_sms_sent_at` (`sent_at`),
    CONSTRAINT `fk_sms_bhw` FOREIGN KEY (`bhw_id`) REFERENCES `health_worker` (`worker_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `service_request` (
    `request_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resident_id` BIGINT UNSIGNED NOT NULL,
    `service_type` VARCHAR(150) NOT NULL,
    `details` TEXT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `request_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`request_id`),
    KEY `idx_service_request_resident` (`resident_id`),
    KEY `idx_service_request_status` (`status`),
    CONSTRAINT `fk_service_request_resident` FOREIGN KEY (`resident_id`) REFERENCES `resident` (`resident_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `services` (`service_name`, `description`, `category`, `is_available`) VALUES
    ('General Health Checkup', 'General health assessment and consultation', 'checkup', 1),
    ('Vaccination', 'Routine vaccination and immunization service', 'vaccination', 1),
    ('Dental Check-up', 'Dental assessment and basic oral care', 'dental', 1),
    ('Prenatal Care', 'Maternal and prenatal health service', 'prenatal', 1),
    ('Medication Distribution', 'Prescribed medicine distribution', 'medication', 1)
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`),
    `category` = VALUES(`category`),
    `is_available` = VALUES(`is_available`);
