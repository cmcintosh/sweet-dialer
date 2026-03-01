<?php
// S-012: Relationship - outr_twilio_settings -> Users (outbound_inbound_agent)
$dictionary["outr_twilio_settings"]["fields"]["outbound_inbound_agent"] = array(
    "name" => "outbound_inbound_agent",
    "vname" => "LBL_OUTBOUND_INBOUND_AGENT",
    "type" => "relate",
    "module" => "Users",
    "id_name" => "outbound_inbound_agent_id",
    "source" => "non-db",
    "massupdate" => false,
);

$dictionary["outr_twilio_settings"]["fields"]["outbound_inbound_agent_id"] = array(
    "name" => "outbound_inbound_agent_id",
    "vname" => "LBL_OUTBOUND_INBOUND_AGENT_ID",
    "type" => "id",
    "len" => 36,
);

$dictionary["outr_twilio_settings"]["fields"]["outbound_inbound_agent_name"] = array(
    "name" => "outbound_inbound_agent_name",
    "rname" => "user_name",
    "id_name" => "outbound_inbound_agent_id",
    "vname" => "LBL_OUTBOUND_INBOUND_AGENT",
    "join_name" => "outbound_inbound_agent",
    "type" => "relate",
    "link" => "outbound_inbound_agent_link",
    "table" => "users",
    "isnull" => "true",
    "module" => "Users",
    "dbType" => "varchar",
    "len" => 255,
    "source" => "non-db",
    "unified_search" => true,
    "massupdate" => false,
);
