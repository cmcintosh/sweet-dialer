<?php
/**
 * S-096: Call Tracker List View with Voicemail Playback
 */

$viewdefs['outr_TwilioCalls']['ListView'] = array(
    'CALL_SID' => array(
        'width' => '15%',
        'label' => 'LBL_CALL_SID',
        'link' => true,
        'default' => true,
    ),
    'FROM_NUMBER' => array(
        'width' => '12%',
        'label' => 'LBL_FROM_NUMBER',
        'default' => true,
    ),
    'TO_NUMBER' => array(
        'width' => '12%',
        'label' => 'LBL_TO_NUMBER',
        'default' => true,
    ),
    'STATUS' => array(
        'width' => '10%',
        'label' => 'LBL_STATUS',
        'default' => true,
    ),
    'DURATION' => array(
        'width' => '8%',
        'label' => 'LBL_DURATION',
        'default' => true,
    ),
    'VOICEMAIL_BADGE' => array(
        'width' => '10%',
        'label' => 'LBL_VOICEMAIL',
        'default' => true,
        'sortable' => false,
        'customCode' => '{$VOICEMAIL_BADGE}',
    ),
    'AUDIO_PLAYER' => array(
        'width' => '20%',
        'label' => 'LBL_PLAYBACK',
        'default' => true,
        'sortable' => false,
        'customCode' => '{$AUDIO_PLAYER}',
    ),
    'DATE_CREATED' => array(
        'width' => '13%',
        'label' => 'LBL_DATE_CREATED',
        'default' => true,
    ),
);
