<?php
/**
 * voiceHold.php
 *
 * Sweet-Dialer Voice Hold Webhook Handler
 *
 * Provides hold music for waiting callers.
 *
 * URL: /entryPoint.php?entryPoint=sweetdialer_voice_hold
 * Mapped to: /twilio/voice/hold
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
 * VoiceHoldHandler
 *
 * S-052: Generates TwiML for hold music playback
 */
class VoiceHoldHandler extends WebhookHandler
{
    /** @var string Default hold music URL (Twilio's default hold music) */
    private $defaultHoldMusic = 'https://demo.twilio.com/docs/voice.xml';

    /**
     * Get the endpoint name
     *
     * @return string
     */
    protected function getEndpointName()
    {
        return 'hold';
    }

    /**
     * Process the hold music request
     *
     * S-052: Generates TwiML: <Play loop="0">{holdRingtoneUrl}</Play>
     *
     * @return TwiMLResponse
     * @throws Exception
     */
    protected function processRequest()
    {
        // Get call details from request
        $toNumber = $this->getParam('To');
        $callSid = $this->getParam('CallSid');

        // Look up CTI settings for custom hold music
        $ctiSettings = null;
        if (!empty($toNumber)) {
            $ctiSettings = $this->findCtiSettingsByPhoneNumber($toNumber);
        }

        // Get hold music URL
        $holdMusicUrl = $this->getHoldMusicUrl($ctiSettings);

        $this->logger->info(sprintf(
            'SweetDialer Hold: Playing hold music - CallSid: %s, URL: %s',
            $callSid ?? 'unknown',
            $holdMusicUrl
        ));

        // Generate TwiML with looping hold music
        $response = new TwiMLResponse();

        // S-052: <Play loop="0"> plays indefinitely
        $response->play($holdMusicUrl, [
            'loop' => '0', // 0 = loop infinitely
        ]);

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
            $this->logger->error('SweetDialer Hold: Error finding CTI settings - ' . $e->getMessage());
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
     * Get hold music URL
     *
     * @param Outr_CtiSettings|null $ctiSettings
     * @return string
     */
    private function getHoldMusicUrl($ctiSettings)
    {
        try {
            // Check for custom hold music file in CTI settings
            if ($ctiSettings && !empty($ctiSettings->hold_ring_file)) {
                // The file field should contain the URL path to the uploaded file
                return $ctiSettings->hold_ring_file;
            }

            // Check for custom ringtone module (SweetDialerRingtones)
            $ringtoneBean = BeanFactory::getBean('outr_TwilioRingtones');
            if ($ringtoneBean) {
                $defaultHold = $ringtoneBean->get_list(
                    "",
                    "outr_twilioringtones.type = 'hold' " .
                    "AND outr_twilioringtones.default = 1 " .
                    "AND outr_twilioringtones.deleted = 0",
                    "",
                    "",
                    1
                );

                if (!empty($defaultHold['list'])) {
                    $ringtone = reset($defaultHold['list']);
                    if (!empty($ringtone->file_url)) {
                        return $ringtone->file_url;
                    }
                }
            }

            // Fall back to default Twilio hold music
            return $this->defaultHoldMusic;

        } catch (Exception $e) {
            $this->logger->warn('SweetDialer Hold: Error getting hold music URL - ' . $e->getMessage());
            return $this->defaultHoldMusic;
        }
    }
}

// Handle the request
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $handler = new VoiceHoldHandler();
    $handler->handle();
}
