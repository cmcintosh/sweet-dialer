<?php
$subpanel_layout = array(
    'top_buttons' => array(
        array('widget_class' => 'SubPanelTopCreateButton'),
        array('widget_class' => 'SubPanelTopSelectButton'),
    ),
    'list_fields' => array(
        'name' => array('vname'=>'LBL_NAME','width'=>'20%','default'=>true),
        'call_sid' => array('vname'=>'LBL_CALL_SID','width'=>'15%','default'=>true),
        'direction' => array('vname'=>'LBL_DIRECTION','width'=>'10%','default'=>true),
        'status' => array('vname'=>'LBL_STATUS','width'=>'10%','default'=>true),
        'duration_minutes' => array('vname'=>'LBL_DURATION_MINUTES','width'=>'10%','default'=>true),
        'date_entered' => array('vname'=>'LBL_DATE_ENTERED','width'=>'15%','default'=>true),
        'edit_button' => array('widget_class'=>'SubPanelEditButton','width'=>'5%','default'=>true),
    ),
);
