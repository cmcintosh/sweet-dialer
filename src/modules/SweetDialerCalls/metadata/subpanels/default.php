<?php
/**
 * S-015: CTI Calls Subpanel
 */
$subpanel_layout = array(
    'top_buttons' => array(
        array('widget_class' => 'SubPanelTopCreateButton'),
        array('widget_class' => 'SubPanelTopSelectButton'),
    ),
    'where' => '',
    'fill_in_additional_fields' => true,
    'list_fields' => array(
        'name' => array(
            'vname' => 'LBL_NAME',
            'widget_class' => 'SubPanelDetailViewLink',
            'width' => '20%',
            'default' => true,
        ),
        'call_sid' => array(
            'vname' => 'LBL_CALL_SID',
            'width' => '15%',
            'default' => true,
        ),
        'direction' => array(
            'vname' => 'LBL_DIRECTION',
            'width' => '10%',
            'default' => true,
        ),
        'parent_name' => array(
            'vname' => 'LBL_PARENT',
            'sortable' => false,
            'width' => '25%',
            'default' => true,
        ),
        'from_number' => array(
            'vname' => 'LBL_FROM_NUMBER',
            'width' => '10%',
            'default' => true,
        ),
        'duration_minutes' => array(
            'vname' => 'LBL_DURATION_MINUTES',
            'width' => '10%',
            'default' => true,
        ),
        'date_entered' => array(
            'vname' => 'LBL_DATE_ENTERED',
            'width' => '10%',
            'default' => true,
        ),
        'edit_button' => array(
            'vname' => 'LBL_EDIT_BUTTON',
            'widget_class' => 'SubPanelEditButton',
            'module' => 'SweetDialerCalls',
            'width' => '5%',
            'default' => true,
        ),
        'remove_button' => array(
            'vname' => 'LBL_REMOVE',
            'widget_class' => 'SubPanelRemoveButton',
            'module' => 'SweetDialerCalls',
            'width' => '5%',
            'default' => true,
        ),
    ),
);
