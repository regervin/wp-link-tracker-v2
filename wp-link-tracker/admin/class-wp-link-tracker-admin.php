<?php
/**
 * Admin functionality for the plugin
 */
class WP_Link_Tracker_Admin {
    /**
     * Initialize the class.
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('manage_wplinktracker_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_wplinktracker_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-wplinktracker_sortable_columns', array($this, 'set_sortable_columns'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('wp_ajax_wp_link_tracker_get_dashboard_stats', array($this, 'get_dashboard_stats_ajax'));
        add_action('wp_ajax_wp_link_tracker_debug_date_range', array($this, 'debug_date_range_ajax'));
        add_action('wp_ajax_wp_link_tracker_view_data_count', array($this, 'view_data_count_ajax'));
    }

    /**
     * Add meta boxes for the tracked link post type.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wplinktracker_link_details',
            __('Link Details', 'wp-link-tracker'),
            array($this, 'render_link_details_meta_box'),
            'wplinktracker',
            'normal',
            'high'
        );
        
        add_meta_box(
            'wplinktracker_link_stats',
            __('Link Statistics', 'wp-link-tracker'),
            array($this, 'render_link_stats_meta_box'),
            'wplinktracker',
            'side',
            'default'
        );
    }

    /**
     * Render the link details meta box.
     */
    public function render_link_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('wplinktracker_meta_box_nonce', 'wplinktracker_meta_box_nonce');
        
        // Get current values
        $destination_url = get_post_meta($post->ID, '_wplinktracker_destination_url', true);
        $short_code = get_post_meta($post->ID, '_wplinktracker_short_code', true);
        $campaign = wp_get_object_terms($post->ID, 'wplinktracker_campaign', array('fields' => 'names'));
        $campaign_name = !empty($campaign) ? $campaign[0] : '';
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wplinktracker_destination_url"><?php _e('Destination URL', 'wp-link-tracker'); ?></label>
                </th>
                <td>
                    <input type="url" id="wplinktracker_destination_url" name="wplinktracker_destination_url" 
                           value="<?php echo esc_attr($destination_url); ?>" class="regular-text" required />
                    <p class="description"><?php _e('The URL where users will be redirected when they click the short link.', 'wp-link-tracker'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wplinktracker_short_code"><?php _e('Short Code', 'wp-link-tracker'); ?></label>
                </th>
                <td>
                    <input type="text" id="wplinktracker_short_code" name="wplinktracker_short_code" 
                           value="<?php echo esc_attr($short_code); ?>" class="regular-text" />
                    <p class="description">
                        <?php _e('Custom short code for the link. Leave blank to auto-generate.', 'wp-link-tracker'); ?>
                        <?php if (!empty($short_code)): ?>
                            <br><?php printf(__('Short URL: %s', 'wp-link-tracker'), '<strong>' . home_url('go/' . $short_code) . '</strong>'); ?>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wplinktracker_campaign"><?php _e('Campaign', 'wp-link-tracker'); ?></label>
                </th>
                <td>
                    <input type="text" id="wplinktracker_campaign" name="wplinktracker_campaign" 
                           value="<?php echo esc_attr($campaign_name); ?>" class="regular-text" />
                    <p class="description"><?php _e('Optional campaign name to group related links.', 'wp-link-tracker'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render the link statistics meta box.
     */
    public function render_link_stats_meta_box($post) {
        $total_clicks = get_post_meta($post->ID, '_wplinktracker_total_clicks', true);
        $unique_visitors = get_post_meta($post->ID, '_wplinktracker_unique_visitors', true);
        $last_clicked = get_post_meta($post->ID, '_wplinktracker_last_clicked', true);
        
        ?>
        <div class="wplinktracker-stats">
            <div class="wplinktracker-stat-item">
                <strong><?php _e('Total Clicks:', 'wp-link-tracker'); ?></strong>
                <span><?php echo !empty($total_clicks) ? esc_html($total_clicks) : '0'; ?></span>
            </div>
            
            <div class="wplinktracker-stat-item">
                <strong><?php _e('Unique Visitors:', 'wp-link-tracker'); ?></strong>
                <span><?php echo !empty($unique_visitors) ? esc_html($unique_visitors) : '0'; ?></span>
            </div>
            
            <div class="wplinktracker-stat-item">
                <strong><?php _e('Conversion Rate:', 'wp-link-tracker'); ?></strong>
                <span>
                    <?php 
                    $total_clicks_int = (int) $total_clicks;
                    $unique_visitors_int = (int) $unique_visitors;
                    $conversion_rate = ($unique_visitors_int > 0) ? round(($total_clicks_int / $unique_visitors_int) * 100, 2) . '%' : '0%';
                    echo esc_html($conversion_rate);
                    ?>
                </span>
            </div>
            
            <?php if (!empty($last_clicked)): ?>
            <div class="wplinktracker-stat-item">
                <strong><?php _e('Last Clicked:', 'wp-link-tracker'); ?></strong>
                <span><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_clicked))); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
            .wplinktracker-stats {
                margin: 10px 0;
            }
            .wplinktracker-stat-item {
                margin-bottom: 10px;
                padding: 8px;
                background: #f9f9f9;
                border-left: 3px solid #0073aa;
            }
            .wplinktracker-stat-item strong {
                display: inline-block;
                width: 120px;
            }
        </style>
        <?php
    }

    /**
     * Save meta box data.
     */
    public function save_meta_boxes($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check if this is the correct post type
        if (get_post_type($post_id) !== 'wplinktracker') {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['wplinktracker_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['wplinktracker_meta_box_nonce'], 'wplinktracker_meta_box_nonce')) {
            return;
        }
        
        // Save destination URL
        if (isset($_POST['wplinktracker_destination_url'])) {
            $destination_url = esc_url_raw($_POST['wplinktracker_destination_url']);
            update_post_meta($post_id, '_wplinktracker_destination_url', $destination_url);
        }
        
        // Save or generate short code
        if (isset($_POST['wplinktracker_short_code'])) {
            $short_code = sanitize_text_field($_POST['wplinktracker_short_code']);
            
            // If no custom short code provided, generate one
            if (empty($short_code)) {
                $short_code = $this->generate_short_code();
            } else {
                // Check if custom short code is already in use
                $existing_post = $this->get_post_by_short_code($short_code);
                if ($existing_post && $existing_post->ID !== $post_id) {
                    // Short code already exists, generate a new one
                    $short_code = $this->generate_short_code();
                }
            }
            
            update_post_meta($post_id, '_wplinktracker_short_code', $short_code);
        }
        
        // Save campaign
        if (isset($_POST['wplinktracker_campaign'])) {
            $campaign = sanitize_text_field($_POST['wplinktracker_campaign']);
            if (!empty($campaign)) {
                wp_set_object_terms($post_id, $campaign, 'wplinktracker_campaign');
            } else {
                wp_delete_object_term_relationships($post_id, 'wplinktracker_campaign');
            }
        }
    }

    /**
     * Get post by short code.
     */
    private function get_post_by_short_code($short_code) {
        $args = array(
            'post_type' => 'wplinktracker',
            'meta_query' => array(
                array(
                    'key' => '_wplinktracker_short_code',
                    'value' => $short_code,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            return $query->posts[0];
        }
        
        return null;
    }

    /**
     * Add admin menu items.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=wplinktracker',
            __('Dashboard', 'wp-link-tracker'),
            __('Dashboard', 'wp-link-tracker'),
            'manage_options',
            'wp-link-tracker-dashboard',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=wplinktracker',
            __('Settings', 'wp-link-tracker'),
            __('Settings', 'wp-link-tracker'),
            'manage_options',
            'wp-link-tracker-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_scripts($hook) {
        global $post_type;
        
        // Debug: Let's see what hook and post_type we have
        error_log('WP Link Tracker Debug - Hook: ' . $hook . ', Post Type: ' . $post_type);
        
        // Check if we're on any wplinktracker related page
        $is_plugin_page = (
            // Dashboard and settings pages
            strpos($hook, 'wp-link-tracker') !== false ||
            // Post edit pages
            ($hook === 'post.php' && $post_type === 'wplinktracker') ||
            ($hook === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'wplinktracker') ||
            // Post list page
            ($hook === 'edit.php' && $post_type === 'wplinktracker')
        );
        
        if (!$is_plugin_page) {
            return;
        }
        
        // Enqueue Chart.js
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js', array(), '3.7.1', true);
        
        // Enqueue our admin script
        wp_enqueue_script(
            'wp-link-tracker-admin',
            WP_LINK_TRACKER_PLUGIN_URL . 'admin/js/wp-link-tracker-admin.js',
            array('jquery', 'chartjs'),
            WP_LINK_TRACKER_VERSION,
            true
        );
        
        // Localize script with translations
        wp_localize_script('wp-link-tracker-admin', 'wpLinkTrackerAdmin', array(
            'noDataMessage' => __('No data available yet. Create some tracked links to see statistics here.', 'wp-link-tracker'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_link_tracker_admin_nonce')
        ));
        
        // Enqueue our admin styles
        wp_enqueue_style(
            'wp-link-tracker-admin',
            WP_LINK_TRACKER_PLUGIN_URL . 'admin/css/wp-link-tracker-admin.css',
            array(),
            WP_LINK_TRACKER_VERSION
        );
        
        // Debug: Confirm scripts are being enqueued
        error_log('WP Link Tracker Debug - Scripts enqueued for hook: ' . $hook);
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting('wp_link_tracker_settings', 'wp_link_tracker_settings');
        
        add_settings_section(
            'wp_link_tracker_general_settings',
            __('General Settings', 'wp-link-tracker'),
            array($this, 'render_general_settings_section'),
            'wp_link_tracker_settings'
        );
        
        add_settings_field(
            'link_prefix',
            __('Link Prefix', 'wp-link-tracker'),
            array($this, 'render_link_prefix_field'),
            'wp_link_tracker_settings',
            'wp_link_tracker_general_settings'
        );
    }

    /**
     * Render the general settings section.
     */
    public function render_general_settings_section() {
        echo '<p>' . __('Configure general settings for the WP Link Tracker plugin.', 'wp-link-tracker') . '</p>';
    }

    /**
     * Render the link prefix field.
     */
    public function render_link_prefix_field() {
        $options = get_option('wp_link_tracker_settings');
        $link_prefix = isset($options['link_prefix']) ? $options['link_prefix'] : 'go';
        
        echo '<input type="text" id="link_prefix" name="wp_link_tracker_settings[link_prefix]" value="' . esc_attr($link_prefix) . '" />';
        echo '<p class="description">' . __('The prefix for shortened links. Default is "go".', 'wp-link-tracker') . '</p>';
        echo '<p class="description">' . __('Example: yourdomain.com/go/abc123', 'wp-link-tracker') . '</p>';
    }

    /**
     * Set custom columns for the tracked links list.
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        
        // Add checkbox and title first
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
        }
        
        if (isset($columns['title'])) {
            $new_columns['title'] = $columns['title'];
        }
        
        // Add our custom columns
        $new_columns['destination'] = __('Destination URL', 'wp-link-tracker');
        $new_columns['short_url'] = __('Short URL', 'wp-link-tracker');
        $new_columns['clicks'] = __('Clicks', 'wp-link-tracker');
        $new_columns['unique_visitors'] = __('Unique Visitors', 'wp-link-tracker');
        $new_columns['conversion_rate'] = __('Conversion Rate', 'wp-link-tracker');
        $new_columns['date'] = __('Date', 'wp-link-tracker');
        
        return $new_columns;
    }

    /**
     * Display content for custom columns.
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'destination':
                $destination_url = get_post_meta($post_id, '_wplinktracker_destination_url', true);
                if (!empty($destination_url)) {
                    echo '<a href="' . esc_url($destination_url) . '" target="_blank">' . esc_url($destination_url) . '</a>';
                } else {
                    echo '—';
                }
                break;
                
            case 'short_url':
                $short_code = get_post_meta($post_id, '_wplinktracker_short_code', true);
                if (!empty($short_code)) {
                    $short_url = home_url('go/' . $short_code);
                    echo '<a href="' . esc_url($short_url) . '" target="_blank">' . esc_url($short_url) . '</a>';
                    echo '<br><button type="button" class="button button-small copy-to-clipboard" data-clipboard-text="' . esc_url($short_url) . '">' . __('Copy', 'wp-link-tracker') . '</button>';
                } else {
                    echo '—';
                }
                break;
                
            case 'clicks':
                $total_clicks = get_post_meta($post_id, '_wplinktracker_total_clicks', true);
                echo !empty($total_clicks) ? esc_html($total_clicks) : '0';
                break;
                
            case 'unique_visitors':
                $unique_visitors = get_post_meta($post_id, '_wplinktracker_unique_visitors', true);
                echo !empty($unique_visitors) ? esc_html($unique_visitors) : '0';
                break;
                
            case 'conversion_rate':
                $total_clicks = (int) get_post_meta($post_id, '_wplinktracker_total_clicks', true);
                $unique_visitors = (int) get_post_meta($post_id, '_wplinktracker_unique_visitors', true);
                $conversion_rate = ($unique_visitors > 0) ? round(($total_clicks / $unique_visitors) * 100, 2) . '%' : '0%';
                echo esc_html($conversion_rate);
                break;
        }
    }

    /**
     * Set sortable columns.
     */
    public function set_sortable_columns($columns) {
        $columns['clicks'] = 'clicks';
        $columns['unique_visitors'] = 'unique_visitors';
        $columns['conversion_rate'] = 'conversion_rate';
        return $columns;
    }

    /**
     * Get dashboard statistics via AJAX.
     */
    public function get_dashboard_stats_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Get date range parameters
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // Get statistics
        $stats = $this->get_dashboard_statistics($days, $date_from, $date_to);
        
        wp_send_json_success($stats);
    }

    /**
     * Debug date range via AJAX.
     */
    public function debug_date_range_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        $debug_info = array(
            'current_time' => current_time('mysql'),
            'timezone' => get_option('timezone_string'),
            'date_format' => get_option('date_format'),
            'time_format' => get_option('time_format'),
            'parameters' => array(
                'days' => $days,
                'date_from' => $date_from,
                'date_to' => $date_to
            )
        );
        
        // Check if clicks table exists
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        $debug_info['clicks_table_exists'] = $table_exists;
        
        if ($table_exists) {
            $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $debug_info['total_click_records'] = $total_records;
            
            if ($total_records > 0) {
                $sample_records = $wpdb->get_results("SELECT * FROM $table_name ORDER BY click_time DESC LIMIT 5");
                $debug_info['sample_records'] = $sample_records;
            }
        }
        
        // Check post meta statistics
        $debug_info['post_meta_stats'] = $this->get_post_meta_statistics();
        
        wp_send_json_success($debug_info);
    }

    /**
     * View data count via AJAX.
     */
    public function view_data_count_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // Get tracked links count
        $links_count = wp_count_posts('wplinktracker');
        $published_links = isset($links_count->publish) ? $links_count->publish : 0;
        
        // Get clicks data
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        $data_count = array(
            'tracked_links' => $published_links,
            'clicks_table_exists' => $table_exists,
            'total_click_records' => 0,
            'filtered_click_records' => 0,
            'date_range' => array(
                'days' => $days,
                'date_from' => $date_from,
                'date_to' => $date_to
            )
        );
        
        if ($table_exists) {
            // Total click records
            $total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $data_count['total_click_records'] = $total_clicks;
            
            // Filtered click records based on date range
            if (!empty($date_from) && !empty($date_to)) {
                $filtered_clicks = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE DATE(click_time) BETWEEN %s AND %s",
                    $date_from, $date_to
                ));
            } else {
                $filtered_clicks = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE click_time >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                    $days
                ));
            }
            $data_count['filtered_click_records'] = $filtered_clicks;
        }
        
        // Add post meta statistics
        $data_count['post_meta_stats'] = $this->get_post_meta_statistics();
        
        wp_send_json_success($data_count);
    }

    /**
     * Get post meta statistics for debugging.
     */
    private function get_post_meta_statistics() {
        global $wpdb;
        
        // Get all tracked links with their meta data
        $posts = get_posts(array(
            'post_type' => 'wplinktracker',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        $stats = array(
            'total_posts' => count($posts),
            'posts_with_clicks' => 0,
            'posts_with_unique_visitors' => 0,
            'total_clicks_from_meta' => 0,
            'total_unique_visitors_from_meta' => 0,
            'sample_posts' => array()
        );
        
        foreach ($posts as $post) {
            $total_clicks = get_post_meta($post->ID, '_wplinktracker_total_clicks', true);
            $unique_visitors = get_post_meta($post->ID, '_wplinktracker_unique_visitors', true);
            
            if (!empty($total_clicks) && $total_clicks > 0) {
                $stats['posts_with_clicks']++;
                $stats['total_clicks_from_meta'] += (int) $total_clicks;
            }
            
            if (!empty($unique_visitors) && $unique_visitors > 0) {
                $stats['posts_with_unique_visitors']++;
                $stats['total_unique_visitors_from_meta'] += (int) $unique_visitors;
            }
            
            // Add first 5 posts as samples
            if (count($stats['sample_posts']) < 5) {
                $stats['sample_posts'][] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'total_clicks' => $total_clicks ?: '0',
                    'unique_visitors' => $unique_visitors ?: '0'
                );
            }
        }
        
        return $stats;
    }

    /**
     * Get dashboard statistics - CORRECTED VERSION.
     */
    private function get_dashboard_statistics($days = 30, $date_from = '', $date_to = '') {
        global $wpdb;
        
        // Method 1: Try to get from clicks table first
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        $total_clicks = 0;
        $unique_visitors = 0;
        
        if ($table_exists) {
            // Build date condition
            $date_condition = '';
            $date_params = array();
            
            if (!empty($date_from) && !empty($date_to)) {
                $date_condition = "WHERE DATE(click_time) BETWEEN %s AND %s";
                $date_params = array($date_from, $date_to);
            } else {
                $date_condition = "WHERE click_time >= DATE_SUB(NOW(), INTERVAL %d DAY)";
                $date_params = array($days);
            }
            
            // Get total clicks for the date range
            $total_clicks = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name $date_condition",
                $date_params
            ));
            
            // Get unique visitors for the date range
            $unique_visitors = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT ip_address) FROM $table_name $date_condition",
                $date_params
            ));
        }
        
        // Method 2: If clicks table doesn't exist or has no data, aggregate from post meta
        if (!$table_exists || $total_clicks == 0) {
            $posts = get_posts(array(
                'post_type' => 'wplinktracker',
                'post_status' => 'publish',
                'numberposts' => -1
            ));
            
            $total_clicks = 0;
            $unique_visitors = 0;
            
            foreach ($posts as $post) {
                $post_clicks = get_post_meta($post->ID, '_wplinktracker_total_clicks', true);
                $post_unique = get_post_meta($post->ID, '_wplinktracker_unique_visitors', true);
                
                $total_clicks += !empty($post_clicks) ? (int) $post_clicks : 0;
                $unique_visitors += !empty($post_unique) ? (int) $post_unique : 0;
            }
        }
        
        // Get active links count
        $links_count = wp_count_posts('wplinktracker');
        $active_links = isset($links_count->publish) ? (int) $links_count->publish : 0;
        
        // Calculate average conversion rate
        $avg_conversion = ($unique_visitors > 0) ? round(($total_clicks / $unique_visitors) * 100, 2) : 0;
        
        return array(
            'total_clicks' => (int) $total_clicks,
            'unique_visitors' => (int) $unique_visitors,
            'active_links' => (int) $active_links,
            'avg_conversion' => $avg_conversion . '%'
        );
    }

    /**
     * Render the dashboard page.
     */
    public function render_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wplinktracker-dashboard">
                <div class="wplinktracker-dashboard-header">
                    <div class="wplinktracker-date-range">
                        <label for="wplinktracker-date-range-select"><?php _e('Date Range:', 'wp-link-tracker'); ?></label>
                        <select id="wplinktracker-date-range-select">
                            <option value="7"><?php _e('Last 7 Days', 'wp-link-tracker'); ?></option>
                            <option value="30" selected><?php _e('Last 30 Days', 'wp-link-tracker'); ?></option>
                            <option value="90"><?php _e('Last 90 Days', 'wp-link-tracker'); ?></option>
                            <option value="365"><?php _e('Last Year', 'wp-link-tracker'); ?></option>
                            <option value="custom"><?php _e('Custom Range', 'wp-link-tracker'); ?></option>
                        </select>
                        
                        <div id="wplinktracker-custom-date-range" style="display: none;">
                            <input type="date" id="wplinktracker-date-from" />
                            <span>to</span>
                            <input type="date" id="wplinktracker-date-to" />
                            <button type="button" class="button" id="wplinktracker-apply-date-range"><?php _e('Apply', 'wp-link-tracker'); ?></button>
                        </div>
                        
                        <div class="wplinktracker-date-actions">
                            <button type="button" class="button" id="wplinktracker-refresh-data">
                                <span class="dashicons dashicons-update"></span> <?php _e('Refresh Data', 'wp-link-tracker'); ?>
                            </button>
                            <button type="button" class="button" id="wplinktracker-view-data-count">
                                <span class="dashicons dashicons-visibility"></span> <?php _e('View Data Count', 'wp-link-tracker'); ?>
                            </button>
                            <button type="button" class="button" id="wplinktracker-debug-date-range">
                                <span class="dashicons dashicons-admin-tools"></span> <?php _e('Debug Date Range', 'wp-link-tracker'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-refresh">
                        <button type="button" class="button" id="wplinktracker-refresh-dashboard">
                            <span class="dashicons dashicons-update"></span> <?php _e('Refresh', 'wp-link-tracker'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="wplinktracker-dashboard-summary">
                    <div class="wplinktracker-summary-box">
                        <h3><?php _e('Total Clicks', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-total-clicks">
                            <span class="spinner is-active" style="float: none; margin: 0;"></span>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-summary-box">
                        <h3><?php _e('Unique Visitors', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-unique-visitors">
                            <span class="spinner is-active" style="float: none; margin: 0;"></span>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-summary-box">
                        <h3><?php _e('Active Links', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-active-links">
                            <span class="spinner is-active" style="float: none; margin: 0;"></span>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-summary-box">
                        <h3><?php _e('Avg. Conversion Rate', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-avg-conversion">
                            <span class="spinner is-active" style="float: none; margin: 0;"></span>
                        </div>
                    </div>
                </div>
                
                <div class="wplinktracker-dashboard-charts">
                    <div class="wplinktracker-chart-container">
                        <h3><?php _e('Clicks Over Time', 'wp-link-tracker'); ?></h3>
                        <div style="height: 300px; overflow: hidden;">
                            <canvas id="wplinktracker-clicks-chart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="wplinktracker-dashboard-tables">
                    <div class="wplinktracker-table-container">
                        <h3><?php _e('Top Performing Links', 'wp-link-tracker'); ?></h3>
                        <div id="wplinktracker-top-links-table" class="wplinktracker-table-content">
                            <p><?php _e('No data available yet. Create some tracked links to see statistics here.', 'wp-link-tracker'); ?></p>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-table-container">
                        <h3><?php _e('Top Referrers', 'wp-link-tracker'); ?></h3>
                        <div id="wplinktracker-top-referrers-table" class="wplinktracker-table-content">
                            <p><?php _e('No data available yet. Create some tracked links to see statistics here.', 'wp-link-tracker'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="wplinktracker-dashboard-devices">
                    <div class="wplinktracker-chart-container">
                        <h3><?php _e('Device Types', 'wp-link-tracker'); ?></h3>
                        <div style="height: 300px; overflow: hidden;">
                            <canvas id="wplinktracker-devices-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-chart-container">
                        <h3><?php _e('Browsers', 'wp-link-tracker'); ?></h3>
                        <div style="height: 300px; overflow: hidden;">
                            <canvas id="wplinktracker-browsers-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-chart-container">
                        <h3><?php _e('Operating Systems', 'wp-link-tracker'); ?></h3>
                        <div style="height: 300px; overflow: hidden;">
                            <canvas id="wplinktracker-os-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .wplinktracker-dashboard {
                margin-top: 20px;
            }
            .wplinktracker-dashboard-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 20px;
            }
            .wplinktracker-date-range {
                flex: 1;
            }
            .wplinktracker-date-actions {
                margin-top: 10px;
            }
            .wplinktracker-date-actions .button {
                margin-right: 10px;
            }
            .wplinktracker-dashboard-summary {
                display: flex;
                justify-content: space-between;
                margin-bottom: 30px;
            }
            .wplinktracker-summary-box {
                flex: 1;
                margin-right: 15px;
                padding: 20px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-align: center;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .wplinktracker-summary-box:last-child {
                margin-right: 0;
            }
            .wplinktracker-summary-box h3 {
                margin-top: 0;
                color: #23282d;
            }
            .wplinktracker-summary-value {
                font-size: 28px;
                font-weight: bold;
                color: #0073aa;
            }
            .wplinktracker-dashboard-charts,
            .wplinktracker-dashboard-tables,
            .wplinktracker-dashboard-devices {
                display: flex;
                margin-bottom: 30px;
            }
            .wplinktracker-chart-container,
            .wplinktracker-table-container {
                flex: 1;
                margin-right: 15px;
                padding: 20px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .wplinktracker-chart-container:last-child,
            .wplinktracker-table-container:last-child {
                margin-right: 0;
            }
            .wplinktracker-chart-container h3,
            .wplinktracker-table-container h3 {
                margin-top: 0;
                color: #23282d;
            }
            #wplinktracker-custom-date-range {
                margin-top: 10px;
            }
            /* Table content scrolling */
            .wplinktracker-table-content {
                max-height: 300px;
                overflow-y: auto;
            }
            @media (max-width: 782px) {
                .wplinktracker-dashboard-summary,
                .wplinktracker-dashboard-charts,
                .wplinktracker-dashboard-tables,
                .wplinktracker-dashboard-devices {
                    flex-direction: column;
                }
                .wplinktracker-summary-box,
                .wplinktracker-chart-container,
                .wplinktracker-table-container {
                    margin-right: 0;
                    margin-bottom: 15px;
                }
            }
        </style>
        <?php
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_link_tracker_settings');
                do_settings_sections('wp_link_tracker_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Create a new link via AJAX.
     */
    public function create_link_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_create_link_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check if user has permission
        if (!current_user_can('publish_posts')) {
            wp_send_json_error('Permission denied');
        }
        
        // Check required fields
        if (!isset($_POST['title']) || !isset($_POST['destination_url'])) {
            wp_send_json_error('Missing required fields');
        }
        
        $title = sanitize_text_field($_POST['title']);
        $destination_url = esc_url_raw($_POST['destination_url']);
        $campaign = isset($_POST['campaign']) ? sanitize_text_field($_POST['campaign']) : '';
        
        // Create the post
        $post_id = wp_insert_post(array(
            'post_title' => $title,
            'post_type' => 'wplinktracker',
            'post_status' => 'publish'
        ));
        
        if (is_wp_error($post_id)) {
            wp_send_json_error($post_id->get_error_message());
        }
        
        // Set the destination URL
        update_post_meta($post_id, '_wplinktracker_destination_url', $destination_url);
        
        // Generate short code
        $short_code = $this->generate_short_code();
        update_post_meta($post_id, '_wplinktracker_short_code', $short_code);
        
        // Set campaign if provided
        if (!empty($campaign)) {
            wp_set_object_terms($post_id, $campaign, 'wplinktracker_campaign');
        }
        
        // Get the short URL
        $short_url = home_url('go/' . $short_code);
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'short_url' => $short_url,
            'shortcode' => '[tracked_link id="' . $post_id . '"]'
        ));
    }

    /**
     * Generate a unique short code.
     */
    private function generate_short_code($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $short_code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $short_code .= $characters[rand(0, $charactersLength - 1)];
        }
        
        // Check if the code already exists
        $args = array(
            'post_type' => 'wplinktracker',
            'meta_query' => array(
                array(
                    'key' => '_wplinktracker_short_code',
                    'value' => $short_code,
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        
        // If the code exists, generate a new one
        if ($query->have_posts()) {
            return $this->generate_short_code($length);
        }
        
        return $short_code;
    }
}
