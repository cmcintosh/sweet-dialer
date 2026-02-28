<?php
/**
 * AjaxPhoneNumberHandler.php
 *
 * Sweet-Dialer AJAX Handler for Auto-Fetching Phone Numbers
 *
 * Handles AJAX requests to fetch phone numbers from Twilio for populating
 * dropdown fields in the CTI Settings edit form.
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    define('sugarEntry', true);
}

require_once 'include/entryPoint.php';
require_once 'custom/include/TwilioDialer/TwilioApiClient.php';
require_once 'custom/include/TwilioDialer/CredentialEncryption.php';

/**
 * AjaxPhoneNumberHandler
 *
 * Handles AJAX requests for phone number fetching and caching
 */
class AjaxPhoneNumberHandler
{
    /** @var string Cache key prefix */
    const CACHE_PREFIX = 'sweetdialer_phonenumbers_';

    /** @var int Cache TTL in seconds (5 minutes) */
    const CACHE_TTL = 300;

    /**
     * Handle the incoming AJAX request
     *
     * @return void Outputs JSON response
     */
    public function handleRequest()
    {
        // Verify this is an AJAX request
        if (!$this->isAjaxRequest()) {
            $this->sendErrorResponse('Invalid request type', 400);
            return;
        }

        // Get request parameters
        $accountSid = $_POST['account_sid'] ?? $_GET['account_sid'] ?? '';
        $authToken = $_POST['auth_token'] ?? $_GET['auth_token'] ?? '';
        $forceRefresh = isset($_POST['force_refresh']) || isset($_GET['force_refresh']);

        // Validate required parameters (S-039)
        if (empty($accountSid) || empty($authToken)) {
            $this->sendErrorResponse('Account SID and Auth Token are required', 400);
            return;
        }

        // Sanitize inputs
        $accountSid = $this->sanitizeInput($accountSid);
        $authToken = $this->sanitizeInput($authToken);

        // Check cache if not forcing refresh
        if (!$forceRefresh) {
            $cached = $this->getCachedPhoneNumbers($accountSid);
            if ($cached !== null) {
                $this->sendSuccessResponse($cached, true);
                return;
            }
        }

        // Fetch from Twilio
        try {
            $client = new TwilioApiClient($accountSid, $authToken);
            $phoneNumbers = $client->fetchPhoneNumbers();

            // Cache the results
            $this->cachePhoneNumbers($accountSid, $phoneNumbers);

            $this->sendSuccessResponse($phoneNumbers, false);

        } catch (TwilioApiException $e) {
            $GLOBALS['log']->error('SweetDialer AJAX: Failed to fetch phone numbers - ' . $e->getMessage());
            $this->sendErrorResponse('Failed to fetch phone numbers: ' . $e->getMessage(), 502);
        } catch (Exception $e) {
            $GLOBALS['log']->error('SweetDialer AJAX: Unexpected error - ' . $e->getMessage());
            $this->sendErrorResponse('Unexpected error occurred', 500);
        }
    }

    /**
     * Check if current request is AJAX
     *
     * @return bool
     */
    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Sanitize input string
     *
     * @param string $input
     * @return string
     */
    private function sanitizeInput($input)
    {
        $input = trim($input);
        $input = strip_tags($input);
        return $input;
    }

    /**
     * Send success JSON response
     *
     * @param array $phoneNumbers Phone numbers array
     * @param bool $fromCache Whether result came from cache
     * @return void
     */
    private function sendSuccessResponse(array $phoneNumbers, $fromCache = false)
    {
        header('Content-Type: application/json');
        header('HTTP/1.1 200 OK');

        echo json_encode([
            'success' => true,
            'from_cache' => $fromCache,
            'count' => count($phoneNumbers),
            'phone_numbers' => $phoneNumbers,
        ]);

        exit;
    }

    /**
     * Send error JSON response
     *
     * @param string $message Error message
     * @param int $httpCode HTTP status code
     * @return void
     */
    private function sendErrorResponse($message, $httpCode = 400)
    {
        header('Content-Type: application/json');
        header("HTTP/1.1 {$httpCode}");

        echo json_encode([
            'success' => false,
            'error' => $message,
        ]);

        exit;
    }

    /**
     * Get cached phone numbers
     *
     * @param string $accountSid Account SID
     * @return array|null Cached phone numbers or null
     */
    private function getCachedPhoneNumbers($accountSid)
    {
        $cacheKey = $this->getCacheKey($accountSid);

        // Try SugarCache first
        if (class_exists('SugarCache')) {
            $cached = SugarCache::instance()->get($cacheKey);
            if ($cached !== null) {
                $data = unserialize($cached);
                if ($data !== false && isset($data['expires']) && $data['expires'] > time()) {
                    return $data['phone_numbers'];
                }
            }
        }

        // Fall back to file cache
        return $this->getFileCache($cacheKey);
    }

    /**
     * Cache phone numbers
     *
     * @param string $accountSid Account SID
     * @param array $phoneNumbers Phone numbers to cache
     * @return void
     */
    private function cachePhoneNumbers($accountSid, array $phoneNumbers)
    {
        $cacheKey = $this->getCacheKey($accountSid);
        $data = [
            'expires' => time() + self::CACHE_TTL,
            'phone_numbers' => $phoneNumbers,
        ];

        // Try SugarCache first
        if (class_exists('SugarCache')) {
            try {
                SugarCache::instance()->set($cacheKey, serialize($data), self::CACHE_TTL);
                return;
            } catch (Exception $e) {
                $GLOBALS['log']->debug('SweetDialer: SugarCache failed, using file cache');
            }
        }

        // Fall back to file cache
        $this->setFileCache($cacheKey, $data);
    }

    /**
     * Generate cache key for an account
     *
     * @param string $accountSid
     * @return string
     */
    private function getCacheKey($accountSid)
    {
        return self::CACHE_PREFIX . md5($accountSid);
    }

    /**
     * Get cached data from file
     *
     * @param string $cacheKey
     * @return array|null
     */
    private function getFileCache($cacheKey)
    {
        $cacheDir = sugar_cached('sweetdialer/');
        $cacheFile = $cacheDir . $cacheKey . '.cache';

        if (!file_exists($cacheFile)) {
            return null;
        }

        $data = unserialize(file_get_contents($cacheFile));

        if ($data === false || !isset($data['expires'])) {
            return null;
        }

        // Check expiration
        if ($data['expires'] < time()) {
            unlink($cacheFile);
            return null;
        }

        return $data['phone_numbers'] ?? null;
    }

    /**
     * Save cache data to file
     *
     * @param string $cacheKey
     * @param array $data
     * @return void
     */
    private function setFileCache($cacheKey, array $data)
    {
        $cacheDir = sugar_cached('sweetdialer/');

        if (!is_dir($cacheDir)) {
            sugar_mkdir($cacheDir, 0755);
        }

        $cacheFile = $cacheDir . $cacheKey . '.cache';
        file_put_contents($cacheFile, serialize($data), LOCK_EX);
    }

    /**
     * Clear cached phone numbers for an account
     *
     * @param string $accountSid
     * @return void
     */
    public function clearCache($accountSid)
    {
        $cacheKey = $this->getCacheKey($accountSid);

        // Clear SugarCache
        if (class_exists('SugarCache')) {
            SugarCache::instance()->clear($cacheKey);
        }

        // Clear file cache
        $cacheDir = sugar_cached('sweetdialer/');
        $cacheFile = $cacheDir . $cacheKey . '.cache';

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}

// Handle the request if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $handler = new AjaxPhoneNumberHandler();
    $handler->handleRequest();
}
