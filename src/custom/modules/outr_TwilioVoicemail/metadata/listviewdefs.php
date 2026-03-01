<?php
/**
 * S-095: Voicemail List View
 * Columns: Name, Voice, Max Length
 */

$viewdefs['outr_TwilioVoicemail']['ListView'] = array(
    'NAME' => array(
        'width' => '30%',
        'label' => 'LBL_NAME',
        'link' => true,
        'default' => true,
    ),
    'VOICE_SPEECH_BY' => array(
        'width' => '20%',
        'label' => 'LBL_VOICE_SPEECH_BY',
        'default' => true,
    ),
    'VOICE_MAX_LENGTH' => array(
        'width' => '15%',
        'label' => 'LBL_VOICE_MAX_LENGTH',
        'default' => true,
    ),
    'DATE_CREATED' => array(
        'width' => '15%',
        'label' => 'LBL_DATE_CREATED',
        'default' => true,
    ),
);
