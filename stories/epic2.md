# Epic 2 — Relationships & Navigation
**Total Points:** 24 | **Stories:** S-012 through S-019

## S-012: CTI Settings → Users Relationship (3 pts)
**Description:** Link CTI configurations to SuiteCRM users

### Acceptance Criteria
- [ ] One CTI Setting can be assigned to multiple Users
- [ ] User detail view shows related CTI Setting
- [ ] CTI Setting detail view shows list of assigned users
- [ ] When CTI Setting deleted, users get cleared reference
- [ ] Subpanel "CTI Settings" appears on User detail view

### Edge Cases
- User assigned to deleted CTI Setting (show warning)
- Multiple users share one CTI Setting (valid scenario)
- User created without CTI assignment (show setup prompt)

## S-013: CTI Settings → Voicemail Relationship (3 pts)
**Description:** Associate voicemail configurations with CTI settings

### Acceptance Criteria
- [ ] Each CTI Setting has multiple voicemail configs
- [ ] Voicemail inherits ring timeout from parent CTI Setting
- [ ] Deleting CTI Setting warns about related voicemails
- [ ] User can select default voicemail from dropdown

## S-014: CTI Calls → Settings Relationship (3 pts)
**Description:** Link call records to their CTI configuration

### Acceptance Criteria
- [ ] Call record stores `cti_setting_id` linking to configuration
- [ ] CTI Setting detail view shows call history subpanel
- [ ] Call metrics aggregated by CTI Setting
- [ ] Filter calls by CTI Setting in list view

## S-015: CTI Calls Polymorphic to Contacts/Leads/Targets/Cases (3 pts)
**Description:** Match calls to any SuiteCRM record type

### Supported Parent Types
Contacts, Leads, Prospects (Targets), Cases, Opportunities, Accounts

### Acceptance Criteria
- [ ] Call can be linked to any supported parent record
- [ ] Parent record detail view shows "Calls" subpanel
- [ ] Incoming calls auto-match to parent by phone number
- [ ] Manual parent assignment UI available

### Edge Cases
- Multiple records with same phone number (show disambiguation UI)
- Call matched to wrong record (easy reassign)
- Phone format variations match despite different formatting

## S-016: CTI Calls → Accounts Relationship (3 pts)
**Description:** Direct relationship to Accounts for B2B scenarios

### Acceptance Criteria
- [ ] `account_id` field on CTICalls
- [ ] When call linked to Contact, auto-populate Account
- [ ] Account-level call analytics available

## S-017: Build Twilio Settings Sidebar (5 pts)
**Description:** Custom sidebar navigation

### Sidebar Structure
```
Twilio Dialer
├── Settings → CTI Settings, Common Settings, System Health
├── Call Management → Call Tracker, Voicemail, Recordings
├── Configuration → Phone Numbers, Ringtones, User Assignments, Opt-Out List
└── Logs → Error Logs, Twilio Logger, Scheduler Jobs
```

### Acceptance Criteria
- [ ] Sidebar appears in SuiteCRM left panel
- [ ] Collapsible sections with persisted state
- [ ] Menu items show/hide based on role permissions
- [ ] Badge counts for unread voicemails

## S-018: Add Twilio to Admin Panel (2 pts)
- [ ] "Twilio Dialer" section appears in Admin panel
- [ ] Repair option for cache rebuild
- [ ] Migration status indicator

## S-019: Add to Top Navigation (2 pts)
- [ ] Phone icon in header when user has CTI access
- [ ] Badge shows unread voicemail count
- [ ] Call status indicator when on active call
