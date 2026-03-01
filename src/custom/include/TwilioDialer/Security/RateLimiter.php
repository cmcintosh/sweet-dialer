<?php
/**
 * Sweet-Dialer Rate Limiter (S-116)
 *
 * Rate limiting for token endpoint
 * Max 10 requests/min per session
 * Returns 429 with retry-after header
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

class TwilioRateLimiter
{
    const MAX_REQUESTS_PER_MINUTE = 10;
    const TIME_WINDOW = 60;
    private static $storageDir = 'custom/SweetDialer/cache/ratelimit';
    
    public static function checkLimit($identifier)
    {
        self::ensureStorageDir();
        
        $key = self::sanitizeIdentifier($identifier);
        $file = self::$storageDir . '/' . $key . '.json';
        
        $now = time();
        $windowStart = $now - self::TIME_WINDOW;
        
        $data = array(
            'requests' => array(),
            'blocked_until' => 0
        );
        
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content) {
                $decoded = json_decode($content, true);
                if ($decoded) {
                    $data = $decoded;
                }
            }
        }
        
        if ($data['blocked_until'] > $now) {
            return array(
                'allowed' => false,
                'retry_after' => $data['blocked_until'] - $now,
                'limit' => self::MAX_REQUESTS_PER_MINUTE,
                'remaining' => 0
            );
        }
        
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        $requestCount = count($data['requests']);
        
        if ($requestCount >= self::MAX_REQUESTS_PER_MINUTE) {
            $oldestRequest = min($data['requests']);
            $blockedUntil = $oldestRequest + self::TIME_WINDOW;
            $data['blocked_until'] = $blockedUntil;
            self::saveData($file, $data);
            
            self::logRateLimitHit($identifier, $requestCount);
            
            return array(
                'allowed' => false,
                'retry_after' => $blockedUntil - $now,
                'limit' => self::MAX_REQUESTS_PER_MINUTE,
                'remaining' => 0
            );
        }
        
        $data['requests'][] = $now;
        self::saveData($file, $data);
        
        return array(
            'allowed' => true,
            'retry_after' => 0,
            'limit' => self::MAX_REQUESTS_PER_MINUTE,
            'remaining' => self::MAX_REQUESTS_PER_MINUTE - count($data['requests'])
        );
    }
    
    public static function sendRateLimitResponse($retryAfter)
    {
        header('HTTP/1.1 429 Too Many Requests');
        header('Content-Type: application/json');
        header('Retry-After: ' . $retryAfter);
        header('X-RateLimit-Limit: ' . self::MAX_REQUESTS_PER_MINUTE);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . (time() + $retryAfter));
        
        echo json_encode(array(
            'success' => false,
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please try again in ' . $retryAfter . ' seconds.',
            'retry_after' => $retryAfter
        ));
        exit;
    }
    
    public static function enforceLimit($identifier)
    {
        $result = self::checkLimit($identifier);
        
        if (!$result['allowed']) {
            self::sendRateLimitResponse($result['retry_after']);
        }
        
        header('X-RateLimit-Limit: ' . $result['limit']);
        header('X-RateLimit-Remaining: ' . $result['remaining']);
    }
    
    public static function getClientIdentifier()
    {
        if (!empty($_SESSION)) {
            return session_id() ?: 'session_' . md5(serialize($_SESSION));
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return 'ip_' . md5($ip . $ua);
    }
    
    private static function sanitizeIdentifier($identifier)
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $identifier);
        return substr($sanitized, 0, 128);
    }
    
    private static function ensureStorageDir()
    {
        if (!is_dir(self::$storageDir)) {
            mkdir(self::$storageDir, 0755, true);
        }
    }
    
    private static function saveData($file, $data)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }
    
    private static function logRateLimitHit($identifier, $requestCount)
    {
        $logData = array(
            'timestamp' => date('Y-m-d H:i:s'),
            'identifier_hash' => md5($identifier),
            'request_count' => $requestCount,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        );
        
        $GLOBALS['log']->security("Sweet-Dialer Rate Limit Hit: " . json_encode($logData));
    }
    
    public static function clearLimit($identifier)
    {
        self::ensureStorageDir();
        
        $key = self::sanitizeIdentifier($identifier);
        $file = self::$storageDir . '/' . $key . '.json';
        
        if (file_exists($file)) {
            return @unlink($file);
        }
        
        return true;
    }
}
