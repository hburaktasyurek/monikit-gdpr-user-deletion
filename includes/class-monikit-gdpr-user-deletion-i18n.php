<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://hbglobal.dev
 * @since      1.0.0
 *
 * @package    Monikit_Gdpr_User_Deletion
 * @subpackage Monikit_Gdpr_User_Deletion/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Monikit_Gdpr_User_Deletion
 * @subpackage Monikit_Gdpr_User_Deletion/includes
 * @author     Hasan Burak Taşyürek <admin@hbglobal.dev>
 */
class Monikit_Gdpr_User_Deletion_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'monikit-gdpr-user-deletion',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
