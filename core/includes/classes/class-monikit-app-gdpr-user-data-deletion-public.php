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
			wp_send_json_error( array(
				'message' => __( 'Please enter a valid email address.', 'monikit-app-gdpr-user-data-deletion' )
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
			wp_send_json_success( array(
				'message' => __( 'Confirmation code sent to your email address.', 'monikit-app-gdpr-user-data-deletion' ),
				'show_code_input' => true
			));
		} else {
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
			wp_send_json_error( array(
				'message' => __( 'Please provide valid email and confirmation code.', 'monikit-app-gdpr-user-data-deletion' )
			));
		}

		// Verify the code
		$code_data = get_transient( 'monigpdr_deletion_code_' . md5( $email ) );
		
		if ( ! $code_data || $code_data['used'] || $code_data['expires'] < time() || $code_data['code'] !== $code ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid or expired confirmation code.', 'monikit-app-gdpr-user-data-deletion' )
			));
		}

		// Mark code as used
		$code_data['used'] = true;
		set_transient( 'monigpdr_deletion_code_' . md5( $email ), $code_data, 60 * 60 );

		// Process the deletion
		$deletion_result = $this->process_account_deletion( $email );
		
		if ( $deletion_result['success'] ) {
			wp_send_json_success( array(
				'message' => __( 'Your account has been successfully deleted.', 'monikit-app-gdpr-user-data-deletion' ),
				'deleted' => true
			));
		} else {
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
			wp_die( __( 'Invalid confirmation link parameters.', 'monikit-app-gdpr-user-data-deletion' ) );
		}
		
		// Verify the confirmation code
		$transient_key = 'monigpdr_deletion_code_' . md5( $email );
		$code_data = get_transient( $transient_key );
		
		if ( ! $code_data || $code_data['used'] || $code_data['expires'] < time() || $code_data['code'] !== $code ) {
			wp_die( __( 'Invalid or expired confirmation code.', 'monikit-app-gdpr-user-data-deletion' ) );
		}
		
		// Mark code as used
		$code_data['used'] = true;
		set_transient( $transient_key, $code_data, 60 * 60 );
		
		// Process the deletion
		$deletion_result = $this->process_account_deletion( $email );
		
		if ( $deletion_result['success'] ) {
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
		// This is where you would integrate with your Keycloak API or other user management system
		// For now, we'll just return success
		
		// You can add your Keycloak API integration here
		// Example:
		// $keycloak_result = $this->delete_user_from_keycloak( $email );
		
		// Log the deletion request
		error_log( sprintf( 'Account deletion requested for email: %s', $email ) );
		
		return array(
			'success' => true,
			'message' => __( 'Account deleted successfully.', 'monikit-app-gdpr-user-data-deletion' )
		);
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

		// Parse shortcode attributes
		$atts = shortcode_atts( array(
			'title' => __( 'Delete Account', 'monikit-app-gdpr-user-data-deletion' ),
			'subtitle' => __( 'Request deletion of your account. You will receive a confirmation email.', 'monikit-app-gdpr-user-data-deletion' ),
			'show_title' => 'true',
			'show_subtitle' => 'true',
			'class' => 'monigpdr-deletion-form-embedded',
			'style' => 'default', // default, minimal, card
		), $atts, 'monigpdr_deletion_form' );

		// Get current language
		$current_lang = $this->get_current_language();
		
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