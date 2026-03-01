<?php
/**
 * S-124-S-126: Call Reports DetailView Definition
 *
 * @package SweetDialer
 * @subpackage Reporting
 */

$viewdefs['OutrReports']['DetailView'] = array(
    'templateMeta' => array(
        'form' => array(
            'buttons' => array(
                'EDIT',
                'DUPLICATE',
                'DELETE',
                array(
                    'customCode' => '<input type="button" class="button" value="{$MOD.LBL_GENERATE_REPORT}" onclick="window.location.href=\'index.php?module=OutrReports&action=generateReport&record={$fields.id.value}\'"/>',
                ),
                array(
                    'customCode' => '<input type="button" class="button" value="{$MOD.LBL_EXPORT_CSV}" onclick="window.location.href=\'index.php?module=OutrReports&action=exportReport&format=csv&record={$fields.id.value}\'"/>',
                ),
                array(
                    'customCode' => '<input type="button" class="button" value="{$MOD.LBL_EXPORT_PDF}" onclick="window.location.href=\'index.php?module=OutrReports&action=exportReport&format=pdf&record={$fields.id.value}\'"/>',
                ),
            ),
            'hidden' => array(),
            'headerTpl' => 'modules/OutrReports/tpls/DetailViewHeader.tpl',
        ),
        'maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30'),
        ),
        'useTabs' => false,
        'tabDefs' => array(
            'LBL_REPORT_DETAILS' => array(
                'newTab' => false,
                'panelDefault' => 'expanded',
            ),
            'LBL_REPORT_RESULTS' => array(
                'newTab' => false,
                'panelDefault' => 'expanded',
            ),
        ),
    ),
    'panels' => array(
        'lbl_report_details' => array(
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
                    'name' => 'total_calls',
                    'label' => 'LBL_TOTAL_CALLS',
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
                    'name' => 'created_by_name',
                    'label' => 'LBL_CREATED_BY',
                ),
                array(
                    'name' => 'date_entered',
                    'label' => 'LBL_DATE_ENTERED',
                ),
            ),
            array(
                'description',
            ),
        ),
        'lbl_report_results' => array(
            array(
                array(
                    'name' => 'report_results',
                    'label' => 'LBL_REPORT_RESULTS',
                    'customCode' => '{include file="modules/OutrReports/tpls/ReportResults.tpl"}',
                ),
            ),
        ),
    ),
);
