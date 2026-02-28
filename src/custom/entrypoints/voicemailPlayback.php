<?php
/**
 * S-090-S-091: Voicemail Playback Entrypoint
 * Streams audio from Twilio securely
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'include/entryPoint.php';

// Authentication check
if (empty($current_user) || empty($current_user->id)) {
    header('HTTP/1.1 401 Unauthorized');
    echo 'Authentication required';
    exit;
}

// Validate recording SID
$recordingSid = $_REQUEST['recording_sid'] ?? '';
if (empty($recordingSid) || !preg_match('/^RE[a-f0-9]{32}$/i', $recordingSid)) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid recording_sid';
    exit;
}

// Get Twilio credentials
$twilioAccountSid = $GLOBALS['sugar_config']['twilio_account_sid'] ?? '';
$twilioAuthToken = $GLOBALS['sugar_config']['twilio_auth_token'] ?? '';

if (empty($twilioAccountSid) || empty($twilioAuthToken)) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Twilio configuration missing';
    exit;
}

// Determine format
$format = $_REQUEST['format'] ?? 'mp3';
if (!in_array($format, ['mp3', 'wav'])) {
    $format = 'mp3';
}

// Build Twilio URL
$url = "https://api.twilio.com/2010-04-01/Accounts/{$twilioAccountSid}/Recordings/{$recordingSid}.{$format}";

// Stream the audio
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "{$twilioAccountSid}:{$twilioAuthToken}");
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Set appropriate headers
if ($format === 'mp3') {
    header('Content-Type: audio/mpeg');
} else {
    header('Content-Type: audio/wav');
}

header('Accept-Ranges: bytes');
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

// Execute and stream
$fp = fopen('php://output', 'w');
curl_setopt($ch, CURLOPT_FILE, $fp);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($fp);

if ($httpCode !== 200) {
    http_response_code($httpCode);
}

exit;
