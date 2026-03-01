<?php
// S-016: Relationship - outr_twilio_calls -> Accounts
$dictionary["outr_twilio_calls"]["fields"]["company"] = array(
    "name" => "company",
    "vname" => "LBL_COMPANY",
    "type" => "relate",
    "module" => "Accounts",
    "id_name" => "company_id",
    "source" => "non-db",
    "massupdate" => false,
);

$dictionary["outr_twilio_calls"]["fields"]["company_id"] = array(
    "name" => "company_id",
    "vname" => "LBL_COMPANY_ID",
    "type" => "id",
    "len" => 36,
);
