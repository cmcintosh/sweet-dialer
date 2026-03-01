# Agile Plan: Twilio AI-Powered Dialer for SuiteCRM

**Total Epics:** 12  
**Total Features:** 38  
**Total Stories:** 142  
**Point Scale:** Fibonacci (1, 2, 3, 5, 8) — no story exceeds 8 points  
**Suggested Sprint Length:** 2 weeks  

---

## Estimation Guide

| Points | Complexity | LLM Buildability | Example |
|--------|-----------|-------------------|---------|
| **1** | Trivial | Single prompt, one file change | Add a field to a config form |
| **2** | Simple | Single prompt, 2-3 files | CRUD for a simple module |
| **3** | Moderate | 1-2 prompts, clear pattern | Build a list view with filters |
| **5** | Significant | 2-3 prompts, some integration | Webhook handler with DB writes |
| **8** | Complex | 3-4 prompts, multi-file coordination | Real-time UI with SDK integration |

---

## Sprint Roadmap (Suggested)

| Sprint | Epics | Theme |
|--------|-------|-------|
| 1-2 | E1, E2 | Foundation: packaging, DB schema, core module scaffolding |
| 3-4 | E3, E4 | Admin Config: CTI settings, credential validation, Twilio API client |
| 5-6 | E5, E6 | Calling Core: Twilio SDK integration, outbound/inbound calling |
| 7-8 | E7, E8 | Call Experience: controls, notes, transfers, dialer UI |
| 9-10 | E9, E10 | Media & Logging: voicemail, ringtones, call tracker, recordings |
| 11-12 | E11, E12 | Polish: error handling, security, scheduling, testing, deployment |

---

## E1 — Package Foundation & Module Scaffolding

> *Set up the SuiteCRM installable package structure, manifest, and all custom module skeletons so that every subsequent epic has a place to land code.*

**Total Points: 30**

### F1.1 — Package Structure & Manifest

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-001 | Create the SuiteCRM module loader package directory structure with `manifest.php`, `ModuleInstall/`, `custom/modules/`, `custom/Extension/`, `custom/include/TwilioDialer/`, `custom/themes/default/js/twilio-dialer/`, `custom/themes/default/css/twilio-dialer/`, and `upload/` | 3 | `.zip` can be uploaded via Module Loader without errors; manifest declares package name, version 2.0, and SuiteCRM 7.12+ compatibility |
| S-002 | Write `ModuleInstall/install.php` post-install script that triggers DB migration execution and registers custom modules in SuiteCRM's module registry | 3 | After install, all custom modules appear in Admin → Display Modules list |
| S-003 | Write `ModuleInstall/uninstall.php` that drops custom tables (with confirmation prompt), removes schedulers, and cleans uploaded files | 3 | Full uninstall leaves no orphaned tables, schedulers, or files; option to preserve call records |

### F1.2 — Database Schema Creation

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-004 | Create DB migration for `outr_twilio_settings` table with all fields from PRD §5.1.1 (name, accounts_sid, auth_token, agent_phone_number, phone_sid, incoming_calls_modules, status, bg_color, text_color, api_key_sid, api_key_secret, twiml_app_sid, plus standard SuiteCRM audit fields: id, date_created, date_modified, deleted, created_by, modified_user_id) | 3 | Table created on install; all columns match PRD types and constraints |
| S-005 | Create DB migration for `outr_twilio_calls` table with all columns from PRD §8.2 (id, call_type ENUM, agent_id, from_number, to_number, status, call_sid, duration, recording_url, recording_sid, parent_type, parent_id, company_id, notes, cti_setting_id, date_created, date_modified, deleted) | 3 | Table created; polymorphic parent_type + parent_id supports Contacts/Leads/Targets/Cases |
| S-006 | Create DB migrations for `outr_twilio_voicemail` (name, file, voice_mail_message, voice_speech_by, voice_finish_key, voice_max_length), `outr_twilio_voicemail_recordings`, and `outr_twilio_opted_out` (phone_number, reason, date_opted_out) | 3 | All three tables created with correct field types |
| S-007 | Create DB migrations for `outr_twilio_dual_ringtone` and `outr_twilio_hold_ringtone` (name, file, category, sub_category, assigned_to, publish_date, expiration_date, status, description) | 2 | Both tables created with identical schema |
| S-008 | Create DB migrations for `outr_twilio_common_settings` (name, ring_timeout_seconds_before_vm), `outr_twilio_error_logs` (error_code, error_message, call_sid, endpoint, request_body, response_body, date_created), `outr_twilio_logger` (log_level, message, context, date_created), and `outr_twilio_phone_numbers` (phone_number, friendly_name, phone_sid, capabilities_voice, capabilities_sms, assignment_status) | 3 | All four tables created |

### F1.3 — Module Scaffolding

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-009 | Scaffold SuiteCRM module metadata files for `outr_twilio_settings`: `vardefs.php`, `language/en_us.lang.php`, `metadata/editviewdefs.php`, `metadata/detailviewdefs.php`, `metadata/listviewdefs.php`, `Menu.php` | 3 | Module appears in admin menu; create/edit/list views render (empty forms OK) |
| S-010 | Scaffold module metadata for `outr_twilio_calls`, `outr_twilio_voicemail`, `outr_twilio_opted_out` with vardefs, language files, and view metadata | 3 | All three modules have working CRUD views |
| S-011 | Scaffold module metadata for `outr_twilio_dual_ringtone`, `outr_twilio_hold_ringtone`, `outr_twilio_common_settings`, `outr_twilio_error_logs`, `outr_twilio_logger`, `outr_twilio_phone_numbers` | 3 | All six modules have working CRUD views |

---

## E2 — Relationships & Navigation

> *Wire up all module relationships and build the Twilio Settings sidebar navigation so admins and agents can navigate the system.*

**Total Points: 24**

### F2.1 — Module Relationships

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-012 | Define relationship: `outr_twilio_settings` → `users` (many-to-one via `outbound_inbound_agent` relate field). Add relate field to editview; agent name displays in detailview and listview | 3 | Selecting an agent in CTI settings saves the user ID; agent name resolves in all views |
| S-013 | Define relationship: `outr_twilio_settings` → `outr_twilio_voicemail` (many-to-one via `twilio_voice_mail` relate field). Add arrow icon selector to editview | 3 | CTI setting links to a voicemail record; voicemail name displays in detailview |
| S-014 | Define relationship: `outr_twilio_calls` → `outr_twilio_settings` (many-to-one via `cti_setting_id`). Each call record references its CTI config | 2 | Call records display the associated CTI setting name |
| S-015 | Define polymorphic relationship: `outr_twilio_calls` → Contacts/Leads/Targets/Cases via `parent_type` + `parent_id`. Add Twilio Calls subpanel to Contact, Lead, Target, and Case detail views | 5 | Each CRM record detail view shows a "Twilio Calls" subpanel listing associated calls |
| S-016 | Define relationship: `outr_twilio_calls` → `accounts` (many-to-one via `company_id`). Link call records to company records where applicable | 2 | Call records optionally display linked company name |

### F2.2 — Twilio Settings Sidebar

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-017 | Build the Twilio Settings sidebar menu that appears on all Twilio module pages. Include links: Create CTI Settings, See CTI Settings, Call Tracker, List Incoming Call Settings, My Outbound Partner Agents, Create VoiceMail, See Voice Mail, Create Dual Ringtone, See Dual Ringtone, Create Hold Ringtone, See Hold Ringtone, Ring Timeout Settings, Twilio Phone Numbers, Twilio Error Logs, Logger, Clean All App, Stop Logging | 5 | Sidebar renders on every Twilio module page; all links navigate to correct views |
| S-018 | Add "Twilio Settings" section to the Administration panel with links to: Twilio Settings (CTI list), Twilio Calls, Opted-out Numbers, Twilio Logger, Twilio Voice Mail — each with a description line | 2 | Admin panel shows Twilio section with all described links |
| S-019 | Register the Twilio Settings module group in SuiteCRM's top navigation bar so it appears as a tab labeled "TWILIO SETTINGS" | 2 | Tab visible in nav bar; clicking navigates to CTI settings list |

---

## E3 — CTI Settings Module (Admin Configuration)

> *Build the full CTI configuration CRUD with all fields, form validation, and the admin list view.*

**Total Points: 34**

### F3.1 — CTI Create/Edit Form

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-020 | Build the CTI Settings edit view form with fields: Name (text), Accounts SID (text), Auth Token (password/masked), Agent Phone Number (text with E.164 placeholder), Phone SID (text), Incoming Calls Modules (dropdown: Home/Contacts/Leads/Targets/Cases), Status (dropdown: Active/Inactive) | 5 | Form renders all fields; saves to DB; reloads with saved values |
| S-021 | Add relate fields to CTI edit view: Twilio Voice Mail (arrow icon popup selector from voicemail module), Outbound/Inbound Agent (user selector with arrow icon and clear button) | 3 | Both relate fields open popup selectors; selected values save and display correctly |
| S-022 | Add color picker fields: BG Color and Text Color. Use HTML5 `<input type="color">` with hex value display | 2 | Color pickers render; selected hex values save to DB and reload correctly |
| S-023 | Add file upload fields: Dual Ring (MP3, max 10MB) and Hold Ring (MP3, max 10MB). Validate file type and size on upload. Show uploaded filename with remove button | 3 | MP3 files upload successfully; non-MP3 rejected with error; files stored in `upload/` directory |
| S-024 | Add v2 API credential fields in a separate "ADDED FOR V2" section: API Key SID (text), API Key Secret (password/masked), TwiML App SID (text, read-only if auto-generated) | 2 | Fields render in a visually distinct section; values save and reload |
| S-025 | Implement client-side form validation: Name required, Account SID must start with "AC", Phone SID must start with "PN", API Key SID must start with "SK", Agent Phone Number must match E.164 pattern (`+\d{10,15}`). Show inline error messages | 3 | Invalid submissions blocked with specific error messages per field; valid submissions proceed |

### F3.2 — CTI List View & Admin Actions

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-026 | Build the "See CTI Settings" list view showing columns: Name, Agent Phone Number, Outbound/Inbound Agent, Status, Date Created. Include edit (pencil), view (eye), and delete (trash) action icons per row | 3 | List view displays all CTI records; actions work correctly; deleted records soft-delete |
| S-027 | Add "Last Validation Message" field to CTI detail view that displays the result of the last credential validation attempt (e.g., "PASSED ATTEMPT" with verified date, or error message) | 2 | After save-with-validation, detail view shows banner with validation result and timestamp |

### F3.3 — Multi-Agent & Multi-Number Logic

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-028 | Implement logic allowing multiple CTI settings to reference the same Agent Phone Number (different agents sharing one number). Add a uniqueness check on the combination of `agent_phone_number` + `outbound_inbound_agent` to prevent duplicate assignments | 3 | Two CTI records can share a phone number with different agents; duplicate agent+number combo is rejected |
| S-029 | Implement logic allowing one agent to appear in multiple CTI settings (agent assigned to multiple numbers). When the agent logs in, load all their active CTI configs for number selection | 3 | Agent with 3 CTI configs sees all 3 numbers available in the dialer |
| S-030 | Build the "My Outbound Partner Agents" view: list all agents who share at least one phone number with the current user, showing agent name, shared number(s), and status | 3 | View correctly identifies and lists partner agents based on shared phone numbers |

### F3.4 — Incoming Call Settings

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-031 | Build "List Incoming Call Settings" view that shows all CTI settings filtered to those with active inbound routing, displaying: Name, Phone Number, Assigned Agent, Incoming Calls Module, Voicemail, Ring Timeout | 2 | List view filters and displays only inbound-relevant configuration data |

---

## E4 — Twilio API Client & Credential Validation

> *Build the PHP-side Twilio API client library, credential validation on CTI save, auto-fetch of phone numbers and credentials, and access token generation.*

**Total Points: 38**

### F4.1 — Core Twilio API Client

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-032 | Create `custom/include/TwilioDialer/TwilioApiClient.php` — a PHP class that wraps cURL calls to Twilio's REST API. Methods: `__construct($accountSid, $authToken)`, `get($endpoint, $params)`, `post($endpoint, $data)`. All requests use HTTPS Basic Auth. Returns decoded JSON or throws exceptions with Twilio error codes | 5 | Client can make authenticated GET/POST requests to Twilio API; errors are caught with meaningful messages |
| S-033 | Add retry logic to `TwilioApiClient`: on HTTP 429 or 5xx responses, retry up to 3 times with exponential backoff (1s, 2s, 4s). Log each retry to `outr_twilio_logger` | 3 | Retries execute on appropriate status codes; gives up after 3 attempts; all retries logged |
| S-034 | Add method `TwilioApiClient::validateCredentials()` — calls `GET /2010-04-01/Accounts/{SID}.json` and verifies the response contains the account SID and status "active". Returns validation result object with success boolean, message, and verified timestamp | 3 | Valid credentials return success=true; invalid SID returns "Invalid Account SID"; invalid token returns "Authentication failed" |

### F4.2 — Credential Validation on CTI Save

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-035 | Add a `before_save` logic hook on `outr_twilio_settings` that calls `TwilioApiClient::validateCredentials()` with the entered Account SID + Auth Token. On success, save the "PASSED ATTEMPT" message and timestamp to `last_validation_message`. On failure, save the error message but still allow the record to save (with a warning banner) | 5 | Saving CTI settings triggers validation; success shows green "PASSED ATTEMPT" banner; failure shows red error banner; record saves in both cases |
| S-036 | Extend the `before_save` hook to also validate the API Key SID + Secret by calling `GET /2010-04-01/Accounts/{SID}.json` authenticated with the API Key SID as username and API Key Secret as password. Log the result separately | 3 | API Key validation runs alongside Account SID validation; both results shown in validation message |

### F4.3 — Auto-Fetch from Twilio

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-037 | Add method `TwilioApiClient::fetchPhoneNumbers()` — calls `GET /2010-04-01/Accounts/{SID}/IncomingPhoneNumbers.json` and returns array of phone numbers with SID, number, friendly name, and capabilities | 3 | Method returns correct phone number data from Twilio account |
| S-038 | Build the "Twilio Phone Numbers" list view that calls `fetchPhoneNumbers()` on page load and displays results in a table: Phone Number, Friendly Name, Phone SID, Voice Capable, SMS Capable, Assignment Status (checks if any CTI setting references this number) | 5 | List view shows all Twilio account phone numbers with assignment status; data refreshes on page load |
| S-039 | Add auto-fetch behavior to CTI edit form: when Account SID and Auth Token are entered and the user tabs out of Auth Token, make an AJAX call to fetch and cache phone numbers. Populate a dropdown or auto-suggest for Agent Phone Number and Phone SID fields | 5 | After entering credentials and tabbing out, phone numbers auto-populate in a selector; selecting a number fills both Agent Phone Number and Phone SID |

### F4.4 — Access Token Generation

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-040 | Create `custom/include/TwilioDialer/TokenGenerator.php` — generates Twilio Client access tokens using the API Key SID, API Key Secret, Account SID, and TwiML App SID. Token includes a `VoiceGrant` with the TwiML App SID and the agent's identity. TTL set to 3600 seconds (1 hour) | 5 | Generated tokens are valid JWT format; Twilio Client SDK can initialize with them |
| S-041 | Create a SuiteCRM entrypoint `custom/include/TwilioDialer/tokenEndpoint.php` (registered in `custom/Extension/application/Ext/EntryPointRegistry/`) that authenticates the current SuiteCRM session, looks up the user's active CTI settings, and returns a JSON response with the access token | 3 | Authenticated agents can GET the endpoint and receive a valid token; unauthenticated requests return 401 |

### F4.5 — Credential Encryption

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-042 | Implement encryption at rest for sensitive CTI fields: `auth_token`, `api_key_secret`. Use PHP `openssl_encrypt`/`openssl_decrypt` with AES-256-CBC. Encryption key derived from SuiteCRM's `sugar_config['unique_key']`. Encrypt on `before_save`, decrypt on `after_retrieve` | 5 | Fields stored encrypted in DB; decrypted transparently when loaded; changing SuiteCRM unique_key invalidates stored credentials (documented) |
| S-043 | Mask sensitive fields in the edit view: Auth Token and API Key Secret show as `********` when a value exists. A "Change" button reveals the input field for updating | 2 | Saved credentials display masked; clicking "Change" reveals empty input for new value; leaving blank on save preserves existing value |

---

## E5 — Webhook Endpoints & TwiML Handling

> *Build all server-side webhook endpoints that Twilio calls for routing inbound/outbound calls, voicemail, hold, DTMF, and status updates.*

**Total Points: 39**

### F5.1 — Webhook Infrastructure

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-044 | Create `custom/include/TwilioDialer/WebhookHandler.php` base class with: request signature validation (using `X-Twilio-Signature` header and Auth Token), request parsing, TwiML response builder, and error logging to `outr_twilio_error_logs` | 5 | Valid Twilio signatures pass; forged requests return 403; all requests logged |
| S-045 | Register SuiteCRM entrypoints for all webhook URLs: `/twilio/voice/inbound`, `/twilio/voice/outbound`, `/twilio/voice/status`, `/twilio/voice/recording`, `/twilio/voice/voicemail`, `/twilio/voice/hold`, `/twilio/voice/transfer`, `/twilio/voice/dtmf`. Each maps to a handler method in `WebhookHandler` | 3 | All 8 endpoints respond to POST requests; return TwiML XML content-type |

### F5.2 — Outbound Call Routing

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-046 | Implement `/twilio/voice/outbound` handler: receives the `To` number from the Twilio Client SDK connection, generates TwiML `<Response><Dial callerId="{agentPhoneNumber}"><Number>{To}</Number></Dial></Response>` with the `statusCallbackUrl` pointing to `/twilio/voice/status` | 5 | Outbound calls connect to the destination number with the correct caller ID; status callbacks fire |
| S-047 | Add call recording to outbound TwiML: include `record="record-from-answer"` on the `<Dial>` verb and `recordingStatusCallback` pointing to `/twilio/voice/recording` | 2 | Outbound calls are recorded from answer; recording callback fires on completion |

### F5.3 — Inbound Call Routing

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-048 | Implement `/twilio/voice/inbound` handler: look up the `To` phone number in `outr_twilio_settings` to find the assigned agent(s), generate TwiML that rings the agent's Twilio Client device using `<Dial><Client>{agentIdentity}</Client></Dial>` with a `timeout` matching the ring timeout setting | 8 | Inbound calls ring the correct agent's browser client; timeout value matches Ring Timeout Settings |
| S-049 | Add fallback in inbound handler: if the `<Dial>` times out (agent doesn't answer), redirect to `/twilio/voice/voicemail` using `<Dial action="/twilio/voice/voicemail">` | 3 | Unanswered calls fall through to voicemail after timeout |

### F5.4 — Voicemail Flow

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-050 | Implement `/twilio/voice/voicemail` handler: look up the voicemail config from the CTI setting. If an audio file exists, play it with `<Play>`. If only text exists, use `<Say voice="{voice_speech_by}">`. Then `<Record maxLength="{voice_max_length}" finishOnKey="{voice_finish_key}" recordingStatusCallback="/twilio/voice/recording">` | 5 | Voicemail greeting plays (audio or TTS); caller can leave a message; recording callback fires |
| S-051 | Save voicemail recordings to `outr_twilio_voicemail_recordings` table linked to the CTI setting and the caller's CRM record (if matched). Include recording URL, duration, and caller number | 3 | Voicemail recordings appear in Call Tracker and on the contact's CRM record subpanel |

### F5.5 — Hold & DTMF

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-052 | Implement `/twilio/voice/hold` handler: generates TwiML `<Response><Play loop="0">{holdRingtoneUrl}</Play></Response>` using the hold ringtone file from the active CTI setting | 2 | Caller hears hold music that loops until unhold |
| S-053 | Implement `/twilio/voice/dtmf` handler: receives gathered DTMF digits from `<Gather>` and forwards them appropriately (used for IVR navigation on outbound calls) | 3 | DTMF digits are sent over the call; IVR menus can be navigated |

---

## E6 — Call Status & Record Logging

> *Handle Twilio status callbacks to create and update call records in the CRM automatically.*

**Total Points: 29**

### F6.1 — Status Callback Processing

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-054 | Implement `/twilio/voice/status` handler: parse `CallSid`, `CallStatus`, `CallDuration`, `From`, `To`, `Direction` from the callback. Create or update the corresponding `outr_twilio_calls` record | 5 | Status callbacks create call records on initiation and update them through ringing → in-progress → completed lifecycle |
| S-055 | Add call type classification logic: map Twilio's `Direction` + `CallStatus` to our ENUM: `incoming` + `completed` → "Incoming", `outbound-api` + `completed` → "Outgoing", `incoming` + `no-answer` → "Missed Call", `incoming` + `busy`/`canceled` → "Rejected" | 3 | Call types are correctly classified in the call record |
| S-056 | Implement CRM record matching: when a call arrives (inbound or outbound), search Contacts, Leads, Targets, and Cases for a matching phone number. Set `parent_type` and `parent_id` on the call record. If multiple matches, prefer Contact → Lead → Target → Case priority | 5 | Call records auto-link to the correct CRM record; priority order respected for duplicates |

### F6.2 — Recording Callback Processing

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-057 | Implement `/twilio/voice/recording` handler: parse `RecordingSid`, `RecordingUrl`, `RecordingDuration`, `CallSid`. Find the matching `outr_twilio_calls` record and update `recording_url`, `recording_sid`, and `duration` | 3 | Call records have recording URLs populated after call completion |
| S-058 | Add authentication to recording URLs: when serving recording playback in the CRM, proxy the request through SuiteCRM (to avoid exposing Twilio recording URLs directly). Create an entrypoint that validates the SuiteCRM session, then streams the recording from Twilio | 5 | Recordings play in the browser; direct Twilio URLs are never exposed to the frontend |

### F6.3 — Auto-Save for Random Calls

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-059 | When an agent dials a random number (not from a CRM record), before initiating the call: search CRM for an existing record with that number. If not found, create a new Contact record with the phone number and a default name ("New Contact from Outgoing Call"). Link the call record to this new/existing contact | 5 | Random dial to new number creates a contact; random dial to existing number links to that record |
| S-060 | Add "New Contact from Outgoing Call" label to the auto-created contact and a flag field `auto_created` so agents can easily identify and update these placeholder records | 3 | Auto-created contacts are identifiable in CRM; agents can edit them into full records |

---

## E7 — Dialer Frontend: Twilio Client SDK Integration

> *Integrate the Twilio Client JS SDK into the SuiteCRM frontend, establishing WebRTC connections and enabling browser-based calling.*

**Total Points: 36**

### F7.1 — SDK Initialization & Token Management

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-061 | Create `custom/themes/default/js/twilio-dialer/twilioClient.js` — on page load (for agents with active CTI settings), fetch an access token from the token endpoint, initialize the `Twilio.Device`, and register for incoming calls | 8 | Twilio.Device initializes successfully; browser registers with Twilio; console shows "Device ready" |
| S-062 | Implement token refresh: listen for the `tokenWillExpire` event on `Twilio.Device`, fetch a new token from the endpoint, and call `device.updateToken(newToken)`. Handle token fetch failure with retry and user notification | 3 | Token refreshes automatically before expiry; failed refresh shows "Connection issue" banner |
| S-063 | Handle `Twilio.Device` error events: `error`, `unregistered`. Log errors to console and display user-friendly notifications. Map common error codes: 31205 (JWT expired), 31009 (transport error), 20104 (invalid token) | 3 | All error states show appropriate notifications; errors logged for debugging |

### F7.2 — Outbound Call Initiation

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-064 | Implement `makeCall(phoneNumber, crmRecordType, crmRecordId)` function: calls `device.connect({ params: { To: phoneNumber, parentType: crmRecordType, parentId: crmRecordId } })`. Returns the `Call` object. Stores active call reference globally | 5 | Calling `makeCall('+15551234567', 'Contacts', 'uuid')` initiates an outbound call via WebRTC |
| S-065 | Handle outbound call state events: `ringing`, `accept` (connected), `disconnect`, `cancel`, `reject`, `error`. Update a global `callState` object with current status and emit custom DOM events for the UI layer | 3 | Call state transitions fire events; `callState` always reflects current call status |

### F7.3 — Inbound Call Handling

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-066 | Handle `Twilio.Device` `incoming` event: store the incoming `Call` object, extract caller info (`From` number, custom parameters), look up CRM record via AJAX call to a search endpoint | 5 | Incoming call triggers event; caller number and matched CRM record info available |
| S-067 | Implement `answerCall()` — calls `incomingCall.accept()`. Implement `rejectCall()` — calls `incomingCall.reject()`. Both update `callState` and fire DOM events | 2 | Answer connects the call; reject sends to voicemail; state updates correctly |
| S-068 | Implement `endCall()` — calls `activeCall.disconnect()`. Triggers the post-call save flow (notes, logs). Cleans up global call references | 2 | Call disconnects cleanly; post-call save executes; state resets to idle |

### F7.4 — Call Controls (SDK-Level)

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-069 | Implement `toggleMute()` — calls `activeCall.mute(!activeCall.isMuted())`. Returns current mute state | 1 | Mute toggles microphone; `isMuted()` returns correct state |
| S-070 | Implement `sendDtmf(digit)` — calls `activeCall.sendDigits(digit)` for DTMF tones (0-9, *, #) | 1 | DTMF tones sent over active call; IVR systems respond correctly |
| S-071 | Implement `holdCall()` and `unholdCall()` — uses Twilio REST API (via SuiteCRM backend proxy) to update the call resource with a hold TwiML URL or resume. Backend endpoint `POST /twilio/api/hold` accepts `callSid` and `action` (hold/unhold) | 5 | Hold plays hold music to caller; unhold resumes conversation; agent hears nothing during hold |

---

## E8 — Dialer Frontend: UI Components

> *Build all visual components of the dialer: the overlay, sidebar dialer, click-to-call buttons, and all in-call controls.*

**Total Points: 53**

### F8.1 — Click-to-Call Buttons

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-072 | Create a SuiteCRM `after_ui_frame` logic hook or view customization that scans detail views of Contacts, Leads, Targets, Cases, and Accounts for phone number fields and injects a clickable phone icon (🔲📞) next to each number | 5 | Phone icons appear next to all phone fields in supported modules for agents with active CTI settings |
| S-073 | On click-to-call icon click: extract the phone number, determine the CRM module and record ID, call `makeCall()` from the SDK layer, and open the calling overlay | 3 | Clicking the icon initiates the call and shows the overlay in one action |
| S-074 | Conditionally show/hide call icons: only display for the currently logged-in user if they have at least one active CTI setting. Hide for admins without CTI settings and all non-agent users | 2 | Agents see icons; non-agents don't; visibility updates if CTI settings change |

### F8.2 — Standalone Dialer (Random Call Input)

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-075 | Add a persistent dialer input widget in the SuiteCRM header area (top-right, near user profile). Contains: phone number input field (with E.164 formatting help), Call icon button, and a number selector dropdown (if agent has multiple numbers) | 5 | Dialer widget visible on all pages for active agents; number input accepts and formats phone numbers |
| S-076 | On Call icon click: validate the phone number format, trigger the auto-save flow (S-059), call `makeCall()`, and open the calling overlay | 3 | Random number calls work end-to-end; new contacts auto-created; overlay appears |

### F8.3 — Calling Interface Overlay

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-077 | Build the calling overlay container: a floating panel that appears over the CRM content when a call is active. Positioned top-right or center. Draggable. Contains slots for: caller info, status indicator, controls bar, and tabbed content area. Style with CSS from `twilio-dialer/` | 8 | Overlay renders on call start; draggable; stays on screen during CRM navigation; dismisses on call end |
| S-078 | Build caller info section: displays contact avatar (first letter with configurable BG/Text color from CTI settings), contact name (or "Unknown Caller"), phone number, and the `outr_cti_name` (which Twilio number is being used) | 3 | Saved contacts show name + number; unknown callers show number only; avatar colors match CTI config |
| S-079 | Build call status indicator: shows real-time status text ("Calling", "Ringing", "In Progress", "On Hold", "Disconnected", "Missed Call") with a color-coded dot (green=active, yellow=hold, red=disconnected, gray=ringing). Include a live call timer (MM:SS) that starts on "In Progress" | 5 | Status updates in real-time matching SDK events; timer counts up accurately; colors match states |

### F8.4 — In-Call Control Buttons

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-080 | Build the controls bar with icon buttons: Mute (🔇), Hold (⏸), Keypad (⌨), Notes (📝), Call Logs (📋), Cold Transfer (→), Warm Transfer (⇒), End Call (🔴). Each button shows active/inactive state | 5 | All 8 buttons render with appropriate icons; toggle buttons show active state visually |
| S-081 | Wire Mute button to `toggleMute()`. Toggle icon between muted/unmuted states. Show a visual indicator (e.g., microphone slash) when muted | 1 | Clicking mute toggles state; icon updates; agent's microphone is disabled/enabled |
| S-082 | Wire Hold button to `holdCall()`/`unholdCall()`. Toggle icon between hold/active. Show "On Hold" in status indicator. Disable other controls (except unhold and end call) during hold | 3 | Hold plays music to caller; status shows "On Hold"; only unhold and end call are clickable during hold |
| S-083 | Build the keypad panel: 12-button grid (1-9, *, 0, #) that slides open when Keypad button is clicked. Each button calls `sendDtmf(digit)`. Show entered digits in a display field. Include a "Back" button to close the keypad | 3 | Keypad opens/closes on toggle; buttons send DTMF; digits display; back closes panel |

### F8.5 — Notes & Call History Panels

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-084 | Build the Notes tab: textarea for agent to type notes during the call. Auto-saves note text to the in-memory call session. On call end, saves notes to the `outr_twilio_calls.notes` field and also creates a SuiteCRM Note record linked to the parent CRM record | 5 | Notes typed during call persist after call end; appear in Call Tracker and contact's Notes subpanel |
| S-085 | Build the Note History tab: on tab open, fetch all previous notes for the same contact/lead (via AJAX to a custom endpoint). Display as a scrollable list with timestamps and agent names | 3 | Previous call notes for the same contact display correctly; empty state shows "No previous notes" |
| S-086 | Build the Call Logs tab: on tab open, fetch complete call history for this contact (via AJAX). Display as a list: call type icon (↗ outgoing, ↙ incoming, ✕ missed, ⊘ rejected), date/time, duration, agent name, status | 3 | Full call history for the contact displays with correct icons and data; sorted most recent first |

---

## E9 — Call Transfers

> *Implement cold and warm call transfer functionality with agent selection and context passing.*

**Total Points: 26**

### F9.1 — Transfer Agent Selection

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-087 | Build the transfer agent selector panel: when Cold Transfer or Warm Transfer is clicked, show a list of available agents (users with active CTI settings and currently online/registered with Twilio). Display agent name, assigned number, and online status indicator | 5 | Panel shows only agents with active CTI configs; online status reflects Twilio Device registration |
| S-088 | Add agent search/filter to the transfer panel: text input that filters the agent list by name. If there are many agents, paginate or virtualize the list | 2 | Typing filters agents in real-time; works for 50+ agents without lag |

### F9.2 — Cold Transfer

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-089 | Implement cold transfer: on agent selection, call the SuiteCRM backend endpoint `POST /twilio/api/transfer` with `callSid`, `targetAgentIdentity`, and `transferType: 'cold'`. Backend updates the call's TwiML to `<Dial><Client>{targetAgent}</Client></Dial>` using Twilio's call update API | 8 | Call transfers to target agent; original agent is disconnected; target agent's overlay shows the call with caller info |
| S-090 | Update the call record on cold transfer: set `transfer_type: 'cold'`, `transferred_from: {originalAgentId}`, `transferred_to: {targetAgentId}` on the `outr_twilio_calls` record | 2 | Call record reflects the transfer with both agent IDs and transfer type |

### F9.3 — Warm Transfer

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-091 | Implement warm transfer — step 1 (conference): on agent selection, create a Twilio Conference. Move the current call into the conference (caller on hold hears hold music). Dial the target agent into the conference. Original agent and target agent can speak privately | 8 | Three-party conference created; caller hears hold music; agents can speak to each other |
| S-092 | Implement warm transfer — step 2 (handoff): after agent-to-agent conversation, original agent clicks "Complete Transfer" button. Original agent's leg is removed from the conference. Target agent is now connected directly to the caller. Pass all notes from the call session to the target agent's overlay | 5 | Original agent disconnects; target agent and caller remain connected; notes appear in target agent's overlay |
| S-093 | Update the call record on warm transfer: set `transfer_type: 'warm'`, agent IDs, and append a "Transfer Notes" section to the notes field with the original agent's notes | 2 | Call record captures warm transfer metadata and preserves original notes |

---

## E10 — Voicemail, Ringtones & Ring Timeout

> *Build the admin CRUD modules for voicemail configuration, dual/hold ringtones, and ring timeout settings.*

**Total Points: 26**

### F10.1 — Voicemail Management

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-094 | Build "Create VoiceMail" edit view: Name, File (upload MP3/WAV), Voice Mail Message (textarea for TTS), Voice Speech By (dropdown: man/woman), Voice Finish Key (text, single char), Voice Max Length (integer, seconds) | 3 | Form saves all fields; file uploads store to `upload/` directory |
| S-095 | Build "See Voice Mail" list view: columns Name, Voice Speech By, Voice Max Length, assigned CTI settings count, Date Created. Include edit/view/delete actions | 2 | List displays all voicemail configs; actions work correctly |
| S-096 | Add voicemail recording playback to Call Tracker: for calls that went to voicemail, show a "Voicemail" badge and an inline audio player to hear the caller's recorded message | 3 | Voicemail calls are identifiable in Call Tracker; recordings play inline |

### F10.2 — Dual Ringtone Management

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-097 | Build "Create Dual Ringtone" edit view: Name, File (upload MP3), Category (dropdown), Sub Category (dropdown), Assigned To (user relate), Publish Date, Expiration Date, Status (Active/Inactive), Description | 3 | Form saves all fields; file uploads validated as MP3 ≤10MB |
| S-098 | Build "See Dual Ringtone" list view with columns: Name, Category, Assigned To, Status, Publish Date, Expiration Date | 2 | List displays all ringtones with filtering and sorting |
| S-099 | Integrate dual ringtone playback: when an inbound call arrives, the dialer frontend loads the configured dual ringtone audio file and plays it through the browser's Audio API until the agent answers or the call ends | 3 | Custom ringtone plays on inbound call; stops on answer/timeout; falls back to browser default if no ringtone configured |

### F10.3 — Hold Ringtone Management

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-100 | Build "Create Hold Ringtone" and "See Hold Ringtone" views (identical structure to Dual Ringtone) | 2 | CRUD works for hold ringtones |
| S-101 | Ensure the hold ringtone file URL is served via the `/twilio/voice/hold` webhook TwiML. If no hold ringtone is configured, use Twilio's default hold music | 2 | Hold plays custom audio when configured; default when not |

### F10.4 — Ring Timeout Settings

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-102 | Build "Ring Timeout Settings" (Twilio Common Settings) list/edit view. Fields: Name, Ring Timeout (Seconds) before VM (integer). Support multiple entries for different configurations | 3 | Settings save and load correctly; value used by inbound call webhook |
| S-103 | Wire ring timeout value into the inbound call TwiML handler (S-048): read the `ring_timeout_seconds_before_vm` value and use it as the `timeout` attribute on the `<Dial>` verb | 1 | Inbound calls ring for exactly the configured number of seconds before voicemail |
| S-104 | Add mass assign capability to Ring Timeout Settings: Security Groups mass assign with Assign/Remove buttons and Group selector | 2 | Admins can assign ring timeout settings to security groups |

---

## E11 — Call Tracker, Opted-Out Numbers & Logging

> *Build the Call Tracker list view with recording playback, the opted-out numbers module, and the logging/error systems.*

**Total Points: 35**

### F11.1 — Call Tracker (Twilio Tracker)

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-105 | Build the Call Tracker list view with columns: Call Type (with icon), Agent, From, To, Status, Call Recording (inline player), Companies, Debtors, Date Created. All columns sortable | 5 | List view renders all call records with correct data in each column |
| S-106 | Add inline audio player to the Call Recording column: play/pause button, progress bar showing current time / total duration, volume control. Uses HTML5 `<audio>` element with the proxied recording URL | 5 | Recordings play inline without page navigation; progress and duration display correctly |
| S-107 | Add filtering to Call Tracker: date range picker, agent dropdown, call type checkboxes (Outgoing/Incoming/Missed/Rejected), status dropdown, phone number search. Filters apply server-side for performance | 5 | All filters work individually and in combination; results update without full page reload |
| S-108 | Add export functionality: "Export" button downloads filtered call records as CSV with all columns | 2 | CSV downloads with correct data matching current filters |

### F11.2 — Opted-Out Numbers

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-109 | Build Opted-Out Numbers CRUD: list view showing phone number, reason, date opted out, added by. Edit view for manual entry with phone number (E.164 validated) and reason fields | 3 | CRUD works; numbers stored in correct format |
| S-110 | Implement opt-out check: before every outbound call (both random and click-to-call), query `outr_twilio_opted_out` for the target number. If found, block the call and show a warning modal: "This number has opted out of calls. Reason: {reason}" | 3 | Calls to opted-out numbers are blocked with warning; calls to other numbers proceed normally |
| S-111 | Create a webhook endpoint `POST /twilio/optout` that accepts opt-out requests (e.g., from an IVR menu "Press 2 to opt out") and adds the number to the opted-out list automatically | 3 | IVR opt-out creates a record; subsequent calls to that number are blocked |

### F11.3 — Twilio Logger

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-112 | Implement the logging service `custom/include/TwilioDialer/Logger.php`: methods `info()`, `warning()`, `error()`, `debug()`. Each writes to `outr_twilio_logger` with log_level, message, context (JSON), and timestamp. Respects a global "logging enabled" flag | 3 | Log entries created at appropriate levels; disabled when flag is off |
| S-113 | Build the Logger list view: columns Log Level (color-coded), Message (truncated), Context (expandable), Date Created. Filterable by log level. Include "Stop Logging" toggle and "Clean All App" button (purges all log entries with confirmation) | 3 | List view displays logs; stop/start logging works; clean all purges with confirmation |

### F11.4 — Twilio Error Logs

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-114 | Build Twilio Error Logs list view: columns Error Code, Error Message (truncated), Call SID (linked to Call Tracker record), Endpoint, Date Created. Detail view shows full request/response bodies | 3 | Error logs display with all fields; Call SID links navigate to the call record |

---

## E12 — Security, Scheduling, Health & Deployment

> *Finalize security hardening, build scheduler jobs, add health monitoring, and prepare the deployment package.*

**Total Points: 41**

### F12.1 — Webhook Security

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-115 | Harden all webhook endpoints: validate `X-Twilio-Signature` on every request using Twilio's standard validation algorithm (HMAC-SHA1 of URL + POST params with Auth Token as key). Return 403 for invalid signatures. Log rejected requests to error logs | 5 | Only requests with valid Twilio signatures are processed; all others get 403; rejected requests logged |
| S-116 | Add rate limiting to the token endpoint: max 10 requests per minute per user session. Return 429 with retry-after header when exceeded | 2 | Exceeding 10 requests/min returns 429; normal usage proceeds |

### F12.2 — Role-Based Access Control

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-117 | Restrict CTI Settings module access: only Admin role can create, edit, or delete CTI settings. Regular users can view their own CTI settings (read-only) | 3 | Admins have full CRUD; agents can only view settings assigned to them |
| S-118 | Restrict dialer functionality: agents can only make/receive calls on numbers assigned to them via CTI settings. Attempting to use another agent's number returns "Unauthorized" | 3 | Agent A cannot call using Agent B's CTI config; token generation validates agent assignment |
| S-119 | Restrict Call Tracker access: agents see only their own call records. Admins and Managers see all records. Implement via SuiteCRM's `SecurityGroup` or `row-level` ACL | 3 | Agents see filtered view; admins see all; role changes take effect immediately |

### F12.3 — Scheduler Jobs

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-120 | Create `TwilioRecordingSync` scheduler job: runs every 15 minutes. Queries `outr_twilio_calls` for records with `call_sid` but no `recording_url`. Fetches recording details from Twilio API and updates the records | 5 | Missing recordings are backfilled; already-populated records are skipped; errors logged |
| S-121 | Create `TwilioPhoneNumberSync` scheduler job: runs daily. Calls `TwilioApiClient::fetchPhoneNumbers()` for each unique Account SID in active CTI settings. Updates `outr_twilio_phone_numbers` table (insert new, update existing, soft-delete removed) | 3 | Phone numbers list stays current; removed numbers flagged; new numbers added |
| S-122 | Create `TwilioLogCleanup` scheduler job: runs weekly. Deletes `outr_twilio_logger` entries older than 30 days (configurable). Deletes `outr_twilio_error_logs` entries older than 90 days (configurable) | 2 | Old logs purged on schedule; retention periods configurable in admin settings |
| S-123 | Create `TwilioTokenRefresh` scheduler job: runs every 45 minutes. For each agent with an active CTI setting and an active SuiteCRM session, pre-generate and cache a fresh access token | 3 | Cached tokens available for quick retrieval; stale tokens replaced |
| S-124 | Register all 4 scheduler jobs in `install.php` so they appear in Admin → Schedulers after installation. Set default frequencies and active status | 2 | All jobs visible in Schedulers; default frequencies match PRD specs |

### F12.4 — Health Check & Monitoring

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-125 | Create `/twilio/health` endpoint that checks: (1) at least one active CTI setting exists, (2) Twilio API is reachable (lightweight GET to account endpoint), (3) token generation succeeds. Return JSON `{ status: "healthy/degraded/unhealthy", checks: {...} }` | 3 | Endpoint returns correct health status; each sub-check reported individually |
| S-126 | Build an Admin dashboard widget (SuiteCRM dashlet): "Twilio Dialer Status" showing: total active CTI configs, calls today, active calls now, failed calls today, system health status from `/twilio/health` | 5 | Dashlet renders on Admin home; data refreshes every 60 seconds; shows real-time metrics |

### F12.5 — Final Packaging & Deployment

| ID | Story | Points | Acceptance Criteria |
|----|-------|--------|---------------------|
| S-127 | Finalize `manifest.php` with: correct version (2.0), acceptable_sugar_versions (7.12.*–8.*), pre/post install/uninstall scripts, list of all custom modules, file copy directives, and license information | 2 | Manifest passes SuiteCRM Module Loader validation |
| S-128 | Write `install.php` final version: runs all DB migrations (idempotent — checks if tables exist before creating), registers modules, installs schedulers, creates `upload/twilio-dialer/` subdirectories, sets file permissions | 3 | Clean install on fresh SuiteCRM works; reinstall over existing installation doesn't duplicate or break anything |
| S-129 | Write `uninstall.php` final version: prompts for call record preservation, drops tables (or preserves), removes schedulers, removes custom files, unregisters modules | 2 | Full uninstall removes all traces; preserve option keeps call data |

---

## Appendix A: Story Dependency Map

```
S-001 ──► S-002 ──► S-009 ──► S-020 ──► S-035 (CTI create → validate)
  │         │
  │         ▼
  │       S-003 (uninstall)
  │
  ▼
S-004 ──► S-012 (settings table → relationships)
S-005 ──► S-015 (calls table → polymorphic relations)
S-006 ──► S-094 (voicemail table → voicemail CRUD)
S-007 ──► S-097 (ringtone table → ringtone CRUD)
S-008 ──► S-102 (common settings table → ring timeout)

S-032 ──► S-034 ──► S-035 (API client → validate → CTI save hook)
  │
  ├──► S-037 ──► S-038 (fetch numbers → list view)
  │               │
  │               ▼
  │             S-039 (auto-populate in CTI form)
  │
  ▼
S-040 ──► S-041 ──► S-061 (token gen → endpoint → SDK init)

S-044 ──► S-045 ──► S-046 (webhook infra → register → outbound handler)
                      │
                      ▼
                    S-048 (inbound handler)
                      │
                      ▼
                    S-049 ──► S-050 (voicemail fallback → voicemail flow)

S-061 ──► S-064 ──► S-073 (SDK init → makeCall → click-to-call wiring)
  │         │
  │         ▼
  │       S-065 ──► S-079 (call events → status indicator)
  │
  ▼
S-066 ──► S-067 ──► S-099 (incoming → answer/reject → ringtone)

S-077 ──► S-078 ──► S-079 ──► S-080 (overlay → caller info → status → controls)

S-054 ──► S-055 ──► S-056 (status callback → classification → CRM matching)
  │
  ▼
S-057 ──► S-058 (recording callback → secure playback)

S-087 ──► S-089 (agent selector → cold transfer)
  │
  ▼
S-091 ──► S-092 (warm transfer conference → handoff)
```

---

## Appendix B: Story Count by Epic

| Epic | Stories | Points |
|------|---------|--------|
| E1 — Package Foundation | 11 | 30 |
| E2 — Relationships & Navigation | 8 | 24 |
| E3 — CTI Settings Module | 12 | 34 |
| E4 — Twilio API Client & Credentials | 12 | 42 |
| E5 — Webhooks & TwiML | 10 | 39 |
| E6 — Call Status & Record Logging | 7 | 29 |
| E7 — Twilio Client SDK Integration | 11 | 36 |
| E8 — Dialer Frontend UI | 15 | 53 |
| E9 — Call Transfers | 7 | 32 |
| E10 — Voicemail, Ringtones & Timeout | 11 | 26 |
| E11 — Call Tracker, Opt-Out & Logging | 10 | 35 |
| E12 — Security, Scheduling & Deployment | 15 | 41 |
| **TOTAL** | **129** | **421** |

---

## Appendix C: Definition of Done (per Story)

A story is "Done" when:

1. **Code complete** — all files created/modified per acceptance criteria.
2. **Self-tested** — developer has manually verified all acceptance criteria.
3. **No regressions** — existing module functionality unaffected.
4. **Committed** — code pushed to feature branch with descriptive commit message referencing story ID.
5. **Reviewed** — code reviewed by at least one other developer (or LLM audit pass).
6. **Documented** — inline code comments for complex logic; README updated if new setup steps required.

---

*End of Agile Plan*
