<?php
/**
 * voiceTransfer.php
 *
 * Sweet-Dialer Voice Transfer Webhook Handler
 *
 * Handles call transfers between agents or to external numbers.
 *
 * URL: /entryPoint.php?entryPoint=sweetdialer_voice_transfer
 * Mapped to: /twilio/voice/transfer
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
 * VoiceTransferHandler
 *
 * Handles call transfer requests
 */
class VoiceTransferHandler extends WebhookHandler
{
    /**
     * Get the endpoint name
     *
     * @return string
     */
    protected function getEndpointName()
    {
        return 'transfer';
    }

    /**
     * Process the transfer request
     *
     * Handles warm transfers, cold transfers, and external transfers
     *
     * @return TwiMLResponse
     * @throws Exception
     */
    protected function processRequest()
    {
        // Get transfer parameters
        $callSid = $this->getParam('CallSid');
        $transferType = $this->getParam('TransferType', 'client'); // client, number, conference
        $transferTo = $this->getParam('TransferTo'); // Client identity or phone number
        $callerId = $this->getParam('CallerId');
        $currentClient = $this->getParam('CurrentClient');
        $toNumber = $this->getParam('To');

        if (empty($transferTo)) {
            $this->logger->warn('SweetDialer Transfer: Missing TransferTo parameter');
            return $this->generateErrorResponse('Missing transfer destination');
        }

        if (empty($callSid)) {
            $this->logger->warn('SweetDialer Transfer: Missing CallSid');
            return $this->generateErrorResponse('Missing call identifier');
        }

        $this->logger->info(sprintf(
            'SweetDialer Transfer: Request received - CallSid: %s, Type: %s, To: %s',
            $callSid,
            $transferType,
            $transferTo
        ));

        try {
            // Look up CTI settings for caller ID
            $ctiSettings = $this->findCtiSettingsByPhoneNumber($toNumber);
            $callerId = $callerId ?: ($ctiSettings ? $ctiSettings->agent_phone_number : '');

            // Log the transfer attempt
            $this->logTransferInitiated($callSid, $transferType, $transferTo, $currentClient);

            // Generate transfer TwiML based on type
            return $this->generateTransferTwiML($transferType, $transferTo, $callerId, $callSid);

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Transfer: Error processing transfer - ' . $e->getMessage());
            return $this->generateErrorResponse('Transfer failed');
        }
    }

    /**
     * Generate transfer TwiML based on type
     *
     * @param string $transferType
     * @param string $transferTo
     * @param string $callerId
     * @param string $callSid
     * @return TwiMLResponse
     */
    private function generateTransferTwiML($transferType, $transferTo, $callerId, $callSid)
    {
        $response = new TwiMLResponse();

        switch ($transferType) {
            case 'client':
                // Transfer to another Twilio Client
                $response->say('Transferring your call...', [
                    'voice' => 'Polly.Joanna',
                ]);
                $response->dial(
                    $transferTo,
                    [
                        'action' => $this->buildWebhookUrl('sweetdialer_voice_status'),
                        'method' => 'POST',
                    ],
                    'client'
                );
                break;

            case 'number':
                // Transfer to external phone number
                $response->say('Transferring your call...', [
                    'voice' => 'Polly.Joanna',
                ]);
                $response->dial(
                    $transferTo,
                    [
                        'callerId' => $callerId,
                        'action' => $this->buildWebhookUrl('sweetdialer_voice_status'),
                        'method' => 'POST',
                    ],
                    'number'
                );
                break;

            case 'conference':
                // Move to conference room
                $response->say('Connecting you to a conference room...', [
                    'voice' => 'Polly.Joanna',
                ]);
                // Conference would be implemented here
                $response->say('Conference rooms are not yet available.', [
                    'voice' => 'Polly.Joanna',
                ]);
                break;

            case 'warm':
                // Warm transfer (announce caller first)
                $response->say('Please hold while we connect you...', [
                    'voice' => 'Polly.Joanna',
                ]);
                $response->pause(1);
                // Dial the transfer target
                $response->dial(
                    $transferTo,
                    [
                        'action' => $this->buildWebhookUrl('sweetdialer_voice_status'),
                        'method' => 'POST',
                    ],
                    'client'
                );
                break;

            default:
                $response->say('Invalid transfer type', [
                    'voice' => 'Polly.Joanna',
                ]);
        }

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
        if (empty($phoneNumber)) {
            return null;
        }

        try {
            $bean = BeanFactory::getBean('outr_CtiSettings');
            $cleanNumber = $this->cleanPhoneNumber($phoneNumber);

            $settings = $bean->get_list(
                "",
                "REPLACE(REPLACE(REPLACE(outr_ctisettings.agent_phone_number, '+', ''), '-', ''), ' ', '') LIKE '%{$cleanNumber}' " .
                "AND outr_ctisettings.status = 'Active' " .
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
     * Build full webhook URL
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
     * Log transfer initiation
     *
     * @param string $callSid
     * @param string $transferType
     * @param string $transferTo
     * @param string $currentClient
     * @return void
     */
    private function logTransferInitiated($callSid, $transferType, $transferTo, $currentClient)
    {
        try {
            // Find the call record
            $bean = BeanFactory::getBean('outr_TwilioCalls');
            $calls = $bean->get_list(
                "",
                "outr_twiliocalls.call_sid = '{$callSid}' AND outr_twiliocalls.deleted = 0",
                "",
                "",
                1
            );

            if (!empty($calls['list'])) {
                $call = reset($calls['list']);
                $call->transfer_initiated = 1;
                $call->transfer_type = $transferType;
                $call->transfer_to = $transferTo;
                $call->transfer_from = $currentClient;
                $call->transfer_date = gmdate('Y-m-d H:i:s');
                $call->save();
            }

        } catch (Exception $e) {
            $this->logger->warn('SweetDialer Transfer: Error logging transfer - ' . $e->getMessage());
        }
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
        $response->say('Unable to complete transfer. ' . $message, [
            'voice' => 'Polly.Joanna',
        ]);
        return $response;
    }
}

// Handle the request
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $handler = new VoiceTransferHandler();
    $handler->handle();
}
