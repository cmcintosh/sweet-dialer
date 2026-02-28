-- S-009: Epic 3 CTI Settings Schema Updates
-- Migration to add new fields for file uploads, relate fields, validation, etc.

-- Add relate field columns
ALTER TABLE outr_twilio_settings 
    ADD COLUMN twilio_voice_mail_id CHAR(36) NULL AFTER status,
    ADD COLUMN outbound_inbound_agent_id CHAR(36) NULL AFTER twilio_voice_mail_id;

-- Add file upload columns
ALTER TABLE outr_twilio_settings 
    ADD COLUMN dual_ring_file VARCHAR(255) NULL AFTER text_color,
    ADD COLUMN dual_ring_file_name VARCHAR(255) NULL AFTER dual_ring_file,
    ADD COLUMN hold_ring_file VARCHAR(255) NULL AFTER dual_ring_file_name,
    ADD COLUMN hold_ring_file_name VARCHAR(255) NULL AFTER hold_ring_file;

-- Update validation/color columns
ALTER TABLE outr_twilio_settings 
    MODIFY COLUMN auth_token VARCHAR(255) NULL,
    MODIFY COLUMN api_key_secret VARCHAR(255) NULL,
    MODIFY COLUMN bg_color VARCHAR(7) DEFAULT '#ffffff',
    MODIFY COLUMN text_color VARCHAR(7) DEFAULT '#000000';

-- Add validation tracking columns
ALTER TABLE outr_twilio_settings 
    ADD COLUMN last_validation_status VARCHAR(30) NULL AFTER twiml_app_sid,
    ADD COLUMN last_validation_message TEXT NULL AFTER last_validation_status,
    ADD COLUMN last_validation_date DATETIME NULL AFTER last_validation_message;

-- Add ring timeout
ALTER TABLE outr_twilio_settings 
    ADD COLUMN ring_timeout INT DEFAULT 30 AFTER last_validation_date;

-- Update incoming_calls_modules to varchar for compatibility
ALTER TABLE outr_twilio_settings 
    MODIFY COLUMN incoming_calls_modules VARCHAR(50) DEFAULT 'Home';

-- Add unique index for multi-agent/multi-number (S-028)
ALTER TABLE outr_twilio_settings 
    ADD UNIQUE INDEX idx_phone_agent_unique (agent_phone_number, outbound_inbound_agent_id, deleted);
