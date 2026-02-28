<?php
/**
 * S-030: My Outbound Partner Agents View
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once 'include/MVC/View/SugarView.php';

class SweetDialerCTIViewPartnerAgents extends SugarView {
    
    public function display() {
        global $mod_strings, $app_strings, $current_user, $db;
        
        $userId = $current_user->id;
        
        echo '<div class="moduleTitle">';
        echo '<h2>' . $mod_strings['LBL_MY_OUTBOUND_PARTNER_AGENTS'] . '</h2>';
        echo '</div>';
        
        // Get current user's phone numbers
        $phoneQuery = "SELECT DISTINCT agent_phone_number FROM outr_twilio_settings 
                        WHERE outbound_inbound_agent_id = '$userId' 
                        AND deleted = 0 
                        AND agent_phone_number IS NOT NULL
                        AND agent_phone_number != ''";
        
        $phoneResult = $db->query($phoneQuery);
        $userPhones = array();
        while ($row = $db->fetchByAssoc($phoneResult)) {
            $userPhones[] = $db->quote($row['agent_phone_number']);
        }
        
        if (empty($userPhones)) {
            echo '<div class="emptyMessage" style="padding:20px;">' . $mod_strings['LBL_NO_PHONE_NUMBERS_ASSIGNED'] . '</div>';
            return;
        }
        
        $phoneList = "'" . implode("','", $userPhones) . "'";
        
        $query = "SELECT 
                    DISTINCT cts.outbound_inbound_agent_id,
                    u.full_name AS agent_name,
                    u.user_name,
                    u.email1,
                    GROUP_CONCAT(DISTINCT cts.agent_phone_number) AS shared_numbers,
                    COUNT(DISTINCT cts.id) AS cti_count
                  FROM outr_twilio_settings cts
                  INNER JOIN users u ON u.id = cts.outbound_inbound_agent_id
                  WHERE cts.agent_phone_number IN ($phoneList)
                    AND cts.outbound_inbound_agent_id != '$userId'
                    AND cts.deleted = 0
                    AND cts.status = 'Active'
                  GROUP BY cts.outbound_inbound_agent_id, u.full_name, u.user_name
                  ORDER BY u.full_name ASC";
        
        $result = $db->query($query);
        
        if ($db->getRowCount($result) == 0) {
            echo '<div class="emptyMessage" style="padding:20px;">' . $mod_strings['LBL_NO_PARTNER_AGENTS'] . '</div>';
            echo '<div style="padding:10px;"><strong>' . $mod_strings['LBL_YOUR_PHONE_NUMBERS'] . ':</strong> ' . str_replace("'", "", $phoneList) . '</div>';
            return;
        }
        
        echo '<table class="list view table-responsive" width="100%" cellspacing="0" cellpadding="0" border="0">';
        echo '<thead><tr>';
        echo '<th>' . $mod_strings['LBL_AGENT_NAME'] . '</th>';
        echo '<th>' . $mod_strings['LBL_SHARED_NUMBERS'] . '</th>';
        echo '<th>' . $mod_strings['LBL_CTI_SETTINGS'] . '</th>';
        echo '<th>' . $mod_strings['LBL_STATUS'] . '</th>';
        echo '<th>' . $mod_strings['LBL_CONTACT'] . '</th>';
        echo '</tr></thead>';
        
        $rowCount = 0;
        while ($row = $db->fetchByAssoc($result)) {
            $class = ($rowCount % 2 == 0) ? 'evenListRowS1' : 'oddListRowS1';
            echo '<tr class="' . $class . '">';
            echo '<td>' . htmlspecialchars($row['agent_name']) . '<br/><span style="color:#666;">@' . htmlspecialchars($row['user_name']) . '</span></td>';
            echo '<td>' . nl2br(htmlspecialchars(str_replace(',', "\n", $row['shared_numbers']))) . '</td>';
            echo '<td>' . $row['cti_count'] . ' ' . $mod_strings['LBL_SETTINGS'] . '</td>';
            echo '<td>' . $mod_strings['LBL_STATUS'] . '</td>';
            echo '<td>' . ($row['email1'] ? '<a href="mailto:' . htmlspecialchars($row['email1']) . '">Email</a>' : '-') . '</td>';
            echo '</tr>';
            $rowCount++;
        }
        
        echo '</table>';
        echo '<div style="margin-top:20px;padding:10px;"><strong>' . $mod_strings['LBL_YOUR_PHONE_NUMBERS'] . ':</strong> ' . str_replace("'", "", $phoneList) . '</div>';
        echo '<div style="margin-top:10px;"><a href="index.php?module=SweetDialerCTI&action=index">' . $mod_strings['LNK_LIST'] . '</a></div>';
    }
}
