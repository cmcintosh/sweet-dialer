<?php
/**
 * voiceDTMF.php
 *
 * Sweet-Dialer Voice DTMF Webhook Handler
 *
 * Handles DTMF key presses from Gather verb for IVR navigation.
 *
 * URL: /entryPoint.php?entryPoint=sweetdialer_voice_dtmf
 * Mapped to: /twilio/voice/dtmf
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    define('sugarEntry', true);
}

require_once 'include/entryPoint.php';
require_once 'custom/include/TwilioDialer/WebhookHandler.php';

/**
 * VoiceDTMFHandler
 *
 * S-053: Receives gathered DTMF digits from Gather
 * Forwards appropriately for IVR navigation
 */
class VoiceDTMFHandler extends WebhookHandler
{
    /** @var array Valid DTMF actions */
    private $validActions = [
        'menu',           // Main IVR menu navigation
        'transfer',       // Transfer to department
        'voicemail',      // Leave voicemail
        'callback',       // Request callback
        'extension',      // Dial by extension
        'recording',      // Recording controls
        'conference',     // Conference access
        'hold',           // Place on hold
        'hangup',         // End call
    ];

    /** @var array Extension mappings */
    private $extensionMappings = [];

    /**
     * Get the endpoint name
     *
     * @return string
     */
    protected function getEndpointName()
    {
        return 'dtmf';
    }

    /**
     * Process the DTMF request
     *
     * S-053: Receive DTMF digits and forward for IVR navigation
     *
     * @return TwiMLResponse
     * @throws Exception
     */
    protected function processRequest()
    {
        // Get DTMF data from Twilio
        $digits = $this->getParam('Digits');
        $callSid = $this->getParam('CallSid');
        $fromNumber = $this->getParam('From');
        $toNumber = $this->getParam('To');
        $dtmfAction = $this->getParam('DtmfAction', 'menu'); // What action triggered this

        // Call details from previous gather
        $previousMenu = $this->getParam('PreviousMenu', 'main');

        if (empty($digits)) {
            $this->logger->warn('SweetDialer DTMF: No digits received');
            return $this->generateErrorResponse('No input received');
        }

        $this->logger->info(sprintf(
            'SweetDialer DTMF: Received digits - CallSid: %s, Action: %s, Digits: %s, Menu: %s',
            $callSid,
            $dtmfAction,
            $digits,
            $previousMenu
        ));

        // Log the DTMF press
        $this->logDtmfPress($callSid, $digits, $dtmfAction, $fromNumber);

        // Process based on action type
        switch ($dtmfAction) {
            case 'menu':
                return $this->handleMenuNavigation($digits, $previousMenu, $callSid, $fromNumber);

            case 'extension':
                return $this->handleExtensionDial($digits, $callSid, $fromNumber);

            case 'transfer':
                return $this->handleTransferRequest($digits, $callSid, $fromNumber);

            case 'voicemail':
                return $this->handleVoicemailRequest($digits, $fromNumber);

            case 'recording':
                return $this->handleRecordingControl($digits, $callSid);

            case 'conference':
                return $this->handleConferenceAccess($digits, $callSid, $fromNumber);

            default:
                return $this->handleMenuNavigation($digits, $previousMenu, $callSid, $fromNumber);
        }
    }

    /**
     * Handle main menu navigation
     *
     * @param string $digits
     * @param string $previousMenu
     * @param string $callSid
     * @param string $fromNumber
     * @return TwiMLResponse
     */
    private function handleMenuNavigation($digits, $previousMenu, $callSid, $fromNumber)
    {
        $response = new TwiMLResponse();

        // Load IVR configuration for this menu
        $ivrConfig = $this->getIvrConfig($previousMenu);

        // Find the menu option for these digits
        $option = $this->findMenuOption($previousMenu, $digits);

        if ($option) {
            $this->logger->info(sprintf(
                'SweetDialer DTMF: Menu option selected - Menu: %s, Digits: %s, Action: %s',
                $previousMenu,
                $digits,
                $option['action']
            ));

            switch ($option['action']) {
                case 'dial_agent':
                    // Dial specific agent
                    $response->say('Connecting you...', ['voice' => 'Polly.Joanna']);
                    $response->dial(
                        $option['target'],
                        [
                            'action' => $this->buildWebhookUrl('sweetdialer_voice_voicemail'),
                            'timeout' => 30,
                        ],
                        'client'
                    );
                    break;

                case 'transfer':
                    // Transfer to department/queue
                    $response->redirect(
                        $this->buildUrl('sweetdialer_voice_transfer', [
                            'TransferType' => 'client',
                            'TransferTo' => $option['target'],
                            'CallSid' => $callSid,
                        ]),
                        ['method' => 'POST']
                    );
                    break;

                case 'voicemail':
                    // Go to voicemail
                    $response->redirect(
                        $this->buildUrl('sweetdialer_voice_voicemail', [
                            'From' => $fromNumber,
                        ]),
                        ['method' => 'POST']
                    );
                    break;

                case 'extension':
                    // Prompt for extension
                    return $this->handleExtensionPrompt($response);

                case 'hold':
                    // Place on hold
                    $response->redirect(
                        $this->buildUrl('sweetdialer_voice_hold', [
                            'From' => $fromNumber,
                        ]),
                        ['method' => 'POST']
                    );
                    break;

                case 'submenu':
                    // Navigate to submenu
                    return $this->renderMenu($option['target']);

                case 'hangup':
                    // End call
                    $response->say('Thank you for calling. Goodbye.', ['voice' => 'Polly.Joanna']);
                    $response->hangup();
                    break;

                case 'repeat':
                    // Repeat current menu
                    return $this->renderMenu($previousMenu);

                case 'operator':
                    // Transfer to operator
                    $operator = $this->findOperator();
                    if ($operator) {
                        $response->say('Connecting you to an operator...', ['voice' => 'Polly.Joanna']);
                        $response->dial(
                            $operator,
                            [
                                'action' => $this->buildWebhookUrl('sweetdialer_voice_voicemail'),
                                'timeout' => 30,
                            ],
                            'client'
                        );
                    } else {
                        $response->say('No operators are available. Please leave a message.', ['voice' => 'Polly.Joanna']);
                        $response->redirect(
                            $this->buildUrl('sweetdialer_voice_voicemail'),
                            ['method' => 'POST']
                        );
                    }
                    break;

                default:
                    // Unknown action, repeat menu
                    $response->say('That option is not available.', ['voice' => 'Polly.Joanna']);
                    return $this->renderMenu($previousMenu);
            }
        } else {
            // Invalid option
            $this->logger->warn(sprintf(
                'SweetDialer DTMF: Invalid menu option - Menu: %s, Digits: %s',
                $previousMenu,
                $digits
            ));

            $response->say('I did not understand that selection.', ['voice' => 'Polly.Joanna']);
            $response->pause(1);
            return $this->renderMenu($previousMenu);
        }

        return $response;
    }

    /**
     * Handle extension dialing
     *
     * @param string $digits
     * @param string $callSid
     * @param string $fromNumber
     * @return TwiMLResponse
     */
    private function handleExtensionDial($digits, $callSid, $fromNumber)
    {
        $response = new TwiMLResponse();

        // Look up extension
        $agent = $this->findAgentByExtension($digits);

        if ($agent) {
            $response->say('Connecting you to extension ' . $digits, ['voice' => 'Polly.Joanna']);
            $response->dial(
                $agent,
                [
                    'action' => $this->buildWebhookUrl('sweetdialer_voice_voicemail'),
                    'timeout' => 30,
                ],
                'client'
            );
        } else {
            $response->say('That extension is not available.', ['voice' => 'Polly.Joanna']);
            $response->pause(1);
            return $this->renderMenu('main');
        }

        return $response;
    }

    /**
     * Handle extension prompt (gather digits)
     *
     * @param TwiMLResponse $response
     * @return TwiMLResponse
     */
    private function handleExtensionPrompt($response)
    {
        $response->say('Please enter the extension number.', ['voice' => 'Polly.Joanna']);

        $response->gather(null, [
            'action' => $this->buildUrl('sweetdialer_voice_dtmf', [
                'DtmfAction' => 'extension',
            ]),
            'method' => 'POST',
            'numDigits' => '4',
            'timeout' => 5,
        ]);

        // If no input, return to main menu
        $response->redirect(
            $this->buildUrl('sweetdialer_voice_dtmf', [
                'PreviousMenu' => 'main',
            ]),
            ['method' => 'POST']
        );

        return $response;
    }

    /**
     * Handle transfer request
     *
     * @param string $digits
     * @param string $callSid
     * @param string $fromNumber
     * @return TwiMLResponse
     */
    private function handleTransferRequest($digits, $callSid, $fromNumber)
    {
        $response = new TwiMLResponse();

        // Map digits to department/destination
        $destination = $this->getTransferDestination($digits);

        if ($destination) {
            $response->redirect(
                $this->buildUrl('sweetdialer_voice_transfer', [
                    'TransferType' => $destination['type'],
                    'TransferTo' => $destination['target'],
                    'CallSid' => $callSid,
                ]),
                ['method' => 'POST']
            );
        } else {
            $response->say('That selection is not valid.', ['voice' => 'Polly.Joanna']);
            $response->pause(1);
            return $this->renderMenu('main');
        }

        return $response;
    }

    /**
     * Handle voicemail request
     *
     * @param string $digits
     * @param string $fromNumber
     * @return TwiMLResponse
     */
    private function handleVoicemailRequest($digits, $fromNumber)
    {
        $response = new TwiMLResponse();

        // Confirm voicemail
        $response->say('Connecting you to voicemail.', ['voice' => 'Polly.Joanna']);
        $response->redirect(
            $this->buildUrl('sweetdialer_voice_voicemail', [
                'From' => $fromNumber,
            ]),
            ['method' => 'POST']
        );

        return $response;
    }

    /**
     * Handle recording control keys
     *
     * @param string $digits
     * @param string $callSid
     * @return TwiMLResponse
     */
    private function handleRecordingControl($digits, $callSid)
    {
        $response = new TwiMLResponse();

        // Common recording controls
        switch ($digits) {
            case '8':
                // Pause/resume recording
                $response->say('Recording paused.', ['voice' => 'Polly.Joanna']);
                break;
            case '9':
                // Resume recording
                $response->say('Recording resumed.', ['voice' => 'Polly.Joanna']);
                break;
            default:
                $response->say('Invalid command.', ['voice' => 'Polly.Joanna']);
        }

        return $response;
    }

    /**
     * Handle conference access codes
     *
     * @param string $digits
     * @param string $callSid
     * @param string $fromNumber
     * @return TwiMLResponse
     */
    private function handleConferenceAccess($digits, $callSid, $fromNumber)
    {
        $response = new TwiMLResponse();

        // Validate conference PIN
        $conference = $this->validateConferencePin($digits);

        if ($conference) {
            $response->say('Connecting you to the conference.', ['voice' => 'Polly.Joanna']);
            // Conference dialing would go here
        } else {
            $response->say('Invalid access code.', ['voice' => 'Polly.Joanna']);
            $response->hangup();
        }

        return $response;
    }

    /**
     * Render a menu
     *
     * @param string $menuName
     * @return TwiMLResponse
     */
    private function renderMenu($menuName)
    {
        $response = new TwiMLResponse();
        $menu = $this->getIvrConfig($menuName);

        // Play menu greeting and options
        $this->playMenuContent($response, $menu);

        // Gather input
        $maxDigits = $menu['max_digits'] ?? 1;
        $response->gather(null, [
            'action' => $this->buildUrl('sweetdialer_voice_dtmf', [
                'PreviousMenu' => $menuName,
                'DtmfAction' => 'menu',
            ]),
            'method' => 'POST',
            'numDigits' => $maxDigits,
            'timeout' => $menu['timeout'] ?? 5,
        ]);

        // If no input after timeout
        $response->say('I did not receive a response.', ['voice' => 'Polly.Joanna']);
        $response->redirect(
            $this->buildUrl('sweetdialer_voice_voicemail'),
            ['method' => 'POST']
        );

        return $response;
    }

    /**
     * Play menu content
     *
     * @param TwiMLResponse $response
     * @param array $menu
     * @return void
     */
    private function playMenuContent($response, array $menu)
    {
        // Play greeting
        if (!empty($menu['greeting_mp3'])) {
            $response->play($menu['greeting_mp3']);
        } else {
            $greeting = $menu['greeting'] ?? 'Welcome. Please select an option.';
            $response->say($greeting, ['voice' => $menu['voice'] ?? 'Polly.Joanna']);
        }

        // Play options
        foreach ($menu['options'] as $key => $option) {
            $prompt = $option['prompt'] ?? "Press {$key} for {$option['label']}";
            $response->say($prompt, ['voice' => $menu['voice'] ?? 'Polly.Joanna']);
        }
    }

    /**
     * Get IVR configuration
     *
     * @param string $menuName
     * @return array
     */
    private function getIvrConfig($menuName)
    {
        // Default main menu configuration
        $defaults = [
            'main' => [
                'greeting' => 'Thank you for calling. Press 1 for Sales, 2 for Support, 3 to leave a voicemail, or 9 for an operator.',
                'voice' => 'Polly.Joanna',
                'max_digits' => 1,
                'timeout' => 5,
                'options' => [
                    '1' => ['action' => 'transfer', 'target' => 'sales_queue', 'label' => 'Sales'],
                    '2' => ['action' => 'transfer', 'target' => 'support_queue', 'label' => 'Support'],
                    '3' => ['action' => 'voicemail', 'label' => 'Voicemail'],
                    '9' => ['action' => 'operator', 'label' => 'Operator'],
                    '*' => ['action' => 'repeat', 'label' => 'Repeat Menu'],
                    '#' => ['action' => 'hangup', 'label' => 'Hang Up'],
                ],
            ],
        ];

        // TODO: Load from database
        return $defaults[$menuName] ?? $defaults['main'];
    }

    /**
     * Find menu option
     *
     * @param string $menuName
     * @param string $digits
     * @return array|null
     */
    private function findMenuOption($menuName, $digits)
    {
        $menu = $this->getIvrConfig($menuName);
        return $menu['options'][$digits] ?? null;
    }

    /**
     * Find agent by extension
     *
     * @param string $extension
     * @return string|null
     */
    private function findAgentByExtension($extension)
    {
        // TODO: Query users table for extension
        // Return client identity if found
        return null;
    }

    /**
     * Get transfer destination
     *
     * @param string $digits
     * @return array|null
     */
    private function getTransferDestination($digits)
    {
        $destinations = [
            '1' => ['type' => 'client', 'target' => 'sales_queue'],
            '2' => ['type' => 'client', 'target' => 'support_queue'],
            '3' => ['type' => 'voicemail', 'target' => 'general'],
        ];

        return $destinations[$digits] ?? null;
    }

    /**
     * Validate conference PIN
     *
     * @param string $pin
     * @return array|null
     */
    private function validateConferencePin($pin)
    {
        // TODO: Query conferences table
        return null;
    }

    /**
     * Find available operator
     *
     * @return string|null
     */
    private function findOperator()
    {
        // TODO: Find an available operator
        return null;
    }

    /**
     * Log DTMF press
     *
     * @param string $callSid
     * @param string $digits
     * @param string $action
     * @param string $fromNumber
     * @return void
     */
    private function logDtmfPress($callSid, $digits, $action, $fromNumber)
    {
        try {
            // Could log to a dtmf_log table for analytics
            $this->logger->debug(sprintf(
                'DTMF Log: CallSid: %s, Digits: %s, Action: %s, From: %s',
                $callSid,
                $digits,
                $action,
                $fromNumber
            ));

        } catch (Exception $e) {
            // Non-critical, just debug
        }
    }

    /**
     * Build webhook URL
     *
     * @param string $entryPoint
     * @return string
     */
    private function buildWebhookUrl($entryPoint)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

        return $protocol . '://' . $host . '/entryPoint.php?entryPoint=' . $entryPoint;
    }

    /**
     * Build URL with parameters
     *
     * @param string $entryPoint
     * @param array $params
     * @return string
     */
    private function buildUrl($entryPoint, array $params = [])
    {
        $url = $this->buildWebhookUrl($entryPoint);

        if (!empty($params)) {
            $url .= '&' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Generate error response
     *
     * @param string $message
     * @return TwiMLResponse
     */
    private function generateErrorResponse($message)
    {
        $response = new TwiMLResponse();
        $response->say('There was a problem. Please try again.', ['voice' => 'Polly.Joanna']);
        $response->pause(1);
        return $this->renderMenu('main');
    }
}

// Handle the request
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $handler = new VoiceDTMFHandler();
    $handler->handle();
}
