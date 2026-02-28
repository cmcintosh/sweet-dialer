<?php
/**
 * CredentialEncryption.php
 *
 * Sweet-Dialer Credential Encryption Handler
 *
 * Implements AES-256-CBC encryption for sensitive Twilio credentials.
 * Uses SuiteCRM's unique_key from sugar_config for key derivation.
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * CredentialEncryption
 *
 * Handles encryption and decryption of sensitive credential fields.
 * Fields encrypted: auth_token, api_key_secret
 */
class CredentialEncryption
{
    /** @var string Masked value placeholder for UI */
    const MASKED_VALUE = '********';

    /** @var string Encryption algorithm */
    const CIPHER_METHOD = 'AES-256-CBC';

    /** @var string Key derivation method for unique_key salting */
    const KEY_DERIVATION = 'sha256';

    /** @var string Encryption key derived from unique_key */
    private $encryptionKey;

    /** @var bool Whether encryption is available */
    private $enabled = true;

    /**
     * Constructor
     *
     * @param array $sugar_config SuiteCRM configuration array
     */
    public function __construct(array $sugar_config)
    {
        // Check if encryption is available
        if (!extension_loaded('openssl')) {
            $GLOBALS['log']->error('SweetDialer: OpenSSL extension not loaded. Encryption disabled.');
            $this->enabled = false;
            return;
        }

        // Derive encryption key from SuiteCRM's unique_key
        $uniqueKey = $sugar_config['unique_key'] ?? '';

        if (empty($uniqueKey)) {
            $GLOBALS['log']->error('SweetDialer: unique_key not found in sugar_config. Encryption disabled.');
            $this->enabled = false;
            return;
        }

        // Derive a 256-bit key using SHA-256
        // This ensures we have a fixed-length key from the variable-length unique_key
        $this->encryptionKey = hash(self::KEY_DERIVATION, $uniqueKey . 'SweetDialerSalt', true);
    }

    /**
     * Encrypt data using AES-256-CBC
     *
     * Format: base64(iv . encrypted_data . hmac)
     * - IV (16 bytes) + Encrypted Data + HMAC (32 bytes) in single base64 string
     *
     * @param string $data Plain text data to encrypt
     * @return string Base64-encoded encrypted data with IV and HMAC
     */
    public function encrypt($data)
    {
        if (!$this->enabled || empty($data)) {
            return $data;
        }

        try {
            // Generate a random 16-byte IV
            $iv = openssl_random_pseudo_bytes(16);

            if ($iv === false) {
                throw new Exception('Failed to generate initialization vector');
            }

            // Encrypt the data
            $encrypted = openssl_encrypt(
                $data,
                self::CIPHER_METHOD,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted === false) {
                throw new Exception('Encryption failed: ' . openssl_error_string());
            }

            // Create HMAC for integrity verification
            $hmac = hash_hmac('sha256', $iv . $encrypted, $this->encryptionKey, true);

            // Combine IV + encrypted data + HMAC and encode as base64
            $combined = $iv . $encrypted . $hmac;
            $encoded = base64_encode($combined);

            // Add prefix to identify encrypted data
            return 'ENC:' . $encoded;

        } catch (Exception $e) {
            $GLOBALS['log']->error('SweetDialer: Encryption error - ' . $e->getMessage());
            return $data; // Return original on error
        }
    }

    /**
     * Decrypt data that was encrypted with encrypt()
     *
     * @param string $data Base64-encoded encrypted data with IV and HMAC
     * @return string|false Decrypted plain text or false on failure
     */
    public function decrypt($data)
    {
        if (!$this->enabled || empty($data)) {
            return $data;
        }

        // Check if data is marked as encrypted
        if (strpos($data, 'ENC:') !== 0) {
            // Data is not encrypted (plain text)
            return $data;
        }

        // Remove the encryption prefix
        $data = substr($data, 4);

        try {
            // Decode from base64
            $combined = base64_decode($data, true);

            if ($combined === false) {
                throw new Exception('Base64 decode failed');
            }

            // Minimum length: 16 (IV) + at least 1 byte data + 32 (HMAC) = 49 bytes
            if (strlen($combined) < 49) {
                throw new Exception('Data too short to contain valid encrypted content');
            }

            // Extract components
            $iv = substr($combined, 0, 16);
            $hmac = substr($combined, -32);
            $encrypted = substr($combined, 16, -32);

            // Verify HMAC (constant-time comparison)
            $calculatedHmac = hash_hmac('sha256', $iv . $encrypted, $this->encryptionKey, true);

            if (!$this->hashEquals($hmac, $calculatedHmac)) {
                throw new Exception('HMAC verification failed - data may be tampered');
            }

            // Decrypt the data
            $decrypted = openssl_decrypt(
                $encrypted,
                self::CIPHER_METHOD,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                throw new Exception('Decryption failed: ' . openssl_error_string());
            }

            return $decrypted;

        } catch (Exception $e) {
            $GLOBALS['log']->error('SweetDialer: Decryption error - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if data is encrypted by our system
     *
     * @param string $data Data to check
     * @return bool True if encrypted with our prefix
     */
    public function isEncrypted($data)
    {
        if (empty($data)) {
            return false;
        }

        return strpos($data, 'ENC:') === 0;
    }

    /**
     * Check if encryption is available and working
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->enabled;
    }

    /**
     * Verify that the unique_key is valid for this installation
     * If unique_key changes, encrypted data will fail to decrypt
     *
     * @param array $sugar_config SuiteCRM config
     * @return bool
     */
    public function verifyKeyIntegrity($sugar_config)
    {
        $testData = 'SweetDialerIntegrityTest';
        $encrypted = $this->encrypt($testData);
        $decrypted = $this->decrypt($encrypted);

        return ($decrypted === $testData);
    }

    /**
     * Get masked value for UI display
     *
     * @param string $value Original value
     * @return string Masked value or empty string
     */
    public static function getMaskedValue($value)
    {
        if (empty($value)) {
            return '';
        }

        return self::MASKED_VALUE;
    }

    /**
     * Check if value is already masked
     *
     * @param string $value Value to check
     * @return bool
     */
    public static function isMasked($value)
    {
        return $value === self::MASKED_VALUE;
    }

    /**
     * Constant-time string comparison to prevent timing attacks
     *
     * @param string $a First string
     * @param string $b Second string
     * @return bool True if equal
     */
    private function hashEquals($a, $b)
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
}
