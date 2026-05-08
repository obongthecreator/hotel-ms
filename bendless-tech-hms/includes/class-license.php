<?php
/**
 * Licensing, tenant resolution, and plan feature gates.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable wrapper for a hotel license row.
 */
class HRM_License_Record {

	/**
	 * Raw hotel row values.
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Constructor.
	 *
	 * @param object|array $row Hotel row.
	 */
	public function __construct( $row ) {
		$this->data = (array) $row;
	}

	/**
	 * Magic property access for database fields.
	 *
	 * @param string $key Field key.
	 * @return mixed|null
	 */
	public function __get( $key ) {
		return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : null;
	}

	/**
	 * Magic isset support for database fields.
	 *
	 * @param string $key Field key.
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->data[ $key ] );
	}

	/**
	 * Determine whether the hotel license is currently usable.
	 *
	 * @return bool
	 */
	public function is_active() {
		if ( empty( $this->data['is_active'] ) ) {
			return false;
		}

		$status = isset( $this->data['subscription_status'] ) ? sanitize_key( $this->data['subscription_status'] ) : '';

		if ( 'enterprise' === $this->plan && 'active' === $status ) {
			return true;
		}

		if ( 'trial' === $status ) {
			return $this->date_is_future_or_today( $this->trial_ends_at );
		}

		if ( 'active' === $status ) {
			if ( empty( $this->subscription_ends_at ) ) {
				return true;
			}

			return $this->date_is_future_or_today( $this->subscription_ends_at );
		}

		return false;
	}

	/**
	 * Return the most relevant expiry date.
	 *
	 * @return string
	 */
	public function expires_at() {
		if ( 'trial' === $this->subscription_status && ! empty( $this->trial_ends_at ) ) {
			return $this->trial_ends_at;
		}

		return (string) $this->subscription_ends_at;
	}

	/**
	 * Return the number of days since expiry.
	 *
	 * @return int
	 */
	public function days_expired() {
		$expires_at = $this->expires_at();

		if ( empty( $expires_at ) ) {
			return 0;
		}

		$expiry_timestamp = strtotime( $expires_at );
		if ( ! $expiry_timestamp ) {
			return 0;
		}

		$now = current_time( 'timestamp' );
		if ( $expiry_timestamp >= $now ) {
			return 0;
		}

		return (int) floor( ( $now - $expiry_timestamp ) / DAY_IN_SECONDS );
	}

	/**
	 * Return the raw row as an array.
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->data;
	}

	/**
	 * Check whether a date is today or in the future.
	 *
	 * @param string|null $date Date string.
	 * @return bool
	 */
	private function date_is_future_or_today( $date ) {
		if ( empty( $date ) ) {
			return false;
		}

		$timestamp = strtotime( $date );
		if ( ! $timestamp ) {
			return false;
		}

		return $timestamp >= strtotime( current_time( 'Y-m-d 00:00:00' ) );
	}
}

/**
 * Central plan and license helper.
 */
class HRM_License {

	/**
	 * Ordered plan slugs.
	 *
	 * @var string[]
	 */
	private static $plan_order = array( 'basic', 'standard', 'pro', 'enterprise' );

	/**
	 * Determine if the current hotel can access a feature.
	 *
	 * @param string $feature Feature slug.
	 * @return bool
	 */
	public static function can( $feature ) {
		$license = self::get_current_license();

		if ( ! $license || ! $license->is_active() ) {
			return false;
		}

		return self::hotel_can( (int) $license->id, $feature );
	}

	/**
	 * Determine if a specific hotel can access a feature.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $feature Feature slug.
	 * @return bool
	 */
	public static function hotel_can( $hotel_id, $feature ) {
		$hotel_id = absint( $hotel_id );
		$feature  = sanitize_key( $feature );

		if ( ! $hotel_id || '' === $feature ) {
			return false;
		}

		$hotel = self::get_hotel( $hotel_id );
		if ( ! $hotel || ! $hotel->is_active() ) {
			return false;
		}

		$plan = self::get_plan( $hotel->plan );
		if ( ! $plan ) {
			return false;
		}

		if ( array( '*' ) === $plan['features'] ) {
			return true;
		}

		if ( in_array( $feature, $plan['features'], true ) ) {
			return true;
		}

		// Fallback: check the hardcoded default plan so that features added to
		// HRM_PLANS after a site already saved its plan config still work.
		$plan_slug    = sanitize_key( $hotel->plan );
		$default_plan = isset( HRM_PLANS[ $plan_slug ] ) ? HRM_PLANS[ $plan_slug ] : null;
		if ( $default_plan ) {
			if ( array( '*' ) === $default_plan['features'] ) {
				return true;
			}
			if ( in_array( $feature, $default_plan['features'], true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return the current hotel license row.
	 *
	 * @return HRM_License_Record|false
	 */
	public static function get_current_license() {
		$hotel_id = self::get_current_hotel_id();

		if ( ! $hotel_id ) {
			return false;
		}

		return self::get_hotel( $hotel_id );
	}

	/**
	 * Resolve the current hotel ID.
	 *
	 * @return int
	 */
	public static function get_current_hotel_id() {
		if ( PHP_SESSION_ACTIVE === session_status() && is_super_admin() && isset( $_SESSION['hrm_impersonate_hotel_id'] ) ) {
			return absint( $_SESSION['hrm_impersonate_hotel_id'] );
		}

		global $wpdb;

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return 0;
		}

		$owner_hotel_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}hrm_hotels WHERE user_id = %d LIMIT 1",
				$user_id
			)
		);

		if ( $owner_hotel_id ) {
			return $owner_hotel_id;
		}

		$staff_hotel_id = absint( get_user_meta( $user_id, 'hrm_hotel_id', true ) );
		if ( $staff_hotel_id ) {
			$exists = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}hrm_hotels WHERE id = %d LIMIT 1",
					$staff_hotel_id
				)
			);

			return $exists ? $staff_hotel_id : 0;
		}

		return 0;
	}

	/**
	 * Fetch a hotel row by ID.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return HRM_License_Record|false
	 */
	public static function get_hotel( $hotel_id ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		if ( ! $hotel_id ) {
			return false;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hrm_hotels WHERE id = %d LIMIT 1",
				$hotel_id
			)
		);

		return $row ? new HRM_License_Record( $row ) : false;
	}

	/**
	 * Fetch a hotel row by slug.
	 *
	 * @param string $slug Hotel slug.
	 * @return HRM_License_Record|false
	 */
	public static function get_hotel_by_slug( $slug ) {
		global $wpdb;

		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return false;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hrm_hotels WHERE hotel_slug = %s LIMIT 1",
				$slug
			)
		);

		return $row ? new HRM_License_Record( $row ) : false;
	}

	/**
	 * Return the active plan configuration.
	 *
	 * @return array
	 */
	public static function get_plans() {
		$default_plans = HRM_PLANS;
		$config        = get_option( 'hrm_plan_config', array() );

		if ( ! is_array( $config ) || empty( $config ) ) {
			return $default_plans;
		}

		$sanitized = self::sanitize_plan_config( $config, $default_plans );

		return wp_parse_args( $sanitized, $default_plans );
	}

	/**
	 * Return a single active plan config.
	 *
	 * @param string $plan_slug Plan slug.
	 * @return array|null
	 */
	public static function get_plan( $plan_slug ) {
		$plans     = self::get_plans();
		$plan_slug = sanitize_key( $plan_slug );

		return isset( $plans[ $plan_slug ] ) ? $plans[ $plan_slug ] : null;
	}

	/**
	 * Return ordered plan slugs.
	 *
	 * @return string[]
	 */
	public static function get_plan_order() {
		return self::$plan_order;
	}

	/**
	 * Return all feature slugs that can be assigned to non-enterprise plans.
	 *
	 * @return string[]
	 */
	public static function allowed_features() {
		return array(
			'room_management',
			'basic_bookings',
			'invoices',
			'dashboard',
			'frontend_booking',
			'room_analytics',
			'guest_management',
			'whatsapp_notifications',
			'shift_reports',
			'multi_payment',
			'weekend_pricing',
			'revenue_leakage',
			'advanced_reports',
			'guest_blacklist',
			'id_scanner',
			'housekeeping',
			'peak_season_pricing',
			'csv_export',
		);
	}

	/**
	 * Human labels for feature slugs.
	 *
	 * @return array
	 */
	public static function feature_labels() {
		return array(
			'room_management'        => __( 'Room management', 'hrm-pro' ),
			'basic_bookings'         => __( 'Booking management', 'hrm-pro' ),
			'invoices'               => __( 'Invoices and receipts', 'hrm-pro' ),
			'dashboard'              => __( 'Live dashboard', 'hrm-pro' ),
			'frontend_booking'       => __( 'Frontend booking page', 'hrm-pro' ),
			'room_analytics'         => __( 'Room analytics', 'hrm-pro' ),
			'guest_management'       => __( 'Guest records', 'hrm-pro' ),
			'whatsapp_notifications' => __( 'WhatsApp notifications', 'hrm-pro' ),
			'shift_reports'          => __( 'End-of-shift reports', 'hrm-pro' ),
			'multi_payment'          => __( 'Multiple payment methods', 'hrm-pro' ),
			'weekend_pricing'        => __( 'Weekend pricing', 'hrm-pro' ),
			'revenue_leakage'        => __( 'Revenue leakage detection', 'hrm-pro' ),
			'advanced_reports'       => __( 'Advanced reports', 'hrm-pro' ),
			'guest_blacklist'        => __( 'Guest blacklist', 'hrm-pro' ),
			'id_scanner'             => __( 'ID scanner', 'hrm-pro' ),
			'housekeeping'           => __( 'Housekeeping updates', 'hrm-pro' ),
			'peak_season_pricing'    => __( 'Peak season pricing', 'hrm-pro' ),
			'csv_export'             => __( 'CSV export', 'hrm-pro' ),
		);
	}

	/**
	 * Find the minimum plan that includes a feature.
	 *
	 * @param string $feature Feature slug.
	 * @return string
	 */
	public static function minimum_plan_for_feature( $feature ) {
		$feature = sanitize_key( $feature );
		$plans   = self::get_plans();

		foreach ( self::$plan_order as $slug ) {
			if ( empty( $plans[ $slug ] ) ) {
				continue;
			}

			$plan = $plans[ $slug ];
			if ( array( '*' ) === $plan['features'] || in_array( $feature, $plan['features'], true ) ) {
				return $plan['name'];
			}
		}

		return __( 'Enterprise', 'hrm-pro' );
	}

	/**
	 * Return a readable plan name.
	 *
	 * @param string $plan_slug Plan slug.
	 * @return string
	 */
	public static function plan_name( $plan_slug ) {
		$plan = self::get_plan( $plan_slug );

		return $plan ? $plan['name'] : ucfirst( sanitize_key( $plan_slug ) );
	}

	/**
	 * Return sales-facing plan advantages.
	 *
	 * @param string $plan_slug Plan slug.
	 * @return string[]
	 */
	public static function plan_advantages( $plan_slug ) {
		$plan_slug = sanitize_key( $plan_slug );

		switch ( $plan_slug ) {
			case 'basic':
				return array(
					__( 'Best for small hotels starting digital room control.', 'hrm-pro' ),
					__( 'Rooms, bookings, invoices, dashboard, and frontend booking pages.', 'hrm-pro' ),
					__( 'Simple setup with controlled staff access.', 'hrm-pro' ),
				);
			case 'standard':
				return array(
					__( 'Adds bank transfer and other non-cash payment workflows.', 'hrm-pro' ),
					__( 'Unlocks guest records, WhatsApp notifications, staff shift reporting, weekend pricing, and room analytics.', 'hrm-pro' ),
					__( 'Ideal for growing hotels that need owner visibility without advanced loss analysis.', 'hrm-pro' ),
				);
			case 'pro':
				return array(
					__( 'Unlocks detailed explanations behind room performance and revenue impact.', 'hrm-pro' ),
					__( 'Adds leakage detection, blacklist controls, housekeeping, peak-season pricing, CSV exports, and AI insight prompts.', 'hrm-pro' ),
					__( 'Built for managers who want to improve sales and reduce operational losses.', 'hrm-pro' ),
				);
			case 'enterprise':
				return array(
					__( 'Unlimited rooms and staff with every HMS feature unlocked.', 'hrm-pro' ),
					__( 'Best for groups, premium properties, and platform-managed hotel portfolios.', 'hrm-pro' ),
					__( 'Includes full analytics, AI insight, reporting, and operational controls.', 'hrm-pro' ),
				);
			default:
				return array();
		}
	}

	/**
	 * Return whether the super admin is viewing a tenant.
	 *
	 * @return bool
	 */
	public static function is_impersonating() {
		return PHP_SESSION_ACTIVE === session_status() && is_super_admin() && ! empty( $_SESSION['hrm_impersonate_hotel_id'] );
	}

	/**
	 * Build expiry context for a hotel.
	 *
	 * @param HRM_License_Record|false $hotel Hotel license.
	 * @return array
	 */
	public static function get_expiry_context( $hotel ) {
		if ( ! $hotel ) {
			return array(
				'plan_name'     => __( 'No plan', 'hrm-pro' ),
				'expiry_date'   => '',
				'days_expired'  => 0,
				'status'        => __( 'Unavailable', 'hrm-pro' ),
				'is_active'     => false,
				'expiry_display'=> __( 'No expiry date', 'hrm-pro' ),
			);
		}

		$expires_at = $hotel->expires_at();

		return array(
			'plan_name'      => self::plan_name( $hotel->plan ),
			'expiry_date'    => $expires_at,
			'days_expired'   => $hotel->days_expired(),
			'status'         => ucfirst( sanitize_key( $hotel->subscription_status ) ),
			'is_active'      => $hotel->is_active(),
			'expiry_display' => $expires_at ? date_i18n( get_option( 'date_format' ), strtotime( $expires_at ) ) : __( 'No expiry date', 'hrm-pro' ),
		);
	}

	/**
	 * Format a Naira amount consistently.
	 *
	 * @param float|int|string $amount Amount.
	 * @return string
	 */
	public static function money( $amount ) {
		return '₦' . number_format( (float) $amount, 2 );
	}

	/**
	 * Return a CSS color class set for a plan.
	 *
	 * @param string $plan_slug Plan slug.
	 * @return string
	 */
	public static function plan_badge_classes( $plan_slug ) {
		$plan_slug = sanitize_key( $plan_slug );

		switch ( $plan_slug ) {
			case 'standard':
				return 'bg-blue-900 text-blue-300';
			case 'pro':
				return 'bg-purple-900 text-purple-300';
			case 'enterprise':
				return 'bg-amber-900 text-amber-300';
			case 'basic':
			default:
				return 'bg-slate-700 text-slate-300';
		}
	}

	/**
	 * Sanitize and normalize a plan configuration array.
	 *
	 * @param array $config        Submitted config.
	 * @param array $default_plans Default config.
	 * @return array
	 */
	public static function sanitize_plan_config( $config, $default_plans = array() ) {
		$default_plans = $default_plans ? $default_plans : HRM_PLANS;
		$allowed       = self::allowed_features();
		$output        = array();

		foreach ( self::$plan_order as $slug ) {
			$base = isset( $default_plans[ $slug ] ) ? $default_plans[ $slug ] : array();
			$row  = isset( $config[ $slug ] ) && is_array( $config[ $slug ] ) ? $config[ $slug ] : array();

			$features = isset( $row['features'] ) && is_array( $row['features'] ) ? array_map( 'sanitize_key', $row['features'] ) : $base['features'];
			if ( 'enterprise' === $slug && in_array( '*', $features, true ) ) {
				$features = array( '*' );
			} else {
				$features = array_values( array_intersect( $allowed, $features ) );
			}

			$output[ $slug ] = array(
				'name'          => isset( $base['name'] ) ? $base['name'] : ucfirst( $slug ),
				'price_monthly' => isset( $row['price_monthly'] ) ? max( 0, (int) $row['price_monthly'] ) : (int) $base['price_monthly'],
				'price_yearly'  => isset( $row['price_yearly'] ) ? max( 0, (int) $row['price_yearly'] ) : (int) $base['price_yearly'],
				'renewal_yearly'=> isset( $row['renewal_yearly'] ) ? max( 0, (int) $row['renewal_yearly'] ) : ( isset( $base['renewal_yearly'] ) ? (int) $base['renewal_yearly'] : (int) $base['price_yearly'] ),
				'rooms_limit'   => isset( $row['rooms_limit'] ) ? (int) $row['rooms_limit'] : (int) $base['rooms_limit'],
				'staff_limit'   => isset( $row['staff_limit'] ) ? (int) $row['staff_limit'] : (int) $base['staff_limit'],
				'features'      => $features,
			);
		}

		return $output;
	}

	/**
	 * Assign HMS owner capabilities to a WordPress user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return void
	 */
	public static function assign_owner_capabilities( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return;
		}

		$user = new WP_User( $user_id );
		foreach ( array( 'hrm_manage_bookings', 'hrm_manage_rooms', 'hrm_manage_guests', 'hrm_view_reports', 'hrm_manage_settings' ) as $capability ) {
			$user->add_cap( $capability );
		}
	}
}
