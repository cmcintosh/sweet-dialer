<?php
/**
 * S-016: Accounts -> CTI Calls Subpanel
 */
$layout_defs['Accounts']['subpanel_setup']['twilio_calls'] = array(
    'order' => 40,
    'module' => 'SweetDialerCalls',
    'subpanel_name' => 'default',
    'sort_order' => 'desc',
    'sort_by' => 'date_entered',
    'title_key' => 'LBL_TWILIO_CALLS_TITLE',
    'get_subpanel_data' => 'twilio_calls',
    'top_buttons' => array(
        array(
            'widget_class' => 'SubPanelTopCreateButton',
            'module' => 'SweetDialerCalls',
        ),
        array(
            'widget_class' => 'SubPanelTopSelectButton',
            'module' => 'SweetDialerCalls',
        ),
    ),
);
