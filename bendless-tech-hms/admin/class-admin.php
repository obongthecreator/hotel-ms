<?php
/**
 * Tenant admin interface and shared admin assets.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the hotel admin engine and tenant-facing license controls.
 */
class HRM_Admin {

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_head', array( $this, 'print_admin_head' ) );
		add_action( 'wp_ajax_hrm_get_room_grid', array( $this, 'ajax_get_room_grid' ) );
		add_action( 'wp_ajax_hrm_initiate_plan_payment', array( $this, 'ajax_initiate_plan_payment' ) );
		add_action( 'wp_ajax_hrm_verify_plan_payment', array( $this, 'ajax_verify_plan_payment' ) );
		add_action( 'wp_ajax_hrm_toggle_room_status', array( $this, 'ajax_toggle_room_status' ) );
		add_action( 'wp_ajax_hrm_save_room', array( $this, 'ajax_save_room' ) );
		add_action( 'wp_ajax_hrm_delete_room', array( $this, 'ajax_delete_room' ) );
		add_action( 'wp_ajax_hrm_guest_lookup', array( $this, 'ajax_guest_lookup' ) );
		add_action( 'wp_ajax_hrm_save_booking', array( $this, 'ajax_save_booking' ) );
		add_action( 'wp_ajax_hrm_update_booking_status', array( $this, 'ajax_update_booking_status' ) );
		add_action( 'wp_ajax_hrm_mark_room_clean', array( $this, 'ajax_mark_room_clean' ) );
		add_action( 'wp_ajax_hrm_admin_calculate_rate', array( $this, 'ajax_admin_calculate_rate' ) );
		add_action( 'wp_ajax_hrm_send_whatsapp', array( $this, 'ajax_send_whatsapp' ) );
		add_action( 'wp_ajax_hrm_save_guest_flag', array( $this, 'ajax_save_guest_flag' ) );
		add_action( 'wp_ajax_hrm_get_report_data', array( $this, 'ajax_get_report_data' ) );
		add_action( 'wp_ajax_hrm_export_csv', array( $this, 'ajax_export_csv' ) );
		add_action( 'wp_ajax_hrm_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_hrm_test_whatsapp', array( $this, 'ajax_test_whatsapp' ) );
	}

	/**
	 * Register tenant admin menus.
	 *
	 * @return void
	 */
	public function register_menus() {
		add_menu_page(
			__( 'Hotel Manager', 'hrm-pro' ),
			__( 'Hotel Manager', 'hrm-pro' ),
			'hrm_manage_bookings',
			'hrm-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-building',
			25
		);

		add_submenu_page(
			'hrm-dashboard',
			__( 'Dashboard', 'hrm-pro' ),
			__( 'Dashboard', 'hrm-pro' ),
			'hrm_manage_bookings',
			'hrm-dashboard',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'hrm-dashboard',
			__( 'Rooms', 'hrm-pro' ),
			__( 'Rooms', 'hrm-pro' ),
			'hrm_manage_rooms',
			'hrm-rooms',
			array( $this, 'render_rooms' )
		);

		add_submenu_page(
			'hrm-dashboard',
			__( 'Bookings', 'hrm-pro' ),
			__( 'Bookings', 'hrm-pro' ),
			'hrm_manage_bookings',
			'hrm-bookings',
			array( $this, 'render_bookings' )
		);

		add_submenu_page(
			'hrm-dashboard',
			__( 'Guests', 'hrm-pro' ),
			__( 'Guests', 'hrm-pro' ),
			'hrm_manage_bookings',
			'hrm-guests',
			array( $this, 'render_guests' )
		);

		add_submenu_page(
			'hrm-dashboard',
			__( 'Reports', 'hrm-pro' ),
			__( 'Reports', 'hrm-pro' ),
			'hrm_view_reports',
			'hrm-reports',
			array( $this, 'render_reports' )
		);

		add_submenu_page(
			'hrm-dashboard',
			__( 'Settings', 'hrm-pro' ),
			__( 'Settings', 'hrm-pro' ),
			'hrm_manage_settings',
			'hrm-settings',
			array( $this, 'render_settings' )
		);

		add_submenu_page(
			null,
			__( 'Invoice', 'hrm-pro' ),
			__( 'Invoice', 'hrm-pro' ),
			'hrm_manage_bookings',
			'hrm-invoice',
			array( $this, 'render_invoice' )
		);

		add_submenu_page(
			null,
			__( 'Guest Profile Export', 'hrm-pro' ),
			__( 'Guest Profile Export', 'hrm-pro' ),
			'hrm_manage_bookings',
			'hrm-guest-profile',
			array( $this, 'render_guest_profile_export' )
		);

		add_submenu_page(
			null,
			__( 'Subscription Callback', 'hrm-pro' ),
			__( 'Subscription Callback', 'hrm-pro' ),
			'manage_options',
			'hrm-subscription-callback',
			array( $this, 'render_subscription_callback' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		unset( $hook_suffix );

		if ( ! $this->is_plugin_page() ) {
			return;
		}

		wp_enqueue_style( 'hrm-admin', HRM_PLUGIN_URL . 'admin/assets/admin.css', array(), HRM_VERSION );
		wp_enqueue_script( 'hrm-admin', HRM_PLUGIN_URL . 'admin/assets/admin.js', array(), HRM_VERSION, true );
		wp_localize_script(
			'hrm-admin',
			'hrmAdmin',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'hrm_nonce' ),
				'currentHotelId'    => HRM_License::get_current_hotel_id(),
				'paystackPublicKey' => $this->paystack->get_public_key(),
				'dashboardUrl'      => admin_url( 'admin.php?page=hrm-dashboard' ),
				'platformUrl'       => admin_url( 'admin.php?page=hrm-platform' ),
				'i18n'              => array(
					'confirmDeleteHotel'  => __( 'Delete this hotel and all of its HMS data?', 'hrm-pro' ),
					'confirmCancel'       => __( 'Cancel this subscription?', 'hrm-pro' ),
					'paymentStarting'     => __( 'Preparing secure Paystack checkout...', 'hrm-pro' ),
					'paymentUnavailable'  => __( 'Paystack checkout could not be started.', 'hrm-pro' ),
					'actionFailed'        => __( 'The action could not be completed.', 'hrm-pro' ),
					'saved'               => __( 'Saved successfully.', 'hrm-pro' ),
					'enterAmount'         => __( 'Enter amount paid in Naira', 'hrm-pro' ),
					'confirmDeleteRoom'   => __( 'Delete this room?', 'hrm-pro' ),
					'confirmStatusChange' => __( 'Update this status?', 'hrm-pro' ),
					'selectRoomDates'     => __( 'Select a room and dates first.', 'hrm-pro' ),
					'confirmFlagGuest'    => __( 'Save this guest flag?', 'hrm-pro' ),
					'whatsappSending'     => __( 'Sending WhatsApp message...', 'hrm-pro' ),
					'whatsappSent'        => __( 'WhatsApp message sent.', 'hrm-pro' ),
					'csvExported'         => __( 'CSV exported.', 'hrm-pro' ),
					'settingsSaved'       => __( 'Settings saved.', 'hrm-pro' ),
					'confirmRemoveStaff'  => __( 'Remove selected staff access?', 'hrm-pro' ),
				),
			)
		);
	}

	/**
	 * Print Tailwind, fonts, icons, and WordPress chrome overrides.
	 *
	 * @return void
	 */
	public function print_admin_head() {
		if ( ! $this->is_plugin_page() ) {
			return;
		}

		echo '<style>
			#adminmenuwrap, #adminmenuback { display:none!important; }
			#wpcontent, #wpbody-content { margin-left:0!important; padding:0!important; }
			#wpbody { padding-top:0!important; }
			.wrap { margin:0!important; max-width:none!important; }
			#wpfooter { display:none!important; }
		</style>';
		echo '<script src="https://cdn.tailwindcss.com"></script>';
		echo '<script>
			tailwind.config = {
				theme: {
					extend: {
						colors: {
							primary: {
								50:  "#f8f5fc",
								100: "#eee7f6",
								500: "#987cc0",
								600: "#866cb0",
								700: "#6f5599",
								900: "#20142e"
							},
							surface: {
								50:  "#ffffff",
								100: "#f8f8f8",
								200: "#e8e8e8",
								800: "#111111",
								900: "#000000"
							}
						},
						fontFamily: {
							sans: ["Inter", "system-ui", "sans-serif"]
						},
						boxShadow: {
							card:  "0 1px 3px 0 rgb(0 0 0 / 0.08), 0 1px 2px -1px rgb(0 0 0 / 0.08)",
							modal: "0 20px 60px -10px rgb(0 0 0 / 0.3)"
						}
					}
				}
			}
		</script>';
		echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
		echo '<script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>';
	}

	/**
	 * Render the tenant dashboard shell.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		$hotel      = HRM_License::get_current_license();
		$hotel_id   = $hotel ? (int) $hotel->id : 0;
		$stats      = $hotel_id ? $this->get_engine_stats( $hotel_id ) : array();
		$activities = $hotel_id ? HRM_Activity_Log::recent( $hotel_id, 8 ) : array();

		$this->render_app_page(
			HRM_PLUGIN_DIR . 'admin/views/dashboard.php',
			__( 'Dashboard', 'hrm-pro' ),
			'dashboard',
			array(
				'hotel'            => $hotel,
				'stats'            => $stats,
				'activities'       => $activities,
				'feature_labels'   => HRM_License::feature_labels(),
				'available_plans'  => HRM_License::get_plans(),
				'current_user_obj' => wp_get_current_user(),
			)
		);
	}

	/**
	 * Render room management.
	 *
	 * @return void
	 */
	public function render_rooms() {
		$hotel    = HRM_License::get_current_license();
		$hotel_id = $hotel ? (int) $hotel->id : 0;
		$rooms    = $hotel_id ? ( new HRM_Rooms() )->all( $hotel_id ) : array();
		$counts   = $hotel_id ? ( new HRM_Rooms() )->status_counts( $hotel_id ) : array();

		$this->render_app_page(
			HRM_PLUGIN_DIR . 'admin/views/rooms.php',
			__( 'Rooms', 'hrm-pro' ),
			'rooms',
			array(
				'hotel'  => $hotel,
				'rooms'  => $rooms,
				'counts' => $counts,
			)
		);
	}

	/**
	 * Render booking management.
	 *
	 * @return void
	 */
	public function render_bookings() {
		$hotel    = HRM_License::get_current_license();
		$hotel_id = $hotel ? (int) $hotel->id : 0;
		$rooms    = $hotel_id ? ( new HRM_Rooms() )->all( $hotel_id ) : array();
		$bookings = $hotel_id ? ( new HRM_Bookings() )->admin_list( $hotel_id ) : array();
		$guests   = $hotel_id ? ( new HRM_Guests() )->search( $hotel_id, '', 8 ) : array();

		$this->render_app_page(
			HRM_PLUGIN_DIR . 'admin/views/bookings.php',
			__( 'Bookings', 'hrm-pro' ),
			'bookings',
			array(
				'hotel'    => $hotel,
				'rooms'    => $rooms,
				'bookings' => $bookings,
				'guests'   => $guests,
			)
		);
	}

	/**
	 * Render guest directory and profiles.
	 *
	 * @return void
	 */
	public function render_guests() {
		$hotel          = HRM_License::get_current_license();
		$hotel_id       = $hotel ? (int) $hotel->id : 0;
		$guest_service  = new HRM_Guests();
		$search         = isset( $_GET['hrm_guest_search'] ) ? sanitize_text_field( wp_unslash( $_GET['hrm_guest_search'] ) ) : '';
		$flag           = isset( $_GET['hrm_guest_flag'] ) ? sanitize_key( wp_unslash( $_GET['hrm_guest_flag'] ) ) : 'all';
		$guests         = $hotel_id ? $guest_service->all(
			$hotel_id,
			array(
				'search' => $search,
				'flag'   => $flag,
			)
		) : array();
		$stats          = $hotel_id ? $guest_service->stats( $hotel_id ) : array();
		$guest_profiles = array();

		foreach ( $guests as $guest ) {
			$guest->total_spent_formatted = HRM_Settings::money( isset( $guest->total_spent ) ? $guest->total_spent : 0, $hotel_id );
			$guest->last_stay_display     = ! empty( $guest->last_stay ) ? date_i18n( get_option( 'date_format' ), strtotime( $guest->last_stay ) ) : __( 'No stays yet', 'hrm-pro' );
			$profile = $guest_service->profile( (int) $guest->id, $hotel_id );
			if ( $profile ) {
				$guest_profiles[ (int) $guest->id ] = $profile;
			}
		}

		$this->render_app_page(
			HRM_PLUGIN_DIR . 'admin/views/guests.php',
			__( 'Guests', 'hrm-pro' ),
			'guests',
			array(
				'hotel'          => $hotel,
				'guests'         => $guests,
				'guest_profiles' => $guest_profiles,
				'stats'          => $stats,
				'search'         => $search,
				'flag'           => $flag,
			)
		);
	}

	/**
	 * Render reports and activity log.
	 *
	 * @return void
	 */
	public function render_reports() {
		$hotel      = HRM_License::get_current_license();
		$hotel_id   = $hotel ? (int) $hotel->id : 0;
		$from       = isset( $_GET['hrm_from'] ) ? sanitize_text_field( wp_unslash( $_GET['hrm_from'] ) ) : '';
		$to         = isset( $_GET['hrm_to'] ) ? sanitize_text_field( wp_unslash( $_GET['hrm_to'] ) ) : '';
		$reports    = new HRM_Reports();
		$report_data = $hotel_id ? $reports->dashboard( $hotel_id, $from, $to ) : array(
			'range'            => $reports->normalize_range( $from, $to ),
			'room_performance' => array( 'rows' => array(), 'summary' => array() ),
			'revenue_leakage'  => array( 'rows' => array(), 'summary' => array() ),
			'end_of_shift'     => array( 'payments' => array(), 'bookings' => array(), 'summary' => array() ),
			'activity'         => array(),
		);

		$this->render_app_page(
			HRM_PLUGIN_DIR . 'admin/views/reports.php',
			__( 'Reports', 'hrm-pro' ),
			'reports',
			array(
				'hotel'       => $hotel,
				'report_data' => $report_data,
			)
		);
	}

	/**
	 * Render hotel settings and access control.
	 *
	 * @return void
	 */
	public function render_settings() {
		$hotel    = HRM_License::get_current_license();
		$hotel_id = $hotel ? (int) $hotel->id : 0;
		$settings = array(
			'vat_rate'            => HRM_Settings::get( 'vat_rate', '7.5', $hotel_id ),
			'currency_symbol'     => HRM_Settings::get( 'currency_symbol', '₦', $hotel_id ),
			'weekend_days'        => explode( ',', (string) HRM_Settings::get( 'weekend_days', 'friday,saturday', $hotel_id ) ),
			'peak_season_dates'   => json_decode( (string) HRM_Settings::get( 'peak_season_dates', '[]', $hotel_id ), true ),
			'whatsapp_enabled'    => HRM_Settings::get( 'whatsapp_enabled', 'no', $hotel_id ),
			'whatsapp_server_url' => HRM_Settings::get( 'whatsapp_server_url', '', $hotel_id ),
			'whatsapp_api_key'    => HRM_Settings::get( 'whatsapp_api_key', '', $hotel_id ),
			'bank_name'           => HRM_Settings::get( 'bank_name', '', $hotel_id ),
			'bank_account_name'   => HRM_Settings::get( 'bank_account_name', '', $hotel_id ),
			'bank_account_number' => HRM_Settings::get( 'bank_account_number', '', $hotel_id ),
			'brand_primary_color' => HRM_Settings::get( 'brand_primary_color', '#987CC0', $hotel_id ),
			'brand_button_color'  => HRM_Settings::get( 'brand_button_color', '#987CC0', $hotel_id ),
			'brand_text_color'    => HRM_Settings::get( 'brand_text_color', '#000000', $hotel_id ),
			'brand_font_family'   => HRM_Settings::get( 'brand_font_family', 'Inter, system-ui, sans-serif', $hotel_id ),
		);

		if ( ! is_array( $settings['peak_season_dates'] ) ) {
			$settings['peak_season_dates'] = array();
		}

		$this->render_app_page(
			HRM_PLUGIN_DIR . 'admin/views/settings.php',
			__( 'Settings', 'hrm-pro' ),
			'settings',
			array(
				'hotel'          => $hotel,
				'settings'       => $settings,
				'staff_users'    => $hotel_id ? $this->get_hotel_staff_users( $hotel_id ) : array(),
				'available_users'=> get_users( array( 'fields' => array( 'ID', 'display_name', 'user_email' ), 'orderby' => 'display_name' ) ),
				'owner_user'     => $hotel ? get_userdata( (int) $hotel->user_id ) : false,
				'staff_roles'    => $this->staff_role_options(),
			)
		);
	}

	/**
	 * Render a print-ready invoice.
	 *
	 * @return void
	 */
	public function render_invoice() {
		if ( ! current_user_can( 'hrm_manage_bookings' ) ) {
			wp_die( esc_html__( 'You are not allowed to view invoices.', 'hrm-pro' ) );
		}

		$booking_id = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;
		$html       = ( new HRM_Invoices() )->render( $booking_id, HRM_License::get_current_hotel_id() );

		if ( is_wp_error( $html ) ) {
			wp_die( esc_html( $html->get_error_message() ) );
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render a printable guest profile export.
	 *
	 * @return void
	 */
	public function render_guest_profile_export() {
		if ( ! current_user_can( 'hrm_manage_bookings' ) ) {
			wp_die( esc_html__( 'You are not allowed to export guest profiles.', 'hrm-pro' ) );
		}

		$hotel_id = HRM_License::get_current_hotel_id();
		$guest_id = isset( $_GET['guest_id'] ) ? absint( wp_unslash( $_GET['guest_id'] ) ) : 0;
		$profile  = ( new HRM_Guests() )->profile( $guest_id, $hotel_id );
		$hotel    = HRM_License::get_hotel( $hotel_id );

		if ( ! $profile || ! $hotel ) {
			wp_die( esc_html__( 'The guest profile could not be found.', 'hrm-pro' ) );
		}

		include HRM_PLUGIN_DIR . 'admin/views/guest-profile-export.php';
	}

	/**
	 * Render the Paystack subscription callback page.
	 *
	 * @return void
	 */
	public function render_subscription_callback() {
		if ( ! current_user_can( 'manage_options' ) && ! is_super_admin() ) {
			wp_die( esc_html__( 'You are not allowed to verify subscriptions.', 'hrm-pro' ) );
		}

		$reference = '';
		if ( isset( $_GET['reference'] ) ) {
			$reference = sanitize_text_field( wp_unslash( $_GET['reference'] ) );
		} elseif ( isset( $_GET['trxref'] ) ) {
			$reference = sanitize_text_field( wp_unslash( $_GET['trxref'] ) );
		}

		$result  = $reference ? $this->paystack->verify_subscription( $reference ) : new WP_Error( 'hrm_missing_reference', __( 'No Paystack reference was supplied.', 'hrm-pro' ) );
		$success = ! is_wp_error( $result ) && true === $result;
		$message = $success ? __( 'Subscription verified successfully.', 'hrm-pro' ) : ( is_wp_error( $result ) ? $result->get_error_message() : __( 'Payment verification failed.', 'hrm-pro' ) );

		$this->render_app_page(
			HRM_PLUGIN_DIR . 'admin/views/dashboard.php',
			__( 'Subscription Verification', 'hrm-pro' ),
			'dashboard',
			array(
				'hotel'                  => HRM_License::get_current_license(),
				'stats'                  => array(),
				'activities'             => array(),
				'feature_labels'         => HRM_License::feature_labels(),
				'available_plans'        => HRM_License::get_plans(),
				'current_user_obj'       => wp_get_current_user(),
				'subscription_callback'  => true,
				'callback_success'       => $success,
				'callback_message'       => $message,
			)
		);
	}

	/**
	 * Render a plugin app page.
	 *
	 * @param string $content_view Absolute view path.
	 * @param string $page_title   Page title.
	 * @param string $active_page  Active nav page key.
	 * @param array  $content_data View data.
	 * @return void
	 */
	public function render_app_page( $content_view, $page_title, $active_page, $content_data = array() ) {
		$hotel          = isset( $content_data['hotel'] ) ? $content_data['hotel'] : HRM_License::get_current_license();
		$plans          = HRM_License::get_plans();
		$current_plan   = $hotel ? HRM_License::get_plan( $hotel->plan ) : null;
		$expiry_context = HRM_License::get_expiry_context( $hotel );
		$is_blocked     = $hotel && ! $hotel->is_active() && ! is_super_admin();
		$navigation     = $this->get_navigation_items();
		$user           = wp_get_current_user();
		$role_label     = $this->get_user_role_label( $user );
		$is_platform    = false;
		$page_actions   = array();
		$test_mode      = (bool) get_option( 'hrm_test_mode', false );

		include HRM_PLUGIN_DIR . 'admin/views/app-shell.php';
	}

	/**
	 * Return whether the current admin screen is owned by the plugin.
	 *
	 * @return bool
	 */
	public function is_plugin_page() {
		if ( empty( $_GET['page'] ) ) {
			return false;
		}

		$page = sanitize_key( wp_unslash( $_GET['page'] ) );

		return 0 === strpos( $page, 'hrm-' );
	}

	/**
	 * Return users assigned as hotel staff.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return array
	 */
	private function get_hotel_staff_users( $hotel_id ) {
		$hotel_id = absint( $hotel_id );
		if ( ! $hotel_id ) {
			return array();
		}

		$users = get_users(
			array(
				'meta_key'   => 'hrm_hotel_id',
				'meta_value' => $hotel_id,
				'orderby'    => 'display_name',
			)
		);

		$payload = array();
		foreach ( $users as $user ) {
			$payload[] = array(
				'id'    => (int) $user->ID,
				'name'  => (string) $user->display_name,
				'email' => (string) $user->user_email,
				'role'  => get_user_meta( $user->ID, 'hrm_staff_role', true ) ? get_user_meta( $user->ID, 'hrm_staff_role', true ) : 'receptionist',
			);
		}

		return $payload;
	}

	/**
	 * Return editable staff role options.
	 *
	 * @return array
	 */
	private function staff_role_options() {
		return array(
			'receptionist' => __( 'Receptionist', 'hrm-pro' ),
			'hotel_admin'  => __( 'Hotel Admin', 'hrm-pro' ),
		);
	}

	/**
	 * Sanitize a staff role key.
	 *
	 * @param string $role Staff role.
	 * @return string
	 */
	private function sanitize_staff_role( $role ) {
		$role = sanitize_key( $role );

		return array_key_exists( $role, $this->staff_role_options() ) ? $role : 'receptionist';
	}

	/**
	 * Sync WordPress capabilities for an HMS staff user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $role    HMS staff role.
	 * @return void
	 */
	private function sync_staff_capabilities( $user_id, $role ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return;
		}

		$role    = $this->sanitize_staff_role( $role );
		$wp_user = new WP_User( $user_id );

		if ( ! in_array( 'administrator', (array) $wp_user->roles, true ) && ! in_array( 'hrm_receptionist', (array) $wp_user->roles, true ) ) {
			$wp_user->set_role( 'hrm_receptionist' );
		}

		foreach ( array( 'hrm_manage_bookings', 'hrm_manage_rooms', 'hrm_manage_guests' ) as $capability ) {
			$wp_user->add_cap( $capability );
		}

		if ( 'hotel_admin' === $role ) {
			foreach ( array( 'hrm_view_reports', 'hrm_manage_settings' ) as $capability ) {
				$wp_user->add_cap( $capability );
			}
		} elseif ( ! in_array( 'administrator', (array) $wp_user->roles, true ) ) {
			foreach ( array( 'hrm_view_reports', 'hrm_manage_settings' ) as $capability ) {
				$wp_user->remove_cap( $capability );
			}
		}
	}

	/**
	 * Assign a WordPress user to the current hotel as staff.
	 *
	 * @param int $user_id       User ID.
	 * @param int $hotel_id      Hotel ID.
	 * @param int $owner_user_id Owner user ID.
	 * @param string $staff_role HMS staff role.
	 * @return true|WP_Error
	 */
	private function assign_staff_user( $user_id, $hotel_id, $owner_user_id, $staff_role = 'receptionist' ) {
		$user_id       = absint( $user_id );
		$hotel_id      = absint( $hotel_id );
		$owner_user_id = absint( $owner_user_id );
		$user          = get_userdata( $user_id );
		$staff_role    = $this->sanitize_staff_role( $staff_role );

		if ( ! $user || ! $hotel_id ) {
			return new WP_Error( 'hrm_staff_missing_user', __( 'The selected staff user could not be found.', 'hrm-pro' ) );
		}

		if ( $user_id === $owner_user_id ) {
			return new WP_Error( 'hrm_staff_owner', __( 'The hotel owner already has admin access.', 'hrm-pro' ) );
		}

		$current_hotel_id = absint( get_user_meta( $user_id, 'hrm_hotel_id', true ) );
		if ( $current_hotel_id && $current_hotel_id !== $hotel_id ) {
			return new WP_Error( 'hrm_staff_other_hotel', __( 'This user is already assigned to another hotel.', 'hrm-pro' ) );
		}

		$hotel = HRM_License::get_hotel( $hotel_id );
		$plan  = $hotel ? HRM_License::get_plan( $hotel->plan ) : null;
		if ( $plan && (int) $plan['staff_limit'] >= 0 ) {
			$current_staff_count = count( $this->get_hotel_staff_users( $hotel_id ) );
			if ( ! $current_hotel_id && $current_staff_count >= (int) $plan['staff_limit'] ) {
				return new WP_Error( 'hrm_staff_limit', __( 'This hotel has reached the staff limit for its plan.', 'hrm-pro' ) );
			}
		}

		update_user_meta( $user_id, 'hrm_hotel_id', $hotel_id );
		update_user_meta( $user_id, 'hrm_staff_role', $staff_role );
		$this->sync_staff_capabilities( $user_id, $staff_role );

		return true;
	}

	/**
	 * Create a new WordPress user and assign them to the hotel.
	 *
	 * @param array $data          Staff data.
	 * @param int   $hotel_id      Hotel ID.
	 * @param int   $owner_user_id Owner user ID.
	 * @return int|WP_Error
	 */
	private function create_staff_user( $data, $hotel_id, $owner_user_id ) {
		$name       = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$email      = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
		$password   = isset( $data['password'] ) ? (string) $data['password'] : '';
		$staff_role = isset( $data['role'] ) ? $this->sanitize_staff_role( $data['role'] ) : 'receptionist';

		if ( '' === $name || '' === $email || ! is_email( $email ) ) {
			return new WP_Error( 'hrm_staff_invalid', __( 'Staff name and a valid email address are required.', 'hrm-pro' ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'hrm_staff_email_exists', __( 'A WordPress user already exists with this staff email. Select that user instead.', 'hrm-pro' ) );
		}

		$password = '' !== trim( $password ) ? $password : wp_generate_password( 16, true, true );
		$email_parts = explode( '@', $email );
		$username    = sanitize_user( reset( $email_parts ), true );
		if ( '' === $username || username_exists( $username ) ) {
			$username = sanitize_user( 'hms_' . wp_generate_password( 8, false, false ), true );
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_pass'    => $password,
				'user_email'   => $email,
				'display_name' => $name,
				'first_name'   => $name,
				'role'         => 'hrm_receptionist',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$assigned = $this->assign_staff_user( (int) $user_id, $hotel_id, $owner_user_id, $staff_role );
		if ( is_wp_error( $assigned ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( (int) $user_id );
			return $assigned;
		}

		wp_new_user_notification( (int) $user_id, null, 'user' );

		return (int) $user_id;
	}

	/**
	 * Update editable staff profiles for the current hotel.
	 *
	 * @param array $profiles Staff profile rows.
	 * @param int   $hotel_id Hotel ID.
	 * @return true|WP_Error
	 */
	private function update_staff_profiles( $profiles, $hotel_id ) {
		$hotel_id = absint( $hotel_id );
		if ( ! is_array( $profiles ) || ! $hotel_id ) {
			return true;
		}

		foreach ( $profiles as $staff_id => $profile ) {
			$staff_id = absint( $staff_id );
			if ( ! $staff_id || absint( get_user_meta( $staff_id, 'hrm_hotel_id', true ) ) !== $hotel_id ) {
				continue;
			}

			$display_name = isset( $profile['display_name'] ) ? sanitize_text_field( $profile['display_name'] ) : '';
			$email        = isset( $profile['email'] ) ? sanitize_email( $profile['email'] ) : '';
			$staff_role   = isset( $profile['role'] ) ? $this->sanitize_staff_role( $profile['role'] ) : 'receptionist';

			if ( '' === $display_name || '' === $email || ! is_email( $email ) ) {
				return new WP_Error( 'hrm_staff_update_invalid', __( 'Each staff member needs a name and valid email address.', 'hrm-pro' ) );
			}

			$existing_email_user = email_exists( $email );
			if ( $existing_email_user && (int) $existing_email_user !== $staff_id ) {
				return new WP_Error( 'hrm_staff_update_email_exists', __( 'A staff email address is already used by another WordPress user.', 'hrm-pro' ) );
			}

			$updated = wp_update_user(
				array(
					'ID'           => $staff_id,
					'display_name' => $display_name,
					'user_email'   => $email,
				)
			);

			if ( is_wp_error( $updated ) ) {
				return $updated;
			}

			update_user_meta( $staff_id, 'hrm_staff_role', $staff_role );
			$this->sync_staff_capabilities( $staff_id, $staff_role );
		}

		return true;
	}

	/**
	 * Remove a staff user's hotel access.
	 *
	 * @param int $user_id  User ID.
	 * @param int $hotel_id Hotel ID.
	 * @return void
	 */
	private function remove_staff_user( $user_id, $hotel_id ) {
		$user_id  = absint( $user_id );
		$hotel_id = absint( $hotel_id );

		if ( ! $user_id || absint( get_user_meta( $user_id, 'hrm_hotel_id', true ) ) !== $hotel_id ) {
			return;
		}

		delete_user_meta( $user_id, 'hrm_hotel_id' );
		delete_user_meta( $user_id, 'hrm_staff_role' );

		$wp_user = new WP_User( $user_id );
		if ( in_array( 'hrm_receptionist', (array) $wp_user->roles, true ) ) {
			$wp_user->remove_role( 'hrm_receptionist' );
			if ( empty( $wp_user->roles ) ) {
				$wp_user->set_role( 'subscriber' );
			}
		}

		foreach ( array( 'hrm_manage_bookings', 'hrm_manage_rooms', 'hrm_manage_guests', 'hrm_view_reports', 'hrm_manage_settings' ) as $capability ) {
			$wp_user->remove_cap( $capability );
		}
	}

	/**
	 * Sanitize peak season date ranges from settings form fields.
	 *
	 * @param array $from_values Start dates.
	 * @param array $to_values   End dates.
	 * @return array
	 */
	private function sanitize_peak_seasons( $from_values, $to_values ) {
		$seasons = array();
		$count   = max( count( $from_values ), count( $to_values ) );

		for ( $i = 0; $i < $count; $i++ ) {
			$from = isset( $from_values[ $i ] ) ? sanitize_text_field( $from_values[ $i ] ) : '';
			$to   = isset( $to_values[ $i ] ) ? sanitize_text_field( $to_values[ $i ] ) : '';

			if ( ! $from || ! $to || ! strtotime( $from ) || ! strtotime( $to ) ) {
				continue;
			}

			if ( strtotime( $to ) < strtotime( $from ) ) {
				$to = $from;
			}

			$seasons[] = array(
				'from' => date( 'Y-m-d', strtotime( $from ) ),
				'to'   => date( 'Y-m-d', strtotime( $to ) ),
			);
		}

		return $seasons;
	}

	/**
	 * AJAX: return room-grid summary data.
	 *
	 * @return void
	 */
	public function ajax_get_room_grid() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_bookings' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to view rooms.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id = HRM_License::get_current_hotel_id();
		if ( ! $hotel_id ) {
			wp_send_json_error( array( 'message' => __( 'No hotel is assigned to this user.', 'hrm-pro' ) ), 404 );
		}

		$rooms_service = new HRM_Rooms();

		wp_send_json_success(
			array(
				'rooms'  => $rooms_service->grid_payload( $hotel_id ),
				'counts' => $rooms_service->status_counts( $hotel_id ),
			)
		);
	}

	/**
	 * AJAX: create or update a room.
	 *
	 * @return void
	 */
	public function ajax_save_room() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to save rooms.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id = HRM_License::get_current_hotel_id();
		$result   = ( new HRM_Rooms() )->save(
			array(
				'id'              => isset( $_POST['room_id'] ) ? absint( wp_unslash( $_POST['room_id'] ) ) : 0,
				'room_number'     => isset( $_POST['room_number'] ) ? sanitize_text_field( wp_unslash( $_POST['room_number'] ) ) : '',
				'room_type'       => isset( $_POST['room_type'] ) ? sanitize_key( wp_unslash( $_POST['room_type'] ) ) : 'single',
				'floor'           => isset( $_POST['floor'] ) ? absint( wp_unslash( $_POST['floor'] ) ) : 1,
				'price_per_night' => isset( $_POST['price_per_night'] ) ? (float) wp_unslash( $_POST['price_per_night'] ) : 0,
				'weekend_rate'    => isset( $_POST['weekend_rate'] ) ? sanitize_text_field( wp_unslash( $_POST['weekend_rate'] ) ) : '',
				'peak_rate'       => isset( $_POST['peak_rate'] ) ? sanitize_text_field( wp_unslash( $_POST['peak_rate'] ) ) : '',
				'max_guests'      => isset( $_POST['max_guests'] ) ? absint( wp_unslash( $_POST['max_guests'] ) ) : 2,
				'status'          => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'available',
				'description'     => isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '',
				'amenities'       => isset( $_POST['amenities'] ) ? sanitize_textarea_field( wp_unslash( $_POST['amenities'] ) ) : '',
				'image_urls'      => isset( $_POST['image_urls'] ) ? sanitize_textarea_field( wp_unslash( $_POST['image_urls'] ) ) : '',
			),
			$hotel_id
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'room_saved', 'room', $result );
		wp_send_json_success( array( 'message' => __( 'Room saved.', 'hrm-pro' ), 'room_id' => $result ) );
	}

	/**
	 * AJAX: delete a room.
	 *
	 * @return void
	 */
	public function ajax_delete_room() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to delete rooms.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id = HRM_License::get_current_hotel_id();
		$room_id  = isset( $_POST['room_id'] ) ? absint( wp_unslash( $_POST['room_id'] ) ) : 0;
		$result   = ( new HRM_Rooms() )->delete( $room_id, $hotel_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'room_deleted', 'room', $room_id );
		wp_send_json_success( array( 'message' => __( 'Room deleted.', 'hrm-pro' ) ) );
	}

	/**
	 * AJAX: toggle a room status.
	 *
	 * @return void
	 */
	public function ajax_toggle_room_status() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_rooms' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to update room status.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id = HRM_License::get_current_hotel_id();
		$room_id  = isset( $_POST['room_id'] ) ? absint( wp_unslash( $_POST['room_id'] ) ) : 0;
		$status   = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$result   = ( new HRM_Rooms() )->update_status( $room_id, $status, $hotel_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'room_status_updated', 'room', $room_id, wp_json_encode( array( 'status' => $status ) ) );
		wp_send_json_success( array( 'message' => __( 'Room status updated.', 'hrm-pro' ) ) );
	}

	/**
	 * AJAX: mark a room clean.
	 *
	 * @return void
	 */
	public function ajax_mark_room_clean() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_bookings' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to update rooms.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id = HRM_License::get_current_hotel_id();
		$room_id  = isset( $_POST['room_id'] ) ? absint( wp_unslash( $_POST['room_id'] ) ) : 0;
		$status   = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'available';
		$status   = in_array( $status, array( 'available', 'cleaning', 'maintenance' ), true ) ? $status : 'available';
		$result   = ( new HRM_Rooms() )->update_status( $room_id, $status, $hotel_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'housekeeping_status_updated', 'room', $room_id, wp_json_encode( array( 'status' => $status ) ) );
		wp_send_json_success( array( 'message' => __( 'Housekeeping status updated.', 'hrm-pro' ) ) );
	}

	/**
	 * AJAX: guest lookup.
	 *
	 * @return void
	 */
	public function ajax_guest_lookup() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_bookings' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to look up guests.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id = HRM_License::get_current_hotel_id();
		$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$guests   = new HRM_Guests();

		if ( $phone ) {
			$guest = $guests->find_by_phone( $phone, $hotel_id );
			wp_send_json_success( array( 'guest' => $guest ? $guests->to_public_payload( $guest ) : null ) );
		}

		$rows = array();
		foreach ( $guests->search( $hotel_id, $search, 12 ) as $guest ) {
			$rows[] = $guests->to_public_payload( $guest );
		}

		wp_send_json_success( array( 'guests' => $rows ) );
	}

	/**
	 * AJAX: calculate an admin booking rate.
	 *
	 * @return void
	 */
	public function ajax_admin_calculate_rate() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_bookings' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to calculate rates.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id  = HRM_License::get_current_hotel_id();
		$room_id   = isset( $_POST['room_id'] ) ? absint( wp_unslash( $_POST['room_id'] ) ) : 0;
		$check_in  = isset( $_POST['check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) ) : '';
		$check_out = isset( $_POST['check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) ) : '';
		$rate      = ( new HRM_Bookings() )->calculate_rate( $room_id, $check_in, $check_out, $hotel_id );

		if ( ! $rate ) {
			wp_send_json_error( array( 'message' => __( 'Rate could not be calculated.', 'hrm-pro' ) ), 400 );
		}

		$rate['subtotal_formatted'] = HRM_Settings::money( $rate['subtotal'], $hotel_id );
		$rate['vat_formatted']      = HRM_Settings::money( $rate['vat_amount'], $hotel_id );
		$rate['total_formatted']    = HRM_Settings::money( $rate['total'], $hotel_id );

		wp_send_json_success( array( 'rate' => $rate ) );
	}

	/**
	 * AJAX: save a booking.
	 *
	 * @return void
	 */
	public function ajax_save_booking() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_bookings' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to save bookings.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id   = HRM_License::get_current_hotel_id();
		$booking_id = isset( $_POST['booking_id'] ) ? absint( wp_unslash( $_POST['booking_id'] ) ) : 0;
		if ( $booking_id && ! current_user_can( 'hrm_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Only hotel admins can edit an existing booking or switch rooms. Staff can update the booking workflow status.', 'hrm-pro' ) ), 403 );
		}

		$result   = ( new HRM_Bookings() )->save_admin_booking(
			$hotel_id,
			array(
				'booking_id'     => $booking_id,
				'room_id'        => isset( $_POST['room_id'] ) ? absint( wp_unslash( $_POST['room_id'] ) ) : 0,
				'check_in'       => isset( $_POST['check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) ) : '',
				'check_out'      => isset( $_POST['check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) ) : '',
				'guest_name'     => isset( $_POST['guest_name'] ) ? sanitize_text_field( wp_unslash( $_POST['guest_name'] ) ) : '',
				'guest_phone'    => isset( $_POST['guest_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['guest_phone'] ) ) : '',
				'guest_email'    => isset( $_POST['guest_email'] ) ? sanitize_email( wp_unslash( $_POST['guest_email'] ) ) : '',
				'id_type'        => isset( $_POST['id_type'] ) ? sanitize_text_field( wp_unslash( $_POST['id_type'] ) ) : '',
				'id_number'      => isset( $_POST['id_number'] ) ? sanitize_text_field( wp_unslash( $_POST['id_number'] ) ) : '',
				'payment_method' => isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'cash',
				'amount_paid'    => isset( $_POST['amount_paid'] ) ? (float) wp_unslash( $_POST['amount_paid'] ) : 0,
				'transfer_ref'   => isset( $_POST['transfer_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['transfer_ref'] ) ) : '',
				'status'         => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'confirmed',
				'quick_checkin'  => isset( $_POST['quick_checkin'] ) ? sanitize_key( wp_unslash( $_POST['quick_checkin'] ) ) : '',
				'notes'          => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
			),
			get_current_user_id()
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'booking_saved', 'booking', $result );
		wp_send_json_success( array( 'message' => __( 'Booking saved.', 'hrm-pro' ), 'booking_id' => $result ) );
	}

	/**
	 * AJAX: update booking status.
	 *
	 * @return void
	 */
	public function ajax_update_booking_status() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_bookings' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to update bookings.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id   = HRM_License::get_current_hotel_id();
		$booking_id = isset( $_POST['booking_id'] ) ? absint( wp_unslash( $_POST['booking_id'] ) ) : 0;
		$status     = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$result     = ( new HRM_Bookings() )->update_status( $booking_id, $status, $hotel_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'booking_status_updated', 'booking', $booking_id, wp_json_encode( array( 'status' => $status ) ) );
		wp_send_json_success( array( 'message' => __( 'Booking status updated.', 'hrm-pro' ) ) );
	}

	/**
	 * AJAX: send a WhatsApp message for a booking.
	 *
	 * @return void
	 */
	public function ajax_send_whatsapp() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_bookings' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to send WhatsApp messages.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id     = HRM_License::get_current_hotel_id();
		$booking_id   = isset( $_POST['booking_id'] ) ? absint( wp_unslash( $_POST['booking_id'] ) ) : 0;
		$message_type = isset( $_POST['message_type'] ) ? sanitize_key( wp_unslash( $_POST['message_type'] ) ) : 'confirmation';
		$booking      = ( new HRM_Bookings() )->get( $booking_id, $hotel_id );

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => __( 'The booking could not be found.', 'hrm-pro' ) ), 404 );
		}

		$result = ( new HRM_WhatsApp() )->send_with_result( '', $message_type, $booking_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'message' => __( 'WhatsApp message sent.', 'hrm-pro' ) ) );
	}

	/**
	 * AJAX: save guest VIP or blacklist flag.
	 *
	 * @return void
	 */
	public function ajax_save_guest_flag() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to flag guests.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id = HRM_License::get_current_hotel_id();
		$guest_id = isset( $_POST['guest_id'] ) ? absint( wp_unslash( $_POST['guest_id'] ) ) : 0;
		$flag     = isset( $_POST['flag'] ) ? sanitize_key( wp_unslash( $_POST['flag'] ) ) : 'none';
		$reason   = isset( $_POST['flag_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['flag_reason'] ) ) : '';
		$notes    = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';
		$result   = ( new HRM_Guests() )->save_flag( $guest_id, $hotel_id, $flag, $reason, $notes );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'guest_flag_saved', 'guest', $guest_id, wp_json_encode( array( 'flag' => $flag ) ) );
		wp_send_json_success( array( 'message' => __( 'Guest profile saved.', 'hrm-pro' ) ) );
	}

	/**
	 * AJAX: return report data for the selected range.
	 *
	 * @return void
	 */
	public function ajax_get_report_data() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_view_reports' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to view reports.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id = HRM_License::get_current_hotel_id();
		$from     = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '';
		$to       = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';

		if ( ! $hotel_id ) {
			wp_send_json_error( array( 'message' => __( 'No hotel is assigned to this user.', 'hrm-pro' ) ), 404 );
		}

		wp_send_json_success(
			array(
				'reports' => ( new HRM_Reports() )->dashboard( $hotel_id, $from, $to ),
			)
		);
	}

	/**
	 * AJAX: export report CSV data.
	 *
	 * @return void
	 */
	public function ajax_export_csv() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_view_reports' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to export reports.', 'hrm-pro' ) ), 403 );
		}

		if ( ! HRM_License::can( 'csv_export' ) ) {
			wp_send_json_error( array( 'message' => __( 'CSV export is not available on this plan.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id = HRM_License::get_current_hotel_id();
		$type     = isset( $_POST['report_type'] ) ? sanitize_key( wp_unslash( $_POST['report_type'] ) ) : '';
		$from     = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '';
		$to       = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';
		$result   = ( new HRM_Reports() )->csv( $hotel_id, $type, $from, $to );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'report_csv_exported', 'report', null, wp_json_encode( array( 'type' => $type ) ) );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX: save hotel settings.
	 *
	 * @return void
	 */
	public function ajax_save_settings() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to save settings.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id = HRM_License::get_current_hotel_id();
		$hotel    = HRM_License::get_hotel( $hotel_id );
		if ( ! $hotel ) {
			wp_send_json_error( array( 'message' => __( 'No hotel is assigned to this user.', 'hrm-pro' ) ), 404 );
		}

		global $wpdb;

		$hotel_row = array(
			'hotel_name'    => isset( $_POST['hotel_name'] ) ? sanitize_text_field( wp_unslash( $_POST['hotel_name'] ) ) : $hotel->hotel_name,
			'hotel_email'   => isset( $_POST['hotel_email'] ) ? sanitize_email( wp_unslash( $_POST['hotel_email'] ) ) : $hotel->hotel_email,
			'hotel_phone'   => isset( $_POST['hotel_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['hotel_phone'] ) ) : $hotel->hotel_phone,
			'hotel_address' => isset( $_POST['hotel_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hotel_address'] ) ) : $hotel->hotel_address,
			'logo_url'      => isset( $_POST['logo_url'] ) ? esc_url_raw( wp_unslash( $_POST['logo_url'] ) ) : $hotel->logo_url,
		);

		if ( '' === $hotel_row['hotel_name'] ) {
			wp_send_json_error( array( 'message' => __( 'Hotel name is required.', 'hrm-pro' ) ), 400 );
		}

		$wpdb->update(
			HRM_Database::table( 'hotels' ),
			$hotel_row,
			array( 'id' => $hotel_id ),
			array( '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		$weekend_days = isset( $_POST['weekend_days'] ) && is_array( $_POST['weekend_days'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['weekend_days'] ) ) : explode( ',', (string) HRM_Settings::get( 'weekend_days', 'friday,saturday', $hotel_id ) );
		$weekend_days = array_values( array_intersect( $weekend_days, array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ) ) );
		if ( isset( $_POST['peak_from'] ) || isset( $_POST['peak_to'] ) ) {
			$peak_dates = $this->sanitize_peak_seasons(
				isset( $_POST['peak_from'] ) && is_array( $_POST['peak_from'] ) ? wp_unslash( $_POST['peak_from'] ) : array(),
				isset( $_POST['peak_to'] ) && is_array( $_POST['peak_to'] ) ? wp_unslash( $_POST['peak_to'] ) : array()
			);
		} else {
			$peak_dates = json_decode( (string) HRM_Settings::get( 'peak_season_dates', '[]', $hotel_id ), true );
			$peak_dates = is_array( $peak_dates ) ? $peak_dates : array();
		}

		HRM_Settings::set( 'vat_rate', isset( $_POST['vat_rate'] ) ? (string) max( 0, (float) wp_unslash( $_POST['vat_rate'] ) ) : '7.5', $hotel_id );
		HRM_Settings::set( 'currency_symbol', isset( $_POST['currency_symbol'] ) ? sanitize_text_field( wp_unslash( $_POST['currency_symbol'] ) ) : HRM_Settings::get( 'currency_symbol', '₦', $hotel_id ), $hotel_id );
		HRM_Settings::set( 'weekend_days', implode( ',', $weekend_days ), $hotel_id );
		HRM_Settings::set( 'peak_season_dates', wp_json_encode( $peak_dates ), $hotel_id );
		HRM_Settings::set( 'whatsapp_enabled', isset( $_POST['whatsapp_enabled'] ) ? ( 'yes' === sanitize_key( wp_unslash( $_POST['whatsapp_enabled'] ) ) ? 'yes' : 'no' ) : HRM_Settings::get( 'whatsapp_enabled', 'no', $hotel_id ), $hotel_id );
		HRM_Settings::set( 'whatsapp_server_url', isset( $_POST['whatsapp_server_url'] ) ? esc_url_raw( wp_unslash( $_POST['whatsapp_server_url'] ) ) : HRM_Settings::get( 'whatsapp_server_url', '', $hotel_id ), $hotel_id );
		HRM_Settings::set( 'whatsapp_api_key', isset( $_POST['whatsapp_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['whatsapp_api_key'] ) ) : HRM_Settings::get( 'whatsapp_api_key', '', $hotel_id ), $hotel_id );
		HRM_Settings::set( 'bank_name', isset( $_POST['bank_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bank_name'] ) ) : HRM_Settings::get( 'bank_name', '', $hotel_id ), $hotel_id );
		HRM_Settings::set( 'bank_account_name', isset( $_POST['bank_account_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bank_account_name'] ) ) : HRM_Settings::get( 'bank_account_name', '', $hotel_id ), $hotel_id );
		HRM_Settings::set( 'bank_account_number', isset( $_POST['bank_account_number'] ) ? sanitize_text_field( wp_unslash( $_POST['bank_account_number'] ) ) : HRM_Settings::get( 'bank_account_number', '', $hotel_id ), $hotel_id );

		$brand_primary = isset( $_POST['brand_primary_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['brand_primary_color'] ) ) : '#987CC0';
		$brand_button  = isset( $_POST['brand_button_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['brand_button_color'] ) ) : '#987CC0';
		$brand_text    = isset( $_POST['brand_text_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['brand_text_color'] ) ) : '#000000';
		$brand_font    = isset( $_POST['brand_font_family'] ) ? sanitize_text_field( wp_unslash( $_POST['brand_font_family'] ) ) : 'Inter, system-ui, sans-serif';
		$brand_font    = preg_replace( '/[^A-Za-z0-9,\-_"\' .]/', '', $brand_font );

		HRM_Settings::set( 'brand_primary_color', $brand_primary ? $brand_primary : '#987CC0', $hotel_id );
		HRM_Settings::set( 'brand_button_color', $brand_button ? $brand_button : '#987CC0', $hotel_id );
		HRM_Settings::set( 'brand_text_color', $brand_text ? $brand_text : '#000000', $hotel_id );
		HRM_Settings::set( 'brand_font_family', $brand_font ? $brand_font : 'Inter, system-ui, sans-serif', $hotel_id );

		$remove_staff_ids = isset( $_POST['remove_staff_ids'] ) && is_array( $_POST['remove_staff_ids'] ) ? array_map( 'absint', wp_unslash( $_POST['remove_staff_ids'] ) ) : array();
		foreach ( $remove_staff_ids as $staff_id ) {
			$this->remove_staff_user( $staff_id, $hotel_id );
		}

		$staff_profiles = isset( $_POST['staff_profiles'] ) && is_array( $_POST['staff_profiles'] ) ? wp_unslash( $_POST['staff_profiles'] ) : array();
		$profile_update = $this->update_staff_profiles( $staff_profiles, $hotel_id );
		if ( is_wp_error( $profile_update ) ) {
			wp_send_json_error( array( 'message' => $profile_update->get_error_message() ), 400 );
		}

		$staff_user_id = isset( $_POST['staff_user_id'] ) ? absint( wp_unslash( $_POST['staff_user_id'] ) ) : 0;
		if ( $staff_user_id ) {
			$staff_user_role = isset( $_POST['staff_user_role'] ) ? sanitize_key( wp_unslash( $_POST['staff_user_role'] ) ) : 'receptionist';
			$assigned        = $this->assign_staff_user( $staff_user_id, $hotel_id, (int) $hotel->user_id, $staff_user_role );
			if ( is_wp_error( $assigned ) ) {
				wp_send_json_error( array( 'message' => $assigned->get_error_message() ), 400 );
			}
		}

		$new_staff_name  = isset( $_POST['new_staff_name'] ) ? sanitize_text_field( wp_unslash( $_POST['new_staff_name'] ) ) : '';
		$new_staff_email = isset( $_POST['new_staff_email'] ) ? sanitize_email( wp_unslash( $_POST['new_staff_email'] ) ) : '';
		if ( '' !== $new_staff_name || '' !== $new_staff_email ) {
			$created_staff = $this->create_staff_user(
				array(
					'name'     => $new_staff_name,
					'email'    => $new_staff_email,
					'password' => isset( $_POST['new_staff_password'] ) ? wp_unslash( $_POST['new_staff_password'] ) : '',
					'role'     => isset( $_POST['new_staff_role'] ) ? sanitize_key( wp_unslash( $_POST['new_staff_role'] ) ) : 'receptionist',
				),
				$hotel_id,
				(int) $hotel->user_id
			);
			if ( is_wp_error( $created_staff ) ) {
				wp_send_json_error( array( 'message' => $created_staff->get_error_message() ), 400 );
			}
		}

		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'settings_saved', 'settings', $hotel_id );
		wp_send_json_success( array( 'message' => __( 'Settings saved.', 'hrm-pro' ) ) );
	}

	/**
	 * AJAX: send a WhatsApp test message.
	 *
	 * @return void
	 */
	public function ajax_test_whatsapp() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'hrm_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to test WhatsApp.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id = HRM_License::get_current_hotel_id();
		if ( ! HRM_License::hotel_can( $hotel_id, 'whatsapp_notifications' ) ) {
			wp_send_json_error( array( 'message' => __( 'WhatsApp notifications are not available on this plan.', 'hrm-pro' ) ), 403 );
		}

		$phone      = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$server_url = esc_url_raw( HRM_Settings::get( 'whatsapp_server_url', '', $hotel_id ) );
		$api_key    = sanitize_text_field( HRM_Settings::get( 'whatsapp_api_key', '', $hotel_id ) );

		if ( '' === $phone || '' === $server_url || '' === $api_key ) {
			wp_send_json_error( array( 'message' => __( 'Test phone, server URL, and API key are required.', 'hrm-pro' ) ), 400 );
		}

		$hotel    = HRM_License::get_hotel( $hotel_id );
		$response = wp_remote_post(
			rtrim( $server_url, '/' ) . '/send',
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'phone'   => $phone,
					'message' => sprintf( __( 'Test message from %s via BENDLESS TECH HMS.', 'hrm-pro' ), $hotel ? $hotel->hotel_name : __( 'your hotel', 'hrm-pro' ) ),
						'api_key' => $api_key,
					)
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			wp_send_json_error( array( 'message' => __( 'WhatsApp test failed. Check the server URL and API key.', 'hrm-pro' ) ), 400 );
		}

		HRM_Activity_Log::log( $hotel_id, get_current_user_id(), 'whatsapp_test_sent', 'settings', $hotel_id, wp_json_encode( array( 'phone' => $phone ) ) );
		wp_send_json_success( array( 'message' => __( 'WhatsApp test sent.', 'hrm-pro' ) ) );
	}

	/**
	 * AJAX: start a plan payment.
	 *
	 * @return void
	 */
	public function ajax_initiate_plan_payment() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) && ! is_super_admin() ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to manage subscriptions.', 'hrm-pro' ) ), 403 );
		}

		$hotel_id = isset( $_POST['hotel_id'] ) ? absint( wp_unslash( $_POST['hotel_id'] ) ) : HRM_License::get_current_hotel_id();
		if ( ! $hotel_id ) {
			$hotel_id = HRM_License::get_current_hotel_id();
		}

		$plan          = isset( $_POST['plan'] ) ? sanitize_key( wp_unslash( $_POST['plan'] ) ) : 'basic';
		$billing_cycle = isset( $_POST['billing_cycle'] ) ? sanitize_key( wp_unslash( $_POST['billing_cycle'] ) ) : 'monthly';
		$url           = $this->paystack->initiate_subscription( $hotel_id, $plan, $billing_cycle );

		if ( is_wp_error( $url ) ) {
			wp_send_json_error( array( 'message' => $url->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'authorization_url' => $url ) );
	}

	/**
	 * AJAX: verify a plan payment.
	 *
	 * @return void
	 */
	public function ajax_verify_plan_payment() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) && ! is_super_admin() ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to verify subscriptions.', 'hrm-pro' ) ), 403 );
		}

		$reference = isset( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '';
		$result    = $this->paystack->verify_subscription( $reference );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Payment verification failed.', 'hrm-pro' ) ), 400 );
		}

		wp_send_json_success( array( 'message' => __( 'Subscription verified.', 'hrm-pro' ) ) );
	}

	/**
	 * Render an upgrade prompt component.
	 *
	 * @param string $feature_name Feature label.
	 * @param string $minimum_plan Minimum plan label.
	 * @return void
	 */
	public static function upgrade_prompt( $feature_name, $minimum_plan ) {
		?>
		<div class="flex flex-col items-center justify-center py-16 px-8 text-center bg-white rounded-2xl border border-dashed border-slate-200">
			<div class="w-16 h-16 rounded-2xl bg-amber-50 flex items-center justify-center mb-4">
				<span class="iconify text-3xl text-amber-500" data-icon="solar:crown-linear"></span>
			</div>
			<h3 class="text-lg font-semibold text-slate-800 mb-2"><?php esc_html_e( 'Upgrade to unlock this', 'hrm-pro' ); ?></h3>
			<p class="text-sm text-slate-500 mb-6 max-w-sm">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: feature name, 2: plan name */
						__( '%1$s is available on %2$s and above.', 'hrm-pro' ),
						$feature_name,
						$minimum_plan
					)
				);
				?>
			</p>
			<button type="button" onclick="openUpgradeModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold rounded-lg transition-colors">
				<span class="iconify" data-icon="solar:crown-linear"></span>
				<?php esc_html_e( 'View Plans & Upgrade', 'hrm-pro' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Return navigation items for the tenant shell.
	 *
	 * @return array
	 */
	private function get_navigation_items() {
		return array(
			array(
				'key'      => 'dashboard',
				'label'    => __( 'Dashboard', 'hrm-pro' ),
				'icon'     => 'solar:home-2-linear',
				'url'      => admin_url( 'admin.php?page=hrm-dashboard' ),
				'disabled' => false,
			),
			array(
				'key'      => 'rooms',
				'label'    => __( 'Rooms', 'hrm-pro' ),
				'icon'     => 'solar:buildings-2-linear',
				'url'      => admin_url( 'admin.php?page=hrm-rooms' ),
				'disabled' => false,
			),
			array(
				'key'      => 'bookings',
				'label'    => __( 'Bookings', 'hrm-pro' ),
				'icon'     => 'solar:calendar-mark-linear',
				'url'      => admin_url( 'admin.php?page=hrm-bookings' ),
				'disabled' => false,
			),
			array(
				'key'      => 'guests',
				'label'    => __( 'Guests', 'hrm-pro' ),
				'icon'     => 'solar:users-group-linear',
				'url'      => admin_url( 'admin.php?page=hrm-guests' ),
				'disabled' => false,
			),
			array(
				'key'      => 'reports',
				'label'    => __( 'Reports', 'hrm-pro' ),
				'icon'     => 'solar:chart-square-linear',
				'url'      => admin_url( 'admin.php?page=hrm-reports' ),
				'disabled' => ! current_user_can( 'hrm_view_reports' ) && ! current_user_can( 'manage_options' ),
			),
			array(
				'key'      => 'settings',
				'label'    => __( 'Settings', 'hrm-pro' ),
				'icon'     => 'solar:settings-linear',
				'url'      => admin_url( 'admin.php?page=hrm-settings' ),
				'disabled' => ! current_user_can( 'hrm_manage_settings' ) && ! current_user_can( 'manage_options' ),
			),
		);
	}

	/**
	 * Return dashboard engine stats.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return array
	 */
	private function get_engine_stats( $hotel_id ) {
		global $wpdb;

		$hotel_id       = absint( $hotel_id );
		$rooms_table    = HRM_Database::table( 'rooms' );
		$bookings_table = HRM_Database::table( 'bookings' );

		$total_rooms = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$rooms_table} WHERE hotel_id = %d",
				$hotel_id
			)
		);

		$total_bookings = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$bookings_table} WHERE hotel_id = %d",
				$hotel_id
			)
		);

		$active_bookings = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$bookings_table} WHERE hotel_id = %d AND status IN ('confirmed','checked_in')",
				$hotel_id
			)
		);

		$paid_revenue = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(amount_paid), 0) FROM {$bookings_table} WHERE hotel_id = %d AND payment_status = %s",
				$hotel_id,
				'paid'
			)
		);

		return array(
			'total_rooms'     => $total_rooms,
			'total_bookings'  => $total_bookings,
			'active_bookings' => $active_bookings,
			'paid_revenue'    => $paid_revenue,
		);
	}

	/**
	 * Return a readable user role label.
	 *
	 * @param WP_User $user User object.
	 * @return string
	 */
	private function get_user_role_label( $user ) {
		if ( is_super_admin( $user->ID ) ) {
			return __( 'Super Admin', 'hrm-pro' );
		}

		if ( in_array( 'hrm_receptionist', (array) $user->roles, true ) ) {
			return __( 'Receptionist', 'hrm-pro' );
		}

		return __( 'Hotel Admin', 'hrm-pro' );
	}
}
