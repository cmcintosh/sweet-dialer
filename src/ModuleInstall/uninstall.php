<?php
/**
 * Sweet-Dialer Uninstall Script with Preservation
 * Removes module but PRESERVES call records
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

function post_uninstall()
{
    \$logPrefix = 'SweetDialer-Uninstall:';
    \$warnings = array();
    
    try {
        \$GLOBALS['log']->debug("{$logPrefix} Starting uninstall...");
        
        // Get database instance
        \$db = DBManagerFactory::getInstance();
        
        // 1. PRE-SERVE DATA
        try {
            backupCallData(\$db, \$logPrefix);
        } catch (Exception \$e) {
            \$warnings[] = 'Call data backup: ' . \$e->getMessage();
        }
        
        // 2. REMOVE CUSTOM FIELDS (but keep tables)
        try {
            removeCustomFields(\$db, \$logPrefix);
        } catch (Exception \$e) {
            \$warnings[] = 'Custom fields: ' . \$e->getMessage();
        }
        
        // 3. REMOVE ENTRY POINTS
        try {
            removeEntryPoints(\$logPrefix);
        } catch (Exception \$e) {
            \$warnings[] = 'Entry points: ' . \$e->getMessage();
        }
        
        // 4. REMOVE SYMFONY ROUTES (if 8.x)
        if (detectSuiteCRM8()) {
            try {
                removeSymfonyRoutes(\$logPrefix);
            } catch (Exception \$e) {
                \$warnings[] = 'Symfony routes: ' . \$e->getMessage();
            }
        }
        
        // 5. CLEAR CACHE
        try {
            repairAndClearAllCache();
        } catch (Exception \$e) {
            // Non-fatal
        }
        
        if (!empty(\$warnings)) {
            \$GLOBALS['log']->warning("{$logPrefix} Uninstall completed with warnings: " . implode(', ', \$warnings));
            echo "⚠️ Uninstall completed with warnings:\n";
            foreach (\$warnings as \$warning) {
                echo "  - {$warning}\n";
            }
        } else {
            \$GLOBALS['log']->debug("{$logPrefix} Uninstall completed successfully");
            echo "✅ SweetDialer uninstalled.\n";
            echo "📞 Call records have been preserved.\n";
        }
        
    } catch (Exception \$e) {
        \$GLOBALS['log']->fatal("{$logPrefix} Uninstall error: " . \$e->getMessage());
        echo "❌ Uninstall error: " . \$e->getMessage() . "\n";
    }
}

/**
 * Backup call data before uninstall
 */
function backupCallData(\$db, \$logPrefix)
{
    \$backupDir = 'upload/sweetdialer_backup/';
    @mkdir(\$backupDir, 0755, true);
    
    \$tables = array(
        'outr_twiliocalls' => 'calls_backup_' . time(),
        'outr_voicemail' => 'voicemail_backup_' . time()
    );
    
    \$saved = array();
    foreach (\$tables as \$source => \$backup) {
        if (\$db->tableExists(\$source)) {
            // Create backup table
            \$sql = "CREATE TABLE {$backup} LIKE {$source}";
            \$db->query(\$sql);
            
            // Copy data
            \$sql = "INSERT INTO {$backup} SELECT * FROM {$source} WHERE deleted = 0";
            \$db->query(\$sql);
            
            \$saved[] = \$source;
        }
    }
    
    // Save backup info
    \$info = array(
        'timestamp' => time(),
        'tables' => \$saved
    );
    file_put_contents(\$backupDir . 'backup_info.json', json_encode(\$info));
    
    \$GLOBALS['log']->debug("{$logPrefix} Data backed up to: " . implode(', ', \$saved));
}

/**
 * Remove custom fields from existing modules
 */
function removeCustomFields(\$db, \$logPrefix)
{
    // Remove from contacts, leads, cases, accounts
    \$tables = array('contacts', 'leads', 'cases', 'accounts');
    \$fields = array('twilio_call_count', 'twilio_last_call', 'twilio_phone_status');
    
    foreach (\$tables as \$table) {
        foreach (\$fields as \$field) {
            try {
                if (\$db->columnExists(\$table, \$field)) {
                    \$sql = "ALTER TABLE {$table} DROP COLUMN {$field}";
                    \$db->query(\$sql);
                }
            } catch (Exception \$e) {
                // Field might not exist, skip
            }
        }
    }
    
    \$GLOBALS['log']->debug("{$logPrefix} Custom fields removed");
}

/**
 * Remove entry points
 */
function removeEntryPoints(\$logPrefix)
{
    \$registryFile = 'custom/Extension/application/Ext/EntryPointRegistry/sweetdialer.php';
    if (file_exists(\$registryFile)) {
        @unlink(\$registryFile);
        \$GLOBALS['log']->debug("{$logPrefix} Entry points removed");
    }
}

/**
 * Remove Symfony routes
 */
function removeSymfonyRoutes(\$logPrefix)
{
    \$routesFile = 'config/routes/sweetdialer.yaml';
    if (file_exists(\$routesFile)) {
        @unlink(\$routesFile);
        \$GLOBALS['log']->debug("{$logPrefix} Symfony routes removed");
    }
}

/**
 * Detect SuiteCRM 8.x
 */
function detectSuiteCRM8(): bool
{
    if (file_exists('public/index.php') && file_exists('core/app/Services/')) {
        return true;
    }
    global \$sugar_version, \$suitecrm_version;
    \$version = \$suitecrm_version ?? \$sugar_version ?? '';
    \$major = (int)substr(\$version, 0, 1);
    return (\$major >= 8);
}
