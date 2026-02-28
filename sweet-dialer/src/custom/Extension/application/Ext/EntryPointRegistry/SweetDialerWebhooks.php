<?php
/**
 * SweetDialerWebhooks.php
 *
 * Sweet-Dialer Webhook Entry Point Registration
 *
 * Registers all Twilio webhook endpoints with SuiteCRM's entry point registry.
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

/**
 * Entry Point Registration for Twilio Voice Webhooks
 *
 * This file is loaded by Extension Manager during rebuild.
 * It registers all voice webhook endpoints for inbound/outbound calling.
 */

// S-045: Register 8 webhook entrypoints

// Outbound call handler (dial from Client SDK to PSTN)
$entry_point_registry['sweetdialer_voice_outbound'] = [
    'file' => 'custom/entrypoints/voiceOutbound.php',
    'auth' => false, // Twilio calls this directly, auth via signature
];

// Inbound call handler (PSTN to Client)
$entry_point_registry['sweetdialer_voice_inbound'] = [
    'file' => 'custom/entrypoints/voiceInbound.php',
    'auth' => false, // Twilio calls this directly, auth via signature
];

// Call status callback handler
$entry_point_registry['sweetdialer_voice_status'] = [
    'file' => 'custom/entrypoints/voiceStatus.php',
    'auth' => false, // Twilio calls this directly, auth via signature
];

// Recording status callback handler
$entry_point_registry['sweetdialer_voice_recording'] = [
    'file' => 'custom/entrypoints/voiceRecording.php',
    'auth' => false, // Twilio calls this directly, auth via signature
];

// Voicemail handler
$entry_point_registry['sweetdialer_voice_voicemail'] = [
    'file' => 'custom/entrypoints/voiceVoicemail.php',
    'auth' => false, // Twilio calls this directly, auth via signature
];

// Hold music handler
$entry_point_registry['sweetdialer_voice_hold'] = [
    'file' => 'custom/entrypoints/voiceHold.php',
    'auth' => false, // Twilio calls this directly, auth via signature
];

// Transfer handler
$entry_point_registry['sweetdialer_voice_transfer'] = [
    'file' => 'custom/entrypoints/voiceTransfer.php',
    'auth' => false, // Twilio calls this directly, auth via signature
];

// DTMF/IVR handler
$entry_point_registry['sweetdialer_voice_dtmf'] = [
    'file' => 'custom/entrypoints/voiceDTMF.php',
    'auth' => false, // Twilio calls this directly, auth via signature
];
