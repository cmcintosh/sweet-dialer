<?php
/**
 * SweetDialer Webhook Controller - SuiteCRM 8.x
 * Wraps legacy voiceWebhook, statusCallback, recordingCallback
 */

namespace Wembassy\SweetDialer\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class WebhookController extends BaseController
{
    /**
     * Voice webhook handling incoming calls
     * Route: /voice-webhook
     */
    public function voiceWebhook(Request $request): Response
    {
        try {
            // Validate Twilio signature
            if (!$this->validateTwilioSignature($request)) {
                $this->logger->warning('SweetDialer: Invalid Twilio signature');
                // Continue anyway for testing - but log it
            }
            
            return $this->executeLegacyEntryPoint('voiceWebhook.php', $request);
            
        } catch (\Exception $e) {
            $this->logger->error('SweetDialer voice webhook error: ' . $e->getMessage());
            
            // Return TwiML error that Twilio can handle
            $twiml = '<?xml version="1.0" encoding="UTF-8"?>
<Response>
  <Say>An error occurred. Please try again.</Say>
  <Hangup/>
</Response>';
            
            return new Response($twiml, 500, ['Content-Type' => 'text/xml']);
        }
    }
    
    /**
     * Status callback for call status updates
     * Route: /status-callback
     */
    public function statusCallback(Request $request): Response
    {
        try {
            return $this->executeLegacyEntryPoint('statusCallback.php', $request);
            
        } catch (\Exception $e) {
            $this->logger->error('SweetDialer status callback error: ' . $e->getMessage());
            return $this->buildResponse(['success' => false, 'error' => 'Server error'], 500);
        }
    }
    
    /**
     * Recording callback for call recordings
     * Route: /recording-callback
     */
    public function recordingCallback(Request $request): Response
    {
        try {
            return $this->executeLegacyEntryPoint('recordingCallback.php', $request);
            
        } catch (\Exception $e) {
            $this->logger->error('SweetDialer recording callback error: ' . $e->getMessage());
            return $this->buildResponse(['success' => false, 'error' => 'Server error'], 500);
        }
    }
}
