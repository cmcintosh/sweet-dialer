<?php
/**
 * voiceRecording.php
 *
 * Sweet-Dialer Voice Recording Webhook Handler
 *
 * Handles recording status callbacks for call recordings.
 * Saves recording URLs to call records.
 *
 * URL: /entryPoint.php?entryPoint=sweetdialer_voice_recording
 * Mapped to: /twilio/voice/recording
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
 * VoiceRecordingHandler
 *
 * S-051: Saves recording URLs to call records
 */
class VoiceRecordingHandler extends WebhookHandler
{
    /**
     * Get the endpoint name
     *
     * @return string
     */
    protected function getEndpointName()
    {
        return 'recording';
    }

    /**
     * Process the recording status callback
     *
     * S-051: Link to CTI setting and caller's CRM record,
     * include URL, duration, caller number
     *
     * @return TwiMLResponse
     * @throws Exception
     */
    protected function processRequest()
    {
        // Get recording data from Twilio callback
        $recordingSid = $this->getParam('RecordingSid');
        $recordingUrl = $this->getParam('RecordingUrl');
        $recordingDuration = $this->getParam('RecordingDuration');
        $recordingStatus = $this->getParam('RecordingStatus');
        $recordingChannels = $this->getParam('RecordingChannels');
        $recordingStartTime = $this->getParam('RecordingStartTime');
        $recordingSource = $this->getParam('RecordingSource');

        // Get associated call data
        $callSid = $this->getParam('CallSid');
        $fromNumber = $this->getParam('From');
        $toNumber = $this->getParam('To');
        $accountSid = $this->getParam('AccountSid');

        if (empty($recordingSid)) {
            $this->logger->warn('SweetDialer Recording: Missing RecordingSid');
            return $this->generateAckResponse();
        }

        // Recording URL needs auth token to access - we'll save the base URL
        // The actual audio can be fetched using the REST API
        $recordingUrl = $recordingUrl . '.mp3'; // Add format extension for direct access

        $this->logger->info(sprintf(
            'SweetDialer Recording: Received callback - Sid: %s, Status: %s, Duration: %ss, Call: %s',
            $recordingSid,
            $recordingStatus,
            $recordingDuration,
            $callSid
        ));

        try {
            // Find the associated call record
            $callRecord = $this->findCallBySid($callSid);

            if ($callRecord) {
                // Update the call record with recording info
                $this->updateCallWithRecording($callRecord, [
                    'recording_sid' => $recordingSid,
                    'recording_url' => $recordingUrl,
                    'recording_duration' => $recordingDuration,
                    'recording_status' => $recordingStatus,
                    'recording_channels' => $recordingChannels,
                    'recording_start_time' => $recordingStartTime,
                ]);
            } else {
                // Create a standalone recording record if no call found
                $this->createStandaloneRecording([
                    'recording_sid' => $recordingSid,
                    'recording_url' => $recordingUrl,
                    'recording_duration' => $recordingDuration,
                    'recording_status' => $recordingStatus,
                    'call_sid' => $callSid,
                    'from_number' => $fromNumber,
                    'to_number' => $toNumber,
                ]);
            }

            // Process recording status
            switch ($recordingStatus) {
                case 'completed':
                    $this->handleRecordingCompleted($recordingSid, $recordingUrl, $recordingDuration);
                    break;

                case 'failed':
                    $recErrorCode = $this->getParam('RecordingErrorCode');
                    $this->handleRecordingFailed($recordingSid, $recErrorCode);
                    break;

                case 'in-progress':
                    $this->handleRecordingInProgress($recordingSid);
                    break;

                case 'absent':
                    $this->handleRecordingAbsent($recordingSid);
                    break;
            }

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Recording: Error processing recording - ' . $e->getMessage());
            $this->logToErrorTable('RECORDING_PROCESS_ERROR', $e->getMessage(), [
                'recording_sid' => $recordingSid,
                'call_sid' => $callSid,
            ]);
        }

        // Return empty TwiML - this is a callback, not an active call control
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

            // Try searching by recording sid in case this is a follow-up
            $calls = $bean->get_list(
                "",
                "outr_twiliocalls.recording_sid = '{$callSid}' AND outr_twiliocalls.deleted = 0",
                "",
                "",
                1
            );

            if (!empty($calls['list'])) {
                return reset($calls['list']);
            }

            return null;

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Recording: Error finding call - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update call record with recording info
     *
     * @param SugarBean $callRecord
     * @param array $recordingData
     * @return void
     */
    private function updateCallWithRecording($callRecord, array $recordingData)
    {
        try {
            $callRecord->recording_sid = $recordingData['recording_sid'];
            $callRecord->recording_url = $recordingData['recording_url'];
            $callRecord->recording_duration = $recordingData['recording_duration'];
            $callRecord->recording_status = $recordingData['recording_status'];

            // Only update if recording is completed
            if ($recordingData['recording_status'] === 'completed') {
                $callRecord->has_recording = 1;
            }

            $callRecord->save();

            $this->logger->info(sprintf(
                'SweetDialer Recording: Updated call ID %s with recording %s',
                $callRecord->id,
                $recordingData['recording_sid']
            ));

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Recording: Error updating call - ' . $e->getMessage());
        }
    }

    /**
     * Create standalone recording record
     *
     * Used when no associated call record exists
     *
     * @param array $recordingData
     * @return void
     */
    private function createStandaloneRecording(array $recordingData)
    {
        try {
            // Use the voicemails table for standalone recordings too
            $voicemailBean = BeanFactory::newBean('outr_TwilioVoicemail');

            if ($voicemailBean) {
                $voicemailBean->name = 'Call recording ' . $recordingData['recording_sid'];
                $voicemailBean->from_number = $recordingData['from_number'];
                $voicemailBean->to_number = $recordingData['to_number'];
                $voicemailBean->call_sid = $recordingData['call_sid'];
                $voicemailBean->recording_sid = $recordingData['recording_sid'];
                $voicemailBean->recording_url = $recordingData['recording_url'];
                $voicemailBean->duration = $recordingData['recording_duration'];
                $voicemailBean->status = $recordingData['recording_status'];
                $voicemailBean->type = 'call_recording';

                // Try to link to parent record
                $parentRecord = $this->findParentRecord($recordingData['from_number']);
                if ($parentRecord) {
                    $voicemailBean->parent_type = $parentRecord['type'];
                    $voicemailBean->parent_id = $parentRecord['id'];
                }

                $voicemailBean->save();

                $this->logger->info(sprintf(
                    'SweetDialer Recording: Created standalone recording record - ID: %s',
                    $voicemailBean->id
                ));
            }

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Recording: Error creating recording record - ' . $e->getMessage());
        }
    }

    /**
     * Handle recording completed
     *
     * @param string $recordingSid
     * @param string $recordingUrl
     * @param string $duration
     * @return void
     */
    private function handleRecordingCompleted($recordingSid, $recordingUrl, $duration)
    {
        $this->logger->info(sprintf(
            'SweetDialer Recording: Completed - Sid: %s, URL: %s, Duration: %ss',
            $recordingSid,
            $recordingUrl,
            $duration
        ));

        // Here you could trigger additional processing:
        // - Transcription (if not using Twilio's native transcription)
        // - Archive to S3/Cloud Storage
        // - Send notifications
        // - Update analytics
    }

    /**
     * Handle recording failed
     *
     * @param string $recordingSid
     * @param string $errorCode
     * @return void
     */
    private function handleRecordingFailed($recordingSid, $errorCode)
    {
        $this->logger->error(sprintf(
            'SweetDialer Recording: Failed - Sid: %s, Error: %s',
            $recordingSid,
            $errorCode
        ));

        $this->logToErrorTable('RECORDING_FAILED', 'Recording failed with error: ' . $errorCode, [
            'recording_sid' => $recordingSid,
            'error_code' => $errorCode,
        ]);
    }

    /**
     * Handle recording in progress
     *
     * @param string $recordingSid
     * @return void
     */
    private function handleRecordingInProgress($recordingSid)
    {
        $this->logger->debug(sprintf('SweetDialer Recording: In progress - Sid: %s', $recordingSid));
    }

    /**
     * Handle recording absent
     *
     * @param string $recordingSid
     * @return void
     */
    private function handleRecordingAbsent($recordingSid)
    {
        $this->logger->warn(sprintf('SweetDialer Recording: Absent - Sid: %s', $recordingSid));
    }

    /**
     * Find parent record by phone number
     *
     * @param string $phoneNumber
     * @return array|null
     */
    private function findParentRecord($phoneNumber)
    {
        try {
            $cleanNumber = $this->cleanPhoneNumber($phoneNumber);

            // Search Contacts
            $contact = $this->searchModuleByPhone('Contacts', $cleanNumber);
            if ($contact) {
                return ['type' => 'Contacts', 'id' => $contact->id];
            }

            // Search Leads
            $lead = $this->searchModuleByPhone('Leads', $cleanNumber);
            if ($lead) {
                return ['type' => 'Leads', 'id' => $lead->id];
            }

            // Search Accounts
            $account = $this->searchModuleByPhone('Accounts', $cleanNumber);
            if ($account) {
                return ['type' => 'Accounts', 'id' => $account->id];
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Search a module by phone number
     *
     * @param string $module
     * @param string $cleanNumber
     * @return SugarBean|null
     */
    private function searchModuleByPhone($module, $cleanNumber)
    {
        try {
            $bean = BeanFactory::getBean($module);

            $phoneFields = ['phone_mobile', 'phone_work', 'phone_home', 'phone_other', 'phone_fax', 'phone_office'];
            $conditions = [];

            foreach ($phoneFields as $field) {
                if (isset($bean->field_defs[$field])) {
                    $conditions[] = "{$module}.{$field} LIKE '%{$cleanNumber}%'";
                }
            }

            if (empty($conditions)) {
                return null;
            }

            $where = '(' . implode(' OR ', $conditions) . ') AND ' .
                     "{$module}.deleted = 0";

            $results = $bean->get_list('', $where, '', '', 1);

            if (!empty($results['list'])) {
                return reset($results['list']);
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
     * Generate acknowledgement response
     *
     * @return TwiMLResponse
     */
    private function generateAckResponse()
    {
        // Just return empty response for callbacks
        return new TwiMLResponse();
    }
}

// Handle the request
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $handler = new VoiceRecordingHandler();
    $handler->handle();
}
