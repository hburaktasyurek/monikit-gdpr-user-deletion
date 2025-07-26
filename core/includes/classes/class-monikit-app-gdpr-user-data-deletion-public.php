<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Monikit_App_Gdpr_User_Data_Deletion_Public
 *
 * Handles the public user deletion request page functionality
 *
 * @package		MONIGPDR
 * @subpackage	Classes/Monikit_App_Gdpr_User_Data_Deletion_Public
 * @author		Hasan Burak TASYUREK
 * @since		1.0.0
 */
class Monikit_App_Gdpr_User_Data_Deletion_Public {

	/**
	 * Our Monikit_App_Gdpr_User_Data_Deletion_Public constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct(){
		// Initialize hooks after plugin is loaded to ensure settings are available
		add_action( 'MONIGPDR/plugin_loaded', array( $this, 'init_hooks' ) );
		
		// Load text domain for translations
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Initialize hooks after plugin is loaded
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function init_hooks() {
		$this->add_hooks();
		
		// Add shortcode for embedding the deletion form
		add_shortcode( 'monigpdr_deletion_form', array( $this, 'deletion_form_shortcode' ) );
	}

	/**
	 * Load text domain for translations
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'monikit-app-gdpr-user-data-deletion',
			false,
			dirname( plugin_basename( MONIGPDR_PLUGIN_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Get translated string
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$key	String key
	 * @param	string	$default	Default value
	 * @return	string	Translated string
	 */
	private function get_translated_string( $key, $default = '' ) {
		$current_lang = $this->get_current_language();
		
		// Use hardcoded translations for now to ensure consistency
		$translations = $this->get_translation_strings();
		
		if ( isset( $translations[ $key ][ $current_lang ] ) ) {
			return $translations[ $key ][ $current_lang ];
		}
		
		return $default;
	}

	/**
	 * Get translation strings
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	array	Translation strings
	 */
	private function get_translation_strings() {
		return array(
			'delete_account' => array(
				'en' => 'Delete Account',
				'de' => 'Konto löschen'
			),
			'request_deletion_subtitle' => array(
				'en' => 'Request deletion of your account. You will receive a confirmation email.',
				'de' => 'Beantragen Sie die Löschung Ihres Kontos. Sie erhalten eine Bestätigungs-E-Mail.'
			),
			'email_address' => array(
				'en' => 'Email Address',
				'de' => 'E-Mail-Adresse'
			),
			'request_deletion' => array(
				'en' => 'Request Deletion',
				'de' => 'Löschung beantragen'
			),
			'confirmation_code' => array(
				'en' => 'Confirmation Code',
				'de' => 'Bestätigungscode'
			),
			'confirm_deletion' => array(
				'en' => 'Confirm Deletion',
				'de' => 'Löschung bestätigen'
			),
			'final_confirmation_title' => array(
				'en' => 'Final Confirmation',
				'de' => 'Endgültige Bestätigung'
			),
			'final_confirmation_message' => array(
				'en' => 'This action cannot be undone. Your account and all associated data will be permanently deleted.',
				'de' => 'Diese Aktion kann nicht rückgängig gemacht werden. Ihr Konto und alle zugehörigen Daten werden dauerhaft gelöscht.'
			),
			'confirm_checkbox_text' => array(
				'en' => 'I understand that this action is irreversible and my account will be permanently deleted.',
				'de' => 'Ich verstehe, dass diese Aktion unwiderruflich ist und mein Konto permanent gelöscht wird.'
			),
			'delete_my_account' => array(
				'en' => 'Delete My Account',
				'de' => 'Mein Konto löschen'
			),
			'confirmation_sent' => array(
				'en' => 'Confirmation code sent to your email address.',
				'de' => 'Bestätigungscode an Ihre E-Mail-Adresse gesendet.'
			),
			'invalid_code' => array(
				'en' => 'Invalid or expired confirmation code.',
				'de' => 'Ungültiger oder abgelaufener Bestätigungscode.'
			),
			'account_deleted' => array(
				'en' => 'Your account has been successfully deleted.',
				'de' => 'Ihr Konto wurde erfolgreich gelöscht.'
			),
			'please_enter_email' => array(
				'en' => 'Please enter a valid email address.',
				'de' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.'
			),
			'please_provide_code' => array(
				'en' => 'Please provide valid email and confirmation code.',
				'de' => 'Bitte geben Sie eine gültige E-Mail-Adresse und einen Bestätigungscode an.'
			),
			'failed_send_email' => array(
				'en' => 'Failed to send confirmation email. Please try again.',
				'de' => 'Bestätigungs-E-Mail konnte nicht gesendet werden. Bitte versuchen Sie es erneut.'
			),
			'security_check_failed' => array(
				'en' => 'Security check failed.',
				'de' => 'Sicherheitsüberprüfung fehlgeschlagen.'
			),
			'invalid_confirmation_link' => array(
				'en' => 'Invalid confirmation link.',
				'de' => 'Ungültiger Bestätigungslink.'
			),
			'invalid_link_parameters' => array(
				'en' => 'Invalid confirmation link parameters.',
				'de' => 'Ungültige Bestätigungslink-Parameter.'
			),
			'account_deleted_title' => array(
				'en' => 'Account Deleted',
				'de' => 'Konto gelöscht'
			)
		);
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOKS
	 * ###
	 * ######################
	 */

	/**
	 * Registers all WordPress and plugin related hooks
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	void
	 */
	private function add_hooks(){
		// Only add hooks if public deletion is enabled
		if ( $this->is_public_deletion_enabled() ) {
			// Enqueue frontend scripts and styles
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts_and_styles' ) );
			
			// AJAX handlers
			add_action( 'wp_ajax_nopriv_monigpdr_request_deletion', array( $this, 'handle_deletion_request' ) );
			add_action( 'wp_ajax_monigpdr_request_deletion', array( $this, 'handle_deletion_request' ) );
			
					add_action( 'wp_ajax_nopriv_monigpdr_confirm_deletion', array( $this, 'handle_deletion_confirmation' ) );
		add_action( 'wp_ajax_monigpdr_confirm_deletion', array( $this, 'handle_deletion_confirmation' ) );
		
		// Handle direct confirmation links from email
		add_action( 'init', array( $this, 'handle_direct_confirmation_link' ) );
		}
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOK CALLBACKS
	 * ###
	 * ######################
	 */

	/**
	 * Enqueue frontend scripts and styles
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function enqueue_frontend_scripts_and_styles() {
		// Always enqueue since shortcode can be used anywhere
		wp_enqueue_style( 'monigpdr-public-styles', MONIGPDR_PLUGIN_URL . 'core/includes/assets/css/public-styles.css', array(), MONIGPDR_VERSION, 'all' );
		wp_enqueue_script( 'jquery' ); // Ensure jQuery is loaded
		wp_enqueue_script( 'monigpdr-public-scripts', MONIGPDR_PLUGIN_URL . 'core/includes/assets/js/public-scripts.js', array( 'jquery' ), MONIGPDR_VERSION, true );
		
		wp_localize_script( 'monigpdr-public-scripts', 'monigpdr_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'monigpdr_public_nonce' ),
			'strings' => array(
				'request_sent' => __( 'Request sent successfully. Please check your email for confirmation.', 'monikit-app-gdpr-user-data-deletion' ),
				'error_occurred' => __( 'An error occurred. Please try again.', 'monikit-app-gdpr-user-data-deletion' ),
				'account_deleted' => __( 'Your account has been successfully deleted.', 'monikit-app-gdpr-user-data-deletion' ),
				'invalid_code' => __( 'Invalid confirmation code. Please try again.', 'monikit-app-gdpr-user-data-deletion' ),
				'email_required' => __( 'Email address is required.', 'monikit-app-gdpr-user-data-deletion' ),
				'code_required' => __( 'Confirmation code is required.', 'monikit-app-gdpr-user-data-deletion' ),
			)
		));
	}

	/**
	 * Handle deletion request AJAX
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function handle_deletion_request() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'monigpdr_public_nonce' ) ) {
			wp_die( __( 'Security check failed.', 'monikit-app-gdpr-user-data-deletion' ) );
		}

		$email = sanitize_email( $_POST['email'] );
		
		if ( ! is_email( $email ) ) {
			// Log failed request
			MONIGPDR()->logs->log_action( $email, 'request', 'failed', __( 'Invalid email address provided.', 'monikit-app-gdpr-user-data-deletion' ), array(
				'request' => $_POST
			) );
			
			wp_send_json_error( array(
				'message' => __( 'Please enter a valid email address.', 'monikit-app-gdpr-user-data-deletion' )
			));
		}

		// Check if public deletion is enabled
		if ( ! $this->is_public_deletion_enabled() ) {
			// Log failed request
			MONIGPDR()->logs->log_action( $email, 'request', 'failed', __( 'Public deletion not enabled.', 'monikit-app-gdpr-user-data-deletion' ), array(
				'request' => $_POST
			) );
			
			wp_send_json_error( array(
				'message' => __( 'Account deletion is not available.', 'monikit-app-gdpr-user-data-deletion' )
			));
		}

		// Generate confirmation code
		$confirmation_code = $this->generate_confirmation_code();
		
		// Store the code temporarily (expires in 1 hour)
		$expiry = time() + ( 60 * 60 ); // 1 hour
		$code_data = array(
			'email' => $email,
			'code' => $confirmation_code,
			'expires' => $expiry,
			'used' => false
		);
		
		set_transient( 'monigpdr_deletion_code_' . md5( $email ), $code_data, 60 * 60 );

		// Send confirmation email
		$email_sent = $this->send_confirmation_email( $email, $confirmation_code );
		
		if ( $email_sent ) {
			// Log successful request and email sending
			MONIGPDR()->logs->log_action( $email, 'request', 'pending', __( 'Deletion request submitted and confirmation email sent. Awaiting user confirmation.', 'monikit-app-gdpr-user-data-deletion' ), array(
				'request' => $_POST
			) );
			
			wp_send_json_success( array(
				'message' => __( 'Confirmation code sent to your email address.', 'monikit-app-gdpr-user-data-deletion' ),
				'show_code_input' => true
			));
		} else {
			// Log failed email sending
			MONIGPDR()->logs->log_action( $email, 'request', 'failed', __( 'Failed to send confirmation email.', 'monikit-app-gdpr-user-data-deletion' ), array(
				'request' => $_POST
			) );
			
			wp_send_json_error( array(
				'message' => __( 'Failed to send confirmation email. Please try again.', 'monikit-app-gdpr-user-data-deletion' )
			));
		}
	}

	/**
	 * Handle deletion confirmation AJAX
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function handle_deletion_confirmation() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'monigpdr_public_nonce' ) ) {
			wp_die( __( 'Security check failed.', 'monikit-app-gdpr-user-data-deletion' ) );
		}

		$email = sanitize_email( $_POST['email'] );
		$code = sanitize_text_field( $_POST['code'] );
		
		if ( ! is_email( $email ) || empty( $code ) ) {
			// Log failed confirmation
			MONIGPDR()->logs->log_action( $email, 'confirmation', 'failed', __( 'Invalid email or confirmation code provided.', 'monikit-app-gdpr-user-data-deletion' ), array(
				'request' => $_POST
			) );
			
			wp_send_json_error( array(
				'message' => __( 'Please provide valid email and confirmation code.', 'monikit-app-gdpr-user-data-deletion' )
			));
		}

		// Verify the code
		$code_data = get_transient( 'monigpdr_deletion_code_' . md5( $email ) );
		
		if ( ! $code_data || $code_data['used'] || $code_data['expires'] < time() || $code_data['code'] !== $code ) {
			// Log failed confirmation
			MONIGPDR()->logs->log_action( $email, 'confirmation', 'failed', __( 'Invalid or expired confirmation code.', 'monikit-app-gdpr-user-data-deletion' ), array(
				'request' => $_POST
			) );
			
			wp_send_json_error( array(
				'message' => __( 'Invalid or expired confirmation code.', 'monikit-app-gdpr-user-data-deletion' )
			));
		}

		// Mark code as used
		$code_data['used'] = true;
		set_transient( 'monigpdr_deletion_code_' . md5( $email ), $code_data, 60 * 60 );

		// Log successful confirmation
		MONIGPDR()->logs->log_action( $email, 'confirmation', 'success', __( 'Email confirmation successful. Proceeding with account deletion.', 'monikit-app-gdpr-user-data-deletion' ), array(
			'request' => $_POST
		) );

		// Process the deletion
		$deletion_result = $this->process_account_deletion( $email );
		
		if ( $deletion_result['success'] ) {
			// Update the original request status to completed
			MONIGPDR()->logs->update_log_status( $email, 'request', 'completed', __( 'Deletion request completed successfully.', 'monikit-app-gdpr-user-data-deletion' ) );
			
			// Log successful deletion from Keycloak
			MONIGPDR()->logs->log_action( $email, 'deletion', 'success', __( 'Account successfully deleted from Keycloak.', 'monikit-app-gdpr-user-data-deletion' ), array(
				'keycloak_user_id' => $deletion_result['keycloak_user_id'] ?? null,
				'response' => $deletion_result
			) );
			
			wp_send_json_success( array(
				'message' => __( 'Your account has been successfully deleted.', 'monikit-app-gdpr-user-data-deletion' ),
				'deleted' => true
			));
		} else {
			// Update the original request status to failed
			MONIGPDR()->logs->update_log_status( $email, 'request', 'failed', __( 'Deletion request failed.', 'monikit-app-gdpr-user-data-deletion' ) );
			
			// Log failed deletion from Keycloak
			MONIGPDR()->logs->log_action( $email, 'deletion', 'failed', $deletion_result['message'], array(
				'response' => $deletion_result
			) );
			
			wp_send_json_error( array(
				'message' => $deletion_result['message']
			));
		}
	}



	/**
	 * ######################
	 * ###
	 * #### HELPER FUNCTIONS
	 * ###
	 * ######################
	 */

	/**
	 * Generate a random confirmation code
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	string	Confirmation code
	 */
	private function generate_confirmation_code() {
		return sprintf( '%06d', mt_rand( 100000, 999999 ) );
	}

	/**
	 * Send confirmation email
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$email	Email address
	 * @param	string	$code	Confirmation code
	 * @return	bool	Success status
	 */
	private function send_confirmation_email( $email, $code ) {
		$current_lang = $this->get_current_language();
		
		// Get email template based on language
		$subject = $this->get_email_subject( $current_lang );
		$message = $this->get_email_message( $current_lang, $code, $email );
		
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		
		return wp_mail( $email, $subject, $message, $headers );
	}

	/**
	 * Get current language
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	string	Language code
	 */
	private function get_current_language() {
		// Check if WPML or Polylang is active
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			return ICL_LANGUAGE_CODE;
		}
		
		if ( function_exists( 'pll_current_language' ) ) {
			return pll_current_language();
		}
		
		// Get default language from settings
		$settings = MONIGPDR()->admin->get_settings();
		$default_language = isset( $settings['default_language'] ) ? $settings['default_language'] : 'en';
		
		return $default_language;
	}

	/**
	 * Get email subject based on language
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$lang	Language code
	 * @return	string	Email subject
	 */
	private function get_email_subject( $lang ) {
		$settings = MONIGPDR()->admin->get_settings();
		$field_key = 'email_subject_' . $lang;
		
		$subject = isset( $settings[ $field_key ] ) ? $settings[ $field_key ] : '';
		
		// Fallback to default subjects if not set
		if ( empty( $subject ) ) {
			$subjects = array(
				'en' => 'Account Deletion Confirmation',
				'de' => 'Bestätigung der Kontolöschung'
			);
			$subject = isset( $subjects[ $lang ] ) ? $subjects[ $lang ] : $subjects['en'];
		}
		
		// Ensure we always return a subject
		if ( empty( $subject ) ) {
			$subject = 'Account Deletion Confirmation';
		}
		
		return $subject;
	}

	/**
	 * Get email message based on language
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$lang	Language code
	 * @param	string	$code	Confirmation code
	 * @return	string	Email message
	 */
	private function get_email_message( $lang, $code, $email ) {
		$settings = MONIGPDR()->admin->get_settings();
		$field_key = 'email_html_' . $lang;
		
		$message = isset( $settings[ $field_key ] ) ? $settings[ $field_key ] : '';
		
		// Generate confirmation link
		$confirmation_link = add_query_arg( array(
			'action' => 'monigpdr_confirm_deletion',
			'email' => urlencode( $email ),
			'code' => $code,
			'nonce' => wp_create_nonce( 'monigpdr_confirm_deletion' )
		), home_url() );
		
		// Fallback to default messages if not set
		if ( empty( $message ) ) {
			$messages = array(
				'en' => sprintf(
					'<h2>Account Deletion Confirmation</h2>
					<p>Dear %s,</p>
					<p>You have requested to delete your account. To confirm this action, please use the following confirmation code:</p>
					<h3 style="background: #f0f0f0; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px;">%s</h3>
					<p>Or click this link to confirm: <a href="%s">Confirm Deletion</a></p>
					<p>This code will expire in 1 hour.</p>
					<p>If you did not request this deletion, please ignore this email.</p>',
					esc_html( $email ),
					$code,
					esc_url( $confirmation_link )
				),
				'de' => sprintf(
					'<h2>Bestätigung der Kontolöschung</h2>
					<p>Sehr geehrte/r %s,</p>
					<p>Sie haben die Löschung Ihres Kontos beantragt. Um diese Aktion zu bestätigen, verwenden Sie bitte den folgenden Bestätigungscode:</p>
					<h3 style="background: #f0f0f0; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px;">%s</h3>
					<p>Oder klicken Sie auf diesen Link zur Bestätigung: <a href="%s">Löschung bestätigen</a></p>
					<p>Dieser Code läuft in 1 Stunde ab.</p>
					<p>Wenn Sie diese Löschung nicht beantragt haben, ignorieren Sie bitte diese E-Mail.</p>',
					esc_html( $email ),
					$code,
					esc_url( $confirmation_link )
				)
			);
			$message = isset( $messages[ $lang ] ) ? $messages[ $lang ] : $messages['en'];
		} else {
			// Replace placeholders in the template
			$message = str_replace(
				array( '{confirmation_code}', '{user_email}', '{confirmation_link}' ),
				array( $code, esc_html( $email ), esc_url( $confirmation_link ) ),
				$message
			);
		}
		
		return $message;
	}

	/**
	 * Check if public deletion is enabled
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	bool	True if enabled
	 */
	private function is_public_deletion_enabled() {
		$settings = MONIGPDR()->admin->get_settings();
		return isset( $settings['enable_public_deletion'] ) && $settings['enable_public_deletion'] === '1';
	}



	/**
	 * Handle direct confirmation links from email
	 *
	 * @access	public
	 * @since	1.0.0
	 */
	public function handle_direct_confirmation_link() {
		// Check if this is a confirmation link request
		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'monigpdr_confirm_deletion' ) {
			return;
		}
		
		// Verify nonce
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'monigpdr_confirm_deletion' ) ) {
			wp_die( __( 'Invalid confirmation link.', 'monikit-app-gdpr-user-data-deletion' ) );
		}
		
		// Get email and code
		$email = isset( $_GET['email'] ) ? sanitize_email( urldecode( $_GET['email'] ) ) : '';
		$code = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
		
		if ( empty( $email ) || empty( $code ) ) {
			// Log failed confirmation
			MONIGPDR()->logs->log_action( $email, 'confirmation', 'failed', __( 'Invalid confirmation link parameters.', 'monikit-app-gdpr-user-data-deletion' ), array(
				'request' => $_GET
			) );
			wp_die( __( 'Invalid confirmation link parameters.', 'monikit-app-gdpr-user-data-deletion' ) );
		}
		
		// Verify the confirmation code
		$transient_key = 'monigpdr_deletion_code_' . md5( $email );
		$code_data = get_transient( $transient_key );
		
		if ( ! $code_data || $code_data['used'] || $code_data['expires'] < time() || $code_data['code'] !== $code ) {
			// Log failed confirmation
			MONIGPDR()->logs->log_action( $email, 'confirmation', 'failed', __( 'Invalid or expired confirmation code.', 'monikit-app-gdpr-user-data-deletion' ), array(
				'request' => $_GET
			) );
			wp_die( __( 'Invalid or expired confirmation code.', 'monikit-app-gdpr-user-data-deletion' ) );
		}
		
		// Mark code as used
		$code_data['used'] = true;
		set_transient( $transient_key, $code_data, 60 * 60 );
		
		// Log successful confirmation
		MONIGPDR()->logs->log_action( $email, 'confirmation', 'success', __( 'Email confirmation successful. Proceeding with account deletion.', 'monikit-app-gdpr-user-data-deletion' ), array(
			'request' => $_GET
		) );
		
		// Process the deletion
		$deletion_result = $this->process_account_deletion( $email );
		
		if ( $deletion_result['success'] ) {
			// Update the original request status to completed
			MONIGPDR()->logs->update_log_status( $email, 'request', 'completed', __( 'Deletion request completed successfully.', 'monikit-app-gdpr-user-data-deletion' ) );
			
			// Log successful deletion from Keycloak
			MONIGPDR()->logs->log_action( $email, 'deletion', 'success', __( 'Account successfully deleted from Keycloak.', 'monikit-app-gdpr-user-data-deletion' ), array(
				'keycloak_user_id' => $deletion_result['keycloak_user_id'] ?? null,
				'response' => $deletion_result
			) );
			
			// Delete the transient
			delete_transient( $transient_key );
			
			// Redirect to success page or show success message
			$current_lang = $this->get_current_language();
			$success_message = $current_lang === 'de' 
				? 'Ihr Konto wurde erfolgreich gelöscht.'
				: 'Your account has been successfully deleted.';
			
			wp_die( 
				'<h1>' . esc_html( $success_message ) . '</h1>',
				__( 'Account Deleted', 'monikit-app-gdpr-user-data-deletion' ),
				array( 'response' => 200 )
			);
		} else {
			// Update the original request status to failed
			MONIGPDR()->logs->update_log_status( $email, 'request', 'failed', __( 'Deletion request failed.', 'monikit-app-gdpr-user-data-deletion' ) );
			
			// Log failed deletion from Keycloak
			MONIGPDR()->logs->log_action( $email, 'deletion', 'failed', $deletion_result['message'], array(
				'response' => $deletion_result
			) );
			
			wp_die( esc_html( $deletion_result['message'] ) );
		}
	}

	/**
	 * Process account deletion
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$email	Email address
	 * @return	array	Result array
	 */
	private function process_account_deletion( $email ) {
		// Get Keycloak settings
		$settings = MONIGPDR()->admin->get_settings();
		
		// Check if Keycloak is configured
		if ( empty( $settings['keycloak_base_url'] ) || 
			 empty( $settings['keycloak_realm'] ) || 
			 empty( $settings['keycloak_client_id'] ) || 
			 empty( $settings['keycloak_admin_username'] ) || 
			 empty( $settings['keycloak_admin_password'] ) ) {
			
			// Log configuration error
			MONIGPDR()->logs->log_action( $email, 'deletion', 'failed', __( 'Keycloak settings not configured.', 'monikit-app-gdpr-user-data-deletion' ) );
			
			error_log( 'Monikit GDPR: Keycloak settings not configured for user deletion' );
			return array(
				'success' => false,
				'message' => __( 'User management system not configured. Please contact administrator.', 'monikit-app-gdpr-user-data-deletion' )
			);
		}
		
		try {
			// Delete user from Keycloak
			$deletion_result = $this->delete_user_from_keycloak( $email, $settings );
			
			if ( $deletion_result['success'] ) {
				error_log( sprintf( 'Monikit GDPR: User account successfully deleted for email: %s', $email ) );
				
				return array(
					'success' => true,
					'message' => __( 'Account deleted successfully.', 'monikit-app-gdpr-user-data-deletion' ),
					'keycloak_user_id' => $deletion_result['keycloak_user_id'] ?? null
				);
			} else {
				error_log( sprintf( 'Monikit GDPR: Failed to delete user account for email: %s. Error: %s', $email, $deletion_result['message'] ) );
				
				return array(
					'success' => false,
					'message' => $deletion_result['message']
				);
			}
			
		} catch ( Exception $e ) {
			// Log exception
			MONIGPDR()->logs->log_action( $email, 'deletion', 'failed', $e->getMessage(), array(
				'keycloak_realm' => $settings['keycloak_realm'] ?? null
			) );
			
			error_log( sprintf( 'Monikit GDPR: Exception during user deletion for email: %s. Error: %s', $email, $e->getMessage() ) );
			
			return array(
				'success' => false,
				'message' => __( 'An error occurred while deleting your account. Please try again or contact support.', 'monikit-app-gdpr-user-data-deletion' )
			);
		}
	}

	/**
	 * Delete user from Keycloak
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$email	Email address
	 * @param	array	$settings	Keycloak settings
	 * @return	array	Result array
	 */
	private function delete_user_from_keycloak( $email, $settings ) {
		// Normalize the base URL
		$base_url = rtrim( $settings['keycloak_base_url'], '/' );
		$realm = $settings['keycloak_realm'];
		$client_id = $settings['keycloak_client_id'];
		$client_secret = $settings['keycloak_client_secret'];
		$admin_username = $settings['keycloak_admin_username'];
		$admin_password = $settings['keycloak_admin_password'];
		
		try {
			// Step 1: Get admin access token with retry logic
			$access_token = $this->get_keycloak_admin_token_with_retry( $base_url, $realm, $client_id, $client_secret, $admin_username, $admin_password );
			
			if ( ! $access_token ) {
				return array(
					'success' => false,
					'message' => __( 'Failed to authenticate with user management system.', 'monikit-app-gdpr-user-data-deletion' )
				);
			}
			
			// Step 2: Find user by email with token refresh if needed
			$user_id = $this->find_keycloak_user_by_email_with_retry( $base_url, $realm, $access_token, $email, $client_id, $client_secret, $admin_username, $admin_password );
			
			if ( ! $user_id ) {
				return array(
					'success' => false,
					'message' => __( 'User account not found.', 'monikit-app-gdpr-user-data-deletion' )
				);
			}
			
			// Step 3: Delete the user with token refresh if needed
			$delete_result = $this->delete_keycloak_user_with_retry( $base_url, $realm, $access_token, $user_id, $client_id, $client_secret, $admin_username, $admin_password );
			
			if ( $delete_result ) {
				return array(
					'success' => true,
					'message' => __( 'Account deleted successfully.', 'monikit-app-gdpr-user-data-deletion' ),
					'keycloak_user_id' => $user_id
				);
			} else {
				return array(
					'success' => false,
					'message' => __( 'Failed to delete user account.', 'monikit-app-gdpr-user-data-deletion' )
				);
			}
			
		} catch ( Exception $e ) {
			error_log( 'Monikit GDPR: Keycloak API error: ' . $e->getMessage() );
			return array(
				'success' => false,
				'message' => __( 'Error communicating with user management system.', 'monikit-app-gdpr-user-data-deletion' )
			);
		}
	}
	
	/**
	 * Get Keycloak admin access token with retry logic
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$base_url	Keycloak base URL
	 * @param	string	$realm	Realm name
	 * @param	string	$client_id	Client ID
	 * @param	string	$client_secret	Client secret
	 * @param	string	$username	Admin username
	 * @param	string	$password	Admin password
	 * @return	string|false	Access token or false on failure
	 */
	private function get_keycloak_admin_token_with_retry( $base_url, $realm, $client_id, $client_secret, $username, $password ) {
		$max_retries = 3;
		$retry_count = 0;
		
		while ( $retry_count < $max_retries ) {
			try {
				$token = $this->get_keycloak_admin_token( $base_url, $realm, $client_id, $client_secret, $username, $password );
				
				if ( $token ) {
					return $token;
				}
				
				$retry_count++;
				if ( $retry_count < $max_retries ) {
					error_log( sprintf( 'Monikit GDPR: Token request failed, retrying (%d/%d)...', $retry_count, $max_retries ) );
					sleep( 1 ); // Wait 1 second before retry
				}
				
			} catch ( Exception $e ) {
				error_log( 'Monikit GDPR: Exception during token request: ' . $e->getMessage() );
				$retry_count++;
				if ( $retry_count < $max_retries ) {
					sleep( 1 );
				}
			}
		}
		
		error_log( 'Monikit GDPR: Failed to get access token after ' . $max_retries . ' attempts' );
		return false;
	}
	
	/**
	 * Build Keycloak URL with proper /auth handling
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$base_url	Keycloak base URL
	 * @param	string	$path	Path to append
	 * @return	string	Complete URL
	 */
	private function build_keycloak_url( $base_url, $path ) {
		// Normalize base URL
		$base_url = rtrim( $base_url, '/' );
		
		// Handle both /auth and non-/auth URLs
		if ( strpos( $base_url, '/auth' ) !== false ) {
			return $base_url . '/' . ltrim( $path, '/' );
		} else {
			return $base_url . '/auth/' . ltrim( $path, '/' );
		}
	}
	
	/**
	 * Get Keycloak admin access token
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$base_url	Keycloak base URL
	 * @param	string	$realm	Realm name
	 * @param	string	$client_id	Client ID
	 * @param	string	$client_secret	Client secret
	 * @param	string	$username	Admin username
	 * @param	string	$password	Admin password
	 * @return	string|false	Access token or false on failure
	 */
	private function get_keycloak_admin_token( $base_url, $realm, $client_id, $client_secret, $username, $password ) {
		// Use master realm for admin authentication (like the admin test does)
		$auth_realm = 'master';
		
		// Build token URL using helper method
		$token_url = $this->build_keycloak_url( $base_url, 'realms/' . $auth_realm . '/protocol/openid-connect/token' );
		
		$body = array(
			'grant_type' => 'password',
			'client_id' => $client_id,
			'client_secret' => $client_secret,
			'username' => $username,
			'password' => $password
		);
		
		$response = wp_remote_post( $token_url, array(
			'body' => $body,
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'User-Agent' => 'Monikit-Plugin/1.0'
			),
			'sslverify' => true,
			'httpversion' => '1.1'
		));
		
		if ( is_wp_error( $response ) ) {
			error_log( 'Monikit GDPR: Token request failed: ' . $response->get_error_message() );
			return false;
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		// Log the response for debugging (only in debug mode)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Monikit GDPR: Token response - Status: %d, Body: %s', $status_code, $body ) );
		}
		
		if ( $status_code === 200 && isset( $data['access_token'] ) ) {
			return $data['access_token'];
		}
		
		// Log specific error details
		if ( isset( $data['error_description'] ) ) {
			error_log( 'Monikit GDPR: Token error: ' . $data['error_description'] );
		} elseif ( isset( $data['error'] ) ) {
			error_log( 'Monikit GDPR: Token error: ' . $data['error'] );
		}
		
		return false;
	}
	
	/**
	 * Find Keycloak user by email with token refresh if needed
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$base_url	Keycloak base URL
	 * @param	string	$realm	Realm name
	 * @param	string	$access_token	Admin access token
	 * @param	string	$email	User email
	 * @param	string	$client_id	Client ID
	 * @param	string	$client_secret	Client secret
	 * @param	string	$admin_username	Admin username
	 * @param	string	$admin_password	Admin password
	 * @return	string|false	User ID or false if not found
	 */
	private function find_keycloak_user_by_email_with_retry( $base_url, $realm, $access_token, $email, $client_id, $client_secret, $admin_username, $admin_password ) {
		$max_retries = 2;
		$retry_count = 0;
		$current_token = $access_token;
		
		while ( $retry_count < $max_retries ) {
			try {
				$user_id = $this->find_keycloak_user_by_email( $base_url, $realm, $current_token, $email );
				
				if ( $user_id !== false ) {
					return $user_id;
				}
				
				// If user not found, try refreshing token and retry once
				if ( $retry_count < $max_retries - 1 ) {
					error_log( 'Monikit GDPR: User not found, refreshing token and retrying...' );
					$current_token = $this->get_keycloak_admin_token( $base_url, $realm, $client_id, $client_secret, $admin_username, $admin_password );
					if ( ! $current_token ) {
						error_log( 'Monikit GDPR: Failed to refresh token for user search' );
						return false;
					}
				}
				
				$retry_count++;
				if ( $retry_count < $max_retries ) {
					sleep( 1 ); // Wait 1 second before retry
				}
				
			} catch ( Exception $e ) {
				error_log( 'Monikit GDPR: Exception during user search: ' . $e->getMessage() );
				$retry_count++;
				if ( $retry_count < $max_retries ) {
					sleep( 1 );
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Find Keycloak user by email
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$base_url	Keycloak base URL
	 * @param	string	$realm	Realm name
	 * @param	string	$access_token	Admin access token
	 * @param	string	$email	User email
	 * @return	string|false	User ID or false if not found
	 */
	private function find_keycloak_user_by_email( $base_url, $realm, $access_token, $email ) {
		// Build users URL using helper method
		$users_url = $this->build_keycloak_url( $base_url, 'admin/realms/' . $realm . '/users?email=' . urlencode( $email ) );
		
		// Log the URL for debugging (only in debug mode)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Monikit GDPR: Searching for user with URL: ' . $users_url );
		}
		
		$response = wp_remote_get( $users_url, array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type' => 'application/json',
				'User-Agent' => 'Monikit-Plugin/1.0'
			),
			'sslverify' => true,
			'httpversion' => '1.1'
		));
		
		if ( is_wp_error( $response ) ) {
			error_log( 'Monikit GDPR: User search failed: ' . $response->get_error_message() );
			return false;
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$users = json_decode( $body, true );
		
		// Log the response for debugging (only in debug mode)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Monikit GDPR: User search response - Status: %d, Body: %s', $status_code, $body ) );
		}
		
		if ( $status_code === 200 && is_array( $users ) && count( $users ) > 0 ) {
			// Return the first user's ID
			$user_id = $users[0]['id'];
			error_log( 'Monikit GDPR: User found with ID: ' . $user_id );
			return $user_id;
		}
		
		error_log( 'Monikit GDPR: User not found or invalid response' );
		return false;
	}
	
	/**
	 * Delete Keycloak user with token refresh if needed
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$base_url	Keycloak base URL
	 * @param	string	$realm	Realm name
	 * @param	string	$access_token	Admin access token
	 * @param	string	$user_id	User ID to delete
	 * @param	string	$client_id	Client ID
	 * @param	string	$client_secret	Client secret
	 * @param	string	$admin_username	Admin username
	 * @param	string	$admin_password	Admin password
	 * @return	bool	Success status
	 */
	private function delete_keycloak_user_with_retry( $base_url, $realm, $access_token, $user_id, $client_id, $client_secret, $admin_username, $admin_password ) {
		$max_retries = 2;
		$retry_count = 0;
		$current_token = $access_token;
		
		while ( $retry_count < $max_retries ) {
			try {
				$result = $this->delete_keycloak_user( $base_url, $realm, $current_token, $user_id );
				
				if ( $result ) {
					return true;
				}
				
				// If deletion failed, try refreshing token and retry once
				if ( $retry_count < $max_retries - 1 ) {
					error_log( 'Monikit GDPR: User deletion failed, refreshing token and retrying...' );
					$current_token = $this->get_keycloak_admin_token( $base_url, $realm, $client_id, $client_secret, $admin_username, $admin_password );
					if ( ! $current_token ) {
						error_log( 'Monikit GDPR: Failed to refresh token for user deletion' );
						return false;
					}
				}
				
				$retry_count++;
				if ( $retry_count < $max_retries ) {
					sleep( 1 ); // Wait 1 second before retry
				}
				
			} catch ( Exception $e ) {
				error_log( 'Monikit GDPR: Exception during user deletion: ' . $e->getMessage() );
				$retry_count++;
				if ( $retry_count < $max_retries ) {
					sleep( 1 );
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Delete Keycloak user
	 *
	 * @access	private
	 * @since	1.0.0
	 * @param	string	$base_url	Keycloak base URL
	 * @param	string	$realm	Realm name
	 * @param	string	$access_token	Admin access token
	 * @param	string	$user_id	User ID to delete
	 * @return	bool	Success status
	 */
	private function delete_keycloak_user( $base_url, $realm, $access_token, $user_id ) {
		// Build delete URL using helper method
		$delete_url = $this->build_keycloak_url( $base_url, 'admin/realms/' . $realm . '/users/' . $user_id );
		
		// Log the URL for debugging (only in debug mode)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Monikit GDPR: Deleting user with URL: ' . $delete_url );
		}
		
		$response = wp_remote_request( $delete_url, array(
			'method' => 'DELETE',
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type' => 'application/json',
				'User-Agent' => 'Monikit-Plugin/1.0'
			),
			'sslverify' => true,
			'httpversion' => '1.1'
		));
		
		if ( is_wp_error( $response ) ) {
			error_log( 'Monikit GDPR: User deletion failed: ' . $response->get_error_message() );
			return false;
		}
		
		$status_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		
		// Log the response for debugging (only in debug mode)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Monikit GDPR: Delete user response - Status: %d, Body: %s', $status_code, $response_body ) );
		}
		
		// Keycloak returns 204 No Content on successful deletion
		if ( $status_code === 204 ) {
			error_log( 'Monikit GDPR: User deleted successfully' );
			return true;
		}
		
		// Handle other status codes
		switch ( $status_code ) {
			case 404:
				error_log( 'Monikit GDPR: User not found in Keycloak' );
				return false;
			case 403:
				error_log( 'Monikit GDPR: Insufficient permissions to delete user' );
				return false;
			case 401:
				error_log( 'Monikit GDPR: Authentication failed for user deletion' );
				return false;
			default:
				error_log( 'Monikit GDPR: Unexpected status code for user deletion: ' . $status_code );
				return false;
		}
	}

	/**
	 * Shortcode for embedding the deletion form
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	array	$atts	Shortcode attributes
	 * @return	string	Form HTML
	 */
	public function deletion_form_shortcode( $atts ) {
		// Check if public deletion is enabled
		if ( ! $this->is_public_deletion_enabled() ) {
			return '';
		}

		// Get current language
		$current_lang = $this->get_current_language();
		
		// Get translated strings
		$translated_title = $this->get_translated_string( 'delete_account', 'Delete Account' );
		$translated_subtitle = $this->get_translated_string( 'request_deletion_subtitle', 'Request deletion of your account. You will receive a confirmation email.' );
		$email_label = $this->get_translated_string( 'email_address', 'Email Address' );
		$email_placeholder = $current_lang === 'de' ? 'ihre@email.de' : 'your@email.com';
		$request_button = $this->get_translated_string( 'request_deletion', 'Request Deletion' );
		$confirmation_label = $this->get_translated_string( 'confirmation_code', 'Confirmation Code' );
		$confirmation_placeholder = '123456';
		$confirmation_help = $current_lang === 'de' 
			? 'Geben Sie den 6-stelligen Code ein, der an Ihre E-Mail-Adresse gesendet wurde.'
			: 'Enter the 6-digit code sent to your email address.';
		$back_button = $current_lang === 'de' ? 'Zurück' : 'Back';
		$confirm_button = $current_lang === 'de' ? 'Bestätigen' : 'Confirm';
		$final_title = $this->get_translated_string( 'final_confirmation_title', 'Final Confirmation' );
		$final_message = $this->get_translated_string( 'final_confirmation_message', 'Are you sure you want to permanently delete your account? This action cannot be undone.' );
		$checkbox_text = $this->get_translated_string( 'confirm_checkbox_text', 'I understand that this action is irreversible and my account will be permanently deleted.' );
		$cancel_button = $current_lang === 'de' ? 'Abbrechen' : 'Cancel';
		$delete_button = $this->get_translated_string( 'delete_my_account', 'Delete My Account' );

		// Parse shortcode attributes
		$atts = shortcode_atts( array(
			'title' => $translated_title,
			'subtitle' => $translated_subtitle,
			'show_title' => 'true',
			'show_subtitle' => 'true',
			'class' => 'monigpdr-deletion-form-embedded',
			'style' => 'default', // default, minimal, card
		), $atts, 'monigpdr_deletion_form' );

		// Ensure scripts and styles are loaded
		$this->enqueue_frontend_scripts_and_styles();

		// Start output buffering
		ob_start();
		
		// Include the form template
		include MONIGPDR_PLUGIN_DIR . 'core/includes/templates/deletion-form.php';
		
		// Return the buffered content
		return ob_get_clean();
	}

} 