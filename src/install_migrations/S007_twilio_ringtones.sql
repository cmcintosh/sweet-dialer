-- S-007: outr_twilio_dual_ringtone and outr_twilio_hold_ringtone

CREATE TABLE IF NOT EXISTS outr_twilio_dual_ringtone (
    id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    file TEXT NULL,
    category VARCHAR(100) NULL,
    sub_category VARCHAR(100) NULL,
    assigned_to CHAR(36) NULL,
    publish_date DATETIME NULL,
    expiration_date DATETIME NULL,
    status ENUM('Active', 'Inactive', 'Expired') DEFAULT 'Active',
    description TEXT NULL,
    date_created DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    deleted TINYINT(1) DEFAULT 0,
    created_by CHAR(36) NULL,
    modified_user_id CHAR(36) NULL,
    PRIMARY KEY (id),
    INDEX idx_name (name),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS outr_twilio_hold_ringtone (
    id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    file TEXT NULL,
    category VARCHAR(100) NULL,
    sub_category VARCHAR(100) NULL,
    assigned_to CHAR(36) NULL,
    publish_date DATETIME NULL,
    expiration_date DATETIME NULL,
    status ENUM('Active', 'Inactive', 'Expired') DEFAULT 'Active',
    description TEXT NULL,
    date_created DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    deleted TINYINT(1) DEFAULT 0,
    created_by CHAR(36) NULL,
    modified_user_id CHAR(36) NULL,
    PRIMARY KEY (id),
    INDEX idx_name (name),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
