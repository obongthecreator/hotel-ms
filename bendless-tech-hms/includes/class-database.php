<?php
/**
 * Database schema and installation utilities.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database tables, roles, and plugin defaults.
 */
class HRM_Database {

	/**
	 * Return the fully-qualified table name for a plugin table suffix.
	 *
	 * @param string $suffix Table suffix without the hrm_ prefix.
	 * @return string
	 */
	public static function table( $suffix ) {
		global $wpdb;

		return $wpdb->prefix . 'hrm_' . sanitize_key( $suffix );
	}

	/**
	 * Create or update all plugin tables.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$hotels_table        = self::table( 'hotels' );
		$rooms_table         = self::table( 'rooms' );
		$guests_table        = self::table( 'guests' );
		$bookings_table      = self::table( 'bookings' );
		$subscriptions_table = self::table( 'subscriptions' );
		$activity_table      = self::table( 'activity_log' );
		$settings_table      = self::table( 'settings' );

		$sql_statements = array(
			"CREATE TABLE {$hotels_table} (
				id  BIGINT(20) NOT NULL AUTO_INCREMENT,
				user_id  BIGINT(20) NOT NULL,
				hotel_name  VARCHAR(150) NOT NULL,
				hotel_slug  VARCHAR(100) NOT NULL,
				hotel_email  VARCHAR(100) DEFAULT '',
				hotel_phone  VARCHAR(30) DEFAULT '',
				hotel_address  TEXT,
				logo_url  VARCHAR(255) DEFAULT '',
				plan  VARCHAR(30) NOT NULL DEFAULT 'basic',
				subscription_status  VARCHAR(20) NOT NULL DEFAULT 'trial',
				trial_ends_at  DATETIME DEFAULT NULL,
				subscription_ends_at  DATETIME DEFAULT NULL,
				paystack_customer_id  VARCHAR(100) DEFAULT '',
				paystack_sub_code  VARCHAR(100) DEFAULT '',
				is_active  TINYINT(1) NOT NULL DEFAULT 1,
				created_at  DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY hotel_slug (hotel_slug),
				KEY user_id (user_id)
			) {$charset_collate};",
			"CREATE TABLE {$rooms_table} (
				id  BIGINT(20) NOT NULL AUTO_INCREMENT,
				hotel_id  BIGINT(20) NOT NULL,
				room_number  VARCHAR(20) NOT NULL,
				room_type  VARCHAR(50) NOT NULL DEFAULT 'single',
				floor  INT(11) NOT NULL DEFAULT 1,
				price_per_night  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				weekend_rate  DECIMAL(10,2) DEFAULT NULL,
				peak_rate  DECIMAL(10,2) DEFAULT NULL,
				max_guests  INT(11) NOT NULL DEFAULT 2,
				status  VARCHAR(20) NOT NULL DEFAULT 'available',
				description  TEXT,
				amenities  TEXT,
				image_urls  TEXT,
				created_at  DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY hotel_id (hotel_id)
			) {$charset_collate};",
			"CREATE TABLE {$guests_table} (
				id  BIGINT(20) NOT NULL AUTO_INCREMENT,
				hotel_id  BIGINT(20) NOT NULL,
				full_name  VARCHAR(100) NOT NULL,
				phone  VARCHAR(20) NOT NULL,
				email  VARCHAR(100) DEFAULT '',
				id_type  VARCHAR(30) DEFAULT '',
				id_number  VARCHAR(50) DEFAULT '',
				flag  VARCHAR(20) DEFAULT 'none',
				flag_reason  TEXT,
				notes  TEXT,
				created_at  DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY hotel_id (hotel_id),
				KEY phone (phone)
			) {$charset_collate};",
			"CREATE TABLE {$bookings_table} (
				id  BIGINT(20) NOT NULL AUTO_INCREMENT,
				hotel_id  BIGINT(20) NOT NULL,
				room_id  BIGINT(20) NOT NULL,
				guest_id  BIGINT(20) NOT NULL,
				booking_source  VARCHAR(20) NOT NULL DEFAULT 'admin',
				check_in  DATE NOT NULL,
				check_out  DATE NOT NULL,
				total_nights  INT(11) NOT NULL DEFAULT 1,
				rate_per_night  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				subtotal  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				vat_rate  DECIMAL(5,2) NOT NULL DEFAULT 7.50,
				vat_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				total_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				payment_method  VARCHAR(30) NOT NULL DEFAULT 'cash',
				payment_status  VARCHAR(20) NOT NULL DEFAULT 'unpaid',
				amount_paid  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				balance  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				transfer_ref  VARCHAR(100) DEFAULT '',
				paystack_reference  VARCHAR(100) DEFAULT '',
				status  VARCHAR(20) NOT NULL DEFAULT 'confirmed',
				notes  TEXT,
				created_by  BIGINT(20) DEFAULT NULL,
				created_at  DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY hotel_id (hotel_id),
				KEY room_id (room_id),
				KEY guest_id (guest_id),
				KEY check_in (check_in),
				KEY check_out (check_out)
			) {$charset_collate};",
			"CREATE TABLE {$subscriptions_table} (
				id  BIGINT(20) NOT NULL AUTO_INCREMENT,
				hotel_id  BIGINT(20) NOT NULL,
				plan  VARCHAR(30) NOT NULL,
				billing_cycle  VARCHAR(10) NOT NULL DEFAULT 'monthly',
				amount_paid  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				paystack_reference  VARCHAR(100) DEFAULT '',
				paystack_sub_code  VARCHAR(100) DEFAULT '',
				starts_at  DATETIME NOT NULL,
				ends_at  DATETIME NOT NULL,
				status  VARCHAR(20) NOT NULL DEFAULT 'active',
				is_test  TINYINT(1) NOT NULL DEFAULT 0,
				created_at  DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY hotel_id (hotel_id)
			) {$charset_collate};",
			"CREATE TABLE {$activity_table} (
				id  BIGINT(20) NOT NULL AUTO_INCREMENT,
				hotel_id  BIGINT(20) NOT NULL,
				user_id  BIGINT(20) NOT NULL,
				action  VARCHAR(100) NOT NULL,
				entity_type  VARCHAR(50) DEFAULT '',
				entity_id  BIGINT(20) DEFAULT NULL,
				details  TEXT,
				created_at  DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY hotel_id (hotel_id),
				KEY created_at (created_at)
			) {$charset_collate};",
			"CREATE TABLE {$settings_table} (
				hotel_id  BIGINT(20) NOT NULL,
				setting_key  VARCHAR(100) NOT NULL,
				setting_value  TEXT,
				PRIMARY KEY  (hotel_id, setting_key)
			) {$charset_collate};",
		);

		foreach ( $sql_statements as $sql ) {
			dbDelta( $sql );
		}

		update_option( 'hrm_db_version', HRM_VERSION, false );
	}

	/**
	 * Register custom roles and capabilities.
	 *
	 * @return void
	 */
	public static function create_roles() {
		$admin_caps = array(
			'hrm_manage_bookings',
			'hrm_manage_rooms',
			'hrm_manage_guests',
			'hrm_view_reports',
			'hrm_manage_settings',
			'hrm_manage_platform',
		);

		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( $admin_caps as $capability ) {
				$administrator->add_cap( $capability );
			}
		}

		if ( ! get_role( 'hrm_receptionist' ) ) {
			add_role(
				'hrm_receptionist',
				__( 'HMS Receptionist', 'hrm-pro' ),
				array(
					'read'                => true,
					'hrm_manage_bookings' => true,
					'hrm_manage_rooms'    => true,
					'hrm_manage_guests'   => true,
				)
			);
		}

		$receptionist = get_role( 'hrm_receptionist' );
		if ( $receptionist ) {
			foreach ( array( 'read', 'hrm_manage_bookings', 'hrm_manage_rooms', 'hrm_manage_guests' ) as $capability ) {
				$receptionist->add_cap( $capability );
			}
		}
	}

	/**
	 * Remove custom roles and capabilities.
	 *
	 * @return void
	 */
	public static function remove_roles() {
		$admin_caps = array(
			'hrm_manage_bookings',
			'hrm_manage_rooms',
			'hrm_manage_guests',
			'hrm_view_reports',
			'hrm_manage_settings',
			'hrm_manage_platform',
		);

		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( $admin_caps as $capability ) {
				$administrator->remove_cap( $capability );
			}
		}

		remove_role( 'hrm_receptionist' );
	}

	/**
	 * Create baseline option values used by the engine.
	 *
	 * @return void
	 */
	public static function ensure_default_options() {
		$defaults = array(
			'hrm_test_mode'                  => 0,
			'hrm_paystack_public_key'        => '',
			'hrm_paystack_secret_key'        => '',
			'hrm_paystack_test_public_key'   => '',
			'hrm_paystack_test_secret_key'   => '',
			'hrm_platform_currency'          => 'NGN',
			'hrm_platform_currency_symbol'   => '₦',
			'hrm_platform_subscription_mode' => 'live',
			'hrm_platform_master_mode'       => 0,
		);

		foreach ( $defaults as $option => $value ) {
			if ( false === get_option( $option, false ) ) {
				add_option( $option, $value, '', false );
			}
		}
	}

	/**
	 * Delete a hotel and all tenant-owned data.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return bool
	 */
	public static function delete_hotel_data( $hotel_id ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		if ( ! $hotel_id ) {
			return false;
		}

		$tenant_tables = array(
			self::table( 'rooms' ),
			self::table( 'guests' ),
			self::table( 'bookings' ),
			self::table( 'subscriptions' ),
			self::table( 'activity_log' ),
			self::table( 'settings' ),
		);

		foreach ( $tenant_tables as $table ) {
			$wpdb->delete( $table, array( 'hotel_id' => $hotel_id ), array( '%d' ) );
		}

		$deleted = $wpdb->delete( self::table( 'hotels' ), array( 'id' => $hotel_id ), array( '%d' ) );

		return false !== $deleted;
	}

	/**
	 * Return all plugin table names.
	 *
	 * @return string[]
	 */
	public static function all_tables() {
		return array(
			self::table( 'hotels' ),
			self::table( 'rooms' ),
			self::table( 'guests' ),
			self::table( 'bookings' ),
			self::table( 'subscriptions' ),
			self::table( 'activity_log' ),
			self::table( 'settings' ),
		);
	}
}
