<?php
/**
 * Public storefront integration.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the frontend booking experience and public AJAX endpoints.
 */
class HRM_Public {

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

		if ( did_action( 'init' ) ) {
			$this->register_shortcode_and_block();
		} else {
			add_action( 'init', array( $this, 'register_shortcode_and_block' ) );
		}

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render_confirmation_page' ) );
		add_action( 'hrm_daily_room_status_sync', array( $this, 'sync_due_room_statuses' ) );

		$this->register_ajax( 'hrm_get_room_availability', 'ajax_get_room_availability' );
		$this->register_ajax( 'hrm_calculate_booking_price', 'ajax_calculate_booking_price' );
		$this->register_ajax( 'hrm_initiate_booking_payment', 'ajax_initiate_booking_payment' );
		$this->register_ajax( 'hrm_verify_booking_payment', 'ajax_verify_booking_payment' );
		$this->register_ajax( 'hrm_public_guest_lookup', 'ajax_public_guest_lookup' );
	}

	/**
	 * Register shortcode and Gutenberg block.
	 *
	 * @return void
	 */
	public function register_shortcode_and_block() {
		add_shortcode( 'hrm_booking_page', array( $this, 'render_booking_shortcode' ) );
		add_shortcode( 'hrm_rooms_widget', array( $this, 'render_rooms_widget_shortcode' ) );
		add_shortcode( 'hrm_frontend_dashboard', array( $this, 'render_frontend_dashboard_shortcode' ) );
		add_shortcode( 'hrm_frontend_analytics', array( $this, 'render_frontend_analytics_shortcode' ) );
		add_shortcode( 'hrm_room_analytics', array( $this, 'render_room_analytics_shortcode' ) );
		add_shortcode( 'hrm_platform_dashboard', array( $this, 'render_platform_dashboard_shortcode' ) );

		if ( function_exists( 'register_block_type' ) ) {
			register_block_type(
				'hrm-pro/booking-page',
				array(
					'api_version'     => 2,
					'attributes'      => array(
						'hotelId' => array(
							'type'    => 'number',
							'default' => 0,
						),
					),
					'render_callback' => array( $this, 'render_booking_block' ),
				)
			);
		}
	}

	/**
	 * Register the booking block editor UI.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		wp_register_script( 'hrm-booking-block', '', array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor' ), HRM_VERSION, true );
		wp_enqueue_script( 'hrm-booking-block' );
		wp_add_inline_script(
			'hrm-booking-block',
			"(function(blocks, element, components, i18n, blockEditor){
				var el = element.createElement;
				var InspectorControls = blockEditor.InspectorControls;
				var PanelBody = components.PanelBody;
				var TextControl = components.TextControl;
				blocks.registerBlockType('hrm-pro/booking-page', {
					title: i18n.__('HMS Booking Page', 'hrm-pro'),
					icon: 'building',
					category: 'widgets',
					attributes: { hotelId: { type: 'number', default: 0 } },
					edit: function(props) {
						return el('div', { className: 'hrm-block-preview' },
							el(InspectorControls, {},
								el(PanelBody, { title: i18n.__('Booking Page Settings', 'hrm-pro') },
									el(TextControl, {
										label: i18n.__('Hotel ID', 'hrm-pro'),
										type: 'number',
										value: props.attributes.hotelId || '',
										onChange: function(value) { props.setAttributes({ hotelId: parseInt(value || 0, 10) }); }
									})
								)
							),
							el('strong', {}, i18n.__('HMS Booking Page', 'hrm-pro')),
							el('p', {}, i18n.__('Displays the BENDLESS TECH HMS booking storefront.', 'hrm-pro'))
						);
					},
					save: function() { return null; }
				});
			})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.i18n, window.wp.blockEditor);"
		);
	}

	/**
	 * Render the booking block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_booking_block( $attributes ) {
		$hotel_id = isset( $attributes['hotelId'] ) ? absint( $attributes['hotelId'] ) : 0;

		return $this->render_booking_shortcode( array( 'hotel_id' => $hotel_id ) );
	}

	/**
	 * Render the booking shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_booking_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'hotel_id'   => 0,
				'hotel_slug' => '',
			),
			(array) $atts,
			'hrm_booking_page'
		);

		$hotel_id = $this->resolve_public_hotel_id( $atts );
		$hotel    = HRM_License::get_hotel( $hotel_id );

		$this->enqueue_public_assets();

		$rooms_service   = new HRM_Rooms();
		$rooms           = $hotel ? $rooms_service->public_payloads( $hotel_id ) : array();
		$today           = current_time( 'Y-m-d' );
		$tomorrow        = date( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );
		$hotel_available = $hotel && $hotel->is_active() && HRM_License::hotel_can( $hotel_id, 'frontend_booking' );
		$maintenance     = $hotel_available ? '' : $this->maintenance_message( $hotel );
		$amenity_icons   = $rooms_service->amenity_icons();
		$bank_name       = $hotel ? HRM_Settings::get( 'bank_name', '', $hotel_id ) : '';
		$bank_account    = $hotel ? HRM_Settings::get( 'bank_account_name', '', $hotel_id ) : '';
		$bank_number     = $hotel ? HRM_Settings::get( 'bank_account_number', '', $hotel_id ) : '';
		$bank_enabled    = $hotel && HRM_License::hotel_can( $hotel_id, 'multi_payment' ) && '' !== $bank_name && '' !== $bank_account && '' !== $bank_number;
		$config          = array(
			'hotelId'          => $hotel_id,
			'hotelName'        => $hotel ? $hotel->hotel_name : __( 'Hotel', 'hrm-pro' ),
			'logoUrl'          => $hotel ? $hotel->logo_url : '',
			'tagline'          => $hotel ? HRM_Settings::get( 'hotel_tagline', __( 'Book your stay with confidence.', 'hrm-pro' ), $hotel_id ) : '',
			'hotelEmail'       => $hotel ? $hotel->hotel_email : '',
			'hotelPhone'       => $hotel ? $hotel->hotel_phone : '',
			'brandPrimary'     => $hotel ? HRM_Settings::get( 'brand_primary_color', '#987CC0', $hotel_id ) : '#987CC0',
			'brandButton'      => $hotel ? HRM_Settings::get( 'brand_button_color', '#987CC0', $hotel_id ) : '#987CC0',
			'brandText'        => $hotel ? HRM_Settings::get( 'brand_text_color', '#000000', $hotel_id ) : '#000000',
			'brandFont'        => $hotel ? HRM_Settings::get( 'brand_font_family', 'Inter, system-ui, sans-serif', $hotel_id ) : 'Inter, system-ui, sans-serif',
			'rooms'            => $rooms,
			'amenityIcons'     => $amenity_icons,
			'defaultCheckIn'   => $today,
			'defaultCheckOut'  => $tomorrow,
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'hrm_nonce' ),
			'currencySymbol'   => HRM_Settings::currency_symbol( $hotel_id ),
			'confirmationPath' => home_url( '/booking-confirmation/' ),
			'available'        => $hotel_available,
			'bankTransferEnabled' => (bool) $bank_enabled,
			'bankName'        => $bank_name,
			'bankAccountName' => $bank_account,
			'bankAccountNumber' => $bank_number,
		);

		ob_start();
		include HRM_PLUGIN_DIR . 'public/views/booking-page.php';

		return ob_get_clean();
	}

	/**
	 * Render the rooms widget shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_rooms_widget_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'hotel_id'   => 0,
				'hotel_slug' => '',
			),
			(array) $atts,
			'hrm_rooms_widget'
		);

		return $this->render_booking_shortcode( $atts );
	}

	/**
	 * Render a logged-in frontend dashboard for hotel staff and admins.
	 *
	 * @return string
	 */
	public function render_frontend_dashboard_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<div class="hrm-frontend-dashboard hrm-login-required"><div class="hrm-dashboard-card"><h2>' . esc_html__( 'Hotel dashboard', 'hrm-pro' ) . '</h2><p>' . esc_html__( 'Please log in with your hotel staff account to continue.', 'hrm-pro' ) . '</p><a class="hrm-brand-button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'hrm-pro' ) . '</a></div></div>';
		}

		if ( ! current_user_can( 'hrm_manage_bookings' ) && ! current_user_can( 'manage_options' ) ) {
			return '<div class="hrm-frontend-dashboard hrm-login-required"><div class="hrm-dashboard-card"><h2>' . esc_html__( 'Access unavailable', 'hrm-pro' ) . '</h2><p>' . esc_html__( 'Your account is not assigned to a hotel dashboard.', 'hrm-pro' ) . '</p></div></div>';
		}

		$this->enqueue_public_assets();

		$hotel_id       = HRM_License::get_current_hotel_id();
		$hotel          = HRM_License::get_hotel( $hotel_id );
		$rooms_service  = new HRM_Rooms();
		$bookings       = $hotel_id ? ( new HRM_Bookings() )->admin_list( $hotel_id ) : array();
		$rooms          = $hotel_id ? $rooms_service->all( $hotel_id ) : array();
		$stats          = $this->frontend_dashboard_stats( $hotel_id );
		$expiry_context = HRM_License::get_expiry_context( $hotel );
		$plan           = $hotel ? HRM_License::get_plan( $hotel->plan ) : null;
		$staff_users    = $hotel_id ? $this->frontend_staff_users( $hotel_id ) : array();
		$available_users = get_users( array( 'fields' => array( 'ID', 'display_name', 'user_email' ), 'orderby' => 'display_name' ) );
		$owner_user     = $hotel ? get_userdata( (int) $hotel->user_id ) : false;
		$staff_roles    = $this->frontend_staff_role_options();
		$staff_limit    = $plan ? (int) $plan['staff_limit'] : 0;
		$dashboard_settings = array(
			'vat_rate'            => $hotel ? HRM_Settings::get( 'vat_rate', '7.5', $hotel_id ) : '7.5',
			'currency_symbol'     => $hotel ? HRM_Settings::get( 'currency_symbol', '₦', $hotel_id ) : '₦',
			'brand_primary_color' => $hotel ? HRM_Settings::get( 'brand_primary_color', '#987CC0', $hotel_id ) : '#987CC0',
			'brand_button_color'  => $hotel ? HRM_Settings::get( 'brand_button_color', '#987CC0', $hotel_id ) : '#987CC0',
			'brand_text_color'    => $hotel ? HRM_Settings::get( 'brand_text_color', '#000000', $hotel_id ) : '#000000',
			'brand_font_family'   => $hotel ? HRM_Settings::get( 'brand_font_family', 'Inter, system-ui, sans-serif', $hotel_id ) : 'Inter, system-ui, sans-serif',
			'bank_name'           => $hotel ? HRM_Settings::get( 'bank_name', '', $hotel_id ) : '',
			'bank_account_name'   => $hotel ? HRM_Settings::get( 'bank_account_name', '', $hotel_id ) : '',
			'bank_account_number' => $hotel ? HRM_Settings::get( 'bank_account_number', '', $hotel_id ) : '',
		);
		$rooms_page_id     = $hotel_id ? absint( get_option( 'hrm_hotel_' . $hotel_id . '_rooms_page_id', 0 ) ) : 0;
		$booking_page_id   = $hotel_id ? absint( get_option( 'hrm_hotel_' . $hotel_id . '_booking_page_id', 0 ) ) : 0;
		$checkout_page_id  = $hotel_id ? absint( get_option( 'hrm_hotel_' . $hotel_id . '_checkout_page_id', 0 ) ) : 0;
		$dashboard_page_id = absint( get_option( 'hrm_frontend_dashboard_page_id', 0 ) );
		$analytics_page_id = absint( get_option( 'hrm_frontend_analytics_page_id', 0 ) );
		$room_analytics_page_id = $hotel_id ? absint( get_option( 'hrm_hotel_' . $hotel_id . '_room_analytics_page_id', 0 ) ) : 0;
		if ( ! $room_analytics_page_id ) {
			$room_analytics_page_id = absint( get_option( 'hrm_room_analytics_page_id', 0 ) );
		}
		$page_links        = array(
			'rooms'          => $rooms_page_id ? get_permalink( $rooms_page_id ) : '',
			'booking'        => $booking_page_id ? get_permalink( $booking_page_id ) : '',
			'checkout'       => $checkout_page_id ? get_permalink( $checkout_page_id ) : '',
			'dashboard'      => $dashboard_page_id ? get_permalink( $dashboard_page_id ) : '',
			'analytics'      => $analytics_page_id ? get_permalink( $analytics_page_id ) : '',
			'room_analytics' => $room_analytics_page_id ? get_permalink( $room_analytics_page_id ) : '',
		);
		$config         = array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'hrm_nonce' ),
			'hotelId'       => $hotel_id,
			'canAdmin'      => $this->current_user_can_hotel_admin(),
			'currencySymbol'=> HRM_Settings::currency_symbol( $hotel_id ),
			'brandPrimary'  => $hotel ? HRM_Settings::get( 'brand_primary_color', '#987CC0', $hotel_id ) : '#987CC0',
			'brandButton'   => $hotel ? HRM_Settings::get( 'brand_button_color', '#987CC0', $hotel_id ) : '#987CC0',
			'brandText'     => $hotel ? HRM_Settings::get( 'brand_text_color', '#000000', $hotel_id ) : '#000000',
			'brandFont'     => $hotel ? HRM_Settings::get( 'brand_font_family', 'Inter, system-ui, sans-serif', $hotel_id ) : 'Inter, system-ui, sans-serif',
		);

		ob_start();
		include HRM_PLUGIN_DIR . 'public/views/frontend-dashboard.php';

		return ob_get_clean();
	}

	/**
	 * Render a frontend analytics page for hotel admins.
	 *
	 * @return string
	 */
	public function render_frontend_analytics_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<div class="hrm-frontend-dashboard hrm-login-required"><div class="hrm-dashboard-card"><h2>' . esc_html__( 'Hotel analytics', 'hrm-pro' ) . '</h2><p>' . esc_html__( 'Please log in with your hotel admin account to continue.', 'hrm-pro' ) . '</p><a class="hrm-brand-button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'hrm-pro' ) . '</a></div></div>';
		}

		if ( ! current_user_can( 'hrm_view_reports' ) && ! current_user_can( 'manage_options' ) ) {
			return '<div class="hrm-frontend-dashboard hrm-login-required"><div class="hrm-dashboard-card"><h2>' . esc_html__( 'Analytics unavailable', 'hrm-pro' ) . '</h2><p>' . esc_html__( 'Only hotel admins can view analytics.', 'hrm-pro' ) . '</p></div></div>';
		}

		$this->enqueue_public_assets();

		$hotel_id          = HRM_License::get_current_hotel_id();
		$hotel             = HRM_License::get_hotel( $hotel_id );
		$from              = isset( $_GET['hrm_from'] ) ? sanitize_text_field( wp_unslash( $_GET['hrm_from'] ) ) : '';
		$to                = isset( $_GET['hrm_to'] ) ? sanitize_text_field( wp_unslash( $_GET['hrm_to'] ) ) : '';
		if ( '' === $from && '' === $to ) {
			$from = current_time( 'Y-m-d' );
			$to   = current_time( 'Y-m-d' );
		}
		$analytics         = $hotel_id ? ( new HRM_Reports() )->analytics_dashboard( $hotel_id, $from, $to ) : array();
		$dashboard_page_id = absint( get_option( 'hrm_frontend_dashboard_page_id', 0 ) );
		$config            = array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'hrm_nonce' ),
			'hotelId'        => $hotel_id,
			'currencySymbol' => HRM_Settings::currency_symbol( $hotel_id ),
			'brandPrimary'   => $hotel ? HRM_Settings::get( 'brand_primary_color', '#987CC0', $hotel_id ) : '#987CC0',
			'brandButton'    => $hotel ? HRM_Settings::get( 'brand_button_color', '#987CC0', $hotel_id ) : '#987CC0',
			'brandText'      => $hotel ? HRM_Settings::get( 'brand_text_color', '#000000', $hotel_id ) : '#000000',
			'brandFont'      => $hotel ? HRM_Settings::get( 'brand_font_family', 'Inter, system-ui, sans-serif', $hotel_id ) : 'Inter, system-ui, sans-serif',
			'dashboardUrl'   => $dashboard_page_id ? get_permalink( $dashboard_page_id ) : '',
		);
		$feature_available = (bool) $hotel;
		$can_detailed      = $hotel && HRM_License::hotel_can( $hotel_id, 'advanced_reports' );

		ob_start();
		include HRM_PLUGIN_DIR . 'public/views/frontend-analytics.php';

		return ob_get_clean();
	}

	/**
	 * Render dedicated room analytics for Standard plans and higher.
	 *
	 * @return string
	 */
	public function render_room_analytics_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<div class="hrm-frontend-dashboard hrm-login-required"><div class="hrm-dashboard-card"><h2>' . esc_html__( 'Room Analytics', 'hrm-pro' ) . '</h2><p>' . esc_html__( 'Please log in with your hotel admin account to continue.', 'hrm-pro' ) . '</p><a class="hrm-brand-button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'hrm-pro' ) . '</a></div></div>';
		}

		if ( ! current_user_can( 'hrm_view_reports' ) && ! current_user_can( 'manage_options' ) ) {
			return '<div class="hrm-frontend-dashboard hrm-login-required"><div class="hrm-dashboard-card"><h2>' . esc_html__( 'Room analytics unavailable', 'hrm-pro' ) . '</h2><p>' . esc_html__( 'Only hotel admins can view room analytics.', 'hrm-pro' ) . '</p></div></div>';
		}

		$this->enqueue_public_assets();

		$hotel_id = HRM_License::get_current_hotel_id();
		$hotel    = HRM_License::get_hotel( $hotel_id );
		if ( ! $hotel ) {
			return '<div class="hrm-frontend-dashboard"><div class="hrm-dashboard-card p-8 text-center"><h2>' . esc_html__( 'No hotel assigned', 'hrm-pro' ) . '</h2><p>' . esc_html__( 'Ask the platform administrator to assign this account to a hotel.', 'hrm-pro' ) . '</p></div></div>';
		}

		$feature_available = HRM_License::hotel_can( $hotel_id, 'room_analytics' );
		$range             = $this->analytics_filter_range();
		$reports           = new HRM_Reports();
		$analytics         = $feature_available ? $reports->analytics_dashboard( $hotel_id, $range['from'], $range['to'] ) : array( 'range' => $range );
		$room_conditions   = $feature_available ? $this->room_condition_summary( $hotel_id ) : array();
		$dashboard_page_id = absint( get_option( 'hrm_frontend_dashboard_page_id', 0 ) );
		$analytics_page_id = absint( get_option( 'hrm_frontend_analytics_page_id', 0 ) );
		$config            = array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'hrm_nonce' ),
			'hotelId'        => $hotel_id,
			'currencySymbol' => HRM_Settings::currency_symbol( $hotel_id ),
			'brandPrimary'   => HRM_Settings::get( 'brand_primary_color', '#987CC0', $hotel_id ),
			'brandButton'    => HRM_Settings::get( 'brand_button_color', '#987CC0', $hotel_id ),
			'brandText'      => HRM_Settings::get( 'brand_text_color', '#000000', $hotel_id ),
			'brandFont'      => HRM_Settings::get( 'brand_font_family', 'Inter, system-ui, sans-serif', $hotel_id ),
			'dashboardUrl'   => $dashboard_page_id ? get_permalink( $dashboard_page_id ) : '',
			'analyticsUrl'   => $analytics_page_id ? get_permalink( $analytics_page_id ) : '',
		);
		$can_explain       = HRM_License::hotel_can( $hotel_id, 'advanced_reports' );

		ob_start();
		include HRM_PLUGIN_DIR . 'public/views/room-analytics.php';

		return ob_get_clean();
	}

	/**
	 * Render a frontend platform dashboard for the SaaS super admin.
	 *
	 * @return string
	 */
	public function render_platform_dashboard_shortcode() {
		global $wpdb;

		if ( ! is_user_logged_in() ) {
			return '<div class="hrm-frontend-dashboard hrm-login-required"><div class="hrm-dashboard-card"><h2>' . esc_html__( 'Platform dashboard', 'hrm-pro' ) . '</h2><p>' . esc_html__( 'Please log in with your platform administrator account.', 'hrm-pro' ) . '</p><a class="hrm-brand-button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'hrm-pro' ) . '</a></div></div>';
		}

		if ( ! is_super_admin() ) {
			return '<div class="hrm-frontend-dashboard hrm-login-required"><div class="hrm-dashboard-card"><h2>' . esc_html__( 'Access denied', 'hrm-pro' ) . '</h2><p>' . esc_html__( 'Only the platform super admin can view this page.', 'hrm-pro' ) . '</p></div></div>';
		}

		$platform_enabled = defined( 'BENDLESS_HMS_PLATFORM_MASTER' ) && BENDLESS_HMS_PLATFORM_MASTER;
		$platform_enabled = $platform_enabled || (bool) get_option( 'hrm_platform_master_mode', false );
		if ( ! $platform_enabled ) {
			return '<div class="hrm-frontend-dashboard hrm-login-required"><div class="hrm-dashboard-card"><h2>' . esc_html__( 'Platform master mode is off', 'hrm-pro' ) . '</h2><p>' . esc_html__( 'Enable hrm_platform_master_mode on your platform WordPress install to view subscriber hotels here. This keeps tenant installs from managing subscriptions.', 'hrm-pro' ) . '</p></div></div>';
		}

		$this->enqueue_public_assets();

		$hotels = $wpdb->get_results(
			"SELECT h.*, u.user_email AS owner_email, u.display_name AS owner_name,
				(SELECT COUNT(*) FROM {$wpdb->prefix}hrm_rooms r WHERE r.hotel_id = h.id) AS rooms_count,
				(SELECT COALESCE(SUM(s.amount_paid), 0) FROM {$wpdb->prefix}hrm_subscriptions s WHERE s.hotel_id = h.id) AS lifetime_revenue
			FROM {$wpdb->prefix}hrm_hotels h
			LEFT JOIN {$wpdb->users} u ON u.ID = h.user_id
			ORDER BY h.created_at DESC"
		);

		$stats = array(
			'total_hotels'         => count( $hotels ),
			'active_subscriptions' => 0,
			'expired'              => 0,
			'trial'                => 0,
			'total_revenue'        => 0,
		);

		foreach ( $hotels as $hotel_row ) {
			if ( 'active' === $hotel_row->subscription_status ) {
				$stats['active_subscriptions']++;
			}
			if ( in_array( $hotel_row->subscription_status, array( 'expired', 'past_due' ), true ) ) {
				$stats['expired']++;
			}
			if ( 'trial' === $hotel_row->subscription_status ) {
				$stats['trial']++;
			}
			$stats['total_revenue'] += (float) $hotel_row->lifetime_revenue;
		}

		ob_start();
		include HRM_PLUGIN_DIR . 'public/views/platform-dashboard.php';

		return ob_get_clean();
	}

	/**
	 * Create or update public HMS pages.
	 *
	 * @param int $hotel_id Optional hotel ID.
	 * @return array
	 */
	public static function ensure_frontend_pages( $hotel_id = 0 ) {
		global $wpdb;

		$created = array(
			'core'   => array(),
			'hotels' => array(),
		);

		$core_pages = array(
			'hms-rooms'            => array(
				'title'   => __( 'Hotel Rooms', 'hrm-pro' ),
				'content' => '[hrm_rooms_widget]',
				'option'  => 'hrm_rooms_page_id',
			),
			'hms-booking'          => array(
				'title'   => __( 'Hotel Booking', 'hrm-pro' ),
				'content' => '[hrm_booking_page]',
				'option'  => 'hrm_booking_page_id',
			),
			'hms-checkout'         => array(
				'title'   => __( 'Hotel Checkout', 'hrm-pro' ),
				'content' => '[hrm_booking_page]',
				'option'  => 'hrm_checkout_page_id',
			),
			'hms-dashboard'        => array(
				'title'   => __( 'Hotel Staff Dashboard', 'hrm-pro' ),
				'content' => '[hrm_frontend_dashboard]',
				'option'  => 'hrm_frontend_dashboard_page_id',
			),
			'hms-analytics'        => array(
				'title'   => __( 'Hotel Analytics', 'hrm-pro' ),
				'content' => '[hrm_frontend_analytics]',
				'option'  => 'hrm_frontend_analytics_page_id',
			),
			'hms-room-analytics'   => array(
				'title'   => __( 'Room Analytics', 'hrm-pro' ),
				'content' => '[hrm_room_analytics]',
				'option'  => 'hrm_room_analytics_page_id',
			),
			'hms-platform-dashboard' => array(
				'title'   => __( 'HMS Platform Dashboard', 'hrm-pro' ),
				'content' => '[hrm_platform_dashboard]',
				'option'  => 'hrm_platform_dashboard_page_id',
			),
			'booking-confirmation' => array(
				'title'   => __( 'Booking Confirmation', 'hrm-pro' ),
				'content' => __( 'Your booking confirmation will appear here after payment.', 'hrm-pro' ),
				'option'  => 'hrm_booking_confirmation_page_id',
			),
		);

		foreach ( $core_pages as $slug => $page ) {
			$page_id = self::ensure_page( $page['title'], $slug, $page['content'] );
			update_option( $page['option'], $page_id, false );
			$created['core'][ $slug ] = $page_id;
		}

		$hotel_ids = array();
		if ( $hotel_id ) {
			$hotel_ids[] = absint( $hotel_id );
		} else {
			$hotel_ids = array_map(
				'absint',
				$wpdb->get_col( "SELECT id FROM {$wpdb->prefix}hrm_hotels ORDER BY id ASC" )
			);
		}

		foreach ( $hotel_ids as $id ) {
			$hotel = HRM_License::get_hotel( $id );
			if ( ! $hotel ) {
				continue;
			}

			$created['hotels'][ $id ] = array(
				'rooms'    => self::ensure_page( sprintf( __( '%s Rooms', 'hrm-pro' ), $hotel->hotel_name ), $hotel->hotel_slug . '-rooms', '[hrm_rooms_widget hotel_id="' . absint( $id ) . '"]', $id, 'rooms' ),
				'booking'  => self::ensure_page( sprintf( __( '%s Booking', 'hrm-pro' ), $hotel->hotel_name ), $hotel->hotel_slug . '-booking', '[hrm_booking_page hotel_id="' . absint( $id ) . '"]', $id, 'booking' ),
				'checkout' => self::ensure_page( sprintf( __( '%s Checkout', 'hrm-pro' ), $hotel->hotel_name ), $hotel->hotel_slug . '-checkout', '[hrm_booking_page hotel_id="' . absint( $id ) . '"]', $id, 'checkout' ),
				'analytics'=> self::ensure_page( sprintf( __( '%s Analytics', 'hrm-pro' ), $hotel->hotel_name ), $hotel->hotel_slug . '-analytics', '[hrm_frontend_analytics]', $id, 'analytics' ),
				'room_analytics' => self::ensure_page( sprintf( __( '%s Room Analytics', 'hrm-pro' ), $hotel->hotel_name ), $hotel->hotel_slug . '-room-analytics', '[hrm_room_analytics]', $id, 'room_analytics' ),
			);
		}

		return $created;
	}

	/**
	 * Create a page if it does not exist yet.
	 *
	 * @param string $title     Page title.
	 * @param string $slug      Page slug.
	 * @param string $content   Page content.
	 * @param int    $hotel_id  Optional hotel ID.
	 * @param string $page_kind Optional hotel page kind.
	 * @return int
	 */
	private static function ensure_page( $title, $slug, $content, $hotel_id = 0, $page_kind = '' ) {
		global $wpdb;

		$title     = sanitize_text_field( $title );
		$slug      = sanitize_title( $slug );
		$content   = (string) $content;
		$hotel_id  = absint( $hotel_id );
		$page_kind = sanitize_key( $page_kind );
		$option    = $hotel_id && $page_kind ? 'hrm_hotel_' . $hotel_id . '_' . $page_kind . '_page_id' : '';

		if ( $option ) {
			$stored_page_id = absint( get_option( $option, 0 ) );
			$stored_page    = $stored_page_id ? get_post( $stored_page_id ) : null;
			if ( $stored_page && 'trash' !== $stored_page->post_status ) {
				return $stored_page_id;
			}
		}

		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_name = %s AND post_status != %s ORDER BY ID ASC LIMIT 1",
				'page',
				$slug,
				'trash'
			)
		);
		$existing    = $existing_id ? get_post( (int) $existing_id ) : null;
		if ( $existing && 'trash' !== $existing->post_status ) {
			if ( '' === trim( (string) $existing->post_content ) ) {
				wp_update_post(
					array(
						'ID'           => (int) $existing->ID,
						'post_content' => $content,
					)
				);
			}

			if ( $option ) {
				update_option( $option, (int) $existing->ID, false );
			}

			update_post_meta( (int) $existing->ID, '_hrm_auto_page', '1' );
			if ( $hotel_id ) {
				update_post_meta( (int) $existing->ID, '_hrm_auto_hotel_id', $hotel_id );
			}
			if ( $page_kind ) {
				update_post_meta( (int) $existing->ID, '_hrm_auto_page_kind', $page_kind );
			}

			return (int) $existing->ID;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return 0;
		}

		update_post_meta( (int) $page_id, '_hrm_auto_page', '1' );
		if ( $hotel_id ) {
			update_post_meta( (int) $page_id, '_hrm_auto_hotel_id', $hotel_id );
		}
		if ( $page_kind ) {
			update_post_meta( (int) $page_id, '_hrm_auto_page_kind', $page_kind );
		}
		if ( $option ) {
			update_option( $option, (int) $page_id, false );
		}

		return (int) $page_id;
	}

	/**
	 * AJAX: return room availability for selected dates.
	 *
	 * @return void
	 */
	public function ajax_get_room_availability() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		$hotel_id    = isset( $_POST['hotel_id'] ) ? absint( wp_unslash( $_POST['hotel_id'] ) ) : 0;
		$check_in    = isset( $_POST['check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) ) : '';
		$check_out   = isset( $_POST['check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) ) : '';
		$guest_count = isset( $_POST['guests'] ) ? absint( wp_unslash( $_POST['guests'] ) ) : 1;

		$available = $this->assert_hotel_available( $hotel_id );
		if ( is_wp_error( $available ) ) {
			wp_send_json_error( array( 'message' => $available->get_error_message() ), 403 );
		}

		if ( ! $this->valid_date_range( $check_in, $check_out ) ) {
			wp_send_json_error( array( 'message' => __( 'Choose valid check-in and check-out dates.', 'hrm-pro' ) ), 400 );
		}

		$rooms = new HRM_Rooms();
		wp_send_json_success(
			array(
				'availability' => $rooms->availability_payloads( $hotel_id, $check_in, $check_out, $guest_count ),
			)
		);
	}

	/**
	 * AJAX: calculate a booking price.
	 *
	 * @return void
	 */
	public function ajax_calculate_booking_price() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		$hotel_id    = isset( $_POST['hotel_id'] ) ? absint( wp_unslash( $_POST['hotel_id'] ) ) : 0;
		$room_id     = isset( $_POST['room_id'] ) ? absint( wp_unslash( $_POST['room_id'] ) ) : 0;
		$check_in    = isset( $_POST['check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) ) : '';
		$check_out   = isset( $_POST['check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) ) : '';
		$guest_count = isset( $_POST['guests'] ) ? absint( wp_unslash( $_POST['guests'] ) ) : 1;

		$available = $this->assert_hotel_available( $hotel_id );
		if ( is_wp_error( $available ) ) {
			wp_send_json_error( array( 'message' => $available->get_error_message() ), 403 );
		}

		if ( ! $this->valid_date_range( $check_in, $check_out ) || ! $room_id ) {
			wp_send_json_error( array( 'message' => __( 'Booking dates and room are required.', 'hrm-pro' ) ), 400 );
		}

		$rooms = new HRM_Rooms();
		$room  = $rooms->get( $room_id, $hotel_id );
		if ( ! $room || $guest_count > (int) $room->max_guests || ! $rooms->is_available( $room_id, $check_in, $check_out, $hotel_id ) ) {
			wp_send_json_error( array( 'message' => __( 'This room is not available for the selected dates.', 'hrm-pro' ) ), 409 );
		}

		$rate = ( new HRM_Bookings() )->calculate_rate( $room_id, $check_in, $check_out, $hotel_id );
		if ( ! $rate ) {
			wp_send_json_error( array( 'message' => __( 'The price could not be calculated.', 'hrm-pro' ) ), 400 );
		}

		wp_send_json_success(
			array(
				'rate' => $this->format_rate_payload( $rate, $hotel_id ),
			)
		);
	}

	/**
	 * AJAX: validate a booking intent and start Paystack checkout.
	 *
	 * @return void
	 */
	public function ajax_initiate_booking_payment() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		$hotel_id = isset( $_POST['hotel_id'] ) ? absint( wp_unslash( $_POST['hotel_id'] ) ) : 0;
		$terms    = isset( $_POST['terms'] ) && '1' === (string) wp_unslash( $_POST['terms'] );

		$available = $this->assert_hotel_available( $hotel_id );
		if ( is_wp_error( $available ) ) {
			wp_send_json_error( array( 'message' => $available->get_error_message() ), 403 );
		}

		if ( ! $terms ) {
			wp_send_json_error( array( 'message' => __( 'Please accept the booking terms to continue.', 'hrm-pro' ) ), 400 );
		}

		$payment_method = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'paystack';
		$payment_method = in_array( $payment_method, array( 'paystack', 'transfer' ), true ) ? $payment_method : 'paystack';
		$email          = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( '' === $email ) {
			wp_send_json_error( array( 'message' => __( 'A valid email address is required for checkout.', 'hrm-pro' ) ), 400 );
		}

		$room_id     = isset( $_POST['room_id'] ) ? absint( wp_unslash( $_POST['room_id'] ) ) : 0;
		$check_in    = isset( $_POST['check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) ) : '';
		$check_out   = isset( $_POST['check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) ) : '';
		$guest_count = isset( $_POST['guests'] ) ? max( 1, absint( wp_unslash( $_POST['guests'] ) ) ) : 1;
		$full_name   = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
		$phone       = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

		if ( '' === $full_name || '' === $phone || ! $room_id || ! $this->valid_date_range( $check_in, $check_out ) ) {
			wp_send_json_error( array( 'message' => __( 'Please complete the guest details, room, and booking dates.', 'hrm-pro' ) ), 400 );
		}

		$rooms = new HRM_Rooms();
		$room  = $rooms->get( $room_id, $hotel_id );
		if ( ! $room || $guest_count > (int) $room->max_guests || ! $rooms->is_available( $room_id, $check_in, $check_out, $hotel_id ) ) {
			wp_send_json_error( array( 'message' => __( 'This room is no longer available for the selected dates.', 'hrm-pro' ) ), 409 );
		}

		$guest = ( new HRM_Guests() )->find_by_phone( $phone, $hotel_id );
		if ( $guest && 'blacklist' === $guest->flag ) {
			wp_send_json_error( array( 'message' => __( 'We are unable to complete this reservation. Please contact the hotel directly.', 'hrm-pro' ) ), 403 );
		}

		$rate = ( new HRM_Bookings() )->calculate_rate( $room_id, $check_in, $check_out, $hotel_id );
		if ( ! $rate ) {
			wp_send_json_error( array( 'message' => __( 'The booking price could not be calculated.', 'hrm-pro' ) ), 400 );
		}

		$intent = array(
			'room_id'     => $room_id,
			'check_in'    => $check_in,
			'check_out'   => $check_out,
			'guest_count' => $guest_count,
			'full_name'   => $full_name,
			'phone'       => $phone,
			'email'       => $email,
			'id_type'     => isset( $_POST['id_type'] ) ? sanitize_text_field( wp_unslash( $_POST['id_type'] ) ) : '',
			'id_number'   => isset( $_POST['id_number'] ) ? sanitize_text_field( wp_unslash( $_POST['id_number'] ) ) : '',
			'notes'       => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
		);

		if ( 'transfer' === $payment_method ) {
			if ( ! HRM_License::hotel_can( $hotel_id, 'multi_payment' ) ) {
				wp_send_json_error( array( 'message' => __( 'Bank transfer is not available on this hotel plan.', 'hrm-pro' ) ), 403 );
			}

			$bank_name    = HRM_Settings::get( 'bank_name', '', $hotel_id );
			$bank_account = HRM_Settings::get( 'bank_account_name', '', $hotel_id );
			$bank_number  = HRM_Settings::get( 'bank_account_number', '', $hotel_id );
			if ( '' === $bank_name || '' === $bank_account || '' === $bank_number ) {
				wp_send_json_error( array( 'message' => __( 'Bank transfer is not configured for this hotel yet.', 'hrm-pro' ) ), 400 );
			}

			$transfer_ref = isset( $_POST['transfer_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['transfer_ref'] ) ) : '';
			if ( '' === $transfer_ref ) {
				wp_send_json_error( array( 'message' => __( 'Enter your bank transfer reference or depositor name.', 'hrm-pro' ) ), 400 );
			}

			$booking_reference              = 'HRM-TRF-' . $hotel_id . '-' . time() . '-' . wp_rand( 1000, 9999 );
			$intent['payment_method']       = 'transfer';
			$intent['payment_status']       = 'transfer_pending';
			$intent['status']               = 'pending';
			$intent['amount_paid']          = 0;
			$intent['transfer_ref']         = $transfer_ref;
			$intent['paystack_reference']   = $booking_reference;
			$intent['verified_total_amount'] = 0;

			$result = ( new HRM_Bookings() )->create_frontend_booking( $hotel_id, $intent );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
			}

			$booking_id = absint( $result['booking_id'] );
			if ( class_exists( 'HRM_WhatsApp' ) ) {
				( new HRM_WhatsApp() )->send( '', 'invoice', $booking_id );
			}

			HRM_Activity_Log::log(
				$hotel_id,
				0,
				'frontend_transfer_booking_submitted',
				'booking',
				$booking_id,
				wp_json_encode(
					array(
						'transfer_ref' => $transfer_ref,
						'total'        => $rate['total'],
					)
				)
			);

			wp_send_json_success(
				array(
					'booking_id'        => $booking_id,
					'confirmation_url'  => add_query_arg( 'hrm_ref', rawurlencode( $booking_reference ), home_url( '/booking-confirmation/' ) ),
					'payment_method'    => 'transfer',
				)
			);
		}

		$url = $this->paystack->initiate_booking_intent( $hotel_id, $rate['total'], $email, $intent );
		if ( is_wp_error( $url ) ) {
			wp_send_json_error( array( 'message' => $url->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'booking_id'        => 0,
				'authorization_url' => $url,
			)
		);
	}

	/**
	 * AJAX: verify a booking payment.
	 *
	 * @return void
	 */
	public function ajax_verify_booking_payment() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		$reference = isset( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '';
		$result    = $this->paystack->verify_booking_payment( $reference );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Payment verification failed.', 'hrm-pro' ) ), 400 );
		}

		wp_send_json_success(
			array(
				'booking_id'       => $result,
				'confirmation_url' => add_query_arg( 'hrm_ref', rawurlencode( $reference ), home_url( '/booking-confirmation/' ) ),
			)
		);
	}

	/**
	 * AJAX: public guest lookup by phone.
	 *
	 * @return void
	 */
	public function ajax_public_guest_lookup() {
		check_ajax_referer( 'hrm_nonce', 'nonce' );

		$hotel_id = isset( $_POST['hotel_id'] ) ? absint( wp_unslash( $_POST['hotel_id'] ) ) : 0;
		$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

		$available = $this->assert_hotel_available( $hotel_id );
		if ( is_wp_error( $available ) ) {
			wp_send_json_error( array( 'message' => $available->get_error_message() ), 403 );
		}

		$guest = ( new HRM_Guests() )->find_by_phone( $phone, $hotel_id );
		if ( ! $guest ) {
			wp_send_json_success( array( 'guest' => null ) );
		}

		wp_send_json_success( array( 'guest' => ( new HRM_Guests() )->to_public_payload( $guest ) ) );
	}

	/**
	 * Render Paystack return confirmation page.
	 *
	 * @return void
	 */
	public function maybe_render_confirmation_page() {
		global $wp;

		$request_path = isset( $wp->request ) ? trim( $wp->request, '/' ) : '';
		if ( 'booking-confirmation' !== $request_path ) {
			return;
		}

		$reference      = isset( $_GET['hrm_ref'] ) ? sanitize_text_field( wp_unslash( $_GET['hrm_ref'] ) ) : '';
		$existing       = $reference ? ( new HRM_Bookings() )->get_by_paystack_reference( $reference ) : false;
		$booking_id     = 0;
		$error_message  = '';

		if ( $reference && $existing && 'transfer' === $existing->payment_method ) {
			$booking_id = (int) $existing->id;
		} elseif ( $reference ) {
			$result = $this->paystack->verify_booking_payment( $reference );
			if ( is_wp_error( $result ) ) {
				$error_message = $result->get_error_message();
			} elseif ( $result ) {
				$booking_id = (int) $result;
			}
		} else {
			$error_message = __( 'No booking payment reference was supplied.', 'hrm-pro' );
		}

		if ( ! $booking_id && $existing ) {
			$booking_id = (int) $existing->id;
		}

		$payload = $booking_id ? ( new HRM_Bookings() )->confirmation_payload( $booking_id ) : false;

		status_header( 200 );
		nocache_headers();

		include HRM_PLUGIN_DIR . 'public/views/confirmation.php';
		exit;
	}

	/**
	 * Sync room statuses from due bookings.
	 *
	 * @return void
	 */
	public function sync_due_room_statuses() {
		( new HRM_Bookings() )->sync_due_room_statuses();
	}

	/**
	 * Register public AJAX action hooks.
	 *
	 * @param string $action   Action name.
	 * @param string $callback Method callback.
	 * @return void
	 */
	private function register_ajax( $action, $callback ) {
		add_action( 'wp_ajax_' . $action, array( $this, $callback ) );
		add_action( 'wp_ajax_nopriv_' . $action, array( $this, $callback ) );
	}

	/**
	 * Enqueue public CSS and JavaScript.
	 *
	 * @return void
	 */
	private function enqueue_public_assets() {
		wp_enqueue_style( 'hrm-public-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', array(), HRM_VERSION );
		wp_enqueue_style( 'hrm-public', HRM_PLUGIN_URL . 'public/assets/public.css', array(), HRM_VERSION );
		wp_enqueue_script( 'hrm-tailwind', 'https://cdn.tailwindcss.com', array(), HRM_VERSION, false );
		wp_add_inline_script(
			'hrm-tailwind',
			"tailwind.config = { theme: { extend: { colors: { primary: { 50: '#F8F5FC', 100: '#EEE7F6', 500: '#987CC0', 600: '#866CB0', 700: '#6F5599', 900: '#20142E' }, surface: { 50: '#ffffff', 100: '#f8f8f8', 200: '#e8e8e8', 800: '#111111', 900: '#000000' } }, fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }, boxShadow: { card: '0 1px 3px 0 rgb(0 0 0 / 0.08), 0 1px 2px -1px rgb(0 0 0 / 0.08)', modal: '0 20px 60px -10px rgb(0 0 0 / 0.3)' } } } } };",
			'before'
		);
		wp_enqueue_script( 'hrm-iconify', 'https://code.iconify.design/3/3.1.0/iconify.min.js', array(), HRM_VERSION, true );
		wp_enqueue_script( 'hrm-public', HRM_PLUGIN_URL . 'public/assets/public.js', array(), HRM_VERSION, true );
	}

	/**
	 * Return whether current user has hotel admin powers.
	 *
	 * @return bool
	 */
	private function current_user_can_hotel_admin() {
		return current_user_can( 'manage_options' ) || current_user_can( 'hrm_manage_settings' );
	}

	/**
	 * Return staff role options shown in frontend hotel admin tools.
	 *
	 * @return array
	 */
	private function frontend_staff_role_options() {
		return array(
			'receptionist' => __( 'Receptionist', 'hrm-pro' ),
			'hotel_admin'  => __( 'Hotel Admin', 'hrm-pro' ),
		);
	}

	/**
	 * Return staff users assigned to a hotel.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return array
	 */
	private function frontend_staff_users( $hotel_id ) {
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
			$role = get_user_meta( $user->ID, 'hrm_staff_role', true );
			$payload[] = array(
				'id'    => (int) $user->ID,
				'name'  => (string) $user->display_name,
				'email' => (string) $user->user_email,
				'role'  => $role ? (string) $role : 'receptionist',
			);
		}

		return $payload;
	}

	/**
	 * Resolve a public hotel ID from shortcode attributes or sensible defaults.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return int
	 */
	private function resolve_public_hotel_id( $atts ) {
		global $wpdb;

		$hotel_id = isset( $atts['hotel_id'] ) ? absint( $atts['hotel_id'] ) : 0;
		if ( $hotel_id && HRM_License::get_hotel( $hotel_id ) ) {
			return $hotel_id;
		}

		$slug = isset( $atts['hotel_slug'] ) ? sanitize_title( $atts['hotel_slug'] ) : '';
		if ( $slug ) {
			$hotel = HRM_License::get_hotel_by_slug( $slug );
			if ( $hotel ) {
				return (int) $hotel->id;
			}
		}

		if ( isset( $_GET['hrm_hotel'] ) ) {
			$requested = sanitize_text_field( wp_unslash( $_GET['hrm_hotel'] ) );
			if ( is_numeric( $requested ) ) {
				$hotel = HRM_License::get_hotel( absint( $requested ) );
			} else {
				$hotel = HRM_License::get_hotel_by_slug( $requested );
			}

			if ( ! empty( $hotel ) ) {
				return (int) $hotel->id;
			}
		}

		$current_hotel_id = HRM_License::get_current_hotel_id();
		if ( $current_hotel_id && HRM_License::get_hotel( $current_hotel_id ) ) {
			return $current_hotel_id;
		}

		return (int) $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}hrm_hotels WHERE is_active = 1 ORDER BY id ASC LIMIT 1" );
	}

	/**
	 * Return frontend dashboard stats.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return array
	 */
	private function frontend_dashboard_stats( $hotel_id ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		if ( ! $hotel_id ) {
			return array(
				'total_rooms'     => 0,
				'active_bookings' => 0,
				'paid_revenue'    => 0,
				'outstanding'     => 0,
			);
		}

		$rooms_table    = HRM_Database::table( 'rooms' );
		$bookings_table = HRM_Database::table( 'bookings' );

		return array(
			'total_rooms'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$rooms_table} WHERE hotel_id = %d", $hotel_id ) ),
			'active_bookings' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bookings_table} WHERE hotel_id = %d AND status IN ('pending','confirmed','checked_in')", $hotel_id ) ),
			'paid_revenue'    => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount_paid), 0) FROM {$bookings_table} WHERE hotel_id = %d", $hotel_id ) ),
			'outstanding'     => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(balance), 0) FROM {$bookings_table} WHERE hotel_id = %d AND payment_status <> 'paid'", $hotel_id ) ),
		);
	}

	/**
	 * Resolve frontend analytics date filters and presets.
	 *
	 * @return array
	 */
	private function analytics_filter_range() {
		$today  = current_time( 'Y-m-d' );
		$period = isset( $_GET['hrm_period'] ) ? sanitize_key( wp_unslash( $_GET['hrm_period'] ) ) : 'today';
		$from   = isset( $_GET['hrm_from'] ) ? sanitize_text_field( wp_unslash( $_GET['hrm_from'] ) ) : '';
		$to     = isset( $_GET['hrm_to'] ) ? sanitize_text_field( wp_unslash( $_GET['hrm_to'] ) ) : '';

		if ( '' !== $from || '' !== $to ) {
			$start = strtotime( $from );
			$end   = strtotime( $to );
			if ( $start && $end ) {
				if ( $end < $start ) {
					$tmp   = $start;
					$start = $end;
					$end   = $tmp;
				}

				return array(
					'from'   => date( 'Y-m-d', $start ),
					'to'     => date( 'Y-m-d', $end ),
					'period' => 'custom',
				);
			}
		}

		switch ( $period ) {
			case 'week':
				$from = date( 'Y-m-d', strtotime( '-6 days', current_time( 'timestamp' ) ) );
				break;
			case 'month':
				$from = date( 'Y-m-01', current_time( 'timestamp' ) );
				break;
			case 'year':
				$from = date( 'Y-01-01', current_time( 'timestamp' ) );
				break;
			case 'today':
			default:
				$period = 'today';
				$from   = $today;
				break;
		}

		return array(
			'from'   => $from,
			'to'     => $today,
			'period' => $period,
		);
	}

	/**
	 * Return current room condition counts for a hotel.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return array
	 */
	private function room_condition_summary( $hotel_id ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		if ( ! $hotel_id ) {
			return array(
				'total'       => 0,
				'by_status'   => array(),
				'by_type'     => array(),
				'status_rows' => array(),
			);
		}

		$rooms_table = HRM_Database::table( 'rooms' );
		$status_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT status, COUNT(*) AS rooms_count FROM {$rooms_table} WHERE hotel_id = %d GROUP BY status",
				$hotel_id
			)
		);
		$type_rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT room_type, COUNT(*) AS rooms_count FROM {$rooms_table} WHERE hotel_id = %d GROUP BY room_type ORDER BY rooms_count DESC, room_type ASC",
				$hotel_id
			)
		);

		$by_status = array(
			'available'   => 0,
			'occupied'    => 0,
			'cleaning'    => 0,
			'maintenance' => 0,
		);
		$total     = 0;
		foreach ( $status_rows as $row ) {
			$key               = sanitize_key( $row->status );
			$count             = (int) $row->rooms_count;
			$by_status[ $key ] = $count;
			$total            += $count;
		}

		$by_type = array();
		foreach ( $type_rows as $row ) {
			$by_type[] = array(
				'type'  => ucwords( str_replace( '_', ' ', $row->room_type ) ),
				'count' => (int) $row->rooms_count,
			);
		}

		return array(
			'total'       => $total,
			'by_status'   => $by_status,
			'by_type'     => $by_type,
			'status_rows' => $status_rows,
		);
	}

	/**
	 * Assert frontend booking availability for a hotel.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return true|WP_Error
	 */
	private function assert_hotel_available( $hotel_id ) {
		$hotel_id = absint( $hotel_id );
		$hotel    = HRM_License::get_hotel( $hotel_id );

		if ( ! $hotel ) {
			return new WP_Error( 'hrm_hotel_missing', __( 'The hotel could not be found.', 'hrm-pro' ) );
		}

		if ( ! $hotel->is_active() || ! HRM_License::hotel_can( $hotel_id, 'frontend_booking' ) ) {
			return new WP_Error( 'hrm_frontend_closed', __( 'Online booking is temporarily unavailable for this hotel.', 'hrm-pro' ) );
		}

		return true;
	}

	/**
	 * Return a maintenance message for unavailable storefronts.
	 *
	 * @param HRM_License_Record|false $hotel Hotel record.
	 * @return string
	 */
	private function maintenance_message( $hotel ) {
		if ( ! $hotel ) {
			return __( 'This booking page is not connected to a hotel yet. Please contact the site owner.', 'hrm-pro' );
		}

		if ( ! $hotel->is_active() ) {
			return __( 'Online reservations are temporarily unavailable while this hotel updates its booking service. Please contact the hotel directly.', 'hrm-pro' );
		}

		return __( 'Online reservations are not enabled for this hotel plan. Please contact the hotel directly.', 'hrm-pro' );
	}

	/**
	 * Validate a date range.
	 *
	 * @param string $check_in  Check-in date.
	 * @param string $check_out Check-out date.
	 * @return bool
	 */
	private function valid_date_range( $check_in, $check_out ) {
		$start = strtotime( sanitize_text_field( $check_in ) );
		$end   = strtotime( sanitize_text_field( $check_out ) );

		return $start && $end && $end > $start;
	}

	/**
	 * Format rate data for frontend JSON.
	 *
	 * @param array $rate     Rate payload.
	 * @param int   $hotel_id Hotel ID.
	 * @return array
	 */
	private function format_rate_payload( $rate, $hotel_id ) {
		$rate['subtotal_formatted'] = HRM_Settings::money( $rate['subtotal'], $hotel_id );
		$rate['vat_formatted']      = HRM_Settings::money( $rate['vat_amount'], $hotel_id );
		$rate['total_formatted']    = HRM_Settings::money( $rate['total'], $hotel_id );

		return $rate;
	}
}
