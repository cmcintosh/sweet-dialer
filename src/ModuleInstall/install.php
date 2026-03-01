<?php
/**
 * Sweet-Dialer Post-Installation Script with Error Handling
 * SuiteCRM 7.x and 8.x Compatible
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * Safe post_install with error handling
 */
function post_install()
{
    global \$sugar_config, \$db;
    
    \$logPrefix = 'SweetDialer-Install:';
    \$errors = array();
    
    try {
        \$GLOBALS['log']->debug("{$logPrefix} Starting installation...");
        
        // Check compatibility mode
        \$isSuiteCRM8 = detectSuiteCRM8();
        if (\$isSuiteCRM8) {
            \$GLOBALS['log']->debug("{$logPrefix} SuiteCRM 8.x detected - enabling compatibility mode");
        }
        
        // Get database instance
        if (empty(\$db)) {
            \$db = DBManagerFactory::getInstance();
        }
        
        if (empty(\$db)) {
            throw new Exception('Database connection failed');
        }
        
        // 1. CREATE DATABASE TABLES
        try {
            createDatabaseTables(\$db, \$logPrefix);
        } catch (Exception \$e) {
            \$errors[] = 'Database tables: ' . \$e->getMessage();
            \$GLOBALS['log']->error("{$logPrefix} Table creation failed: " . \$e->getMessage());
        }
        
        // 2. REGISTER ENTRY POINTS (Legacy)
        try {
            registerEntryPoints(\$logPrefix);
        } catch (Exception \$e) {
            \$errors[] = 'Entry points: ' . \$e->getMessage();
            \$GLOBALS['log']->error("{$logPrefix} Entry point registration failed: " . \$e->getMessage());
        }
        
        // 3. SETUP SUITECRM 8 ROUTES (If 8.x)
        if (\$isSuiteCRM8) {
            try {
                setupSuiteCRM8Routes(\$logPrefix);
            } catch (Exception \$e) {
                \$errors[] = 'SuiteCRM 8 routes: ' . \$e->getMessage();
                \$GLOBALS['log']->error("{$logPrefix} Route setup warning: " . \$e->getMessage());
                // Non-fatal for 8.x
            }
        }
        
        // 4. CREATE DEFAULT CTI SETTINGS
        try {
            createDefaultSettings(\$db, \$logPrefix);
        } catch (Exception \$e) {
            \$errors[] = 'Default settings: ' . \$e->getMessage();
            \$GLOBALS['log']->error("{$logPrefix} Settings creation warning: " . \$e->getMessage());
        }
        
        // 5. CLEAR CACHE
        try {
            repairAndClearAllCache();
        } catch (Exception \$e) {
            \$GLOBALS['log']->warning("{$logPrefix} Cache clear warning: " . \$e->getMessage());
        }
        
        // REPORT RESULTS
        if (!empty(\$errors)) {
            \$GLOBALS['log']->warning("{$logPrefix} Installation completed with warnings: " . implode(', ', \$errors));
            echo "⚠️ Installation completed with warnings:\n";
            foreach (\$errors as \$error) {
                echo "  - {$error}\n";
            }
            echo "\nModule is functional but some features may be limited.\n";
            echo "Check sugarcrm.log for details.\n";
        } else {
            \$GLOBALS['log']->debug("{$logPrefix} Installation completed successfully");
            echo "✅ SweetDialer installed successfully!\n";
            if (\$isSuiteCRM8) {
                echo "SuiteCRM 8.x Compatibility Mode: ENABLED\n";
                echo "Note: Some UI features use legacy fallback.\n";
            }
        }
        
    } catch (Exception \$e) {
        \$GLOBALS['log']->fatal("{$logPrefix} FATAL INSTALL ERROR: " . \$e->getMessage());
        throw new Exception("SweetDialer installation failed: " . \$e->getMessage());
    }
}

/**
 * Detect SuiteCRM 8.x
 */
function detectSuiteCRM8(): bool
{
    // Check for Symfony structure
    if (file_exists('public/index.php') && file_exists('core/app/Services/')) {
        return true;
    }
    
    // Check version
    global \$sugar_version, \$suitecrm_version;
    \$version = \$suitecrm_version ?? \$sugar_version ?? '';
    \$major = (int)substr(\$version, 0, 1);
    return (\$major >= 8);
}

/**
 * Create database tables with error handling
 */
function createDatabaseTables(\$db, \$logPrefix)
{
    \$migrations = getMigrationFiles();
    
    foreach (\$migrations as \$file) {
        \$path = "custom/install_migrations/{$file}";
        if (!file_exists(\$path)) {
            \$path = "install_migrations/{$file}";
        }
        
        if (!file_exists(\$path)) {
            \$GLOBALS['log']->warning("{$logPrefix} Migration file not found: {$file}");
            continue;
        }
        
        \$sql = file_get_contents(\$path);
        
        try {
            \$db->query(\$sql);
            \$GLOBALS['log']->debug("{$logPrefix} Executed migration: {$file}");
        } catch (Exception \$e) {
            if (stripos(\$e->getMessage(), 'already exists') === false) {
                throw new Exception("Migration {$file} failed: " . \$e->getMessage());
            }
        }
    }
}

/**
 * Register legacy entry points
 */
function registerEntryPoints(\$logPrefix)
{
    \$entryPoints = array(
        'voiceWebhook' => array('file' => 'custom/entrypoints/voiceWebhook.php', 'auth' => false),
        'statusCallback' => array('file' => 'custom/entrypoints/statusCallback.php', 'auth' => false),
        'recordingCallback' => array('file' => 'custom/entrypoints/recordingCallback.php', 'auth' => false),
        'transferWarm' => array('file' => 'custom/entrypoints/transferWarm.php', 'auth' => true),
        'transferCold' => array('file' => 'custom/entrypoints/transferCold.php', 'auth' => true),
        'conferenceJoin' => array('file' => 'custom/entrypoints/conferenceJoin.php', 'auth' => true),
        'conferenceControl' => array('file' => 'custom/entrypoints/conferenceControl.php', 'auth' => true),
        'voicemailFetch' => array('file' => 'custom/entrypoints/voicemailFetch.php', 'auth' => true),
        'voicemailPlayback' => array('file' => 'custom/entrypoints/voicemailPlayback.php', 'auth' => false),
        'dialerDashboard' => array('file' => 'custom/entrypoints/dialerDashboard.php', 'auth' => true),
        'analyticsData' => array('file' => 'custom/entrypoints/analyticsData.php', 'auth' => true),
        'exportReport' => array('file' => 'custom/entrypoints/exportReport.php', 'auth' => true),
    );
    
    \$registryFile = 'custom/Extension/application/Ext/EntryPointRegistry/sweetdialer.php';
    
    \$content = "<?php\n";
    \$content .= "/**\n";
    \$content .= " * SweetDialer Entry Points - AUTO GENERATED\n";
    \$content .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
    \$content .= " */\n\n";
    
    \$content .= "\$entry_point_registry = array(\n";
    foreach (\$entryPoints as \$name => \$config) {
        \$content .= "    '{$name}' => array(\n";
        \$content .= "        'file' => '{$config['file']}',\n";
        \$content .= "        'auth' => " . (\$config['auth'] ? 'true' : 'false') . ",\n";
        \$content .= "    ),\n";
    }
    \$content .= ");\n";
    
    // Ensure directory exists
    @mkdir(dirname(\$registryFile), 0755, true);
    
    if (file_put_contents(\$registryFile, \$content) === false) {
        throw new Exception('Cannot write entry point registry');
    }
    
    \$GLOBALS['log']->debug("{$logPrefix} Entry points registered");
}

/**
 * Setup SuiteCRM 8 routes
 */
function setupSuiteCRM8Routes(\$logPrefix)
{
    \$sourceRoutes = __DIR__ . '/../config/routes/sweetdialer.yaml';
    \$targetRoutes = 'config/routes/sweetdialer.yaml';
    
    if (file_exists(\$sourceRoutes)) {
        @mkdir(dirname(\$targetRoutes), 0755, true);
        @copy(\$sourceRoutes, \$targetRoutes);
        \$GLOBALS['log']->debug("{$logPrefix} Symfony routes installed");
    }
    
    // Also copy controllers
    \$sourceController = __DIR__ . '/../src/Controller';
    \$targetController = 'src/Controller/Wembassy/SweetDialer';
    
    if (file_exists(\$sourceController) && is_dir(\$sourceController)) {
        @mkdir(\$targetController, 0755, true);
        recurseCopy(\$sourceController, \$targetController);
        \$GLOBALS['log']->debug("{$logPrefix} Symfony controllers installed");
    }
}

/**
 * Create default settings
 */
function createDefaultSettings(\$db, \$logPrefix)
{
    // Check if default record exists
    \$check = \$db->query("SELECT id FROM outr_ctisettings WHERE deleted = 0 LIMIT 1");
    if (\$check && \$check->fetchRow()) {
        \$GLOBALS['log']->debug("{$logPrefix} Default CTI settings already exist");
        return;
    }
    
    \$id = create_guid();
    \$now = date('Y-m-d H:i:s');
    
    \$sql = "INSERT INTO outr_ctisettings 
        (id, name, twilio_account_sid, twilio_auth_token, twilio_api_key, twilio_api_secret, 
         twilio_phone_number, app_sid, date_entered, created_by)
        VALUES 
        ('{$id}', 'Default CTI Settings', '', '', '', '', '', '', '{$now}', '1')";
    
    \$db->query(\$sql);
    \$GLOBALS['log']->debug("{$logPrefix} Default CTI settings created");
}

/**
 * Get migration files
 */
function getMigrationFiles()
{
    \$files = array();
    \$dir = __DIR__ . '/../install_migrations/';
    
    if (is_dir(\$dir)) {
        \$iterator = new DirectoryIterator(\$dir);
        foreach (\$iterator as \$fileinfo) {
            if (\$fileinfo->isFile() && \$fileinfo->getExtension() === 'sql') {
                \$files[] = \$fileinfo->getFilename();
            }
        }
    }
    
    sort(\$files);
    return \$files;
}

/**
 * Recursive directory copy
 */
function recurseCopy(\$src, \$dst)
{
    \$dir = opendir(\$src);
    @mkdir(\$dst, 0755, true);
    while (false !== (\$file = readdir(\$dir))) {
        if ((\$file != '.') && (\$file != '..')) {
            if (is_dir(\$src . '/' . \$file)) {
                recurseCopy(\$src . '/' . \$file, \$dst . '/' . \$file);
            } else {
                copy(\$src . '/' . \$file, \$dst . '/' . \$file);
            }
        }
    }
    closedir(\$dir);
}
