<?php
/**
 * Plugin Update Manager Core Class
 *
 * @package Plugin_Update_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin core class
 */
class Plugin_Update_Manager_Core {
    
    /**
     * Database table name
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Cache group
     *
     * @var string
     */
    private $cache_group = 'pum_disabled_plugins';
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pum_disabled_plugins';
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize admin if in admin area
        if (is_admin()) {
            $admin = new Plugin_Update_Manager_Admin();
            $admin->init();
            
            $updater = new Plugin_Update_Manager_Updater();
            $updater->init();
        }
    }
    
    /**
     * Create database table for storing disabled plugin data
     */
    public function create_database_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            plugin_file varchar(255) NOT NULL,
            disabled_version varchar(50) NOT NULL,
            disable_note text NOT NULL,
            disabled_date datetime DEFAULT CURRENT_TIMESTAMP,
            disabled_by bigint(20) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY plugin_file (plugin_file)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Store db version
        add_option('pum_db_version', PUM_VERSION);
    }
    
    /**
     * Get all disabled plugins
     *
     * @return array
     */
    public function get_disabled_plugins() {
        global $wpdb;
        
        // Check cache first
        $cache_key = 'all_disabled_plugins';
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if (false !== $cached) {
            return $cached;
        }
        
        $table_name = $this->table_name;
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- Table name is validated
        $results = $wpdb->get_results(
		 // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe
            "SELECT * FROM $table_name ORDER BY disabled_date DESC",
            ARRAY_A
        );
        
        $results = $results ? $results : array();
        
        // Cache the results
        wp_cache_set($cache_key, $results, $this->cache_group, 3600);
        
        return $results;
    }
    
    /**
     * Get a specific disabled plugin
     *
     * @param string $plugin_file Plugin file path
     * @return array|null
     */
    public function get_disabled_plugin($plugin_file) {
        global $wpdb;
        
        // Check cache first
        $cache_key = 'disabled_plugin_' . md5($plugin_file);
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if (false !== $cached) {
            return $cached;
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table query
        $result = $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe
                "SELECT * FROM {$this->table_name} WHERE plugin_file = %s",
                $plugin_file
            ),
            ARRAY_A
        );
        
        // Cache the result
        wp_cache_set($cache_key, $result, $this->cache_group, 3600);
        
        return $result;
    }
    
    /**
     * Disable a plugin at specific version
     *
     * @param string $plugin_file Plugin file path
     * @param string $version Version to disable at
     * @param string $note Reason for disabling
     * @return bool
     */
    public function disable_plugin($plugin_file, $version, $note) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        
        // Check if plugin is already disabled
        $existing = $this->get_disabled_plugin($plugin_file);
        
        if ($existing) {
            // Update existing record
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'disabled_version' => sanitize_text_field($version),
                    'disable_note' => sanitize_textarea_field($note),
                    'disabled_date' => current_time('mysql'),
                    'disabled_by' => $user_id
                ),
                array('plugin_file' => $plugin_file),
                array('%s', '%s', '%s', '%d'),
                array('%s')
            );
        } else {
            // Insert new record
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'plugin_file' => sanitize_text_field($plugin_file),
                    'disabled_version' => sanitize_text_field($version),
                    'disable_note' => sanitize_textarea_field($note),
                    'disabled_by' => $user_id
                ),
                array('%s', '%s', '%s', '%d')
            );
        }
        
        // Clear cache
        $this->clear_cache($plugin_file);
        
        // Clear update cache
        delete_site_transient('update_plugins');
        
        return $result !== false;
    }
    
    /**
     * Enable a plugin
     *
     * @param string $plugin_file Plugin file path
     * @return bool
     */
    public function enable_plugin($plugin_file) {
        global $wpdb;
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query
        $result = $wpdb->delete(
            $this->table_name,
            array('plugin_file' => $plugin_file),
            array('%s')
        );
        
        // Clear cache
        $this->clear_cache($plugin_file);
        
        // Clear update cache
        delete_site_transient('update_plugins');
        
        return $result !== false;
    }
    
    /**
     * Check if a plugin is disabled
     *
     * @param string $plugin_file Plugin file path
     * @return bool
     */
    public function is_plugin_disabled($plugin_file) {
        $disabled_plugin = $this->get_disabled_plugin($plugin_file);
        return !empty($disabled_plugin);
    }
    
    /**
     * Clear cache for a specific plugin or all plugins
     *
     * @param string|null $plugin_file Plugin file path or null for all
     */
    private function clear_cache($plugin_file = null) {
        if ($plugin_file) {
            wp_cache_delete('disabled_plugin_' . md5($plugin_file), $this->cache_group);
        }
        wp_cache_delete('all_disabled_plugins', $this->cache_group);
    }
}