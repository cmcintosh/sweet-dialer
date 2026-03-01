<?php
/**
 * Sweet-Dialer CRM Record Matcher (S-056)
 *
 * Matches phone numbers to Contacts/Leads/Targets/Cases
 * Priority: Contact → Lead → Target → Case
 * Returns parent_type and parent_id
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'data/SugarBean.php';
require_once 'data/BeanFactory.php';

class CrmRecordMatcher
{
    /**
     * @var DBManager
     */
    private $db;
    
    /**
     * Search priority order
     */
    private $priorityOrder = ['Contacts', 'Leads', 'Prospects', 'Cases'];
    
    public function __construct()
    {
        $this->db = DBManagerFactory::getInstance();
    }
    
    /**
     * Match a phone number to a CRM record
     * Priority: Contact → Lead → Target → Case
     *
     * @param string $phoneNumber
     * @return array [parent_type, parent_id] or empty array if no match
     */
    public function matchByPhone($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return [];
        }
        
        // Normalize phone number for searching
        $normalizedPhone = $this->normalizePhone($phoneNumber);
        $e164Phone = $this->toE164($phoneNumber);
        
        $GLOBALS['log']->info("SweetDialer: CrmRecordMatcher - Searching for phone: $phoneNumber (normalized: $normalizedPhone)");
        
        // Search in priority order
        foreach ($this->priorityOrder as $module) {
            $result = $this->searchModule($module, $normalizedPhone, $e164Phone, $phoneNumber);
            if (!empty($result)) {
                return $result;
            }
        }
        
        $GLOBALS['log']->info("SweetDialer: CrmRecordMatcher - No match found for $phoneNumber");
        return [];
    }
    
    /**
     * Search a specific module by phone number
     *
     * @param string $module
     * @param string $normalizedPhone
     * @param string $e164Phone
     * @param string $originalPhone
     * @return array|void
     */
    private function searchModule($module, $normalizedPhone, $e164Phone, $originalPhone)
    {
        $table = strtolower($module);
        $phoneFields = $this->getPhoneFields($module);
        
        $conditions = [];
        foreach ($phoneFields as $field) {
            // Search by various formats
            $conditions[] = "REPLACE(REPLACE(REPLACE($field, '-', ''), ' ', ''), '(', '') LIKE '%" . 
                $this->db->quote($normalizedPhone) . "%'";
        }
        
        // Additional search by exact original format
        foreach ($phoneFields as $field) {
            $conditions[] = "$field = '" . $this->db->quote($originalPhone) . "'";
        }
        
        $whereClause = implode(' OR ', $conditions);
        
        $sql = "SELECT id FROM $table 
                WHERE deleted = 0 
                AND ($whereClause)
                ORDER BY date_modified DESC
                LIMIT 1";
        
        $result = $this->db->query($sql);
        $row = $this->db->fetchByAssoc($result);
        
        if ($row && !empty($row['id'])) {
            $parentType = $module === 'Prospects' ? 'Targets' : $module;
            $GLOBALS['log']->info("SweetDialer: CrmRecordMatcher - Found match in $parentType: {$row['id']}");
            return [
                'parent_type' => $parentType,
                'parent_id' => $row['id']
            ];
        }
        
        return null;
    }
    
    /**
     * Get phone fields for a module
     *
     * @param string $module
     * @return array
     */
    private function getPhoneFields($module)
    {
        $fieldMap = [
            'Contacts' => ['phone_work', 'phone_mobile', 'phone_home', 'phone_other', 'phone_fax'],
            'Leads' => ['phone_work', 'phone_mobile', 'phone_home', 'phone_other', 'phone_fax'],
            'Prospects' => ['phone_work', 'phone_mobile', 'phone_home', 'phone_other', 'phone_fax'],
            'Cases' => ['phone_c'], // Custom cases phone field
        ];
        
        return $fieldMap[$module] ?? ['phone_work', 'phone_mobile'];
    }
    
    /**
     * Normalize phone number for search
     * Removes all non-numeric characters except leading +
     *
     * @param string $phone
     * @return string
     */
    private function normalizePhone($phone)
    {
        // Keep only digits
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        
        // Remove leading 1 for US numbers if present
        if (strlen($normalized) === 11 && substr($normalized, 0, 1) === '1') {
            $normalized = substr($normalized, 1);
        }
        
        return $normalized;
    }
    
    /**
     * Convert to E.164 format
     *
     * @param string $phone
     * @return string
     */
    private function toE164($phone)
    {
        $digitsOnly = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 1 and has 11 digits, it's already E.164 for US
        if (strlen($digitsOnly) === 11 && substr($digitsOnly, 0, 1) === '1') {
            return '+' . $digitsOnly;
        }
        
        // If 10 digits, assume US and add +1
        if (strlen($digitsOnly) === 10) {
            return '+1' . $digitsOnly;
        }
        
        // If already starts with +, return as is but ensure digits only after
        if (substr($phone, 0, 1) === '+') {
            return '+' . $digitsOnly;
        }
        
        return $digitsOnly;
    }
    
    /**
     * Batch match multiple phone numbers
     *
     * @param array $phoneNumbers Array of phone numbers
     * @return array Associative array with phone as key, match result as value
     */
    public function batchMatch(array $phoneNumbers)
    {
        $results = [];
        foreach ($phoneNumbers as $phone) {
            $results[$phone] = $this->matchByPhone($phone);
        }
        return $results;
    }
    
    /**
     * Find company/account associated with a contact/lead
     *
     * @param string $parentType
     * @param string $parentId
     * @return string|null Account ID or null
     */
    public function findRelatedAccount($parentType, $parentId)
    {
        if (empty($parentType) || empty($parentId)) {
            return null;
        }
        
        $module = $parentType === 'Targets' ? 'Prospects' : $parentType;
        
        if ($module === 'Cases') {
            // For cases, look up account_id field
            $sql = "SELECT account_id FROM cases WHERE id = '" . $this->db->quote($parentId) . "' AND deleted = 0";
            $result = $this->db->query($sql);
            $row = $this->db->fetchByAssoc($result);
            return $row['account_id'] ?? null;
        }
        
        // For Contacts, Leads, Targets - look for account_id or account_name mapping
        $table = strtolower($module);
        $sql = "SELECT account_id FROM $table WHERE id = '" . $this->db->quote($parentId) . "' AND deleted = 0";
        $result = $this->db->query($sql);
        $row = $this->db->fetchByAssoc($result);
        
        return $row['account_id'] ?? null;
    }
}
