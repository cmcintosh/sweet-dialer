<?php
/**
 * voiceStatus.php
 *
 * Sweet-Dialer Voice Status Webhook Handler
 *
 * Handles call status callbacks from Twilio for tracking call lifecycle.
 *
 * URL: /entryPoint.php?entryPoint=sweetdialer_voice_status
 * Mapped to: /twilio/voice/status
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
 * VoiceStatusHandler
 *
 * Handles call status callbacks and updates call records
 */
class VoiceStatusHandler extends WebhookHandler
{
    /** @var array Valid call statuses */
    private $validStatuses = [
        'queued',
        'initiated',
        'ringing',
        'in-progress',
        'busy',
        'failed',
        'no-answer',
        'canceled',
        'completed',
    ];

    /**
     * Get the endpoint name
     *
     * @return string
     */
    protected function getEndpointName()
    {
        return 'status';
    }

    /**
     * Process the status callback
     *
     * Updates call records with status changes
     *
     * @return TwiMLResponse
     * @throws Exception
     */
    protected function processRequest()
    {
        // Get status data from Twilio
        $callSid = $this->getParam('CallSid');
        $parentCallSid = $this->getParam('ParentCallSid');
        $callStatus = $this->getParam('CallStatus');
        $callDuration = $this->getParam('CallDuration');
        $fromNumber = $this->getParam('From');
        $toNumber = $this->getParam('To');
        $direction = $this->getParam('Direction');
        $timestamp = $this->getParam('Timestamp');
        $callbackSource = $this->getParam('CallbackSource');
        $sequenceNumber = $this->getParam('SequenceNumber');

        // Call quality metrics (if available)
        $fromCountry = $this->getParam('FromCountry');
        $toCountry = $this->getParam('ToCountry');
        $price = $this->getParam('Price');
        $priceUnit = $this->getParam('PriceUnit');

        // Answered by information
        $answeredBy = $this->getParam('AnsweredBy'); // human, machine, unknown, fax

        if (empty($callSid) || empty($callStatus)) {
            $this->logger->warn('SweetDialer Status: Missing CallSid or CallStatus');
            return $this->generateAckResponse();
        }

        if (!in_array($callStatus, $this->validStatuses)) {
            $this->logger->warn('SweetDialer Status: Unknown call status - ' . $callStatus);
        }

        $this->logger->info(sprintf(
            'SweetDialer Status: CallSid: %s, Status: %s, From: %s, To: %s, Duration: %s',
            $callSid,
            $callStatus,
            $fromNumber,
            $toNumber,
            $callDuration ?? 'N/A'
        ));

        try {
            // Find and update the call record
            $callRecord = $this->findCallBySid($callSid);

            if ($callRecord) {
                $this->updateCallStatus($callRecord, [
                    'status' => $callStatus,
                    'duration' => $callDuration,
                    'answered_by' => $answeredBy,
                    'from_country' => $fromCountry,
                    'to_country' => $toCountry,
                    'price' => $price,
                    'price_unit' => $priceUnit,
                    'sequence_number' => $sequenceNumber,
                ]);
            } else {
                // This might be a child call (e.g., from a Dial)
                if (!empty($parentCallSid)) {
                    $this->handleChildCallStatus($parentCallSid, $callSid, $callStatus, $callDuration);
                } else {
                    $this->logger->warn('SweetDialer Status: Call not found - ' . $callSid);
                }
            }

            // Trigger status-specific actions
            $this->handleStatusSpecificActions($callSid, $callStatus, $callDuration, $fromNumber, $toNumber);

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Status: Error processing status - ' . $e->getMessage());
            $this->logToErrorTable('STATUS_PROCESS_ERROR', $e->getMessage(), [
                'call_sid' => $callSid,
                'status' => $callStatus,
            ]);
        }

        return $this->generateAckResponse();
    }

    /**
     * Find call record by CallSid
     *
     * @param string $callSid
     * @return SugarBean|null
     */
    private function findCallBySid($callSid)
    {
        try {
            $bean = BeanFactory::getBean('outr_TwilioCalls');

            $calls = $bean->get_list(
                "",
                "outr_twiliocalls.call_sid = '{$callSid}' AND outr_twiliocalls.deleted = 0",
                "",
                "",
                1
            );

            if (!empty($calls['list'])) {
                return reset($calls['list']);
            }

            return null;

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Status: Error finding call - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update call record with status info
     *
     * @param SugarBean $callRecord
     * @param array $statusData
     * @return void
     */
    private function updateCallStatus($callRecord, array $statusData)
    {
        try {
            $callRecord->status = $statusData['status'];

            // Only update duration if provided (usually only on completed calls)
            if (!empty($statusData['duration'])) {
                $callRecord->duration = $statusData['duration'];
            }

            // Store additional metadata
            if (!empty($statusData['answered_by'])) {
                $callRecord->answered_by = $statusData['answered_by'];
            }

            if (!empty($statusData['price'])) {
                $callRecord->price = $statusData['price'];
                $callRecord->price_unit = $statusData['price_unit'] ?? 'USD';
            }

            if (!empty($statusData['from_country'])) {
                $callRecord->from_country = $statusData['from_country'];
            }

            if (!empty($statusData['to_country'])) {
                $callRecord->to_country = $statusData['to_country'];
            }

            // Set date answered for in-progress/completed calls
            if (in_array($statusData['status'], ['in-progress', 'completed']) && empty($callRecord->date_answered)) {
                $callRecord->date_answered = gmdate('Y-m-d H:i:s');
            }

            // Set date ended for terminal statuses
            if (in_array($statusData['status'], ['completed', 'failed', 'busy', 'no-answer', 'canceled'])) {
                $callRecord->date_ended = gmdate('Y-m-d H:i:s');
            }

            $callRecord->save();

            $this->logger->info(sprintf(
                'SweetDialer Status: Updated call ID %s - Status: %s',
                $callRecord->id,
                $statusData['status']
            ));

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Status: Error updating call - ' . $e->getMessage());
        }
    }

    /**
     * Handle child call status updates
     *
     * @param string $parentCallSid
     * @param string $childCallSid
     * @param string $status
     * @param string $duration
     * @return void
     */
    private function handleChildCallStatus($parentCallSid, $childCallSid, $status, $duration)
    {
        try {
            $parentCall = $this->findCallBySid($parentCallSid);

            if ($parentCall) {
                // Log the child call information
                $this->logger->info(sprintf(
                    'SweetDialer Status: Child call %s for parent %s - Status: %s',
                    $childCallSid,
                    $parentCallSid,
                    $status
                ));

                // Update parent call with child call status if needed
                if ($status === 'completed' && !empty($duration)) {
                    $parentCall->duration = $duration;
                    $parentCall->save();
                }
            }

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Status: Error handling child call - ' . $e->getMessage());
        }
    }

    /**
     * Handle status-specific actions
     *
     * @param string $callSid
     * @param string $status
     * @param string $duration
     * @param string $fromNumber
     * @param string $toNumber
     * @return void
     */
    private function handleStatusSpecificActions($callSid, $status, $duration, $fromNumber, $toNumber)
    {
        switch ($status) {
            case 'completed':
                $this->handleCallCompleted($callSid, $duration, $fromNumber, $toNumber);
                break;

            case 'busy':
                $this->handleCallBusy($callSid, $fromNumber, $toNumber);
                break;

            case 'failed':
                $errorMessage = $this->getParam('ErrorMessage');
                $this->handleCallFailed($callSid, $errorMessage, $fromNumber, $toNumber);
                break;

            case 'no-answer':
                $this->handleCallNoAnswer($callSid, $fromNumber, $toNumber);
                break;

            case 'in-progress':
                $this->handleCallConnected($callSid, $fromNumber, $toNumber);
                break;

            case 'ringing':
                $this->handleCallRinging($callSid, $fromNumber, $toNumber);
                break;
        }
    }

    /**
     * Handle call completed
     *
     * @param string $callSid
     * @param string $duration
     * @param string $fromNumber
     * @param string $toNumber
     * @return void
     */
    private function handleCallCompleted($callSid, $duration, $fromNumber, $toNumber)
    {
        $this->logger->info(sprintf(
            'SweetDialer Status: Call completed - Sid: %s, Duration: %ss',
            $callSid,
            $duration ?? 0
        ));

        // Could trigger workflows here:
        // - Log call analytics
        // - Update contact's last call date
        // - Create follow-up task if needed
    }

    /**
     * Handle call busy
     *
     * @param string $callSid
     * @param string $fromNumber
     * @param string $toNumber
     * @return void
     */
    private function handleCallBusy($callSid, $fromNumber, $toNumber)
    {
        $this->logger->info(sprintf(
            'SweetDialer Status: Call busy - Sid: %s, From: %s, To: %s',
            $callSid,
            $fromNumber,
            $toNumber
        ));

        // Could trigger:
        // - Retry logic
        // - Schedule callback
        // - Alternative routing
    }

    /**
     * Handle call failed
     *
     * @param string $callSid
     * @param string $errorMessage
     * @param string $fromNumber
     * @param string $toNumber
     * @return void
     */
    private function handleCallFailed($callSid, $errorMessage, $fromNumber, $toNumber)
    {
        $this->logger->error(sprintf(
            'SweetDialer Status: Call failed - Sid: %s, Error: %s',
            $callSid,
            $errorMessage
        ));

        $this->logToErrorTable('CALL_FAILED', $errorMessage, [
            'call_sid' => $callSid,
            'from_number' => $fromNumber,
            'to_number' => $toNumber,
        ]);
    }

    /**
     * Handle call no answer
     *
     * @param string $callSid
     * @param string $fromNumber
     * @param string $toNumber
     * @return void
     */
    private function handleCallNoAnswer($callSid, $fromNumber, $toNumber)
    {
        $this->logger->info(sprintf(
            'SweetDialer Status: No answer - Sid: %s, From: %s, To: %s',
            $callSid,
            $fromNumber,
            $toNumber
        ));

        // Trigger voicemail if not already done
    }

    /**
     * Handle call connected
     *
     * @param string $callSid
     * @param string $fromNumber
     * @param string $toNumber
     * @return void
     */
    private function handleCallConnected($callSid, $fromNumber, $toNumber)
    {
        $this->logger->info(sprintf(
            'SweetDialer Status: Call connected - Sid: %s',
            $callSid
        ));
    }

    /**
     * Handle call ringing
     *
     * @param string $callSid
     * @param string $fromNumber
     * @param string $toNumber
     * @return void
     */
    private function handleCallRinging($callSid, $fromNumber, $toNumber)
    {
        $this->logger->debug(sprintf(
            'SweetDialer Status: Call ringing - Sid: %s',
            $callSid
        ));
    }

    /**
     * Generate acknowledgement response
     *
     * @return TwiMLResponse
     */
    private function generateAckResponse()
    {
        return new TwiMLResponse();
    }
}

// Handle the request
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $handler = new VoiceStatusHandler();
    $handler->handle();
}
