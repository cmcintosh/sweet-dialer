<?php
// Twilio Calls subpanel
$layout_defs["Targets"]["subpanel_setup"]["twilio_calls"] = array(
    "order" => 100,
    "module" => "outr_twilio_calls",
    "subpanel_name" => "default",
    "sort_order" => "desc",
    "sort_by" => "date_entered",
    "title_key" => "LBL_TWILIO_CALLS_SUBPANEL_TITLE",
    "get_subpanel_data" => "twilio_calls",
    "top_buttons" => array(
        array("widget_class" => "SubPanelTopButtonQuickCreate"),
    ),
);
