<?php
/**
 * SweetDialer Pre-Flight Check Script
 * Run this on your SuiteCRM 8.8.0 server BEFORE installing the module
 * Provides detailed diagnostics without risking the live site
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     SweetDialer Pre-Flight Compatibility Check            ║\n";
echo "║     Safe diagnostics for SuiteCRM 8.x + SweetDialer       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$results = [
    'critical' => [],
    'warnings' => [],
    'success' => []
];

// Track if we can continue
$canInstall = true;

// Helper functions
function addCritical($msg) {
    global $results, $canInstall;
    $results['critical'][] = $msg;
    $canInstall = false;
}

function addWarning($msg) {
    global $results;
    $results['warnings'][] = $msg;
}

function addSuccess($msg) {
    global $results;
    $results['success'][] = $msg;
}

function section($title) {
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  🔍 $title\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
}

// ═══════════════════════════════════════════════════════════════
// 1. PHP VERSION CHECK
// ═══════════════════════════════════════════════════════════════
section("1. PHP Environment");

$phpVersion = phpversion();
echo "PHP Version: $phpVersion\n";

if (version_compare($phpVersion, '7.4.0', '>=')) {
    addSuccess("PHP $phpVersion (meets requirement 7.4+)");
} else {
    addCritical("PHP $phpVersion (required: 7.4+)");
}

// Check extensions
$required = ['curl', 'json', 'mbstring', 'openssl', 'pdo', 'pdo_mysql'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        addSuccess("Extension: $ext ✓");
    } else {
        addCritical("Missing extension: $ext");
    }
}

// ═══════════════════════════════════════════════════════════════
// 2. SUITECRM DETECTION
// ═══════════════════════════════════════════════════════════════
section("2. SuiteCRM Environment");

$suiteVersion = 'unknown';
$isSuiteCRM8 = false;
$suitecrmPath = './';

// Look for SuiteCRM files
if (file_exists('public/index.php') && file_exists('core/app/Services/')) {
    echo "✓ SuiteCRM 8.x structure detected\n";
    $isSuiteCRM8 = true;
} elseif (file_exists('index.php') && file_exists('include/utils.php')) {
    echo "✓ SuiteCRM 7.x structure detected\n";
} else {
    addCritical("SuiteCRM installation not detected in current directory");
}

// Try to read version
$versionFile = 'suitecrm_version.php';
if (file_exists($versionFile)) {
    $content = @file_get_contents($versionFile);
    if (preg_match(\/\/suite_crm_matches\/[&$sugar_version;#039;\"]\s*=>\s*[&$sugar_version;#039;\"](.*?)[&$sugar_version;#039;\"]/, $content, $matches)) {
        $suiteVersion = $matches[1];
        echo "Version: $suiteVersion\n";
    }
}

// Check for config.php
if (!file_exists('config.php') && !file_exists('config_override.php')) {
    addCritical("SuiteCRM config not found");
} else {
    addSuccess("SuiteCRM config files present");
}

// Detect write permissions
$paths = ['custom/', 'cache/', 'upload/', 'public/'];
foreach ($paths as $path) {
    if (file_exists($path)) {
        if (is_writable($path)) {
            addSuccess("Writable: $path");
        } else {
            addCritical("Not writable: $path");
        }
    }
}

// ═══════════════════════════════════════════════════════════════
// 3. DATABASE CHECK
// ═══════════════════════════════════════════════════════════════
section("3. Database Connectivity");

$dbConfig = [];
if (file_exists('config.php')) {
    require_once 'config.php';
    $dbConfig = $sugar_config['dbconfig'] ?? [];
}

if (empty($dbConfig)) {
    addCritical("Database configuration not found");
} else {
    try {
        $pdo = new PDO(
            "mysql:host={$dbConfig['db_host']};dbname={$dbConfig['db_name']}",
            $dbConfig['db_user_name'],
            $dbConfig['db_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        addSuccess("Database connection OK ({$dbConfig['db_name']})");
        
        // Check database version
        $stmt = $pdo->query("SELECT VERSION()");
        $version = $stmt->fetchColumn();
        echo "MySQL Version: $version\n";
        
        // Check for existing tables
        $tables = ['outr_twiliocalls', 'outr_voicemail', 'outr_ctisettings'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->fetch()) {
                addWarning("Existing table found: $table (upgrade mode will be used)");
            }
        }
        
    } catch (Exception $e) {
        addCritical("Database connection failed: " . $e->getMessage());
    }
}

// ═══════════════════════════════════════════════════════════════
// 4. EXISTING MODULES CHECK
// ═══════════════════════════════════════════════════════════════
section("4. Module Conflicts");

$conflictingModules = [
    'AsteriskIntegration',
    'VICIDIAL', 
    'TwilioIntegration',
    'FreePbxIntegration',
    'FusionPBX',
];

$modulesPath = 'modules/';
foreach ($conflictingModules as $module) {
    if (file_exists($modulesPath . $module)) {
        addWarning("Potential conflict: $module module detected");
    }
}

if (empty($results['warnings'])) {
    addSuccess("No conflicting modules detected");
}

// ═══════════════════════════════════════════════════════════════
// 5. WEBHOOK CONNECTIVITY CHECK
// ═══════════════════════════════════════════════════════════════
section("5. Webhook Requirements");

$serverName = $_SERVER['SERVER_NAME'] ?? 'unknown';
echo "Server URL: https://$serverName\n";

if ($serverName === 'localhost' || $serverName === '127.0.0.1') {
    addWarning("localhost detected - webhooks require public URL");
}

// Check SSL
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    addSuccess("SSL/HTTPS enabled ✓");
} else {
    addWarning("SSL not detected - Twilio requires HTTPS");
}

// Check if server is reachable
$protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
$baseUrl = "$protocol://$serverName";
echo "Base URL: $baseUrl\n";
echo "Webhook URLs will be:\n";
echo "  • Voice: {$baseUrl}/index.php?entryPoint=voiceWebhook\n";
echo "  • Status: {$baseUrl}/index.php?entryPoint=statusCallback\n";
echo "  • Recording: {$baseUrl}/index.php?entryPoint=recordingCallback\n";

// ═══════════════════════════════════════════════════════════════
// 6. SUITECRM 8 SPECIFIC CHECKS
// ═══════════════════════════════════════════════════════════════
if ($isSuiteCRM8) {
    section("6. SuiteCRM 8.x Compatibility");
    
    // Check for Symfony
    if (file_exists('vendor/symfony/')) {
        addSuccess("Symfony framework detected");
    } else {
        addWarning("Symfony not in vendor - may need composer install");
    }
    
    // Check for public directory
    if (file_exists('public/')) {
        addSuccess("Public directory present ✓");
    } else {
        addCritical("Public directory missing");
    }
    
    // Check for routes config
    if (file_exists('config/routes/')) {
        addSuccess("Routes directory exists ✓");
    }
    
    addWarning("SuiteCRM 8.x detected - some UI features use legacy mode");
    addSuccess("Entry points will use Symfony routing + legacy fallback");
}

// ═══════════════════════════════════════════════════════════════
// 7. FILE PERMISSIONS
// ═══════════════════════════════════════════════════════════════
section("7. File System Permissions");

$requiredPaths = [
    'custom/' => ['read' => true, 'write' => true],
    'upload/' => ['read' => true, 'write' => true],
    'cache/' => ['read' => true, 'write' => true],
    'config.php' => ['read' => true, 'write' => true],
];

foreach ($requiredPaths as $path => $perms) {
    if (file_exists($path)) {
        $readable = is_readable($path);
        $writable = is_writable($path);
        
        $status = [];
        if ($perms['read'] && $readable) $status[] = "R";
        if ($perms['read'] && !$readable) $status[] = "NO-R";
        if ($perms['write'] && $writable) $status[] = "W";
        if ($perms['write'] && !$writable) $status[] = "NO-W";
        
        $statusStr = implode('/', $status);
        
        if (!$readable || !$writable) {
            addCritical("Permissions issue: $path ($statusStr)");
        } else {
            addSuccess("Permissions OK: $path");
        }
    } else {
        addCritical("Missing path: $path");
    }
}

// ═══════════════════════════════════════════════════════════════
// 8. MEMORY AND TIMEOUT
// ═══════════════════════════════════════════════════════════════
section("8. System Resources");

$memoryLimit = ini_get('memory_limit');
echo "Memory limit: $memoryLimit\n";

$memBytes = returnBytes($memoryLimit);
if ($memBytes < 256 * 1024 * 1024) {
    addWarning("Low memory limit: $memoryLimit (recommended: 256M+)");
} else {
    addSuccess("Memory limit: $memoryLimit");
}

$maxExecution = ini_get('max_execution_time');
echo "Max execution time: $maxExecution seconds\n";

if ($maxExecution < 300 && $maxExecution != 0) {
    addWarning("Short timeout: {$maxExecution}s (recommended: 300s+)");
} else {
    addSuccess("Execution timeout: " . ($maxExecution == 0 ? "unlimited" : $maxExecution . 's'));
}

// ═══════════════════════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════════════════════
section("SUMMARY");

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                     RESULTS                               ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";

if (!empty($results['critical'])) {
    echo "║  ❌ CRITICAL ISSUES: " . count($results['critical']) . "                                    ║\n";
    foreach ($results['critical'] as $issue) {
        $line = "  • $issue";
        echo "║" . str_pad(substr($line, 0, 58), 58, ' ') . "║\n";
    }
} else {
    echo "║  ✅ No critical issues found                               ║\n";
}

echo "╠════════════════════════════════════════════════════════════╣\n";

if (!empty($results['warnings'])) {
    echo "║  ⚠️  WARNINGS: " . count($results['warnings']) . "                                          ║\n";
    foreach ($results['warnings'] as $warning) {
        $line = "  • $warning";
        echo "║" . str_pad(substr($line, 0, 58), 58, ' ') . "║\n";
    }
} else {
    echo "║  ✅ No warnings                                            ║\n";
}

echo "╠════════════════════════════════════════════════════════════╣\n";
echo "║  ✅ CHECKS PASSED: " . count($results['success']) . "                                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if ($canInstall) {
    echo "🎉 PRE-FLIGHT COMPLETE: Ready to install SweetDialer!\n\n";
    echo "Next steps:\n";
    echo "  1. Upload sweet-dialer-v1.0.0.zip to SuiteCRM\n";
    echo "  2. Go to Admin → Module Loader\n";
    echo "  3. Upload and Install the package\n";
    echo "  4. Configure in Admin → SweetDialer → Settings\n";
    echo "  5. Set up Twilio webhooks\n";
    if ($isSuiteCRM8) {
        echo "\nNote: SuiteCRM 8.x detected - some features use legacy mode\n";
    }
} else {
    echo "❌ PRE-FLIGHT COMPLETE: Installation BLOCKED\n\n";
    echo "Please fix the critical issues above before installing.\n";
    exit(1);
}

// Helper function for memory
function returnBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int) $val;
    
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}
