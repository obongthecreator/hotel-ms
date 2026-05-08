<?php
/**
 * Plugin Name: BENDLESS TECH HMS
 * Plugin URI: https://bendless-tech.local/bendless-tech-hms
 * Description: Multi-tenant hotel room management, bookings, licensing, and platform administration for WordPress.
 * Version: 1.0.0
 * Author: BENDLESS TECH
 * Text Domain: hrm-pro
 * Domain Path: /languages
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'HRM_VERSION' ) ) {
	define( 'HRM_VERSION', '1.0.0' );
}

if ( ! defined( 'HRM_PLUGIN_FILE' ) ) {
	define( 'HRM_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'HRM_PLUGIN_DIR' ) ) {
	define( 'HRM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'HRM_PLUGIN_URL' ) ) {
	define( 'HRM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'HRM_PLUGIN_BASENAME' ) ) {
	define( 'HRM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'HRM_TEXT_DOMAIN' ) ) {
	define( 'HRM_TEXT_DOMAIN', 'hrm-pro' );
}

if ( ! defined( 'HRM_PLUGIN_SLUG' ) ) {
	define( 'HRM_PLUGIN_SLUG', 'bendless-tech-hms' );
}

if ( ! defined( 'HRM_PAYSTACK_BASE_URL' ) ) {
	define( 'HRM_PAYSTACK_BASE_URL', 'https://api.paystack.co' );
}

if ( ! defined( 'HRM_PAYSTACK_PUBLIC_KEY' ) ) {
	define(
		'HRM_PAYSTACK_PUBLIC_KEY',
		(bool) get_option( 'hrm_test_mode', false )
			? get_option( 'hrm_paystack_test_public_key', get_option( 'hrm_paystack_public_key', '' ) )
			: get_option( 'hrm_paystack_public_key', '' )
	);
}

if ( ! defined( 'HRM_PAYSTACK_SECRET_KEY' ) ) {
	define(
		'HRM_PAYSTACK_SECRET_KEY',
		(bool) get_option( 'hrm_test_mode', false )
			? get_option( 'hrm_paystack_test_secret_key', get_option( 'hrm_paystack_secret_key', '' ) )
			: get_option( 'hrm_paystack_secret_key', '' )
	);
}

if ( ! defined( 'HRM_PLANS' ) ) {
	define(
		'HRM_PLANS',
		array(
			'basic'      => array(
				'name'          => 'Starter',
				'price_monthly' => 0,
				'price_yearly'  => 580000,
				'renewal_yearly'=> 350000,
				'rooms_limit'   => 15,
				'staff_limit'   => 2,
				'features'      => array(
					'room_management',
					'basic_bookings',
					'invoices',
					'dashboard',
					'frontend_booking',
				),
			),
			'standard'   => array(
				'name'          => 'Standard',
				'price_monthly' => 0,
				'price_yearly'  => 860000,
				'renewal_yearly'=> 660000,
				'rooms_limit'   => 50,
				'staff_limit'   => 5,
				'features'      => array(
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
				),
			),
			'pro'        => array(
				'name'          => 'Control Pro',
				'price_monthly' => 0,
				'price_yearly'  => 1350000,
				'renewal_yearly'=> 950000,
				'rooms_limit'   => 150,
				'staff_limit'   => 15,
				'features'      => array(
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
				),
			),
			'enterprise' => array(
				'name'          => 'Enterprise',
				'price_monthly' => 0,
				'price_yearly'  => 2200000,
				'renewal_yearly'=> 1500000,
				'rooms_limit'   => -1,
				'staff_limit'   => -1,
				'features'      => array( '*' ),
			),
		)
	);
}

/**
 * Main plugin coordinator.
 */
final class HRM_Pro_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var HRM_Pro_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Admin controller.
	 *
	 * @var HRM_Admin|null
	 */
	private $admin = null;

	/**
	 * Super admin controller.
	 *
	 * @var HRM_Super_Admin|null
	 */
	private $super_admin = null;

	/**
	 * Paystack service.
	 *
	 * @var HRM_Paystack|null
	 */
	private $paystack = null;

	/**
	 * Public storefront controller.
	 *
	 * @var HRM_Public|null
	 */
	private $public = null;

	/**
	 * Return the singleton instance.
	 *
	 * @return HRM_Pro_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Activation callback.
	 *
	 * @return void
	 */
	public static function activate() {
		self::load_core_files();
		HRM_Database::create_tables();
		HRM_Database::create_roles();
		HRM_Database::ensure_default_options();
		HRM_Public::ensure_frontend_pages();
		update_option( 'hrm_frontend_pages_version', HRM_VERSION, false );

		if ( ! wp_next_scheduled( 'hrm_daily_room_status_sync' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'hrm_daily_room_status_sync' );
		}
	}

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'hrm_daily_subscription_check' );
		wp_clear_scheduled_hook( 'hrm_daily_room_status_sync' );
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		self::load_core_files();

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'start_session' ), 1 );
		add_action( 'init', array( $this, 'maybe_upgrade_database' ), 5 );
		add_action( 'init', array( $this, 'maybe_ensure_frontend_pages' ), 8 );
		add_action( 'init', array( $this, 'register_components' ), 20 );
		add_action( 'wp_logout', array( $this, 'clear_impersonation_session' ) );
	}

	/**
	 * Load required class files.
	 *
	 * @return void
	 */
	private static function load_core_files() {
		require_once HRM_PLUGIN_DIR . 'includes/class-database.php';
		require_once HRM_PLUGIN_DIR . 'includes/class-license.php';
		require_once HRM_PLUGIN_DIR . 'includes/class-settings.php';
		require_once HRM_PLUGIN_DIR . 'includes/class-activity-log.php';
		require_once HRM_PLUGIN_DIR . 'includes/class-rooms.php';
		require_once HRM_PLUGIN_DIR . 'includes/class-guests.php';
		require_once HRM_PLUGIN_DIR . 'includes/class-bookings.php';
		require_once HRM_PLUGIN_DIR . 'includes/class-whatsapp.php';
		require_once HRM_PLUGIN_DIR . 'includes/class-invoices.php';
		require_once HRM_PLUGIN_DIR . 'includes/class-reports.php';
		require_once HRM_PLUGIN_DIR . 'includes/class-paystack.php';
		require_once HRM_PLUGIN_DIR . 'public/class-public.php';
		require_once HRM_PLUGIN_DIR . 'admin/class-admin.php';
		require_once HRM_PLUGIN_DIR . 'admin/class-super-admin.php';
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( HRM_TEXT_DOMAIN, false, dirname( HRM_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Start a PHP session for secure super admin impersonation.
	 *
	 * @return void
	 */
	public function start_session() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		if ( PHP_SESSION_ACTIVE === session_status() || headers_sent() ) {
			return;
		}

		session_start();
	}

	/**
	 * Register admin-facing components.
	 *
	 * @return void
	 */
	public function register_components() {
		$this->paystack    = new HRM_Paystack();
		$this->public      = new HRM_Public( $this->paystack );
		$this->admin       = new HRM_Admin( $this->paystack );
		$this->super_admin = new HRM_Super_Admin( $this->paystack );
	}

	/**
	 * Run schema upgrades when the installed version is stale.
	 *
	 * @return void
	 */
	public function maybe_upgrade_database() {
		$installed_version = get_option( 'hrm_db_version', '' );

		if ( HRM_VERSION === $installed_version ) {
			HRM_Database::ensure_default_options();
			return;
		}

		HRM_Database::create_tables();
		HRM_Database::create_roles();
		HRM_Database::ensure_default_options();
	}

	/**
	 * Create public HMS pages after updates or first run.
	 *
	 * @return void
	 */
	public function maybe_ensure_frontend_pages() {
		$page_options  = array(
			'hrm_rooms_page_id',
			'hrm_booking_page_id',
			'hrm_checkout_page_id',
			'hrm_frontend_dashboard_page_id',
			'hrm_frontend_analytics_page_id',
			'hrm_room_analytics_page_id',
			'hrm_booking_confirmation_page_id',
		);
		$missing_pages = false;

		foreach ( $page_options as $option ) {
			$page_id = absint( get_option( $option, 0 ) );
			$page    = $page_id ? get_post( $page_id ) : null;
			if ( ! $page || 'trash' === $page->post_status ) {
				$missing_pages = true;
				break;
			}
		}

		if ( HRM_VERSION === get_option( 'hrm_frontend_pages_version', '' ) && ! $missing_pages ) {
			return;
		}

		HRM_Public::ensure_frontend_pages();
		update_option( 'hrm_frontend_pages_version', HRM_VERSION, false );
	}

	/**
	 * Clear impersonation when the WordPress session ends.
	 *
	 * @return void
	 */
	public function clear_impersonation_session() {
		if ( PHP_SESSION_ACTIVE !== session_status() ) {
			return;
		}

		unset( $_SESSION['hrm_impersonate_hotel_id'] );
	}
}

register_activation_hook( __FILE__, array( 'HRM_Pro_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HRM_Pro_Plugin', 'deactivate' ) );

HRM_Pro_Plugin::instance();
