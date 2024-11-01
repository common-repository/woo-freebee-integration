<?php

/**
 *
 * Plugin Name:       Woocommerce Freebee Integration
 * Plugin URI:        https://inspirelabs.pl/
 * Description:       Freebee integration with WooCommerce.
 * Version:           1.0.1
 * Author:            Inspire Labs
 * Text Domain:       freebee-il
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

register_activation_hook( __FILE__, 'activate_freebee_il' );
/**
 * The code that runs during plugin activation.
 */
function activate_freebee_il() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-freebee-activator.php';
	Freebee_Il_Activator::activate();
}

register_deactivation_hook( __FILE__, 'deactivate_freebee_il' );
/**
 * The code that runs during plugin deactivation.
 */
function deactivate_freebee_il() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-freebee-deactivator.php';
    Freebee_Il_Deactivator::deactivate();
}

register_uninstall_hook( __FILE__, 'uninstal_freebee_il');
/**
 * The code that runs during plugin deactivation.
 */
function uninstal_freebee_il() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-freebee-uninstaller.php';
    Freebee_Il_Uninstaller::uninstall();
}

/**
 * The core plugin class that is used to define internationalization,
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-freebee.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 */
function freebee_il_start_plugin() {
	$plugin = new Freebee_Il();
	$plugin->load_plugin(__FILE__);
}

freebee_il_start_plugin();