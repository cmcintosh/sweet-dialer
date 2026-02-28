<?php

require_once 'include/entryPoint.php';
require_once 'custom/include/TwilioDialer/lib/TwilioConnector.php';

$conn = new TwilioConnector();
$response = ['success' => false, 'message' => ''];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $callSid = $input['call_sid'] ?? '';
    $targetAgentId = $input['target_agent_id'] ?? '';

    if (empty($callSid)) {
        throw new Exception('Missing required parameter: call_sid');
    }

    if (empty($targetAgentId)) {
        throw new Exception('Missing required parameter: target_agent_id');
    }

    // Get target agent's phone/extension
    $agentBean = BeanFactory::getBean('Users', $targetAgentId);
    if (!$agentBean || !$agentBean->id) {
        throw new Exception('Target agent not found');
    }

    $targetPhone = $agentBean->phone_mobile ?: $agentBean->phone_work;
    if (empty($targetPhone)) {
        throw new Exception('Target agent has no phone number configured');
    }

    // Get call record
    $callBean = new OutrTwilioCalls();
    $callBean->retrieve_by_string_fields([
        'twilio_call_id' => $callSid,
        'deleted' => 0
    ]);

    if (empty($callBean->id)) {
        throw new Exception('Call record not found');
    }

    // Update call with transfer metadata
    $callBean->transfer_type = 'warm';
    $callBean->transfer_status = 'pending';
    $callBean->transfer_to = $targetAgentId;
    $callBean->save();

    // Get Twilio client
    $twilio = $conn->getClient();
    $accountSid = $conn->getAccountSid();

    // Create conference name from call SID
    $conferenceName = 'transfer_' . $callSid;

    // Get current call details
    $call = $twilio->calls($callSid)->fetch();

    // Place the caller on hold in a conference
    $holdTwiml = new \Twilio\TwiML\VoiceResponse();
    $holdTwiml->say('Please hold while we connect you to another agent.');
    $holdTwiml->play('http://com.twilio.sounds.music.s3.amazonaws.com/MARKOVICHAMP-Borghestral.mp3');
    $holdTwiml->dial()->conference($conferenceName, [
        'startConferenceOnEnter' => false,
        'endConferenceOnExit' => false,
        'waitUrl' => ''
    ]);

    // Update call to put caller in conference
    $twilio->calls($callSid)->update([
        'twiml' => $holdTwiml->asXML()
    ]);

    // Call the target agent and add them to conference
    $agentTwiml = new \Twilio\TwiML\VoiceResponse();
    $agentTwiml->say('Transferring a call to you. Press any key to accept.');
    $agentTwiml->dial()->conference($conferenceName, [
        'startConferenceOnEnter' => true,
        'endConferenceOnExit' => true
    ]);

    $outboundCall = $twilio->calls->create(
        $targetPhone,
        $call->to,
        [
            'twiml' => $agentTwiml->asXML(),
            'statusCallback' => $GLOBALS['sugar_config']['site_url'] . '/custom/entrypoints/transferStatus.php',
            'statusCallbackEvent' => ['completed', 'failed', 'no-answer']
        ]
    );

    $response['success'] = true;
    $response['message'] = 'Warm transfer initiated';
    $response['conference_name'] = $conferenceName;
    $response['agent_call_sid'] = $outboundCall->sid;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

header('Content-Type: application/json');
echo json_encode($response);
