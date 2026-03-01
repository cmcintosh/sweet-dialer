<?php
/**
 * Sweet-Dialer Post-Installation Script
 *
 * Creates database tables and performs post-install setup
 *
 * @package SweetDialer
 * @author Wembassy
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

/**
 * Execute database migrations for all Sweet-Dialer tables
 */
function post_install()
{
    global $sugar_config, $db;
    
    $GLOBALS['log']->debug('Sweet-Dialer: Starting post_install...');
    
    // Get database instance if not already available
    if (empty($db)) {
        $db = DBManagerFactory::getInstance();
    }
    
    try {
        // Run all database migrations
        $migrations = getDatabaseMigrations();
        
        foreach ($migrations as $tableName => $sql) {
            $GLOBALS['log']->debug("Sweet-Dialer: Creating table {$tableName}");
            
            // Check if table exists
            if (!$db->tableExists($tableName)) {
                $db->query($sql);
                $GLOBALS['log']->debug("Sweet-Dialer: Table {$tableName} created successfully");
            } else {
                $GLOBALS['log']->debug("Sweet-Dialer: Table {$tableName} already exists, skipping");
            }
        }
        
        // Create default records if needed
        createDefaultRecords($db);
        
        // Clear cache
        repairAndClearAllCache();
        
        $GLOBALS['log']->debug('Sweet-Dialer: post_install completed successfully');
        
    } catch (Exception $e) {
        $GLOBALS['log']->fatal("Sweet-Dialer: post_install failed: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Get all database migration SQL
 * @return array Array of table name => SQL pairs
 */
function getDatabaseMigrations()
{
    return array(
        'twiliodialer_cti_settings' => getCtiSettingsTableSql(),
        'twiliodialer_calls' => getCallsTableSql(),
        'twiliodialer_voicemail' => getVoicemailTableSql(),
        'twiliodialer_recordings' => getRecordingsTableSql(),
        'twiliodialer_opted_out' => getOptedOutTableSql(),
        'twiliodialer_dual_ringtone' => getDualRingtoneTableSql(),
        'twiliodialer_hold_ringtone' => getHoldRingtoneTableSql(),
        'twiliodialer_common_settings' => getCommonSettingsTableSql(),
        'twiliodialer_error_logs' => getErrorLogsTableSql(),
        'twiliodialer_logger' => getLoggerTableSql(),
        'twiliodialer_phone_numbers' => getPhoneNumbersTableSql(),
    );
}

/**
 * CTI Settings table - stores Twilio account configuration
 */
function getCtiSettingsTableSql()
{
    return "CREATE TABLE twiliodialer_cti_settings (
        id CHAR(36) NOT NULL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        account_sid VARCHAR(255) NOT NULL,
        auth_token TEXT NULL,
        api_key TEXT NULL,
        api_key_secret TEXT NULL,
        twilio_phone_number VARCHAR(50) NULL,
        twilio_app_sid VARCHAR(255) NULL,
        caller_name VARCHAR(255) NULL,
        incoming_call_type ENUM('user','ivr') DEFAULT 'user',
        recording_enabled TINYINT(1) DEFAULT 1,
        ai_enabled TINYINT(1) DEFAULT 0,
        status ENUM('Active','Inactive') DEFAULT 'Active',
        domain_name VARCHAR(255) NULL,
        assigned_user_id CHAR(36) NULL,
        date_entered DATETIME NOT NULL,
        date_modified DATETIME NOT NULL,
        modified_user_id CHAR(36) NULL,
        created_by CHAR(36) NULL,
        deleted TINYINT(1) DEFAULT 0,
        INDEX idx_status (status),
        INDEX idx_assigned_user (assigned_user_id),
        INDEX idx_deleted (deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/**
 * Calls table - stores call records
 */
function getCallsTableSql()
{
    return "CREATE TABLE twiliodialer_calls (
        id CHAR(36) NOT NULL PRIMARY KEY,
        name VARCHAR(255) NULL,
        call_sid VARCHAR(255) NOT NULL,
        parent_type VARCHAR(100) NULL,
        parent_id CHAR(36) NULL,
        account_id CHAR(36) NULL,
        contact_id CHAR(36) NULL,
        lead_id CHAR(36) NULL,
        case_id CHAR(36) NULL,
        direction ENUM('inbound','outbound') NOT NULL,
        status VARCHAR(50) NULL,
        duration INT DEFAULT 0,
        duration_minutes INT DEFAULT 0,
        from_number VARCHAR(50) NULL,
        to_number VARCHAR(50) NULL,
        recording_url TEXT NULL,
        recording_sid VARCHAR(255) NULL,
        notes TEXT NULL,
        cti_setting_id CHAR(36) NULL,
        date_entered DATETIME NOT NULL,
        date_modified DATETIME NOT NULL,
        modified_user_id CHAR(36) NULL,
        created_by CHAR(36) NULL,
        deleted TINYINT(1) DEFAULT 0,
        INDEX idx_call_sid (call_sid),
        INDEX idx_parent (parent_type, parent_id),
        INDEX idx_account (account_id),
        INDEX idx_contact (contact_id),
        INDEX idx_direction (direction),
        INDEX idx_status (status),
        INDEX idx_date_entered (date_entered),
        INDEX idx_deleted (deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/**
 * Voicemail table - stores voicemail records
 */
function getVoicemailTableSql()
{
    return "CREATE TABLE twiliodialer_voicemail (
        id CHAR(36) NOT NULL PRIMARY KEY,
        name VARCHAR(255) NULL,
        voicemail_sid VARCHAR(255) NOT NULL,
        from_number VARCHAR(50) NULL,
        to_number VARCHAR(50) NULL,
        recording_url TEXT NULL,
        recording_sid VARCHAR(255) NULL,
        transcription TEXT NULL,
        duration INT DEFAULT 0,
        listened TINYINT(1) DEFAULT 0,
        cti_setting_id CHAR(36) NULL,
        related_type VARCHAR(100) NULL,
        related_id CHAR(36) NULL,
        date_entered DATETIME NOT NULL,
        date_modified DATETIME NOT NULL,
        modified_user_id CHAR(36) NULL,
        created_by CHAR(36) NULL,
        deleted TINYINT(1) DEFAULT 0,
        INDEX idx_voicemail_sid (voicemail_sid),
        INDEX idx_from_number (from_number),
        INDEX idx_listened (listened),
        INDEX idx_related (related_type, related_id),
        INDEX idx_deleted (deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/**
 * Recordings table - stores call recording metadata
 */
function getRecordingsTableSql()
{
    return "CREATE TABLE twiliodialer_recordings (
        id CHAR(36) NOT NULL PRIMARY KEY,
        name VARCHAR(255) NULL,
        recording_sid VARCHAR(255) NOT NULL,
        call_sid VARCHAR(255) NULL,
        url TEXT NULL,
        duration INT DEFAULT 0,
        file_size INT DEFAULT 0,
        format VARCHAR(10) DEFAULT 'mp3',
        status VARCHAR(50) DEFAULT 'completed',
        downloaded TINYINT(1) DEFAULT 0,
        local_path TEXT NULL,
        cti_setting_id CHAR(36) NULL,
        date_entered DATETIME NOT NULL,
        date_modified DATETIME NOT NULL,
        modified_user_id CHAR(36) NULL,
        created_by CHAR(36) NULL,
        deleted TINYINT(1) DEFAULT 0,
        INDEX idx_recording_sid (recording_sid),
        INDEX idx_call_sid (call_sid),
        INDEX idx_status (status),
        INDEX idx_deleted (deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/**
 * Opted Out table - stores numbers that have opted out
 */
function getOptedOutTableSql()
{
    return "CREATE TABLE twiliodialer_opted_out (
        id CHAR(36) NOT NULL PRIMARY KEY,
        phone_number VARCHAR(50) NOT NULL,
        opt_out_type VARCHAR(50) DEFAULT 'all',
        reason TEXT NULL,
        source VARCHAR(100) NULL,
        date_entered DATETIME NOT NULL,
        date_modified DATETIME NOT NULL,
        modified_user_id CHAR(36) NULL,
        created_by CHAR(36) NULL,
        deleted TINYINT(1) DEFAULT 0,
        UNIQUE KEY idx_phone_number (phone_number),
        INDEX idx_opt_out_type (opt_out_type),
        INDEX idx_deleted (deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/**
 * Dual Ringtone table - stores dual ringtones
 */
function getDualRingtoneTableSql()
{
    return "CREATE TABLE twiliodialer_dual_ringtone (
        id CHAR(36) NOT NULL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        file_path TEXT NULL,
        file_url TEXT NULL,
        twilio_url TEXT NULL,
        duration INT DEFAULT 0,
        active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        date_entered DATETIME NOT NULL,
        date_modified DATETIME NOT NULL,
        modified_user_id CHAR(36) NULL,
        created_by CHAR(36) NULL,
        deleted TINYINT(1) DEFAULT 0,
        INDEX idx_active (active),
        INDEX idx_sort_order (sort_order),
        INDEX idx_deleted (deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/**
 * Hold Ringtone table - stores hold music/ringtones
 */
function getHoldRingtoneTableSql()
{
    return "CREATE TABLE twiliodialer_hold_ringtone (
        id CHAR(36) NOT NULL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        file_path TEXT NULL,
        file_url TEXT NULL,
        twilio_url TEXT NULL,
        duration INT DEFAULT 0,
        active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        date_entered DATETIME NOT NULL,
        date_modified DATETIME NOT NULL,
        modified_user_id CHAR(36) NULL,
        created_by CHAR(36) NULL,
        deleted TINYINT(1) DEFAULT 0,
        INDEX idx_active (active),
        INDEX idx_sort_order (sort_order),
        INDEX idx_deleted (deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/**
 * Common Settings table - stores global settings
 */
function getCommonSettingsTableSql()
{
    return "CREATE TABLE twiliodialer_common_settings (
        id CHAR(36) NOT NULL PRIMARY KEY,
        setting_name VARCHAR(100) NOT NULL,
        setting_value TEXT NULL,
        setting_type VARCHAR(50) DEFAULT 'string',
        category VARCHAR(100) DEFAULT 'general',
        description TEXT NULL,
        date_entered DATETIME NOT NULL,
        date_modified DATETIME NOT NULL,
        modified_user_id CHAR(36) NULL,
        created_by CHAR(36) NULL,
        deleted TINYINT(1) DEFAULT 0,
        UNIQUE KEY idx_setting_name (setting_name),
        INDEX idx_category (category),
        INDEX idx_deleted (deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/**
 * Error Logs table - stores error logs
 */
function getErrorLogsTableSql()
{
    return "CREATE TABLE twiliodialer_error_logs (
        id CHAR(36) NOT NULL PRIMARY KEY,
        level VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        context TEXT NULL,
        file VARCHAR(255) NULL,
        line INT NULL,
        trace TEXT NULL,
        request_id VARCHAR(100) NULL,
        user_id CHAR(36) NULL,
        ip_address VARCHAR(45) NULL,
        date_entered DATETIME NOT NULL,
        date_modified DATETIME NOT NULL,
        INDEX idx_level (level),
        INDEX idx_date_entered (date_entered),
        INDEX idx_request_id (request_id),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/**
 * Logger table - stores application logs
 */
function getLoggerTableSql()
{
    return "CREATE TABLE twiliodialer_logger (
        id CHAR(36) NOT NULL PRIMARY KEY,
        level VARCHAR(20) NOT NULL,
        category VARCHAR(100) NULL,
        message TEXT NOT NULL,
        context TEXT NULL,
        related_type VARCHAR(100) NULL,
        related_id CHAR(36) NULL,
        user_id CHAR(36) NULL,
        ip_address VARCHAR(45) NULL,
        date_entered DATETIME NOT NULL,
        INDEX idx_level (level),
        INDEX idx_category (category),
        INDEX idx_related (related_type, related_id),
        INDEX idx_user_id (user_id),
        INDEX idx_date_entered (date_entered)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED";
}

/**
 * Phone Numbers table - stores Twilio phone numbers
 */
function getPhoneNumbersTableSql()
{
    return "CREATE TABLE twiliodialer_phone_numbers (
        id CHAR(36) NOT NULL PRIMARY KEY,
        phone_number VARCHAR(50) NOT NULL,
        friendly_name VARCHAR(255) NULL,
        sid VARCHAR(255) NOT NULL,
        capabilities TEXT NULL,
        voice_url TEXT NULL,
        voice_method VARCHAR(10) DEFAULT 'POST',
        sms_url TEXT NULL,
        sms_method VARCHAR(10) DEFAULT 'POST',
        status_callback TEXT NULL,
        status_callback_method VARCHAR(10) DEFAULT 'POST',
        cti_setting_id CHAR(36) NULL,
        active TINYINT(1) DEFAULT 1,
        date_entered DATETIME NOT NULL,
        date_modified DATETIME NOT NULL,
        modified_user_id CHAR(36) NULL,
        created_by CHAR(36) NULL,
        deleted TINYINT(1) DEFAULT 0,
        INDEX idx_phone_number (phone_number),
        INDEX idx_sid (sid),
        INDEX idx_active (active),
        INDEX idx_cti_setting (cti_setting_id),
        INDEX idx_deleted (deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/**
 * Create default records for common settings
 */
function createDefaultRecords($db)
{
    $defaults = array(
        array('setting_name' => 'log_level', 'setting_value' => 'info', 'category' => 'logging'),
        array('setting_name' => 'auto_record_calls', 'setting_value' => '1', 'category' => 'calls'),
        array('setting_name' => 'webhook_enabled', 'setting_value' => '1', 'category' => 'webhooks'),
        array('setting_name' => 'recording_auto_download', 'setting_value' => '0', 'category' => 'recordings'),
    );
    
    $now = gmdate('Y-m-d H:i:s');
    
    foreach ($defaults as $setting) {
        $id = create_guid();
        $sql = sprintf(
            "INSERT IGNORE INTO twiliodialer_common_settings (id, setting_name, setting_value, setting_type, category, date_entered, date_modified) 
             VALUES ('%s', '%s', '%s', 'string', '%s', '%s', '%s')",
            $db->quote($id),
            $db->quote($setting['setting_name']),
            $db->quote($setting['setting_value']),
            $db->quote($setting['category']),
            $db->quote($now),
            $db->quote($now)
        );
        $db->query($sql);
    }
}
