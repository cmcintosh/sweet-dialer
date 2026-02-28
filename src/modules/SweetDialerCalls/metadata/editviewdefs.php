<?php
$viewdefs['outr_TwilioCalls']['EditView'] = array(
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
            array('call_sid', 'call_type'),
            array('from_number', 'to_number'),
            array('status', 'duration'),
            array('agent_id', 'cti_setting_id'),
            array('parent_type', 'parent_id'),
            array('company_id', ''),
            array('notes'),
            array('recording_sid', 'recording_url'),
        ),
    ),
);
