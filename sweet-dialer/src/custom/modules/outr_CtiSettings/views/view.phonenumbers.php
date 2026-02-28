<?php
/**
 * view.phonenumbers.php
 *
 * Sweet-Dialer Twilio Phone Numbers List View
 *
 * Displays all Twilio phone numbers with assignment status from the CTI account.
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'include/MVC/View/SugarView.php';
require_once 'custom/include/TwilioDialer/TwilioApiClient.php';
require_once 'custom/include/TwilioDialer/CredentialEncryption.php';

/**
 * Outr_CtiSettingsViewPhoneNumbers
 *
 * Custom list view for displaying Twilio phone numbers from the API
 */
class Outr_CtiSettingsViewPhoneNumbers extends SugarView
{
    /**
     * @var array Phone numbers fetched from Twilio
     */
    protected $phoneNumbers = [];

    /**
     * @var string Error message if API call fails
     */
    protected $errorMessage = '';

    /**
     * @var array Used phone SIDs from CTI settings
     */
    protected $assignedPhoneSids = [];

    /**
     * Display the phone numbers list
     *
     * @return void
     */
    public function display()
    {
        global $mod_strings, $app_strings, $current_user;

        // Check permissions
        if (!$this->checkAccess()) {
            $this->displayNoAccess();
            return;
        }

        // Load CTI settings for the current user
        $ctiSettings = $this->getCtiSettings($current_user->id);

        if (empty($ctiSettings)) {
            $this->errorMessage = 'No CTI settings found. Please configure your Twilio credentials first.';
        } else {
            // Fetch phone numbers from Twilio
            $this->fetchPhoneNumbers($ctiSettings);
        }

        // Load assigned phone SIDs
        $this->loadAssignedPhoneSids();

        // Assign to Smarty
        $this->ss->assign('MOD', $mod_strings);
        $this->ss->assign('APP', $app_strings);
        $this->ss->assign('PHONE_NUMBERS', $this->phoneNumbers);
        $this->ss->assign('ERROR_MESSAGE', $this->errorMessage);
        $this->ss->assign('ASSIGNED_SIDS', $this->assignedPhoneSids);
        $this->ss->assign('MODULE_TITLE', $this->getModuleTitle());

        // Fetch and assign the template
        $templateFile = $this->getTemplateFile();
        echo $this->ss->fetch($templateFile);
    }

    /**
     * Get template file path
     *
     * @return string
     */
    protected function getTemplateFile()
    {
        // Check for custom theme template first
        $customTemplate = 'custom/themes/' . SugarThemeRegistry::current()->dirName .
                         '/modules/outr_CtiSettings/tpls/phonenumbers.tpl';

        if (file_exists($customTemplate)) {
            return $customTemplate;
        }

        // Fall back to module template
        $moduleTemplate = 'custom/modules/outr_CtiSettings/tpls/phonenumbers.tpl';

        if (file_exists($moduleTemplate)) {
            return $moduleTemplate;
        }

        // Return default template path (will use inline display if not found)
        return $moduleTemplate;
    }

    /**
     * Build module title breadcrumb
     *
     * @return string HTML for module title
     */
    protected function getModuleTitle()
    {
        global $mod_strings;

        $moduleTitle = [];
        $moduleTitle[] = <<<HTML
<a href="index.php?module=outr_CtiSettings&action=index">
    {$mod_strings['LBL_MODULE_NAME']}
</a>
HTML;
        $moduleTitle[] = $mod_strings['LBL_TWILIO_PHONE_NUMBERS'] ?? 'Twilio Phone Numbers';

        return $this->buildModuleTitle($moduleTitle, true);
    }

    /**
     * Check if user has access to view phone numbers
     *
     * @return bool
     */
    protected function checkAccess()
    {
        global $current_user;

        // Check module access
        if (!ACLController::checkAccess('outr_CtiSettings', 'view', $current_user)) {
            return false;
        }

        return true;
    }

    /**
     * Display access denied message
     *
     * @return void
     */
    protected function displayNoAccess()
    {
        global $app_strings;
        echo <<<HTML
<div class="error">
    {$app_strings['LBL_NO_ACCESS']}
</div>
HTML;
    }

    /**
     * Get CTI settings for the user
     *
     * @param string $userId User ID
     * @return Outr_CtiSettings|null
     */
    protected function getCtiSettings($userId)
    {
        $bean = BeanFactory::getBean('outr_CtiSettings');

        // Find active CTI settings for user
        $settings = $bean->get_list(
            "",
            "outr_ctisettings.assigned_user_id = '{$userId}'",
            "",
            "",
            1
        );

        if (!empty($settings['list']) && count($settings['list']) > 0) {
            $ctiBean = reset($settings['list']);

            // Decrypt sensitive fields
            global $sugar_config;
            $encryption = new CredentialEncryption($sugar_config);

            if (!empty($ctiBean->auth_token) && $encryption->isEncrypted($ctiBean->auth_token)) {
                $decrypted = $encryption->decrypt($ctiBean->auth_token);
                if ($decrypted !== false) {
                    $ctiBean->auth_token = $decrypted;
                }
            }

            return $ctiBean;
        }

        // Try to get default/global settings
        $settings = $bean->get_list(
            "",
            "outr_ctisettings.is_default = 1",
            "",
            "",
            1
        );

        if (!empty($settings['list']) && count($settings['list']) > 0) {
            $ctiBean = reset($settings['list']);

            // Decrypt sensitive fields
            global $sugar_config;
            $encryption = new CredentialEncryption($sugar_config);

            if (!empty($ctiBean->auth_token) && $encryption->isEncrypted($ctiBean->auth_token)) {
                $decrypted = $encryption->decrypt($ctiBean->auth_token);
                if ($decrypted !== false) {
                    $ctiBean->auth_token = $decrypted;
                }
            }

            return $ctiBean;
        }

        return null;
    }

    /**
     * Fetch phone numbers from Twilio API
     *
     * @param Outr_CtiSettings $ctiSettings
     * @return void
     */
    protected function fetchPhoneNumbers($ctiSettings)
    {
        if (empty($ctiSettings->account_sid) || empty($ctiSettings->auth_token)) {
            $this->errorMessage = 'Invalid CTI settings: Account SID and Auth Token are required.';
            return;
        }

        try {
            $client = new TwilioApiClient($ctiSettings->account_sid, $ctiSettings->auth_token);
            $this->phoneNumbers = $client->fetchPhoneNumbers();

            if (empty($this->phoneNumbers)) {
                $this->errorMessage = 'No phone numbers found in your Twilio account.';
            }

        } catch (TwilioApiException $e) {
            $this->errorMessage = 'Failed to fetch phone numbers: ' . $e->getMessage();
            $GLOBALS['log']->error('SweetDialer: Failed to fetch phone numbers - ' . $e->getMessage());
        } catch (Exception $e) {
            $this->errorMessage = 'Unexpected error: ' . $e->getMessage();
            $GLOBALS['log']->error('SweetDialer: Unexpected error fetching phone numbers - ' . $e->getMessage());
        }
    }

    /**
     * Load all assigned phone SIDs from CTI settings
     *
     * @return void
     */
    protected function loadAssignedPhoneSids()
    {
        $db = DBManagerFactory::getInstance();
        $table = 'outr_ctisettings';

        $query = "SELECT DISTINCT phone_sid FROM {$table} WHERE deleted = 0 AND phone_sid IS NOT NULL AND phone_sid != ''";
        $result = $db->query($query);

        while ($row = $db->fetchByAssoc($result)) {
            $this->assignedPhoneSids[] = $row['phone_sid'];
        }
    }
}
