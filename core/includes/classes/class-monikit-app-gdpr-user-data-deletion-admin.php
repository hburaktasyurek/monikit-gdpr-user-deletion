<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Monikit_App_Gdpr_User_Data_Deletion_Admin
 *
 * This class handles the admin settings page functionality.
 *
 * @package		MONIGPDR
 * @subpackage	Classes/Monikit_App_Gdpr_User_Data_Deletion_Admin
 * @author		Hasan Burak TASYUREK
 * @since		1.0.0
 */
class Monikit_App_Gdpr_User_Data_Deletion_Admin {

	/**
	 * Settings option name
	 *
	 * @var		string
	 * @since   1.0.0
	 */
	private $option_name = 'monikit_settings';

	/**
	 * Our Monikit_App_Gdpr_User_Data_Deletion_Admin constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct(){
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'init_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_monikit_test_keycloak_connection', array( $this, 'test_keycloak_connection' ) );
		add_action( 'wp_ajax_monikit_preview_email_template', array( $this, 'preview_email_template' ) );
		add_action( 'wp_ajax_monikit_load_default_templates', array( $this, 'load_default_templates' ) );
		add_action( 'wp_ajax_monikit_save_translations', array( $this, 'save_translations' ) );
		add_action( 'wp_ajax_monikit_export_logs', array( $this, 'export_logs' ) );
		add_action( 'wp_ajax_monikit_cleanup_logs', array( $this, 'cleanup_logs' ) );
		add_action( 'wp_ajax_monikit_get_log_details', array( $this, 'get_log_details' ) );
		add_action( 'wp_ajax_monikit_delete_selected_logs', array( $this, 'delete_selected_logs' ) );
		add_action( 'wp_ajax_monikit_delete_single_log', array( $this, 'delete_single_log' ) );
	}

	/**
	 * Add admin menu
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Monikit Settings', 'monikit-app-gdpr-user-data-deletion' ),
			__( 'Monikit', 'monikit-app-gdpr-user-data-deletion' ),
			'manage_options',
			'monikit_settings',
			array( $this, 'admin_page' ),
			'dashicons-admin-generic',
			30
		);
		
		// Add Translation submenu
		add_submenu_page(
			'monikit_settings',
			__( 'Translations', 'monikit-app-gdpr-user-data-deletion' ),
			__( 'Translations', 'monikit-app-gdpr-user-data-deletion' ),
			'manage_options',
			'monikit_translations',
			array( $this, 'translations_page' )
		);
		
		// Add Logs submenu
		add_submenu_page(
			'monikit_settings',
			__( 'Deletion Logs', 'monikit-app-gdpr-user-data-deletion' ),
			__( 'Logs', 'monikit-app-gdpr-user-data-deletion' ),
			'manage_options',
			'monikit_logs',
			array( $this, 'logs_page' )
		);
	}

	/**
	 * Initialize settings
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function init_settings() {
		register_setting(
			'monikit_settings_group',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);

		// Keycloak Settings Section
		add_settings_section(
			'keycloak_settings_section',
			__( 'Keycloak Connection Settings', 'monikit-app-gdpr-user-data-deletion' ),
			array( $this, 'keycloak_section_callback' ),
			'monikit_settings'
		);

		// Email Templates Section
		add_settings_section(
			'email_templates_section',
			__( 'Email Templates', 'monikit-app-gdpr-user-data-deletion' ),
			array( $this, 'email_templates_section_callback' ),
			'monikit_settings'
		);

		// Language Settings Section
		add_settings_section(
			'language_settings_section',
			__( 'Language Settings', 'monikit-app-gdpr-user-data-deletion' ),
			array( $this, 'language_section_callback' ),
			'monikit_settings'
		);

		// Public Deletion Form Section
		add_settings_section(
			'public_deletion_section',
			__( 'Public Deletion Form', 'monikit-app-gdpr-user-data-deletion' ),
			array( $this, 'public_deletion_section_callback' ),
			'monikit_settings'
		);

		// Keycloak Fields
		$keycloak_fields = array(
			'keycloak_base_url' => __( 'Keycloak Base URL', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_realm' => __( 'Realm', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_client_id' => __( 'Client ID', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_client_secret' => __( 'Client Secret', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_admin_username' => __( 'Admin Username', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_admin_password' => __( 'Admin Password', 'monikit-app-gdpr-user-data-deletion' ),
		);

		foreach ( $keycloak_fields as $field_key => $field_label ) {
			$field_type = in_array( $field_key, array( 'keycloak_client_secret', 'keycloak_admin_password' ) ) ? 'password' : 'text';
			$is_required = in_array( $field_key, array( 'keycloak_base_url', 'keycloak_realm', 'keycloak_client_id', 'keycloak_admin_username', 'keycloak_admin_password' ) );
			add_settings_field(
				$field_key,
				$field_label . ( $is_required ? ' <span style="color: #dc3232;">*</span>' : '' ),
				array( $this, 'render_field' ),
				'monikit_settings',
				'keycloak_settings_section',
				array(
					'field_key' => $field_key,
					'field_type' => $field_type,
					'required' => $is_required,
					'description' => $this->get_field_description( $field_key )
				)
			);
		}

		// Email Template Fields
		$email_fields = array(
			'email_subject_en' => __( 'E-Mail Subject (English)', 'monikit-app-gdpr-user-data-deletion' ),
			'email_html_en' => __( 'E-Mail Body (HTML - English)', 'monikit-app-gdpr-user-data-deletion' ),
			'email_subject_de' => __( 'E-Mail Subject (German)', 'monikit-app-gdpr-user-data-deletion' ),
			'email_html_de' => __( 'E-Mail Body (HTML - German)', 'monikit-app-gdpr-user-data-deletion' ),
		);

		foreach ( $email_fields as $field_key => $field_label ) {
			$field_type = strpos( $field_key, 'html' ) !== false ? 'wysiwyg' : 'text';
			add_settings_field(
				$field_key,
				$field_label,
				array( $this, 'render_field' ),
				'monikit_settings',
				'email_templates_section',
				array(
					'field_key' => $field_key,
					'field_type' => $field_type,
					'required' => false,
					'description' => $this->get_field_description( $field_key )
				)
			);
		}

		// Language Field
		add_settings_field(
			'default_language',
			__( 'Default Language', 'monikit-app-gdpr-user-data-deletion' ),
			array( $this, 'render_field' ),
			'monikit_settings',
			'language_settings_section',
			array(
				'field_key' => 'default_language',
				'field_type' => 'select',
				'required' => false,
				'options' => array(
					'en' => __( 'English', 'monikit-app-gdpr-user-data-deletion' ),
					'de' => __( 'German', 'monikit-app-gdpr-user-data-deletion' ),
				),
				'description' => $this->get_field_description( 'default_language' )
			)
		);

		// Public Deletion Form Field
		add_settings_field(
			'enable_public_deletion',
			__( 'Enable Public Deletion Form', 'monikit-app-gdpr-user-data-deletion' ),
			array( $this, 'render_field' ),
			'monikit_settings',
			'public_deletion_section',
			array(
				'field_key' => 'enable_public_deletion',
				'field_type' => 'checkbox',
				'required' => false,
				'description' => $this->get_field_description( 'enable_public_deletion' )
			)
		);
	}

	/**
	 * Render field
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	array $args Field arguments
	 * @return	void
	 */
	public function render_field( $args ) {
		$field_key = $args['field_key'];
		$field_type = $args['field_type'];
		$options = isset( $args['options'] ) ? $args['options'] : array();
		$description = isset( $args['description'] ) ? $args['description'] : '';
		$required = isset( $args['required'] ) ? $args['required'] : false;
		
		$current_settings = $this->get_settings();
		$current_value = isset( $current_settings[ $field_key ] ) ? $current_settings[ $field_key ] : '';

		switch ( $field_type ) {
			case 'text':
			case 'password':
				printf(
					'<input type="%s" id="%s" name="%s[%s]" value="%s" class="regular-text" %s />',
					esc_attr( $field_type ),
					esc_attr( $field_key ),
					esc_attr( $this->option_name ),
					esc_attr( $field_key ),
					esc_attr( $current_value ),
					$required ? 'required' : ''
				);
				break;

			case 'checkbox':
				printf(
					'<input type="checkbox" id="%s" name="%s[%s]" value="1" %s />',
					esc_attr( $field_key ),
					esc_attr( $this->option_name ),
					esc_attr( $field_key ),
					checked( $current_value, '1', false )
				);
				break;

			case 'select':
				printf(
					'<select id="%s" name="%s[%s]" %s>',
					esc_attr( $field_key ),
					esc_attr( $this->option_name ),
					esc_attr( $field_key ),
					$required ? 'required' : ''
				);
				foreach ( $options as $value => $label ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $value ),
						selected( $current_value, $value, false ),
						esc_html( $label )
					);
				}
				echo '</select>';
				break;

			case 'wysiwyg':
				wp_editor(
					$current_value,
					$field_key,
					array(
						'textarea_name' => $this->option_name . '[' . $field_key . ']',
						'textarea_rows' => 10,
						'media_buttons' => false,
						'teeny' => true,
						'tinymce' => array(
							'toolbar1' => 'bold,italic,underline,link,unlink,bullist,numlist',
							'toolbar2' => '',
						),
					)
				);
				break;
		}

		if ( ! empty( $description ) ) {
			printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
		}
	}

	/**
	 * Get field description
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string $field_key Field key
	 * @return	string Description
	 */
	private function get_field_description( $field_key ) {
		$descriptions = array(
			'keycloak_base_url' => __( 'Example: https://testserver.monikit.com/', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_realm' => __( 'Example: Patient', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_client_id' => __( 'Example: admin-cli', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_client_secret' => __( 'Client secret if required', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_admin_username' => __( 'Keycloak admin API username', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_admin_password' => __( 'Password for API access', 'monikit-app-gdpr-user-data-deletion' ),
			'email_subject_en' => __( 'Email subject for English users', 'monikit-app-gdpr-user-data-deletion' ),
			'email_html_en' => __( 'HTML email template for English users. Use {confirmation_link} and {confirmation_code} placeholders.', 'monikit-app-gdpr-user-data-deletion' ),
			'email_subject_de' => __( 'Email subject for German users', 'monikit-app-gdpr-user-data-deletion' ),
			'email_html_de' => __( 'HTML email template for German users. Use {confirmation_link} and {confirmation_code} placeholders.', 'monikit-app-gdpr-user-data-deletion' ),
			'default_language' => __( 'Default language for the plugin', 'monikit-app-gdpr-user-data-deletion' ),
			'enable_public_deletion' => __( 'Enable the public deletion form for users to request account deletion using the shortcode [monigpdr_deletion_form]', 'monikit-app-gdpr-user-data-deletion' ),
		);

		return isset( $descriptions[ $field_key ] ) ? $descriptions[ $field_key ] : '';
	}

	/**
	 * Get field label
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string $field_key Field key
	 * @return	string Label
	 */
	private function get_field_label( $field_key ) {
		$labels = array(
			'keycloak_base_url' => __( 'Keycloak Base URL', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_realm' => __( 'Realm', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_client_id' => __( 'Client ID', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_client_secret' => __( 'Client Secret', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_admin_username' => __( 'Admin Username', 'monikit-app-gdpr-user-data-deletion' ),
			'keycloak_admin_password' => __( 'Admin Password', 'monikit-app-gdpr-user-data-deletion' ),
			'email_subject_en' => __( 'E-Mail Subject (English)', 'monikit-app-gdpr-user-data-deletion' ),
			'email_html_en' => __( 'E-Mail Body (HTML - English)', 'monikit-app-gdpr-user-data-deletion' ),
			'email_subject_de' => __( 'E-Mail Subject (German)', 'monikit-app-gdpr-user-data-deletion' ),
			'email_html_de' => __( 'E-Mail Body (HTML - German)', 'monikit-app-gdpr-user-data-deletion' ),
			'default_language' => __( 'Default Language', 'monikit-app-gdpr-user-data-deletion' ),
		);

		return isset( $labels[ $field_key ] ) ? $labels[ $field_key ] : $field_key;
	}

	/**
	 * Section callbacks
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function keycloak_section_callback() {
		echo '<p>' . esc_html__( 'Configure your Keycloak connection settings.', 'monikit-app-gdpr-user-data-deletion' ) . '</p>';
		echo '<div class="keycloak-test-section">';
		echo '<button type="button" class="button button-secondary test-keycloak-connection" style="margin-top: 10px;">';
		echo esc_html__( '🔐 Test Keycloak Connection', 'monikit-app-gdpr-user-data-deletion' );
		echo '</button>';
		echo '<div class="keycloak-test-result" style="margin-top: 10px; display: none;"></div>';
		echo '</div>';
	}

	public function email_templates_section_callback() {
		echo '<p>' . esc_html__( 'Configure email templates for user notifications. Available placeholders: {user_email}, {confirmation_link}, {confirmation_code}', 'monikit-app-gdpr-user-data-deletion' ) . '</p>';
		echo '<div class="email-preview-section">';
		echo '<button type="button" class="button button-secondary preview-email-en" style="margin-right: 10px;">';
		echo esc_html__( '📧 Preview English Email', 'monikit-app-gdpr-user-data-deletion' );
		echo '</button>';
		echo '<button type="button" class="button button-secondary preview-email-de" style="margin-right: 10px;">';
		echo esc_html__( '📧 Preview German Email', 'monikit-app-gdpr-user-data-deletion' );
		echo '</button>';
		echo '<button type="button" class="button button-secondary load-default-templates" style="margin-right: 10px;">';
		echo esc_html__( '📝 Load Default Templates', 'monikit-app-gdpr-user-data-deletion' );
		echo '</button>';
		echo '<div class="email-preview-result" style="margin-top: 10px; display: none;"></div>';
		echo '</div>';
	}

	public function language_section_callback() {
		echo '<p>' . esc_html__( 'Configure language settings for the plugin.', 'monikit-app-gdpr-user-data-deletion' ) . '</p>';
	}

	public function public_deletion_section_callback() {
		echo '<p>' . esc_html__( 'Configure the public deletion form settings. This allows users to request account deletion using the shortcode.', 'monikit-app-gdpr-user-data-deletion' ) . '</p>';
		
		$settings = $this->get_settings();
		$is_enabled = isset( $settings['enable_public_deletion'] ) ? $settings['enable_public_deletion'] : '0';
		
		if ( $is_enabled === '1' ) {
			echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 12px; margin: 10px 0;">';
			echo '<strong>' . esc_html__( '✅ Public Deletion Form is Enabled', 'monikit-app-gdpr-user-data-deletion' ) . '</strong><br>';
			echo esc_html__( 'You can now use the shortcode', 'monikit-app-gdpr-user-data-deletion' ) . ' <code>[monigpdr_deletion_form]</code> ' . esc_html__( 'to embed the deletion form anywhere on your site.', 'monikit-app-gdpr-user-data-deletion' );
			echo '</div>';
			
			// Add shortcode examples
			echo '<div style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; padding: 12px; margin: 10px 0;">';
			echo '<strong>' . esc_html__( '📝 Shortcode Examples', 'monikit-app-gdpr-user-data-deletion' ) . '</strong><br>';
			echo '<code>[monigpdr_deletion_form]</code> - ' . esc_html__( 'Basic form', 'monikit-app-gdpr-user-data-deletion' ) . '<br>';
			echo '<code>[monigpdr_deletion_form style="minimal"]</code> - ' . esc_html__( 'Minimal style', 'monikit-app-gdpr-user-data-deletion' ) . '<br>';
			echo '<code>[monigpdr_deletion_form title="Delete Account"]</code> - ' . esc_html__( 'Custom title', 'monikit-app-gdpr-user-data-deletion' );
			echo '</div>';
		} else {
			echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 12px; margin: 10px 0;">';
			echo '<strong>' . esc_html__( '❌ Public Deletion Form is Disabled', 'monikit-app-gdpr-user-data-deletion' ) . '</strong>';
			echo '</div>';
		}
	}

	/**
	 * Sanitize settings
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	array $input Input data
	 * @return	array Sanitized data
	 */
	public function sanitize_settings( $input ) {
		$sanitized_input = array();
		$errors = array();

		// Required Keycloak fields
		$required_keycloak_fields = array(
			'keycloak_base_url',
			'keycloak_realm',
			'keycloak_client_id',
			'keycloak_admin_username',
			'keycloak_admin_password',
		);

		// Validate required fields
		foreach ( $required_keycloak_fields as $field ) {
			if ( empty( $input[ $field ] ) ) {
				$errors[] = sprintf( __( 'The field "%s" is required.', 'monikit-app-gdpr-user-data-deletion' ), $this->get_field_label( $field ) );
			}
		}

		// If there are errors, add them and return current settings
		if ( ! empty( $errors ) ) {
			foreach ( $errors as $error ) {
				add_settings_error(
					'monikit_settings',
					'monikit_required_fields',
					$error,
					'error'
				);
			}
			return get_option( $this->option_name, array() );
		}

		// Keycloak settings
		$keycloak_fields = array(
			'keycloak_base_url',
			'keycloak_realm',
			'keycloak_client_id',
			'keycloak_client_secret',
			'keycloak_admin_username',
			'keycloak_admin_password',
		);

		foreach ( $keycloak_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$value = sanitize_text_field( $input[ $field ] );
				
				// Normalize the base URL
				if ( $field === 'keycloak_base_url' ) {
					$value = $this->normalize_keycloak_url( $value );
				}
				
				$sanitized_input[ $field ] = $value;
			}
		}

		// Email settings
		$email_text_fields = array(
			'email_subject_en',
			'email_subject_de',
		);

		foreach ( $email_text_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized_input[ $field ] = sanitize_text_field( $input[ $field ] );
			}
		}

		// HTML email fields
		$email_html_fields = array(
			'email_html_en',
			'email_html_de',
		);

		foreach ( $email_html_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized_input[ $field ] = wp_kses_post( $input[ $field ] );
			}
		}

		// Language setting
		if ( isset( $input['default_language'] ) ) {
			$sanitized_input['default_language'] = sanitize_text_field( $input['default_language'] );
		}

		// Public deletion form settings
		if ( isset( $input['enable_public_deletion'] ) ) {
			$sanitized_input['enable_public_deletion'] = '1';
		} else {
			$sanitized_input['enable_public_deletion'] = '0';
		}

		return $sanitized_input;
	}

	/**
	 * Admin page
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'monikit_messages',
				'monikit_message',
				__( 'Settings Saved', 'monikit-app-gdpr-user-data-deletion' ),
				'updated'
			);
		}

		?>
		<div class="wrap monikit-settings-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors( 'monikit_messages' ); ?>

			<div class="section-description">
				<p><?php esc_html_e( 'Configure your Monikit GDPR User Data Deletion plugin settings. All settings are stored securely and can be accessed programmatically.', 'monikit-app-gdpr-user-data-deletion' ); ?></p>
			</div>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'monikit_settings_group' );
				do_settings_sections( 'monikit_settings' );
				?>
				
				<div class="submit">
					<?php submit_button( __( 'Save Settings', 'monikit-app-gdpr-user-data-deletion' ) ); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	string $hook Current admin page hook
	 * @return	void
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Load scripts on main settings page or any page that might be our translations page
		if ( 'toplevel_page_monikit_settings' !== $hook && 
			 'toplevel_page_monikit_translations' !== $hook &&
			 'monikit_page_monikit_translations' !== $hook &&
			 strpos( $hook, 'monikit' ) === false ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_editor();

		// Enqueue custom admin styles and scripts
		wp_enqueue_style(
			'monikit-admin-styles',
			MONIGPDR_PLUGIN_URL . 'core/includes/assets/css/admin-styles.css',
			array(),
			MONIGPDR_VERSION
		);

		wp_enqueue_script(
			'monikit-admin-scripts',
			MONIGPDR_PLUGIN_URL . 'core/includes/assets/js/admin-scripts.js',
			array( 'jquery' ),
			MONIGPDR_VERSION,
			true
		);

		// Localize script for AJAX
		wp_localize_script(
			'monikit-admin-scripts',
			'monikit_ajax',
			array(
				'nonce' => wp_create_nonce( 'monikit_admin_nonce' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Get settings
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	string $key Optional specific setting key
	 * @return	mixed Settings array or specific value
	 */
	public function get_settings( $key = null ) {
		$settings = get_option( $this->option_name, array() );
		
		// Set default values if settings are empty
		if ( empty( $settings ) ) {
			$settings = $this->get_default_settings();
			update_option( $this->option_name, $settings );
		}
		
		if ( $key ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
		}
		
		return $settings;
	}

	/**
	 * Get default settings
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	array Default settings
	 */
	private function get_default_settings() {
		return array(
			'default_language' => 'en',
			'email_subject_en' => __( 'Confirm Your Data Deletion Request', 'monikit-app-gdpr-user-data-deletion' ),
			'email_html_en' => $this->get_default_email_template_en(),
			'email_subject_de' => __( 'Bestätigen Sie Ihre Datenlöschungsanfrage', 'monikit-app-gdpr-user-data-deletion' ),
			'email_html_de' => $this->get_default_email_template_de(),
			'enable_public_deletion' => '0',
		);
	}

	/**
	 * Get default English email template
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	string Default English email template
	 */
	private function get_default_email_template_en() {
		return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Deletion Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h1 style="color: #0073aa; margin: 0 0 20px 0; font-size: 24px;">Data Deletion Request</h1>
        
        <p>Dear {user_email},</p>
        
        <p>We have received your request to delete your personal data from our system. To proceed with this request, please confirm your intention by clicking the link below or entering the confirmation code.</p>
        
        <div style="background: #fff; padding: 20px; border-radius: 5px; margin: 20px 0; text-align: center;">
            <a href="{confirmation_link}" style="background: #0073aa; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Confirm Deletion Request</a>
        </div>
        
        <p><strong>Confirmation Code:</strong> <code style="background: #f1f1f1; padding: 5px 10px; border-radius: 3px; font-family: monospace;">{confirmation_code}</code></p>
        
        <p>If you did not request this deletion, please ignore this email. Your data will remain secure.</p>
        
        <p>This confirmation link will expire in 24 hours for security reasons.</p>
        
        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
        
        <p style="font-size: 12px; color: #666;">
            This is an automated message. Please do not reply to this email.<br>
            If you have any questions, please contact our support team.
        </p>
    </div>
</body>
</html>';
	}

	/**
	 * Get default German email template
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	string Default German email template
	 */
	private function get_default_email_template_de() {
		return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenlöschungsbestätigung</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h1 style="color: #0073aa; margin: 0 0 20px 0; font-size: 24px;">Datenlöschungsanfrage</h1>
        
        <p>Sehr geehrte/r {user_email},</p>
        
        <p>Wir haben Ihre Anfrage zur Löschung Ihrer persönlichen Daten aus unserem System erhalten. Um mit dieser Anfrage fortzufahren, bestätigen Sie bitte Ihre Absicht durch Klicken auf den untenstehenden Link oder durch Eingabe des Bestätigungscodes.</p>
        
        <div style="background: #fff; padding: 20px; border-radius: 5px; margin: 20px 0; text-align: center;">
            <a href="{confirmation_link}" style="background: #0073aa; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Löschungsanfrage bestätigen</a>
        </div>
        
        <p><strong>Bestätigungscode:</strong> <code style="background: #f1f1f1; padding: 5px 10px; border-radius: 3px; font-family: monospace;">{confirmation_code}</code></p>
        
        <p>Falls Sie diese Löschung nicht angefordert haben, ignorieren Sie bitte diese E-Mail. Ihre Daten bleiben sicher.</p>
        
        <p>Dieser Bestätigungslink läuft aus Sicherheitsgründen in 24 Stunden ab.</p>
        
        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
        
        <p style="font-size: 12px; color: #666;">
            Dies ist eine automatische Nachricht. Bitte antworten Sie nicht auf diese E-Mail.<br>
            Bei Fragen wenden Sie sich bitte an unser Support-Team.
        </p>
    </div>
</body>
</html>';
	}

	/**
	 * Get email template
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	string $language Language code
	 * @param	string $type Template type (subject or html)
	 * @return	string Template content
	 */
	public function get_email_template( $language = 'en', $type = 'html' ) {
		$settings = $this->get_settings();
		$field_key = 'email_' . $type . '_' . $language;
		
		$template = isset( $settings[ $field_key ] ) ? $settings[ $field_key ] : '';
		
		// Allow template override via filter
		return apply_filters( 'monikit_email_template_' . $language, $template, $type );
	}

	/**
	 * Test Keycloak connection via AJAX
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function test_keycloak_connection() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'monikit_admin_nonce' ) ) {
			wp_die( __( 'Security check failed.', 'monikit-app-gdpr-user-data-deletion' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'monikit-app-gdpr-user-data-deletion' ) );
		}

		$settings = $_POST['settings'];
		
		// Debug: Log the received settings (only in debug mode)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Monikit Debug - Received settings: ' . print_r( $settings, true ) );
		}
		
		// Extract Keycloak settings with proper field name handling
		$base_url = '';
		$realm = '';
		$client_id = '';
		$client_secret = '';
		$admin_username = '';
		$admin_password = '';
		
		// Parse the settings array properly
		foreach ( $settings as $key => $value ) {
			if ( $key === 'monikit_settings[keycloak_base_url]' ) {
				$base_url = sanitize_text_field( $value );
			} elseif ( $key === 'monikit_settings[keycloak_realm]' ) {
				$realm = sanitize_text_field( $value );
			} elseif ( $key === 'monikit_settings[keycloak_client_id]' ) {
				$client_id = sanitize_text_field( $value );
			} elseif ( $key === 'monikit_settings[keycloak_client_secret]' ) {
				$client_secret = sanitize_text_field( $value );
			} elseif ( $key === 'monikit_settings[keycloak_admin_username]' ) {
				$admin_username = sanitize_text_field( $value );
			} elseif ( $key === 'monikit_settings[keycloak_admin_password]' ) {
				$admin_password = sanitize_text_field( $value );
			}
		}
		
		// Debug: Log the extracted values (only in debug mode)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Monikit Debug - Extracted values: base_url=' . $base_url . ', realm=' . $realm . ', client_id=' . $client_id . ', admin_username=' . $admin_username . ', admin_password=' . ( ! empty( $admin_password ) ? 'SET' : 'EMPTY' ) );
		}

		// Basic validation with detailed error messages
		$missing_fields = array();
		if ( empty( $base_url ) ) $missing_fields[] = 'Keycloak Base URL';
		if ( empty( $realm ) ) $missing_fields[] = 'Realm';
		if ( empty( $client_id ) ) $missing_fields[] = 'Client ID';
		if ( empty( $admin_username ) ) $missing_fields[] = 'Admin Username';
		if ( empty( $admin_password ) ) $missing_fields[] = 'Admin Password';
		
		// If we have missing fields, try to get them from saved settings
		if ( ! empty( $missing_fields ) ) {
			$saved_settings = get_option( $this->option_name, array() );
			
			if ( empty( $base_url ) && ! empty( $saved_settings['keycloak_base_url'] ) ) {
				$base_url = $saved_settings['keycloak_base_url'];
				$missing_fields = array_diff( $missing_fields, array( 'Keycloak Base URL' ) );
			}
			if ( empty( $realm ) && ! empty( $saved_settings['keycloak_realm'] ) ) {
				$realm = $saved_settings['keycloak_realm'];
				$missing_fields = array_diff( $missing_fields, array( 'Realm' ) );
			}
			if ( empty( $client_id ) && ! empty( $saved_settings['keycloak_client_id'] ) ) {
				$client_id = $saved_settings['keycloak_client_id'];
				$missing_fields = array_diff( $missing_fields, array( 'Client ID' ) );
			}
			if ( empty( $admin_username ) && ! empty( $saved_settings['keycloak_admin_username'] ) ) {
				$admin_username = $saved_settings['keycloak_admin_username'];
				$missing_fields = array_diff( $missing_fields, array( 'Admin Username' ) );
			}
			if ( empty( $admin_password ) && ! empty( $saved_settings['keycloak_admin_password'] ) ) {
				$admin_password = $saved_settings['keycloak_admin_password'];
				$missing_fields = array_diff( $missing_fields, array( 'Admin Password' ) );
			}
		}
		
		// Final validation check
		if ( ! empty( $missing_fields ) ) {
			$error_message = sprintf( 
				__( 'Please fill in the following required fields: %s', 'monikit-app-gdpr-user-data-deletion' ),
				implode( ', ', $missing_fields )
			);
			wp_send_json_error( $error_message );
		}

		// Normalize the base URL
		$base_url = $this->normalize_keycloak_url( $base_url );

		// Test connection by attempting to get access token
		// Check if /auth is already in the base URL
		if ( strpos( $base_url, '/auth' ) !== false ) {
			$token_url = trailingslashit( $base_url ) . 'realms/master/protocol/openid-connect/token';
		} else {
			$token_url = trailingslashit( $base_url ) . 'auth/realms/master/protocol/openid-connect/token';
		}
		
		// Prepare POST body
		$post_body = array(
			'grant_type' => 'password',
			'client_id' => $client_id,
			'username' => $admin_username,
			'password' => $admin_password,
		);

		// Add client secret if provided
		if ( ! empty( $client_secret ) ) {
			$post_body['client_secret'] = $client_secret;
		}

		// Make the token request
		$response = wp_remote_post( $token_url, array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'User-Agent' => 'Monikit-Plugin/1.0',
			),
			'body' => $post_body,
		) );

		// Handle connection errors
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( sprintf( __( 'Connection error: %s', 'monikit-app-gdpr-user-data-deletion' ), $response->get_error_message() ) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		// Check for successful response
		if ( $status_code === 200 && ! empty( $response_data ) ) {
			// Check if access token is present
			if ( isset( $response_data['access_token'] ) && ! empty( $response_data['access_token'] ) ) {
				$access_token = $response_data['access_token'];
				
				// Step 2: Test user realm access
				$realm_access_result = $this->test_realm_access( $base_url, $realm, $access_token );
				
				if ( $realm_access_result['success'] ) {
					wp_send_json_success( __( '✅ Connected successfully to Keycloak. Access token retrieved and realm access verified.', 'monikit-app-gdpr-user-data-deletion' ) );
				} else {
					// Token is valid but realm access failed
					wp_send_json_error( sprintf( 
						__( '⚠️ Token retrieved successfully, but realm access failed: %s', 'monikit-app-gdpr-user-data-deletion' ),
						$realm_access_result['message']
					) );
				}
			} else {
				wp_send_json_error( __( '❌ Connection successful but no access token received.', 'monikit-app-gdpr-user-data-deletion' ) );
			}
		} else {
			// Handle error responses
			if ( ! empty( $response_data ) && isset( $response_data['error_description'] ) ) {
				wp_send_json_error( sprintf( __( '❌ Keycloak error: %s', 'monikit-app-gdpr-user-data-deletion' ), $response_data['error_description'] ) );
			} elseif ( ! empty( $response_data ) && isset( $response_data['error'] ) ) {
				wp_send_json_error( sprintf( __( '❌ Keycloak error: %s', 'monikit-app-gdpr-user-data-deletion' ), $response_data['error'] ) );
			} else {
				wp_send_json_error( sprintf( __( '❌ Connection failed with status code: %d', 'monikit-app-gdpr-user-data-deletion' ), $status_code ) );
			}
		}
	}

	/**
	 * Normalize Keycloak base URL to ensure proper formatting
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string $url The URL to normalize
	 * @return	string Normalized URL
	 */
	private function normalize_keycloak_url( $url ) {
		// Remove any whitespace
		$url = trim( $url );
		
		// If URL is empty, return as is
		if ( empty( $url ) ) {
			return $url;
		}
		
		// Add protocol if missing
		if ( ! preg_match( '/^https?:\/\//', $url ) ) {
			$url = 'https://' . $url;
		}
		
		// Ensure it ends with a single slash
		$url = rtrim( $url, '/' ) . '/';
		
		return $url;
	}

	/**
	 * Test realm access with the retrieved access token
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string $base_url Keycloak base URL
	 * @param	string $realm Realm name
	 * @param	string $access_token Access token
	 * @return	array Result with success status and message
	 */
	private function test_realm_access( $base_url, $realm, $access_token ) {
		// Build the users endpoint URL - check if /auth is in the base URL
		if ( strpos( $base_url, '/auth' ) !== false ) {
			$users_url = trailingslashit( $base_url ) . 'admin/realms/' . urlencode( $realm ) . '/users';
		} else {
			$users_url = trailingslashit( $base_url ) . 'auth/admin/realms/' . urlencode( $realm ) . '/users';
		}
		
		// Make authenticated request to get users
		$response = wp_remote_get( $users_url, array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type' => 'application/json',
				'User-Agent' => 'Monikit-Plugin/1.0',
			),
		) );
		
		// Handle connection errors
		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Connection error: %s', 'monikit-app-gdpr-user-data-deletion' ), $response->get_error_message() )
			);
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );
		
		// Check for successful response
		if ( $status_code === 200 ) {
			// Verify we got a valid JSON array
			if ( is_array( $response_data ) ) {
				return array(
					'success' => true,
					'message' => sprintf( __( 'Successfully connected to %s realm and retrieved user list (%d users found)', 'monikit-app-gdpr-user-data-deletion' ), $realm, count( $response_data ) )
				);
			} else {
				return array(
					'success' => false,
					'message' => __( 'Invalid response format from Keycloak server', 'monikit-app-gdpr-user-data-deletion' )
				);
			}
		} elseif ( $status_code === 401 ) {
			return array(
				'success' => false,
				'message' => __( 'Token is valid, but access to realm users is forbidden. Check Keycloak client permissions.', 'monikit-app-gdpr-user-data-deletion' )
			);
		} elseif ( $status_code === 403 ) {
			return array(
				'success' => false,
				'message' => __( 'Token is valid, but access to realm users is forbidden. Check Keycloak client permissions.', 'monikit-app-gdpr-user-data-deletion' )
			);
		} elseif ( $status_code === 404 ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Realm "%s" not found. Check the realm name.', 'monikit-app-gdpr-user-data-deletion' ), $realm )
			);
		} else {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Realm access failed with status code: %d', 'monikit-app-gdpr-user-data-deletion' ), $status_code )
			);
		}
	}

	/**
	 * Load default email templates via AJAX
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function load_default_templates() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'monikit_admin_nonce' ) ) {
			wp_die( __( 'Security check failed.', 'monikit-app-gdpr-user-data-deletion' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'monikit-app-gdpr-user-data-deletion' ) );
		}

		// Get default templates
		$default_templates = array(
			'email_subject_en' => __( 'Confirm Your Data Deletion Request', 'monikit-app-gdpr-user-data-deletion' ),
			'email_html_en' => $this->get_default_email_template_en(),
			'email_subject_de' => __( 'Bestätigen Sie Ihre Datenlöschungsanfrage', 'monikit-app-gdpr-user-data-deletion' ),
			'email_html_de' => $this->get_default_email_template_de(),
		);

		// Return the default templates
		wp_send_json_success( $default_templates );
	}

	/**
	 * Preview email template via AJAX
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function preview_email_template() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'monikit_admin_nonce' ) ) {
			wp_die( __( 'Security check failed.', 'monikit-app-gdpr-user-data-deletion' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'monikit-app-gdpr-user-data-deletion' ) );
		}

		$language = sanitize_text_field( $_POST['language'] );
		$subject = sanitize_text_field( $_POST['subject'] );
		$html = wp_kses_post( $_POST['html'] );

		if ( empty( $subject ) || empty( $html ) ) {
			wp_send_json_error( __( 'Please provide both subject and HTML content.', 'monikit-app-gdpr-user-data-deletion' ) );
		}

		// Replace placeholders with sample data
		$preview_html = str_replace(
			array( '{user_email}', '{confirmation_link}', '{confirmation_code}' ),
			array( 'user@example.com', '<a href="#">https://example.com/confirm/123456</a>', '123456' ),
			$html
		);

		wp_send_json_success( array(
			'subject' => $subject,
			'html' => $preview_html,
		) );
	}

	/**
	 * Translations page
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function translations_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'monikit-app-gdpr-user-data-deletion' ) );
		}

		$translations = $this->get_translatable_strings();
		$current_translations = $this->load_current_translations();
		
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<div class="notice notice-info">
				<p><?php _e( 'Edit public-facing translations for the plugin. Changes will be saved to language files in the languages folder.', 'monikit-app-gdpr-user-data-deletion' ); ?></p>
			</div>

			<form id="monikit-translations-form">
				<?php wp_nonce_field( 'monikit_translations_nonce', 'monikit_translations_nonce' ); ?>
				
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 30%;"><?php _e( 'String Key', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
							<th style="width: 35%;"><?php _e( 'English', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
							<th style="width: 35%;"><?php _e( 'German', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $translations as $key => $string ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $key ); ?></strong>
									<br>
									<small style="color: #666;"><?php echo esc_html( $string['context'] ); ?></small>
								</td>
								<td>
									<input type="text" 
										   name="translations[en][<?php echo esc_attr( $key ); ?>]" 
										   value="<?php echo esc_attr( isset( $current_translations['en'][ $key ] ) ? $current_translations['en'][ $key ] : $string['en'] ); ?>"
										   class="regular-text"
										   placeholder="<?php echo esc_attr( $string['en'] ); ?>">
								</td>
								<td>
									<input type="text" 
										   name="translations[de][<?php echo esc_attr( $key ); ?>]" 
										   value="<?php echo esc_attr( isset( $current_translations['de'][ $key ] ) ? $current_translations['de'][ $key ] : $string['de'] ); ?>"
										   class="regular-text"
										   placeholder="<?php echo esc_attr( $string['de'] ); ?>">
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary" id="save-translations" style="font-size: 16px; padding: 12px 24px; font-weight: 600;">
						<span class="dashicons dashicons-saved" style="margin-right: 8px;"></span>
						<?php _e( 'Save Translations', 'monikit-app-gdpr-user-data-deletion' ); ?>
					</button>
					<span class="spinner" style="float: none; margin-left: 10px;"></span>
					<p class="description" style="margin-top: 10px; color: #666;">
						<?php _e( 'Click to save all translation changes. The changes will be applied immediately to the frontend forms.', 'monikit-app-gdpr-user-data-deletion' ); ?>
					</p>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Get translatable strings
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	array	Array of translatable strings
	 */
	private function get_translatable_strings() {
		return array(
			'delete_account' => array(
				'en' => 'Delete Account',
				'de' => 'Konto löschen',
				'context' => 'Form title'
			),
			'request_deletion_subtitle' => array(
				'en' => 'Request deletion of your account. You will receive a confirmation email.',
				'de' => 'Beantragen Sie die Löschung Ihres Kontos. Sie erhalten eine Bestätigungs-E-Mail.',
				'context' => 'Form subtitle'
			),
			'email_address' => array(
				'en' => 'Email Address',
				'de' => 'E-Mail-Adresse',
				'context' => 'Form field label'
			),
			'request_deletion' => array(
				'en' => 'Request Deletion',
				'de' => 'Löschung beantragen',
				'context' => 'Button text'
			),
			'confirmation_code' => array(
				'en' => 'Confirmation Code',
				'de' => 'Bestätigungscode',
				'context' => 'Form field label'
			),
			'confirm_deletion' => array(
				'en' => 'Confirm Deletion',
				'de' => 'Löschung bestätigen',
				'context' => 'Button text'
			),
			'final_confirmation_title' => array(
				'en' => 'Final Confirmation',
				'de' => 'Endgültige Bestätigung',
				'context' => 'Final step title'
			),
			'final_confirmation_message' => array(
				'en' => 'This action cannot be undone. Your account and all associated data will be permanently deleted.',
				'de' => 'Diese Aktion kann nicht rückgängig gemacht werden. Ihr Konto und alle zugehörigen Daten werden dauerhaft gelöscht.',
				'context' => 'Final confirmation message'
			),
			'confirm_checkbox_text' => array(
				'en' => 'I understand that this action is irreversible and my account will be permanently deleted.',
				'de' => 'Ich verstehe, dass diese Aktion unwiderruflich ist und mein Konto permanent gelöscht wird.',
				'context' => 'Checkbox text'
			),
			'delete_my_account' => array(
				'en' => 'Delete My Account',
				'de' => 'Mein Konto löschen',
				'context' => 'Final button text'
			),
			'confirmation_sent' => array(
				'en' => 'Confirmation code sent to your email address.',
				'de' => 'Bestätigungscode an Ihre E-Mail-Adresse gesendet.',
				'context' => 'Success message'
			),
			'invalid_code' => array(
				'en' => 'Invalid or expired confirmation code.',
				'de' => 'Ungültiger oder abgelaufener Bestätigungscode.',
				'context' => 'Error message'
			),
			'account_deleted' => array(
				'en' => 'Your account has been successfully deleted.',
				'de' => 'Ihr Konto wurde erfolgreich gelöscht.',
				'context' => 'Success message'
			),
			'please_enter_email' => array(
				'en' => 'Please enter a valid email address.',
				'de' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
				'context' => 'Validation message'
			),
			'please_provide_code' => array(
				'en' => 'Please provide valid email and confirmation code.',
				'de' => 'Bitte geben Sie eine gültige E-Mail-Adresse und einen Bestätigungscode an.',
				'context' => 'Validation message'
			),
			'failed_send_email' => array(
				'en' => 'Failed to send confirmation email. Please try again.',
				'de' => 'Bestätigungs-E-Mail konnte nicht gesendet werden. Bitte versuchen Sie es erneut.',
				'context' => 'Error message'
			),
			'security_check_failed' => array(
				'en' => 'Security check failed.',
				'de' => 'Sicherheitsüberprüfung fehlgeschlagen.',
				'context' => 'Error message'
			),
			'invalid_confirmation_link' => array(
				'en' => 'Invalid confirmation link.',
				'de' => 'Ungültiger Bestätigungslink.',
				'context' => 'Error message'
			),
			'invalid_link_parameters' => array(
				'en' => 'Invalid confirmation link parameters.',
				'de' => 'Ungültige Bestätigungslink-Parameter.',
				'context' => 'Error message'
			),
			'account_deleted_title' => array(
				'en' => 'Account Deleted',
				'de' => 'Konto gelöscht',
				'context' => 'Page title'
			)
		);
	}

	/**
	 * Load current translations from language files
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	array	Current translations
	 */
	private function load_current_translations() {
		$translations = array(
			'en' => array(),
			'de' => array()
		);

		// Load English translations
		$en_file = MONIGPDR_PLUGIN_DIR . 'languages/monikit-app-gdpr-user-data-deletion-en_US.po';
		if ( file_exists( $en_file ) ) {
			$translations['en'] = $this->parse_po_file( $en_file );
		}

		// Load German translations
		$de_file = MONIGPDR_PLUGIN_DIR . 'languages/monikit-app-gdpr-user-data-deletion-de_DE.po';
		if ( file_exists( $de_file ) ) {
			$translations['de'] = $this->parse_po_file( $de_file );
		}

		return $translations;
	}

	/**
	 * Parse PO file
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$file_path	Path to PO file
	 * @return	array	Parsed translations
	 */
	private function parse_po_file( $file_path ) {
		$translations = array();
		
		if ( ! file_exists( $file_path ) ) {
			return $translations;
		}

		$content = file_get_contents( $file_path );
		$lines = explode( "\n", $content );
		
		$current_msgid = '';
		$current_msgstr = '';
		$in_msgstr = false;
		
		foreach ( $lines as $line ) {
			$line = trim( $line );
			
			if ( strpos( $line, 'msgid "' ) === 0 ) {
				// Save previous translation
				if ( ! empty( $current_msgid ) && ! empty( $current_msgstr ) ) {
					$translations[ $current_msgid ] = $current_msgstr;
				}
				
				$current_msgid = $this->extract_quoted_string( $line );
				$current_msgstr = '';
				$in_msgstr = false;
			} elseif ( strpos( $line, 'msgstr "' ) === 0 ) {
				$current_msgstr = $this->extract_quoted_string( $line );
				$in_msgstr = true;
			} elseif ( $in_msgstr && strpos( $line, '"' ) === 0 ) {
				$current_msgstr .= $this->extract_quoted_string( $line );
			}
		}
		
		// Save last translation
		if ( ! empty( $current_msgid ) && ! empty( $current_msgstr ) ) {
			$translations[ $current_msgid ] = $current_msgstr;
		}
		
		return $translations;
	}

	/**
	 * Extract quoted string from PO file line
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$line	PO file line
	 * @return	string	Extracted string
	 */
	private function extract_quoted_string( $line ) {
		$start = strpos( $line, '"' ) + 1;
		$end = strrpos( $line, '"' );
		
		if ( $start === false || $end === false || $start >= $end ) {
			return '';
		}
		
		return substr( $line, $start, $end - $start );
	}

	/**
	 * Save translations
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function save_translations() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'monikit_translations_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		$translations_json = isset( $_POST['translations'] ) ? $_POST['translations'] : '{}';
		$translations = json_decode( stripslashes( $translations_json ), true );
		
		if ( ! is_array( $translations ) ) {
			$translations = array();
		}
		
		// Ensure languages directory exists
		$languages_dir = MONIGPDR_PLUGIN_DIR . 'languages/';
		if ( ! is_dir( $languages_dir ) ) {
			wp_mkdir_p( $languages_dir );
		}

		$success_count = 0;
		$errors = array();

		// Save English translations
		if ( isset( $translations['en'] ) ) {
			$en_file = $languages_dir . 'monikit-app-gdpr-user-data-deletion-en_US.po';
			$result = $this->write_po_file( $en_file, $translations['en'], 'en_US' );
			if ( $result ) {
				$success_count++;
			} else {
				$errors[] = 'English translations';
			}
		}

		// Save German translations
		if ( isset( $translations['de'] ) ) {
			$de_file = $languages_dir . 'monikit-app-gdpr-user-data-deletion-de_DE.po';
			$result = $this->write_po_file( $de_file, $translations['de'], 'de_DE' );
			if ( $result ) {
				$success_count++;
			} else {
				$errors[] = 'German translations';
			}
		}

		if ( $success_count > 0 ) {
			wp_send_json_success( array(
				'message' => sprintf( 
					__( 'Translations saved successfully. %d language(s) updated.', 'monikit-app-gdpr-user-data-deletion' ),
					$success_count
				)
			));
		} else {
			wp_send_json_error( array(
				'message' => __( 'Failed to save translations. Please check file permissions.', 'monikit-app-gdpr-user-data-deletion' )
			));
		}
	}

	/**
	 * Write PO file
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$file_path	Path to PO file
	 * @param	array	$translations	Translations array
	 * @param	string	$locale	Locale code
	 * @return	bool	Success status
	 */
	private function write_po_file( $file_path, $translations, $locale ) {
		$content = "# Translation file for Monikit GDPR User Data Deletion\n";
		$content .= "# Language: " . ( $locale === 'en_US' ? 'English' : 'German' ) . "\n";
		$content .= "# Generated: " . date( 'Y-m-d H:i:s' ) . "\n\n";

		foreach ( $translations as $msgid => $msgstr ) {
			if ( ! empty( $msgstr ) ) {
				$content .= 'msgid "' . $this->escape_po_string( $msgid ) . '"' . "\n";
				$content .= 'msgstr "' . $this->escape_po_string( $msgstr ) . '"' . "\n\n";
			}
		}

		return file_put_contents( $file_path, $content ) !== false;
	}

	/**
	 * Escape string for PO file
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$string	String to escape
	 * @return	string	Escaped string
	 */
	private function escape_po_string( $string ) {
		return str_replace( array( '"', '\\' ), array( '\\"', '\\\\' ), $string );
	}

	/**
	 * Logs page
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function logs_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'monikit-app-gdpr-user-data-deletion' ) );
		}

		// Get filter parameters
		$page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$email_filter = isset( $_GET['email'] ) ? sanitize_text_field( $_GET['email'] ) : '';
		$action_filter = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
		$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';

		// Get logs
		$args = array(
			'page' => $page,
			'per_page' => 20,
			'email' => $email_filter,
			'action' => $action_filter,
			'status' => $status_filter,
			'date_from' => $date_from,
			'date_to' => $date_to,
			'orderby' => 'created_at',
			'order' => 'DESC'
		);

		$logs_data = MONIGPDR()->logs->get_logs( $args );
		$logs = $logs_data['logs'];
		$total_items = $logs_data['total_items'];
		$total_pages = $logs_data['total_pages'];
		$current_page = $logs_data['current_page'];

		// Get statistics
		$stats = MONIGPDR()->logs->get_statistics( 'month' );

		// Get labels
		$action_labels = MONIGPDR()->logs->get_action_labels();
		$status_labels = MONIGPDR()->logs->get_status_labels();
		$status_colors = MONIGPDR()->logs->get_status_colors();

		?>
		<div class="wrap">
			<input type="hidden" id="monikit_logs_nonce" value="<?php echo wp_create_nonce( 'monikit_logs_nonce' ); ?>">
			<h1><?php echo esc_html__( 'Deletion Logs', 'monikit-app-gdpr-user-data-deletion' ); ?></h1>
			
			<!-- Statistics Cards -->
			<div class="monikit-stats-cards">
				<div class="monikit-stat-card">
					<h3><?php echo esc_html__( 'Total Requests', 'monikit-app-gdpr-user-data-deletion' ); ?></h3>
					<div class="stat-number"><?php echo esc_html( $stats['totals']->total ?? 0 ); ?></div>
				</div>
				<div class="monikit-stat-card">
					<h3><?php echo esc_html__( 'Successful', 'monikit-app-gdpr-user-data-deletion' ); ?></h3>
					<div class="stat-number success"><?php echo esc_html( $stats['totals']->successful ?? 0 ); ?></div>
				</div>
				<div class="monikit-stat-card">
					<h3><?php echo esc_html__( 'Failed', 'monikit-app-gdpr-user-data-deletion' ); ?></h3>
					<div class="stat-number failed"><?php echo esc_html( $stats['totals']->failed ?? 0 ); ?></div>
				</div>
				<div class="monikit-stat-card">
					<h3><?php echo esc_html__( 'Pending', 'monikit-app-gdpr-user-data-deletion' ); ?></h3>
					<div class="stat-number pending"><?php echo esc_html( $stats['totals']->pending ?? 0 ); ?></div>
				</div>
				<div class="monikit-stat-card">
					<h3><?php echo esc_html__( 'Completed', 'monikit-app-gdpr-user-data-deletion' ); ?></h3>
					<div class="stat-number completed"><?php echo esc_html( $stats['totals']->completed ?? 0 ); ?></div>
				</div>
			</div>

			<!-- Filters -->
			<div class="monikit-logs-filters">
				<form method="get" action="">
					<input type="hidden" name="page" value="monikit_logs">
					
					<div class="filter-row">
						<div class="filter-group">
							<label for="email"><?php echo esc_html__( 'Email:', 'monikit-app-gdpr-user-data-deletion' ); ?></label>
							<input type="text" id="email" name="email" value="<?php echo esc_attr( $email_filter ); ?>" placeholder="<?php echo esc_attr__( 'Filter by email', 'monikit-app-gdpr-user-data-deletion' ); ?>">
						</div>
						
						<div class="filter-group">
							<label for="action"><?php echo esc_html__( 'Action:', 'monikit-app-gdpr-user-data-deletion' ); ?></label>
							<select id="action" name="action">
								<option value=""><?php echo esc_html__( 'All Actions', 'monikit-app-gdpr-user-data-deletion' ); ?></option>
								<?php foreach ( $action_labels as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $action_filter, $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						
						<div class="filter-group">
							<label for="status"><?php echo esc_html__( 'Status:', 'monikit-app-gdpr-user-data-deletion' ); ?></label>
							<select id="status" name="status">
								<option value=""><?php echo esc_html__( 'All Statuses', 'monikit-app-gdpr-user-data-deletion' ); ?></option>
								<?php foreach ( $status_labels as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status_filter, $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						
						<div class="filter-group">
							<label for="date_from"><?php echo esc_html__( 'From:', 'monikit-app-gdpr-user-data-deletion' ); ?></label>
							<input type="date" id="date_from" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
						</div>
						
						<div class="filter-group">
							<label for="date_to"><?php echo esc_html__( 'To:', 'monikit-app-gdpr-user-data-deletion' ); ?></label>
							<input type="date" id="date_to" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
						</div>
						
						<div class="filter-actions">
							<button type="submit" class="button button-primary"><?php echo esc_html__( 'Filter', 'monikit-app-gdpr-user-data-deletion' ); ?></button>
							<a href="?page=monikit_logs" class="button"><?php echo esc_html__( 'Clear', 'monikit-app-gdpr-user-data-deletion' ); ?></a>
						</div>
					</div>
				</form>
			</div>

			<!-- Actions -->
			<div class="monikit-logs-actions">
				<button type="button" class="button" id="export-logs"><?php echo esc_html__( 'Export CSV', 'monikit-app-gdpr-user-data-deletion' ); ?></button>
				
				<div class="bulk-actions-section">
					<h3><?php echo esc_html__( 'Bulk Actions', 'monikit-app-gdpr-user-data-deletion' ); ?></h3>
					<div class="bulk-actions">
						<button type="button" class="button" id="select-all-logs"><?php echo esc_html__( 'Select All', 'monikit-app-gdpr-user-data-deletion' ); ?></button>
						<button type="button" class="button" id="deselect-all-logs"><?php echo esc_html__( 'Deselect All', 'monikit-app-gdpr-user-data-deletion' ); ?></button>
						<button type="button" class="button button-danger" id="delete-selected-logs" disabled><?php echo esc_html__( 'Delete Selected', 'monikit-app-gdpr-user-data-deletion' ); ?></button>
						<span class="selected-count"><?php echo esc_html__( '0 selected', 'monikit-app-gdpr-user-data-deletion' ); ?></span>
					</div>
					<div class="bulk-info">
						<p><?php echo esc_html__( 'Select individual log entries to delete them. This action cannot be undone.', 'monikit-app-gdpr-user-data-deletion' ); ?></p>
					</div>
				</div>
				
				<div class="cleanup-section">
					<h3><?php echo esc_html__( 'Cleanup Old Logs', 'monikit-app-gdpr-user-data-deletion' ); ?></h3>
					<div class="cleanup-options">
						<label for="retention-period"><?php echo esc_html__( 'Delete logs older than:', 'monikit-app-gdpr-user-data-deletion' ); ?></label>
						<select id="retention-period">
							<option value="30"><?php echo esc_html__( '30 days', 'monikit-app-gdpr-user-data-deletion' ); ?></option>
							<option value="90"><?php echo esc_html__( '3 months', 'monikit-app-gdpr-user-data-deletion' ); ?></option>
							<option value="180"><?php echo esc_html__( '6 months', 'monikit-app-gdpr-user-data-deletion' ); ?></option>
							<option value="365" selected><?php echo esc_html__( '1 year', 'monikit-app-gdpr-user-data-deletion' ); ?></option>
							<option value="730"><?php echo esc_html__( '2 years', 'monikit-app-gdpr-user-data-deletion' ); ?></option>
							<option value="1095"><?php echo esc_html__( '3 years', 'monikit-app-gdpr-user-data-deletion' ); ?></option>
						</select>
						<button type="button" class="button button-primary" id="cleanup-logs"><?php echo esc_html__( 'Cleanup', 'monikit-app-gdpr-user-data-deletion' ); ?></button>
					</div>
					<div class="cleanup-info">
						<p><?php echo esc_html__( 'This will permanently delete logs older than the selected period. This action cannot be undone.', 'monikit-app-gdpr-user-data-deletion' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Logs Table -->
			<div class="monikit-logs-table-wrapper">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th class="check-column">
								<input type="checkbox" id="select-all-checkbox">
							</th>
							<th><?php echo esc_html__( 'ID', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
							<th><?php echo esc_html__( 'Email', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
							<th><?php echo esc_html__( 'Action', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
							<th><?php echo esc_html__( 'Status', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
							<th><?php echo esc_html__( 'Message', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
							<th><?php echo esc_html__( 'IP Address', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
							<th><?php echo esc_html__( 'Date', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
							<th><?php echo esc_html__( 'Actions', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $logs ) ) : ?>
							<tr>
								<td colspan="9"><?php echo esc_html__( 'No logs found.', 'monikit-app-gdpr-user-data-deletion' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $logs as $log ) : ?>
								<tr>
									<td class="check-column">
										<input type="checkbox" class="log-checkbox" value="<?php echo esc_attr( $log->id ); ?>">
									</td>
									<td><?php echo esc_html( $log->id ); ?></td>
									<td><?php echo esc_html( $log->email ); ?></td>
									<td><?php echo esc_html( $action_labels[ $log->action ] ?? $log->action ); ?></td>
									<td>
										<span class="status-badge status-<?php echo esc_attr( $log->status ); ?>" style="background-color: <?php echo esc_attr( $status_colors[ $log->status ] ?? '#666' ); ?>">
											<?php echo esc_html( $status_labels[ $log->status ] ?? $log->status ); ?>
										</span>
									</td>
									<td><?php echo esc_html( wp_trim_words( $log->message, 10 ) ); ?></td>
									<td><?php echo esc_html( $log->ip_address ); ?></td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->created_at ) ) ); ?></td>
									<td>
										<button type="button" class="button button-small view-log-details" data-log-id="<?php echo esc_attr( $log->id ); ?>">
											<?php echo esc_html__( 'View', 'monikit-app-gdpr-user-data-deletion' ); ?>
										</button>
										<button type="button" class="button button-small button-danger delete-single-log" data-log-id="<?php echo esc_attr( $log->id ); ?>" title="<?php echo esc_attr__( 'Delete this log entry', 'monikit-app-gdpr-user-data-deletion' ); ?>">
											<?php echo esc_html__( 'Delete', 'monikit-app-gdpr-user-data-deletion' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php
						echo paginate_links( array(
							'base' => add_query_arg( 'paged', '%#%' ),
							'format' => '',
							'prev_text' => __( '&laquo;' ),
							'next_text' => __( '&raquo;' ),
							'total' => $total_pages,
							'current' => $current_page,
							'add_args' => array(
								'email' => $email_filter,
								'action' => $action_filter,
								'status' => $status_filter,
								'date_from' => $date_from,
								'date_to' => $date_to
							)
						) );
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- Log Details Modal -->
		<div id="log-details-modal" class="monikit-modal" style="display: none;">
			<div class="monikit-modal-content">
				<div class="monikit-modal-header">
					<h2><?php echo esc_html__( 'Log Details', 'monikit-app-gdpr-user-data-deletion' ); ?></h2>
					<span class="monikit-modal-close">&times;</span>
				</div>
				<div class="monikit-modal-body">
					<div id="log-details-content"></div>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Export logs
			$('#export-logs').on('click', function() {
				var filters = {
					email: '<?php echo esc_js( $email_filter ); ?>',
					action: '<?php echo esc_js( $action_filter ); ?>',
					status: '<?php echo esc_js( $status_filter ); ?>',
					date_from: '<?php echo esc_js( $date_from ); ?>',
					date_to: '<?php echo esc_js( $date_to ); ?>'
				};
				
				$.post(ajaxurl, {
					action: 'monikit_export_logs',
					nonce: '<?php echo wp_create_nonce( 'monikit_logs_nonce' ); ?>',
					filters: filters
				}, function(response) {
					if (response.success) {
						// Create download link
						var blob = new Blob([response.data.csv], {type: 'text/csv'});
						var url = window.URL.createObjectURL(blob);
						var a = document.createElement('a');
						a.href = url;
						a.download = 'monikit-deletion-logs-' + new Date().toISOString().split('T')[0] + '.csv';
						document.body.appendChild(a);
						a.click();
						document.body.removeChild(a);
						window.URL.revokeObjectURL(url);
					} else {
						alert('<?php echo esc_js( __( 'Export failed.', 'monikit-app-gdpr-user-data-deletion' ) ); ?>');
					}
				});
			});

			// Cleanup logs
			$('#cleanup-logs').on('click', function() {
				if (confirm('<?php echo esc_js( __( 'This will delete logs older than 1 year. Continue?', 'monikit-app-gdpr-user-data-deletion' ) ); ?>')) {
					$.post(ajaxurl, {
						action: 'monikit_cleanup_logs',
						nonce: '<?php echo wp_create_nonce( 'monikit_logs_nonce' ); ?>'
					}, function(response) {
						if (response.success) {
							alert('<?php echo esc_js( __( 'Cleanup completed.', 'monikit-app-gdpr-user-data-deletion' ) ); ?>');
							location.reload();
						} else {
							alert('<?php echo esc_js( __( 'Cleanup failed.', 'monikit-app-gdpr-user-data-deletion' ) ); ?>');
						}
					});
				}
			});

			// View log details
			$('.view-log-details').on('click', function() {
				var logId = $(this).data('log-id');
				$('#log-details-content').html('<p><?php echo esc_js( __( 'Loading...', 'monikit-app-gdpr-user-data-deletion' ) ); ?></p>');
				$('#log-details-modal').show();
				
				// Load log details via AJAX
				$.post(ajaxurl, {
					action: 'monikit_get_log_details',
					nonce: '<?php echo wp_create_nonce( 'monikit_logs_nonce' ); ?>',
					log_id: logId
				}, function(response) {
					if (response.success) {
						$('#log-details-content').html(response.data.html);
					} else {
						$('#log-details-content').html('<p><?php echo esc_js( __( 'Failed to load log details.', 'monikit-app-gdpr-user-data-deletion' ) ); ?></p>');
					}
				});
			});

			// Close modal
			$('.monikit-modal-close').on('click', function() {
				$('#log-details-modal').hide();
			});

			$(window).on('click', function(e) {
				if ($(e.target).hasClass('monikit-modal')) {
					$('#log-details-modal').hide();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Export logs AJAX handler
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function export_logs() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'monikit_logs_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		$filters = isset( $_POST['filters'] ) ? $_POST['filters'] : array();
		
		$csv = MONIGPDR()->logs->export_csv( $filters );
		
		wp_send_json_success( array( 'csv' => $csv ) );
	}

	/**
	 * Cleanup logs AJAX handler
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function cleanup_logs() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'monikit_logs_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Get retention period from request
		$retention_days = isset( $_POST['retention_days'] ) ? intval( $_POST['retention_days'] ) : 365;
		
		// Validate retention period
		if ( $retention_days < 1 || $retention_days > 3650 ) { // Max 10 years
			wp_send_json_error( array( 'message' => __( 'Invalid retention period. Please select a period between 1 day and 10 years.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Get count of logs that will be deleted
		$logs_to_delete = MONIGPDR()->logs->get_logs_count_older_than( $retention_days );
		
		// Perform cleanup
		$deleted = MONIGPDR()->logs->cleanup_old_logs( $retention_days );
		
		// Get human-readable period
		$period_text = $this->get_human_readable_period( $retention_days );
		
		wp_send_json_success( array( 
			'message' => sprintf( __( 'Deleted %d log entries older than %s.', 'monikit-app-gdpr-user-data-deletion' ), $deleted, $period_text ),
			'deleted_count' => $deleted,
			'logs_to_delete' => $logs_to_delete,
			'retention_days' => $retention_days
		) );
	}

	/**
	 * Get human-readable period text
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	int	$days	Number of days
	 * @return	string	Human-readable period
	 */
	private function get_human_readable_period( $days ) {
		if ( $days == 1 ) {
			return __( '1 day', 'monikit-app-gdpr-user-data-deletion' );
		} elseif ( $days < 30 ) {
			return sprintf( __( '%d days', 'monikit-app-gdpr-user-data-deletion' ), $days );
		} elseif ( $days < 365 ) {
			$months = round( $days / 30 );
			return sprintf( __( '%d month(s)', 'monikit-app-gdpr-user-data-deletion' ), $months );
		} else {
			$years = round( $days / 365, 1 );
			return sprintf( __( '%.1f year(s)', 'monikit-app-gdpr-user-data-deletion' ), $years );
		}
	}

	/**
	 * Get log details AJAX handler
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function get_log_details() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'monikit_logs_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		$log_id = intval( $_POST['log_id'] );
		$log = MONIGPDR()->logs->get_log( $log_id );
		
		if ( ! $log ) {
			wp_send_json_error( array( 'message' => __( 'Log not found.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		$action_labels = MONIGPDR()->logs->get_action_labels();
		$status_labels = MONIGPDR()->logs->get_status_labels();
		$status_colors = MONIGPDR()->logs->get_status_colors();

		// Parse JSON data
		$request_data = ! empty( $log->request_data ) ? json_decode( $log->request_data, true ) : null;
		$response_data = ! empty( $log->response_data ) ? json_decode( $log->response_data, true ) : null;

		ob_start();
		?>
		<div class="log-details">
			<table class="widefat">
				<tr>
					<th><?php echo esc_html__( 'ID', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
					<td><?php echo esc_html( $log->id ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Email', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
					<td><?php echo esc_html( $log->email ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Action', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
					<td><?php echo esc_html( $action_labels[ $log->action ] ?? $log->action ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Status', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
					<td>
						<span class="status-badge status-<?php echo esc_attr( $log->status ); ?>" style="background-color: <?php echo esc_attr( $status_colors[ $log->status ] ?? '#666' ); ?>">
							<?php echo esc_html( $status_labels[ $log->status ] ?? $log->status ); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Message', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
					<td><?php echo esc_html( $log->message ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'IP Address', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
					<td><?php echo esc_html( $log->ip_address ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'User Agent', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
					<td><?php echo esc_html( $log->user_agent ); ?></td>
				</tr>
				<?php if ( ! empty( $log->keycloak_user_id ) ) : ?>
				<tr>
					<th><?php echo esc_html__( 'Keycloak User ID', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
					<td><?php echo esc_html( $log->keycloak_user_id ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( ! empty( $log->keycloak_realm ) ) : ?>
				<tr>
					<th><?php echo esc_html__( 'Keycloak Realm', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
					<td><?php echo esc_html( $log->keycloak_realm ); ?></td>
				</tr>
				<?php endif; ?>
				<tr>
					<th><?php echo esc_html__( 'Created At', 'monikit-app-gdpr-user-data-deletion' ); ?></th>
					<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->created_at ) ) ); ?></td>
				</tr>
			</table>

			<?php if ( $request_data ) : ?>
			<h3><?php echo esc_html__( 'Request Data', 'monikit-app-gdpr-user-data-deletion' ); ?></h3>
			<pre><?php echo esc_html( json_encode( $request_data, JSON_PRETTY_PRINT ) ); ?></pre>
			<?php endif; ?>

			<?php if ( $response_data ) : ?>
			<h3><?php echo esc_html__( 'Response Data', 'monikit-app-gdpr-user-data-deletion' ); ?></h3>
			<pre><?php echo esc_html( json_encode( $response_data, JSON_PRETTY_PRINT ) ); ?></pre>
			<?php endif; ?>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Delete selected logs AJAX handler
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function delete_selected_logs() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'monikit_logs_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Get selected log IDs
		$log_ids = isset( $_POST['log_ids'] ) ? $_POST['log_ids'] : array();
		
		if ( empty( $log_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No logs selected for deletion.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Validate log IDs
		$log_ids = array_map( 'intval', $log_ids );
		$log_ids = array_filter( $log_ids );

		if ( empty( $log_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid log IDs provided.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Delete the logs
		$deleted = MONIGPDR()->logs->delete_logs_by_ids( $log_ids );
		
		wp_send_json_success( array( 
			'message' => sprintf( __( 'Successfully deleted %d log entries.', 'monikit-app-gdpr-user-data-deletion' ), $deleted ),
			'deleted_count' => $deleted,
			'log_ids' => $log_ids
		) );
	}

	/**
	 * Delete single log AJAX handler
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function delete_single_log() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'monikit_logs_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Get log ID
		$log_id = isset( $_POST['log_id'] ) ? intval( $_POST['log_id'] ) : 0;
		
		if ( ! $log_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid log ID provided.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}

		// Delete the log
		$deleted = MONIGPDR()->logs->delete_log_by_id( $log_id );
		
		if ( $deleted ) {
			wp_send_json_success( array( 
				'message' => __( 'Log entry deleted successfully.', 'monikit-app-gdpr-user-data-deletion' ),
				'log_id' => $log_id
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete log entry.', 'monikit-app-gdpr-user-data-deletion' ) ) );
		}
	}
} 