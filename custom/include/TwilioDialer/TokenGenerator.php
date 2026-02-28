<?php
/**
 * TokenGenerator.php
 *
 * Sweet-Dialer Twilio Client Access Token Generator
 *
 * Generates JWT access tokens for Twilio Client SDK to enable
 * browser-based voice calling.
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 *
 * @requires firebase/php-jwt ^6.0
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

// Check if firebase/php-jwt is available
$jwtAutoloadPaths = [
    'vendor/autoload.php',
    'custom/vendor/autoload.php',
    'custom/include/TwilioDialer/vendor/autoload.php',
];

$jwtLoaded = false;
foreach ($jwtAutoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $jwtLoaded = class_exists('Firebase\JWT\JWT');
        if ($jwtLoaded) {
            break;
        }
    }
}

if (!$jwtLoaded) {
    // Manual JWT implementation if library not available
    // This is a fallback implementation - recommend installing firebase/php-jwt
    spl_autoload_register(function ($class) {
        if ($class === 'Firebase\JWT\JWT') {
            require_once __DIR__ . '/JWT.php';
        }
    });
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * TokenGenerator
 *
 * Generates Twilio Client access tokens for browser voice calling.
 */
class TokenGenerator
{
    /** @var string Twilio API Key SID */
    private $apiKeySid;

    /** @var string Twilio API Key Secret */
    private $apiKeySecret;

    /** @var string Twilio Account SID */
    private $accountSid;

    /** @var string TwiML Application SID */
    private $twimlAppSid;

    /** @var string Agent identity (user/extension identifier) */
    private $identity;

    /** @var int Token TTL in seconds (default 3600 = 1 hour) */
    private $ttl;

    /** @var string JWT algorithm */
    private $algorithm = 'HS256';

    /** @var array Grant payload for the token */
    private $grants = [];

    /**
     * Constructor
     *
     * @param string $apiKeySid API Key SID from Twilio console
     * @param string $apiKeySecret API Key Secret from Twilio console
     * @param string $accountSid Twilio Account SID
     * @param string $twimlAppSid TwiML Application SID for voice
     */
    public function __construct($apiKeySid, $apiKeySecret, $accountSid, $twimlAppSid)
    {
        $this->apiKeySid = $apiKeySid;
        $this->apiKeySecret = $apiKeySecret;
        $this->accountSid = $accountSid;
        $this->twimlAppSid = $twimlAppSid;
        $this->ttl = 3600; // Default 1 hour
    }

    /**
     * Set the agent identity for the token
     *
     * @param string $identity Agent identity (e.g., user ID, extension, or email)
     * @return self
     */
    public function setIdentity($identity)
    {
        $this->identity = $this->sanitizeIdentity($identity);
        return $this;
    }

    /**
     * Set token TTL
     *
     * @param int $seconds TTL in seconds
     * @return self
     */
    public function setTtl($seconds)
    {
        $this->ttl = intval($seconds);
        return $this;
    }

    /**
     * Add Voice Grant to the token
     *
     * @param string|null $outgoingApplicationSid Optional outgoing application SID
     * @param array $outgoingParams Optional parameters for outgoing calls
     * @param string|null $pushCredentialSid Optional push notification credential SID
     * @return self
     */
    public function addVoiceGrant($outgoingApplicationSid = null, array $outgoingParams = [], $pushCredentialSid = null)
    {
        $grant = [
            'outgoing' => [],
            'incoming' => [
                'allow' => true,
            ],
        ];

        // Add outgoing application
        if ($outgoingApplicationSid || $this->twimlAppSid) {
            $grant['outgoing'] = [
                'application_sid' => $outgoingApplicationSid ?: $this->twimlAppSid,
            ];

            if (!empty($outgoingParams)) {
                $grant['outgoing']['params'] = $outgoingParams;
            }
        }

        // Add push credentials if provided
        if ($pushCredentialSid) {
            $grant['push_credential_sid'] = $pushCredentialSid;
        }

        $this->grants['voice'] = $grant;

        return $this;
    }

    /**
     * Generate the access token
     *
     * @return string JWT access token
     * @throws TokenGenerationException If token generation fails
     */
    public function generateToken()
    {
        // Validate required fields
        if (empty($this->identity)) {
            throw new TokenGenerationException('Identity is required');
        }

        if (empty($this->apiKeySid) || empty($this->apiKeySecret)) {
            throw new TokenGenerationException('API Key SID and Secret are required');
        }

        if (empty($this->accountSid)) {
            throw new TokenGenerationException('Account SID is required');
        }

        // Build JWT payload
        $now = time();
        $expiration = $now + $this->ttl;

        $payload = [
            'jti' => $this->apiKeySid . '-' . $now . '-' . uniqid(),
            'iss' => $this->apiKeySid,
            'sub' => $this->accountSid,
            'iat' => $now,
            'exp' => $expiration,
            'nbf' => $now,
            'grants' => array_merge(
                ['identity' => $this->identity],
                $this->grants
            ),
        ];

        try {
            // Generate JWT
            $token = JWT::encode($payload, $this->apiKeySecret, $this->algorithm);

            $GLOBALS['log']->debug('SweetDialer: Generated access token for identity: ' . $this->identity);

            return $token;

        } catch (Exception $e) {
            $GLOBALS['log']->error('SweetDialer: Token generation failed - ' . $e->getMessage());
            throw new TokenGenerationException('Failed to generate token: ' . $e->getMessage());
        }
    }

    /**
     * Generate token with Voice Grant (convenience method)
     *
     * @param string $identity Agent identity
     * @param string|null $outgoingApplicationSid Optional outgoing app SID (uses default if null)
     * @return string JWT access token
     * @throws TokenGenerationException
     */
    public function generateVoiceToken($identity, $outgoingApplicationSid = null)
    {
        $this->setIdentity($identity);
        $this->addVoiceGrant($outgoingApplicationSid);
        return $this->generateToken();
    }

    /**
     * Decode and validate a token (for debugging/testing)
     *
     * @param string $token JWT token
     * @return object Decoded token payload
     * @throws TokenGenerationException If token is invalid
     */
    public function decodeToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->apiKeySecret, $this->algorithm));
            return $decoded;
        } catch (Exception $e) {
            throw new TokenGenerationException('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Sanitize identity string
     *
     * @param string $identity
     * @return string
     */
    private function sanitizeIdentity($identity)
    {
        // Remove any characters not allowed in Twilio identity
        // Allowed: alphanumeric, underscore, dash, @, :
        return preg_replace('/[^a-zA-Z0-9_\-@:]/', '', $identity);
    }

    /**
     * Get token expiration time
     *
     * @return int Unix timestamp
     */
    public function getExpiration()
    {
        return time() + $this->ttl;
    }

    /**
     * Check if token will expire within given seconds
     *
     * @param string $token JWT token
     * @param int $withinSeconds Seconds threshold
     * @return bool
     */
    public function isExpiringSoon($token, $withinSeconds = 300)
    {
        try {
            $decoded = $this->decodeToken($token);
            $expiration = $decoded->exp;
            $now = time();

            return ($expiration - $now) < $withinSeconds;
        } catch (Exception $e) {
            return true; // If we can't decode, assume it's expiring
        }
    }
}

/**
 * Custom exception for token generation errors
 */
class TokenGenerationException extends Exception
{}
