<?php
/**
 * SweetDialerVoicemail Vardefs (S-010)
 * outr_twilio_voicemail table
 */

$dictionary['outr_TwilioVoicemail'] = array(
    'table' => 'outr_twilio_voicemail',
    'audited' => true,
    'fields' => array(
        'id' => array(
            'name' => 'id',
            'vname' => 'LBL_ID',
            'type' => 'id',
            'required' => true,
        ),
        'name' => array(
            'name' => 'name',
            'vname' => 'LBL_NAME',
            'type' => 'name',
            'dbType' => 'varchar',
            'len' => 255,
        ),
        'file' => array(
            'name' => 'file',
            'vname' => 'LBL_FILE',
            'type' => 'text',
        ),
        'voice_mail_message' => array(
            'name' => 'voice_mail_message',
            'vname' => 'LBL_VOICE_MAIL_MESSAGE',
            'type' => 'text',
        ),
        'voice_speech_by' => array(
            'name' => 'voice_speech_by',
            'vname' => 'LBL_VOICE_SPEECH_BY',
            'type' => 'varchar',
            'len' => 100,
        ),
        'voice_finish_key' => array(
            'name' => 'voice_finish_key',
            'vname' => 'LBL_VOICE_FINISH_KEY',
            'type' => 'varchar',
            'len' => 10,
        ),
        'voice_max_length' => array(
            'name' => 'voice_max_length',
            'vname' => 'LBL_VOICE_MAX_LENGTH',
            'type' => 'int',
            'default' => 300,
        ),
        'cti_setting_id' => array(
            'name' => 'cti_setting_id',
            'vname' => 'LBL_CTI_SETTING_ID',
            'type' => 'relate',
            'rname' => 'name',
            'id_name' => 'cti_setting_id',
            'table' => 'outr_twilio_settings',
        ),
        'date_created' => array(
            'name' => 'date_created',
            'vname' => 'LBL_DATE_CREATED',
            'type' => 'datetime',
        ),
        'date_modified' => array(
            'name' => 'date_modified',
            'vname' => 'LBL_DATE_MODIFIED',
            'type' => 'datetime',
        ),
        'deleted' => array(
            'name' => 'deleted',
            'vname' => 'LBL_DELETED',
            'type' => 'bool',
            'default' => 0,
        ),
    ),
);
