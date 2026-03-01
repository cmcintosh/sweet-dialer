<?php
/**
 * Conference Admin Panel Configuration
 * S-107-S-109: Conference Settings in Admin
 */

$admin_group_header['sweetdialer'] = array(
    'LBL_SWEETDIALER_CONFERENCE_HEADER' => array(
        'LBL_OUTR_CONFERENCE_SETTINGS',
        false,
        array('Conference'),
        'conference-settings'
    )
);

$admin_option_defs = array();
$admin_option_defs['Administration']['outr_conference_settings'] = array(
    'Conference',
    'LBL_OUTR_CONFERENCE_SETTINGS_TITLE',
    'LBL_OUTR_CONFERENCE_SETTINGS_DESC',
    './index.php?module=Administration&action=outrConferenceConfig',
    'sweetdialer'
);
