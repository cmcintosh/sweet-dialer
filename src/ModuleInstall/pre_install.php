<?php
/**
 * Pre-Install Check - SuiteCRM 8.x Compatibility
 * Safe installation with error handling and rollback detection
 */

function sweetDialerPreInstallCheck() {
    \$errors = array();
    \$warnings = array();
    \$isSuiteCRM8 = false;
    
    // 1. DETECT SUITECRM VERSION
    global \$sugar_version, \$sugar_config;
    \$suiteVersion = isset(\$sugar_version) ? \$sugar_version : (isset(\$sugar_config['suitecrm_version']) ? \$sugar_config['suitecrm_version'] : 'unknown');
    
    \$majorVersion = (int)substr(\$suiteVersion, 0, 1);
    \$isSuiteCRM8 = (\$majorVersion >= 8);
    
    // 2. CHECK PHP VERSION
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        \$errors[] = 'PHP 7.4+ required. Current: ' . PHP_VERSION;
    }
    
    // 3. CHECK REQUIRED EXTENSIONS
    \$required = array('curl', 'json', 'mbstring', 'openssl');
    foreach (\$required as \$ext) {
        if (!extension_loaded(\$ext)) {
            \$errors[] = "Required PHP extension missing: {$ext}";
        }
    }
    
    // 4. CHECK DATABASE PERMISSIONS
    try {
        \$db = DBManagerFactory::getInstance();
        \$result = \$db->query("SELECT 1");
        if (!\$result) {
            \$errors[] = 'Database connection issue';
        }
    } catch (Exception \$e) {
        \$errors[] = 'Database error: ' . \$e->getMessage();
    }
    
    // 5. CHECK WRITE PERMISSIONS
    \$paths = array(
        'custom/',
        'cache/',
        'upload/',
        'custom/Extension/',
        'custom/modules/',
        'custom/entrypoints/',
        'custom/include/'
    );
    
    foreach (\$paths as \$path) {
        \$fullPath = \$path;
        if (!file_exists(\$fullPath)) {
            // Try to create
            @mkdir(\$fullPath, 0755, true);
        }
        if (!is_writable(\$fullPath)) {
            \$errors[] = "Directory not writable: {$path}";
        }
    }
    
    // 6. CHECK FOR CONFLICTING MODULES
    \$conflicting = array(
        'AsteriskIntegration',
        'VICIDIAL',
        'TwilioIntegration',
        'FreePbxIntegration'
    );
    
    foreach (\$conflicting as \$module) {
        if (file_exists("modules/{$module}") || file_exists("custom/modules/{$module}")) {
            \$warnings[] = "Potential conflict: {$module} module detected";
        }
    }
    
    // 7. VERSION-SPECIFIC WARNINGS
    if (\$isSuiteCRM8) {
        \$warnings[] = 'Installing on SuiteCRM 8.x - Compatibility mode will be enabled';
        \$warnings[] = 'Some UI features may use legacy fallback mode';
        
        // Check for extension framework
        if (!file_exists('public/')) {
            \$errors[] = 'Public directory not found - invalid SuiteCRM 8.x installation';
        }
        
        // Check for Symfony
        if (!file_exists('core/app/Services/')) {
            \$errors[] = 'Symfony app structure not found - SuiteCRM 8.x required';
        }
    }
    
    // 8. CHECK EXISTING TABLES (prevent duplicate install issues)
    try {
        \$tables = array(
            'outr_twiliocalls',
            'outr_voicemail',
            'outr_ctisettings',
            'outr_conference'
        );
        \$existingTables = array();
        foreach (\$tables as \$table) {
            \$check = \$db->query("SHOW TABLES LIKE '{$table}'");
            if (\$check && \$check->fetchRow()) {
                \$existingTables[] = \$table;
            }
        }
        if (!empty(\$existingTables)) {
            \$warnings[] = 'Existing SweetDialer tables detected: ' . implode(', ', \$existingTables) . '. Upgrade mode enabled.';
        }
    } catch (Exception \$e) {
        // Non-fatal
    }
    
    // 9. CHECK ENTRY POINT REGISTRY
    if (file_exists('custom/Extension/application/Ext/EntryPointRegistry/sweetdialer.php')) {
        \$warnings[] = 'Existing entry point registry found. Will be overwritten.';
    }
    
    // BUILD RESULT
    \$result = array(
        'version' => \$suiteVersion,
        'is_suitecrm8' => \$isSuiteCRM8,
        'errors' => \$errors,
        'warnings' => \$warnings,
        'can_install' => empty(\$errors),
        'requires_compat_mode' => \$isSuiteCRM8
    );
    
    return \$result;
}

// EXECUTE CHECK
\$check = sweetDialerPreInstallCheck();

if (!\$check['can_install']) {
    \$msg = "❌ Installation BLOCKED:\n\n";
    foreach (\$check['errors'] as \$error) {
        \$msg .= "• {$error}\n";
    }
    \$msg .= "\nPlease fix these issues before installing SweetDialer.";
    die(\$msg);
}

// SHOW WARNINGS BUT ALLOW CONTINUE
if (!empty(\$check['warnings'])) {
    \$GLOBALS['sweetdialer_install_warnings'] = \$check['warnings'];
}

// STORE COMPATIBILITY INFO FOR INSTALL
\$GLOBALS['sweetdialer_is_suitecrm8'] = \$check['is_suitecrm8'];
\$GLOBALS['sweetdialer_version'] = \$check['version'];

echo "✅ Pre-install check passed for SuiteCRM " . \$check['version'];
if (\$check['is_suitecrm8']) {
    echo " (8.x Compatibility Mode ENABLED)";
}
echo "\n";

return true;
