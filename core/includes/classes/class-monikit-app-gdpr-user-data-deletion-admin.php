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
		
		$current_settings = get_option( $this->option_name, array() );
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
			'keycloak_base_url' => __( 'Example: https://testserver.monikit.com/auth/', 'monikit-app-gdpr-user-data-deletion' ),
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
	}

	public function email_templates_section_callback() {
		echo '<p>' . esc_html__( 'Configure email templates for user notifications. Available placeholders: {user_email}, {confirmation_link}, {confirmation_code}', 'monikit-app-gdpr-user-data-deletion' ) . '</p>';
	}

	public function language_section_callback() {
		echo '<p>' . esc_html__( 'Configure language settings for the plugin.', 'monikit-app-gdpr-user-data-deletion' ) . '</p>';
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
				$sanitized_input[ $field ] = sanitize_text_field( $input[ $field ] );
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
					
					<button type="button" class="button test-keycloak-connection" style="margin-left: 10px;">
						<?php esc_html_e( 'Test Keycloak Connection', 'monikit-app-gdpr-user-data-deletion' ); ?>
					</button>
					
					<button type="button" class="button preview-email-en" style="margin-left: 10px;">
						<?php esc_html_e( 'Preview English Email', 'monikit-app-gdpr-user-data-deletion' ); ?>
					</button>
					
					<button type="button" class="button preview-email-de" style="margin-left: 10px;">
						<?php esc_html_e( 'Preview German Email', 'monikit-app-gdpr-user-data-deletion' ); ?>
					</button>
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
		if ( 'toplevel_page_monikit_settings' !== $hook ) {
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
		
		// Extract Keycloak settings
		$base_url = sanitize_text_field( $settings['monikit_settings[keycloak_base_url]'] );
		$realm = sanitize_text_field( $settings['monikit_settings[keycloak_realm]'] );
		$client_id = sanitize_text_field( $settings['monikit_settings[keycloak_client_id]'] );
		$client_secret = sanitize_text_field( $settings['monikit_settings[keycloak_client_secret]'] );
		$admin_username = sanitize_text_field( $settings['monikit_settings[keycloak_admin_username]'] );
		$admin_password = sanitize_text_field( $settings['monikit_settings[keycloak_admin_password]'] );

		// Basic validation
		if ( empty( $base_url ) || empty( $realm ) || empty( $client_id ) ) {
			wp_send_json_error( __( 'Please fill in all required Keycloak fields.', 'monikit-app-gdpr-user-data-deletion' ) );
		}

		// Test connection
		$test_url = trailingslashit( $base_url ) . 'realms/' . $realm . '/.well-known/openid_configuration';
		
		$response = wp_remote_get( $test_url, array(
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'Monikit-Plugin/1.0',
			),
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		
		if ( $status_code === 200 ) {
			wp_send_json_success( __( 'Keycloak connection successful!', 'monikit-app-gdpr-user-data-deletion' ) );
		} else {
			wp_send_json_error( sprintf( __( 'Connection failed with status code: %d', 'monikit-app-gdpr-user-data-deletion' ), $status_code ) );
		}
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
} 