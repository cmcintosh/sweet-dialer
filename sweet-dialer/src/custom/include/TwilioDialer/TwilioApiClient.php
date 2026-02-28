<?php
/**
 * TwilioApiClient.php
 *
 * Sweet-Dialer Twilio API Client
 * Wraps cURL calls to Twilio REST API with retry logic and authentication
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'include/SugarLogger/SugarLogger.php';

/**
 * TwilioApiClient
 *
 * Handles all Twilio REST API communications with retry logic,
 * authentication, and error handling.
 */
class TwilioApiClient
{
    /** @var string Twilio Account SID */
    private $accountSid;

    /** @var string Twilio Auth Token */
    private $authToken;

    /** @var string Twilio API Key SID (optional) */
    private $apiKeySid;

    /** @var string Twilio API Key Secret (optional) */
    private $apiKeySecret;

    /** @var string Base URL for Twilio API */
    private $baseUrl = 'https://api.twilio.com';

    /** @var int Maximum number of retry attempts */
    private $maxRetries = 3;

    /** @var SugarLogger Logger instance */
    private $logger;

    /**
     * Constructor
     *
     * @param string $accountSid Twilio Account SID
     * @param string $authToken Twilio Auth Token
     */
    public function __construct($accountSid, $authToken)
    {
        $this->accountSid = $accountSid;
        $this->authToken = $authToken;
        $this->logger = LoggerManager::getLogger('outr_twilio_logger');
    }

    /**
     * Set API Key credentials for enhanced security
     *
     * @param string $apiKeySid API Key SID
     * @param string $apiKeySecret API Key Secret
     * @return void
     */
    public function setApiKeyCredentials($apiKeySid, $apiKeySecret)
    {
        $this->apiKeySid = $apiKeySid;
        $this->apiKeySecret = $apiKeySecret;
    }

    /**
     * Make a GET request to the Twilio API
     *
     * @param string $endpoint API endpoint (e.g., '/2010-04-01/Accounts/{SID}.json')
     * @param array $params Query parameters
     * @return array|object Decoded JSON response
     * @throws TwilioApiException On API error
     */
    public function get($endpoint, array $params = [])
    {
        $url = $this->buildUrl($endpoint, $params);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Make a POST request to the Twilio API
     *
     * @param string $endpoint API endpoint
     * @param array $data Post data
     * @return array|object Decoded JSON response
     * @throws TwilioApiException On API error
     */
    public function post($endpoint, array $data = [])
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Validate account credentials
     *
     * @return array Validation result with success, message, and verified timestamp
     */
    public function validateCredentials()
    {
        $result = [
            'success' => false,
            'message' => '',
            'verified_at' => null,
            'account_status' => null,
        ];

        try {
            // Call the Account endpoint to verify credentials
            $response = $this->get("/2010-04-01/Accounts/{$this->accountSid}.json");

            if (isset($response->sid) && $response->sid === $this->accountSid) {
                $result['success'] = true;
                $result['account_status'] = $response->status ?? 'unknown';
                $result['verified_at'] = date('Y-m-d H:i:s');

                if ($result['account_status'] === 'active') {
                    $result['message'] = 'Credentials validated successfully. Account is active.';
                } else {
                    $result['message'] = "Credentials valid but account status is: {$result['account_status']}";
                }
            } else {
                $result['message'] = 'Invalid response from Twilio API';
            }
        } catch (TwilioApiException $e) {
            $result['message'] = $e->getMessage();
            $errorCode = $e->getCode();

            // Map common error codes to user-friendly messages
            if ($errorCode === 404) {
                $result['message'] = 'Account not found. Please verify your Account SID.';
            } elseif ($errorCode === 401) {
                $result['message'] = 'Authentication failed. Please verify your Auth Token.';
            } elseif ($errorCode === 403) {
                $result['message'] = 'Access forbidden. Account may be suspended or credentials invalid.';
            }
        } catch (Exception $e) {
            $result['message'] = 'Unexpected error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Validate API Key credentials separately
     *
     * @return array Validation result with success and message
     */
    public function validateApiKeyCredentials()
    {
        $result = [
            'success' => false,
            'message' => '',
            'verified_at' => null,
        ];

        if (empty($this->apiKeySid) || empty($this->apiKeySecret)) {
            $result['message'] = 'API Key credentials not configured';
            return $result;
        }

        try {
            // Make a request using API Key authentication
            $url = "{$this->baseUrl}/2010-04-01/Accounts/{$this->accountSid}.json";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->apiKeySid}:{$this->apiKeySecret}");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new Exception("cURL error: {$curlError}");
            }

            $decoded = json_decode($response);

            if ($httpCode === 200 && isset($decoded->sid)) {
                $result['success'] = true;
                $result['message'] = 'API Key credentials validated successfully.';
                $result['verified_at'] = date('Y-m-d H:i:s');
            } elseif ($httpCode === 401 || $httpCode === 403) {
                $result['message'] = 'API Key authentication failed. Please verify API Key SID and Secret.';
            } else {
                $result['message'] = "API Key validation failed (HTTP {$httpCode})";
            }
        } catch (Exception $e) {
            $result['message'] = 'API Key validation error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Fetch all incoming phone numbers from Twilio account
     *
     * @return array Array of phone number data
     */
    public function fetchPhoneNumbers()
    {
        $phoneNumbers = [];

        try {
            $response = $this->get("/2010-04-01/Accounts/{$this->accountSid}/IncomingPhoneNumbers.json");

            if (isset($response->incoming_phone_numbers) && is_array($response->incoming_phone_numbers)) {
                foreach ($response->incoming_phone_numbers as $number) {
                    $phoneNumbers[] = [
                        'sid' => $number->sid ?? '',
                        'number' => $number->phone_number ?? '',
                        'friendly_name' => $number->friendly_name ?? '',
                        'capabilities' => [
                            'voice' => $number->capabilities->voice ?? false,
                            'sms' => $number->capabilities->sms ?? false,
                            'mms' => $number->capabilities->mms ?? false,
                            'fax' => $number->capabilities->fax ?? false,
                        ],
                        'voice_url' => $number->voice_url ?? '',
                        'sms_url' => $number->sms_url ?? '',
                        'status_callback' => $number->status_callback ?? '',
                    ];
                }
            }
        } catch (Exception $e) {
            $this->logger->error("Failed to fetch phone numbers: " . $e->getMessage());
            throw $e;
        }

        return $phoneNumbers;
    }

    /**
     * Build full URL with query parameters
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return string Full URL
     */
    private function buildUrl($endpoint, array $params = [])
    {
        $url = $this->baseUrl . $endpoint;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Make HTTP request with retry logic
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $data Post data (for POST requests)
     * @return array|object Decoded JSON response
     * @throws TwilioApiException On API error after retries
     */
    private function makeRequest($method, $url, array $data = [])
    {
        $attempt = 0;
        $lastException = null;

        // Exponential backoff intervals: 1s, 2s, 4s
        $backoffIntervals = [1, 2, 4];

        while ($attempt <= $this->maxRetries) {
            try {
                return $this->executeRequest($method, $url, $data);
            } catch (TwilioApiException $e) {
                $lastException = $e;
                $httpCode = $e->getCode();

                // Check if retry is warranted (429 rate limit or 5xx server errors)
                $shouldRetry = ($httpCode === 429) || ($httpCode >= 500 && $httpCode < 600);

                if ($shouldRetry && $attempt < $this->maxRetries) {
                    $delay = $backoffIntervals[$attempt] ?? 4;

                    $this->logger->warn(sprintf(
                        'Twilio API request failed (HTTP %d), retrying in %d seconds (attempt %d/%d): %s',
                        $httpCode,
                        $delay,
                        $attempt + 1,
                        $this->maxRetries,
                        $e->getMessage()
                    ));

                    sleep($delay);
                    $attempt++;
                    continue;
                }

                // Not retryable or max retries exceeded
                throw $e;
            }
        }

        // Should not reach here, but just in case
        if ($lastException) {
            throw $lastException;
        }

        throw new TwilioApiException('Unexpected error in request handling', 0);
    }

    /**
     * Execute a single HTTP request
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $data Post data
     * @return array|object Decoded JSON response
     * @throws TwilioApiException On API error
     */
    private function executeRequest($method, $url, array $data = [])
    {
        $ch = curl_init();

        // Determine authentication credentials to use
        // Prefer API Key if available, otherwise use Account SID + Auth Token
        if (!empty($this->apiKeySid) && !empty($this->apiKeySecret)) {
            $username = $this->apiKeySid;
            $password = $this->apiKeySecret;
        } else {
            $username = $this->accountSid;
            $password = $this->authToken;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // Set headers
        $headers = [
            'Accept: application/json',
        ];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);

            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            }
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        // Handle cURL errors
        if ($curlError) {
            throw new TwilioApiException("Request failed: {$curlError}", 0);
        }

        // Decode response
        $decoded = json_decode($response);

        // Handle HTTP errors
        if ($httpCode >= 400) {
            $errorMessage = 'Twilio API error';
            $errorCode = $httpCode;

            if (isset($decoded->message)) {
                $errorMessage = $decoded->message;
            }

            if (isset($decoded->code)) {
                $errorCode = $decoded->code;
            }

            throw new TwilioApiException($errorMessage, $errorCode);
        }

        return $decoded;
    }
}

/**
 * Custom exception class for Twilio API errors
 */
class TwilioApiException extends Exception
{
    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param Exception|null $previous Previous exception
     */
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
