<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HELPER COMMENT START
 * 
 * This is the main class that is responsible for registering
 * the core functions, including the files and setting up all features. 
 * 
 * To add a new class, here's what you need to do: 
 * 1. Add your new class within the following folder: core/includes/classes
 * 2. Create a new variable you want to assign the class to (as e.g. public $helpers)
 * 3. Assign the class within the instance() function ( as e.g. self::$instance->helpers = new Monikit_App_Gdpr_User_Data_Deletion_Helpers();)
 * 4. Register the class you added to core/includes/classes within the includes() function
 * 
 * HELPER COMMENT END
 */

if ( ! class_exists( 'Monikit_App_Gdpr_User_Data_Deletion' ) ) :

	/**
	 * Main Monikit_App_Gdpr_User_Data_Deletion Class.
	 *
	 * @package		MONIGPDR
	 * @subpackage	Classes/Monikit_App_Gdpr_User_Data_Deletion
	 * @since		1.0.0
	 * @author		Hasan Burak TASYUREK
	 */
	final class Monikit_App_Gdpr_User_Data_Deletion {

		/**
		 * The real instance
		 *
		 * @access	private
		 * @since	1.0.0
		 * @var		object|Monikit_App_Gdpr_User_Data_Deletion
		 */
		private static $instance;

		/**
		 * MONIGPDR helpers object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Monikit_App_Gdpr_User_Data_Deletion_Helpers
		 */
		public $helpers;

		/**
		 * MONIGPDR settings object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Monikit_App_Gdpr_User_Data_Deletion_Settings
		 */
		public $settings;

		/**
		 * MONIGPDR admin object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Monikit_App_Gdpr_User_Data_Deletion_Admin
		 */
		public $admin;

		/**
		 * MONIGPDR public object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Monikit_App_Gdpr_User_Data_Deletion_Public
		 */
		public $public;

		/**
		 * MONIGPDR logs object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Monikit_App_Gdpr_User_Data_Deletion_Logs
		 */
		public $logs;

		/**
		 * MONIGPDR API object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Monikit_App_Gdpr_User_Data_Deletion_API
		 */
		public $api;

		/**
		 * Throw error on object clone.
		 *
		 * Cloning instances of the class is forbidden.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'monikit-app-gdpr-user-data-deletion' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'monikit-app-gdpr-user-data-deletion' ), '1.0.0' );
		}

		/**
		 * Main Monikit_App_Gdpr_User_Data_Deletion Instance.
		 *
		 * Insures that only one instance of Monikit_App_Gdpr_User_Data_Deletion exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @access		public
		 * @since		1.0.0
		 * @static
		 * @return		object|Monikit_App_Gdpr_User_Data_Deletion	The one true Monikit_App_Gdpr_User_Data_Deletion
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Monikit_App_Gdpr_User_Data_Deletion ) ) {
				self::$instance					= new Monikit_App_Gdpr_User_Data_Deletion;
				self::$instance->base_hooks();
				self::$instance->includes();
				self::$instance->helpers		= new Monikit_App_Gdpr_User_Data_Deletion_Helpers();
				self::$instance->settings		= new Monikit_App_Gdpr_User_Data_Deletion_Settings();
				self::$instance->admin		= new Monikit_App_Gdpr_User_Data_Deletion_Admin();
				self::$instance->public		= new Monikit_App_Gdpr_User_Data_Deletion_Public();
				self::$instance->logs		= new Monikit_App_Gdpr_User_Data_Deletion_Logs();
				self::$instance->api		= new Monikit_App_Gdpr_User_Data_Deletion_API();

				//Fire the plugin logic
				new Monikit_App_Gdpr_User_Data_Deletion_Run();

				/**
				 * Fire a custom action to allow dependencies
				 * after the successful plugin setup
				 */
				do_action( 'MONIGPDR/plugin_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Include required files.
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function includes() {
					require_once MONIGPDR_PLUGIN_DIR . 'core/includes/classes/class-monikit-app-gdpr-user-data-deletion-helpers.php';
		require_once MONIGPDR_PLUGIN_DIR . 'core/includes/classes/class-monikit-app-gdpr-user-data-deletion-settings.php';
		require_once MONIGPDR_PLUGIN_DIR . 'core/includes/classes/class-monikit-app-gdpr-user-data-deletion-admin.php';
		require_once MONIGPDR_PLUGIN_DIR . 'core/includes/classes/class-monikit-app-gdpr-user-data-deletion-public.php';
		require_once MONIGPDR_PLUGIN_DIR . 'core/includes/classes/class-monikit-app-gdpr-user-data-deletion-logs.php';
		require_once MONIGPDR_PLUGIN_DIR . 'core/includes/classes/class-monikit-app-gdpr-user-data-deletion-api.php';

		require_once MONIGPDR_PLUGIN_DIR . 'core/includes/classes/class-monikit-app-gdpr-user-data-deletion-run.php';
		}

		/**
		 * Add base hooks for the core functionality
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function base_hooks() {
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @return  void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'monikit-app-gdpr-user-data-deletion', FALSE, dirname( plugin_basename( MONIGPDR_PLUGIN_FILE ) ) . '/languages/' );
		}

	}

endif; // End if class_exists check.