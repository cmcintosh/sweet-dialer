<?php
/**
 * S-016: Accounts -> Twilio Calls Relationship
 * CTI Calls -> Accounts many-to-one Relationship
 */

$layout_defs['Accounts']['subpanel_setup']['sweetdialer_calls'] = array(
    'order' => 200,
    'module' => 'SweetDialerCalls',
    'subpanel_name' => 'default',
    'sort_order' => 'desc',
    'sort_by' => 'date_entered',
    'title_key' => 'LBL_TWILIO_CALLS_TITLE',
    'get_subpanel_data' => 'function:get_linked_calls',
    'function_parameters' => array(
        'account_id' => '$record',
    ),
    'top_buttons' => array(
        array(
            'widget_class' => 'SubPanelTopCreateButton',
            'module' => 'SweetDialerCalls',
        ),
        array(
            'widget_class' => 'SubPanelTopSelectButton',
            'popup_module' => 'SweetDialerCalls',
        ),
    ),
);
