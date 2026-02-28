<?php
/**
 * CtiSettingsHooks.php
 *
 * Sweet-Dialer Logic Hooks for CTI Settings validation
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'custom/include/TwilioDialer/TwilioApiClient.php';
require_once 'custom/include/TwilioDialer/CredentialEncryption.php';

/**
 * CtiSettingsHooks
 *
 * Handles validation and encryption logic for CTI Settings records
 */
class CtiSettingsHooks
{
    /**
     * Validation messages to display to user
     *
     * @var array
     */
    private $validationMessages = [];

    /**
     * Whether validation passed
     *
     * @var bool
     */
    private $validationPassed = true;

    /**
     * before_save hook handler
     *
     * - Encrypts sensitive fields before saving
     * - Validates Twilio credentials
     * - Stores validation results in last_validation_message field
     *
     * @param Outr_CtiSettings $bean The CTI Settings bean being saved
     * @param string $event Event name
     * @param array $arguments Event arguments
     * @return void
     */
    public function beforeSave($bean, $event, $arguments)
    {
        global $sugar_config;

        // Handle encryption of sensitive fields
        $this->encryptSensitiveFields($bean, $sugar_config);

        // Validate credentials if we have the minimum required
        $this->validateCredentials($bean);

        // Store validation message
        if (!empty($this->validationMessages)) {
            $timestamp = date('Y-m-d H:i:s');
            $message = implode("\n", $this->validationMessages);

            if ($this->validationPassed) {
                $bean->last_validation_message = "PASSED ATTEMPT - {$timestamp}\n{$message}";
                $bean->last_validation_status = 'passed';
            } else {
                $bean->last_validation_message = "FAILED ATTEMPT - {$timestamp}\n{$message}";
                $bean->last_validation_status = 'failed';
            }
        }
    }

    /**
     * after_retrieve hook handler
     *
     * - Decrypts sensitive fields after retrieval
     * - Masks sensitive fields in edit view
     *
     * @param Outr_CtiSettings $bean The CTI Settings bean
     * @param string $event Event name
     * @param array $arguments Event arguments
     * @return void
     */
    public function afterRetrieve($bean, $event, $arguments)
    {
        global $sugar_config;

        // Decrypt sensitive fields
        $this->decryptSensitiveFields($bean, $sugar_config);

        // Mask sensitive fields (S-043)
        $this->maskSensitiveFields($bean);
    }

    /**
     * Encrypt sensitive fields before save
     *
     * @param Outr_CtiSettings $bean
     * @param array $sugar_config
     * @return void
     */
    private function encryptSensitiveFields($bean, $sugar_config)
    {
        $encryption = new CredentialEncryption($sugar_config);

        // Encrypt auth_token if it has changed (not masked value)
        if (!empty($bean->auth_token) && !$this->isMaskedValue($bean->auth_token)) {
            $bean->auth_token = $encryption->encrypt($bean->auth_token);
        } elseif ($this->isMaskedValue($bean->auth_token) || empty($bean->auth_token)) {
            // Preserve existing value if masked or empty
            $bean->auth_token = $this->getExistingValue('auth_token', $bean->id);
        }

        // Encrypt api_key_secret if it has changed (not masked value)
        if (!empty($bean->api_key_secret) && !$this->isMaskedValue($bean->api_key_secret)) {
            $bean->api_key_secret = $encryption->encrypt($bean->api_key_secret);
        } elseif ($this->isMaskedValue($bean->api_key_secret) || empty($bean->api_key_secret)) {
            // Preserve existing value if masked or empty
            $bean->api_key_secret = $this->getExistingValue('api_key_secret', $bean->id);
        }
    }

    /**
     * Decrypt sensitive fields after retrieval
     *
     * @param Outr_CtiSettings $bean
     * @param array $sugar_config
     * @return void
     */
    private function decryptSensitiveFields($bean, $sugar_config)
    {
        $encryption = new CredentialEncryption($sugar_config);

        // Decrypt auth_token
        if (!empty($bean->auth_token)) {
            $decrypted = $encryption->decrypt($bean->auth_token);
            if ($decrypted !== false) {
                $bean->auth_token = $decrypted;
            }
        }

        // Decrypt api_key_secret
        if (!empty($bean->api_key_secret)) {
            $decrypted = $encryption->decrypt($bean->api_key_secret);
            if ($decrypted !== false) {
                $bean->api_key_secret = $decrypted;
            }
        }
    }

    /**
     * Mask sensitive fields for UI display
     *
     * @param Outr_CtiSettings $bean
     * @return void
     */
    private function maskSensitiveFields($bean)
    {
        // Mask auth_token if it has a value
        if (!empty($bean->auth_token)) {
            $bean->auth_token = CredentialEncryption::MASKED_VALUE;
        }

        // Mask api_key_secret if it has a value
        if (!empty($bean->api_key_secret)) {
            $bean->api_key_secret = CredentialEncryption::MASKED_VALUE;
        }
    }

    /**
     * Check if a value is the masked placeholder
     *
     * @param string $value
     * @return bool
     */
    private function isMaskedValue($value)
    {
        return $value === CredentialEncryption::MASKED_VALUE;
    }

    /**
     * Get existing value from database
     *
     * @param string $field Field name
     * @param string $recordId Record ID
     * @return string|null
     */
    private function getExistingValue($field, $recordId)
    {
        if (empty($recordId)) {
            return null;
        }

        $db = DBManagerFactory::getInstance();
        $table = 'outr_ctisettings';
        $fieldQuoted = $db->quote($field);
        $idQuoted = $db->quoted($recordId);

        $query = "SELECT {$fieldQuoted} FROM {$table} WHERE id = {$idQuoted} AND deleted = 0";
        $result = $db->query($query);

        if ($row = $db->fetchByAssoc($result)) {
            return $row[$field] ?? null;
        }

        return null;
    }

    /**
     * Validate Twilio credentials
     *
     * @param Outr_CtiSettings $bean
     * @return void
     */
    private function validateCredentials($bean)
    {
        // Need at minimum Account SID and Auth Token to validate
        if (empty($bean->account_sid) || empty($bean->auth_token)) {
            $this->validationMessages[] = 'Account SID and Auth Token are required for validation';
            $this->validationPassed = false;
            return;
        }

        // Decrypt auth_token if it's encrypted for validation
        $authToken = $bean->auth_token;
        if (!empty($authToken) && strlen($authToken) > 100) {
            // Likely encrypted, try to decrypt
            global $sugar_config;
            $encryption = new CredentialEncryption($sugar_config);
            $decrypted = $encryption->decrypt($authToken);
            if ($decrypted !== false) {
                $authToken = $decrypted;
            }
        }

        $client = new TwilioApiClient($bean->account_sid, $authToken);

        // Validate account credentials (S-035)
        $accountResult = $client->validateCredentials();

        if ($accountResult['success']) {
            $this->validationMessages[] = "Account SID: {$accountResult['message']}";
        } else {
            $this->validationMessages[] = "Account SID: {$accountResult['message']}";
            $this->validationPassed = false;
        }

        // Log account validation
        $GLOBALS['log']->info(
            'SweetDialer: Account credential validation result - ' .
            ($accountResult['success'] ? 'PASSED' : 'FAILED') . ': ' .
            $accountResult['message']
        );

        // Validate API Key credentials if provided (S-036)
        if (!empty($bean->api_key_sid) && !empty($bean->api_key_secret)) {
            // Decrypt api_key_secret if it's encrypted
            $apiKeySecret = $bean->api_key_secret;
            if (!empty($apiKeySecret) && strlen($apiKeySecret) > 100) {
                // Likely encrypted, try to decrypt
                global $sugar_config;
                $encryption = new CredentialEncryption($sugar_config);
                $decrypted = $encryption->decrypt($apiKeySecret);
                if ($decrypted !== false) {
                    $apiKeySecret = $decrypted;
                }
            }

            $client->setApiKeyCredentials($bean->api_key_sid, $apiKeySecret);
            $apiKeyResult = $client->validateApiKeyCredentials();

            if ($apiKeyResult['success']) {
                $this->validationMessages[] = "API Key: {$apiKeyResult['message']}";
            } else {
                $this->validationMessages[] = "API Key: {$apiKeyResult['message']}";
                $this->validationPassed = false;
            }

            // Log API key validation
            $GLOBALS['log']->info(
                'SweetDialer: API Key credential validation result - ' .
                ($apiKeyResult['success'] ? 'PASSED' : 'FAILED') . ': ' .
                $apiKeyResult['message']
            );
        }

        // Set SugarFlashMessage for user feedback
        $this->setValidationFlashMessage();
    }

    /**
     * Set flash message based on validation result
     *
     * @return void
     */
    private function setValidationFlashMessage()
    {
        global $app_strings;

        if ($this->validationPassed) {
            SugarApplication::appendErrorMessage(
                <<<HTML
<div class="alert alert-success" role="alert">
    <strong>✓ Validation Passed</strong><br>
    {$this->escapeHtml(implode('<br>', $this->validationMessages))}
</div>
HTML
            );
        } else {
            SugarApplication::appendErrorMessage(
                <<<HTML
<div class="alert alert-warning" role="alert">
    <strong>⚠ Validation Warning</strong><br>
    Record saved, but credentials could not be validated:<br>
    {$this->escapeHtml(implode('<br>', $this->validationMessages))}
</div>
HTML
            );
        }
    }

    /**
     * Escape HTML entities in message
     *
     * @param string $text
     * @return string
     */
    private function escapeHtml($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
