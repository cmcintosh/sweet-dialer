<?php
/**
 * Sweet-Dialer Post-Installation Script (S-002)
 *
 * Creates database tables and registers custom modules in SuiteCRM.
 * Idempotent - safe to run multiple times.
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

function post_install()
{
    global $db;
    $GLOBALS['log']->debug('Sweet-Dialer: Starting post_install...');

    try {
        if (empty($db)) {
            $db = DBManagerFactory::getInstance();
        }

        if (!checkEnvironment($db)) {
            throw new Exception('Sweet-Dialer: Environment check failed');
        }

        runMigrations($db);
        registerModules($db);
        createSchedulers($db);
        repairAndClearAllCache();
        logInstallVersion($db);

        $GLOBALS['log']->info('Sweet-Dialer: post_install completed');

    } catch (Exception $e) {
        $GLOBALS['log']->fatal('Sweet-Dialer: ' . $e->getMessage());
        throw $e;
    }
}

function checkEnvironment($db)
{
    global $sugar_config;
    $compatible = true;

    $phpVersion = phpversion();
    if (version_compare($phpVersion, '7.4.0', '<')) {
        $GLOBALS['log']->error("PHP version {$phpVersion} is below minimum 7.4");
        $compatible = false;
    }

    $requiredExtensions = array('openssl', 'json', 'mbstring', 'curl');
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $GLOBALS['log']->error("Required extension '{$ext}' not loaded");
            $compatible = false;
        }
    }

    try {
        $testTable = 'outr_test_' . time();
        $db->query("CREATE TABLE IF NOT EXISTS {$testTable} (id INT)");
        $db->query("DROP TABLE IF EXISTS {$testTable}");
    } catch (Exception $e) {
        $GLOBALS['log']->error("Database lacks CREATE TABLE privileges");
        $compatible = false;
    }

    return $compatible;
}

function runMigrations($db)
{
    $migrationsDir = dirname(__DIR__) . '/install_migrations';
    if (!is_dir($migrationsDir)) {
        $GLOBALS['log']->error("Migrations directory not found: {$migrationsDir}");
        return;
    }

    $migrationFiles = glob($migrationsDir . '/*.sql');
    sort($migrationFiles);

    foreach ($migrationFiles as $file) {
        $sql = file_get_contents($file);
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (empty($statement)) continue;
            try {
                if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                    if ($db->tableExists($matches[1])) {
                        $GLOBALS['log']->debug("Table {$matches[1]} exists, skipping");
                        continue;
                    }
                }
                $db->query($statement);
            } catch (Exception $e) {
                $GLOBALS['log']->debug("Migration skipped: " . $e->getMessage());
            }
        }
    }
}

function registerModules($db)
{
    $modules = array(
        'outr_TwilioSettings', 'outr_TwilioCalls', 'outr_TwilioVoicemail',
        'outr_TwilioOptedOut', 'outr_TwilioDualRingtone', 'outr_TwilioHoldRingtone',
        'outr_TwilioCommonSettings', 'outr_TwilioErrorLogs', 'outr_TwilioLogger',
        'outr_TwilioPhoneNumbers', 'outr_TwilioVoicemailRecordings'
    );

    foreach ($modules as $name) {
        $result = $db->fetchOne("SELECT COUNT(*) as cnt FROM config WHERE category = 'module' AND name = '{$name}'");
        if (empty($result['cnt'])) {
            $db->query("INSERT INTO config (category, name, value) VALUES ('module', '{$name}', '1')");
        }
    }
}

function createSchedulers($db)
{
    $schedulers = array(
        array('name' => 'Twilio Call Sync', 'job' => 'function::twilioCallSync', 'interval' => '*/15'),
        array('name' => 'Twilio Voicemail Cleanup', 'job' => 'function::twilioVoicemailCleanup', 'interval' => '0::22'),
        array('name' => 'Twilio Log Archiving', 'job' => 'function::twilioLogArchive', 'interval' => '0::1'),
    );

    foreach ($schedulers as $s) {
        $existing = $db->fetchOne("SELECT id FROM schedulers WHERE job = '{$s['job']}' AND deleted = 0");
        if (empty($existing)) {
            $id = create_guid();
            $now = gmdate('Y-m-d H:i:s');
            $db->query("INSERT INTO schedulers (id, name, job, date_time_start, `interval`, status, catch_up, date_entered, date_modified, deleted) 
                       VALUES ('{$id}', '{$s['name']}', '{$s['job']}', '2024-01-01 00:00:01', '{$s['interval']}', 'Active', 1, '{$now}', '{$now}', 0)");
        }
    }
}

function logInstallVersion($db)
{
    $version = '1.0.0';
    $date = gmdate('Y-m-d H:i:s');
    $db->query("INSERT INTO config (category, name, value) VALUES ('SweetDialer', 'version', '{$version}') ON DUPLICATE KEY UPDATE value = '{$version}'");
    $db->query("INSERT INTO config (category, name, value) VALUES ('SweetDialer', 'install_date', '{$date}') ON DUPLICATE KEY UPDATE value = '{$date}'");
}

function repairAndClearAllCache()
{
    foreach (array('cache/modules', 'cache/smarty', 'cache/jsLanguage') as $dir) {
        if (is_dir($dir)) {
            foreach (glob($dir . '/*') as $file) {
                if (is_file($file)) @unlink($file);
            }
        }
    }
}
