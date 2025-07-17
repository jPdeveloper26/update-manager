<?php
/**
 * Plugin Update Manager Updater Class
 *
 * @package Plugin_Update_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles filtering of plugin updates
 */
class Plugin_Update_Manager_Updater {
    
    /**
     * Core instance
     *
     * @var Plugin_Update_Manager_Core
     */
    private $core;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->core = new Plugin_Update_Manager_Core();
    }
    
    /**
     * Initialize updater functionality
     */
    public function init() {
        // Filter update checks
        add_filter('site_transient_update_plugins', array($this, 'filter_plugin_updates'));
        
        // Prevent auto-updates for disabled plugins
        add_filter('auto_update_plugin', array($this, 'prevent_auto_update'), 10, 2);
        
        // Add update notice on plugin page
        add_action('after_plugin_row', array($this, 'show_disabled_notice'), 10, 2);
    }
    
    /**
     * Filter plugin updates to remove disabled plugins
     *
     * @param object $transient Update transient object
     * @return object Modified transient
     */
    public function filter_plugin_updates($transient) {
        // If no update data, return as-is
        if (empty($transient->response)) {
            return $transient;
        }
        
        $disabled_plugins = $this->core->get_disabled_plugins();
        
        // Remove disabled plugins from update list
        foreach ($disabled_plugins as $disabled) {
            if (isset($transient->response[$disabled['plugin_file']])) {
                // Move to no_update list to indicate it's checked but disabled
                $transient->no_update[$disabled['plugin_file']] = $transient->response[$disabled['plugin_file']];
                unset($transient->response[$disabled['plugin_file']]);
            }
        }
        
        return $transient;
    }
    
    /**
     * Prevent auto-updates for disabled plugins
     *
     * @param bool $update Whether to update
     * @param object $item Plugin update data
     * @return bool
     */
    public function prevent_auto_update($update, $item) {
        // Check if this plugin is disabled
        if (isset($item->plugin) && $this->core->is_plugin_disabled($item->plugin)) {
            return false;
        }
        
        return $update;
    }
    
    /**
     * Show notice after plugin row for disabled plugins
     *
     * @param string $plugin_file Plugin file
     * @param array $plugin_data Plugin data
     */
    public function show_disabled_notice($plugin_file, $plugin_data) {
        $disabled_plugin = $this->core->get_disabled_plugin($plugin_file);
        
        if (!$disabled_plugin) {
            return;
        }
        
        // Check if there's an available update
        $update_plugins = get_site_transient('update_plugins');
        $has_update = false;
        $new_version = '';
        
        if (isset($update_plugins->response[$plugin_file]) || isset($update_plugins->no_update[$plugin_file])) {
            $update_data = isset($update_plugins->response[$plugin_file]) 
                ? $update_plugins->response[$plugin_file] 
                : $update_plugins->no_update[$plugin_file];
                
            if (version_compare($update_data->new_version, $disabled_plugin['disabled_version'], '>')) {
                $has_update = true;
                $new_version = $update_data->new_version;
            }
        }
        
        if (!$has_update) {
            return;
        }
        
        $wp_list_table = _get_list_table('WP_Plugins_List_Table');
        $columns_count = $wp_list_table->get_column_count();
        ?>
        <tr class="plugin-update-tr update pum-disabled-update-notice" data-plugin="<?php echo esc_attr($plugin_file); ?>">
            <td colspan="<?php echo esc_attr($columns_count); ?>" class="plugin-update">
                <div class="update-message notice inline notice-warning notice-alt">
                    <p>
                        <strong>🔒 <?php esc_html_e('Updates Disabled', 'update-manager'); ?></strong><br>
                        <?php
                        printf(
                            /* translators: 1: new version number, 2: disabled version number */
                            esc_html__('Version %1$s is available, but updates are disabled for this plugin at version %2$s.', 'update-manager'),
                            '<span style="color: #d63638; font-weight: 600;">' . esc_html($new_version) . '</span>',
                            '<span style="color: #2271b1; font-weight: 600;">' . esc_html($disabled_plugin['disabled_version']) . '</span>'
                        );
                        ?>
                    </p>
                    <p style="margin-top: 10px;">
                        <strong><?php esc_html_e('Reason:', 'update-manager'); ?></strong> 
                        <em><?php echo esc_html($disabled_plugin['disable_note']); ?></em>
                    </p>
                    <p style="margin-top: 10px;">
                        <a href="<?php echo esc_url(admin_url('plugins.php?page=plugin-update-manager')); ?>" class="button button-small">
                            <?php esc_html_e('Manage Disabled Plugins', 'update-manager'); ?>
                        </a>
                        <?php
                        $user = get_userdata($disabled_plugin['disabled_by']);
                        if ($user) {
                            printf(
                                '<span style="margin-left: 10px; color: #666;">%s %s %s</span>',
                                esc_html__('Disabled by', 'update-manager'),
                                esc_html($user->display_name),
                                esc_html(human_time_diff(strtotime($disabled_plugin['disabled_date']), current_time('timestamp')) . ' ' . __('ago', 'update-manager'))
                            );
                        }
                        ?>
                    </p>
                </div>
            </td>
        </tr>
        <?php
    }
}