<?php
/**
 * EditViewDefs for SweetDialerCTI (Epic 3)
 * S-020 to S-025: Create/Edit Form
 */

$viewdefs['outr_TwilioSettings']['EditView'] = array(
    'templateMeta' => array(
        'form' => array(
            'buttons' => array(
                array(
                    'customCode' => '<input id="SAVE" title="{$APP.LBL_SAVE_BUTTON_TITLE}" accesskey="{$APP.LBL_SAVE_BUTTON_KEY}" class="button primary" onclick="return validateCTIForm();" type="submit" name="save" value="{$APP.LBL_SAVE_BUTTON_LABEL}">',
                ),
                'CANCEL',
            ),
            'hidden' => array(
                '<input type="hidden" name="return_module" value="{$smarty.request.return_module}">',
                '<input type="hidden" name="return_action" value="{$smarty.request.return_action}">',
                '<input type="hidden" name="return_id" value="{$smarty.request.return_id}">',
            ),
        ),
        'maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30'),
        ),
        'javascript' => '
            {sugar_getscript file="modules/SweetDialerCTI/js/cti_validation.js"}
            {sugar_getscript file="modules/SweetDialerCTI/js/cti_editview.js"}
        ',
        'tabDefs' => array(
            'DEFAULT' => array(
                'newTab' => false,
                'panelDefault' => true,
            ),
        ),
    ),
    'panels' => array(
        'default' => array(
            array(
                array(
                    'name' => 'name',
                    'required' => true,
                ),
                'status',
            ),
            array(
                array('name' => 'outbound_inbound_agent', 'label' => 'LBL_OUTBOUND_INBOUND_AGENT'),
                '',
            ),
            array(
                'accounts_sid',
                array(
                    'name' => 'auth_token',
                    'type' => 'password',
                ),
            ),
            array(
                'phone_sid',
                'agent_phone_number',
            ),
            array(
                'incoming_calls_modules',
                'ring_timeout',
            ),
            array(
                'twilio_voice_mail',
                '',
            ),
            array(
                'bg_color',
                'text_color',
            ),
            array(
                'dual_ring_file_name',
                'hold_ring_file_name',
            ),
            array(
                'api_key_sid',
                array(
                    'name' => 'api_key_secret',
                    'type' => 'password',
                ),
            ),
            array(
                'twiml_app_sid',
                '',
            ),
        ),
    ),
);
