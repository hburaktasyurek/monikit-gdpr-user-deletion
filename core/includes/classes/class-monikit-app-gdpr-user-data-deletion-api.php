<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Monikit_App_Gdpr_User_Data_Deletion_API
 *
 * Handles secure REST API endpoints for programmatic user deletion
 *
 * @package		MONIGPDR
 * @subpackage	Classes/Monikit_App_Gdpr_User_Data_Deletion_API
 * @author		Hasan Burak TASYUREK
 * @since		1.0.0
 */
class Monikit_App_Gdpr_User_Data_Deletion_API {

	/**
	 * Our Monikit_App_Gdpr_User_Data_Deletion_API constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct(){
		add_action( 'rest_api_init', array( $this, 'register_api_routes' ) );
	}

	/**
	 * Register REST API routes
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function register_api_routes() {
		register_rest_route( 'monigpdr/v1', '/delete', array(
			'methods' => 'POST',
			'callback' => array( $this, 'handle_user_deletion_request' ),
			'permission_callback' => '__return_true', // We handle authentication in the callback
			'args' => array(
				'email' => array(
					'required' => true,
					'type' => 'string',
					'format' => 'email',
					'sanitize_callback' => 'sanitize_email',
					'validate_callback' => 'is_email',
				),
				'api_key' => array(
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );
	}

	/**
	 * Handle user deletion request via API
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	WP_REST_Request	$request	The request object
	 * @return	WP_REST_Response	Response object
	 */
	public function handle_user_deletion_request( $request ) {
		// Check if API is enabled
		$settings = MONIGPDR()->admin->get_settings();
		if ( ! isset( $settings['enable_direct_api'] ) || $settings['enable_direct_api'] !== '1' ) {
			return new WP_REST_Response( array(
				'error' => 'api_disabled'
			), 503 );
		}

		// Validate API key
		$provided_api_key = $request->get_param( 'api_key' );
		$stored_api_key = isset( $settings['internal_api_key'] ) ? $settings['internal_api_key'] : '';
		

		
		if ( empty( $stored_api_key ) || $provided_api_key !== $stored_api_key ) {
			// Log failed authentication attempt
			MONIGPDR()->logs->log_action( 
				$request->get_param( 'email' ), 
				'request', 
				'failed', 
				'Invalid API key provided', 
				array(
					'source' => 'API',
					'ip_address' => $this->get_client_ip(),
					'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
				)
			);
			
			return new WP_REST_Response( array(
				'error' => 'invalid_api_key'
			), 401 );
		}

		$email = $request->get_param( 'email' );
		
		// Log API request
		MONIGPDR()->logs->log_action( 
			$email, 
			'request', 
			'pending', 
			'API deletion request received', 
			array(
				'source' => 'API',
				'ip_address' => $this->get_client_ip(),
				'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
			)
		);

		try {
			// Use existing deletion logic from public class
			$public_class = new Monikit_App_Gdpr_User_Data_Deletion_Public();
			$deletion_result = $public_class->process_account_deletion( $email );
			


			if ( $deletion_result['success'] ) {
				// Update the original request status to completed
				MONIGPDR()->logs->update_log_status( $email, 'request', 'completed', 'API deletion request completed successfully.' );

				// Log successful deletion
				MONIGPDR()->logs->log_action( 
					$email, 
					'deletion', 
					'success', 
					'Account successfully deleted via API', 
					array(
						'source' => 'API',
						'keycloak_user_id' => isset( $deletion_result['keycloak_user_id'] ) ? $deletion_result['keycloak_user_id'] : '',
						'ip_address' => $this->get_client_ip(),
						'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
					)
				);

				return new WP_REST_Response( array(
					'status' => 'deleted',
					'email' => $email
				), 200 );
			} else {
				// Check if user was not found
				if ( strpos( $deletion_result['message'], 'not found' ) !== false || 
					 strpos( $deletion_result['message'], 'User account not found' ) !== false ) {
					
									// Log user not found
				MONIGPDR()->logs->log_action( 
					$email, 
					'request', 
					'failed', 
					'User not found via API', 
					array(
						'source' => 'API',
						'ip_address' => $this->get_client_ip(),
						'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
					)
				);

					return new WP_REST_Response( array(
						'error' => 'user_not_found'
					), 404 );
				}

				// Log other errors
				MONIGPDR()->logs->log_action( 
					$email, 
					'request', 
					'failed', 
					'API deletion failed: ' . $deletion_result['message'], 
					array(
						'source' => 'API',
						'ip_address' => $this->get_client_ip(),
						'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
					)
				);

				return new WP_REST_Response( array(
					'error' => 'keycloak_error'
				), 500 );
			}

		} catch ( Exception $e ) {
			// Log exception
			MONIGPDR()->logs->log_action( 
				$email, 
				'request', 
				'failed', 
				'API deletion exception: ' . $e->getMessage(), 
				array(
					'source' => 'API',
					'ip_address' => $this->get_client_ip(),
					'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
				)
			);

			return new WP_REST_Response( array(
				'error' => 'keycloak_error'
			), 500 );
		}
	}

	/**
	 * Get client IP address
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	string	Client IP address
	 */
	private function get_client_ip() {
		$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		
		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}
		
		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
	}

	/**
	 * Generate a secure API key
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	string	Generated API key
	 */
	public function generate_api_key() {
		return wp_generate_password( 64, false );
	}
} 