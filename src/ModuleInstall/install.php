<?php
/**
 * Sweet-Dialer Post-Installation Script (S-002)
 *
 * Creates database tables and registers custom modules in SuiteCRM.
 * Idempotent - safe to run multiple times.
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * Main post-install function - triggered after package installation
 */
function post_install()
{
    global \$db, \$sugar_config;

    \$GLOBALS['log']->debug('Sweet-Dialer: Starting post_install...');

    try {
        // Get database instance
        if (empty(\$db)) {
            \$db = DBManagerFactory::getInstance();
        }

        // Check SuiteCRM environment
        if (!checkEnvironment(\$db)) {
            throw new Exception("Sweet-Dialer: Environment check failed. Install aborted.");
        }

        // Run database migrations
        \$GLOBALS['log']->debug('Sweet-Dialer: Running database migrations...');
        runMigrations(\$db);

        // Register modules in SuiteCRM's module registry
        \$GLOBALS['log']->debug('Sweet-Dialer: Registering modules...');
        registerModules(\$db);

        // Create default scheduler jobs
        \$GLOBALS['log']->debug('Sweet-Dialer: Creating scheduler jobs...');
        createSchedulers(\$db);

        // Clear SuiteCRM cache
        \$GLOBALS['log']->debug('Sweet-Dialer: Clearing cache...');
        repairAndClearAllCache();

        // Log install completion
        logInstallVersion(\$db);

        \$GLOBALS['log']->info('Sweet-Dialer: post_install completed successfully');

    } catch (Exception \$e) {
        \$GLOBALS['log']->fatal("Sweet-Dialer: post_install failed: " . \$e->getMessage());
        throw \$e;
    }
}

/**
 * Check SuiteCRM environment compatibility
 */
function checkEnvironment(\$db)
{
    global \$sugar_config;

    \$compatible = true;

    // Check SuiteCRM version
    \$suitecrmVersion = isset(\$sugar_config['suitecrm_version']) ? \$sugar_config['suitecrm_version'] : \$sugar_config['sugar_version'];
    \$GLOBALS['log']->debug("Sweet-Dialer: Detected SuiteCRM version: " . \$suitecrmVersion);

    // Check PHP version (8.0+ preferred, 7.4+ minimum)
    \$phpVersion = phpversion();
    if (version_compare(\$phpVersion, '7.4.0', '<')) {
        \$GLOBALS['log']->error("Sweet-Dialer: PHP version {\$phpVersion} is below minimum required 7.4");
        \$compatible = false;
    }

    // Check required PHP extensions
    \$requiredExtensions = array('openssl', 'json', 'mbstring', 'curl');
    foreach (\$requiredExtensions as \$ext) {
        if (!extension_loaded(\$ext)) {
            \$GLOBALS['log']->error("Sweet-Dialer: Required PHP extension '{\$ext}' not loaded");
            \$compatible = false;
        }
    }

    // Check database privileges
    \$testTable = 'outr_twilio_test_priv_' . time();
    try {
        \$db->query("CREATE TABLE IF NOT EXISTS {\$testTable} (id INT)");
        \$db->query("DROP TABLE IF EXISTS {\$testTable}");
    } catch (Exception \$e) {
        \$GLOBALS['log']->error("Sweet-Dialer: Database user lacks CREATE TABLE privileges");
        \$compatible = false;
    }

    return \$compatible;
}

/**
 * Run all database migrations from SQL files
 */
function runMigrations(\$db)
{
    \$migrationsDir = __DIR__ . '/../install_migrations';

    if (!is_dir(\$migrationsDir)) {
        \$GLOBALS['log']->error("Sweet-Dialer: Migrations directory not found: {\$migrationsDir}");
        return;
    }

    // Get all SQL migration files sorted
    \$migrationFiles = glob(\$migrationsDir . '/*.sql');
    sort(\$migrationFiles);

    foreach (\$migrationFiles as \$file) {
        \$filename = basename(\$file);
        \$GLOBALS['log']->debug("Sweet-Dialer: Processing migration: {\$filename}");

        \$sql = file_get_contents(\$file);
        if (\$sql === false) {
            \$GLOBALS['log']->error("Sweet-Dialer: Failed to read migration file: {\$filename}");
            continue;
        }

        // Split on semicolons
        \$statements = array_filter(array_map('trim', explode(';', \$sql)));

        foreach (\$statements as \$statement) {
            if (empty(\$statement)) {
                continue;
            }

            try {
                // Check if table already exists
                if (preg_match('/CREATE\\s+TABLE\\s+(?:IF\\s+NOT\\s+EXISTS\\s+)?`?(\\w+)`?/i', \$statement, \$matches)) {
                    \$tableName = \$matches[1];
                    if (\$db->tableExists(\$tableName)) {
                        \$GLOBALS['log']->debug("Sweet-Dialer: Table {\$tableName} already exists, skipping");
                        continue;
                    }
                }

                \$db->query(\$statement);
                \$GLOBALS['log']->debug("Sweet-Dialer: Executed migration: {\$filename}");
            } catch (Exception \$e) {
                \$GLOBALS['log']->debug("Sweet-Dialer: Migration skipped: " . \$e->getMessage());
            }
        }
    }

    \$GLOBALS['log']->info("Sweet-Dialer: Database migrations completed");
}

