# Sweet-Dialer Technical Dependencies

## Core Platform

### SuiteCRM Requirements
| Requirement | Version | Notes |
|-------------|---------|-------|
| SuiteCRM | 7.12+ or 8.x | 7.14 recommended |
| PHP | 8.0+ | 7.4 minimum |
| MySQL | 5.7+ or MariaDB 10.3+ | InnoDB required |
| Web Server | Apache 2.4 or Nginx 1.18+ | mod_rewrite required |

### Required PHP Extensions
- openssl (AES-256 encryption)
- json (API handling)
- mbstring (multilingual)
- curl (Twilio API)
- pdo_mysql (database)
- fileinfo (file upload MIME)

## Twilio Services

### SDKs and APIs
- Twilio PHP SDK ^7.0
- Twilio Client JS SDK ^2.0
- Twilio REST API v2010-04-01

### Required Credentials
- Account SID (ACxxxxxxxxxx)
- Auth Token or API Key + Secret
- Purchased Phone Numbers

### Twilio Features Cost Impact
| Feature | Usage |
|---------|-------|
| Programmable Voice | Per-minute rates |
| Call Recording | Per-minute + storage |
| Conference | Per-participant minute |
| Status Callbacks | No additional cost |

## Frontend Dependencies

### JavaScript Libraries
- twilio-client ^2.0.0 (WebRTC voice)
- howler.js ^2.2.3 (audio playback)

### Browser Requirements
- WebRTC support (Chrome 60+, Firefox 60+, Safari 14+)
- Web Audio API
- WebSocket support
- LocalStorage

## Security

### Encryption Standards
| Component | Standard |
|-----------|----------|
| Credential Storage | AES-256-GCM |
| Transmission | TLS 1.2+ |
| Webhook Validation | HMAC-SHA256 |

### Certificate Requirements
- Valid SSL certificate for webhooks (self-signed NOT accepted)
- Complete trust chain
- Auto-renewal recommended

## Version Compatibility Matrix

| SuiteCRM | PHP | Twilio SDK | Status |
|----------|-----|------------|--------|
| 7.12.x | 7.4 | 6.x | Deprecated |
| 7.14.x | 8.1 | 7.x | **Recommended** |
| 8.0.x | 8.1 | 7.x | Supported |
| 8.1.x | 8.2 | 7.x | Future |

## Key Dependencies for Epics 1-3

### Epic 1: Package Foundation
- ModuleInstaller class
- MySQL CREATE/ALTER/DROP privileges
- Custom directory write access
- PHP version_compare()

### Epic 2: Relationships & Navigation
- SuiteCRM Relationships framework
- SubPanelDefinitions
- SugarView extension
- Menu framework

### Epic 3: CTI Settings
- Twilio REST API connectivity
- TLS 1.2+
- Color picker library
- File upload handling

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Twilio API deprecation | High | Version lock, upgrade path |
| SuiteCRM breaking change | High | CI testing on multiple versions |
| PHP deprecation | Medium | Upgrade schedule |
| SSL certificate expiry | High | Monitoring, auto-renewal |

---
*Archer - PRD Architect*
*2026-02-28*
