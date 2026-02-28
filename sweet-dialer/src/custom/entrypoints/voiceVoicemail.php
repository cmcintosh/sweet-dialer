<?php
/**
 * voiceVoicemail.php
 *
 * Sweet-Dialer Voice Voicemail Webhook Handler
 *
 * Handles voicemail recording for unanswered calls.
 * Plays voicemail greeting and records caller's message.
 *
 * URL: /entryPoint.php?entryPoint=sweetdialer_voice_voicemail
 * Mapped to: /twilio/voice/voicemail
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
 * VoiceVoicemailHandler
 *
 * S-050: Handles voicemail recording
 * S-051: Saves voicemail recordings to database
 */
class VoiceVoicemailHandler extends WebhookHandler
{
    /** @var array Default voicemail settings */
    private $defaultSettings = [
        'voice' => 'Polly.Joanna',
        'max_length' => 300,      // 5 minutes max
        'finish_key' => '#',        // Press # to finish
        'timeout' => 5,            // Seconds of silence to end
        'play_beep' => true,
    ];

    /**
     * Get the endpoint name
     *
     * @return string
     */
    protected function getEndpointName()
    {
        return 'voicemail';
    }

    /**
     * Process the voicemail request
     *
     * S-050: Look up voicemail config, play audio or Say,
     * Record with maxLength and finishOnKey
     *
     * @return TwiMLResponse
     * @throws Exception
     */
    protected function processRequest()
    {
        // Get call details from request
        $fromNumber = $this->getParam('From');
        $toNumber = $this->getParam('To');
        $callSid = $this->getParam('CallSid');
        $recordingUrl = $this->getParam('RecordingUrl');
        $recordingDuration = $this->getParam('RecordingDuration');
        $recordingSid = $this->getParam('RecordingSid');

        // If we have a recording URL, this is the callback after recording is complete
        if (!empty($recordingUrl)) {
            return $this->handleRecordingComplete($fromNumber, $toNumber, $callSid, $recordingUrl, $recordingDuration, $recordingSid);
        }

        // This is the initial voicemail request
        return $this->handleInitialVoicemail($fromNumber, $toNumber, $callSid);
    }

    /**
     * Handle the initial voicemail request
     *
     * @param string $fromNumber
     * @param string $toNumber
     * @param string $callSid
     * @return TwiMLResponse
     */
    private function handleInitialVoicemail($fromNumber, $toNumber, $callSid)
    {
        // Look up CTI settings for the called number
        $ctiSettings = $this->findCtiSettingsByPhoneNumber($toNumber);

        // Get voicemail configuration
        $voicemailConfig = $this->getVoicemailConfig($ctiSettings);

        // Build recording callback URL
        $recordingCallback = $this->buildWebhookUrl('sweetdialer_voice_voicemail');

        // Generate TwiML response
        $response = new TwiMLResponse();

        // Play voicemail greeting - either audio file or text-to-speech
        if (!empty($voicemailConfig['audio_file'])) {
            // Play custom voicemail audio
            $response->play($voicemailConfig['audio_file']);
        } else {
            // Use text-to-speech
            $message = $voicemailConfig['message'] ?? 'Please leave a message after the beep.';
            $response->say($message, [
                'voice' => $voicemailConfig['voice'],
            ]);
        }

        // Add pause after greeting
        $response->pause(1);

        // Record the voicemail
        // S-050: <Record maxLength="{max}" finishOnKey="{key}">
        $response->record([
            'action' => $recordingCallback,
            'method' => 'POST',
            'maxLength' => $voicemailConfig['max_length'],
            'finishOnKey' => $voicemailConfig['finish_key'],
            'timeout' => $voicemailConfig['timeout'],
            'playBeep' => $voicemailConfig['play_beep'] ? 'true' : 'false',
            'transcribe' => 'false', // We can enable this later if needed
        ]);

        // If user hangs up or times out during recording, say goodbye
        $response->say('Goodbye', [
            'voice' => $voicemailConfig['voice'],
        ]);
        $response->hangup();

        // Log that call went to voicemail
        $this->logVoicemailInitiated($fromNumber, $toNumber, $callSid, $ctiSettings);

        return $response;
    }

    /**
     * Handle recording completion
     *
     * S-051: Save voicemail recording to database
     *
     * @param string $fromNumber
     * @param string $toNumber
     * @param string $callSid
     * @param string $recordingUrl
     * @param string $recordingDuration
     * @param string $recordingSid
     * @return TwiMLResponse
     */
    private function handleRecordingComplete($fromNumber, $toNumber, $callSid, $recordingUrl, $recordingDuration, $recordingSid)
    {
        // Look up CTI settings
        $ctiSettings = $this->findCtiSettingsByPhoneNumber($toNumber);

        try {
            // S-051: Save to outr_twilio_voicemail_recordings
            $voicemailBean = BeanFactory::newBean('outr_TwilioVoicemail');

            if ($voicemailBean) {
                // Find or create the related call record
                $callId = $this->findCallIdBySid($callSid);

                $voicemailBean->name = 'Voicemail from ' . $fromNumber;
                $voicemailBean->from_number = $fromNumber;
                $voicemailBean->to_number = $toNumber;
                $voicemailBean->call_sid = $callSid;
                $voicemailBean->recording_url = $recordingUrl;
                $voicemailBean->recording_sid = $recordingSid;
                $voicemailBean->duration = $recordingDuration;
                $voicemailBean->cti_setting_id = $ctiSettings ? $ctiSettings->id : null;
                $voicemailBean->call_id = $callId;
                $voicemailBean->status = 'new';

                // Try to link to caller's CRM record
                $parentRecord = $this->findParentRecord($fromNumber);
                if ($parentRecord) {
                    $voicemailBean->parent_type = $parentRecord['type'];
                    $voicemailBean->parent_id = $parentRecord['id'];
                }

                $voicemailBean->save();

                $this->logger->info(sprintf(
                    'SweetDialer Voicemail: Saved - ID: %s, From: %s, Duration: %ss, URL: %s',
                    $voicemailBean->id,
                    $fromNumber,
                    $recordingDuration,
                    $recordingUrl
                ));

                // Update the call record status
                if ($callId) {
                    $this->updateCallStatus($callId, 'voicemail', $voicemailBean->id);
                }
            }

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Voicemail: Failed to save recording - ' . $e->getMessage());
            $this->logToErrorTable('VOICEMAIL_SAVE_ERROR', $e->getMessage(), [
                'params' => $this->requestParams,
            ]);
        }

        // Return thank you TwiML
        $response = new TwiMLResponse();
        $response->say('Thank you for your message. Goodbye.', [
            'voice' => $this->defaultSettings['voice'],
        ]);
        $response->hangup();

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
            $this->logger->error('SweetDialer Voicemail: Error finding CTI settings - ' . $e->getMessage());
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
     * Get voicemail configuration from CTI settings or voicemail module
     *
     * @param Outr_CtiSettings|null $ctiSettings
     * @return array
     */
    private function getVoicemailConfig($ctiSettings)
    {
        $config = $this->defaultSettings;

        try {
            if ($ctiSettings && !empty($ctiSettings->twilio_voice_mail_id)) {
                // Load linked voicemail configuration
                $voicemailBean = BeanFactory::getBean('outr_TwilioVoicemail', $ctiSettings->twilio_voice_mail_id);

                if ($voicemailBean) {
                    // Use voicemail settings
                    if (!empty($voicemailBean->file)) {
                        $config['audio_file'] = $voicemailBean->file;
                    }

                    if (!empty($voicemailBean->voice_mail_message)) {
                        $config['message'] = $voicemailBean->voice_mail_message;
                    }

                    if (!empty($voicemailBean->voice_speech_by)) {
                        $config['voice'] = $voicemailBean->voice_speech_by;
                    }

                    if (!empty($voicemailBean->voice_finish_key)) {
                        $config['finish_key'] = $voicemailBean->voice_finish_key;
                    }

                    if (!empty($voicemailBean->voice_max_length)) {
                        $config['max_length'] = intval($voicemailBean->voice_max_length);
                    }
                }
            }

        } catch (Exception $e) {
            $this->logger->warn('SweetDialer Voicemail: Error loading voicemail config - ' . $e->getMessage());
        }

        return $config;
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
     * Find parent record (Contact, Lead, Account) by phone number
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
            $this->logger->warn('SweetDialer Voicemail: Error finding parent record - ' . $e->getMessage());
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

            // Build phone field query - search multiple phone fields
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
     * Find call ID by CallSid
     *
     * @param string $callSid
     * @return string|null
     */
    private function findCallIdBySid($callSid)
    {
        try {
            $bean = BeanFactory::getBean('outr_TwilioCalls');
            $call = $bean->get_list(
                "",
                "outr_twiliocalls.call_sid = '{$callSid}' AND outr_twiliocalls.deleted = 0",
                "",
                "",
                1
            );

            if (!empty($call['list'])) {
                $record = reset($call['list']);
                return $record->id;
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Update call record status
     *
     * @param string $callId
     * @param string $status
     * @param string $voicemailId
     * @return void
     */
    private function updateCallStatus($callId, $status, $voicemailId = null)
    {
        try {
            $callBean = BeanFactory::getBean('outr_TwilioCalls', $callId);
            if ($callBean) {
                $callBean->status = $status;
                if ($voicemailId) {
                    $callBean->voicemail_id = $voicemailId;
                }
                $callBean->save();
            }
        } catch (Exception $e) {
            $this->logger->warn('SweetDialer Voicemail: Error updating call status - ' . $e->getMessage());
        }
    }

    /**
     * Log voicemail initiation
     *
     * @param string $fromNumber
     * @param string $toNumber
     * @param string $callSid
     * @param Outr_CtiSettings|null $ctiSettings
     * @return void
     */
    private function logVoicemailInitiated($fromNumber, $toNumber, $callSid, $ctiSettings)
    {
        $this->logger->info(sprintf(
            'SweetDialer Voicemail: Initiated - From: %s, To: %s, CallSid: %s',
            $fromNumber,
            $toNumber,
            $callSid
        ));
    }
}

// Handle the request
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $handler = new VoiceVoicemailHandler();
    $handler->handle();
}
