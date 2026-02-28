<?php
/**
 * S-089: Voicemail Fetch Entrypoint
 * Fetches voicemail metadata from Twilio API
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'include/entryPoint.php';

// Authentication check
if (empty($current_user) || empty($current_user->id)) {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

try {
    // Validate input
    $callSid = $_REQUEST['call_sid'] ?? '';
    $recordingSid = $_REQUEST['recording_sid'] ?? '';

    if (empty($callSid) && empty($recordingSid)) {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: application/json');
        echo json_encode(['error' => 'call_sid or recording_sid required']);
        exit;
    }

    // Get Twilio credentials from config
    $twilioAccountSid = $GLOBALS['sugar_config']['twilio_account_sid'] ?? '';
    $twilioAuthToken = $GLOBALS['sugar_config']['twilio_auth_token'] ?? '';

    if (empty($twilioAccountSid) || empty($twilioAuthToken)) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Twilio configuration missing']);
        exit;
    }

    // Build Twilio API URL
    $baseUrl = "https://api.twilio.com/2010-04-01/Accounts/{$twilioAccountSid}";

    if (!empty($recordingSid)) {
        $url = "{$baseUrl}/Recordings/{$recordingSid}.json";
    } else {
        $url = "{$baseUrl}/Calls/{$callSid}/Recordings.json";
    }

    // Make request to Twilio
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "{$twilioAccountSid}:{$twilioAuthToken}");
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || $response === false) {
        header('HTTP/1.1 502 Bad Gateway');
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to fetch from Twilio', 'code' => $httpCode]);
        exit;
    }

    $data = json_decode($response, true);

    // Process recordings
    $recordings = [];
    if (isset($data['recordings'])) {
        foreach ($data['recordings'] as $recording) {
            $recordings[] = [
                'sid' => $recording['sid'],
                'account_sid' => $recording['account_sid'],
                'call_sid' => $recording['call_sid'],
                'duration' => $recording['duration'],
                'date_created' => $recording['date_created'],
                'uri' => $recording['uri'],
                'mp3_url' => "https://api.twilio.com{$recording['uri']}.mp3",
                'wav_url' => "https://api.twilio.com{$recording['uri']}.wav",
                'status' => $recording['status'],
                'source' => $recording['source'],
                'channels' => $recording['channels'],
                'price' => $recording['price'],
                'price_unit' => $recording['price_unit'],
            ];
        }
    } elseif (isset($data['sid'])) {
        // Single recording response
        $recordings[] = [
            'sid' => $data['sid'],
            'account_sid' => $data['account_sid'],
            'call_sid' => $data['call_sid'],
            'duration' => $data['duration'],
            'date_created' => $data['date_created'],
            'uri' => $data['uri'],
            'mp3_url' => "https://api.twilio.com{$data['uri']}.mp3",
            'wav_url' => "https://api.twilio.com{$data['uri']}.wav",
            'status' => $data['status'],
            'source' => $data['source'],
            'channels' => $data['channels'],
            'price' => $data['price'],
            'price_unit' => $data['price_unit'],
        ];
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => count($recordings),
        'recordings' => $recordings,
    ]);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}

exit;
