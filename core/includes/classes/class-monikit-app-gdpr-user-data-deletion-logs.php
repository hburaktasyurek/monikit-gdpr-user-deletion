<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Monikit_App_Gdpr_User_Data_Deletion_Logs
 *
 * Handles logging of deletion requests and actions for GDPR compliance
 *
 * @package		MONIGPDR
 * @subpackage	Classes/Monikit_App_Gdpr_User_Data_Deletion_Logs
 * @author		Hasan Burak TASYUREK
 * @since		1.0.0
 */
class Monikit_App_Gdpr_User_Data_Deletion_Logs {

	/**
	 * Log table name
	 *
	 * @var		string
	 * @since   1.0.0
	 */
	private $table_name;

	/**
	 * Our Monikit_App_Gdpr_User_Data_Deletion_Logs constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct(){
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'monikit_deletion_logs';
		
		// Create table on plugin activation
		add_action( 'MONIGPDR/plugin_loaded', array( $this, 'create_log_table' ) );
	}

	/**
	 * Create the logs table if it doesn't exist
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function create_log_table() {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			email varchar(255) NOT NULL,
			action varchar(50) NOT NULL,
			status varchar(50) NOT NULL,
			message text,
			ip_address varchar(45),
			user_agent text,
			request_data longtext,
			response_data longtext,
			keycloak_user_id varchar(255),
			keycloak_realm varchar(255),
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY email (email),
			KEY action (action),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Log a deletion action
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	string	$email	Email address
	 * @param	string	$action	Action type (request, confirmation, deletion, error)
	 * @param	string	$status	Status (success, failed, pending)
	 * @param	string	$message	Message or error description
	 * @param	array	$additional_data	Additional data to log
	 * @return	int|false	Log ID or false on failure
	 */
	public function log_action( $email, $action, $status, $message = '', $additional_data = array() ) {
		global $wpdb;
		
		// Sanitize inputs
		$email = sanitize_email( $email );
		$action = sanitize_text_field( $action );
		$status = sanitize_text_field( $status );
		$message = sanitize_textarea_field( $message );
		
		// Get client information
		$ip_address = $this->get_client_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';
		
		// Prepare data for logging
		$log_data = array(
			'email' => $email,
			'action' => $action,
			'status' => $status,
			'message' => $message,
			'ip_address' => $ip_address,
			'user_agent' => $user_agent,
			'request_data' => isset( $additional_data['request'] ) ? wp_json_encode( $additional_data['request'] ) : null,
			'response_data' => isset( $additional_data['response'] ) ? wp_json_encode( $additional_data['response'] ) : null,
			'keycloak_user_id' => isset( $additional_data['keycloak_user_id'] ) ? sanitize_text_field( $additional_data['keycloak_user_id'] ) : null,
			'keycloak_realm' => isset( $additional_data['keycloak_realm'] ) ? sanitize_text_field( $additional_data['keycloak_realm'] ) : null,
		);
		
		// Insert log entry
		$result = $wpdb->insert( $this->table_name, $log_data );
		
		if ( $result === false ) {
			error_log( 'Monikit GDPR: Failed to log deletion action: ' . $wpdb->last_error );
			return false;
		}
		
		return $wpdb->insert_id;
	}

	/**
	 * Get client IP address
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	string	IP address
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
		
		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
	}

	/**
	 * Get logs with pagination and filtering
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	array	$args	Query arguments
	 * @return	array	Logs and pagination info
	 */
	public function get_logs( $args = array() ) {
		global $wpdb;
		
		$defaults = array(
			'page' => 1,
			'per_page' => 20,
			'email' => '',
			'action' => '',
			'status' => '',
			'date_from' => '',
			'date_to' => '',
			'orderby' => 'created_at',
			'order' => 'DESC'
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		// Build WHERE clause
		$where_conditions = array();
		$where_values = array();
		
		if ( ! empty( $args['email'] ) ) {
			$where_conditions[] = 'email LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['email'] ) . '%';
		}
		
		if ( ! empty( $args['action'] ) ) {
			$where_conditions[] = 'action = %s';
			$where_values[] = $args['action'];
		}
		
		if ( ! empty( $args['status'] ) ) {
			$where_conditions[] = 'status = %s';
			$where_values[] = $args['status'];
		}
		
		if ( ! empty( $args['date_from'] ) ) {
			$where_conditions[] = 'created_at >= %s';
			$where_values[] = $args['date_from'] . ' 00:00:00';
		}
		
		if ( ! empty( $args['date_to'] ) ) {
			$where_conditions[] = 'created_at <= %s';
			$where_values[] = $args['date_to'] . ' 23:59:59';
		}
		
		$where_clause = '';
		if ( ! empty( $where_conditions ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}
		
		// Validate orderby
		$allowed_orderby = array( 'id', 'email', 'action', 'status', 'created_at' );
		$orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'created_at';
		
		// Validate order
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		
		// Get total count
		$count_query = "SELECT COUNT(*) FROM {$this->table_name} {$where_clause}";
		if ( ! empty( $where_values ) ) {
			$count_query = $wpdb->prepare( $count_query, $where_values );
		}
		$total_items = $wpdb->get_var( $count_query );
		
		// Calculate pagination
		$total_pages = ceil( $total_items / $args['per_page'] );
		$offset = ( $args['page'] - 1 ) * $args['per_page'];
		
		// Get logs
		$query = "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$query_values = array_merge( $where_values, array( $args['per_page'], $offset ) );
		
		if ( ! empty( $query_values ) ) {
			$query = $wpdb->prepare( $query, $query_values );
		}
		
		$logs = $wpdb->get_results( $query );
		
		return array(
			'logs' => $logs,
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'current_page' => $args['page'],
			'per_page' => $args['per_page']
		);
	}

	/**
	 * Get log by ID
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	int	$log_id	Log ID
	 * @return	object|null	Log entry or null
	 */
	public function get_log( $log_id ) {
		global $wpdb;
		
		$log_id = intval( $log_id );
		
		return $wpdb->get_row( $wpdb->prepare( 
			"SELECT * FROM {$this->table_name} WHERE id = %d", 
			$log_id 
		) );
	}

	/**
	 * Get count of logs older than specified days
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	int	$days	Days threshold
	 * @return	int	Number of logs older than threshold
	 */
	public function get_logs_count_older_than( $days ) {
		global $wpdb;
		
		$days = intval( $days );
		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		
		$count = $wpdb->get_var( $wpdb->prepare( 
			"SELECT COUNT(*) FROM {$this->table_name} WHERE created_at < %s", 
			$cutoff_date 
		) );
		
		return intval( $count );
	}

	/**
	 * Delete old logs (cleanup)
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	int	$days	Days to keep logs
	 * @return	int	Number of deleted logs
	 */
	public function cleanup_old_logs( $days = 365 ) {
		global $wpdb;
		
		$days = intval( $days );
		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		
		$deleted = $wpdb->query( $wpdb->prepare( 
			"DELETE FROM {$this->table_name} WHERE created_at < %s", 
			$cutoff_date 
		) );
		
		return $deleted;
	}

	/**
	 * Delete logs by IDs
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	array	$log_ids	Array of log IDs to delete
	 * @return	int	Number of deleted logs
	 */
	public function delete_logs_by_ids( $log_ids ) {
		global $wpdb;
		
		if ( empty( $log_ids ) || ! is_array( $log_ids ) ) {
			return 0;
		}
		
		// Sanitize log IDs
		$log_ids = array_map( 'intval', $log_ids );
		$log_ids = array_filter( $log_ids );
		
		if ( empty( $log_ids ) ) {
			return 0;
		}
		
		// Create placeholders for IN clause
		$placeholders = implode( ',', array_fill( 0, count( $log_ids ), '%d' ) );
		
		$deleted = $wpdb->query( $wpdb->prepare( 
			"DELETE FROM {$this->table_name} WHERE id IN ({$placeholders})", 
			$log_ids
		) );
		
		return $deleted;
	}

	/**
	 * Delete single log by ID
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	int	$log_id	Log ID to delete
	 * @return	bool	Success status
	 */
	public function delete_log_by_id( $log_id ) {
		global $wpdb;
		
		$log_id = intval( $log_id );
		
		if ( ! $log_id ) {
			return false;
		}
		
		$deleted = $wpdb->delete( 
			$this->table_name, 
			array( 'id' => $log_id ), 
			array( '%d' )
		);
		
		return $deleted !== false;
	}

	/**
	 * Update log status by email and action
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	string	$email	Email address
	 * @param	string	$action	Action type
	 * @param	string	$new_status	New status
	 * @param	string	$new_message	New message
	 * @return	bool	Success status
	 */
	public function update_log_status( $email, $action, $new_status, $new_message = '' ) {
		global $wpdb;
		
		$email = sanitize_email( $email );
		$action = sanitize_text_field( $action );
		$new_status = sanitize_text_field( $new_status );
		$new_message = sanitize_textarea_field( $new_message );
		
		if ( empty( $email ) || empty( $action ) || empty( $new_status ) ) {
			return false;
		}
		
		$updated = $wpdb->update( 
			$this->table_name, 
			array( 
				'status' => $new_status,
				'message' => $new_message
			), 
			array( 
				'email' => $email,
				'action' => $action
			), 
			array( '%s', '%s' ), 
			array( '%s', '%s' )
		);
		
		return $updated !== false;
	}

	/**
	 * Get log statistics
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	string	$period	Period (today, week, month, year)
	 * @return	array	Statistics
	 */
	public function get_statistics( $period = 'month' ) {
		global $wpdb;
		
		$date_format = '%Y-%m-%d';
		$group_by = 'DATE(created_at)';
		
		switch ( $period ) {
			case 'today':
				$where_date = 'DATE(created_at) = CURDATE()';
				break;
			case 'week':
				$where_date = 'created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
				break;
			case 'month':
				$where_date = 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
				break;
			case 'year':
				$where_date = 'created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
				$date_format = '%Y-%m';
				$group_by = 'DATE_FORMAT(created_at, "%Y-%m")';
				break;
			default:
				$where_date = 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
		}
		
		// Get action statistics
		$action_stats = $wpdb->get_results( $wpdb->prepare( "
			SELECT 
				action,
				status,
				COUNT(*) as count
			FROM {$this->table_name}
			WHERE {$where_date}
			GROUP BY action, status
			ORDER BY action, status
		" ) );
		
		// Get daily/monthly trends
		$trends = $wpdb->get_results( $wpdb->prepare( "
			SELECT 
				DATE_FORMAT(created_at, %s) as date,
				COUNT(*) as count
			FROM {$this->table_name}
			WHERE {$where_date}
			GROUP BY {$group_by}
			ORDER BY date
		", $date_format ) );
		
		// Get total counts
		$totals = $wpdb->get_row( $wpdb->prepare( "
			SELECT 
				COUNT(*) as total,
				COUNT(CASE WHEN status = 'success' THEN 1 END) as successful,
				COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
				COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
				COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
			FROM {$this->table_name}
			WHERE {$where_date}
		" ) );
		
		return array(
			'action_stats' => $action_stats,
			'trends' => $trends,
			'totals' => $totals,
			'period' => $period
		);
	}

	/**
	 * Export logs to CSV
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	array	$args	Query arguments
	 * @return	string	CSV content
	 */
	public function export_csv( $args = array() ) {
		$logs_data = $this->get_logs( $args );
		$logs = $logs_data['logs'];
		
		$csv_data = array();
		
		// Add headers
		$csv_data[] = array(
			'ID',
			'Email',
			'Action',
			'Status',
			'Message',
			'IP Address',
			'Keycloak User ID',
			'Keycloak Realm',
			'Created At'
		);
		
		// Add data rows
		foreach ( $logs as $log ) {
			$csv_data[] = array(
				$log->id,
				$log->email,
				$log->action,
				$log->status,
				$log->message,
				$log->ip_address,
				$log->keycloak_user_id,
				$log->keycloak_realm,
				$log->created_at
			);
		}
		
		// Generate CSV
		$output = fopen( 'php://temp', 'r+' );
		foreach ( $csv_data as $row ) {
			fputcsv( $output, $row );
		}
		rewind( $output );
		$csv = stream_get_contents( $output );
		fclose( $output );
		
		return $csv;
	}

	/**
	 * Get action labels for display
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	array	Action labels
	 */
	public function get_action_labels() {
		return array(
			'request' => __( 'Deletion Request', 'monikit-app-gdpr-user-data-deletion' ),
			'confirmation' => __( 'Email Confirmation', 'monikit-app-gdpr-user-data-deletion' ),
			'deletion' => __( 'Keycloak Deletion', 'monikit-app-gdpr-user-data-deletion' ),
			'error' => __( 'Error', 'monikit-app-gdpr-user-data-deletion' ),
			'expired' => __( 'Expired Request', 'monikit-app-gdpr-user-data-deletion' )
		);
	}

	/**
	 * Get status labels for display
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	array	Status labels
	 */
	public function get_status_labels() {
		return array(
			'success' => __( 'Success', 'monikit-app-gdpr-user-data-deletion' ),
			'failed' => __( 'Failed', 'monikit-app-gdpr-user-data-deletion' ),
			'pending' => __( 'Pending', 'monikit-app-gdpr-user-data-deletion' ),
			'completed' => __( 'Completed', 'monikit-app-gdpr-user-data-deletion' )
		);
	}

	/**
	 * Get status colors for display
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	array	Status colors
	 */
	public function get_status_colors() {
		return array(
			'success' => '#28a745',
			'failed' => '#dc3545',
			'pending' => '#ffc107',
			'completed' => '#17a2b8'
		);
	}

} 