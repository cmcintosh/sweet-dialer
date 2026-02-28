<?php
/**
 * voiceInbound.php
 *
 * Sweet-Dialer Voice Inbound Webhook Handler
 *
 * Handles inbound calls from PSTN to Twilio Client agents.
 * Looks up the To number in CTI settings and routes to agent.
 *
 * URL: /entryPoint.php?entryPoint=sweetdialer_voice_inbound
 * Mapped to: /twilio/voice/inbound
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
 * VoiceInboundHandler
 *
 * S-048: Handles inbound calls from PSTN
 * S-049: Adds voicemail fallback on timeout
 */
class VoiceInboundHandler extends WebhookHandler
{
    /**
     * Get the endpoint name
     *
     * @return string
     */
    protected function getEndpointName()
    {
        return 'inbound';
    }

    /**
     * Process the inbound call request
     *
     * Look up To phone number in outr_twilio_settings,
     * find assigned agent(s), and generate TwiML to dial client.
     *
     * @return TwiMLResponse
     * @throws Exception
     */
    protected function processRequest()
    {
        // Get the called number (To) and caller number (From)
        $toNumber = $this->getParam('To');
        $fromNumber = $this->getParam('From');
        $callSid = $this->getParam('CallSid');

        if (empty($toNumber)) {
            $this->logger->warn('SweetDialer Inbound: Missing To number');
            return $this->generateErrorResponse('Missing called number');
        }

        // Look up CTI settings for the called number
        $ctiSettings = $this->findCtiSettingsByPhoneNumber($toNumber);

        if (!$ctiSettings) {
            $this->logger->warn('SweetDialer Inbound: No CTI settings found for number: ' . $toNumber);
            return $this->generateRejectionResponse('No agent configured for this number');
        }

        // Get the assigned agent identity
        $agentIdentity = $this->getAgentIdentity($ctiSettings);

        if (empty($agentIdentity)) {
            $this->logger->warn('SweetDialer Inbound: No agent identity for CTI setting: ' . $ctiSettings->id);
            return $this->generateVoicemailResponse($ctiSettings, $fromNumber);
        }

        // Get timeout from settings
        $timeout = $ctiSettings->ring_timeout ?: 30;
        if (empty($timeout) || $timeout < 10 || $timeout > 600) {
            $timeout = 30; // Default 30 seconds
        }

        // Build voicemail redirect URL for timeout action (S-049)
        $voicemailUrl = $this->buildWebhookUrl('sweetdialer_voice_voicemail');

        // Log the inbound call
        $this->logInboundCall($toNumber, $fromNumber, $agentIdentity, $ctiSettings, $callSid);

        // Generate TwiML response
        $response = new TwiMLResponse();

        // S-048: Generate Dial with Client
        // S-049: Add action attribute for voicemail fallback
        $response->dial(
            $agentIdentity,
            [
                'timeout' => $timeout,
                'action' => $voicemailUrl,
                'method' => 'POST',
            ],
            'client'
        );

        return $response;
    }

    /**
     * Find CTI settings by phone number
     *
     * @param string $phoneNumber
     * @return Outr_CtiSettings|null
     */
    private function findCtiSettingsByPhoneNumber($phoneNumber)
    {
        try {
            $bean = BeanFactory::getBean('outr_CtiSettings');

            // Clean the phone number for comparison
            $cleanNumber = $this->cleanPhoneNumber($phoneNumber);

            // Look up by phone number
            $settings = $bean->get_list(
                "",
                "outr_ctisettings.agent_phone_number LIKE '%{$cleanNumber}' " .
                "AND outr_ctisettings.status = 'Active' " .
                "AND outr_ctisettings.deleted = 0",
                "",
                "",
                1
            );

            if (!empty($settings['list'])) {
                return reset($settings['list']);
            }

            // Try matching without country code
            if (strlen($cleanNumber) > 10) {
                $shortNumber = substr($cleanNumber, -10);
                $settings = $bean->get_list(
                    "",
                    "REPLACE(REPLACE(REPLACE(outr_ctisettings.agent_phone_number, '+', ''), '-', ''), ' ', '') LIKE '%{$shortNumber}' " .
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

            return null;

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Inbound: Error finding CTI settings - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Clean phone number for comparison
     *
     * @param string $number
     * @return string
     */
    private function cleanPhoneNumber($number)
    {
        return preg_replace('/[^0-9]/', '', $number);
    }

    /**
     * Get agent identity from CTI settings
     *
     * @param Outr_CtiSettings $ctiSettings
     * @return string|null
     */
    private function getAgentIdentity($ctiSettings)
    {
        try {
            // If there's an assigned agent, build their identity
            if (!empty($ctiSettings->outbound_inbound_agent_id)) {
                $agent = BeanFactory::getBean('Users', $ctiSettings->outbound_inbound_agent_id);

                if ($agent && !empty($agent->user_name)) {
                    // Build identity in format: user_{user_id}_{username}
                    return sprintf(
                        'user_%s_%s',
                        substr($agent->id, 0, 8),
                        $this->sanitizeIdentity($agent->user_name)
                    );
                }
            }

            // Check if we have a default agent identity stored
            if (!empty($ctiSettings->default_agent_identity)) {
                return $ctiSettings->default_agent_identity;
            }

            return null;

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Inbound: Error getting agent identity - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sanitize string for use as identity
     *
     * @param string $str
     * @return string
     */
    private function sanitizeIdentity($str)
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $str);
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
        $response->say('We are experiencing technical difficulties. Please try again later.', [
            'voice' => 'Polly.Joanna',
        ]);
        $response->hangup();
        return $response;
    }

    /**
     * Generate rejection TwiML response
     *
     * @param string $message
     * @return TwiMLResponse
     */
    private function generateRejectionResponse($message)
    {
        $response = new TwiMLResponse();

        // Play a polite message before hanging up
        $response->say('The number you have dialed is not in service. Please check the number and try again.', [
            'voice' => 'Polly.Joanna',
        ]);

        $response->reject('rejected');
        return $response;
    }

    /**
     * Generate voicemail TwiML when no agent is available
     *
     * @param Outr_CtiSettings $ctiSettings
     * @param string $fromNumber
     * @return TwiMLResponse
     */
    private function generateVoicemailResponse($ctiSettings, $fromNumber)
    {
        // Redirect directly to voicemail
        $voicemailUrl = $this->buildWebhookUrl('sweetdialer_voice_voicemail');

        $response = new TwiMLResponse();
        $response->redirect($voicemailUrl, ['method' => 'POST']);

        return $response;
    }

    /**
     * Log the inbound call
     *
     * @param string $toNumber
     * @param string $fromNumber
     * @param string $agentIdentity
     * @param Outr_CtiSettings $ctiSettings
     * @param string $callSid
     * @return void
     */
    private function logInboundCall($toNumber, $fromNumber, $agentIdentity, $ctiSettings, $callSid)
    {
        try {
            $callBean = BeanFactory::newBean('outr_TwilioCalls');
            if ($callBean) {
                $callBean->name = 'Inbound call from ' . $fromNumber;
                $callBean->direction = 'Inbound';
                $callBean->from_number = $fromNumber;
                $callBean->to_number = $toNumber;
                $callBean->status = 'ringing';
                $callBean->call_sid = $callSid;
                $callBean->cti_setting_id = $ctiSettings->id;
                $callBean->assigned_user_id = $ctiSettings->outbound_inbound_agent_id;
                $callBean->save();

                $this->logger->info(sprintf(
                    'SweetDialer Inbound: Call logged - ID: %s, From: %s, Agent: %s',
                    $callBean->id,
                    $fromNumber,
                    $agentIdentity
                ));
            }
        } catch (Exception $e) {
            $this->logger->error('SweetDialer Inbound: Failed to log call - ' . $e->getMessage());
        }
    }
}

// Handle the request
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $handler = new VoiceInboundHandler();
    $handler->handle();
}
