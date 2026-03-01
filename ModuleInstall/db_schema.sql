-- S-004 through S-008: Database Schema for Twilio Dialer
-- SuiteCRM 8.8.0+ Compatible

-- outr_twilio_settings
CREATE TABLE IF NOT EXISTS outr_twilio_settings (
    id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    accounts_sid VARCHAR(100) DEFAULT NULL,
    auth_token VARCHAR(100) DEFAULT NULL,
    agent_phone_number VARCHAR(20) DEFAULT NULL,
    phone_sid VARCHAR(50) DEFAULT NULL,
    incoming_calls_modules VARCHAR(50) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'Active',
    bg_color VARCHAR(7) DEFAULT NULL,
    text_color VARCHAR(7) DEFAULT NULL,
    api_key_sid VARCHAR(100) DEFAULT NULL,
    api_key_secret VARCHAR(100) DEFAULT NULL,
    twiml_app_sid VARCHAR(50) DEFAULT NULL,
    last_validation_message TEXT DEFAULT NULL,
    date_entered DATETIME DEFAULT NULL,
    date_modified DATETIME DEFAULT NULL,
    deleted TINYINT(1) DEFAULT 0,
    created_by CHAR(36) DEFAULT NULL,
    modified_user_id CHAR(36) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_outr_twilio_settings_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- outr_twilio_calls
CREATE TABLE IF NOT EXISTS outr_twilio_calls (
    id CHAR(36) NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    call_sid VARCHAR(100) DEFAULT NULL,
    from_number VARCHAR(20) DEFAULT NULL,
    to_number VARCHAR(20) DEFAULT NULL,
    call_type VARCHAR(20) DEFAULT NULL,
    agent_id CHAR(36) DEFAULT NULL,
    status VARCHAR(50) DEFAULT NULL,
    duration INT DEFAULT 0,
    recording_url VARCHAR(500) DEFAULT NULL,
    recording_sid VARCHAR(100) DEFAULT NULL,
    parent_type VARCHAR(100) DEFAULT NULL,
    parent_id CHAR(36) DEFAULT NULL,
    company_id CHAR(36) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    cti_setting_id CHAR(36) DEFAULT NULL,
    direction VARCHAR(20) DEFAULT NULL,
    date_entered DATETIME DEFAULT NULL,
    date_modified DATETIME DEFAULT NULL,
    deleted TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_outr_twilio_calls_sid (call_sid),
    KEY idx_outr_twilio_calls_parent (parent_type, parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- outr_twilio_voicemail
CREATE TABLE IF NOT EXISTS outr_twilio_voicemail (
    id CHAR(36) NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    file VARCHAR(500) DEFAULT NULL,
    voice_mail_message TEXT DEFAULT NULL,
    voice_speech_by VARCHAR(20) DEFAULT NULL,
    voice_finish_key VARCHAR(5) DEFAULT NULL,
    voice_max_length INT DEFAULT NULL,
    date_entered DATETIME DEFAULT NULL,
    date_modified DATETIME DEFAULT NULL,
    deleted TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- outr_twilio_voicemail_recordings
CREATE TABLE IF NOT EXISTS outr_twilio_voicemail_recordings (
    id CHAR(36) NOT NULL,
    voicemail_id CHAR(36) DEFAULT NULL,
    recording_url VARCHAR(500) DEFAULT NULL,
    duration INT DEFAULT 0,
    from_number VARCHAR(20) DEFAULT NULL,
    date_entered DATETIME DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- outr_twilio_opted_out
CREATE TABLE IF NOT EXISTS outr_twilio_opted_out (
    id CHAR(36) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    reason TEXT DEFAULT NULL,
    date_opted_out DATETIME DEFAULT NULL,
    date_entered DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_phone_number (phone_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- outr_twilio_dual_ringtone
CREATE TABLE IF NOT EXISTS outr_twilio_dual_ringtone (
    id CHAR(36) NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    file VARCHAR(500) DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    sub_category VARCHAR(100) DEFAULT NULL,
    assigned_to CHAR(36) DEFAULT NULL,
    publish_date DATE DEFAULT NULL,
    expiration_date DATE DEFAULT NULL,
    status VARCHAR(20) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    date_entered DATETIME DEFAULT NULL,
    date_modified DATETIME DEFAULT NULL,
    deleted TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- outr_twilio_hold_ringtone
CREATE TABLE IF NOT EXISTS outr_twilio_hold_ringtone (
    id CHAR(36) NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    file VARCHAR(500) DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    sub_category VARCHAR(100) DEFAULT NULL,
    assigned_to CHAR(36) DEFAULT NULL,
    publish_date DATE DEFAULT NULL,
    expiration_date DATE DEFAULT NULL,
    status VARCHAR(20) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    date_entered DATETIME DEFAULT NULL,
    date_modified DATETIME DEFAULT NULL,
    deleted TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- outr_twilio_common_settings
CREATE TABLE IF NOT EXISTS outr_twilio_common_settings (
    id CHAR(36) NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    ring_timeout_seconds_before_vm INT DEFAULT 30,
    date_entered DATETIME DEFAULT NULL,
    date_modified DATETIME DEFAULT NULL,
    deleted TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- outr_twilio_error_logs
CREATE TABLE IF NOT EXISTS outr_twilio_error_logs (
    id CHAR(36) NOT NULL,
    error_code VARCHAR(50) DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    call_sid VARCHAR(100) DEFAULT NULL,
    endpoint VARCHAR(255) DEFAULT NULL,
    request_body TEXT DEFAULT NULL,
    response_body TEXT DEFAULT NULL,
    date_created DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_error_date (date_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- outr_twilio_logger
CREATE TABLE IF NOT EXISTS outr_twilio_logger (
    id CHAR(36) NOT NULL,
    log_level VARCHAR(20) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    context TEXT DEFAULT NULL,
    date_created DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_log_date (date_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- outr_twilio_phone_numbers
CREATE TABLE IF NOT EXISTS outr_twilio_phone_numbers (
    id CHAR(36) NOT NULL,
    phone_number VARCHAR(20) DEFAULT NULL,
    friendly_name VARCHAR(255) DEFAULT NULL,
    phone_sid VARCHAR(50) DEFAULT NULL,
    capabilities_voice TINYINT(1) DEFAULT 0,
    capabilities_sms TINYINT(1) DEFAULT 0,
    assignment_status VARCHAR(50) DEFAULT NULL,
    date_entered DATETIME DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
