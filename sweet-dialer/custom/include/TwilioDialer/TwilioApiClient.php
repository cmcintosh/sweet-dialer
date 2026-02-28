<?php
/**
 * Twilio API Client (S-032)
 * REST client for Twilio API with retry logic
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once 'modules/outr_TwilioLogger/outr_TwilioLogger.php';

class TwilioApiClient
{
    protected string $accountSid;
    protected string $authToken;
    protected string $apiKeySid;
    protected string $apiKeySecret;
    protected string $baseUrl = 'https://api.twilio.com';
    protected int $maxRetries = 3;
    protected array $retryBackoffSeconds = [1, 2, 4];

    public function __construct(string $accountSid = '', string $authToken = '')
    {
        $this->accountSid = $accountSid;
        $this->authToken = $authToken;
    }

    public function setApiKey(string $apiKeySid, string $apiKeySecret): void
    {
        $this->apiKeySid = $apiKeySid;
        $this->apiKeySecret = $apiKeySecret;
    }

    public function setCredentials(string $accountSid, string $authToken): void
    {
        $this->accountSid = $accountSid;
        $this->authToken = $authToken;
    }

    /**
     * Perform GET request with retry logic
     * @return array Response with success, status_code, response/error
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    /**
     * Perform POST request with retry logic
     * @return array Response with success, status_code, response/error
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [], $data);
    }

    /**
     * Execute HTTP request with retry logic
     * Retries on 429 or 5xx status codes with exponential backoff (1s, 2s, 4s)
     */
    protected function request(string $method, string $endpoint, array $params = [], array $body = []): array
    {
        $lastError = null;
        $lastResult = null;
        
        for ($attempt = 0; $attempt < $this->maxRetries; $attempt++) {
            try {
                $result = $this->executeRequest($method, $endpoint, $params, $body);
                
                // Check if we should retry
                if ($this->isRetryableError($result['status_code'] ?? 0)) {
                    if ($attempt < $this->maxRetries - 1) {
                        $this->logRetry($endpoint, $method, $attempt + 1, $result['status_code']);
                        $sleepSeconds = $this->retryBackoffSeconds[$attempt] ?? 4;
                        sleep($sleepSeconds);
                        continue;
                    }
                }
                
                return $result;
            } catch (Exception $e) {
                $lastError = $e;
                
                if ($attempt < $this->maxRetries - 1) {
                    $this->logRetry($endpoint, $method, $attempt + 1, 0, $e->getMessage());
                    $sleepSeconds = $this->retryBackoffSeconds[$attempt] ?? 4;
                    sleep($sleepSeconds);
                }
            }
        }

        return [
            'success' => false,
            'error' => $lastError ? $lastError->getMessage() : 'Max retries exceeded',
            'status_code' => $lastResult['status_code'] ?? 0,
        ];
    }

    /**
     * Execute single HTTP request
     * @throws Exception on cURL error
     */
    protected function executeRequest(string $method, string $endpoint, array $params, array $body): array
    {
        $url = $this->buildUrl($endpoint, $params);
        
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        // HTTPS Basic Auth
        $username = $this->accountSid;
        $password = $this->authToken;
        curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($body)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: {$error}");
        }

        $decodedResponse = json_decode($response, true);
        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            $decodedResponse = ['raw_response' => $response];
        }

        return [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'status_code' => $statusCode,
            'response' => $decodedResponse,
            'raw_response' => $response,
        ];
    }

    /**
     * Build full URL with query parameters
     */
    protected function buildUrl(string $endpoint, array $params): string
    {
        $url = $this->baseUrl . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * Check if status code should trigger retry
     * 429 = Too Many Requests, 5xx = Server Error
     */
    protected function isRetryableError(int $statusCode): bool
    {
        return $statusCode === 429 || ($statusCode >= 500 && $statusCode < 600);
    }

    /**
     * Log retry attempt to outr_twilio_logger
     */
    protected function logRetry(string $endpoint, string $method, int $attemptNumber, int $statusCode, string $errorMessage = ''): void
    {
        if (class_exists('outr_TwilioLogger')) {
            $logger = new outr_TwilioLogger();
            $logger->logRetry([
                'endpoint' => $endpoint,
                'method' => $method,
                'attempt_number' => $attemptNumber,
                'status_code' => $statusCode,
                'error_message' => $errorMessage,
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function getAccountSid(): string
    {
        return $this->accountSid;
    }

    public function getAuthToken(): string
    {
        return $this->authToken;
    }
}
