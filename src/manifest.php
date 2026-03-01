<?php
/**
 * SweetDialer Manifest - SuiteCRM 8.8.0 Compatible
 */

$manifest = array(
    'name' => 'SweetDialer',
    'version' => '1.0.0',
    'description' => 'Twilio AI-Powered Dialer for SuiteCRM 8.x',
    'author' => 'Wembassy',
    'readme' => 'README.md',
    'icon' => 'icon_SweetDialer.png',
    'is_uninstallable' => true,
    'published_date' => '2026-03-01',
    'acceptable_sugar_versions' => array(
        'regex_matches' => array(
            '8\.8\.\d+',
            '8\.\d+\.\d+',
            '7\.\d+\.\d+',
        ),
    ),
    'acceptable_sugar_flavors' => array(
        'CE',
        'ENT',
        'ULT',
    ),
);

$installdefs = array(
    'id' => 'SweetDialer',
    'copy' => array(
        array('from' => '<basedir>/custom', 'to' => 'custom'),
        array('from' => '<basedir>/modules', 'to' => 'modules'),
        array('from' => '<basedir>/config', 'to' => 'config'),
    ),
    'entrypoints' => array(
        'voiceWebhook',
        'statusCallback',
        'recordingCallback',
        'transferWarm',
        'transferCold',
        'voicemailFetch',
        'voicemailPlayback',
        'conferenceJoin',
        'conferenceControl',
        'conferenceParticipants',
        'dialerDashboard',
        'exportReport',
        'analyticsData',
        'voiceRecording',
        'voiceStatus',
        'voiceOutbound',
    ),
);

$upgrade_manifest = array(
    'acceptable_sugar_versions' => array(
        'regex_matches' => array('8\.8\.\d+', '8\.\d+\.\d+', '7\.\d+\.\d+'),
    ),
);
