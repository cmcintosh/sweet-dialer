<?php
/**
 * WebhookHandler.php
 *
 * Sweet-Dialer Twilio Webhook Handler Base Class
 *
 * Handles Twilio webhook requests with signature validation,
 * request parsing, TwiML response building, and error logging.
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
 * WebhookHandler Base Class
 *
 * Abstract base class for all Twilio webhook handlers.
 * Provides signature validation, request parsing, and TwiML response building.
 */
abstract class WebhookHandler
{
    /** @var string Twilio Auth Token for signature validation */
    protected $authToken;

    /** @var string Request URL (must match Twilio's configured webhook URL) */
    protected $requestUrl;

    /** @var array Parsed request parameters */
    protected $requestParams;

    /** @var SugarLogger Logger instance */
    protected $logger;

    /** @var bool Whether request signature is valid */
    protected $isValidSignature;

    /** @var array Raw request headers */
    protected $headers;

    /**
     * Constructor
     *
     * @param string|null $authToken Twilio Auth Token (null to load from settings)
     * @param string|null $requestUrl Request URL (null to auto-detect)
     */
    public function __construct($authToken = null, $requestUrl = null)
    {
        $this->logger = LoggerManager::getLogger('outr_twilio_logger');
        $this->headers = $this->getAllHeaders();
        $this->requestParams = $_POST;

        // Load auth token from settings if not provided
        if ($authToken === null) {
            $this->authToken = $this->loadAuthTokenFromSettings();
        } else {
            $this->authToken = $authToken;
        }

        // Set request URL
        if ($requestUrl === null) {
            $this->requestUrl = $this->detectRequestUrl();
        } else {
            $this->requestUrl = $requestUrl;
        }

        // Validate signature
        $this->isValidSignature = $this->validateRequestSignature();
    }

    /**
     * Main entry point for webhook handling
     *
     * Validates signature and delegates to concrete handler
     *
     * @return void
     */
    public function handle()
    {
        // Validate signature
        if (!$this->isValidSignature) {
            $this->logInvalidSignature();
            $this->respondError('Invalid signature', 403);
            return;
        }

        try {
            // Log the request
            $this->logRequest();

            // Delegate to concrete handler
            $twiml = $this->processRequest();

            // Output TwiML response
            $this->respondTwiML($twiml);

        } catch (Exception $e) {
            $this->logError($e);
            $this->respondError('Internal error', 500);
        }
    }

    /**
     * Process the webhook request
     *
     * Abstract method to be implemented by concrete handlers
     *
     * @return TwiMLResponse TwiML response object
     * @throws Exception On processing errors
     */
    abstract protected function processRequest();

    /**
     * Get the endpoint name for this handler
     *
     * @return string Endpoint name (e.g., 'outbound', 'inbound', 'status')
     */
    abstract protected function getEndpointName();

    /**
     * Validate Twilio request signature
     *
     * @return bool True if signature is valid
     */
    protected function validateRequestSignature()
    {
        // Get the signature header
        $signature = $this->headers['X-Twilio-Signature'] ?? '';

        if (empty($signature)) {
            $this->logger->warn('SweetDialer Webhook: Missing X-Twilio-Signature header');
            return false;
        }

        if (empty($this->authToken)) {
            $this->logger->error('SweetDialer Webhook: Auth token not configured');
            return false;
        }

        // Compute expected signature
        $expectedSignature = $this->computeSignature($this->requestUrl, $this->requestParams);

        // Use constant-time comparison to prevent timing attacks
        return $this->secureCompare($signature, $expectedSignature);
    }

    /**
     * Compute Twilio request signature
     *
     * @param string $url Request URL
     * @param array $params Request parameters
     * @return string Computed signature
     */
    protected function computeSignature($url, array $params)
    {
        // Build the data string: URL + sorted param keys and values
        $data = $url;

        // Sort params by key
        ksort($params);

        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }

        // Compute HMAC-SHA1
        return base64_encode(hash_hmac('sha1', $data, $this->authToken, true));
    }

    /**
     * Constant-time string comparison to prevent timing attacks
     *
     * @param string $a First string
     * @param string $b Second string
     * @return bool True if strings are equal
     */
    protected function secureCompare($a, $b)
    {
        $aLen = strlen($a);
        $bLen = strlen($b);

        if ($aLen !== $bLen) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < $aLen; $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }

        return $result === 0;
    }

    /**
     * Load auth token from CTI settings
     *
     * @return string|null Auth token or null if not found
     */
    protected function loadAuthTokenFromSettings()
    {
        try {
            $settingsBean = BeanFactory::getBean('outr_TwilioSettings');
            if (!$settingsBean) {
                $this->logger->error('SweetDialer Webhook: Unable to load TwilioSettings bean');
                return null;
            }

            $settings = $settingsBean->get_full_list("status = 'Active' AND deleted = 0");

            if (empty($settings)) {
                $this->logger->error('SweetDialer Webhook: No active CTI settings found');
                return null;
            }

            // Use first active setting
            $setting = reset($settings);
            $authToken = $setting->auth_token;

            // Decrypt if encrypted
            if (!empty($authToken) && class_exists('CredentialEncryption')) {
                require_once 'custom/include/TwilioDialer/CredentialEncryption.php';
                $decrypted = CredentialEncryption::decrypt($authToken);
                if ($decrypted !== false) {
                    return $decrypted;
                }
            }

            return $authToken;

        } catch (Exception $e) {
            $this->logger->error('SweetDialer Webhook: Error loading auth token - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Auto-detect the full request URL
     *
     * @return string Full request URL
     */
    protected function detectRequestUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return $protocol . '://' . $host . $uri;
    }

    /**
     * Get all request headers (cross-platform compatible)
     *
     * @return array Associative array of headers
     */
    protected function getAllHeaders()
    {
        $headers = [];

        // Use apache_request_headers if available
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            // Fallback for non-Apache servers
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $headerName = str_replace('_', '-', substr($key, 5));
                    $headers[$headerName] = $value;
                }
            }
        }

        // Normalize header names (case-insensitive)
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[ucwords(strtolower(str_replace('-', ' ', $key)), '-')] = $value;
        }

        return $normalized;
    }

    /**
     * Get a request parameter value
     *
     * @param string $key Parameter name
     * @param mixed $default Default value if not found
     * @return mixed Parameter value
     */
    protected function getParam($key, $default = null)
    {
        return $this->requestParams[$key] ?? $default;
    }

    /**
     * Log the incoming request
     *
     * @return void
     */
    protected function logRequest()
    {
        $this->logger->info(sprintf(
            'SweetDialer Webhook: %s - CallSid: %s, From: %s, To: %s',
            $this->getEndpointName(),
            $this->getParam('CallSid', 'N/A'),
            $this->getParam('From', 'N/A'),
            $this->getParam('To', 'N/A')
        ));
    }

    /**
     * Log an invalid signature attempt
     *
     * @return void
     */
    protected function logInvalidSignature()
    {
        $errorData = [
            'endpoint' => $this->getEndpointName(),
            'request_url' => $this->requestUrl,
            'params' => $this->requestParams,
            'headers' => $this->headers,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ];

        $this->logger->error('SweetDialer Webhook: Invalid signature - ' . json_encode($errorData));

        // Log to error_logs table
        $this->logToErrorTable('INVALID_SIGNATURE', 'Invalid Twilio request signature', $errorData);
    }

    /**
     * Log an error to the error_logs table
     *
     * @param string $code Error code
     * @param string $message Error message
     * @param array $data Additional error data
     * @return void
     */
    protected function logToErrorTable($code, $message, array $data = [])
    {
        try {
            $errorBean = BeanFactory::newBean('outr_TwilioErrorLogs');
            if ($errorBean) {
                $errorBean->error_code = $code;
                $errorBean->error_message = $message;
                $errorBean->call_sid = $data['params']['CallSid'] ?? null;
                $errorBean->endpoint = $this->getEndpointName();
                $errorBean->request_body = json_encode($data['params'] ?? $this->requestParams);
                $errorBean->response_body = json_encode(['error' => $message, 'data' => $data]);
                $errorBean->save();
            }
        } catch (Exception $e) {
            $this->logger->error('SweetDialer Webhook: Failed to log error - ' . $e->getMessage());
        }
    }

    /**
     * Log an exception
     *
     * @param Exception $e Exception to log
     * @return void
     */
    protected function logError(Exception $e)
    {
        $errorData = [
            'endpoint' => $this->getEndpointName(),
            'request_url' => $this->requestUrl,
            'params' => $this->requestParams,
        ];

        $this->logger->error(sprintf(
            'SweetDialer Webhook: Error in %s - %s',
            $this->getEndpointName(),
            $e->getMessage()
        ));

        $this->logToErrorTable('WEBHOOK_ERROR', $e->getMessage(), $errorData);
    }

    /**
     * Respond with TwiML XML
     *
     * @param TwiMLResponse $twiml TwiML response object
     * @return void
     */
    protected function respondTwiML(TwiMLResponse $twiml)
    {
        header('Content-Type: application/xml');
        echo $twiml->toXml();
        exit;
    }

    /**
     * Respond with an error
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @return void
     */
    protected function respondError($message, $code = 500)
    {
        http_response_code($code);

        // Return minimal TwiML that hangs up for voice errors
        if ($code === 403) {
            header('Content-Type: application/xml');
            $response = new TwiMLResponse();
            $response->hangup();
            echo $response->toXml();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message]);
        }

        exit;
    }
}

/**
 * TwiML Response Builder
 *
 * Builds TwiML XML responses for Twilio voice webhooks
 */
class TwiMLResponse
{
    /** @var array TwiML verbs */
    private $verbs = [];

    /**
     * Add a Say verb
     *
     * @param string $text Text to speak
     * @param array $attributes Optional attributes (voice, language, loop)
     * @return self
     */
    public function say($text, array $attributes = [])
    {
        $this->verbs[] = [
            'verb' => 'Say',
            'attributes' => $attributes,
            'content' => $this->escapeXml($text),
        ];
        return $this;
    }

    /**
     * Add a Play verb
     *
     * @param string $url URL of audio file
     * @param array $attributes Optional attributes (loop, digits)
     * @return self
     */
    public function play($url, array $attributes = [])
    {
        $this->verbs[] = [
            'verb' => 'Play',
            'attributes' => $attributes,
            'content' => $this->escapeXml($url),
        ];
        return $this;
    }

    /**
     * Add a Dial verb
     *
     * @param string $content Number or Client identity to dial
     * @param array $attributes Optional attributes (callerId, timeout, action, record, etc.)
     * @param string $type Type of dial: 'number', 'client', 'conference', 'queue', 'sim'
     * @return self
     */
    public function dial($content, array $attributes = [], $type = 'number')
    {
        $dial = [
            'verb' => 'Dial',
            'attributes' => $attributes,
            'children' => [],
        ];

        // Wrap content in appropriate child element
        switch ($type) {
            case 'client':
                $dial['children'][] = [
                    'verb' => 'Client',
                    'attributes' => [],
                    'content' => $this->escapeXml($content),
                ];
                break;
            case 'number':
            default:
                $dial['children'][] = [
                    'verb' => 'Number',
                    'attributes' => [],
                    'content' => $this->escapeXml($content),
                ];
                break;
        }

        $this->verbs[] = $dial;
        return $this;
    }

    /**
     * Add a Client verb (for use within Dial)
     *
     * @param string $identity Client identity
     * @param array $attributes Optional attributes
     * @return string XML string
     */
    public function client($identity, array $attributes = [])
    {
        return $this->buildVerb('Client', $attributes, $this->escapeXml($identity));
    }

    /**
     * Add a Number verb (for use within Dial)
     *
     * @param string $number Phone number
     * @param array $attributes Optional attributes
     * @return string XML string
     */
    public function number($number, array $attributes = [])
    {
        return $this->buildVerb('Number', $attributes, $this->escapeXml($number));
    }

    /**
     * Add a Record verb
     *
     * @param array $attributes Optional attributes (action, method, timeout, maxLength, etc.)
     * @return self
     */
    public function record(array $attributes = [])
    {
        $this->verbs[] = [
            'verb' => 'Record',
            'attributes' => $attributes,
            'content' => null,
        ];
        return $this;
    }

    /**
     * Add a Gather verb
     *
     * @param callable|null $callback Callback to add nested verbs
     * @param array $attributes Optional attributes (action, method, timeout, numDigits, etc.)
     * @return self
     */
    public function gather(callable $callback = null, array $attributes = [])
    {
        $gather = [
            'verb' => 'Gather',
            'attributes' => $attributes,
            'children' => [],
        ];

        // Temporarily replace verbs to capture children
        $originalVerbs = $this->verbs;
        $this->verbs = [];

        if ($callback) {
            $callback($this);
        }

        $gather['children'] = $this->verbs;
        $this->verbs = $originalVerbs;
        $this->verbs[] = $gather;

        return $this;
    }

    /**
     * Add a Redirect verb
     *
     * @param string $url URL to redirect to
     * @param array $attributes Optional attributes (method)
     * @return self
     */
    public function redirect($url, array $attributes = [])
    {
        $this->verbs[] = [
            'verb' => 'Redirect',
            'attributes' => $attributes,
            'content' => $this->escapeXml($url),
        ];
        return $this;
    }

    /**
     * Add a Hangup verb
     *
     * @return self
     */
    public function hangup()
    {
        $this->verbs[] = [
            'verb' => 'Hangup',
            'attributes' => [],
            'content' => null,
        ];
        return $this;
    }

    /**
     * Add a Reject verb
     *
     * @param string $reason Reason for rejection (rejected, busy)
     * @return self
     */
    public function reject($reason = 'rejected')
    {
        $this->verbs[] = [
            'verb' => 'Reject',
            'attributes' => ['reason' => $reason],
            'content' => null,
        ];
        return $this;
    }

    /**
     * Add a Pause verb
     *
     * @param int $length Length of pause in seconds (default 1)
     * @return self
     */
    public function pause($length = 1)
    {
        $this->verbs[] = [
            'verb' => 'Pause',
            'attributes' => ['length' => $length],
            'content' => null,
        ];
        return $this;
    }

    /**
     * Convert to XML string
     *
     * @return string XML representation
     */
    public function toXml()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Response>' . "\n";

        foreach ($this->verbs as $verb) {
            $xml .= $this->renderVerb($verb, 1);
        }

        $xml .= '</Response>';

        return $xml;
    }

    /**
     * Render a verb to XML
     *
     * @param array $verb Verb definition
     * @param int $indent Indentation level
     * @return string XML string
     */
    private function renderVerb(array $verb, $indent = 0)
    {
        $indentStr = str_repeat('  ', $indent);
        $attributes = $this->renderAttributes($verb['attributes'] ?? []);

        if (empty($verb['children']) && empty($verb['content'])) {
            // Self-closing tag
            return $indentStr . '<' . $verb['verb'] . $attributes . ' />' . "\n";
        }

        // Opening tag
        $xml = $indentStr . '<' . $verb['verb'] . $attributes . '>';

        // Content (text or children)
        if (!empty($verb['children'])) {
            $xml .= "\n";
            foreach ($verb['children'] as $child) {
                $xml .= $this->renderVerb($child, $indent + 1);
            }
            $xml .= $indentStr;
        } elseif (!empty($verb['content'])) {
            $xml .= $verb['content'];
        }

        // Closing tag
        $xml .= '</' . $verb['verb'] . '>' . "\n";

        return $xml;
    }

    /**
     * Render attributes array to string
     *
     * @param array $attributes Attributes
     * @return string Attribute string
     */
    private function renderAttributes(array $attributes)
    {
        if (empty($attributes)) {
            return '';
        }

        $pairs = [];
        foreach ($attributes as $key => $value) {
            $pairs[] = $key . '="' . $this->escapeXml($value) . '"';
        }

        return ' ' . implode(' ', $pairs);
    }

    /**
     * Build a single verb XML string
     *
     * @param string $verb Verb name
     * @param array $attributes Attributes
     * @param string|null $content Content
     * @return string XML string
     */
    private function buildVerb($verb, array $attributes, $content = null)
    {
        $attrStr = $this->renderAttributes($attributes);

        if ($content === null) {
            return "<{$verb}{$attrStr} />";
        }

        return "<{$verb}{$attrStr}>{$content}</{$verb}>";
    }

    /**
     * Escape special XML characters
     *
     * @param string $text Text to escape
     * @return string Escaped text
     */
    private function escapeXml($text)
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Webhook Exception Class
 */
class WebhookException extends Exception
{
}
