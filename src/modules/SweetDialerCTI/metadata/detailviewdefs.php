<?php
/**
 * DetailViewDefs for SweetDialerCTI (S-009)
 */

$viewdefs['outr_TwilioSettings']['DetailView'] = array(
    'templateMeta' => array(
        'form' => array(
            'buttons' => array(
                'EDIT',
                'DUPLICATE',
                'DELETE',
            ),
        ),
        'maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30'),
        ),
    ),
    'panels' => array(
        'default' => array(
            array(
                'name',
                'status',
            ),
            array(
                'accounts_sid',
                'phone_sid',
            ),
            array(
                'auth_token',
            ),
            array(
                'api_key_sid',
                'api_key_secret',
            ),
            array(
                'twiml_app_sid',
                'agent_phone_number',
            ),
            array(
                'incoming_calls_modules',
            ),
            array(
                'bg_color',
                'text_color',
            ),
            array(
                'date_created',
                'date_modified',
            ),
        ),
    ),
);
