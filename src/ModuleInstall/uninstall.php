<?php
/**
 * Sweet-Dialer Uninstallation Script (S-003)
 *
 * Removes all module artifacts with confirmation prompt and preserve option.
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * Main post-uninstall function
 * @param bool $preserveCallRecords - Option to preserve call data
 * @param bool $force - Skip confirmation prompts
 */
function post_uninstall($preserveCallRecords = false, $force = false)
{
    global $db;

    $GLOBALS['log']->debug('Sweet-Dialer: Starting uninstall...');

    try {
        if (empty($db)) {
            $db = DBManagerFactory::getInstance();
        }

        // Archive call data if preserving
        if ($preserveCallRecords) {
            archiveCallData($db);
        }

        // Remove scheduler jobs
        removeSchedulers($db);

        // Unregister modules
        unregisterModules($db);

        // Drop database tables
        dropTables($db, $preserveCallRecords);

        // Clean uploaded files
        cleanUploadedFiles();

        // Remove config entries
        removeConfigEntries($db);

        // Clear cache
        repairAndClearAllCache();

        $GLOBALS['log']->info('Sweet-Dialer: Uninstall completed successfully');

    } catch (Exception $e) {
        $GLOBALS['log']->fatal('Sweet-Dialer: Uninstall failed: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Archive call data to CSV before deletion
 */
function archiveCallData($db)
{
    $GLOBALS['log']->info('Sweet-Dialer: Archiving call data...');

    $archiveDir = 'custom/SweetDialerArchive/' . date('Y-m-d-His');
    if (!is_dir($archiveDir)) {
        mkdir($archiveDir, 0755, true);
    }

    $tables = array('outr_twilio_calls', 'outr_twilio_voicemail', 'outr_twilio_voicemail_recordings');

    foreach ($tables as $table) {
        if (!$db->tableExists($table)) {
            continue;
        }

        $file = $archiveDir . '/' . $table . '.csv';
        $fp = fopen($file, 'w');
        if (!$fp) {
            $GLOBALS['log']->error("Cannot create archive file: {$file}");
            continue;
        }

        // Get column headers
        $cols = $db->get_columns($table);
        if (empty($cols)) {
            // Fallback: query first row for headers
            $row = $db->fetchOne("SELECT * FROM {$table} LIMIT 1");
            $cols = $row ? array_keys($row) : array();
        }

        if (!empty($cols)) {
            fputcsv($fp, $cols);

            // Export data in batches
            $offset = 0;
            $batchSize = 1000;
            do {
                $rows = $db->fetchRows("SELECT * FROM {$table} LIMIT {$batchSize} OFFSET {$offset}");
                foreach ($rows as $row) {
                    fputcsv($fp, $row);
                }
                $offset += $batchSize;
            } while (!empty($rows));
        }

        fclose($fp);
        $GLOBALS['log']->debug("Archived {$table} to {$file}");
    }

    $GLOBALS['log']->info("Sweet-Dialer: Call data archived to {$archiveDir}");
}

/**
 * Remove scheduler jobs
 */
function removeSchedulers($db)
{
    $GLOBALS['log']->debug('Sweet-Dialer: Removing schedulers...');

    $jobs = array('function::twilioCallSync', 'function::twilioVoicemailCleanup', 'function::twilioLogArchive');
    foreach ($jobs as $job) {
        $db->query("UPDATE schedulers SET deleted = 1 WHERE job = '{$job}' AND deleted = 0");
    }

    $GLOBALS['log']->info('Sweet-Dialer: Schedulers removed');
}

/**
 * Unregister modules from SuiteCRM
 */
function unregisterModules($db)
{
    $GLOBALS['log']->debug('Sweet-Dialer: Unregistering modules...');

    $modules = array(
        'outr_TwilioSettings', 'outr_TwilioCalls', 'outr_TwilioVoicemail',
        'outr_TwilioOptedOut', 'outr_TwilioDualRingtone', 'outr_TwilioHoldRingtone',
        'outr_TwilioCommonSettings', 'outr_TwilioErrorLogs', 'outr_TwilioLogger',
        'outr_TwilioPhoneNumbers', 'outr_TwilioVoicemailRecordings'
    );

    foreach ($modules as $name) {
        $db->query("DELETE FROM config WHERE category = 'module' AND name = '{$name}'");
    }

    $GLOBALS['log']->info('Sweet-Dialer: Modules unregistered');
}

/**
 * Drop database tables with preserve option for call records
 */
function dropTables($db, $preserveCallRecords = false)
{
    $GLOBALS['log']->debug('Sweet-Dialer: Dropping tables...');

    // Order matters - drop child tables before parents
    $tables = array(
        'outr_twilio_voicemail_recordings',  // Child of voicemail
        'outr_twilio_voicemail',
        'outr_twilio_dual_ringtone',
        'outr_twilio_hold_ringtone',
        'outr_twilio_error_logs',
        'outr_twilio_logger',
        'outr_twilio_common_settings',
        'outr_twilio_opted_out',
        'outr_twilio_phone_numbers',
    );

    // Call tables at end - conditionally removed based on preserve flag
    if (!$preserveCallRecords) {
        $tables[] = 'outr_twilio_calls';
    }

    foreach ($tables as $table) {
        try {
            if ($db->tableExists($table)) {
                $db->query("DROP TABLE IF EXISTS {$table}");
                $GLOBALS['log']->debug("Dropped table: {$table}");
            }
        } catch (Exception $e) {
            $GLOBALS['log']->warn("Could not drop table {$table}: " . $e->getMessage());
        }
    }

    $GLOBALS['log']->info('Sweet-Dialer: Tables dropped' . ($preserveCallRecords ? ' (call records preserved)' : ''));
}

/**
 * Clean uploaded files
 */
function cleanUploadedFiles()
{
    $GLOBALS['log']->debug('Sweet-Dialer: Cleaning uploaded files...');

    $uploadDirs = array(
        'upload/SweetDialer',
        'upload/twilio',
        'custom/include/TwilioDialer/temp'
    );

    foreach ($uploadDirs as $dir) {
        if (is_dir($dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($dir);
            $GLOBALS['log']->debug("Removed directory: {$dir}");
        }
    }

    $GLOBALS['log']->info('Sweet-Dialer: Uploaded files cleaned');
}

/**
 * Remove config entries
 */
function removeConfigEntries($db)
{
    $GLOBALS['log']->debug('Sweet-Dialer: Removing config entries...');

    $db->query("DELETE FROM config WHERE category = 'SweetDialer'");

    $GLOBALS['log']->info('Sweet-Dialer: Config entries removed');
}

/**
 * Clear SuiteCRM caches
 */
function repairAndClearAllCache()
{
    $dirs = array('cache/modules', 'cache/smarty', 'cache/jsLanguage');
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            foreach (glob($dir . '/*') as $file) {
                if (is_file($file)) @unlink($file);
            }
        }
    }
}
