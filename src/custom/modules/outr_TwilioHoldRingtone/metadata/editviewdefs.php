<?php
/**
 * S-100: Hold Ringtone Edit View
 * Same structure as Dual Ringtone
 */

$viewdefs['outr_TwilioHoldRingtone']['EditView'] = array(
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
                array('name' => 'status', 'label' => 'LBL_STATUS'),
            ),
        ),
        'lbl_audio_file' => array(
            array(
                array(
                    'name' => 'file',
                    'label' => 'LBL_FILE',
                    'type' => 'file',
                    'comment' => 'Upload audio file for hold music (mp3, wav)',
                ),
            ),
        ),
        'lbl_categorization' => array(
            array(
                array('name' => 'category', 'label' => 'LBL_CATEGORY'),
                array('name' => 'sub_category', 'label' => 'LBL_SUB_CATEGORY'),
            ),
        ),
        'lbl_assignment' => array(
            array(
                array('name' => 'assigned_to', 'label' => 'LBL_ASSIGNED_TO'),
                '',
            ),
        ),
        'lbl_dates' => array(
            array(
                array('name' => 'publish_date', 'label' => 'LBL_PUBLISH_DATE'),
                array('name' => 'expiration_date', 'label' => 'LBL_EXPIRATION_DATE'),
            ),
        ),
        'lbl_description' => array(
            array(
                array(
                    'name' => 'description',
                    'label' => 'LBL_DESCRIPTION',
                    'type' => 'text',
                    'rows' => 3,
                ),
            ),
        ),
    ),
);
