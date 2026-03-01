/**
 * S-130-S-132: Analytics Charts JavaScript
 *
 * @package SweetDialer
 * @subpackage Reporting
 */

(function($) {
    'use strict';

    // Analytics Charts Namespace
    window.OutrAnalyticsCharts = window.OutrAnalyticsCharts || {};

    // Chart instances
    OutrAnalyticsCharts.charts = {};

    // Default chart colors
    OutrAnalyticsCharts.colors = {
        primary: '#1976d2',
        success: '#388e3c',
        warning: '#f57c00',
        danger: '#d32f2f',
        info: '#7b1fa2',
        secondary: '#5d4037',
        light: '#90a4ae',
        palette: ['#1976d2', '#388e3c', '#f57c00', '#d32f2f', '#7b1fa2', '#5d4037', '#00897b', '#5e35b1']
    };

    /**
     * Initialize analytics page
     */
    OutrAnalyticsCharts.init = function() {
        this.apiEndpoint = 'index.php?entryPoint=analyticsData';
        this.currentRange = 'last_30_days';
        this.currentChart = 'calls_over_time';
        
        this.renderContainer();
        this.renderControls();
        this.renderChartContainer();
        this.bindEvents();
        this.loadChart('calls_over_time');
    };

    /**
     * Render main container
     */
    OutrAnalyticsCharts.renderContainer = function() {
        var html = '<div id="outr-analytics-container" class="outr-analytics-wrapper">' +
            '<div class="outr-analytics-header">' +
                '<h2><i class="fa fa-bar-chart"></i> ' + SUGAR.language.get('app_strings', 'LBL_OUTR_ANALYTICS') + '</h2>' +
            '</div>' +
            '<div class="outr-analytics-controls" id="outr-analytics-controls"></div>' +
            '<div class="outr-charts-grid" id="outr-charts-grid"></div>' +
        '</div>';

        $('#content').html(html);
    };

    /**
     * Render controls
     */
    OutrAnalyticsCharts.renderControls = function() {
        var html = '<div class="outr-controls-row">' +
            '<div class="outr-control-group">' +
                '<label>' + SUGAR.language.get('app_strings', 'LBL_OUTR_CHART_TYPE') + '</label>' +
                '<select id="outr-chart-select" class="outr-select">' +
                    '<option value="calls_over_time">' + SUGAR.language.get('app_strings', 'LBL_OUTR_CALLS_OVER_TIME') + '</option>' +
                    '<option value="calls_by_direction">' + SUGAR.language.get('app_strings', 'LBL_OUTR_CALLS_BY_DIRECTION') + '</option>' +
                    '<option value="calls_by_status">' + SUGAR.language.get('app_strings', 'LBL_OUTR_CALLS_BY_STATUS') + '</option>' +
                    '<option value="calls_by_hour">' + SUGAR.language.get('app_strings', 'LBL_OUTR_CALLS_BY_HOUR') + '</option>' +
                    '<option value="agent_performance">' + SUGAR.language.get('app_strings', 'LBL_OUTR_AGENT_PERFORMANCE') + '</option>' +
                    '<option value="call_duration">' + SUGAR.language.get('app_strings', 'LBL_OUTR_DURATION_DISTRIBUTION') + '</option>' +
                    '<option value="conversion_rate">' + SUGAR.language.get('app_strings', 'LBL_OUTR_CONVERSION_RATE') + '</option>' +
                '</select>' +
            '</div>' +
            '<div class="outr-control-group outr-date-range-group">' +
                '<label>' + SUGAR.language.get('app_strings', 'LBL_OUTR_DATE_RANGE') + '</label>' +
                '<select id="outr-range-select" class="outr-select">' +
                    '<option value="today">' + SUGAR.language.get('app_strings', 'LBL_OUTR_TODAY') + '</option>' +
                    '<option value="yesterday">' + SUGAR.language.get('app_strings', 'LBL_OUTR_YESTERDAY') + '</option>' +
                    '<option value="last_7_days">' + SUGAR.language.get('app_strings', 'LBL_OUTR_LAST_7_DAYS') + '</option>' +
                    '<option value="last_30_days" selected>' + SUGAR.language.get('app_strings', 'LBL_OUTR_LAST_30_DAYS') + '</option>' +
                    '<option value="this_month">' + SUGAR.language.get('app_strings', 'LBL_OUTR_THIS_MONTH') + '</option>' +
                    '<option value="last_month">' + SUGAR.language.get('app_strings', 'LBL_OUTR_LAST_MONTH') + '</option>' +
                '</select>' +
            '</div>' +
            '<div class="outr-control-group outr-group-by-group" style="display:none;">' +
                '<label>' + SUGAR.language.get('app_strings', 'LBL_OUTR_GROUP_BY') + '</label>' +
                '<select id="outr-group-select" class="outr-select">' +
                    '<option value="day">' + SUGAR.language.get('app_strings', 'LBL_OUTR_BY_DAY') + '</option>' +
                    '<option value="week">' + SUGAR.language.get('app_strings', 'LBL_OUTR_BY_WEEK') + '</option>' +
                    '<option value="month">' + SUGAR.language.get('app_strings', 'LBL_OUTR_BY_MONTH') + '</option>' +
                '</select>' +
            '</div>' +
            '<div class="outr-control-group">' +
                '<button id="outr-refresh-chart" class="outr-btn outr-btn-primary">' +
                    '<i class="fa fa-refresh"></i> ' + SUGAR.language.get('app_strings', 'LBL_REFRESH') +
                '</button>' +
                '<button id="outr-export-chart" class="outr-btn outr-btn-secondary">' +
                    '<i class="fa fa-download"></i> ' + SUGAR.language.get('app_strings', 'LBL_EXPORT') +
                '</button>' +
            '</div>' +
        '</div>';

        $('#outr-analytics-controls').html(html);
    };

    /**
     * Render chart container
     */
    OutrAnalyticsCharts.renderChartContainer = function() {
        var html = '<div class="outr-chart-card outr-main-chart">' +
            '<div class="outr-chart-header">' +
                '<h4 id="outr-chart-title">' + SUGAR.language.get('app_strings', 'LBL_OUTR_CALLS_OVER_TIME') + '</h4>' +
            '</div>' +
            '<div class="outr-chart-body">' +
                '<div id="outr-chart-container" style="height: 400px;"></div>' +
            '</div>' +
        '</div>' +
        '<div class="outr-chart-card outr-summary-stats" id="outr-summary-stats"></div>';

        $('#outr-charts-grid').html(html);
    };

    /**
     * Bind event handlers
     */
    OutrAnalyticsCharts.bindEvents = function() {
        var self = this;

        $('#outr-chart-select').on('change', function() {
            self.currentChart = $(this).val();
            self.updateGroupByVisibility();
            self.loadChart(self.currentChart);
        });

        $('#outr-range-select').on('change', function() {
            self.currentRange = $(this).val();
            self.loadChart(self.currentChart);
        });

        $('#outr-group-select').on('change', function() {
            if (self.currentChart === 'calls_over_time') {
                self.loadChart(self.currentChart);
            }
        });

        $('#outr-refresh-chart').on('click', function() {
            self.loadChart(self.currentChart);
        });

        $('#outr-export-chart').on('click', function() {
            self.exportChart();
        });
    };

    /**
     * Update group by visibility
     */
    OutrAnalyticsCharts.updateGroupByVisibility = function() {
        if (this.currentChart === 'calls_over_time') {
            $('.outr-group-by-group').show();
        } else {
            $('.outr-group-by-group').hide();
        }
    };

    /**
     * Load chart data
     */
    OutrAnalyticsCharts.loadChart = function(chartType) {
        var self = this;
        var groupBy = $('#outr-group-select').val() || 'day';

        // Show loading
        $('#outr-chart-container').html('<div class="outr-loading"><i class="fa fa-spinner fa-spin"></i> Loading...');</div>');

        $.ajax({
            url: this.apiEndpoint,
            type: 'GET',
            data: {
                chart: chartType,
                range: this.currentRange,
                groupBy: groupBy
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    self.renderChart(chartType, response.data);
                    self.renderSummary(response.data);
                } else {
                    $('#outr-chart-container').html('<div class="outr-error">Error: ' + (response.error || 'Unknown error') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#outr-chart-container').html('<div class="outr-error">Failed to load data</div>');
            }
        });
    };

    /**
     * Render chart based on type
     */
    OutrAnalyticsCharts.renderChart = function(chartType, data) {
        switch (chartType) {
            case 'calls_over_time':
                this.renderLineChart(data);
                break;
            case 'calls_by_direction':
            case 'calls_by_status':
            case 'call_duration':
                this.renderPieChart(chartType, data);
                break;
            case 'calls_by_hour':
            case 'agent_performance':
                this.renderBarChart(chartType, data);
                break;
            case 'conversion_rate':
                this.renderConversionRate(data);
                break;
        }
    };

    /**
     * Render line chart for calls over time
     */
    OutrAnalyticsCharts.renderLineChart = function(data) {
        var labels = data.map(function(d) { return d.period; });
        var totalData = data.map(function(d) { return d.total; });
        var inboundData = data.map(function(d) { return d.inbound; });
        var outboundData = data.map(function(d) { return d.outbound; });

        var datasets = [{
            label: 'Total Calls',
            data: totalData,
            borderColor: this.colors.primary,
            backgroundColor: this.colors.primary + '20',
            fill: true,
            tension: 0.4
        }];

        if (data.some(function(d) { return d.inbound > 0 || d.outbound > 0; })) {
            datasets.push({
                label: 'Inbound',
                data: inboundData,
                borderColor: this.colors.success,
                backgroundColor: this.colors.success + '20',
                fill: false,
                tension: 0.4
            });
            datasets.push({
                label: 'Outbound',
                data: outboundData,
                borderColor: this.colors.warning,
                backgroundColor: this.colors.warning + '20',
                fill: false,
                tension: 0.4
            });
        }

        this.createChart('line', labels, datasets);
    };

    /**
     * Render pie/doughnut chart
     */
    OutrAnalyticsCharts.renderPieChart = function(chartType, data) {
        var labels = [];
        var values = [];
        var key = 'count';

        data.forEach(function(d) {
            if (chartType === 'calls_by_direction') {
                labels.push(d.direction.charAt(0).toUpperCase() + d.direction.slice(1));
            } else if (chartType === 'calls_by_status') {
                labels.push(d.label);
            } else if (chartType === 'call_duration') {
                labels.push(d.range);
            }
            values.push(d[key]);
        });

        var datasets = [{
            data: values,
            backgroundColor: this.colors.palette,
            borderWidth: 2,
            borderColor: '#fff'
        }];

        this.createChart('doughnut', labels, datasets);
    };

    /**
     * Render bar chart
     */
    OutrAnalyticsCharts.renderBarChart = function(chartType, data) {
        var labels = [];
        var values = [];

        data.forEach(function(d) {
            if (chartType === 'calls_by_hour') {
                labels.push(d.hourLabel);
            } else if (chartType === 'agent_performance') {
                labels.push(d.agentName);
            }
            values.push(d.count || d.totalCalls);
        });

        var datasets = [{
            label: 'Calls',
            data: values,
            backgroundColor: this.colors.primary,
            borderColor: this.colors.primary,
            borderWidth: 1
        }];

        this.createChart('bar', labels, datasets);
    };

    /**
     * Render conversion rate chart
     */
    OutrAnalyticsCharts.renderConversionRate = function(data) {
        var html = '<div class="outr-conversion-stats">' +
            '<div class="outr-conversion-card">' +
                '<div class="outr-conversion-value" style="font-size: 48px; color: ' + this.colors.success + ';">' + data.conversionRate + '%</div>' +
                '<div class="outr-conversion-label">Conversion Rate</div>' +
            '</div>' +
            '<div class="outr-conversion-details">' +
                '<table class="outr-stats-table">' +
                    '<tr><td>Total Calls</td><td class="outr-stat-value">' + data.totalCalls + '</td></tr>' +
                    '<tr><td>With Contact</td><td class="outr-stat-value">' + data.callsWithContact + '</td></tr>' +
                    '<tr><td>With Result</td><td class="outr-stat-value">' + data.callsWithResult + '</td></tr>' +
                    '<tr><td>Related Leads</td><td class="outr-stat-value">' + data.relatedLeads + '</td></tr>' +
                    '<tr><td>Related Contacts</td><td class="outr-stat-value">' + data.relatedContacts + '</td></tr>' +
                '</table>' +
            '</div>' +
        '</div>';

        $('#outr-chart-container').html(html);
    };

    /**
     * Create chart using Chart.js or canvas fallback
     */
    OutrAnalyticsCharts.createChart = function(type, labels, datasets) {
        var canvas = document.createElement('canvas');
        canvas.id = 'outr-analytics-canvas';
        canvas.style.height = '100%';
        canvas.style.width = '100%';

        $('#outr-chart-container').empty().append(canvas);

        // Simple SVG chart renderer (fallback for environments without Chart.js)
        if (typeof Chart === 'undefined') {
            this.renderSVGFallback(type, labels, datasets);
            return;
        }

        var ctx = canvas.getContext('2d');

        // Destroy existing chart
        if (this.charts.main) {
            this.charts.main.destroy();
        }

        var config = {
            type: type,
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: type === 'doughnut' ? {} : {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        };

        this.charts.main = new Chart(ctx, config);
    };

    /**
     * Render SVG fallback chart
     */
    OutrAnalyticsCharts.renderSVGFallback = function(type, labels, datasets) {
        if (type === 'line') {
            this.renderSVGLineChart(labels, datasets);
        } else if (type === 'doughnut') {
            this.renderSVGDoughnut(labels, datasets);
        } else {
            this.renderSVGBarChart(labels, datasets);
        }
    };

    /**
     * Render simple SVG bar chart
     */
    OutrAnalyticsCharts.renderSVGBarChart = function(labels, datasets) {
        var data = datasets[0].data;
        var maxVal = Math.max.apply(null, data);
        var height = 350;
        var width = $('#outr-chart-container').width();
        var barWidth = (width / data.length) * 0.7;
        var gap = (width / data.length) * 0.3;

        var svg = '<svg width="' + width + '" height="' + (height + 50) + '" xmlns="http://www.w3.org/2000/svg">';

        // Bars
        data.forEach(function(val, i) {
            var barHeight = (val / maxVal) * height;
            var x = i * (barWidth + gap) + gap / 2;
            var y = height - barHeight + 20;

            svg += '<rect x="' + x + '" y="' + y + '" width="' + barWidth + '" height="' + barHeight + '" fill="' + OutrAnalyticsCharts.colors.primary + '" rx="2" />';
            svg += '<text x="' + (x + barWidth/2) + '" y="' + (y - 5) + '" text-anchor="middle" font-size="11" fill="#333">' + val + '</text>';

            // Labels (limited)
            if (labels.length <= 10 || i % Math.ceil(labels.length / 10) === 0) {
                var label = labels[i].length > 10 ? labels[i].substring(0, 10) + '...' : labels[i];
                svg += '<text x="' + (x + barWidth/2) + '" y="' + (height + 40) + '" text-anchor="middle" font-size="10" fill="#666" transform="rotate(-45 ' + (x + barWidth/2) + ' ' + (height + 40) + ')">' + label + '</text>';
            }
        });

        svg += '</svg>';
        $('#outr-chart-container').html(svg);
    };

    /**
     * Render summary statistics
     */
    OutrAnalyticsCharts.renderSummary = function(data) {
        // Summary rendering based on data type
        var html = '';
        $('#outr-summary-stats').html(html);
    };

    /**
     * Export chart data
     */
    OutrAnalyticsCharts.exportChart = function() {
        var chartType = this.currentChart;
        var range = this.currentRange;
        window.open('index.php?entryPoint=analyticsData&chart=' + chartType + '&range=' + range + '&format=csv', '_blank');
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#outr-analytics-container').length === 0 && 
            window.location.href.indexOf('module=OutrReports') > -1 &&
            window.location.href.indexOf('action=Analytics') > -1) {
            OutrAnalyticsCharts.init();
        }
    });

})(jQuery);
