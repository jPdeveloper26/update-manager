<?php
/**
 * Plugin Name: Update Manager
 * Description: Disable plugin updates at specific versions with notes explaining why updates are disabled.
 * Version: 1.2.0
 * Author: Juan Mojica
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: update-manager
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if ( ! function_exists( 'cwppum_wpbay_sdk' ) ) {
    function cwppum_wpbay_sdk() {
        require_once dirname( __FILE__ ) . '/wpbay-sdk/WPBay_Loader.php';
        $sdk_instance = false;
        global $wpbay_sdk_latest_loader;
        $sdk_loader_class = $wpbay_sdk_latest_loader;
        $sdk_params = array(
            'api_key'                 => 'OIAKDA-LTRHGZK4VP5ZXK3DECZI2OJACI',
            'wpbay_product_id'        => '', 
            'product_file'            => __FILE__,
            'activation_redirect'     => '',
            'is_free'                 => true,
            'is_upgradable'           => false,
            'uploaded_to_wp_org'      => false,
            'disable_feedback'        => false,
            'disable_support_page'    => false,
            'disable_contact_form'    => false,
            'disable_upgrade_form'    => true,
            'disable_analytics'       => false,
            'rating_notice'           => '1 week',
            'debug_mode'              => 'false',
            'no_activation_required'  => false,
            'menu_data'               => array(
                'menu_slug' => ''
            ),
        );
        if ( class_exists( $sdk_loader_class ) ) {
            $sdk_instance = $sdk_loader_class::load_sdk( $sdk_params );
        }
        return $sdk_instance;
    }
    cwppum_wpbay_sdk();
    do_action( 'cwppum_wpbay_sdk_loaded' );
}

// Define plugin constants
define('PUM_VERSION', '1.0.0');
define('PUM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PUM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PUM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load text domain for translations
function pum_load_textdomain() {
    load_plugin_textdomain('plugin-update-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'pum_load_textdomain');

// Include required files
require_once PUM_PLUGIN_DIR . 'includes/class-plugin-update-manager-core.php';
require_once PUM_PLUGIN_DIR . 'includes/class-plugin-update-manager-admin.php';
require_once PUM_PLUGIN_DIR . 'includes/class-plugin-update-manager-updater.php';

// Initialize the plugin
function pum_init() {
    $plugin = new Plugin_Update_Manager_Core();
    $plugin->init();
}
add_action('init', 'pum_init');

// Activation hook
register_activation_hook(__FILE__, 'pum_activate');
function pum_activate() {
    // Create database table if needed
    $core = new Plugin_Update_Manager_Core();
    $core->create_database_table();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'pum_deactivate');
function pum_deactivate() {
    // Clean up temporary data
    delete_transient('pum_update_check');
    flush_rewrite_rules();
}
