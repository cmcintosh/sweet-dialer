<?php
/**
 * S-015: Twilio Calls Subpanel for Contacts
 * CTI Calls -> Contacts Polymorphic Relationship
 */

$layout_defs['Contacts']['subpanel_setup']['sweetdialer_calls'] = array(
    'order' => 200,
    'module' => 'SweetDialerCalls',
    'subpanel_name' => 'default',
    'sort_order' => 'desc',
    'sort_by' => 'date_entered',
    'title_key' => 'LBL_TWILIO_CALLS_TITLE',
    'get_subpanel_data' => 'function:get_linked_calls',
    'set_subpanel_data' => 'function:set_linked_calls',
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
    'function_parameters' => array(
        'parent_id' => '$record',
        'parent_type' => 'Cases',
    ),
);
