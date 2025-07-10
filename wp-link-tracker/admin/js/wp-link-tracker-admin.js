/**
 * Admin JavaScript for WP Link Tracker
 */
(function($) {
    'use strict';

    // Debug: Check if script is loading
    console.log('WP Link Tracker Admin JS loaded');

    // Initialize when document is ready
    $(document).ready(function() {
        console.log('Document ready - WP Link Tracker');
        
        // Test if jQuery is working
        console.log('jQuery version:', $.fn.jquery);
        
        // Check if copy buttons exist
        var copyButtons = $('.copy-to-clipboard');
        console.log('Found copy buttons:', copyButtons.length);
        
        // Copy to clipboard functionality
        $(document).on('click', '.copy-to-clipboard', function(e) {
            e.preventDefault();
            console.log('Copy button clicked!');
            
            var $button = $(this);
            var text = $button.attr('data-clipboard-text');
            var originalText = $button.text();
            
            console.log('Text to copy:', text);
            
            if (!text) {
                console.error('No text to copy found');
                alert('Debug: No text found to copy. Check console for details.');
                return;
            }
            
            // Try modern clipboard API first
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    console.log('Successfully copied:', text);
                    $button.text('Copied!');
                    setTimeout(function() {
                        $button.text(originalText);
                    }, 2000);
                }).catch(function(err) {
                    console.error('Could not copy text: ', err);
                    fallbackCopyTextToClipboard(text, $button, originalText);
                });
            } else {
                console.log('Using fallback copy method');
                fallbackCopyTextToClipboard(text, $button, originalText);
            }
        });

        // Initialize dashboard if we're on the dashboard page
        if ($('#wplinktracker-clicks-chart').length) {
            console.log('Initializing dashboard');
            initDashboard();
        }
    });

    /**
     * Fallback copy function for older browsers
     */
    function fallbackCopyTextToClipboard(text, $button, originalText) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        
        console.log('Fallback copy - text to copy:', text);
        
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            var successful = document.execCommand('copy');
            console.log('execCommand copy result:', successful);
            if (successful) {
                $button.text('Copied!');
                setTimeout(function() {
                    $button.text(originalText);
                }, 2000);
            } else {
                $button.text('Failed');
                setTimeout(function() {
                    $button.text(originalText);
                }, 2000);
            }
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
            $button.text('Failed');
            setTimeout(function() {
                $button.text(originalText);
            }, 2000);
        }
        
        document.body.removeChild(textArea);
    }

    /**
     * Initialize dashboard functionality
     */
    function initDashboard() {
        console.log('Dashboard initialization started');
        
        // Initialize charts
        initDashboardCharts();
        
        // Load initial stats
        loadDashboardStats();
        
        // Set up event handlers
        setupEventHandlers();
        
        console.log('Dashboard initialization completed');
    }

    /**
     * Set up all event handlers for dashboard buttons
     */
    function setupEventHandlers() {
        console.log('Setting up event handlers');
        
        // Date range select change
        $('#wplinktracker-date-range-select').off('change').on('change', function() {
            console.log('Date range select changed');
            var value = $(this).val();
            
            if (value === 'custom') {
                $('#wplinktracker-custom-date-range').show();
            } else {
                $('#wplinktracker-custom-date-range').hide();
                loadDashboardStats(value);
                console.log('Date range changed to: ' + value + ' days');
            }
        });

        // Refresh dashboard button
        $('#wplinktracker-refresh-dashboard').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('Refresh dashboard button clicked');
            
            var selectedRange = $('#wplinktracker-date-range-select').val();
            
            if (selectedRange === 'custom') {
                var fromDate = $('#wplinktracker-date-from').val();
                var toDate = $('#wplinktracker-date-to').val();
                
                if (fromDate && toDate) {
                    loadDashboardStats(null, fromDate, toDate);
                } else {
                    loadDashboardStats(30);
                }
            } else {
                loadDashboardStats(selectedRange);
            }
        });

        // Refresh data button
        $('#wplinktracker-refresh-data').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('Refresh data button clicked');
            
            var selectedRange = $('#wplinktracker-date-range-select').val();
            
            if (selectedRange === 'custom') {
                var fromDate = $('#wplinktracker-date-from').val();
                var toDate = $('#wplinktracker-date-to').val();
                
                if (fromDate && toDate) {
                    loadDashboardStats(null, fromDate, toDate);
                } else {
                    loadDashboardStats(30);
                }
            } else {
                loadDashboardStats(selectedRange);
            }
        });

        // View data count button
        $('#wplinktracker-view-data-count').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('View data count button clicked');
            
            var selectedRange = $('#wplinktracker-date-range-select').val();
            
            if (selectedRange === 'custom') {
                var fromDate = $('#wplinktracker-date-from').val();
                var toDate = $('#wplinktracker-date-to').val();
                
                if (fromDate && toDate) {
                    viewDataCount(null, fromDate, toDate);
                } else {
                    viewDataCount(30);
                }
            } else {
                viewDataCount(selectedRange);
            }
        });

        // Debug date range button
        $('#wplinktracker-debug-date-range').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('Debug date range button clicked');
            
            var selectedRange = $('#wplinktracker-date-range-select').val();
            
            if (selectedRange === 'custom') {
                var fromDate = $('#wplinktracker-date-from').val();
                var toDate = $('#wplinktracker-date-to').val();
                
                if (fromDate && toDate) {
                    debugDateRange(null, fromDate, toDate);
                } else {
                    debugDateRange(30);
                }
            } else {
                debugDateRange(selectedRange);
            }
        });

        // Apply custom date range button
        $('#wplinktracker-apply-date-range').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('Apply date range button clicked');
            
            var fromDate = $('#wplinktracker-date-from').val();
            var toDate = $('#wplinktracker-date-to').val();
            
            if (!fromDate || !toDate) {
                alert('Please select both from and to dates.');
                return;
            }
            
            loadDashboardStats(null, fromDate, toDate);
            console.log('Custom date range applied: ' + fromDate + ' to ' + toDate);
        });
        
        console.log('Event handlers setup completed');
    }

    /**
     * Load dashboard statistics
     */
    function loadDashboardStats(days, dateFrom, dateTo) {
        console.log('Loading dashboard stats with params:', {days: days, dateFrom: dateFrom, dateTo: dateTo});
        
        // Show loading spinners
        $('#wplinktracker-total-clicks').html('<span class="spinner is-active" style="float: none; margin: 0;"></span>');
        $('#wplinktracker-unique-visitors').html('<span class="spinner is-active" style="float: none; margin: 0;"></span>');
        $('#wplinktracker-active-links').html('<span class="spinner is-active" style="float: none; margin: 0;"></span>');
        $('#wplinktracker-avg-conversion').html('<span class="spinner is-active" style="float: none; margin: 0;"></span>');
        
        var data = {
            action: 'wp_link_tracker_get_dashboard_stats',
            nonce: wpLinkTrackerAdmin.nonce
        };
        
        if (days) {
            data.days = days;
        }
        
        if (dateFrom && dateTo) {
            data.date_from = dateFrom;
            data.date_to = dateTo;
        }
        
        console.log('AJAX data being sent:', data);
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, data)
            .done(function(response) {
                console.log('Dashboard stats response:', response);
                
                if (response.success) {
                    var stats = response.data;
                    
                    // Update summary values
                    $('#wplinktracker-total-clicks').text(stats.total_clicks);
                    $('#wplinktracker-unique-visitors').text(stats.unique_visitors);
                    $('#wplinktracker-active-links').text(stats.active_links);
                    $('#wplinktracker-avg-conversion').text(stats.avg_conversion);
                    
                    console.log('Dashboard stats updated successfully');
                } else {
                    console.error('Failed to load dashboard stats:', response.data);
                    
                    // Show error state
                    $('#wplinktracker-total-clicks').text('Error');
                    $('#wplinktracker-unique-visitors').text('Error');
                    $('#wplinktracker-active-links').text('Error');
                    $('#wplinktracker-avg-conversion').text('Error');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('AJAX request failed for dashboard stats:', {xhr: xhr, status: status, error: error});
                
                // Show error state
                $('#wplinktracker-total-clicks').text('Error');
                $('#wplinktracker-unique-visitors').text('Error');
                $('#wplinktracker-active-links').text('Error');
                $('#wplinktracker-avg-conversion').text('Error');
            });
    }

    /**
     * Debug date range functionality
     */
    function debugDateRange(days, dateFrom, dateTo) {
        console.log('Debug date range called with params:', {days: days, dateFrom: dateFrom, dateTo: dateTo});
        
        var data = {
            action: 'wp_link_tracker_debug_date_range',
            nonce: wpLinkTrackerAdmin.nonce
        };
        
        if (days) {
            data.days = days;
        }
        
        if (dateFrom && dateTo) {
            data.date_from = dateFrom;
            data.date_to = dateTo;
        }
        
        console.log('Debug AJAX data being sent:', data);
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, data)
            .done(function(response) {
                console.log('Debug response:', response);
                
                if (response.success) {
                    var debug = response.data;
                    var message = 'Debug Information:\n\n';
                    message += 'Current Time: ' + debug.current_time + '\n';
                    message += 'Timezone: ' + debug.timezone + '\n';
                    message += 'Date Format: ' + debug.date_format + '\n';
                    message += 'Time Format: ' + debug.time_format + '\n\n';
                    message += 'Parameters:\n';
                    message += 'Days: ' + debug.parameters.days + '\n';
                    message += 'Date From: ' + debug.parameters.date_from + '\n';
                    message += 'Date To: ' + debug.parameters.date_to + '\n\n';
                    message += 'Clicks Table Exists: ' + (debug.clicks_table_exists ? 'Yes' : 'No') + '\n';
                    message += 'Total Click Records: ' + debug.total_click_records + '\n';
                    
                    if (debug.sample_records && debug.sample_records.length > 0) {
                        message += '\nSample Records:\n';
                        debug.sample_records.forEach(function(record, index) {
                            message += (index + 1) + '. Post ID: ' + record.post_id + ', Time: ' + record.click_time + ', IP: ' + record.ip_address + '\n';
                        });
                    }
                    
                    if (debug.post_meta_stats) {
                        message += '\nPost Meta Statistics:\n';
                        message += 'Total Posts: ' + debug.post_meta_stats.total_posts + '\n';
                        message += 'Posts with Clicks: ' + debug.post_meta_stats.posts_with_clicks + '\n';
                        message += 'Total Clicks from Meta: ' + debug.post_meta_stats.total_clicks_from_meta + '\n';
                        message += 'Total Unique Visitors from Meta: ' + debug.post_meta_stats.total_unique_visitors_from_meta + '\n';
                    }
                    
                    alert(message);
                } else {
                    alert('Debug failed: ' + response.data);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Debug AJAX request failed:', {xhr: xhr, status: status, error: error});
                alert('Debug request failed. Check console for details.');
            });
    }

    /**
     * View data count functionality
     */
    function viewDataCount(days, dateFrom, dateTo) {
        console.log('View data count called with params:', {days: days, dateFrom: dateFrom, dateTo: dateTo});
        
        var data = {
            action: 'wp_link_tracker_view_data_count',
            nonce: wpLinkTrackerAdmin.nonce
        };
        
        if (days) {
            data.days = days;
        }
        
        if (dateFrom && dateTo) {
            data.date_from = dateFrom;
            data.date_to = dateTo;
        }
        
        console.log('View data count AJAX data being sent:', data);
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, data)
            .done(function(response) {
                console.log('View data count response:', response);
                
                if (response.success) {
                    var count = response.data;
                    var message = 'Data Count Information:\n\n';
                    message += 'Tracked Links: ' + count.tracked_links + '\n';
                    message += 'Clicks Table Exists: ' + (count.clicks_table_exists ? 'Yes' : 'No') + '\n';
                    message += 'Total Click Records: ' + count.total_click_records + '\n';
                    message += 'Filtered Click Records: ' + count.filtered_click_records + '\n\n';
                    message += 'Current Date Range:\n';
                    message += 'Days: ' + count.date_range.days + '\n';
                    message += 'Date From: ' + count.date_range.date_from + '\n';
                    message += 'Date To: ' + count.date_range.date_to + '\n';
                    
                    if (count.post_meta_stats) {
                        message += '\nPost Meta Statistics:\n';
                        message += 'Total Posts: ' + count.post_meta_stats.total_posts + '\n';
                        message += 'Posts with Clicks: ' + count.post_meta_stats.posts_with_clicks + '\n';
                        message += 'Total Clicks from Meta: ' + count.post_meta_stats.total_clicks_from_meta + '\n';
                        message += 'Total Unique Visitors from Meta: ' + count.post_meta_stats.total_unique_visitors_from_meta + '\n';
                    }
                    
                    alert(message);
                } else {
                    alert('View data count failed: ' + response.data);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('View data count AJAX request failed:', {xhr: xhr, status: status, error: error});
                alert('View data count request failed. Check console for details.');
            });
    }

    /**
     * Initialize dashboard charts
     */
    function initDashboardCharts() {
        console.log('Initializing dashboard charts');
        
        // Initialize empty charts
        var emptyData = {
            labels: [],
            datasets: [{
                label: 'Clicks',
                data: [],
                backgroundColor: 'rgba(0, 115, 170, 0.2)',
                borderColor: 'rgba(0, 115, 170, 1)',
                borderWidth: 1
            }]
        };

        var emptyPieData = {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: []
            }]
        };

        // Create empty charts
        try {
            var clicksChart = new Chart(
                document.getElementById('wplinktracker-clicks-chart'),
                {
                    type: 'line',
                    data: emptyData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                }
            );

            var devicesChart = new Chart(
                document.getElementById('wplinktracker-devices-chart'),
                {
                    type: 'pie',
                    data: emptyPieData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                }
            );

            var browsersChart = new Chart(
                document.getElementById('wplinktracker-browsers-chart'),
                {
                    type: 'pie',
                    data: emptyPieData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                }
            );

            var osChart = new Chart(
                document.getElementById('wplinktracker-os-chart'),
                {
                    type: 'pie',
                    data: emptyPieData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                }
            );
            
            console.log('Charts initialized successfully');
        } catch (error) {
            console.error('Error initializing charts:', error);
        }

        // Empty tables
        var emptyTableHtml = `
            <table class="widefat striped">
                <tbody>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 20px; color: #666;">
                            ${wpLinkTrackerAdmin.noDataMessage || 'No data available yet. Create some tracked links to see statistics here.'}
                        </td>
                    </tr>
                </tbody>
            </table>
        `;

        // Update tables with empty state
        $('#wplinktracker-top-links-table').html(emptyTableHtml);
        $('#wplinktracker-top-referrers-table').html(emptyTableHtml);
        
        console.log('Dashboard charts initialization completed');
    }

    /**
     * Function to draw clicks chart for individual link stats
     */
    window.drawClicksChart = function(data) {
        if (!data || !data.length) {
            return;
        }
        
        var ctx = document.getElementById('wplinktracker-clicks-chart');
        if (!ctx) {
            return;
        }
        
        var labels = [];
        var values = [];
        
        data.forEach(function(item) {
            labels.push(item.date);
            values.push(item.clicks);
        });
        
        var clicksChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Clicks',
                    data: values,
                    backgroundColor: 'rgba(0, 115, 170, 0.2)',
                    borderColor: 'rgba(0, 115, 170, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    };

})(jQuery);
