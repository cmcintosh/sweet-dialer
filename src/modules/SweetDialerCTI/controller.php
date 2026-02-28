<?php
/**
 * SweetDialerCTI Module Controller
 * Handles save/validation with file uploads
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once 'include/MVC/Controller/SugarController.php';

class SweetDialerCTIController extends SugarController {

    /**
     * Override save to handle validation
     */
    function action_save() {
        $bean = $this->bean;
        
        // Server-side validation
        $errors = $this->validateBean($bean);
        if (!empty($errors)) {
            SugarApplication::appendErrorMessage(implode('\n', $errors));
            SugarApplication::redirect('index.php?module=SweetDialerCTI&action=EditView' . (!empty($this->record) ? '&record=' . $this->record : ''));
            return;
        }
        
        // Check uniqueness constraint (S-028)
        if ($this->isDuplicatePhoneAgent($bean)) {
            SugarApplication::appendErrorMessage('This phone number is already assigned to this agent');
            SugarApplication::redirect('index.php?module=SweetDialerCTI&action=EditView' . (!empty($this->record) ? '&record=' . $this->record : ''));
            return;
        }
        
        // Run credential validation (S-027)
        $validation = $this->validateCredentials($bean);
        $bean->last_validation_status = $validation['status'];
        $bean->last_validation_message = $validation['message'];
        $bean->last_validation_date = $validation['date'];
        
        // Save
        $bean->save();
        
        SugarApplication::redirect('index.php?module=SweetDialerCTI&action=DetailView&record=' . $bean->id);
    }
    
    /**
     * Validate bean fields
     */
    protected function validateBean($bean) {
        $errors = array();
        
        if (empty($bean->name)) {
            $errors[] = 'Name is required';
        }
        
        if (!empty($bean->accounts_sid) && !preg_match('/^AC/', $bean->accounts_sid)) {
            $errors[] = 'Account SID must start with AC';
        }
        
        if (!empty($bean->phone_sid) && !preg_match('/^PN/', $bean->phone_sid)) {
            $errors[] = 'Phone SID must start with PN';
        }
        
        if (!empty($bean->api_key_sid) && !preg_match('/^SK/', $bean->api_key_sid)) {
            $errors[] = 'API Key SID must start with SK';
        }
        
        if (!empty($bean->agent_phone_number) && !preg_match('/^\+\d{10,15}$/', $bean->agent_phone_number)) {
            $errors[] = 'Phone must be in E.164 format (+12345678901)';
        }
        
        return $errors;
    }
    
    /**
     * Check for duplicate phone + agent
     */
    protected function isDuplicatePhoneAgent($bean) {
        global $db;
        
        if (empty($bean->agent_phone_number) || empty($bean->outbound_inbound_agent_id)) {
            return false;
        }
        
        $phone = $db->quote($bean->agent_phone_number);
        $agentId = $db->quote($bean->outbound_inbound_agent_id);
        $recordId = $db->quote($bean->id);
        
        $query = "SELECT id FROM outr_twilio_settings 
                  WHERE agent_phone_number = '$phone'
                    AND outbound_inbound_agent_id = '$agentId'
                    AND deleted = 0";
        
        if (!empty($recordId)) {
            $query .= " AND id != '$recordId'";
        }
        
        $result = $db->query($query);
        return ($db->getRowCount($result) > 0);
    }
    
    /**
     * Validate Twilio credentials
     */
    protected function validateCredentials($bean) {
        $result = array(
            'status' => '',
            'message' => '',
            'date' => date('Y-m-d H:i:s'),
        );
        
        if (empty($bean->accounts_sid) || empty($bean->auth_token)) {
            return $result;
        }
        
        // Format validation
        if (!preg_match('/^AC[a-f0-9]{32}$/i', $bean->accounts_sid)) {
            $result['status'] = 'Failed';
            $result['message'] = 'Invalid Account SID format';
            return $result;
        }
        
        $result['status'] = 'Passed';
        $result['message'] = 'Credential format validation successful';
        
        return $result;
    }
    
    /**
     * S-030: Partner Agents
     */
    function action_partneragents() {
        $this->view = 'partneragents';
    }
    
    /**
     * S-031: Incoming Call Settings
     */
    function action_listincoming() {
        $this->view = 'listincoming';
    }
    
    /**
     * S-026: Soft delete
     */
    function action_delete() {
        if (!empty($_REQUEST['record'])) {
            $bean = $this->getBean();
            $bean->retrieve($_REQUEST['record']);
            if ($bean->id) {
                $bean->deleted = 1;
                $bean->save();
                SugarApplication::appendSuccessMessage('Record deleted');
            }
        }
        SugarApplication::redirect('index.php?module=SweetDialerCTI&action=index');
    }
}
