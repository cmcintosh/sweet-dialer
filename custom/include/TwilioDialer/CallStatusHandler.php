<?php
// Epic 6/S-054: Call Status Webhook Handler
if (!defined("sugarEntry") || !sugarEntry) die("Not A Valid Entry Point");

class CallStatusHandler {
    
    public function handleStatusCallback($data) {
        $callSid = $data["CallSid"] ?? null;
        $status = $data["CallStatus"] ?? null;
        $duration = $data["CallDuration"] ?? 0;
        $from = $data["From"] ?? null;
        $to = $data["To"] ?? null;
        $direction = $data["Direction"] ?? null;
        
        if (!$callSid) {
            return $this->getEmptyResponse();
        }
        
        // Find or create call record
        $callBean = $this->findOrCreateCall($callSid);
        $callBean->status = $status;
        $callBean->duration = $duration;
        $callBean->from_number = $from;
        $callBean->to_number = $to;
        $callBean->direction = $direction;
        
        // Classify call type
        $callBean->call_type = $this->classifyCallType($direction, $status);
        
        // Match to CRM record
        $this->matchToCrmRecord($callBean, $from, $to, $direction);
        
        $callBean->save();
        
        return $this->getEmptyResponse();
    }
    
    private function findOrCreateCall($callSid) {
        $bean = BeanFactory::getBean("outr_twilio_calls");
        $list = $bean->get_list("", "outr_twilio_calls.call_sid = "" . $bean->db->quote($callSid) . """, 0, 1);
        
        if (!empty($list["list"])) {
            return $list["list"][0];
        }
        
        $bean = BeanFactory::newBean("outr_twilio_calls");
        $bean->call_sid = $callSid;
        $bean->name = "Call " . substr($callSid, -8);
        return $bean;
    }
    
    private function classifyCallType($direction, $status) {
        if ($direction === "inbound") {
            if ($status === "completed") return "Incoming";
            if ($status === "no-answer") return "Missed Call";
            if ($status === "busy" || $status === "canceled") return "Rejected";
        }
        if ($direction === "outbound-api" || $direction === "outbound-dial") {
            if ($status === "completed") return "Outgoing";
        }
        return "Unknown";
    }
    
    private function matchToCrmRecord($callBean, $from, $to, $direction) {
        $phoneToMatch = ($direction === "inbound" || $direction === "inbound") ? $from : $to;
        if (!$phoneToMatch) return;
        
        // Search Contacts first
        $contact = BeanFactory::getBean("Contacts");
        $list = $contact->get_list("", "contacts.phone_mobile LIKE "" . $contact->db->quote($phoneToMatch) . "" OR contacts.phone_work LIKE "" . $contact->db->quote($phoneToMatch) . """, 0, 1);
        
        if (!empty($list["list"])) {
            $record = $list["list"][0];
            $callBean->parent_type = "Contacts";
            $callBean->parent_id = $record->id;
            return;
        }
        
        // Search Leads
        $lead = BeanFactory::getBean("Leads");
        $list = $lead->get_list("", "leads.phone_mobile LIKE "" . $lead->db->quote($phoneToMatch) . "" OR leads.phone_work LIKE "" . $lead->db->quote($phoneToMatch) . """, 0, 1);
        
        if (!empty($list["list"])) {
            $record = $list["list"][0];
            $callBean->parent_type = "Leads";
            $callBean->parent_id = $record->id;
            return;
        }
    }
    
    private function getEmptyResponse() {
        return "<?xml version="1.0" encoding="UTF-8"?>
<Response/>";
    }
}
