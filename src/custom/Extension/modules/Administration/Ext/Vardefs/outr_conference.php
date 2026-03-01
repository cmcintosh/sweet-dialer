<?php
/**
 * SuiteCRM Conference Settings Vardefs
 * S-107-S-109: Conference Module Configuration
 */

$dictionary['Administration']['fields']['outr_conference_room_name'] = array(
    'name' => 'outr_conference_room_name',
    'vname' => 'LBL_OUTR_CONF_ROOM_NAME',
    'type' => 'varchar',
    'len' => 100,
    'default' => 'Main Conference',
    'required' => true,
);

$dictionary['Administration']['fields']['outr_conference_pin'] = array(
    'name' => 'outr_conference_pin',
    'vname' => 'LBL_OUTR_CONF_PIN',
    'type' => 'varchar',
    'len' => 10,
    'default' => '',
    'required' => false,
);

$dictionary['Administration']['fields']['outr_conference_max_participants'] = array(
    'name' => 'outr_conference_max_participants',
    'vname' => 'LBL_OUTR_CONF_MAX_PARTICIPANTS',
    'type' => 'int',
    'default' => 10,
    'required' => true,
    'min' => 2,
    'max' => 50,
);

$dictionary['Administration']['fields']['outr_conference_recording_enabled'] = array(
    'name' => 'outr_conference_recording_enabled',
    'vname' => 'LBL_OUTR_CONF_RECORDING',
    'type' => 'bool',
    'default' => false,
);

$dictionary['Administration']['fields']['outr_conference_wait_for_mod'] = array(
    'name' => 'outr_conference_wait_for_mod',
    'vname' => 'LBL_OUTR_CONF_WAIT_MOD',
    'type' => 'bool',
    'default' => true,
    'comment' => 'Wait for moderator before starting conference',
);

$dictionary['Administration']['fields']['outr_conference_mute_on_entry'] = array(
    'name' => 'outr_conference_mute_on_entry',
    'vname' => 'LBL_OUTR_CONF_MUTE_ENTRY',
    'type' => 'bool',
    'default' => true,
);
