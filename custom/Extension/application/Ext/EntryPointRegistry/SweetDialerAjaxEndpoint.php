<?php
/**
 * EntryPoint Registry - AJAX Phone Numbers
 *
 * Registers the AJAX endpoint for fetching phone numbers.
 */

$entry_point_registry['sweetdialer_ajax_phone_numbers'] = [
    'file' => 'custom/include/TwilioDialer/AjaxPhoneNumberHandler.php',
    'auth' => false, // Handle auth in handler for AJAX cross-origin flexibility
];
