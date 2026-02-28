# Epic 3 — CTI Settings Module
**Total Points:** 34 | **Stories:** S-020 through S-031

## S-020 to S-021: CTI Settings Edit Form Fields (6 pts)
**Description:** Core configuration form with all required fields

### Form Fields
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| name | Text | Yes | Unique, max 255 chars |
| account_sid | Text | Yes | /^AC[a-f0-9]{32}$/ |
| auth_token | Password | Yes | Min 32 chars, auto-hide |
| api_key | Text | No | /^SK[a-f0-9]{32}$/ |
| api_key_secret | Password | No | Min 32 chars |
| webhook_base_url | URL | Yes | Valid HTTPS URL |
| ring_timeout | Integer | Yes | 5-300 seconds |

### Acceptance Criteria
- [ ] Account SID validates Twilio format
- [ ] Auth token masked by default
- [ ] Webhook URL must use HTTPS
- [ ] Auto-save draft every 30 seconds

### Edge Cases
- Invalid Account SID format
- Webhook URL with HTTP rejected
- Browser refresh with unsaved changes

## S-022 to S-023: CTI Settings UI Components (6 pts)
**Description:** Color pickers, file uploaders, responsive layouts

### Acceptance Criteria
- [ ] Color pickers for theme customization
- [ ] File uploads: MP3, WAV, OGG for audio; PNG, JPG for images
- [ ] File size limits: 10MB audio, 2MB image
- [ ] Drag-and-drop upload support
- [ ] Responsive: 2-column desktop, stack on mobile

### Edge Cases
- Unsupported file type
- File exceeds size limit
- Upload interrupted

## S-024 to S-025: Credential Validation & Twilio Test (6 pts)
**Description:** Validate credentials against Twilio API before save

### Acceptance Criteria
- [ ] "Test Credentials" button validates against Twilio
- [ ] Success: green checkmark with account name
- [ ] Failure: specific error mapping (401, 403, 404, timeout)
- [ ] Credentials NOT saved if test fails (without override)

### Edge Cases
- Twilio API down
- Network intermittent
- Valid credentials but no phone numbers purchased

## S-026: CTI Settings List View (3 pts)
**Description:** List all CTI configurations with status

### Acceptance Criteria
- [ ] Status indicators: green (valid), yellow (untested), red (invalid)
- [ ] Filters: Status, Created By, Date Range
- [ ] Bulk actions: Delete, Export, Disable
- [ ] Export to CSV with full credential masking

## S-027: CTI Settings Detail View (2 pts)
**Description:** Read-only view with edit/delete actions

### Acceptance Criteria
- [ ] Credentials shown as "••••••••" with copy button
- [ ] Related subpanels: Assigned Users, Recent Calls, Phone Numbers
- [ ] Actions: Test Credentials, Sync Phone Numbers, Duplicate Config

## S-028 to S-029: Multi-Agent, Multi-Number Logic (5 pts)
**Description:** Support multiple users and phone numbers per config

### Acceptance Criteria
- [ ] User selector (multi-select)
- [ ] Phone number selector from Twilio sync
- [ ] Round-robin or primary number assignment
- [ ] Removing user from config revokes CTI access

### Edge Cases
- User assigned to multiple CTI Settings
- All numbers in pool busy
- Number removed from Twilio account

## S-030: Partner Agents View (4 pts)
**Description:** External agent/contractor configuration

### Acceptance Criteria
- [ ] "Partner Agent" checkbox on User record
- [ ] Partner can only access assigned numbers
- [ ] Partner sees only their calls
- [ ] Data isolation at query level (security critical)

### Edge Cases
- Partner leaves company (revoke access)
- Partner tries to access other partner data (403)

## S-031: Incoming Call Settings Filtered View (2 pts)
**Description:** Dedicated view for inbound call handling

### Acceptance Criteria
- [ ] Filtered view accessible from sidebar
- [ ] Inbound/Outbound tabs
- [ ] Toggle switches for features
- [ ] Preview mode for caller experience
