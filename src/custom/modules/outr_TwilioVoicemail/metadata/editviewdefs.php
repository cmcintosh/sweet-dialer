<?php
/**
 * S-094: Voicemail Edit View
 * Fields: Name, File upload, TTS fields
 */

$viewdefs['outr_TwilioVoicemail']['EditView'] = array(
    'templateMeta' => array(
        'form' => array(
            'buttons' => array('SAVE', 'CANCEL'),
            'enctype' => 'multipart/form-data',
        ),
        'maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30'),
        ),
    ),
    'panels' => array(
        'lbl_general' => array(
            array(
                array('name' => 'name', 'label' => 'LBL_NAME'),
                array('name' => 'cti_setting_id', 'label' => 'LBL_CTI_SETTING_ID'),
            ),
        ),
        'lbl_audio_file' => array(
            array(
                array(
                    'name' => 'file',
                    'label' => 'LBL_FILE',
                    'type' => 'file',
                ),
            ),
        ),
        'lbl_tts_configuration' => array(
            array(
                array('name' => 'voice_speech_by', 'label' => 'LBL_VOICE_SPEECH_BY'),
                array('name' => 'voice_finish_key', 'label' => 'LBL_VOICE_FINISH_KEY'),
            ),
            array(
                array('name' => 'voice_max_length', 'label' => 'LBL_VOICE_MAX_LENGTH'),
                '',
            ),
            array(
                array(
                    'name' => 'voice_mail_message',
                    'label' => 'LBL_VOICE_MAIL_MESSAGE',
                    'type' => 'text',
                ),
            ),
        ),
    ),
);
