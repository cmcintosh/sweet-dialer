<?php
/**
 * OutrConference Edit View
 * S-110-S-112: Conference Room Management
 */

$module_name = 'OutrConference';

$viewdefs[$module_name]['EditView'] = array(
    'templateMeta' => array(
        'form' => array(
            'buttons' => array(
                'SAVE',
                'CANCEL',
                array(
                    'customCode' => '{if $fields.status.value == "Active"}<input type="button" class="button" value="End Conference" onclick="endConference();"/>{/if}'
                )
            )
        ),
        'maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30')
        )
    ),
    'panels' => array(
        'default' => array(
            array(
                array('name' => 'name', 'label' => 'LBL_NAME'),
                array('name' => 'status', 'label' => 'LBL_STATUS'),
            ),
            array(
                array('name' => 'pin_code', 'label' => 'LBL_PIN_CODE'),
                array('name' => 'max_participants', 'label' => 'LBL_MAX_PARTICIPANTS'),
            ),
            array(
                array('name' => 'moderator_id', 'label' => 'LBL_MODERATOR'),
                array('name' => 'recording_enabled', 'label' => 'LBL_RECORDING_ENABLED'),
            ),
            array(
                array('name' => 'wait_for_moderator', 'label' => 'LBL_WAIT_FOR_MOD'),
                array('name' => 'mute_on_entry', 'label' => 'LBL_MUTE_ON_ENTRY'),
            ),
            array(
                array(
                    'name' => 'description',
                    'label' => 'LBL_DESCRIPTION',
                    'span' => 2
                )
            )
        )
    )
);
