<?php
// Epic 4/S-040: Token Generator for Twilio Client SDK
// NOTE: Requires Twilio PHP SDK: composer require twilio/sdk
if (!defined("sugarEntry") || !sugarEntry) die("Not A Valid Entry Point");

class TokenGenerator {
    public static function generate($apiKeySid, $apiKeySecret, $accountSid, $twimlAppSid, $agentIdentity) {
        // Check if Twilio SDK is available
        if (!class_exists("Twilio\Jwt\AccessToken")) {
            throw new Exception("Twilio PHP SDK not installed. Run: composer require twilio/sdk");
        }
        
        $token = new Twilio\Jwt\AccessToken(
            $accountSid,
            $apiKeySid,
            $apiKeySecret,
            3600, // 1 hour TTL
            $agentIdentity
        );
        
        $voiceGrant = new Twilio\Jwt\Grants\VoiceGrant();
        $voiceGrant->setOutgoingApplicationSid($twimlAppSid);
        $voiceGrant->setIncomingAllow(true);
        $token->addGrant($voiceGrant);
        
        return $token->toJWT();
    }
}
