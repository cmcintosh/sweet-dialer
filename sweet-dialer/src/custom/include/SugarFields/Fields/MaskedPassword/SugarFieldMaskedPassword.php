/**
 * SugarFieldMaskedPassword.php
 *
 * Sweet-Dialer Custom SugarField for Masked Password Display
 *
 * Displays sensitive fields (auth_token, api_key_secret) as masked (********)
 * with a "Change" button to reveal and update the value.
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'include/SugarFields/Fields/Password/SugarFieldPassword.php';

/**
 * SugarFieldMaskedPassword
 *
 * Extended password field that shows masking for existing values
 * with Change button functionality (S-043)
 */
class SugarFieldMaskedPassword extends SugarFieldPassword
{
    /**
     * @var string The mask value
     */
    const MASK_VALUE = '********';

    /**
     * Get field edit view HTML
     *
     * @param string $parentFieldArray
     * @param array $vardef
     * @param SugarBean $parentFocus
     * @param string $tabindex
     * @param string $additionalViewParams
     * @return string
     */
    public function getEditViewSmarty($parentFieldArray, $vardef, $parentFocus, $tabindex, $additionalViewParams = [])
    {
        // Mark as masked password field
        $vardef['is_masked_field'] = true;

        // Get the base edit view HTML
        $html = parent::getEditViewSmarty($parentFieldArray, $vardef, $parentFocus, $tabindex, $additionalViewParams);

        return $html;
    }

    /**
     * Format the field for display in edit view
     *
     * @param string $rawField
     * @param string $vardefName
     * @param array $vardef
     * @param string $focus
     * @param string $tabindex
     * @return array
     */
    public function getEditView($rawField, $vardefName, $vardef, $focus, $tabindex = '0')
    {
        $result = parent::getEditView($rawField, $vardefName, $vardef, $focus, $tabindex);

        // If field has a value (from after_retrieve hook), it's already masked
        // Just add the CSS class and data attribute
        $result['field'] = $this->addMaskedFieldAttributes($result['field'], $vardefName);

        // Add the Change button HTML
        $result['field'] .= $this->getChangeButtonHtml($vardefName);

        return $result;
    }

    /**
     * Add masked field attributes to input
     *
     * @param string $fieldHtml
     * @param string $vardefName
     * @return string
     */
    protected function addMaskedFieldAttributes($fieldHtml, $vardefName)
    {
        // Add data attribute for JS identification
        $fieldHtml = str_replace(
            'type="password"',
            'type="password" data-sweetdialer-field="' . $vardefName . '"',
            $fieldHtml
        );

        // Add special class
        $fieldHtml = str_replace(
            'class="',
            'class="sweetdialer-masked-field sweetdialer-password-field ',
            $fieldHtml
        );

        // If value is already set (masked), ensure it shows as password type
        if (strpos($fieldHtml, 'value="********"') !== false || strpos($fieldHtml, 'value=""') === false && strpos($fieldHtml, 'value=') !== false) {
            $fieldHtml = str_replace('type="text"', 'type="password"', $fieldHtml);
        }

        return $fieldHtml;
    }

    /**
     * Get Change button HTML
     *
     * @param string $vardefName
     * @return string
     */
    protected function getChangeButtonHtml($vardefName)
    {
        $buttonId = 'change_' . $vardefName;
        $btnText = 'Change';

        return <<<HTML
<button type="button" 
    id="{$buttonId}" 
    class="button sweetdialer-change-btn" 
    data-field="{$vardefName}"
    onclick="SweetDialer.EditView.toggleMaskedField($('[data-sweetdialer-field=\"{$vardefName}\"]'));">
    {$btnText}
</button>
HTML;
    }

    /**
     * Display the field in detail view
     *
     * @param string $parentFieldArray
     * @param array $vardef
     * @param SugarBean $parentFocus
     * @param string $tabindex
     * @return string
     */
    public function getDetailViewSmarty($parentFieldArray, $vardef, $parentFocus, $tabindex)
    {
        // Always show masked in detail view
        $this->smarty = $this->createSmarty($parentFieldArray, $vardef, $parentFocus, $tabindex);
        $this->smarty->assign('field_value', self::MASK_VALUE);

        return $this->fetch($this->findTemplate('DetailView'));
    }

    /**
     * Check if a value is a masked value
     *
     * @param string $value
     * @return bool
     */
    public static function isMasked($value)
    {
        return $value === self::MASK_VALUE;
    }

    /**
     * Unformat the field value before saving
     *
     * @param string $field
     * @param string $value
     * @return string
     */
    public function unformatField($field, $value)
    {
        // If value is masked, return empty to preserve existing value
        // The logic hook will handle preserving the original value
        if (self::isMasked($value)) {
            return ''; // Signal to preserve existing
        }

        return $value;
    }
}
