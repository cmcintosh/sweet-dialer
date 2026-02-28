-- S-006: outr_twilio_voicemail, outr_twilio_voicemail_recordings, outr_twilio_opted_out

CREATE TABLE IF NOT EXISTS outr_twilio_voicemail (
    id CHAR(36) NOT NULL,
    name VARCHAR(255) NULL,
    file TEXT NULL,
    voice_mail_message TEXT NULL,
    voice_speech_by VARCHAR(100) NULL,
    voice_finish_key VARCHAR(10) NULL,
    voice_max_length INT DEFAULT 300,
    cti_setting_id CHAR(36) NULL,
    date_created DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    deleted TINYINT(1) DEFAULT 0,
    created_by CHAR(36) NULL,
    modified_user_id CHAR(36) NULL,
    PRIMARY KEY (id),
    INDEX idx_cti_setting (cti_setting_id),
    INDEX idx_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS outr_twilio_voicemail_recordings (
    id CHAR(36) NOT NULL,
    voicemail_id CHAR(36) NOT NULL,
    recording_sid VARCHAR(255) NULL,
    recording_url TEXT NULL,
    duration INT DEFAULT 0,
    transcription TEXT NULL,
    transcription_status VARCHAR(50) NULL,
    date_created DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    deleted TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_voicemail_id (voicemail_id),
    INDEX idx_recording_sid (recording_sid),
    INDEX idx_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS outr_twilio_opted_out (
    id CHAR(36) NOT NULL,
    phone_number VARCHAR(50) NOT NULL,
    reason TEXT NULL,
    date_opted_out DATETIME NOT NULL,
    date_created DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    deleted TINYINT(1) DEFAULT 0,
    created_by CHAR(36) NULL,
    modified_user_id CHAR(36) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_phone_number (phone_number),
    INDEX idx_date_opted_out (date_opted_out),
    INDEX idx_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
