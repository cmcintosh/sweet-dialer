<?php
/**
 * SweetDialerTokenEndpoint.php
 *
 * Sweet-Dialer Token Generation Entry Point Registration
 *
 * Registers the token endpoint with SuiteCRM's entry point registry.
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

/**
 * Entry Point Registration
 *
 * This file is loaded by Extension Manager during rebuild.
 * It registers the token generation endpoint.
 */
$entry_point_registry['sweetdialer_token'] = [
    'file' => 'custom/entrypoints/tokenEndpoint.php',
    'auth' => true, // Requires authenticated session
];
