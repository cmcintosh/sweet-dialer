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

    // Get target agent's phone
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

    if (!empty($callBean->id)) {
        // Update call with transfer metadata
        $callBean->transfer_type = 'cold';
        $callBean->transfer_status = 'completed';
        $callBean->transfer_to = $targetAgentId;
        $callBean->save();
    }

    // Get Twilio client
    $twilio = $conn->getClient();

    // Perform blind/cold transfer - redirect call to target agent
    $redirectTwiml = new \Twilio\TwiML\VoiceResponse();
    $redirectTwiml->say('Your call is being transferred. Please hold.');
    $redirectTwiml->dial($targetPhone, [
        'timeout' => 30,
        'callerId' => $callBean->from_number ?? null
    ]);

    // Update the call with new TwiML
    $twilio->calls($callSid)->update([
        'twiml' => $redirectTwiml->asXML()
    ]);

    $response['success'] = true;
    $response['message'] = 'Cold transfer completed';
    $response['transferred_to'] = $targetPhone;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

header('Content-Type: application/json');
echo json_encode($response);
