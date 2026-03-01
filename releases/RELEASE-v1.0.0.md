# Sweet-Dialer v1.0.0 Release Notes

**Release Date:** February 28, 2026

## Overview
Sweet-Dialer is a complete Twilio-powered AI Dialer for SuiteCRM, enabling click-to-call, call tracking, voicemail management, conferences, transfers, and comprehensive reporting.

## 📦 Package Info
- **Version:** 1.0.0
- **Size:** 170 KB (compressed)
- **Files:** 91 files
- **Lines of Code:** ~8,825
- **Epics:** 12 complete
- **Total Points:** 312

## ✨ Features

### Core Calling
- ☎️ **Click-to-Call** from any contact/lead record
- 📞 **Inbound/Outbound** call handling via Twilio Client SDK
- 🔊 **Voicemail** with playback and transcription (TTS)
- 🔄 **Transfer** (Warm & Cold) with agent selector
- 👥 **Conference** calls with participant management

### UI Components
- 💻 **Floating Dialer Overlay** — draggable, resizable
- 📊 **Dashboard Widget** — real-time metrics
- 🔔 **Click-to-Call Icons** throughout CRM
- 🎧 **Ringtone Management** — custom hold/dual tones
- 📈 **Analytics Charts** — call volume, conversions, trends

### Backend & Integration
- 🔗 **Twilio API Integration** — secure, rate-limited
- 🌐 **Webhook Handlers** — voice, status, recording callbacks
- 💾 **Call Logging** with recording playback
- 📱 **CRM Relationships** — linked to Contacts, Leads, Cases, Accounts
- 🔒 **RBAC Security** — role-based permissions
- 🔐 **AES-256 Encryption** for credentials
- 🛡️ **HMAC-SHA1 Webhook Validation**

### Reporting
- 📊 **Call Reports** — filterable, exportable
- 📉 **Analytics Dashboard** — visual metrics
- 📥 **Export** CSV & PDF formats
- 📅 **Scheduled Exports** via email

## 📁 Module Structure
```
sweet-dialer/
├── manifest.php                 # Module manifest
├── modules/                     # Core modules
│   ├── SweetDialerCTI/        # CTI Settings
│   ├── SweetDialerCalls/      # Call records
│   ├── SweetDialerVoicemail/  # Voicemail
│   ├── SweetDialerTracker/    # Call tracking
│   ├── SweetDialerRingtones/  # Custom ringtones
│   ├── SweetDialerPhoneNumbers/
│   ├── SweetDialerLogger/     # Audit logs
│   └── SweetDialerOptOut/     # Opt-out management
├── custom/                      # Extensions
│   ├── entrypoints/           # Webhook endpoints
│   ├── Extension/             # Vardefs, layout
│   └── include/TwilioDialer/  # Libraries & JS
│       ├── js/                # Client SDK, UI
│       ├── Security/           # Rate limiting
│       └── services/           # API services
├── ModuleInstall/               # Install/uninstall scripts
└── install_migrations/          # Database migrations
```

## 🔧 Installation

1. **Download:** `sweet-dialer-v1.0.0.zip`
2. **Upload:** Admin → Module Loader → Upload Package
3. **Install:** Follow prompts, complete without errors
4. **Configure:** Admin → Sweet Dialer → CTI Settings
5. **Test:** Make a test call

## ⚙️ Configuration

### Required Settings
- Twilio Account SID
- Twilio Auth Token
- Twilio Phone Number
- API Key/Secret
- App SID (for Client SDK)

### Webhook URLs
Set these in your Twilio Console:
- **Voice URL:** `https://your-crm.com/index.php?entryPoint=voiceWebhook`
- **Status Callback:** `https://your-crm.com/index.php?entryPoint=statusCallback`
- **Recording Callback:** `https://your-crm.com/index.php?entryPoint=recordingCallback`

## 📊 Database Changes
- Creates 8 new module tables
- Adds custom fields to existing modules
- Creates relationships to Contacts, Leads, Cases, Accounts

## 🔄 Upgrade Notes
- **Idempotent:** Safe to re-install
- **Preserves Data:** Call history maintained
- **Back Up First:** Always backup before installing

## 🆘 Support
- **Repository:** https://github.com/cmcintosh/sweet-dialer
- **Issues:** https://github.com/cmcintosh/sweet-dialer/issues
- **Tag:** `v1.0.0`

## 📜 License
GNU AGPLv3 - Open Source

## 🙏 Credits
Built by Wembassy — AI-powered web development & automation.
