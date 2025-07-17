<?php
/**
 * Plugin Update Manager Admin Class
 *
 * @package Plugin_Update_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin functionality class
 */
class Plugin_Update_Manager_Admin {
    
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
     * Initialize admin functionality
     */
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add action links to plugins page
        add_filter('plugin_action_links', array($this, 'add_plugin_action_links'), 10, 2);
        
        // Handle AJAX requests
        add_action('wp_ajax_pum_disable_plugin', array($this, 'ajax_disable_plugin'));
        add_action('wp_ajax_pum_enable_plugin', array($this, 'ajax_enable_plugin'));
        
        // Add disabled indicator in plugins list
        add_filter('plugin_row_meta', array($this, 'add_disabled_indicator'), 10, 2);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'plugins.php',
            __('Plugin Update Manager', 'update-manager'),
            __('Update Manager', 'update-manager'),
            'manage_options',
            'plugin-update-manager',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin page and plugins page
        if ($hook !== 'plugins_page_plugin-update-manager' && $hook !== 'plugins.php') {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'pum-admin-style',
            PUM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PUM_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'pum-admin-script',
            PUM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            PUM_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('pum-admin-script', 'pum_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pum_ajax_nonce'),
            'disable_text' => __('Disable Updates', 'update-manager'),
            'enable_text' => __('Enable Updates', 'update-manager'),
            'confirm_enable' => __('Are you sure you want to enable updates for this plugin?', 'update-manager'),
            'error_message' => __('An error occurred. Please try again.', 'update-manager'),
            'disable_dialog_title' => __('Disable Plugin Updates', 'update-manager'),
            'plugin_label' => __('Plugin', 'update-manager'),
            'version_label' => __('Current Version', 'update-manager'),
            'reason_label' => __('Reason for Disabling', 'update-manager'),
            'reason_placeholder' => __('e.g., Custom modifications, compatibility issues, waiting for theme update...', 'update-manager'),
            'disable_button' => __('Disable Plugin', 'update-manager'),
            'cancel_button' => __('Cancel', 'update-manager'),
            'note_required' => __('Please provide a reason for disabling updates for this plugin.', 'update-manager')
        ));
    }
    
    /**
     * Add plugin action links
     *
     * @param array $links Existing links
     * @param string $plugin_file Plugin file
     * @return array Modified links
     */
    public function add_plugin_action_links($links, $plugin_file) {
        // Don't add link to our own plugin
        if ($plugin_file === PUM_PLUGIN_BASENAME) {
            return $links;
        }
        
        $is_disabled = $this->core->is_plugin_disabled($plugin_file);
        
        if ($is_disabled) {
            $action_link = sprintf(
                '<a href="#" class="pum-enable-link" data-plugin="%s">%s</a>',
                esc_attr($plugin_file),
                esc_html__('Enable Updates', 'update-manager')
            );
        } else {
            $action_link = sprintf(
                '<a href="#" class="pum-disable-link" data-plugin="%s">%s</a>',
                esc_attr($plugin_file),
                esc_html__('Disable Updates', 'update-manager')
            );
        }
        
        array_unshift($links, $action_link);
        
        return $links;
    }
    
    /**
     * Add disabled indicator to plugin row meta
     *
     * @param array $plugin_meta Plugin meta
     * @param string $plugin_file Plugin file
     * @return array Modified meta
     */
    public function add_disabled_indicator($plugin_meta, $plugin_file) {
        $disabled_plugin = $this->core->get_disabled_plugin($plugin_file);
        
        if ($disabled_plugin) {
            $indicator = sprintf(
                '<span class="pum-disabled-indicator" title="%s">%s %s</span>',
                esc_attr($disabled_plugin['disable_note']),
                esc_html__('Updates disabled at', 'update-manager'),
                esc_html($disabled_plugin['disabled_version'])
            );
            
            array_unshift($plugin_meta, $indicator);
        }
        
        return $plugin_meta;
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle form submission with nonce check
        if (isset($_POST['pum_action']) && isset($_POST['pum_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pum_nonce'])), 'pum_enable_action')) {
            $this->handle_form_submission();
        }
        
        $disabled_plugins = $this->core->get_disabled_plugins();
        $all_plugins = get_plugins();
        $disabled_count = count($disabled_plugins);
        ?>
        <div class="wrap">
            <div class="pum-admin-header">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <p><?php esc_html_e('Manage which plugins have updates disabled at specific versions. These plugins will not receive update notifications.', 'update-manager'); ?></p>
            </div>
            
            <div class="pum-stats-cards">
                <div class="pum-stat-card">
                    <h3><?php esc_html_e('Disabled Plugins', 'update-manager'); ?></h3>
                    <div class="pum-stat-number"><?php echo esc_html($disabled_count); ?></div>
                </div>
                <div class="pum-stat-card">
                    <h3><?php esc_html_e('Total Plugins', 'update-manager'); ?></h3>
                    <div class="pum-stat-number"><?php echo esc_html(count($all_plugins)); ?></div>
                </div>
                <div class="pum-stat-card">
                    <h3><?php esc_html_e('Protection Rate', 'update-manager'); ?></h3>
                    <div class="pum-stat-number"><?php echo $all_plugins ? esc_html(round(($disabled_count / count($all_plugins)) * 100)) : 0; ?>%</div>
                </div>
            </div>
            
            <?php if (empty($disabled_plugins)) : ?>
                <div class="pum-empty-state">
                    <div class="pum-empty-state-icon">🔓</div>
                    <h3><?php esc_html_e('No Disabled Plugin Updates Yet', 'update-manager'); ?></h3>
                    <p><?php esc_html_e('Start by disabling updates for a plugin from the Plugins page to prevent unwanted updates.', 'update-manager'); ?></p>
                    <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="button button-primary">
                        <?php esc_html_e('Go to Plugins', 'update-manager'); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="pum-table-wrapper">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Plugin', 'update-manager'); ?></th>
                                <th><?php esc_html_e('Disabled Version', 'update-manager'); ?></th>
                                <th><?php esc_html_e('Reason', 'update-manager'); ?></th>
                                <th><?php esc_html_e('Disabled Date', 'update-manager'); ?></th>
                                <th><?php esc_html_e('Disabled By', 'update-manager'); ?></th>
                                <th><?php esc_html_e('Actions', 'update-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($disabled_plugins as $disabled) : 
                                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $disabled['plugin_file']);
                                $user = get_userdata($disabled['disabled_by']);
                                $plugin_initial = strtoupper(substr($plugin_data['Name'], 0, 1));
                            ?>
                                <tr>
                                    <td>
                                        <div class="pum-plugin-info">
                                            <div class="pum-plugin-icon"><?php echo esc_html($plugin_initial); ?></div>
                                            <div>
                                                <strong><?php echo esc_html($plugin_data['Name']); ?></strong>
                                                <br>
                                                <code style="font-size: 11px;"><?php echo esc_html($disabled['plugin_file']); ?></code>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="pum-version-badge"><?php echo esc_html($disabled['disabled_version']); ?></span></td>
                                    <td><div class="pum-reason-text"><?php echo esc_html($disabled['disable_note']); ?></div></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($disabled['disabled_date']))); ?></td>
                                    <td>
                                        <?php if ($user) : ?>
                                            <div class="pum-user-avatar">
                                                <?php echo get_avatar($user->ID, 32); ?>
                                                <?php echo esc_html($user->display_name); ?>
                                            </div>
                                        <?php else : ?>
                                            <?php esc_html_e('Unknown', 'update-manager'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('pum_enable_action', 'pum_nonce'); ?>
                                            <input type="hidden" name="pum_action" value="enable">
                                            <input type="hidden" name="plugin_file" value="<?php echo esc_attr($disabled['plugin_file']); ?>">
                                            <button type="submit" class="button button-small"><?php esc_html_e('Enable', 'update-manager'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Handle form submission
     */
    private function handle_form_submission() {
        // Verify nonce with proper sanitization
        $nonce = isset($_POST['pum_nonce']) ? sanitize_text_field(wp_unslash($_POST['pum_nonce'])) : '';
        
        if (!wp_verify_nonce($nonce, 'pum_enable_action')) {
            wp_die(esc_html__('Security check failed', 'update-manager'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action', 'update-manager'));
        }
        
        $action = isset($_POST['pum_action']) ? sanitize_text_field(wp_unslash($_POST['pum_action'])) : '';
        
        if ($action === 'enable' && isset($_POST['plugin_file'])) {
            $plugin_file = sanitize_text_field(wp_unslash($_POST['plugin_file']));
            
            if ($this->core->enable_plugin($plugin_file)) {
                add_settings_error(
                    'pum_messages',
                    'pum_message',
                    esc_html__('Plugin updates enabled successfully.', 'update-manager'),
                    'updated'
                );
            } else {
                add_settings_error(
                    'pum_messages',
                    'pum_message',
                    esc_html__('Failed to enable plugin updates.', 'update-manager'),
                    'error'
                );
            }
        }
        
        // Display messages
        settings_errors('pum_messages');
    }
    
    /**
     * AJAX handler for disabling plugin
     */
    public function ajax_disable_plugin() {
        // Check nonce with proper sanitization
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        
        if (!wp_verify_nonce($nonce, 'pum_ajax_nonce')) {
            wp_die(esc_html__('Security check failed', 'update-manager'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action', 'update-manager'));
        }
        
        $plugin_file = isset($_POST['plugin_file']) ? sanitize_text_field(wp_unslash($_POST['plugin_file'])) : '';
        $version = isset($_POST['version']) ? sanitize_text_field(wp_unslash($_POST['version'])) : '';
        $note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';
        
        if ($this->core->disable_plugin($plugin_file, $version, $note)) {
            wp_send_json_success(array(
                'message' => esc_html__('Plugin updates disabled successfully.', 'update-manager')
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to disable plugin updates.', 'update-manager')
            ));
        }
    }
    
    /**
     * AJAX handler for enabling plugin
     */
    public function ajax_enable_plugin() {
        // Check nonce with proper sanitization
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        
        if (!wp_verify_nonce($nonce, 'pum_ajax_nonce')) {
            wp_die(esc_html__('Security check failed', 'update-manager'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action', 'update-manager'));
        }
        
        $plugin_file = isset($_POST['plugin_file']) ? sanitize_text_field(wp_unslash($_POST['plugin_file'])) : '';
        
        if ($this->core->enable_plugin($plugin_file)) {
            wp_send_json_success(array(
                'message' => esc_html__('Plugin updates enabled successfully.', 'update-manager')
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to enable plugin updates.', 'update-manager')
            ));
        }
    }
}