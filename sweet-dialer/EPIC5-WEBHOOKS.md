# Sweet-Dialer Webhooks Epic 5

## Overview

Epic 5 implements all 8 Twilio webhook endpoints for voice calling functionality, complete with signature validation, TwiML responses, and CRM integration.

## Webhook Endpoints

All webhooks are registered in SuiteCRM's entry point registry and handle Twilio voice requests securely.

| Entry Point | File | Path | Purpose |
|-------------|------|------|---------|
| `sweetdialer_voice_outbound` | `voiceOutbound.php` | `/twilio/voice/outbound` | Outbound PSTN calls from Client SDK |
| `sweetdialer_voice_inbound` | `voiceInbound.php` | `/twilio/voice/inbound` | Inbound calls from PSTN to Clients |
| `sweetdialer_voice_status` | `voiceStatus.php` | `/twilio/voice/status` | Call status callbacks |
| `sweetdialer_voice_recording` | `voiceRecording.php` | `/twilio/voice/recording` | Recording status callbacks |
| `sweetdialer_voice_voicemail` | `voiceVoicemail.php` | `/twilio/voice/voicemail` | Voicemail recording and playback |
| `sweetdialer_voice_hold` | `voiceHold.php` | `/twilio/voice/hold` | Hold music handler |
| `sweetdialer_voice_transfer` | `voiceTransfer.php` | `/twilio/voice/transfer` | Call transfers |
| `sweetdialer_voice_dtmf` | `voiceDTMF.php` | `/twilio/voice/dtmf` | DTMF/IVR navigation |

## Security

All webhooks implement:
- **X-Twilio-Signature validation** - HMAC-SHA1 signature verification
- **Constant-time comparison** - Prevents timing attacks
- **403 response** for invalid signatures
- **Error logging** to outr_twilio_error_logs table

## Story Completion

### S-044: WebhookHandler Base Class (`WebhookHandler.php`)
- ✅ Request signature validation (`X-Twilio-Signature` header)
- ✅ Request parsing
- ✅ TwiML response builder (`TwiMLResponse` class)
- ✅ Error logging to `outr_twilio_error_logs`
- ✅ `WebhookException` class

### S-045: Register 8 Webhook Entrypoints
- ✅ Entrypoint registry file: `SweetDialerWebhooks.php`
- ✅ All 8 endpoints registered with `auth => false` (signature-based auth)

### S-046: Outbound Call Handler (`voiceOutbound.php`)
- ✅ Receives To number from Client SDK
- ✅ Generates TwiML: `<Dial callerId="{agentPhone}"><Number>{To}</Number></Dial>`
- ✅ Links to CTI settings for caller ID
- ✅ Creates call records in `outr_twilio_calls`

### S-047: Recording Support (in `voiceOutbound.php`)
- ✅ `record="record-from-answer"` on `<Dial>`
- ✅ `recordingStatusCallback` to `/twilio/voice/recording`

### S-048: Inbound Call Handler (`voiceInbound.php`)
- ✅ Looks up To phone number in `outr_twilio_settings`
- ✅ Finds assigned agent(s) by `agent_phone_number`
- ✅ Generates TwiML: `<Dial><Client>{agentIdentity}</Client></Dial>`
- ✅ Timeout from `ring_timeout_seconds_before_vm` setting
- ✅ Creates call records with `direction = Inbound`

### S-049: Voicemail Fallback (in `voiceInbound.php`)
- ✅ If `<Dial>` times out, redirects to `/twilio/voice/voicemail`
- ✅ Uses `action` attribute on `<Dial>` element

### S-050: Voicemail Handler (`voiceVoicemail.php`)
- ✅ Looks up voicemail config from CTI setting
- ✅ Plays audio file OR uses `<Say voice="{voice}">`
- ✅ `<Record maxLength="{max}" finishOnKey="{key}">`
- ✅ Handles recording completion callback

### S-051: Save Voicemail Recordings (in `voiceVoicemail.php` + `voiceRecording.php`)
- ✅ Saves to `outr_twilio_voicemail_recordings`
- ✅ Links to CTI setting and caller's CRM record (Contact/Lead/Account)
- ✅ Includes URL, duration, caller number
- ✅ Recording status callback handling

### S-052: Hold Handler (`voiceHold.php`)
- ✅ Generates TwiML: `<Play loop="0">{holdRingtoneUrl}</Play>`
- ✅ Looks up custom hold music from CTI settings
- ✅ Fallback to default Twilio hold music

### S-053: DTMF Handler (`voiceDTMF.php`)
- ✅ Receives gathered DTMF digits from `<Gather>`
- ✅ Handles menu navigation (press 1 for Sales, 2 for Support, etc.)
- ✅ Supports extension dialing
- ✅ Supports transfers to departments
- ✅ Redirects appropriately for IVR navigation

## Files Created

```
sweet-dialer/src/custom/
├── Extension/application/Ext/EntryPointRegistry/SweetDialerWebhooks.php
│   └── Entry point registry for all 8 webhooks
├── include/TwilioDialer/WebhookHandler.php
│   └── Base class with signature validation and TwiML builder
└── entrypoints/
    ├── voiceOutbound.php   (S-046, S-047)
    ├── voiceInbound.php    (S-048, S-049)
    ├── voiceStatus.php
    ├── voiceRecording.php  (S-051)
    ├── voiceVoicemail.php  (S-050)
    ├── voiceHold.php       (S-052)
    ├── voiceTransfer.php
    └── voiceDTMF.php       (S-053)
```

## TwiML Response Builder

The `TwiMLResponse` class provides a fluent API for building TwiML:

```php
$response = new TwiMLResponse();
$response
    ->say('Hello', ['voice' => 'Polly.Joanna'])
    ->dial('+1234567890', ['callerId' => '+0987654321'], 'number')
    ->hangup();

echo $response->toXml();
```

Supported verbs:
- `say($text, $attributes)` - Text-to-speech
- `play($url, $attributes)` - Audio playback
- `dial($content, $attributes, $type)` - Call dialing (number/client)
- `record($attributes)` - Record audio
- `gather($callback, $attributes)` - Collect DTMF digits
- `redirect($url, $attributes)` - Transfer control
- `hangup()` - End call
- `reject($reason)` - Reject call
- `pause($length)` - Wait

## Usage

Configure these webhook URLs in your Twilio console:

| TwiML App Setting | Entry Point URL |
|------------------|-----------------|
| Voice Request URL | `https://{your-crm}/entryPoint.php?entryPoint=sweetdialer_voice_inbound` |
| Status Callback URL | `https://{your-crm}/entryPoint.php?entryPoint=sweetdialer_voice_status` |

For outbound calls from Client SDK:
- Configure `sweetdialer_voice_outbound` as your outgoing dial handler

## Testing

1. Install the module and run Repair & Rebuild
2. Configure active CTI settings with agent phone number
3. Set webhook URLs in Twilio console
4. Make test calls and verify:
   - Signature validation (403 for invalid)
   - Call records created
   - Recordings saved
   - Voicemails captured

## Points Summary

| Story | Points | Status |
|-------|--------|--------|
| S-044 | 5 | ✅ Complete |
| S-045 | 3 | ✅ Complete |
| S-046 | 5 | ✅ Complete |
| S-047 | 2 | ✅ Complete |
| S-048 | 8 | ✅ Complete |
| S-049 | 3 | ✅ Complete |
| S-050 | 5 | ✅ Complete |
| S-051 | 3 | ✅ Complete |
| S-052 | 2 | ✅ Complete |
| S-053 | 3 | ✅ Complete |
| **Total** | **39** | **✅ Complete** |
