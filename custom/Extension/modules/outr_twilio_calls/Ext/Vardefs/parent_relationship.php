<?php
// S-015: Polymorphic Relationship - outr_twilio_calls -> Contacts/Leads/Targets/Cases
$dictionary["outr_twilio_calls"]["fields"]["parent_name"] = array(
    "name" => "parent_name",
    "vname" => "LBL_PARENT_NAME",
    "type" => "parent",
    "dbType" => "varchar",
    "len" => 100,
    "parent_type" => "record_type_display",
    "options" => "parent_type_display",
    "source" => "non-db",
);

$dictionary["outr_twilio_calls"]["fields"]["parent_id"] = array(
    "name" => "parent_id",
    "vname" => "LBL_PARENT_ID",
    "type" => "id",
    "len" => 36,
);

$dictionary["outr_twilio_calls"]["fields"]["parent_type"] = array(
    "name" => "parent_type",
    "vname" => "LBL_PARENT_TYPE",
    "type" => "parent_type",
    "dbType" => "varchar",
    "len" => 100,
    "group" => "parent_name",
    "parent_type" => "record_type_display",
    "options" => "parent_type_display",
);
