<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://hbglobal.dev
 * @since             1.0.0
 * @package           Monikit_Gdpr_User_Deletion
 *
 * @wordpress-plugin
 * Plugin Name:       Monikit App GPDR User Data Deletion
 * Plugin URI:        https://github.com/hburaktasyurek/monikit-gdpr-user-deletion
 * Description:       Allows Monikit users to request deletion or anonymization of their account in accordance with GDPR using secure Keycloak API integration.
 * Version:           1.0.0
 * Author:            Hasan Burak Taşyürek
 * Author URI:        https://hbglobal.dev/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       monikit-gdpr-user-deletion
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'MONIKIT_GDPR_USER_DELETION_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-monikit-gdpr-user-deletion-activator.php
 */
function activate_monikit_gdpr_user_deletion() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-monikit-gdpr-user-deletion-activator.php';
	Monikit_Gdpr_User_Deletion_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-monikit-gdpr-user-deletion-deactivator.php
 */
function deactivate_monikit_gdpr_user_deletion() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-monikit-gdpr-user-deletion-deactivator.php';
	Monikit_Gdpr_User_Deletion_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_monikit_gdpr_user_deletion' );
register_deactivation_hook( __FILE__, 'deactivate_monikit_gdpr_user_deletion' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-monikit-gdpr-user-deletion.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_monikit_gdpr_user_deletion() {

	$plugin = new Monikit_Gdpr_User_Deletion();
	$plugin->run();

}
run_monikit_gdpr_user_deletion();
