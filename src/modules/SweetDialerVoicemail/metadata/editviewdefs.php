<?php
$viewdefs['outr_TwilioVoicemail']['EditView'] = array(
    'templateMeta' => array(
        'form' => array('buttons' => array('SAVE', 'CANCEL')),
        'maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30'),
        ),
    ),
    'panels' => array(
        'default' => array(
            array('name', 'cti_setting_id'),
            array('voice_speech_by', 'voice_finish_key'),
            array('voice_max_length', ''),
            array('voice_mail_message'),
        ),
    ),
);
