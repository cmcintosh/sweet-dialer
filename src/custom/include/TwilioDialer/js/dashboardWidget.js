/**
 * S-127-S-129: Dashboard Widget JavaScript
 *
 * @package SweetDialer
 * @subpackage Reporting
 */

(function($) {
    'use strict';

    // Dashboard Widget Namespace
    window.OutrDashboardWidget = window.OutrDashboardWidget || {};

    /**
     * Initialize the dashboard widget
     */
    OutrDashboardWidget.init = function() {
        this.refreshInterval = window.outrDashboardConfig?.refreshInterval || 60;
        this.apiEndpoint = 'index.php?entryPoint=dialerDashboard';
        this.container = $('#outr-dashboard-widget');
        
        if (this.container.length === 0) {
            this.injectWidget();
        }
        
        this.loadDashboardData();
        this.startAutoRefresh();
        this.bindEvents();
    };

    /**
     * Inject the dashboard widget into the page
     */
    OutrDashboardWidget.injectWidget = function() {
        var widgetHtml = '<div id="outr-dashboard-widget" class="outr-dashboard-panel">' +
            '<div class="outr-dashboard-header">' +
                '<h3><i class="fa fa-phone"></i> ' + SUGAR.language.get('app_strings', 'LBL_OUTR_CALL_DASHBOARD') + '</h3>' +
                '<div class="outr-dashboard-controls">' +
                    '<span class="outr-last-updated"></span>' +
                    '<button class="outr-refresh-btn" title="' + SUGAR.language.get('app_strings', 'LBL_REFRESH') + '">' +
                        '<i class="fa fa-refresh"></i>' +
                    '</button>' +
                '</div>' +
            '</div>' +
            '<div class="outr-dashboard-content">' +
                '<div class="outr-metrics-grid">' +
                    '<div class="outr-metric-card outr-metric-total">' +
                        '<div class="outr-metric-icon"><i class="fa fa-phone"></i></div>' +
                        '<div class="outr-metric-data">' +
                            '<span class="outr-metric-value" id="outr-total-calls">-</span>' +
                            '<span class="outr-metric-label">' + SUGAR.language.get('app_strings', 'LBL_OUTR_TOTAL_CALLS') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="outr-metric-card outr-metric-inbound">' +
                        '<div class="outr-metric-icon"><i class="fa fa-arrow-down"></i></div>' +
                        '<div class="outr-metric-data">' +
                            '<span class="outr-metric-value" id="outr-inbound-calls">-</span>' +
                            '<span class="outr-metric-label">' + SUGAR.language.get('app_strings', 'LBL_OUTR_INBOUND_CALLS') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="outr-metric-card outr-metric-outbound">' +
                        '<div class="outr-metric-icon"><i class="fa fa-arrow-up"></i></div>' +
                        '<div class="outr-metric-data">' +
                            '<span class="outr-metric-value" id="outr-outbound-calls">-</span>' +
                            '<span class="outr-metric-label">' + SUGAR.language.get('app_strings', 'LBL_OUTR_OUTBOUND_CALLS') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="outr-metric-card outr-metric-missed">' +
                        '<div class="outr-metric-icon"><i class="fa fa-times-circle"></i></div>' +
                        '<div class="outr-metric-data">' +
                            '<span class="outr-metric-value" id="outr-missed-calls">-</span>' +
                            '<span class="outr-metric-label">' + SUGAR.language.get('app_strings', 'LBL_OUTR_MISSED_CALLS') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="outr-metric-card outr-metric-avg-duration">' +
                        '<div class="outr-metric-icon"><i class="fa fa-clock-o"></i></div>' +
                        '<div class="outr-metric-data">' +
                            '<span class="outr-metric-value" id="outr-avg-duration">-</span>' +
                            '<span class="outr-metric-label">' + SUGAR.language.get('app_strings', 'LBL_OUTR_AVG_DURATION') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="outr-metric-card outr-metric-success-rate">' +
                        '<div class="outr-metric-icon"><i class="fa fa-check-circle"></i></div>' +
                        '<div class="outr-metric-data">' +
                            '<span class="outr-metric-value" id="outr-success-rate">-</span>' +
                            '<span class="outr-metric-label">' + SUGAR.language.get('app_strings', 'LBL_OUTR_SUCCESS_RATE') + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="outr-recent-calls">' +
                    '<h4>' + SUGAR.language.get('app_strings', 'LBL_OUTR_RECENT_CALLS') + '</h4>' +
                    '<div id="outr-recent-calls-list" class="outr-calls-list"></div>' +
                '</div>' +
            '</div>' +
        '</div>';

        // Try to inject into the dashboard home
        if ($('#dashboard-content').length > 0) {
            $('#dashboard-content').prepend(widgetHtml);
        } else if ($('#content').length > 0) {
            $('#content').prepend(widgetHtml);
        }

        this.container = $('#outr-dashboard-widget');
    };

    /**
     * Load dashboard data via AJAX
     */
    OutrDashboardWidget.loadDashboardData = function() {
        var self = this;
        
        $.ajax({
            url: this.apiEndpoint,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    self.updateMetrics(response.data);
                    self.updateRecentCalls(response.data.recentCalls);
                    self.updateTimestamp();
                }
            },
            error: function(xhr, status, error) {
                console.error('Dashboard Widget Error:', error);
            }
        });
    };

    /**
     * Update metric values
     */
    OutrDashboardWidget.updateMetrics = function(data) {
        $('#outr-total-calls').text(data.totalCalls || 0);
        $('#outr-inbound-calls').text(data.inboundCalls || 0);
        $('#outr-outbound-calls').text(data.outboundCalls || 0);
        $('#outr-missed-calls').text(data.missedCalls || 0);
        $('#outr-avg-duration').text(data.avgDuration || '0:00');
        $('#outr-success-rate').text((data.successRate || 0) + '%');
    };

    /**
     * Update recent calls list
     */
    OutrDashboardWidget.updateRecentCalls = function(calls) {
        var html = '';
        var self = this;
        
        if (calls && calls.length > 0) {
            $.each(calls, function(index, call) {
                var directionIcon = call.direction === 'inbound' ? 'fa-arrow-down' : 'fa-arrow-up';
                var statusClass = self.getStatusClass(call.status);
                
                html += '<div class="outr-call-item ' + statusClass + '">' +
                    '<div class="outr-call-direction"><i class="fa ' + directionIcon + '"></i></div>' +
                    '<div class="outr-call-info">' +
                        '<span class="outr-call-number">' + (call.phoneNumber || 'Unknown') + '</span>' +
                        '<span class="outr-call-contact">' + (call.contactName || '-') + '</span>' +
                    '</div>' +
                    '<div class="outr-call-meta">' +
                        '<span class="outr-call-status">' + call.status + '</span>' +
                        '<span class="outr-call-time">' + call.timeAgo + '</span>' +
                    '</div>' +
                '</div>';
            });
        } else {
            html = '<div class="outr-no-calls">' + SUGAR.language.get('app_strings', 'LBL_OUTR_NO_RECENT_CALLS') + '</div>';
        }
        
        $('#outr-recent-calls-list').html(html);
    };

    /**
     * Get CSS class based on call status
     */
    OutrDashboardWidget.getStatusClass = function(status) {
        switch(status) {
            case 'completed':
                return 'outr-status-completed';
            case 'missed':
                return 'outr-status-missed';
            case 'voicemail':
                return 'outr-status-voicemail';
            default:
                return 'outr-status-other';
        }
    };

    /**
     * Update the last updated timestamp
     */
    OutrDashboardWidget.updateTimestamp = function() {
        var now = new Date();
        var timeString = now.toLocaleTimeString();
        $('.outr-last-updated').text(SUGAR.language.get('app_strings', 'LBL_OUTR_LAST_UPDATED') + ' ' + timeString);
    };

    /**
     * Start auto-refresh timer
     */
    OutrDashboardWidget.startAutoRefresh = function() {
        var self = this;
        
        // Clear existing interval
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
        
        // Set new interval (convert seconds to milliseconds)
        this.refreshTimer = setInterval(function() {
            self.loadDashboardData();
        }, this.refreshInterval * 1000);
    };

    /**
     * Bind event handlers
     */
    OutrDashboardWidget.bindEvents = function() {
        var self = this;
        
        // Manual refresh button
        $(document).on('click', '.outr-refresh-btn', function(e) {
            e.preventDefault();
            self.loadDashboardData();
        });

        // Pause refresh on page visibility change
        $(document).on('visibilitychange', function() {
            if (document.hidden) {
                clearInterval(self.refreshTimer);
            } else {
                self.startAutoRefresh();
                self.loadDashboardData();
            }
        });
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Only initialize on dashboard or home pages
        if ($('#dashboard-content').length > 0 || 
            window.location.href.indexOf('action=index') > -1 ||
            window.location.href.indexOf('module=Home') > -1) {
            OutrDashboardWidget.init();
        }
    });

})(jQuery);
