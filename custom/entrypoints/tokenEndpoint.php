<?php
/**
 * tokenEndpoint.php
 *
 * Sweet-Dialer Twilio Client Token Endpoint
 *
 * SuiteCRM entry point that generates Twilio Client access tokens
 * for authenticated agents to use with the Twilio Client SDK.
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    define('sugarEntry', true);
}

require_once 'include/entryPoint.php';
require_once 'custom/include/TwilioDialer/TokenGenerator.php';
require_once 'custom/include/TwilioDialer/CredentialEncryption.php';

/**
 * SweetDialerTokenEndpoint
 *
 * Handles token generation requests from authenticated users.
 */
class SweetDialerTokenEndpoint
{
    /**
     * @var User Current authenticated user
     */
    private $currentUser;

    /**
     * @var bool Whether request was made via AJAX
     */
    private $isAjax = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $current_user;
        $this->currentUser = $current_user;
        $this->isAjax = $this->isAjaxRequest();
    }

    /**
     * Handle the incoming token request
     *
     * @return void Outputs JSON response
     */
    public function handle()
    {
        try {
            // 1. Authenticate session (S-041)
            if (!$this->authenticate()) {
                $this->sendUnauthorizedResponse();
                return;
            }

            // 2. Get user's active CTI settings
            $ctiSettings = $this->getUserCtiSettings();

            if (!$ctiSettings) {
                $this->sendErrorResponse('No active CTI settings found for user', 404);
                return;
            }

            // 3. Generate access token
            $tokenData = $this->generateToken($ctiSettings);

            // 4. Send success response
            $this->sendSuccessResponse($tokenData);

        } catch (Exception $e) {
            $GLOBALS['log']->error('SweetDialer TokenEndpoint: Error - ' . $e->getMessage());
            $this->sendErrorResponse('Internal server error', 500);
        }
    }

    /**
     * Authenticate the user session
     *
     * @return bool True if authenticated
     */
    private function authenticate()
    {
        global $sugar_config, $current_user;

        // Check if user is logged in
        if (empty($current_user) || empty($current_user->id)) {
            return false;
        }

        // Verify session is valid
        if (!empty($_SESSION['authenticated_user_id']) &&
            $_SESSION['authenticated_user_id'] === $current_user->id) {
            return true;
        }

        // Check for API token in headers (for API clients)
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (strpos($authHeader, 'Bearer ') === 0) {
            // Could implement OAuth token validation here
            // For now, rely on SuiteCRM session
            return false;
        }

        return false;
    }

    /**
     * Get the authenticated user's CTI settings
     *
     * @return Outr_CtiSettings|null
     */
    private function getUserCtiSettings()
    {
        $bean = BeanFactory::getBean('outr_CtiSettings');

        // First, try to find settings assigned to this user with is_active = 1
        $settings = $bean->get_list(
            "",
            "outr_ctisettings.assigned_user_id = '{$this->currentUser->id}' " .
            "AND outr_ctisettings.is_active = 1 " .
            "AND outr_ctisettings.deleted = 0",
            "",
            "",
            1
        );

        if (!empty($settings['list']) && count($settings['list']) > 0) {
            return $this->prepareSettings(reset($settings['list']));
        }

        // If no active user-specific settings, try global default
        $settings = $bean->get_list(
            "",
            "outr_ctisettings.is_default = 1 " .
            "AND outr_ctisettings.is_active = 1 " .
            "AND outr_ctisettings.deleted = 0",
            "",
            "",
            1
        );

        if (!empty($settings['list']) && count($settings['list']) > 0) {
            return $this->prepareSettings(reset($settings['list']));
        }

        return null;
    }

    /**
     * Prepare CTI settings by decrypting sensitive fields
     *
     * @param Outr_CtiSettings $settings
     * @return Outr_CtiSettings
     */
    private function prepareSettings($settings)
    {
        global $sugar_config;
        $encryption = new CredentialEncryption($sugar_config);

        // Decrypt auth_token
        if (!empty($settings->auth_token) && $encryption->isEncrypted($settings->auth_token)) {
            $decrypted = $encryption->decrypt($settings->auth_token);
            if ($decrypted !== false) {
                $settings->auth_token = $decrypted;
            }
        }

        // Decrypt api_key_secret
        if (!empty($settings->api_key_secret) && $encryption->isEncrypted($settings->api_key_secret)) {
            $decrypted = $encryption->decrypt($settings->api_key_secret);
            if ($decrypted !== false) {
                $settings->api_key_secret = $decrypted;
            }
        }

        return $settings;
    }

    /**
     * Generate Twilio Client access token
     *
     * @param Outr_CtiSettings $ctiSettings
     * @return array Token data
     * @throws TokenGenerationException
     */
    private function generateToken($ctiSettings)
    {
        // Validate we have the required credentials
        if (empty($ctiSettings->account_sid)) {
            throw new TokenGenerationException('Account SID is required');
        }

        if (empty($ctiSettings->api_key_sid) || empty($ctiSettings->api_key_secret)) {
            // Fall back to using account credentials
            // Note: API Key is preferred for security
            if (empty($ctiSettings->auth_token)) {
                throw new TokenGenerationException('Either API Key or Auth Token is required');
            }
            // Use account SID and auth token as fallback
            $apiKeySid = $ctiSettings->account_sid;
            $apiKeySecret = $ctiSettings->auth_token;
        } else {
            $apiKeySid = $ctiSettings->api_key_sid;
            $apiKeySecret = $ctiSettings->api_key_secret;
        }

        $accountSid = $ctiSettings->account_sid;
        $twimlAppSid = $ctiSettings->twiml_app_sid ?: '';

        if (empty($twimlAppSid)) {
            throw new TokenGenerationException('Twilio TwiML Application SID is required for voice');
        }

        // Create identity based on user info
        $identity = $this->getIdentity();

        // Generate token
        $generator = new TokenGenerator($apiKeySid, $apiKeySecret, $accountSid, $twimlAppSid);

        $token = $generator
            ->setIdentity($identity)
            ->setTtl(3600) // 1 hour
            ->generateVoiceToken($identity);

        return [
            'token' => $token,
            'identity' => $identity,
            'expires_at' => $generator->getExpiration(),
            'account_sid' => $accountSid,
            'caller_id' => $ctiSettings->caller_id ?: '',
            'agent_phone' => $ctiSettings->agent_phone_number ?: '',
        ];
    }

    /**
     * Get identity for the token
     *
     * @return string Agent identity
     */
    private function getIdentity()
    {
        // Create a unique identity for this user
        // Format: user_{user_id}_{username}
        $identity = sprintf(
            'user_%s_%s',
            substr($this->currentUser->id, 0, 8),
            $this->sanitizeIdentity($this->currentUser->user_name)
        );

        return $identity;
    }

    /**
     * Sanitize string for use as identity
     *
     * @param string $str
     * @return string
     */
    private function sanitizeIdentity($str)
    {
        // Remove spaces and special characters, keep alphanumeric and underscore
        return preg_replace('/[^a-zA-Z0-9_]/', '', $str);
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Send unauthorized (401) response
     *
     * @return void
     */
    private function sendUnauthorizedResponse()
    {
        header('Content-Type: application/json');
        header('HTTP/1.1 401 Unauthorized');

        $response = [
            'success' => false,
            'error' => 'Unauthorized',
            'message' => 'Authentication required. Please log in.',
        ];

        echo json_encode($response);
        exit;
    }

    /**
     * Send error response
     *
     * @param string $message Error message
     * @param int $httpCode HTTP status code
     * @return void
     */
    private function sendErrorResponse($message, $httpCode = 400)
    {
        header('Content-Type: application/json');
        header("HTTP/1.1 {$httpCode}");

        $response = [
            'success' => false,
            'error' => $message,
        ];

        if (!$this->isAjax) {
            // For non-AJAX requests, wrap in HTML
            $this->renderErrorPage($message, $httpCode);
        } else {
            echo json_encode($response);
        }

        exit;
    }

    /**
     * Send success response
     *
     * @param array $data Token data
     * @return void
     */
    private function sendSuccessResponse(array $data)
    {
        header('Content-Type: application/json');
        header('HTTP/1.1 200 OK');

        $response = [
            'success' => true,
            'data' => $data,
        ];

        if (!$this->isAjax) {
            // For non-AJAX requests, render a debug page
            $this->renderTokenPage($data);
        } else {
            echo json_encode($response);
        }

        $GLOBALS['log']->debug(
            'SweetDialer: Token generated for user ' . $this->currentUser->user_name
        );

        exit;
    }

    /**
     * Render error page for browser requests
     *
     * @param string $message
     * @param int $httpCode
     * @return void
     */
    private function renderErrorPage($message, $httpCode)
    {
        $title = $httpCode === 401 ? 'Authentication Required' : 'Error';

        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>{$title} - SweetDialer</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .error-code { color: #d9534f; font-size: 72px; margin: 0; }
        .error-message { color: #666; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">{$httpCode}</h1>
        <h2>{$title}</h2>
        <p class="error-message">{$message}</p>
        <p><a href="index.php">Return to Dashboard</a></p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render token page for browser requests
     *
     * @param array $data
     * @return void
     */
    private function renderTokenPage(array $data)
    {
        $identity = htmlspecialchars($data['identity']);
        $tokenPreview = substr($data['token'], 0, 50) . '...';

        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Token Generated - SweetDialer</title>
    <style>
        body { font-family: monospace; padding: 40px; background: #f5f5f5; }
        .token-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .token-display { background: #f0f0f0; padding: 15px; border-radius: 4px; word-break: break-all; font-size: 12px; }
        .info { color: #666; }
    </style>
</head>
<body>
    <div class="token-container">
        <h1>✓ Token Generated</h1>
        <p class="info">Identity: {$identity}</p>
        <p class="info">Token preview (truncated for display):</p>
        <div class="token-display">{$tokenPreview}</div>
        <p><a href="index.php">Return to Dashboard</a></p>
    </div>
</body>
</html>
HTML;
    }
}

// Handle the request
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $endpoint = new SweetDialerTokenEndpoint();
    $endpoint->handle();
}
