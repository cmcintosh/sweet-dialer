<?php
/**
 * S-013: CTI Settings -> Voicemail Subpanel
 */

$layout_defs['SweetDialerCTI']['subpanel_setup']['voicemails'] = array(
    'order' => 30,
    'sort_order' => 'desc',
    'sort_by' => 'date_entered',
    'module' => 'SweetDialerVoicemail',
    'subpanel_name' => 'default',
    'title_key' => 'LBL_VOICEMAILS_SUBPANEL_TITLE',
    'get_subpanel_data' => 'voicerecords',
    'top_buttons' => array(
        array(
            'widget_class' => 'SubPanelTopButtonQuickCreate',
        ),
        array(
            'widget_class' => 'SubPanelTopSelectButton',
            'popup_module' => 'SweetDialerVoicemail',
        ),
    ),
);
