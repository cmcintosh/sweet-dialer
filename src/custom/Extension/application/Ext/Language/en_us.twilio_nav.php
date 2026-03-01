<?php
/**
<<<<<<< HEAD
 * Epic 3: CTI Settings Navigation and Dropdown Lists
=======
 * S-017, S-018, S-019: Twilio Navigation and Menu Language
 * Twilio Settings Sidebar and Admin Panel
>>>>>>> epic/e1-package-foundation-complete
 */

$app_list_strings['moduleList']['SweetDialerCTI'] = 'CTI Settings';
$app_list_strings['moduleList']['SweetDialerCalls'] = 'Twilio Calls';
$app_list_strings['moduleList']['SweetDialerVoicemail'] = 'Voicemail';
$app_list_strings['moduleList']['SweetDialerPhoneNumbers'] = 'Phone Numbers';
$app_list_strings['moduleList']['SweetDialerTracker'] = 'Call Tracker';
$app_list_strings['moduleList']['SweetDialerRingtones'] = 'Ringtones';
$app_list_strings['moduleList']['SweetDialerLogger'] = 'Logger';
$app_list_strings['moduleList']['SweetDialerOptOut'] = 'Opt Out';

// S-019: Top Navigation Tab
$app_list_strings['moduleList']['Twilio'] = 'Twilio Settings';

// Sidebar menu item labels
$app_strings['LBL_TWILIO_DIALER'] = 'Twilio Dialer';
$app_strings['LBL_TWILIO_SETTINGS'] = 'Twilio Settings';
$app_strings['LBL_CTI_SETTINGS_TITLE'] = 'CTI Settings';
$app_strings['LBL_CTI_CREATE'] = 'Create CTI Settings';
$app_strings['LBL_CTI_VIEW'] = 'See CTI Settings';
$app_strings['LBL_CALL_TRACKER'] = 'Call Tracker';
$app_strings['LBL_INCOMING_CALL_SETTINGS'] = 'List Incoming Call Settings';
$app_strings['LBL_MY_OUTBOUND_PARTNER_AGENTS'] = 'My Outbound Partner Agents';
$app_strings['LBL_VOICEMAIL_CREATE'] = 'Create Voicemail';
$app_strings['LBL_VOICEMAIL_VIEW'] = 'See Voice Mail';
$app_strings['LBL_DUAL_RINGTONE_CREATE'] = 'Create Dual Ringtone';
$app_strings['LBL_DUAL_RINGTONE_VIEW'] = 'See Dual Ringtone';
$app_strings['LBL_HOLD_RINGTONE_CREATE'] = 'Create Hold Ringtone';
$app_strings['LBL_HOLD_RINGTONE_VIEW'] = 'See Hold Ringtone';
$app_strings['LBL_RING_TIMEOUT_SETTINGS'] = 'Ring Timeout Settings';
$app_strings['LBL_TWILIO_PHONE_NUMBERS'] = 'Twilio Phone Numbers';
$app_strings['LBL_TWILIO_ERROR_LOGS'] = 'Twilio Error Logs';
$app_strings['LBL_LOGGER'] = 'Logger';
$app_strings['LBL_CLEAN_ALL_APP'] = 'Clean All App';
$app_strings['LBL_STOP_LOGGING'] = 'Stop Logging';

<<<<<<< HEAD
// S-020: Incoming Calls Modules Dropdown
$app_list_strings['twilio_incoming_calls_modules_list'] = array(
    'Home' => 'Home',
    'Contacts' => 'Contacts',
    'Leads' => 'Leads',
    'Targets' => 'Targets',
    'Cases' => 'Cases',
);

// S-020: CTI Status Dropdown
=======
// Dropdown options
$app_list_strings['twilio_incoming_call_type_list'] = array(
    'user' => 'User',
    'ivr' => 'IVR',
);
>>>>>>> epic/e1-package-foundation-complete
$app_list_strings['twilio_cti_status_list'] = array(
    'Active' => 'Active',
    'Inactive' => 'Inactive',
);
<<<<<<< HEAD

// S-027: Validation Status Dropdown
$app_list_strings['twilio_validation_status_list'] = array(
    '' => '',
    'Passed' => 'Passed',
    'Failed' => 'Failed',
=======
$app_list_strings['call_direction_list'] = array(
    'inbound' => 'Inbound',
    'outbound' => 'Outbound',
);
$app_list_strings['call_status_list'] = array(
    'planned' => 'Planned',
    'held' => 'Held',
    'not_held' => 'Not Held',
    'inbound' => 'Inbound Call',
>>>>>>> epic/e1-package-foundation-complete
);
