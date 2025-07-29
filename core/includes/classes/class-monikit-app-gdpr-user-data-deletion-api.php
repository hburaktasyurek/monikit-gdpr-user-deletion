<?php
/**
 * Monikit App GDPR User Data Deletion API
 *
 * @package       MONIGPDR
 * @author        Hasan Burak TASYUREK
 * @license       gplv2
 * @version       1.4.0
 *
 * @since         1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * API Class for OAuth2-based user deletion
 *
 * @since	1.2.0
 */
class Monikit_App_Gdpr_User_Data_Deletion_API {

	/**
	 * Constructor
	 *
	 * @access	public
	 * @since	1.2.0
	 */
	function __construct() {
		$this->init_hooks();
		$this->add_security_headers();
	}
	
	/**
	 * Add security headers for API responses
	 *
	 * @access	private
	 * @since	1.3.0
	 */
	private function add_security_headers() {
		add_action( 'rest_api_init', function() {
			add_filter( 'rest_pre_serve_request', function( $served, $result, $request, $server ) {
				if ( strpos( $request->get_route(), '/monigpdr/v1/' ) === 0 ) {
					header( 'X-Content-Type-Options: nosniff' );
					header( 'X-Frame-Options: DENY' );
					header( 'X-XSS-Protection: 1; mode=block' );
					header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
				}
				return $served;
			}, 10, 4 );
		});
	}

	/**
	 * Initialize hooks
	 *
	 * @access	private
	 * @since	1.2.0
	 */
	private function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_api_routes' ) );
	}

	/**
	 * Register API routes
	 *
	 * @access	public
	 * @since	1.2.0
	 */
	public function register_api_routes() {
		register_rest_route( 'monigpdr/v1', '/delete', array(
			'methods' => 'POST',
			'callback' => array( $this, 'handle_user_deletion_request' ),
			'permission_callback' => '__return_true',
			'args' => array(
				'headers' => array(
					'validate_callback' => function( $param, $request, $key ) {
						// Ensure Authorization header is present
						$auth_header = $request->get_header( 'Authorization' );
						return ! empty( $auth_header ) && preg_match( '/^Bearer\s+.+$/i', $auth_header );
					},
				),
			),
		) );
	}

	/**
	 * Handle user deletion request with OAuth2 access token
	 *
	 * @access	public
	 * @since	1.2.0
	 * @param	WP_REST_Request	$request	Request object
	 * @return	WP_REST_Response	Response object
	 */
	public function handle_user_deletion_request( $request ) {
		// Basic rate limiting protection
		$client_ip = $this->get_client_ip();
		$rate_limit_key = 'monikit_api_rate_limit_' . md5( $client_ip );
		$rate_limit_count = get_transient( $rate_limit_key );
		
		if ( $rate_limit_count && $rate_limit_count >= 10 ) {
			return new WP_REST_Response( array(
				'error' => 'rate_limit_exceeded'
			), 429 );
		}
		
		// Increment rate limit counter
		if ( $rate_limit_count ) {
			set_transient( $rate_limit_key, $rate_limit_count + 1, 300 ); // 5 minutes
		} else {
			set_transient( $rate_limit_key, 1, 300 ); // 5 minutes
		}
		
		// Get plugin settings
		$settings = MONIGPDR()->admin->get_settings();
		
		// Check if Keycloak is configured
		if ( empty( $settings['keycloak_base_url'] ) || 
			 empty( $settings['keycloak_realm'] ) || 
			 empty( $settings['keycloak_client_id'] ) || 
			 empty( $settings['keycloak_client_secret'] ) || 
			 empty( $settings['keycloak_admin_username'] ) || 
			 empty( $settings['keycloak_admin_password'] ) ) {
			
			return new WP_REST_Response( array(
				'error' => 'keycloak_error'
			), 500 );
		}

		// Get authorization header
		$auth_header = $request->get_header( 'Authorization' );
		if ( empty( $auth_header ) || ! preg_match( '/^Bearer\s+(.+)$/i', $auth_header, $matches ) ) {
			return new WP_REST_Response( array(
				'error' => 'invalid_token'
			), 401 );
		}

		$access_token = $matches[1];
		
		// Validate token format (basic JWT structure)
		if ( ! preg_match( '/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]*$/', $access_token ) ) {
			return new WP_REST_Response( array(
				'error' => 'invalid_token_format'
			), 400 );
		}

		try {
			// Validate access token and get user info
			$user_info = $this->validate_access_token( $access_token, $settings );
			
			if ( ! $user_info ) {
				return new WP_REST_Response( array(
					'error' => 'invalid_token'
				), 401 );
			}

			// Extract user identifier
			$user_id = $user_info['sub'] ?? null;
			$user_email = $user_info['email'] ?? null;

			if ( empty( $user_id ) && empty( $user_email ) ) {
				return new WP_REST_Response( array(
					'error' => 'invalid_token'
				), 400 );
			}

			// Log the request
			$log_email = $user_email ?: 'user_' . $user_id;
			MONIGPDR()->logs->log_action( 
				$log_email, 
				'request', 
				'pending', 
				'Access token deletion request received', 
				array(
					'source' => 'access_token',
					'ip_address' => $this->get_client_ip(),
					'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
					'keycloak_user_id' => $user_id,
					'user_email' => $user_email
				)
			);

			// Delete user from Keycloak
			$deletion_result = $this->delete_user_by_token_info( $user_id, $user_email, $settings );

			if ( $deletion_result['success'] ) {
				// Update the original request status to completed
				MONIGPDR()->logs->update_log_status( $log_email, 'request', 'completed', 'Access token deletion request completed successfully.' );

				// Log successful deletion
				MONIGPDR()->logs->log_action( 
					$log_email, 
					'deletion', 
					'success', 
					'Account successfully deleted via access token', 
					array(
						'source' => 'access_token',
						'keycloak_user_id' => $deletion_result['keycloak_user_id'] ?? $user_id,
						'ip_address' => $this->get_client_ip(),
						'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
						'user_email' => $user_email
					)
				);

				return new WP_REST_Response( array(
					'status' => 'deleted'
				), 200 );
			} else {
				// Check if user was not found
				if ( strpos( $deletion_result['message'], 'not found' ) !== false || 
					 strpos( $deletion_result['message'], 'User account not found' ) !== false ) {
					
					// Log user not found
					MONIGPDR()->logs->log_action( 
						$log_email, 
						'request', 
						'failed', 
						'User not found via access token', 
						array(
							'source' => 'access_token',
							'ip_address' => $this->get_client_ip(),
							'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
							'keycloak_user_id' => $user_id,
							'user_email' => $user_email
						)
					);

					return new WP_REST_Response( array(
						'error' => 'user_not_found'
					), 404 );
				}

				// Log other errors
				MONIGPDR()->logs->log_action( 
					$log_email, 
					'request', 
					'failed', 
					'Access token deletion failed: ' . $deletion_result['message'], 
					array(
						'source' => 'access_token',
						'ip_address' => $this->get_client_ip(),
						'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
						'keycloak_user_id' => $user_id,
						'user_email' => $user_email
					)
				);

				return new WP_REST_Response( array(
					'error' => 'keycloak_error'
				), 500 );
			}

		} catch ( Exception $e ) {
			// Log exception
			$log_email = isset( $user_email ) ? $user_email : 'unknown_user';
			MONIGPDR()->logs->log_action( 
				$log_email, 
				'request', 
				'failed', 
				'Access token deletion exception: ' . $e->getMessage(), 
				array(
					'source' => 'access_token',
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
	 * Validate access token using Keycloak userinfo endpoint
	 *
	 * @access	private
	 * @since	1.2.0
	 * @param	string	$access_token	Access token to validate
	 * @param	array	$settings		Plugin settings
	 * @return	array|false	User info array or false if invalid
	 */
	private function validate_access_token( $access_token, $settings ) {
		$base_url = rtrim( $settings['keycloak_base_url'], '/' );
		$realm = $settings['keycloak_realm'];
		
		// Ensure base URL includes /auth/ path
		if ( strpos( $base_url, '/auth' ) === false ) {
			$base_url .= '/auth';
		}
		
		$userinfo_url = $base_url . '/realms/' . $realm . '/protocol/openid-connect/userinfo';
		
		$ch = curl_init();
		curl_setopt_array( $ch, array(
			CURLOPT_URL => $userinfo_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer ' . $access_token,
				'Content-Type: application/json'
			),
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2
		) );
		
		$response = curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$curl_error = curl_error( $ch );
		curl_close( $ch );
		
		if ( $curl_error ) {
			error_log( 'Monikit GDPR: cURL error validating access token: ' . $curl_error );
			return false;
		}
		
		if ( $http_code !== 200 ) {
			error_log( 'Monikit GDPR: Invalid access token, HTTP code: ' . $http_code );
			return false;
		}
		
		$user_info = json_decode( $response, true );
		if ( ! $user_info || ! is_array( $user_info ) ) {
			error_log( 'Monikit GDPR: Invalid userinfo response format' );
			return false;
		}
		
		return $user_info;
	}

	/**
	 * Delete user from Keycloak using user ID or email
	 *
	 * @access	private
	 * @since	1.2.0
	 * @param	string	$user_id		Keycloak user ID
	 * @param	string	$user_email		User email
	 * @param	array	$settings		Plugin settings
	 * @return	array	Result array
	 */
	private function delete_user_by_token_info( $user_id, $user_email, $settings ) {
		// Get admin token
		$admin_token = $this->get_keycloak_admin_token( $settings );
		if ( ! $admin_token ) {
			return array(
				'success' => false,
				'message' => 'Failed to obtain admin token'
			);
		}
		
		$base_url = rtrim( $settings['keycloak_base_url'], '/' );
		$realm = $settings['keycloak_realm'];
		
		// Ensure base URL includes /auth/ path
		if ( strpos( $base_url, '/auth' ) === false ) {
			$base_url .= '/auth';
		}
		
		// If we have user ID, delete directly
		if ( ! empty( $user_id ) ) {
			$delete_url = $base_url . '/admin/realms/' . $realm . '/users/' . $user_id;
			
			$ch = curl_init();
			curl_setopt_array( $ch, array(
				CURLOPT_URL => $delete_url,
				CURLOPT_CUSTOMREQUEST => 'DELETE',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTPHEADER => array(
					'Authorization: Bearer ' . $admin_token,
					'Content-Type: application/json'
				),
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2
			) );
			
			$response = curl_exec( $ch );
			$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$curl_error = curl_error( $ch );
			curl_close( $ch );
			
			if ( $curl_error ) {
				return array(
					'success' => false,
					'message' => 'cURL error: ' . $curl_error
				);
			}
			
			if ( $http_code === 204 ) {
				return array(
					'success' => true,
					'keycloak_user_id' => $user_id
				);
			} elseif ( $http_code === 404 ) {
				return array(
					'success' => false,
					'message' => 'User not found'
				);
			} else {
				error_log( 'Monikit GDPR: User deletion failed, HTTP code: ' . $http_code . ', Response: ' . $response );
				return array(
					'success' => false,
					'message' => 'Keycloak error, HTTP code: ' . $http_code . ', Response: ' . $response
				);
			}
		}
		
		// If we only have email, search for user first
		if ( ! empty( $user_email ) ) {
			$search_url = $base_url . '/admin/realms/' . $realm . '/users?email=' . urlencode( $user_email );
			
			$ch = curl_init();
			curl_setopt_array( $ch, array(
				CURLOPT_URL => $search_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTPHEADER => array(
					'Authorization: Bearer ' . $admin_token,
					'Content-Type: application/json'
				),
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2
			) );
			
			$response = curl_exec( $ch );
			$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$curl_error = curl_error( $ch );
			curl_close( $ch );
			
			if ( $curl_error ) {
				return array(
					'success' => false,
					'message' => 'cURL error searching user: ' . $curl_error
				);
			}
			
			if ( $http_code !== 200 ) {
				return array(
					'success' => false,
					'message' => 'Failed to search user, HTTP code: ' . $http_code
				);
			}
			
			$users = json_decode( $response, true );
			if ( ! $users || ! is_array( $users ) || empty( $users ) ) {
				return array(
					'success' => false,
					'message' => 'User not found'
				);
			}
			
			// Use the first user found
			$found_user = $users[0];
			$found_user_id = $found_user['id'] ?? null;
			
			if ( empty( $found_user_id ) ) {
				return array(
					'success' => false,
					'message' => 'User ID not found in search results'
				);
			}
			
			// Delete the found user
			return $this->delete_user_by_token_info( $found_user_id, null, $settings );
		}
		
		return array(
			'success' => false,
			'message' => 'No user identifier provided'
		);
	}

	/**
	 * Get Keycloak admin token
	 *
	 * @access	private
	 * @since	1.2.0
	 * @param	array	$settings	Plugin settings
	 * @return	string|false	Admin token or false on failure
	 */
	private function get_keycloak_admin_token( $settings ) {
		$base_url = rtrim( $settings['keycloak_base_url'], '/' );
		$realm = $settings['keycloak_realm'];
		$client_id = $settings['keycloak_client_id'];
		$client_secret = $settings['keycloak_client_secret'] ?? '';
		$admin_username = $settings['keycloak_admin_username'];
		$admin_password = $settings['keycloak_admin_password'];
		
		// Use master realm for admin authentication (like the UI version does)
		$auth_realm = 'master';
		
		// Ensure base URL includes /auth/ path
		if ( strpos( $base_url, '/auth' ) === false ) {
			$base_url .= '/auth';
		}
		
		$token_url = $base_url . '/realms/' . $auth_realm . '/protocol/openid-connect/token';
		

		
		$post_data = array(
			'grant_type' => 'password',
			'client_id' => $client_id,
			'username' => $admin_username,
			'password' => $admin_password
		);
		
		if ( ! empty( $client_secret ) ) {
			$post_data['client_secret'] = $client_secret;
		}
		

		
		$ch = curl_init();
		curl_setopt_array( $ch, array(
			CURLOPT_URL => $token_url,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query( $post_data ),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/x-www-form-urlencoded'
			),
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2
		) );
		
		$response = curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$curl_error = curl_error( $ch );
		curl_close( $ch );
		
		if ( $curl_error ) {
			error_log( 'Monikit GDPR: cURL error getting admin token: ' . $curl_error );
			return false;
		}
		
		if ( $http_code !== 200 ) {
			error_log( 'Monikit GDPR: Failed to get admin token, HTTP code: ' . $http_code . ', Response: ' . $response );
			return false;
		}
		
		$token_data = json_decode( $response, true );
		if ( ! $token_data || ! isset( $token_data['access_token'] ) ) {
			error_log( 'Monikit GDPR: Invalid token response format' );
			return false;
		}
		
		return $token_data['access_token'];
	}

	/**
	 * Get client IP address
	 *
	 * @access	private
	 * @since	1.2.0
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
		
		return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
	}
} 