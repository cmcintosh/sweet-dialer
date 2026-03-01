<?php
/**
 * Sweet-Dialer Webhook Security Validator (S-115)
 *
 * Validates X-Twilio-Signature using Twilio's HMAC-SHA1 algorithm
 * Returns 403 for invalid signatures, logs rejected requests
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

class TwilioWebhookValidator
{
    public static function validateRequest($authToken, $url, $postData, $signature)
    {
        $expectedSignature = self::computeSignature($authToken, $url, $postData);
        return hash_equals($expectedSignature, $signature);
    }
    
    public static function computeSignature($authToken, $url, $data = array())
    {
        if (is_array($data) && count($data) > 0) {
            ksort($data);
            foreach ($data as $key => $value) {
                $url .= $key . $value;
            }
        }
        
        $hash = hash_hmac('sha1', $url, $authToken, true);
        return base64_encode($hash);
    }
    
    public static function getWebhookUrl($path = '')
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . $host . $path;
    }
    
    public static function logRejectedRequest($reason, $context = array())
    {
        $logData = array(
            'timestamp' => date('Y-m-d H:i:s'),
            'reason' => $reason,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        );
        
        $logData = array_merge($logData, $context);
        $GLOBALS['log']->security("Sweet-Dialer Webhook Rejected: " . json_encode($logData));
        
        $logFile = 'custom/SweetDialer/logs/webhook-security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logLine = date('Y-m-d H:i:s') . ' | ' . $reason . ' | IP: ' . $logData['ip_address'] . 
                   ' | URI: ' . $logData['request_uri'] . PHP_EOL;
        
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    public static function sendForbiddenResponse()
    {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => false,
            'error' => 'Invalid signature',
            'message' => 'Request signature verification failed'
        ));
        exit;
    }
    
    public static function requireValidSignature($authToken, $endpointPath)
    {
        $signature = $_SERVER['HTTP_X_TWILIO_SIGNATURE'] ?? '';
        
        if (empty($signature)) {
            self::logRejectedRequest('Missing X-Twilio-Signature header', array(
                'headers' => getallheaders()
            ));
            self::sendForbiddenResponse();
        }
        
        $url = self::getWebhookUrl($endpointPath);
        $postData = $_POST ?? array();
        
        if (!self::validateRequest($authToken, $url, $postData, $signature)) {
            self::logRejectedRequest('Invalid signature', array(
                'provided_signature' => $signature,
                'expected_signature' => self::computeSignature($authToken, $url, $postData)
            ));
            self::sendForbiddenResponse();
        }
        
        return true;
    }
}
