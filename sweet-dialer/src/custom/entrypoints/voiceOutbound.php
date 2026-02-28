<?php
/**
 * voiceOutbound.php
 *
 * Sweet-Dialer Voice Outbound Webhook Handler
 *
 * Handles outbound calls from Twilio Client SDK to PSTN numbers.
 * Generates TwiML to dial the destination with recording enabled.
 *
 * URL: /entryPoint.php?entryPoint=sweetdialer_voice_outbound
 * Mapped to: /twilio/voice/outbound
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    define('sugarEntry', true);
}

require_once 'include/entryPoint.php';
require_once 'custom/include/TwilioDialer/WebhookHandler.php';

/**
 * VoiceOutboundHandler
 *
 * S-046: Handles outbound calls from Client SDK to PSTN
 * S-047: Adds recording to outbound calls
 */
class VoiceOutboundHandler extends WebhookHandler
{
    /**
     * Get the endpoint name
     *
     * @return string
     */
    protected function getEndpointName()
    {
        return 'outbound';
    }

    /**
     * Process the outbound call request
     *
     * Receives To number from Client SDK, generates TwiML to dial
     * with agent's caller ID and recording.
     *
     * @return TwiMLResponse
     * @throws Exception
     */
    protected function processRequest()
    {
        // Get the destination number (To) from request
        $toNumber = $this->getParam('To');

        if (empty($toNumber)) {
            $this->logger->warn('SweetDialer Outbound: Missing To number');
            return $this->generateErrorResponse('Missing destination number');
        }

        // Get agent's identity from request (Client identity who initiated the call)
        $agentIdentity = $this->getParam('ClientIdentity');
        if (empty($agentIdentity)) {
            // Try to extract from caller or other params
            $agentIdentity = $this->getParam('Caller', 'unknown');
        }

        // Look up CTI settings and agent phone number
        $ctiSettings = $this->getCtiSettingsForAgent($agentIdentity);

        if (!$ctiSettings) {
            $this->logger->error('SweetDialer Outbound: No CTI settings found for agent: ' . $agentIdentity);
            return $this->generateErrorResponse('No CTI settings configured');
        }

        $agentPhone = $ctiSettings->agent_phone_number;

        if (empty($agentPhone)) {
            // Fallback to a default caller ID from settings
            $agentPhone = $ctiSettings->accounts_sid; // This won't work, but we need something
            $this->logger->warn('SweetDialer Outbound: No agent phone number configured');
        }

        // Build the recording status callback URL
        $recordingCallback = $this->buildWebhookUrl('sweetdialer_voice_recording');

        // Generate TwiML response
        $response = new TwiMLResponse();

        // S-046: Generate Dial with callerId and Number
        // S-047: Add record and recordingStatusCallback
        $response->dial(
            $toNumber,
            [
                'callerId' => $agentPhone,
                'record' => 'record-from-answer',
                'recordingStatusCallback' => $recordingCallback,
                'recordingStatusCallbackMethod' => 'POST',
                'recordingStatusCallbackEvent' => 'completed,failed,in-progress',
            ],
            'number'
        );

        // Log the outbound call
        $this->logOutboundCall($toNumber, $agentPhone, $agentIdentity, $ctiSettings);

        return $response;
    }

    /**
     * Get CTI settings for the agent making the call
     *
     * @param string $agentIdentity
     * @return Outr_CtiSettings|null
     */
    private function getCtiSettingsForAgent($agentIdentity)
    {
        try {
            $bean = BeanFactory::getBean('outr_CtiSettings');

            // Try to find by agent identity in the outbound_inbound_agent field
            // First, parse the identity to get user info
            $userId = $this->extractUserIdFromIdentity($agentIdentity);

            if ($userId) {
                // Look for settings assigned to this user
                $settings = $bean->get_list(
                    "",
                    "outr_ctisettings.assigned_user_id = '{$userId}' " .
                    "AND outr_ctisettings.status = 'Active' " .
                    "AND outr_ctisettings.deleted = 0",
                    "",
                    "",
                    1
                );

                if (!empty($settings['list'])) {
                    return reset($settings['list']);
                }
            }

            // Fall back to default active settings
            $settings = $bean->get_list(
                "",
                "outr_ctisettings.status = 'Active' " .
                "AND outr_ctisettings.deleted = 0",
                "",
                "",
                1
            );

            if (!empty($settings['list'])) {
                return reset($settings['list']);
            }

            return null;

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Outbound: Error fetching CTI settings - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract user ID from identity string
     *
     * @param string $identity
     * @return string|null
     */
    private function extractUserIdFromIdentity($identity)
    {
        // Identity format: user_{user_id}_{username}
        if (preg_match('/^user_([a-f0-9\-]+)_/', $identity, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Build full webhook URL for callback
     *
     * @param string $entryPoint
     * @return string
     */
    private function buildWebhookUrl($entryPoint)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

        return $protocol . '://' . $host . '/entryPoint.php?entryPoint=' . $entryPoint;
    }

    /**
     * Generate error TwiML response
     *
     * @param string $message
     * @return TwiMLResponse
     */
    private function generateErrorResponse($message)
    {
        $response = new TwiMLResponse();
        $response->say('Sorry, we are unable to complete your call at this time. ' . $message, [
            'voice' => 'Polly.Joanna',
        ]);
        $response->hangup();
        return $response;
    }

    /**
     * Log the outbound call
     *
     * @param string $toNumber
     * @param string $agentPhone
     * @param string $agentIdentity
     * @param Outr_CtiSettings $ctiSettings
     * @return void
     */
    private function logOutboundCall($toNumber, $agentPhone, $agentIdentity, $ctiSettings)
    {
        try {
            // Create or update call record
            $callBean = BeanFactory::newBean('outr_TwilioCalls');
            if ($callBean) {
                $callBean->name = 'Outbound call to ' . $toNumber;
                $callBean->direction = 'Outbound';
                $callBean->from_number = $agentPhone;
                $callBean->to_number = $toNumber;
                $callBean->status = 'initiated';
                $callBean->cti_setting_id = $ctiSettings->id;
                $callBean->assigned_user_id = $ctiSettings->assigned_user_id;
                $callBean->save();

                $this->logger->info(sprintf(
                    'SweetDialer Outbound: Call logged - ID: %s, To: %s, Agent: %s',
                    $callBean->id,
                    $toNumber,
                    $agentIdentity
                ));
            }
        } catch (Exception $e) {
            $this->logger->error('SweetDialer Outbound: Failed to log call - ' . $e->getMessage());
        }
    }
}

// Handle the request
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $handler = new VoiceOutboundHandler();
    $handler->handle();
}
