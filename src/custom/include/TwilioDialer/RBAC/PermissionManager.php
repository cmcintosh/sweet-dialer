<?php
/**
 * Sweet-Dialer Role-Based Access Control (S-117, S-118, S-119)
 *
 * Restricts CTI Settings to Admin
 * Restricts dialer to assigned agents
 * Restricts Call Tracker by role
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'modules/ACLRoles/ACLRole.php';

class SweetDialerPermissionManager
{
    /**
     * Check if current user is Admin
     * @return bool
     */
    public static function isAdmin()
    {
        global $current_user;
        
        if (empty($current_user)) {
            return false;
        }
        
        return $current_user->is_admin == 1 || $current_user->isAdmin();
    }
    
    /**
     * Check if current user is Manager
     * @return bool
     */
    public static function isManager()
    {
        global $current_user;
        
        if (self::isAdmin()) {
            return true;
        }
        
        return self::hasRole($current_user->id, 'Manager');
    }
    
    /**
     * Check if current user is Agent
     * @return bool
     */
    public static function isAgent()
    {
        global $current_user;
        
        if (empty($current_user)) {
            return false;
        }
        
        return self::hasRole($current_user->id, 'Agent') || self::hasRole($current_user->id, 'Sales');
    }
    
    /**
     * Check if user has specific role
     * @param string $userId
     * @param string $roleName
     * @return bool
     */
    public static function hasRole($userId, $roleName)
    {
        if (empty($userId) || empty($roleName)) {
            return false;
        }
        
        $role = new ACLRole();
        $roles = $role->getUserRoles($userId);
        
        foreach ($roles as $r) {
            if (stripos($r['name'], $roleName) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * S-117: Get accessible CTI settings for current user
     * Admin: full CRUD on all
     * Agents: view-only their own assigned config
     *
     * @param string $action 'view', 'edit', 'delete', 'create'
     * @param string $ctiSettingId CTI setting ID (for specific record checks)
     * @return bool
     */
    public static function canAccessCTISetting($action = 'view', $ctiSettingId = null)
    {
        global $current_user;
        
        if (self::isAdmin()) {
            return true;
        }
        
        // For create, require admin
        if ($action === 'create') {
            return false;
        }
        
        // Agents can only view their own assigned CTI config
        if ($action === 'view' && !empty($ctiSettingId)) {
            $bean = BeanFactory::getBean('outr_TwilioSettings', $ctiSettingId);
            if ($bean && !empty($bean->outbound_inbound_agent_id)) {
                return $bean->outbound_inbound_agent_id === $current_user->id;
            }
        }
        
        // Agents can view but not edit
        if ($action === 'view') {
            return true;
        }
        
        return false;
    }
    
    /**
     * S-118: Check if user can use dialer with specific CTI config
     *
     * @param string $ctiSettingId
     * @return bool
     */
    public static function canUseDialer($ctiSettingId = null)
    {
        global $current_user;
        
        if (empty($current_user)) {
            return false;
        }
        
        // Admin can use any
        if (self::isAdmin()) {
            return true;
        }
        
        // Check if CTI config is assigned to this user
        if (!empty($ctiSettingId)) {
            $bean = BeanFactory::getBean('outr_TwilioSettings', $ctiSettingId);
            if ($bean && $bean->outbound_inbound_agent_id === $current_user->id) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * S-119: Get visible calls for current user
     * Agents: own calls only
     * Admins/Managers: all calls
     *
     * @param string $alias Table alias for SQL query
     * @return array Array containing 'where' clause and 'params'
     */
    public static function getCallsVisibilityFilter($alias = '')
    {
        global $current_user;
        
        $prefix = !empty($alias) ? $alias . '.' : '';
        
        // Admin and Manager see all
        if (self::isAdmin() || self::isManager()) {
            return array(
                'where' => '',
                'params' => array()
            );
        }
        
        // Agent sees own calls only
        $userId = $current_user->id ?? '';
        
        return array(
            'where' => " AND (${prefix}agent_id = ? OR ${prefix}assigned_user_id = ?)",
            'params' => array($userId, $userId)
        );
    }
    
    /**
     * Override list query for Call Tracker (S-119)
     *
     * @param SugarBean $bean
     * @return bool True if access allowed, false otherwise
     */
    public static function checkCallAccess($bean)
    {
        global $current_user;
        
        if (empty($bean) || empty($current_user)) {
            return false;
        }
        
        // Admin and Manager can access any call
        if (self::isAdmin() || self::isManager()) {
            return true;
        }
        
        // Agent can only access their own calls
        $userId = $current_user->id;
        
        if ($bean->agent_id === $userId || $bean->assigned_user_id === $userId) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get list of CTI config IDs accessible to current user
     * @return array
     */
    public static function getAccessibleCTISettingIds()
    {
        global $current_user, $db;
        
        $ids = array();
        
        $sql = "SELECT id FROM outr_twilio_settings WHERE deleted = 0";
        
        // Agents only see their assigned config
        if (!self::isAdmin() && !empty($current_user->id)) {
            $sql .= " AND outbound_inbound_agent_id = '". $db->quote($current_user->id) . "'";
        }
        
        $result = $db->query($sql);
        
        while ($row = $db->fetchByAssoc($result)) {
            $ids[] = $row['id'];
        }
        
        return $ids;
    }
    
    /**
     * Record access attempt for audit trail
     *
     * @param string $module
     * @param string $recordId
     * @param string $action
     * @param bool $granted
     */
    public static function auditAccess($module, $recordId, $action, $granted)
    {
        global $current_user;
        
        $logData = array(
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $current_user->id ?? 'unknown',
            'user_name' => $current_user->user_name ?? 'unknown',
            'module' => $module,
            'record_id' => $recordId,
            'action' => $action,
            'granted' => $granted,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        );
        
        $GLOBALS['log']->security("Sweet-Dialer RBAC: " . json_encode($logData));
    }
}
