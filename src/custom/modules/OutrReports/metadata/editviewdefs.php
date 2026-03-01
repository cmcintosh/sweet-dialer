<?php
/**
 * S-124-S-126: Call Reports EditView Definition
 *
 * @package SweetDialer
 * @subpackage Reporting
 */

$viewdefs['OutrReports']['EditView'] = array(
    'templateMeta' => array(
        'form' => array(
            'hidden' => array(
                0 => '<input type="hidden" name="fp_events_leads_1fp_events_ida" value="{$fields.fp_events_leads_1fp_events_ida.value}">',
            ),
            'buttons' => array(
                'SAVE',
                'CANCEL',
            ),
        ),
        'maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30'),
        ),
        'useTabs' => false,
        'tabDefs' => array(
            'DEFAULT' => array(
                'newTab' => false,
                'panelDefault' => 'expanded',
            ),
        ),
        'syncDetailEditViews' => true,
    ),
    'panels' => array(
        'default' => array(
            array(
                'name',
                'assigned_user_name',
            ),
            array(
                array(
                    'name' => 'report_type',
                    'label' => 'LBL_REPORT_TYPE',
                ),
                array(
                    'name' => 'date_range',
                    'label' => 'LBL_DATE_RANGE',
                ),
            ),
            array(
                array(
                    'name' => 'date_start',
                    'label' => 'LBL_DATE_START',
                ),
                array(
                    'name' => 'date_end',
                    'label' => 'LBL_DATE_END',
                ),
            ),
            array(
                array(
                    'name' => 'filters',
                    'label' => 'LBL_FILTERS',
                ),
                array(
                    'name' => 'group_by',
                    'label' => 'LBL_GROUP_BY',
                ),
            ),
            array(
                'description',
            ),
        ),
    ),
);
