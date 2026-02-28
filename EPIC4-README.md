# Epic 4: Twilio API Client & Credential Validation (38 points)

## Overview

This epic implements the core Twilio integration layer for Sweet-Dialer, including:
- REST API client with retry logic
- Credential validation and encryption
- Phone number auto-fetching
- Token generation for the Twilio Client SDK
- Secure credential masking in the UI

## Stories Implemented

### S-032: Create TwilioApiClient.php (5 pts) ✓
**Location:** `custom/include/TwilioDialer/TwilioApiClient.php`

Features:
- PHP class wrapping cURL calls to Twilio REST API
- Methods: `__construct()`, `get()`, `post()`
- HTTPS Basic Auth (supports both Account SID/Auth Token and API Key/Secret)
- Returns decoded JSON or throws `TwilioApiException` with Twilio error codes

### S-033: Add retry logic (3 pts) ✓
**Location:** `TwilioApiClient.php` - `makeRequest()` method

Features:
- Retries on HTTP 429 (rate limit) or 5xx responses
- Exponential backoff: 1s, 2s, 4s
- Logs each retry to `outr_twilio_logger`
- Max 3 retry attempts before giving up

### S-034: Add validateCredentials() method (3 pts) ✓
**Location:** `TwilioApiClient.php` - `validateCredentials()` method

Features:
- Calls `GET /2010-04-01/Accounts/{SID}.json`
- Verifies account SID and status
- Returns validation result with:
  - `success` (boolean)
  - `message` (string)
  - `verified_at` (timestamp)
  - `account_status` (string)

### S-035: Add before_save logic hook (5 pts) ✓
**Location:** `custom/modules/outr_CtiSettings/logic_hooks/CtiSettingsHooks.php`

Features:
- Validates credentials on CTI save
- On success: saves "PASSED ATTEMPT" with timestamp
- On failure: saves error message but allows record to save
- Displays green "PASSED" or red warning banner

### S-036: Extend hook to validate API Key credentials (3 pts) ✓
**Location:** `CtiSettingsHooks.php` - `validateCredentials()` method

Features:
- Validates API Key SID + Secret separately
- Both validation results shown in flash messages

### S-037: Add fetchPhoneNumbers() method (3 pts) ✓
**Location:** `TwilioApiClient.php` - `fetchPhoneNumbers()` method

Features:
- Calls `GET /2010-04-01/Accounts/{SID}/IncomingPhoneNumbers.json`
- Returns array with:
  - `sid`
  - `number`
  - `friendly_name`
  - `capabilities` (voice, sms, mms, fax)
  - `voice_url`, `sms_url`, `status_callback`

### S-038: Build "Twilio Phone Numbers" list view (5 pts) ✓
**Location:**
- `custom/modules/outr_CtiSettings/views/view.phonenumbers.php`
- `custom/modules/outr_CtiSettings/tpls/phonenumbers.tpl`

Features:
- Calls `fetchPhoneNumbers()` on page load
- Displays:
  - Phone Number
  - Friendly Name
  - Phone SID
  - Voice Capable (✓/✗)
  - SMS Capable (✓/✗)
  - Assignment Status (Assigned/Unassigned)

### S-039: Auto-fetch in CTI edit form (5 pts) ✓
**Location:** `custom/modules/outr_CtiSettings/js/sweetdialer_edit.js`

Features:
- Triggers on blur of Account SID or Auth Token fields
- AJAX endpoint: `sweetdialer_ajax_phone_numbers`
- Fetches and caches phone numbers (5 minute TTL)
- Populates dropdown for Agent Phone Number and Phone SID fields

### S-040: Create TokenGenerator.php (5 pts) ✓
**Location:** `custom/include/TwilioDialer/TokenGenerator.php`

Features:
- Generates Twilio Client access tokens
- Uses API Key SID, API Key Secret, Account SID, TwiML App SID
- Includes `VoiceGrant` with TwiML App SID and agent identity
- TTL set to 3600 seconds (1 hour)
- Generates valid JWT format tokens

### S-041: Create tokenEndpoint.php (3 pts) ✓
**Location:** `custom/entrypoints/tokenEndpoint.php`

Features:
- Registered in entry point registry
- Authenticates SuiteCRM session
- Looks up user's active CTI settings
- Returns JSON with access token
- Returns 401 for unauthenticated requests

### S-042: Implement encryption at rest (5 pts) ✓
**Location:** `custom/include/TwilioDialer/CredentialEncryption.php`

Features:
- Encrypts: `auth_token`, `api_key_secret`
- Uses `openssl_encrypt()` with AES-256-CBC
- Key derived from `$sugar_config['unique_key']`
- Encrypts on `before_save`, decrypts on `after_retrieve`
- Format: `ENC:base64(iv + encrypted + hmac)`
- HMAC for integrity verification

### S-043: Mask sensitive fields in edit view (2 pts) ✓
**Location:**
- `custom/include/SugarFields/Fields/MaskedPassword/SugarFieldMaskedPassword.php`
- `custom/modules/outr_CtiSettings/js/sweetdialer_edit.js`
- `custom/themes/default/css/sweetdialer.css`

Features:
- Auth Token and API Key Secret show as `********`
- "Change" button reveals input field
- Clicking "Change" clears field for new input
- Blank on save preserves existing value

## Files Created

### Core API Client
```
custom/include/TwilioDialer/
├── TwilioApiClient.php          # Main API client (S-032, S-033, S-034, S-037)
├── CredentialEncryption.php       # AES-256-CBC encryption (S-042)
├── TokenGenerator.php           # JWT token generation (S-040)
├── AjaxPhoneNumberHandler.php   # AJAX handler (S-039)
└── services/
    └── (placeholder for additional services)
```

### Module Files
```
custom/modules/outr_CtiSettings/
├── logic_hooks/
│   ├── CtiSettingsHooks.php     # before_save, after_retrieve hooks (S-035, S-036)
│   └── logic_hooks.php          # Hook registration
├── views/
│   └── view.phonenumbers.php    # Phone numbers list view (S-038)
├── tpls/
│   └── phonenumbers.tpl         # Phone numbers template (S-038)
└── js/
    └── sweetdialer_edit.js      # AJAX phone number fetching (S-039)
```

### Entry Points
```
custom/
├── entrypoints/
│   └── tokenEndpoint.php        # Token generation endpoint (S-041)
└── Extension/application/Ext/EntryPointRegistry/
    ├── SweetDialerTokenEndpoint.php     # Token EP registration
    └── SweetDialerAjaxEndpoint.php      # AJAX EP registration
```

### SugarFields
```
custom/include/SugarFields/Fields/MaskedPassword/
└── SugarFieldMaskedPassword.php # Masked password field (S-043)
```

### Styles
```
custom/themes/default/css/
└── sweetdialer.css              # UI styles (S-043)
```

## Security Features

1. **Encryption at Rest**
   - AES-256-CBC encryption
   - Key derived from SuiteCRM unique_key
   - HMAC verification for integrity

2. **Credential Masking**
   - Sensitive fields masked in UI
   - Change button workflow for updates
   - Original values preserved when blank

3. **JWT Token Security**
   - Short-lived tokens (1 hour TTL)
   - Identity-based access control
   - Session authentication required

4. **API Security**
   - HTTPS only
   - Basic Auth with credentials
   - Input sanitization
   - Error message sanitization

## Technical Requirements

### PHP Extensions
- OpenSSL (for encryption)
- cURL (for API requests)

### Composer Dependencies (Recommended)
```bash
composer require firebase/php-jwt:^6.0
```

Install to: `custom/include/TwilioDialer/vendor/`

## Testing

### API Client
```php
$client = new TwilioApiClient($accountSid, $authToken);
$result = $client->validateCredentials();
$numbers = $client->fetchPhoneNumbers();
```

### Token Generation
```php
$generator = new TokenGenerator($apiKeySid, $apiKeySecret, $accountSid, $twimlAppSid);
$token = $generator->setIdentity('user_123')->generateVoiceToken('user_123');
```

### Encryption
```php
$encryption = new CredentialEncryption($sugar_config);
$encrypted = $encryption->encrypt($secret);
$decrypted = $encryption->decrypt($encrypted);
```

## Acceptance Criteria Status

- [x] TwilioApiClient can authenticate and make API calls with retry logic
- [x] Credentials validated on CTI save with visual feedback
- [x] Phone numbers auto-fetched from Twilio and displayed
- [x] Access tokens generated in valid JWT format for Twilio Client SDK
- [x] Sensitive fields encrypted at rest and masked in UI

## Git Workflow

```bash
# Branch created from develop
git checkout -b epic/e4-twilio-api develop

# Commits:
# S-032, S-033, S-034: Create TwilioApiClient with retry logic and credential validation
# S-035, S-036, S-042: Add CTI Settings logic hooks, credential validation, and encryption
# S-037, S-038, S-039, S-040, S-041: Add phone number views, AJAX handler, and token generation
# S-043: Add masked password fields, JavaScript, and CSS styles
```

## Notes for Epic 5 (Webhooks) and Epic 6 (Calling)

This epic provides the foundation:
- `TwilioApiClient` will be used for webhook validation
- `TokenGenerator` will be used for client-side calling
- `CredentialEncryption` ensures secure credential storage

## License

GNU AGPLv3 - Same as SuiteCRM
