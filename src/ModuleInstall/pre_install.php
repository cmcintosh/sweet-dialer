<?php
/**
 * Sweet-Dialer Pre-Installation Script
 *
 * Runs before package installation to validate environment
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

function pre_install()
{
    $GLOBALS['log']->debug('Sweet-Dialer: Starting pre_install checks...');
    
    // Validate PHP version
    $phpVersion = phpversion();
    if (version_compare($phpVersion, '7.4.0', '<')) {
        throw new Exception("PHP version {$phpVersion} is below minimum required 7.4");
    }
    
    // Validate required extensions
    $requiredExtensions = array('openssl', 'json', 'mbstring', 'curl');
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            throw new Exception("Required PHP extension '{$ext}' not loaded");
        }
    }
    
    $GLOBALS['log']->info('Sweet-Dialer: Pre-install checks passed');
    return true;
}
