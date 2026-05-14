<?php
/**
 * Platform super admin interface.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and processes the HMS Platform screens.
 */
class HRM_Super_Admin {

	/**
	 * Paystack service.
	 *
	 * @var HRM_Paystack
	 */
	private $paystack;

	/**
	 * Constructor.
	 *
	 * @param HRM_Paystack $paystack Paystack service.
	 */
	public function __construct( HRM_Paystack $paystack ) {
		$this->paystack = $paystack;

		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_post_hrm_save_plan_config', array( $this, 'handle_save_plan_config' ) );
		add_action( 'admin_post_hrm_save_platform_settings', array( $this, 'handle_save_platform_settings' ) );
		add_action( 'wp_ajax_hrm_super_add_hotel', array( $this, 'ajax_add_hotel' ) );
		add_action( 'wp_ajax_hrm_super_update_hotel', array( $this, 'ajax_update_hotel' ) );
		add_action( 'wp_ajax_hrm_super_delete_hotel', array( $this, 'ajax_delete_hotel' ) );
		add_action( 'wp_ajax_hrm_super_toggle_subscription', array( $this, 'ajax_toggle_subscription' ) );
		add_action( 'wp_ajax_hrm_super_extend_subscription', array( $this, 'ajax_extend_subscription' ) );
		add_action( 'wp_ajax_hrm_super_impersonate', array( $this, 'ajax_impersonate' ) );
		add_action( 'wp_ajax_hrm_super_exit_impersonate', array( $this, 'ajax_exit_impersonate' ) );
		add_action( 'wp_ajax_hrm_super_subscription_action', array( $this, 'ajax_subscription_action' ) );
		add_action( 'wp_ajax_hrm_super_toggle_test_mode', array( $this, 'ajax_toggle_test_mode' ) );
	}

	/**
	 * Register platform menus.
	 *
	 * @return void
	 */
	public function register_menus() {
		if ( ! $this->platform_enabled() || ! is_super_admin() ) {
			return;
		}

		add_menu_page(
			__( 'HMS Platform', 'hrm-pro' ),
			__( 'HMS Platform', 'hrm-pro' ),
			'manage_options',
			'hrm-platform',
			array( $this, 'render_platform_dashboard' ),
			'dashicons-building',
			24
		);

		add_submenu_page(
			'hrm-platform',
			__( 'Platform Dashboard', 'hrm-pro' ),
			__( 'Dashboard', 'hrm-pro' ),
			'manage_options',
			'hrm-platform',
			array( $this, 'render_platform_dashboard' )
		);

		add_submenu_page(
			'hrm-platform',
			__( 'Hotels', 'hrm-pro' ),
			__( 'Hotels', 'hrm-pro' ),
			'manage_options',
			'hrm-platform-hotels',
			array( $this, 'render_hotels' )
		);

		add_submenu_page(
			'hrm-platform',
			__( 'Subscriptions', 'hrm-pro' ),
			__( 'Subscriptions', 'hrm-pro' ),
			'manage_options',
			'hrm-platform-subscriptions',
			array( $this, 'render_subscriptions' )
		);

		add_submenu_page(
			'hrm-platform',
			__( 'Plan Manager', 'hrm-pro' ),
			__( 'Plan Manager', 'hrm-pro' ),
			'manage_options',
			'hrm-platform-plans',
			array( $this, 'render_plan_manager' )
		);
	}

	/**
	 * Render the platform dashboard.
	 *
	 * @return void
	 */
	public function render_platform_dashboard() {
		$this->guard_super_page();

		$this->render_platform_page(
			HRM_PLUGIN_DIR . 'admin/views/super-admin/platform-dashboard.php',
			__( 'Platform Dashboard', 'hrm-pro' ),
			'platform',
			array(
				'stats'                => $this->get_platform_stats(),
				'monthly_revenue'      => $this->get_monthly_revenue(),
				'recent_subscriptions' => $this->get_recent_subscription_payments(),
				'expired_hotels'       => $this->get_expired_hotels(),
				'activity'             => HRM_Activity_Log::recent_platform( 8 ),
				'paystack_public'      => get_option( 'hrm_paystack_public_key', '' ),
				'paystack_secret'      => get_option( 'hrm_paystack_secret_key', '' ),
				'paystack_test_public' => get_option( 'hrm_paystack_test_public_key', '' ),
				'paystack_test_secret' => get_option( 'hrm_paystack_test_secret_key', '' ),
			)
		);
	}

	/**
	 * Render hotel management.
	 *
	 * @return void
	 */
	public function render_hotels() {
		$this->guard_super_page();

		$this->render_platform_page(
			HRM_PLUGIN_DIR . 'admin/views/super-admin/hotels.php',
			__( 'Hotels', 'hrm-pro' ),
			'hotels',
			array(
				'hotels' => $this->get_hotels(),
				'users'  => $this->get_user_options(),
				'plans'  => HRM_License::get_plans(),
			)
		);
	}

	/**
	 * Render subscription management.
	 *
	 * @return void
	 */
	public function render_subscriptions() {
		$this->guard_super_page();

		$filters = array(
			'plan'          => isset( $_GET['plan'] ) ? sanitize_key( wp_unslash( $_GET['plan'] ) ) : '',
			'status'        => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
			'billing_cycle' => isset( $_GET['billing_cycle'] ) ? sanitize_key( wp_unslash( $_GET['billing_cycle'] ) ) : '',
			'date_from'     => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
			'date_to'       => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
		);

		$this->render_platform_page(
			HRM_PLUGIN_DIR . 'admin/views/super-admin/subscriptions.php',
			__( 'Subscriptions', 'hrm-pro' ),
			'subscriptions',
			array(
				'subscriptions' => $this->get_subscriptions( $filters ),
				'filters'       => $filters,
				'plans'         => HRM_License::get_plans(),
			)
		);
	}

	/**
	 * Render plan manager.
	 *
	 * @return void
	 */
	public function render_plan_manager() {
		$this->guard_super_page();

		$this->render_platform_page(
			HRM_PLUGIN_DIR . 'admin/views/super-admin/plan-manager.php',
			__( 'Plan Manager', 'hrm-pro' ),
			'plans',
			array(
				'plans'          => HRM_License::get_plans(),
				'feature_labels' => HRM_License::feature_labels(),
			)
		);
	}

	/**
	 * Save plan manager configuration.
	 *
	 * @return void
	 */
	public function handle_save_plan_config() {
		$this->guard_super_post( 'hrm_save_plan_config' );

		$raw_plans = isset( $_POST['plans'] ) && is_array( $_POST['plans'] ) ? wp_unslash( $_POST['plans'] ) : array();
		$config    = HRM_License::sanitize_plan_config( $raw_plans );

		update_option( 'hrm_plan_config', $config, false );

		wp_safe_redirect( add_query_arg( 'hrm_notice', 'plans_saved', admin_url( 'admin.php?page=hrm-platform-plans' ) ) );
		exit;
	}

	/**
	 * Save platform payment settings.
	 *
	 * @return void
	 */
	public function handle_save_platform_settings() {
		$this->guard_super_post( 'hrm_save_platform_settings' );

		update_option( 'hrm_test_mode', isset( $_POST['hrm_test_mode'] ) ? 1 : 0, false );
		update_option( 'hrm_paystack_public_key', isset( $_POST['hrm_paystack_public_key'] ) ? sanitize_text_field( wp_unslash( $_POST['hrm_paystack_public_key'] ) ) : '', false );
		update_option( 'hrm_paystack_secret_key', isset( $_POST['hrm_paystack_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['hrm_paystack_secret_key'] ) ) : '', false );
		update_option( 'hrm_paystack_test_public_key', isset( $_POST['hrm_paystack_test_public_key'] ) ? sanitize_text_field( wp_unslash( $_POST['hrm_paystack_test_public_key'] ) ) : '', false );
		update_option( 'hrm_paystack_test_secret_key', isset( $_POST['hrm_paystack_test_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['hrm_paystack_test_secret_key'] ) ) : '', false );

		wp_safe_redirect( add_query_arg( 'hrm_notice', 'platform_saved', admin_url( 'admin.php?page=hrm-platform' ) ) );
		exit;
	}

	/**
	 * AJAX: add a hotel.
	 *
	 * @return void
	 */
	public function ajax_add_hotel() {
		$this->guard_super_ajax();

		global $wpdb;

		$hotel_name   = isset( $_POST['hotel_name'] ) ? sanitize_text_field( wp_unslash( $_POST['hotel_name'] ) ) : '';
		$user_id      = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
		$plan         = isset( $_POST['plan'] ) ? sanitize_key( wp_unslash( $_POST['plan'] ) ) : 'basic';
		$cycle        = isset( $_POST['billing_cycle'] ) && 'yearly' === sanitize_key( wp_unslash( $_POST['billing_cycle'] ) ) ? 'yearly' : 'monthly';
		$hotel_email  = isset( $_POST['hotel_email'] ) ? sanitize_email( wp_unslash( $_POST['hotel_email'] ) ) : '';
		$hotel_phone  = isset( $_POST['hotel_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['hotel_phone'] ) ) : '';
		$address      = isset( $_POST['hotel_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hotel_address'] ) ) : '';
		$end_date     = isset( $_POST['subscription_ends_at'] ) ? sanitize_text_field( wp_unslash( $_POST['subscription_ends_at'] ) ) : '';
		$plans        = HRM_License::get_plans();

		if ( '' === $hotel_name || ! $user_id || empty( $plans[ $plan ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Hotel name, owner, and plan are required.', 'hrm-pro' ) ), 400 );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => __( 'The selected owner does not exist.', 'hrm-pro' ) ), 400 );
		}

		$hotel_email = $hotel_email ? $hotel_email : $user->user_email;
		$ends_at     = $this->normalize_end_datetime( $end_date, $cycle, $plan );
		$slug        = $this->unique_hotel_slug( $hotel_name );
		$inserted    = $wpdb->insert(
			HRM_Database::table( 'hotels' ),
			array(
				'user_id'              => $user_id,
				'hotel_name'           => $hotel_name,
				'hotel_slug'           => $slug,
				'hotel_email'          => $hotel_email,
				'hotel_phone'          => $hotel_phone,
				'hotel_address'        => $address,
				'plan'                 => $plan,
				'subscription_status'  => 'active',
				'trial_ends_at'        => null,
				'subscription_ends_at' => $ends_at,
				'is_active'            => 1,
				'created_at'           => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		if ( ! $inserted ) {
			wp_send_json_error( array( 'message' => __( 'The hotel could not be created.', 'hrm-pro' ) ), 500 );
		}

		$hotel_id = (int) $wpdb->insert_id;
		HRM_License::assign_owner_capabilities( $user_id );
		$this->insert_manual_subscription( $hotel_id, $plan, $cycle, $ends_at, 'active' );
		HRM_Public::ensure_frontend_pages( $hotel_id );

		wp_mail(
			$user->user_email,
			__( 'Welcome to BENDLESS TECH HMS', 'hrm-pro' ),
			sprintf(
				/* translators: 1: hotel name, 2: dashboard URL */
				__( "Your hotel \"%1\$s\" has been added to BENDLESS TECH HMS.\n\nDashboard: %2\$s", 'hrm-pro' ),
				$hotel_name,
				admin_url( 'admin.php?page=hrm-dashboard' )
			)
		);

		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'hotel_created', 'hotel', $hotel_id, wp_json_encode( array( 'plan' => $plan ) ) );

		wp_send_json_success(
			array(
				'message'  => __( 'Hotel created successfully.', 'hrm-pro' ),
				'hotel_id' => $hotel_id,
			)
		);
	}

	/**
	 * AJAX: update a hotel.
	 *
	 * @return void
	 */
	public function ajax_update_hotel() {
		$this->guard_super_ajax();

		global $wpdb;

		$hotel_id = isset( $_POST['hotel_id'] ) ? absint( wp_unslash( $_POST['hotel_id'] ) ) : 0;
		$hotel    = HRM_License::get_hotel( $hotel_id );
		if ( ! $hotel ) {
			wp_send_json_error( array( 'message' => __( 'Hotel not found.', 'hrm-pro' ) ), 404 );
		}

		$hotel_name = isset( $_POST['hotel_name'] ) ? sanitize_text_field( wp_unslash( $_POST['hotel_name'] ) ) : $hotel->hotel_name;
		$user_id    = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : (int) $hotel->user_id;
		$plan       = isset( $_POST['plan'] ) ? sanitize_key( wp_unslash( $_POST['plan'] ) ) : $hotel->plan;
		$status     = isset( $_POST['subscription_status'] ) ? sanitize_key( wp_unslash( $_POST['subscription_status'] ) ) : $hotel->subscription_status;
		$end_date   = isset( $_POST['subscription_ends_at'] ) ? sanitize_text_field( wp_unslash( $_POST['subscription_ends_at'] ) ) : '';
		$plans      = HRM_License::get_plans();

		if ( '' === $hotel_name || ! $user_id || empty( $plans[ $plan ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Hotel name, owner, and plan are required.', 'hrm-pro' ) ), 400 );
		}

		if ( ! get_userdata( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'The selected owner does not exist.', 'hrm-pro' ) ), 400 );
		}

		$allowed_statuses = array( 'active', 'trial', 'expired', 'past_due' );
		$status           = in_array( $status, $allowed_statuses, true ) ? $status : 'active';
		$ends_at          = $end_date && strtotime( $end_date ) ? date( 'Y-m-d 23:59:59', strtotime( $end_date ) ) : $hotel->subscription_ends_at;
		$updated          = $wpdb->update(
			HRM_Database::table( 'hotels' ),
			array(
				'user_id'              => $user_id,
				'hotel_name'           => $hotel_name,
				'hotel_slug'           => $this->unique_hotel_slug( $hotel_name, $hotel_id ),
				'hotel_email'          => isset( $_POST['hotel_email'] ) ? sanitize_email( wp_unslash( $_POST['hotel_email'] ) ) : $hotel->hotel_email,
				'hotel_phone'          => isset( $_POST['hotel_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['hotel_phone'] ) ) : $hotel->hotel_phone,
				'hotel_address'        => isset( $_POST['hotel_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hotel_address'] ) ) : $hotel->hotel_address,
				'plan'                 => $plan,
				'subscription_status'  => $status,
				'trial_ends_at'        => 'trial' === $status ? $ends_at : $hotel->trial_ends_at,
				'subscription_ends_at' => $ends_at,
				'is_active'            => 'expired' === $status ? 0 : 1,
			),
			array( 'id' => $hotel_id ),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			wp_send_json_error( array( 'message' => __( 'Hotel could not be updated.', 'hrm-pro' ) ), 500 );
		}

		HRM_License::assign_owner_capabilities( $user_id );
		HRM_Public::ensure_frontend_pages( $hotel_id );
		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'hotel_updated', 'hotel', $hotel_id, wp_json_encode( array( 'plan' => $plan, 'status' => $status ) ) );

		wp_send_json_success( array( 'message' => __( 'Hotel updated successfully.', 'hrm-pro' ) ) );
	}

	/**
	 * AJAX: delete a hotel and tenant data.
	 *
	 * @return void
	 */
	public function ajax_delete_hotel() {
		$this->guard_super_ajax();

		$hotel_id = isset( $_POST['hotel_id'] ) ? absint( wp_unslash( $_POST['hotel_id'] ) ) : 0;
		if ( ! HRM_License::get_hotel( $hotel_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Hotel not found.', 'hrm-pro' ) ), 404 );
		}

		$deleted = HRM_Database::delete_hotel_data( $hotel_id );
		if ( ! $deleted ) {
			wp_send_json_error( array( 'message' => __( 'Hotel could not be deleted.', 'hrm-pro' ) ), 500 );
		}

		if ( PHP_SESSION_ACTIVE === session_status() && isset( $_SESSION['hrm_impersonate_hotel_id'] ) && absint( $_SESSION['hrm_impersonate_hotel_id'] ) === $hotel_id ) {
			unset( $_SESSION['hrm_impersonate_hotel_id'] );
		}

		wp_send_json_success( array( 'message' => __( 'Hotel deleted successfully.', 'hrm-pro' ) ) );
	}

	/**
	 * AJAX: enable or disable a subscription.
	 *
	 * @return void
	 */
	public function ajax_toggle_subscription() {
		$this->guard_super_ajax();

		global $wpdb;

		$hotel_id = isset( $_POST['hotel_id'] ) ? absint( wp_unslash( $_POST['hotel_id'] ) ) : 0;
		$mode     = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'disable';
		$hotel    = HRM_License::get_hotel( $hotel_id );

		if ( ! $hotel ) {
			wp_send_json_error( array( 'message' => __( 'Hotel not found.', 'hrm-pro' ) ), 404 );
		}

		if ( 'enable' === $mode ) {
			$ends_at = $hotel->subscription_ends_at && strtotime( $hotel->subscription_ends_at ) > current_time( 'timestamp' )
				? $hotel->subscription_ends_at
				: date( 'Y-m-d H:i:s', strtotime( '+30 days', current_time( 'timestamp' ) ) );

			$data = array(
				'subscription_status'  => 'active',
				'subscription_ends_at' => $ends_at,
				'is_active'            => 1,
			);
		} else {
			$data = array(
				'subscription_status' => 'expired',
				'is_active'           => 0,
			);
		}

		$wpdb->update( HRM_Database::table( 'hotels' ), $data, array( 'id' => $hotel_id ) );
		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'subscription_' . $mode, 'hotel', $hotel_id, wp_json_encode( $data ) );

		wp_send_json_success( array( 'message' => __( 'Subscription status updated.', 'hrm-pro' ) ) );
	}

	/**
	 * AJAX: extend a hotel subscription.
	 *
	 * @return void
	 */
	public function ajax_extend_subscription() {
		$this->guard_super_ajax();

		global $wpdb;

		$hotel_id = isset( $_POST['hotel_id'] ) ? absint( wp_unslash( $_POST['hotel_id'] ) ) : 0;
		$days     = isset( $_POST['days'] ) ? absint( wp_unslash( $_POST['days'] ) ) : 0;
		$end_date = isset( $_POST['ends_at'] ) ? sanitize_text_field( wp_unslash( $_POST['ends_at'] ) ) : '';
		$hotel    = HRM_License::get_hotel( $hotel_id );

		if ( ! $hotel ) {
			wp_send_json_error( array( 'message' => __( 'Hotel not found.', 'hrm-pro' ) ), 404 );
		}

		if ( $days ) {
			$base_timestamp = $hotel->subscription_ends_at && strtotime( $hotel->subscription_ends_at ) > current_time( 'timestamp' )
				? strtotime( $hotel->subscription_ends_at )
				: current_time( 'timestamp' );
			$ends_at        = date( 'Y-m-d H:i:s', strtotime( '+' . $days . ' days', $base_timestamp ) );
		} elseif ( $end_date && strtotime( $end_date ) ) {
			$ends_at = date( 'Y-m-d 23:59:59', strtotime( $end_date ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Choose a valid extension date.', 'hrm-pro' ) ), 400 );
		}

		$wpdb->update(
			HRM_Database::table( 'hotels' ),
			array(
				'subscription_status'  => 'active',
				'subscription_ends_at' => $ends_at,
				'is_active'            => 1,
			),
			array( 'id' => $hotel_id ),
			array( '%s', '%s', '%d' ),
			array( '%d' )
		);

		$this->insert_manual_subscription( $hotel_id, $hotel->plan, 'monthly', $ends_at, 'active' );
		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'subscription_extended', 'hotel', $hotel_id, wp_json_encode( array( 'ends_at' => $ends_at ) ) );

		wp_send_json_success(
			array(
				'message' => __( 'Subscription extended.', 'hrm-pro' ),
				'ends_at' => $ends_at,
			)
		);
	}

	/**
	 * AJAX: impersonate a hotel.
	 *
	 * @return void
	 */
	public function ajax_impersonate() {
		$this->guard_super_ajax();

		$hotel_id = isset( $_POST['hotel_id'] ) ? absint( wp_unslash( $_POST['hotel_id'] ) ) : 0;
		if ( ! HRM_License::get_hotel( $hotel_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Hotel not found.', 'hrm-pro' ) ), 404 );
		}

		if ( PHP_SESSION_ACTIVE !== session_status() && ! headers_sent() ) {
			session_start();
		}

		$_SESSION['hrm_impersonate_hotel_id'] = $hotel_id;

		wp_send_json_success(
			array(
				'message'  => __( 'Impersonation started.', 'hrm-pro' ),
				'redirect' => admin_url( 'admin.php?page=hrm-dashboard' ),
			)
		);
	}

	/**
	 * AJAX: exit impersonation.
	 *
	 * @return void
	 */
	public function ajax_exit_impersonate() {
		$this->guard_super_ajax();

		if ( PHP_SESSION_ACTIVE === session_status() ) {
			unset( $_SESSION['hrm_impersonate_hotel_id'] );
		}

		wp_send_json_success(
			array(
				'message'  => __( 'Impersonation ended.', 'hrm-pro' ),
				'redirect' => admin_url( 'admin.php?page=hrm-platform' ),
			)
		);
	}

	/**
	 * AJAX: run a per-subscription action.
	 *
	 * @return void
	 */
	public function ajax_subscription_action() {
		$this->guard_super_ajax();

		global $wpdb;

		$subscription_id = isset( $_POST['subscription_id'] ) ? absint( wp_unslash( $_POST['subscription_id'] ) ) : 0;
		$action_type     = isset( $_POST['subscription_action'] ) ? sanitize_key( wp_unslash( $_POST['subscription_action'] ) ) : '';
		$table           = HRM_Database::table( 'subscriptions' );
		$subscription    = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d LIMIT 1",
				$subscription_id
			)
		);

		if ( ! $subscription ) {
			wp_send_json_error( array( 'message' => __( 'Subscription not found.', 'hrm-pro' ) ), 404 );
		}

		if ( 'cancel' === $action_type ) {
			$wpdb->update( $table, array( 'status' => 'cancelled' ), array( 'id' => $subscription_id ), array( '%s' ), array( '%d' ) );
			$wpdb->update(
				HRM_Database::table( 'hotels' ),
				array(
					'subscription_status' => 'expired',
					'is_active'           => 0,
				),
				array( 'id' => (int) $subscription->hotel_id ),
				array( '%s', '%d' ),
				array( '%d' )
			);
		} elseif ( 'mark_paid' === $action_type ) {
			$amount = isset( $_POST['amount_paid'] ) ? max( 0, (float) wp_unslash( $_POST['amount_paid'] ) ) : 0;
			$wpdb->update(
				$table,
				array(
					'amount_paid' => $amount,
					'status'      => 'active',
				),
				array( 'id' => $subscription_id ),
				array( '%f', '%s' ),
				array( '%d' )
			);
		} elseif ( in_array( $action_type, array( 'extend_30', 'extend_90', 'extend_365' ), true ) ) {
			$days      = (int) str_replace( 'extend_', '', $action_type );
			$base_time = strtotime( $subscription->ends_at ) > current_time( 'timestamp' ) ? strtotime( $subscription->ends_at ) : current_time( 'timestamp' );
			$ends_at   = date( 'Y-m-d H:i:s', strtotime( '+' . $days . ' days', $base_time ) );

			$wpdb->update(
				$table,
				array(
					'ends_at' => $ends_at,
					'status'  => 'active',
				),
				array( 'id' => $subscription_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
			$wpdb->update(
				HRM_Database::table( 'hotels' ),
				array(
					'subscription_status'  => 'active',
					'subscription_ends_at' => $ends_at,
					'is_active'            => 1,
				),
				array( 'id' => (int) $subscription->hotel_id ),
				array( '%s', '%s', '%d' ),
				array( '%d' )
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid subscription action.', 'hrm-pro' ) ), 400 );
		}

		HRM_Activity_Log::log( (int) $subscription->hotel_id, get_current_user_id(), 'subscription_action_' . $action_type, 'subscription', $subscription_id );
		wp_send_json_success( array( 'message' => __( 'Subscription updated.', 'hrm-pro' ) ) );
	}

	/**
	 * AJAX: toggle platform test mode.
	 *
	 * @return void
	 */
	public function ajax_toggle_test_mode() {
		$this->guard_super_ajax();

		$enabled = isset( $_POST['enabled'] ) && '1' === (string) wp_unslash( $_POST['enabled'] );
		update_option( 'hrm_test_mode', $enabled ? 1 : 0, false );

		wp_send_json_success(
			array(
				'message' => $enabled ? __( 'Test mode enabled.', 'hrm-pro' ) : __( 'Test mode disabled.', 'hrm-pro' ),
				'enabled' => $enabled,
			)
		);
	}

	/**
	 * Render a platform page using the shared shell.
	 *
	 * @param string $content_view View file.
	 * @param string $page_title   Page title.
	 * @param string $active_page  Active nav key.
	 * @param array  $content_data View data.
	 * @return void
	 */
	private function render_platform_page( $content_view, $page_title, $active_page, $content_data = array() ) {
		$hotel          = false;
		$plans          = HRM_License::get_plans();
		$current_plan   = null;
		$expiry_context = array();
		$is_blocked     = false;
		$is_platform    = true;
		$navigation     = $this->get_platform_navigation_items();
		$user           = wp_get_current_user();
		$role_label     = __( 'Super Admin', 'hrm-pro' );
		$test_mode      = (bool) get_option( 'hrm_test_mode', false );
		$page_actions   = array();

		include HRM_PLUGIN_DIR . 'admin/views/app-shell.php';
	}

	/**
	 * Guard a platform page.
	 *
	 * @return void
	 */
	private function guard_super_page() {
		if ( ! $this->platform_enabled() || ! is_super_admin() || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to access the HMS Platform.', 'hrm-pro' ) );
		}
	}

	/**
	 * Guard a platform admin-post request.
	 *
	 * @param string $nonce_action Nonce action.
	 * @return void
	 */
	private function guard_super_post( $nonce_action ) {
		if ( ! $this->platform_enabled() || ! is_super_admin() || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to update the HMS Platform.', 'hrm-pro' ) );
		}

		check_admin_referer( $nonce_action );
	}

	/**
	 * Guard a platform AJAX request.
	 *
	 * @return void
	 */
	private function guard_super_ajax() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! $this->platform_enabled() || ! is_super_admin() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to manage the HMS Platform.', 'hrm-pro' ) ), 403 );
		}
	}

	/**
	 * Determine if this WordPress install is allowed to run the platform console.
	 *
	 * @return bool
	 */
	private function platform_enabled() {
		return ( defined( 'BENDLESS_HMS_PLATFORM_MASTER' ) && BENDLESS_HMS_PLATFORM_MASTER ) || (bool) get_option( 'hrm_platform_master_mode', false );
	}

	/**
	 * Return platform navigation items.
	 *
	 * @return array
	 */
	private function get_platform_navigation_items() {
		return array(
			array(
				'key'      => 'platform',
				'label'    => __( 'Dashboard', 'hrm-pro' ),
				'icon'     => 'solar:home-2-linear',
				'url'      => admin_url( 'admin.php?page=hrm-platform' ),
				'disabled' => false,
			),
			array(
				'key'      => 'hotels',
				'label'    => __( 'Hotels', 'hrm-pro' ),
				'icon'     => 'solar:city-linear',
				'url'      => admin_url( 'admin.php?page=hrm-platform-hotels' ),
				'disabled' => false,
			),
			array(
				'key'      => 'subscriptions',
				'label'    => __( 'Subscriptions', 'hrm-pro' ),
				'icon'     => 'solar:wallet-money-linear',
				'url'      => admin_url( 'admin.php?page=hrm-platform-subscriptions' ),
				'disabled' => false,
			),
			array(
				'key'      => 'plans',
				'label'    => __( 'Plan Manager', 'hrm-pro' ),
				'icon'     => 'solar:crown-star-linear',
				'url'      => admin_url( 'admin.php?page=hrm-platform-plans' ),
				'disabled' => false,
			),
		);
	}

	/**
	 * Return dashboard platform stats.
	 *
	 * @return array
	 */
	private function get_platform_stats() {
		global $wpdb;

		$hotels_table        = HRM_Database::table( 'hotels' );
		$subscriptions_table = HRM_Database::table( 'subscriptions' );

		return array(
			'total_hotels'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$hotels_table}" ),
			'active_subscriptions' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$hotels_table} WHERE subscription_status = 'active' AND is_active = 1" ),
			'expired'              => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$hotels_table} WHERE subscription_status IN ('expired','past_due') OR is_active = 0" ),
			'trial'                => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$hotels_table} WHERE subscription_status = 'trial'" ),
			'total_revenue'        => (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount_paid), 0) FROM {$subscriptions_table}" ),
		);
	}

	/**
	 * Return revenue grouped by recent month.
	 *
	 * @return array
	 */
	private function get_monthly_revenue() {
		global $wpdb;

		$months = array();
		for ( $i = 5; $i >= 0; $i-- ) {
			$key            = date( 'Y-m', strtotime( '-' . $i . ' months', current_time( 'timestamp' ) ) );
			$months[ $key ] = array(
				'label' => date_i18n( 'M', strtotime( $key . '-01' ) ),
				'total' => 0,
			);
		}

		$table      = HRM_Database::table( 'subscriptions' );
		$start_date = array_key_first( $months ) . '-01 00:00:00';
		$rows       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE_FORMAT(created_at, '%%Y-%%m') AS month_key, COALESCE(SUM(amount_paid), 0) AS total FROM {$table} WHERE created_at >= %s GROUP BY month_key ORDER BY month_key ASC",
				$start_date
			)
		);

		foreach ( $rows as $row ) {
			if ( isset( $months[ $row->month_key ] ) ) {
				$months[ $row->month_key ]['total'] = (float) $row->total;
			}
		}

		return array_values( $months );
	}

	/**
	 * Return recent subscription payment rows.
	 *
	 * @return array
	 */
	private function get_recent_subscription_payments() {
		global $wpdb;

		$subscriptions_table = HRM_Database::table( 'subscriptions' );
		$hotels_table        = HRM_Database::table( 'hotels' );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.*, h.hotel_name FROM {$subscriptions_table} s LEFT JOIN {$hotels_table} h ON h.id = s.hotel_id ORDER BY s.created_at DESC LIMIT %d",
				8
			)
		);
	}

	/**
	 * Return recently expired hotels for one-click reactivation.
	 *
	 * @return array
	 */
	private function get_expired_hotels() {
		global $wpdb;

		$hotels_table = HRM_Database::table( 'hotels' );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, hotel_name, plan, subscription_status, subscription_ends_at FROM {$hotels_table} WHERE subscription_status IN ('expired','past_due') OR is_active = 0 ORDER BY subscription_ends_at ASC LIMIT %d",
				6
			)
		);
	}

	/**
	 * Return all hotels with owner and room counts.
	 *
	 * @return array
	 */
	private function get_hotels() {
		global $wpdb;

		$hotels_table = HRM_Database::table( 'hotels' );
		$rooms_table  = HRM_Database::table( 'rooms' );
		$users_table  = $wpdb->users;

		return $wpdb->get_results(
			"SELECT h.*, u.user_email AS owner_email, u.display_name AS owner_name,
				(SELECT COUNT(*) FROM {$rooms_table} r WHERE r.hotel_id = h.id) AS rooms_count
			FROM {$hotels_table} h
			LEFT JOIN {$users_table} u ON u.ID = h.user_id
			ORDER BY h.created_at DESC"
		);
	}

	/**
	 * Return filtered subscription rows.
	 *
	 * @param array $filters Filters.
	 * @return array
	 */
	private function get_subscriptions( $filters ) {
		global $wpdb;

		$subscriptions_table = HRM_Database::table( 'subscriptions' );
		$hotels_table        = HRM_Database::table( 'hotels' );
		$where               = array( '1=1' );
		$params              = array();

		if ( ! empty( $filters['plan'] ) ) {
			$where[]  = 's.plan = %s';
			$params[] = sanitize_key( $filters['plan'] );
		}

		if ( ! empty( $filters['status'] ) ) {
			$where[]  = 's.status = %s';
			$params[] = sanitize_key( $filters['status'] );
		}

		if ( ! empty( $filters['billing_cycle'] ) ) {
			$where[]  = 's.billing_cycle = %s';
			$params[] = sanitize_key( $filters['billing_cycle'] );
		}

		if ( ! empty( $filters['date_from'] ) && strtotime( $filters['date_from'] ) ) {
			$where[]  = 's.created_at >= %s';
			$params[] = date( 'Y-m-d 00:00:00', strtotime( $filters['date_from'] ) );
		}

		if ( ! empty( $filters['date_to'] ) && strtotime( $filters['date_to'] ) ) {
			$where[]  = 's.created_at <= %s';
			$params[] = date( 'Y-m-d 23:59:59', strtotime( $filters['date_to'] ) );
		}

		$sql = "SELECT s.*, h.hotel_name FROM {$subscriptions_table} s LEFT JOIN {$hotels_table} h ON h.id = s.hotel_id WHERE " . implode( ' AND ', $where ) . ' ORDER BY s.created_at DESC LIMIT 200';

		if ( $params ) {
			return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Return users for owner selectors.
	 *
	 * @return array
	 */
	private function get_user_options() {
		return get_users(
			array(
				'fields'  => array( 'ID', 'display_name', 'user_email' ),
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);
	}

	/**
	 * Insert a manual subscription ledger row.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $plan     Plan slug.
	 * @param string $cycle    Billing cycle.
	 * @param string $ends_at  End datetime.
	 * @param string $status   Status.
	 * @return int
	 */
	private function insert_manual_subscription( $hotel_id, $plan, $cycle, $ends_at, $status ) {
		global $wpdb;

		$wpdb->insert(
			HRM_Database::table( 'subscriptions' ),
			array(
				'hotel_id'           => absint( $hotel_id ),
				'plan'               => sanitize_key( $plan ),
				'billing_cycle'      => 'yearly' === sanitize_key( $cycle ) ? 'yearly' : 'monthly',
				'amount_paid'        => 0,
				'paystack_reference' => 'HRM-MANUAL-' . absint( $hotel_id ) . '-' . time(),
				'paystack_sub_code'  => '',
				'starts_at'          => current_time( 'mysql' ),
				'ends_at'            => $ends_at,
				'status'             => sanitize_key( $status ),
				'is_test'            => get_option( 'hrm_test_mode' ) ? 1 : 0,
				'created_at'         => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Return a unique hotel slug.
	 *
	 * @param string $hotel_name Hotel name.
	 * @param int    $exclude_id Existing hotel ID to ignore.
	 * @return string
	 */
	private function unique_hotel_slug( $hotel_name, $exclude_id = 0 ) {
		global $wpdb;

		$base       = sanitize_title( $hotel_name );
		$base       = $base ? $base : 'hotel';
		$candidate  = $base;
		$counter    = 2;
		$table      = HRM_Database::table( 'hotels' );
		$exclude_id = absint( $exclude_id );

		while ( true ) {
			if ( $exclude_id ) {
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$table} WHERE hotel_slug = %s AND id <> %d LIMIT 1",
						$candidate,
						$exclude_id
					)
				);
			} else {
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$table} WHERE hotel_slug = %s LIMIT 1",
						$candidate
					)
				);
			}

			if ( ! $exists ) {
				return $candidate;
			}

			$candidate = $base . '-' . $counter;
			$counter++;
		}
	}

	/**
	 * Normalize a subscription end date.
	 *
	 * @param string $date  Submitted date.
	 * @param string $cycle Billing cycle.
	 * @param string $plan  Plan slug.
	 * @return string
	 */
	private function normalize_end_datetime( $date, $cycle, $plan ) {
		if ( $date && strtotime( $date ) ) {
			return date( 'Y-m-d 23:59:59', strtotime( $date ) );
		}

		if ( 'enterprise' === $plan ) {
			return '2099-12-31 23:59:59';
		}

		$months = 'yearly' === $cycle ? 12 : 1;

		return date( 'Y-m-d H:i:s', strtotime( '+' . $months . ' months', current_time( 'timestamp' ) ) );
	}
}
