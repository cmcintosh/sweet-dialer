/**
 * S-133-S-135: Report Export JavaScript
 *
 * @package SweetDialer
 * @subpackage Reporting
 */

(function($) {
    'use strict';

    // Report Export Namespace
    window.OutrReportExport = window.OutrReportExport || {};

    // Export configuration
    OutrReportExport.config = {
        endpoint: 'index.php?entryPoint=exportReport',
        formats: ['csv', 'pdf'],
    };

    /**
     * Initialize export functionality
     */
    OutrReportExport.init = function() {
        this.bindEvents();
        this.injectExportButtons();
    };

    /**
     * Bind event handlers
     */
    OutrReportExport.bindEvents = function() {
        var self = this;

        // Export button click handlers
        $(document).on('click', '.outr-export-btn', function(e) {
            e.preventDefault();
            var format = $(this).data('format');
            var reportId = $(this).data('report-id');
            self.exportReport(format, reportId);
        });

        // Export modal handlers
        $(document).on('click', '.outr-export-trigger', function(e) {
            e.preventDefault();
            var reportId = $(this).data('report-id');
            self.showExportModal(reportId);
        });

        // Schedule report handler
        $(document).on('click', '.outr-schedule-report', function(e) {
            e.preventDefault();
            self.showScheduleModal();
        });
    };

    /**
     * Inject export buttons into report views
     */
    OutrReportExport.injectExportButtons = function() {
        // Check if we're on the reports page
        if ($('.outr-reports-list').length === 0 && 
            window.location.href.indexOf('module=OutrReports') === -1) {
            return;
        }

        // Add export buttons to list view actions
        if ($('#outr-reports-action-bar').length > 0) {
            var buttonsHtml = '<div class="outr-export-group">' +
                '<button class="outr-btn outr-btn-secondary outr-export-dropdown" data-toggle="dropdown">' +
                    '<i class="fa fa-download"></i> ' + 
                    SUGAR.language.get('app_strings', 'LBL_OUTR_EXPORT') + ' <span class="caret"></span>' +
                '</button>' +
                '<ul class="dropdown-menu">' +
                    '<li><a href="#" class="outr-export-trigger" data-report-id="">' + 
                        SUGAR.language.get('app_strings', 'LBL_OUTR_EXPORT_CURRENT') + '</a></li>' +
                    '<li class="divider"></li>' +
                    '<li><a href="#" class="outr-export-batch" data-format="csv">' + 
                        SUGAR.language.get('app_strings', 'LBL_OUTR_EXPORT_ALL_CSV') + '</a></li>' +
                    '<li><a href="#" class="outr-export-batch" data-format="pdf">' + 
                        SUGAR.language.get('app_strings', 'LBL_OUTR_EXPORT_ALL_PDF') + '</a></li>' +
                    '<li class="divider"></li>' +
                    '<li><a href="#" class="outr-schedule-report">' + 
                        SUGAR.language.get('app_strings', 'LBL_OUTR_SCHEDULE_REPORT') + '</a></li>' +
                '</ul>' +
            '</div>';

            $('#outr-reports-action-bar').append(buttonsHtml);
        }
    };

    /**
     * Show export modal with options
     */
    OutrReportExport.showExportModal = function(reportId) {
        var modalHtml = '<div id="outr-export-modal" class="modal fade" tabindex="-1" role="dialog">' +
            '<div class="modal-dialog" role="document">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                        '<h4 class="modal-title">' + SUGAR.language.get('app_strings', 'LBL_OUTR_EXPORT_REPORT') + '</h4>' +
                    '</div>' +
                    '<div class="modal-body">' +
                        '<form id="outr-export-form">' +
                            '<div class="form-group">' +
                                '<label>' + SUGAR.language.get('app_strings', 'LBL_OUTR_EXPORT_FORMAT') + '</label>' +
                                '<select class="form-control" name="format" id="outr-export-format">' +
                                    '<option value="csv">CSV (Excel)</option>' +
                                    '<option value="pdf">PDF Document</option>' +
                                '</select>' +
                            '</div>' +
                            '<div class="form-group">' +
                                '<label>' + SUGAR.language.get('app_strings', 'LBL_OUTR_DATE_RANGE') + '</label>' +
                                '<select class="form-control" name="range" id="outr-export-range">' +
                                    '<option value="today">' + SUGAR.language.get('app_strings', 'LBL_OUTR_TODAY') + '</option>' +
                                    '<option value="yesterday">' + SUGAR.language.get('app_strings', 'LBL_OUTR_YESTERDAY') + '</option>' +
                                    '<option value="last_7_days">' + SUGAR.language.get('app_strings', 'LBL_OUTR_LAST_7_DAYS') + '</option>' +
                                    '<option value="last_30_days" selected>' + SUGAR.language.get('app_strings', 'LBL_OUTR_LAST_30_DAYS') + '</option>' +
                                    '<option value="this_month">' + SUGAR.language.get('app_strings', 'LBL_OUTR_THIS_MONTH') + '</option>' +
                                    '<option value="last_month">' + SUGAR.language.get('app_strings', 'LBL_OUTR_LAST_MONTH') + '</option>' +
                                '</select>' +
                            '</div>' +
                        '</form>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                        '<button type="button" class="btn btn-default" data-dismiss="modal">' + 
                            SUGAR.language.get('app_strings', 'LBL_CANCEL_BUTTON_LABEL') + '</button>' +
                        '<button type="button" class="btn btn-primary outr-confirm-export" data-report-id="' + reportId + '" data-dismiss="modal">' + 
                            SUGAR.language.get('app_strings', 'LBL_EXPORT_BUTTON_LABEL') + '</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';

        // Remove existing modal
        $('#outr-export-modal').remove();
        $('body').append(modalHtml);

        // Bind export confirmation
        $('.outr-confirm-export').on('click', function() {
            var format = $('#outr-export-format').val();
            var range = $('#outr-export-range').val();
            var reportId = $(this).data('report-id');
            OutrReportExport.exportReport(format, reportId, range);
        });

        // Show modal
        $('#outr-export-modal').modal('show');
    };

    /**
     * Export a report
     */
    OutrReportExport.exportReport = function(format, reportId, range) {
        range = range || 'last_30_days';

        var url = this.config.endpoint + 
            '&format=' + encodeURIComponent(format) +
            '&range=' + encodeURIComponent(range);

        if (reportId) {
            url += '&savedReportId=' + encodeURIComponent(reportId);
        }

        // Open export in new window/tab
        window.open(url, '_blank');
    };

    /**
     * Show schedule report modal
     */
    OutrReportExport.showScheduleModal = function() {
        var modalHtml = '<div id="outr-schedule-modal" class="modal fade" tabindex="-1" role="dialog">' +
            '<div class="modal-dialog" role="document">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                        '<h4 class="modal-title">' + SUGAR.language.get('app_strings', 'LBL_OUTR_SCHEDULE_REPORT') + '</h4>' +
                    '</div>' +
                    '<div class="modal-body">' +
                        '<form id="outr-schedule-form">' +
                            '<div class="form-group">' +
                                '<label>' + SUGAR.language.get('app_strings', 'LBL_OUTR_SCHEDULE_FREQUENCY') + '</label>' +
                                '<select class="form-control" name="frequency">' +
                                    '<option value="daily">Daily</option>' +
                                    '<option value="weekly">Weekly</option>' +
                                    '<option value="monthly">Monthly</option>' +
                                '</select>' +
                            '</div>' +
                            '<div class="form-group">' +
                                '<label>' + SUGAR.language.get('app_strings', 'LBL_OUTR_SCHEDULE_EMAIL') + '</label>' +
                                '<input type="email" class="form-control" name="email" placeholder="email@example.com" />' +
                            '</div>' +
                            '<div class="form-group">' +
                                '<label>' + SUGAR.language.get('app_strings', 'LBL_OUTR_SCHEDULE_FORMAT') + '</label>' +
                                '<select class="form-control" name="schedule_format">' +
                                    '<option value="csv">CSV</option>' +
                                    '<option value="pdf">PDF</option>' +
                                '</select>' +
                            '</div>' +
                        '</form>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                        '<button type="button" class="btn btn-default" data-dismiss="modal">' + 
                            SUGAR.language.get('app_strings', 'LBL_CANCEL_BUTTON_LABEL') + '</button>' +
                        '<button type="button" class="btn btn-primary outr-confirm-schedule" data-dismiss="modal">' + 
                            SUGAR.language.get('app_strings', 'LBL_SAVE_BUTTON_LABEL') + '</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';

        // Remove existing modal
        $('#outr-schedule-modal').remove();
        $('body').append(modalHtml);

        // Bind schedule confirmation
        $('.outr-confirm-schedule').on('click', function() {
            OutrReportExport.saveSchedule();
        });

        // Show modal
        $('#outr-schedule-modal').modal('show');
    };

    /**
     * Save scheduled report
     */
    OutrReportExport.saveSchedule = function() {
        var formData = $('#outr-schedule-form').serialize();
        
        // Show success message (in production, this would save to the server)
        this.showNotification(SUGAR.language.get('app_strings', 'LBL_OUTR_SCHEDULE_SAVED'), 'success');
    };

    /**
     * Show notification
     */
    OutrReportExport.showNotification = function(message, type) {
        type = type || 'info';
        
        var alertClass = 'alert-' + type;
        var html = '<div class="alert ' + alertClass + ' outr-notification" style="position:fixed;top:20px;right:20px;z-index:9999;">' +
            message +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
        '</div>';

        $('.outr-notification').remove();
        $('body').append(html);

        setTimeout(function() {
            $('.outr-notification').fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    };

    /**
     * Set export configuration
     */
    OutrReportExport.setConfig = function(config) {
        $.extend(this.config, config);
    };

    /**
     * Batch export multiple reports
     */
    OutrReportExport.batchExport = function(reportIds, format) {
        var progress = 0;
        var total = reportIds.length;

        this.showNotification('Starting batch export of ' + total + ' reports...', 'info');

        reportIds.forEach(function(reportId, index) {
            setTimeout(function() {
                OutrReportExport.exportReport(format, reportId);
            }, index * 1000); // Stagger exports
        });
    };

    // Initialize on document ready
    $(document).ready(function() {
        OutrReportExport.init();
    });

})(jQuery);
