<?php
/**
 * SweetDialer Base Controller - SuiteCRM 8.x Compatibility Layer
 * Wraps legacy entry points for Symfony routing
 */

namespace Wembassy\SweetDialer\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Base controller with error handling and compatibility checks
 */
abstract class BaseController extends AbstractController
{
    /**
     * Execute legacy entry point with error handling
     * @param string \$entryPointFile Path to legacy entry point
     * @param Request \$request Symfony request
     * @return Response
     */
    protected function executeLegacyEntryPoint(string \$entryPointFile, Request \$request): Response
    {
        try {
            // Check if legacy file exists
            \$legacyPath = 'custom/entrypoints/' . \$entryPointFile;
            \$fullPath = \$_SERVER['DOCUMENT_ROOT'] . '/' . \$legacyPath;
            
            if (!file_exists(\$fullPath)) {
                \$this->logger->error('SweetDialer: Legacy entry point not found: ' . \$entryPointFile);
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Entry point not configured'
                ], 404);
            }
            
            // Set up legacy globals
            \$_POST = array_merge(\$_POST, \$request->request->all());
            \$_GET = array_merge(\$_GET, \$request->query->all());
            
            // Capture output
            ob_start();
            
            try {
                // Define sugarEntry
                if (!defined('sugarEntry')) {
                    define('sugarEntry', true);
                }
                
                // Load SugarCRM
                require_once \$_SERVER['DOCUMENT_ROOT'] . '/include/utils.php';
                require_once \$_SERVER['DOCUMENT_ROOT'] . '/include/entryPoint.php';
                
                // Include legacy entry point
                require_once \$fullPath;
                
            } catch (\Exception \$e) {
                \$this->logger->error('SweetDialer: Legacy execution error: ' . \$e->getMessage());
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Internal error occurred'
                ], 500);
            }
            
            \$output = ob_get_clean();
            
            // Detect if output is JSON
            if (\$this->isJson(\$output)) {
                return new Response(\$output, 200, ['Content-Type' => 'application/json']);
            }
            
            // Otherwise return as-is
            return new Response(\$output);
            
        } catch (\Exception \$e) {
            \$this->logger->error('SweetDialer: Controller error: ' . \$e->getMessage());
            return new JsonResponse([
                'success' => false,
                'error' => 'Server error'
            ], 500);
        }
    }
    
    /**
     * Safe JSON decode with error handling
     */
    protected function safeJsonDecode(string \$data): ?array
    {
        try {
            \$decoded = json_decode(\$data, true);
            return (json_last_error() === JSON_ERROR_NONE) ? \$decoded : null;
        } catch (\Exception \$e) {
            return null;
        }
    }
    
    /**
     * Check if string is valid JSON
     */
    protected function isJson(string \$string): bool
    {
        json_decode(\$string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Validate Twilio webhook signature
     */
    protected function validateTwilioSignature(Request \$request): bool
    {
        \$signature = \$request->headers->get('X-Twilio-Signature');
        \$url = \$request->getSchemeAndHttpHost() . \$request->getPathInfo();
        
        // Load auth token from config
        \$authToken = \$this->getAuthToken();
        
        if (empty(\$authToken)) {
            return false;
        }
        
        // Build expected signature
        \$expected = base64_encode(hash_hmac('sha1', \$url, \$authToken, true));
        
        return hash_equals(\$expected, \$signature);
    }
    
    /**
     * Get Twilio auth token
     */
    private function getAuthToken(): string
    {
        try {
            \$bean = \BeanFactory::getBean('OutrCtiSettings');
            \$settings = \$bean->get_full_list();
            if (!empty(\$settings) && !empty(\$settings[0])) {
                return \$settings[0]->twilio_auth_token ?? '';
            }
        } catch (\Exception \$e) {
            // Silent fail
        }
        return '';
    }
    
    /**
     * Build safe response with CORS headers
     */
    protected function buildResponse(\$data, int \$status = 200): Response
    {
        \$response = new JsonResponse(\$data, \$status);
        \$response->headers->set('Access-Control-Allow-Origin', '*');
        \$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        \$response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Twilio-Signature');
        \$response->headers->set('X-Frame-Options', 'DENY');
        \$response->headers->set('X-Content-Type-Options', 'nosniff');
        return \$response;
    }
}
