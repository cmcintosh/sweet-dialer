<?php
/**
 * S-031: List Incoming Call Settings View
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once 'include/MVC/View/SugarView.php';

class SweetDialerCTIViewListIncoming extends SugarView {
    
    public function display() {
        global $mod_strings, $app_strings, $current_user, $db;
        
        echo '<div class="moduleTitle">';
        echo '<h2>' . $mod_strings['LBL_INCOMING_CALL_SETTINGS'] . '</h2>';
        echo '</div>';
        
        // Query for CTI settings
        $query = "SELECT 
                    cts.id,
                    cts.name,
                    cts.agent_phone_number,
                    cts.incoming_calls_modules,
                    cts.ring_timeout,
                    cts.status,
                    cts.twiml_app_sid,
                    u.full_name AS agent_name
                  FROM outr_twilio_settings cts
                  LEFT JOIN users u ON u.id = cts.outbound_inbound_agent_id
                  WHERE cts.status = 'Active' 
                    AND cts.deleted = 0
                  ORDER BY cts.name ASC";
        
        $result = $db->query($query);
        
        if ($db->getRowCount($result) == 0) {
            echo '<div class="emptyMessage" style="padding:20px;">' . $mod_strings['LBL_NO_INCOMING_SETTINGS'] . '</div>';
            return;
        }
        
        echo '<table class="list view table-responsive" width="100%" cellspacing="0" cellpadding="0" border="0">';
        echo '<thead><tr height="20">';
        echo '<th scope="col">' . $mod_strings['LBL_NAME'] . '</th>';
        echo '<th scope="col">' . $mod_strings['LBL_AGENT_PHONE_NUMBER'] . '</th>';
        echo '<th scope="col">' . $mod_strings['LBL_OUTBOUND_INBOUND_AGENT'] . '</th>';
        echo '<th scope="col">' . $mod_strings['LBL_INCOMING_CALLS_MODULES'] . '</th>';
        echo '<th scope="col">' . $mod_strings['LBL_RING_TIMEOUT'] . '</th>';
        echo '</tr></thead>';
        
        $rowCount = 0;
        while ($row = $db->fetchByAssoc($result)) {
            $class = ($rowCount % 2 == 0) ? 'evenListRowS1' : 'oddListRowS1';
            echo '<tr class="' . $class . '">';
            echo '<td><a href="index.php?module=SweetDialerCTI&action=DetailView&record=' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</a></td>';
            echo '<td>' . ($row['agent_phone_number'] ? htmlspecialchars($row['agent_phone_number']) : '-') . '</td>';
            echo '<td>' . ($row['agent_name'] ? htmlspecialchars($row['agent_name']) : '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['incoming_calls_modules']) . '</td>';
            echo '<td>' . ($row['ring_timeout'] ? $row['ring_timeout'] . 's' : '30s') . '</td>';
            echo '</tr>';
            $rowCount++;
        }
        
        echo '</table>';
        echo '<div style="margin-top:20px;"><a href="index.php?module=SweetDialerCTI&action=index">' . $mod_strings['LNK_LIST'] . '</a></div>';
    }
}
