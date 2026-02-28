<?php
/**
 * S-014: CTI Calls -> CTI Settings Relationship
 */
$dictionary['SweetDialerCalls']['fields']['cti_setting_id'] = array(
    'name' => 'cti_setting_id',
    'vname' => 'LBL_CTI_SETTING_ID',
    'type' => 'id',
    'reportable' => true,
);
$dictionary['SweetDialerCalls']['fields']['cti_settings_name'] = array(
    'name' => 'cti_settings_name',
    'vname' => 'LBL_CTI_SETTINGS_NAME',
    'type' => 'relate',
    'rname' => 'name',
    'id_name' => 'cti_setting_id',
    'module' => 'SweetDialerCTI',
    'source' => 'non-db',
);
