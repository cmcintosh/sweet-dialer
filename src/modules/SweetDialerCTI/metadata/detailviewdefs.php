<?php
/**
 * DetailViewDefs for SweetDialerCTI (Epic 3)
 * S-027: Last Validation Message field
 */

$viewdefs['outr_TwilioSettings']['DetailView'] = array(
    'templateMeta' => array(
        'form' => array(
            'buttons' => array(
                'EDIT',
                'DUPLICATE',
                'DELETE',
            ),
            'javascript' => '
                {sugar_getscript file="modules/SweetDialerCTI/js/cti_detailview.js"}
            ',
        ),
        'maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30'),
        ),
        'useTabs' => false,
    ),
    'panels' => array(
        'LBL_VALIDATION_STATUS' => array(
            array(
                array(
                    'name' => 'last_validation_status',
                    'label' => 'LBL_LAST_VALIDATION_STATUS',
                    'customCode' => '
                        {if $fields.last_validation_status.value == "Passed"}
                            <div class="validation-banner validation-passed" style="background:#e6f7e6;border:1px solid #2ecc71;padding:10px;border-radius:4px;">
                                <span style="color:#2ecc71;font-weight:bold;">&#10004;</span> {$MOD.LBL_VALIDATION_PASSED}
                                {if $fields.last_validation_date.value}
                                    &nbsp;- {$fields.last_validation_date.value}
                                {/if}
                            </div>
                        {elseif $fields.last_validation_status.value == "Failed"}
                            <div class="validation-banner validation-failed" style="background:#ffe6e6;border:1px solid #e74c3c;padding:10px;border-radius:4px;">
                                <span style="color:#e74c3c;font-weight:bold;">&#10008;</span> {$MOD.LBL_VALIDATION_FAILED}
                                {if $fields.last_validation_message.value}
                                    <br/><span style="color:#e74c3c;font-size:0.9em;">{$fields.last_validation_message.value}</span>
                                {/if}
                            </div>
                        {else}
                            <div class="validation-banner validation-not-run" style="background:#f5f5f5;border:1px solid #ddd;padding:10px;border-radius:4px;color:#666;">
                                {$MOD.LBL_VALIDATION_NOT_RUN}
                            </div>
                        {/if}',
                ),
            ),
        ),
        'LBL_CTI_BASIC_INFO' => array(
            array(
                'name',
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
                    'customCode' => '<span class="masked-field">&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;</span>',
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
                array(
                    'name' => 'bg_color',
                    'customCode' => '<span style="display:inline-block;width:24px;height:24px;background-color:{$fields.bg_color.value|default:"#ffffff"};border:1px solid #ccc;"></span> {$fields.bg_color.value|default:"#ffffff"}',
                ),
                array(
                    'name' => 'text_color',
                    'customCode' => '<span style="display:inline-block;width:24px;height:24px;background-color:{$fields.text_color.value|default:"#000000"};border:1px solid #ccc;"></span> {$fields.text_color.value|default:"#000000"}',
                ),
            ),
            array(
                'dual_ring_file_name',
                'hold_ring_file_name',
            ),
            array(
                'api_key_sid',
                array(
                    'name' => 'api_key_secret',
                    'customCode' => '<span class="masked-field">&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;</span>',
                ),
            ),
            array(
                'twiml_app_sid',
                '',
            ),
            array(
                'date_created',
                'date_modified',
            ),
        ),
    ),
);
