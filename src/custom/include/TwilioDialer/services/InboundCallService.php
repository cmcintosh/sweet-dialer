<?php
/**
 * S-103: Wire timeout into inbound handler
 * Uses ring_timeout value on <Dial> element
 */

require_once 'vendor/autoload.php';
use Twilio\TwiML\VoiceResponse;

class InboundCallService
{
    /**
     * Handle inbound call with configured ring timeout
     * @param array $params Twilio webhook parameters
     * @param string $ctiSettingId CTI Setting ID
     * @return string TwiML response
     */
    public function handleInboundCall($params, $ctiSettingId)
    {
        $response = new VoiceResponse();
        
        // Fetch CTI settings including ring timeout
        $settings = $this->fetchCtiSettings($ctiSettingId);
        
        if (empty($settings)) {
            // Fallback: no timeout specified, direct to voicemail
            $response->Say('The requested extension is not available.');
            return $response->asXML();
        }
        
        // Get ring timeout (default to 30 seconds)
        $ringTimeout = !empty($settings['ring_timeout']) ? intval($settings['ring_timeout']) : 30;
        
        // Validate range
        if ($ringTimeout < 5) $ringTimeout = 5;
        if ($ringTimeout > 60) $ringTimeout = 60;
        
        // Get agent info
        $agentNumber = $settings['agent_phone_number'];
        $agentId = $settings['outbound_inbound_agent_id'];
        
        // Build Dial with timeout
        $dial = $response->dial([
            'timeout' => $ringTimeout,
            'action' => $this->getDialActionUrl($ctiSettingId, $params['CallSid']),
            'method' => 'POST',
            'callerId' => $params['To'] ?? null,
            'record' => 'record-from-answer',
            'recordingStatusCallback' => $this->getRecordingCallbackUrl(),
        ]);
        
        // Add agent number
        if (!empty($agentNumber)) {
            $dial->number($agentNumber, [
                'statusCallback' => $this->getAgentStatusCallbackUrl(),
                'statusCallbackEvent' => 'initiated ringing answered completed',
            ]);
        }
        
        // Log the call with timeout value
        $this->logCall([
            'call_sid' => $params['CallSid'],
            'cti_setting_id' => $ctiSettingId,
            'ring_timeout' => $ringTimeout,
            'to_number' => $params['To'] ?? null,
            'from_number' => $params['From'] ?? null,
            'direction' => 'inbound',
        ]);
        
        return $response->asXML();
    }
    
    /**
     * Handle dial timeout - redirect to voicemail or queue
     */
    public function handleDialTimeout($params, $ctiSettingId)
    {
        $response = new VoiceResponse();
        
        // Check if DialStatus is 'no-answer' or 'busy'
        $dialStatus = $params['DialStatus'] ?? 'no-answer';
        
        if (in_array($dialStatus, ['no-answer', 'busy', 'failed'])) {
            // Play ringtone then voicemail
            $settings = $this->fetchCtiSettings($ctiSettingId);
            
            // Get voicemail configuration
            $voicemailId = $settings['twilio_voice_mail_id'] ?? null;
            
            if (!empty($voicemailId)) {
                // Play custom voicemail greeting
                $voicemailService = new VoicemailService();
                $voicemailService->playGreetingAndRecord($response, $voicemailId, $params['CallSid']);
            } else {
                // Default message
                $response->Say('Please leave a message after the tone.');
                $response->record([
                    'maxLength' => 300,
                    'action' => $this->getVoicemailCallbackUrl($params['CallSid']),
                ]);
            }
        }
        
        return $response->asXML();
    }
    
    /**
     * Fetch CTI settings from database
     */
    private function fetchCtiSettings($ctiSettingId)
    {
        $db = DBManagerFactory::getInstance();
        $sql = "SELECT id, name, ring_timeout, agent_phone_number, 
                       outbound_inbound_agent_id, twilio_voice_mail_id
                FROM outr_twilio_settings
                WHERE id = '%s' AND deleted = 0 AND status = 'Active'";
        
        $result = $db->query(sprintf($sql, $db->quote($ctiSettingId)));
        return $db->fetchByAssoc($result);
    }
    
    /**
     * Get URL for dial action callback
     */
    private function getDialActionUrl($ctiSettingId, $callSid)
    {
        return $GLOBALS['sugar_config']['site_url'] . 
               '/index.php?entryPoint=twilioWebhook&action=dial_timeout' .
               '&cti_setting_id=' . $ctiSettingId .
               '&call_sid=' . $callSid;
    }
    
    /**
     * Get URL for recording callback
     */
    private function getRecordingCallbackUrl()
    {
        return $GLOBALS['sugar_config']['site_url'] . 
               '/index.php?entryPoint=twilioWebhook&action=recording';
    }
    
    /**
     * Get agent status callback URL
     */
    private function getAgentStatusCallbackUrl()
    {
        return $GLOBALS['sugar_config']['site_url'] . 
               '/index.php?entryPoint=twilioWebhook&action=agent_status';
    }
    
    /**
     * Get voicemail callback URL
     */
    private function getVoicemailCallbackUrl($callSid)
    {
        return $GLOBALS['sugar_config']['site_url'] . 
               '/index.php?entryPoint=twilioWebhook&action=voicemail_save' .
               '&call_sid=' . $callSid;
    }
    
    /**
     * Log the inbound call
     */
    private function logCall($data)
    {
        $db = DBManagerFactory::getInstance();
        $id = create_guid();
        $sql = sprintf(
            "INSERT INTO outr_twilio_calls (id, call_sid, cti_setting_id, call_type, from_number, to_number, date_created, status) 
             VALUES ('%s', '%s', '%s', '%s', '%s', '%s', NOW(), '%s')",
            $id,
            $db->quote($data['call_sid']),
            $db->quote($data['cti_setting_id']),
            $db->quote($data['direction']),
            $db->quote($data['from_number']),
            $db->quote($data['to_number']),
            'ringing'
        );
        $db->query($sql);
    }
}
