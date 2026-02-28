-- S-008: outr_twilio_common_settings, outr_twilio_error_logs, outr_twilio_logger, outr_twilio_phone_numbers

CREATE TABLE IF NOT EXISTS outr_twilio_common_settings (
    id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    ring_timeout_seconds_before_vm INT DEFAULT 30,
    date_created DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    deleted TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_name (name),
    INDEX idx_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS outr_twilio_error_logs (
    id CHAR(36) NOT NULL,
    error_code VARCHAR(50) NULL,
    error_message TEXT NULL,
    call_sid VARCHAR(255) NULL,
    endpoint VARCHAR(255) NULL,
    request_body TEXT NULL,
    response_body TEXT NULL,
    date_created DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_error_code (error_code),
    INDEX idx_call_sid (call_sid),
    INDEX idx_date_created (date_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED;

CREATE TABLE IF NOT EXISTS outr_twilio_logger (
    id CHAR(36) NOT NULL,
    log_level VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    context TEXT NULL,
    date_created DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_log_level (log_level),
    INDEX idx_date_created (date_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED;

CREATE TABLE IF NOT EXISTS outr_twilio_phone_numbers (
    id CHAR(36) NOT NULL,
    phone_number VARCHAR(50) NOT NULL,
    friendly_name VARCHAR(255) NULL,
    phone_sid VARCHAR(255) NULL,
    capabilities_voice TINYINT(1) DEFAULT 1,
    capabilities_sms TINYINT(1) DEFAULT 1,
    assignment_status ENUM('Assigned', 'Available', 'Reserved') DEFAULT 'Available',
    cti_setting_id CHAR(36) NULL,
    date_created DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    deleted TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_phone_number (phone_number),
    INDEX idx_phone_sid (phone_sid),
    INDEX idx_assignment_status (assignment_status),
    INDEX idx_cti_setting (cti_setting_id),
    INDEX idx_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
