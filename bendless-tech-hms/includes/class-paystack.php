<?php
/**
 * Paystack payment integration.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Paystack subscription and guest booking payments.
 */
class HRM_Paystack {

	/**
	 * Register integration hooks.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'hrm/v1',
			'/paystack-webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Start a hotel-owner subscription payment.
	 *
	 * @param int    $hotel_id      Hotel ID.
	 * @param string $plan          Plan slug.
	 * @param string $billing_cycle Billing cycle.
	 * @return string|WP_Error
	 */
	public function initiate_subscription( $hotel_id, $plan, $billing_cycle ) {
		$hotel_id      = absint( $hotel_id );
		$plan          = sanitize_key( $plan );
		$billing_cycle = 'yearly' === sanitize_key( $billing_cycle ) ? 'yearly' : 'monthly';
		$plans         = HRM_License::get_plans();

		if ( ! $hotel_id || empty( $plans[ $plan ] ) ) {
			return new WP_Error( 'hrm_invalid_plan', __( 'The selected subscription plan is not available.', 'hrm-pro' ) );
		}

		$secret_key = $this->get_secret_key();
		if ( '' === $secret_key ) {
			return new WP_Error( 'hrm_paystack_missing_key', __( 'Paystack secret key has not been configured.', 'hrm-pro' ) );
		}

		$hotel = HRM_License::get_hotel( $hotel_id );
		if ( ! $hotel ) {
			return new WP_Error( 'hrm_hotel_missing', __( 'The selected hotel could not be found.', 'hrm-pro' ) );
		}

		$amount = 'yearly' === $billing_cycle ? $this->subscription_yearly_amount( $hotel_id, $plan, $plans[ $plan ] ) : (float) $plans[ $plan ]['price_monthly'];
		if ( $amount <= 0 && 'enterprise' !== $plan ) {
			return new WP_Error( 'hrm_invalid_amount', __( 'The selected subscription amount is invalid.', 'hrm-pro' ) );
		}

		if ( 'enterprise' === $plan && $amount <= 0 ) {
			$this->activate_subscription_record( $hotel_id, $plan, $billing_cycle, 0, 'HRM-MANUAL-' . time(), '' );
			return admin_url( 'admin.php?page=hrm-dashboard&hrm_payment=enterprise' );
		}

		$reference = 'HRM-SUB-' . $hotel_id . '-' . time();
		$payload   = array(
			'email'        => sanitize_email( $hotel->hotel_email ),
			'amount'       => (int) round( $amount * 100 ),
			'reference'    => $reference,
			'callback_url' => admin_url( 'admin.php?page=hrm-subscription-callback' ),
			'metadata'     => wp_json_encode(
				array(
					'type'          => 'hms_subscription',
					'hotel_id'      => $hotel_id,
					'plan'          => $plan,
					'billing_cycle' => $billing_cycle,
				)
			),
		);

		$response = $this->request( 'POST', '/transaction/initialize', $payload );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['status'] ) || empty( $response['data']['authorization_url'] ) ) {
			return new WP_Error( 'hrm_paystack_init_failed', __( 'Paystack could not initialize this payment.', 'hrm-pro' ) );
		}

		HRM_Activity_Log::log(
			$hotel_id,
			get_current_user_id(),
			'subscription_payment_started',
			'subscription',
			null,
			wp_json_encode(
				array(
					'plan'      => $plan,
					'cycle'     => $billing_cycle,
					'reference' => $reference,
					'amount'    => $amount,
				)
			)
		);

		return esc_url_raw( $response['data']['authorization_url'] );
	}

	/**
	 * Verify a hotel-owner subscription payment.
	 *
	 * @param string $reference Paystack reference.
	 * @return bool|WP_Error
	 */
	public function verify_subscription( $reference ) {
		$reference = sanitize_text_field( $reference );

		if ( '' === $reference ) {
			return new WP_Error( 'hrm_missing_reference', __( 'Missing Paystack reference.', 'hrm-pro' ) );
		}

		$body = $this->verify_transaction( $reference );
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		if ( empty( $body['data']['status'] ) || 'success' !== $body['data']['status'] ) {
			return new WP_Error( 'hrm_payment_not_successful', __( 'The Paystack transaction was not successful.', 'hrm-pro' ) );
		}

		$metadata = $this->normalize_metadata( isset( $body['data']['metadata'] ) ? $body['data']['metadata'] : array() );
		if ( empty( $metadata['type'] ) || 'hms_subscription' !== $metadata['type'] ) {
			return new WP_Error( 'hrm_invalid_metadata', __( 'This Paystack reference is not an HMS subscription.', 'hrm-pro' ) );
		}

		$hotel_id      = absint( $metadata['hotel_id'] );
		$plan          = sanitize_key( $metadata['plan'] );
		$billing_cycle = 'yearly' === sanitize_key( $metadata['billing_cycle'] ) ? 'yearly' : 'monthly';
		$amount_paid   = isset( $body['data']['amount'] ) ? (float) $body['data']['amount'] / 100 : 0;
		$sub_code      = isset( $body['data']['subscription']['subscription_code'] ) ? sanitize_text_field( $body['data']['subscription']['subscription_code'] ) : '';

		return $this->activate_subscription_record( $hotel_id, $plan, $billing_cycle, $amount_paid, $reference, $sub_code );
	}

	/**
	 * Start a guest booking payment.
	 *
	 * @param int    $booking_id  Booking ID.
	 * @param float  $amount      Amount.
	 * @param string $guest_email Guest email.
	 * @param int    $hotel_id    Hotel ID.
	 * @return string|WP_Error
	 */
	public function initiate_booking_payment( $booking_id, $amount, $guest_email, $hotel_id ) {
		global $wpdb;

		$booking_id  = absint( $booking_id );
		$hotel_id    = absint( $hotel_id );
		$amount      = (float) $amount;
		$guest_email = sanitize_email( $guest_email );

		if ( ! $booking_id || ! $hotel_id || $amount <= 0 || '' === $guest_email ) {
			return new WP_Error( 'hrm_invalid_booking_payment', __( 'Booking payment details are incomplete.', 'hrm-pro' ) );
		}

		$secret_key = $this->get_secret_key();
		if ( '' === $secret_key ) {
			return new WP_Error( 'hrm_paystack_missing_key', __( 'Paystack secret key has not been configured.', 'hrm-pro' ) );
		}

		$reference = 'HRM-BKG-' . $booking_id . '-' . time();
		$payload   = array(
			'email'        => $guest_email,
			'amount'       => (int) round( $amount * 100 ),
			'reference'    => $reference,
			'callback_url' => home_url( '/booking-confirmation/?hrm_ref=' . rawurlencode( $reference ) ),
			'metadata'     => wp_json_encode(
				array(
					'type'       => 'room_booking',
					'booking_id' => $booking_id,
					'hotel_id'   => $hotel_id,
				)
			),
		);

		$response = $this->request( 'POST', '/transaction/initialize', $payload );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$wpdb->update(
			HRM_Database::table( 'bookings' ),
			array( 'paystack_reference' => $reference ),
			array(
				'id'       => $booking_id,
				'hotel_id' => $hotel_id,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);

		if ( empty( $response['status'] ) || empty( $response['data']['authorization_url'] ) ) {
			return new WP_Error( 'hrm_paystack_init_failed', __( 'Paystack could not initialize this payment.', 'hrm-pro' ) );
		}

		return esc_url_raw( $response['data']['authorization_url'] );
	}

	/**
	 * Start a guest booking payment before a booking row is created.
	 *
	 * The sanitized booking payload is kept in a short-lived transient and is
	 * committed only after Paystack verifies the transaction.
	 *
	 * @param int    $hotel_id    Hotel ID.
	 * @param float  $amount      Amount.
	 * @param string $guest_email Guest email.
	 * @param array  $intent      Sanitized booking intent.
	 * @return string|WP_Error
	 */
	public function initiate_booking_intent( $hotel_id, $amount, $guest_email, $intent ) {
		$hotel_id    = absint( $hotel_id );
		$amount      = (float) $amount;
		$guest_email = sanitize_email( $guest_email );
		$intent      = is_array( $intent ) ? $intent : array();

		if ( ! $hotel_id || $amount <= 0 || '' === $guest_email || empty( $intent ) ) {
			return new WP_Error( 'hrm_invalid_booking_payment', __( 'Booking payment details are incomplete.', 'hrm-pro' ) );
		}

		$secret_key = $this->get_secret_key();
		if ( '' === $secret_key ) {
			return new WP_Error( 'hrm_paystack_missing_key', __( 'Paystack secret key has not been configured.', 'hrm-pro' ) );
		}

		$reference = 'HRM-BKG-INT-' . $hotel_id . '-' . time() . '-' . wp_rand( 1000, 9999 );
		set_transient(
			'hrm_booking_intent_' . $reference,
			array(
				'hotel_id' => $hotel_id,
				'amount'   => $amount,
				'intent'   => $intent,
			),
			2 * HOUR_IN_SECONDS
		);

		$payload = array(
			'email'        => $guest_email,
			'amount'       => (int) round( $amount * 100 ),
			'reference'    => $reference,
			'callback_url' => home_url( '/booking-confirmation/?hrm_ref=' . rawurlencode( $reference ) ),
			'metadata'     => wp_json_encode(
				array(
					'type'     => 'room_booking_intent',
					'hotel_id' => $hotel_id,
				)
			),
		);

		$response = $this->request( 'POST', '/transaction/initialize', $payload );
		if ( is_wp_error( $response ) ) {
			delete_transient( 'hrm_booking_intent_' . $reference );
			return $response;
		}

		if ( empty( $response['status'] ) || empty( $response['data']['authorization_url'] ) ) {
			delete_transient( 'hrm_booking_intent_' . $reference );
			return new WP_Error( 'hrm_paystack_init_failed', __( 'Paystack could not initialize this payment.', 'hrm-pro' ) );
		}

		return esc_url_raw( $response['data']['authorization_url'] );
	}

	/**
	 * Verify a guest booking payment.
	 *
	 * @param string $reference Paystack reference.
	 * @return int|false|WP_Error
	 */
	public function verify_booking_payment( $reference ) {
		global $wpdb;

		$reference = sanitize_text_field( $reference );
		if ( '' === $reference ) {
			return new WP_Error( 'hrm_missing_reference', __( 'Missing Paystack reference.', 'hrm-pro' ) );
		}

		$body = $this->verify_transaction( $reference );
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		if ( empty( $body['data']['status'] ) || 'success' !== $body['data']['status'] ) {
			return false;
		}

		$metadata = $this->normalize_metadata( isset( $body['data']['metadata'] ) ? $body['data']['metadata'] : array() );
		if ( empty( $metadata['type'] ) ) {
			return false;
		}

		if ( 'room_booking_intent' === $metadata['type'] ) {
			return $this->verify_booking_intent( $reference, $body, $metadata );
		}

		if ( 'room_booking' !== $metadata['type'] ) {
			return false;
		}

		$booking_id = absint( $metadata['booking_id'] );
		$hotel_id   = absint( $metadata['hotel_id'] );
		$amount     = isset( $body['data']['amount'] ) ? (float) $body['data']['amount'] / 100 : 0;

		if ( ! $booking_id || ! $hotel_id ) {
			return false;
		}

		$existing_booking = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT payment_status FROM {$wpdb->prefix}hrm_bookings WHERE id = %d AND hotel_id = %d LIMIT 1",
				$booking_id,
				$hotel_id
			)
		);
		$already_paid     = $existing_booking && 'paid' === $existing_booking->payment_status;

		$wpdb->update(
			HRM_Database::table( 'bookings' ),
			array(
				'payment_status' => 'paid',
				'amount_paid'    => $amount,
				'balance'        => 0,
				'status'         => 'confirmed',
			),
			array(
				'id'       => $booking_id,
				'hotel_id' => $hotel_id,
			),
			array( '%s', '%f', '%f', '%s' ),
			array( '%d', '%d' )
		);

		if ( ! $already_paid && class_exists( 'HRM_WhatsApp' ) ) {
			$whatsapp = new HRM_WhatsApp();
			$whatsapp->send( '', 'confirmation', $booking_id );
			$whatsapp->send( '', 'receipt', $booking_id );
		}

		HRM_Activity_Log::log(
			$hotel_id,
			0,
			'booking_payment_verified',
			'booking',
			$booking_id,
			wp_json_encode(
				array(
					'reference' => $reference,
					'amount'    => $amount,
				)
			)
		);

		return $booking_id;
	}

	/**
	 * Verify a Paystack booking intent and create the actual booking.
	 *
	 * @param string $reference Paystack reference.
	 * @param array  $body      Verified Paystack response.
	 * @param array  $metadata  Normalized metadata.
	 * @return int|false|WP_Error
	 */
	private function verify_booking_intent( $reference, $body, $metadata ) {
		$reference = sanitize_text_field( $reference );
		$hotel_id  = ! empty( $metadata['hotel_id'] ) ? absint( $metadata['hotel_id'] ) : 0;
		$amount    = isset( $body['data']['amount'] ) ? (float) $body['data']['amount'] / 100 : 0;
		$existing  = ( new HRM_Bookings() )->get_by_paystack_reference( $reference );

		if ( $existing ) {
			return (int) $existing->id;
		}

		$transient_key = 'hrm_booking_intent_' . $reference;
		$stored        = get_transient( $transient_key );
		if ( ! is_array( $stored ) || empty( $stored['intent'] ) ) {
			return new WP_Error( 'hrm_booking_intent_expired', __( 'This booking session has expired. Please start the reservation again.', 'hrm-pro' ) );
		}

		$hotel_id = $hotel_id ? $hotel_id : absint( $stored['hotel_id'] );
		if ( ! $hotel_id || absint( $stored['hotel_id'] ) !== $hotel_id ) {
			return new WP_Error( 'hrm_booking_intent_invalid', __( 'This booking session could not be matched to a hotel.', 'hrm-pro' ) );
		}

		$intent                         = (array) $stored['intent'];
		$intent['payment_status']       = 'paid';
		$intent['status']               = 'confirmed';
		$intent['amount_paid']          = $amount;
		$intent['paystack_reference']   = $reference;
		$intent['verified_total_amount'] = $amount;

		$result = ( new HRM_Bookings() )->create_frontend_booking( $hotel_id, $intent );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		delete_transient( $transient_key );

		$booking_id = absint( $result['booking_id'] );
		if ( class_exists( 'HRM_WhatsApp' ) ) {
			$whatsapp = new HRM_WhatsApp();
			$whatsapp->send( '', 'confirmation', $booking_id );
			$whatsapp->send( '', 'receipt', $booking_id );
		}

		HRM_Activity_Log::log(
			$hotel_id,
			0,
			'booking_payment_verified',
			'booking',
			$booking_id,
			wp_json_encode(
				array(
					'reference' => $reference,
					'amount'    => $amount,
					'source'    => 'frontend_intent',
				)
			)
		);

		return $booking_id;
	}

	/**
	 * Handle signed Paystack webhooks.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function handle_webhook( WP_REST_Request $request ) {
		$payload   = $request->get_body();
		$signature = (string) $request->get_header( 'x-paystack-signature' );
		$secret    = $this->get_secret_key();

		if ( '' === $secret || ! hash_equals( hash_hmac( 'sha512', $payload, $secret ), $signature ) ) {
			return new WP_REST_Response( array( 'message' => 'Invalid signature' ), 400 );
		}

		$event = json_decode( $payload, true );
		if ( ! is_array( $event ) || empty( $event['event'] ) ) {
			return new WP_REST_Response( array( 'message' => 'Invalid event' ), 400 );
		}

		$this->process_webhook_event( $event );

		return new WP_REST_Response( array( 'message' => 'OK' ), 200 );
	}

	/**
	 * Verify a Paystack transaction.
	 *
	 * @param string $reference Paystack reference.
	 * @return array|WP_Error
	 */
	public function verify_transaction( $reference ) {
		$reference = sanitize_text_field( $reference );

		return $this->request( 'GET', '/transaction/verify/' . rawurlencode( $reference ) );
	}

	/**
	 * Return the active public key.
	 *
	 * @return string
	 */
	public function get_public_key() {
		return (string) HRM_PAYSTACK_PUBLIC_KEY;
	}

	/**
	 * Return the active secret key.
	 *
	 * @return string
	 */
	public function get_secret_key() {
		return (string) HRM_PAYSTACK_SECRET_KEY;
	}

	/**
	 * Return whether Paystack test mode is enabled.
	 *
	 * @return bool
	 */
	public function is_test_mode() {
		return (bool) get_option( 'hrm_test_mode', false );
	}

	/**
	 * Activate a hotel subscription and write the subscription ledger row.
	 *
	 * @param int    $hotel_id      Hotel ID.
	 * @param string $plan          Plan slug.
	 * @param string $billing_cycle Billing cycle.
	 * @param float  $amount_paid   Amount paid.
	 * @param string $reference     Paystack reference.
	 * @param string $sub_code      Paystack subscription code.
	 * @return bool|WP_Error
	 */
	private function activate_subscription_record( $hotel_id, $plan, $billing_cycle, $amount_paid, $reference, $sub_code ) {
		global $wpdb;

		$hotel_id      = absint( $hotel_id );
		$plan          = sanitize_key( $plan );
		$billing_cycle = 'yearly' === sanitize_key( $billing_cycle ) ? 'yearly' : 'monthly';
		$plans         = HRM_License::get_plans();

		if ( ! $hotel_id || empty( $plans[ $plan ] ) ) {
			return new WP_Error( 'hrm_invalid_subscription', __( 'Subscription data is invalid.', 'hrm-pro' ) );
		}

		$months  = 'yearly' === $billing_cycle ? 12 : 1;
		$ends_at = date( 'Y-m-d H:i:s', strtotime( '+' . $months . ' months', current_time( 'timestamp' ) ) );

		$updated = $wpdb->update(
			HRM_Database::table( 'hotels' ),
			array(
				'plan'                 => $plan,
				'subscription_status'  => 'active',
				'subscription_ends_at' => $ends_at,
				'paystack_sub_code'    => sanitize_text_field( $sub_code ),
				'is_active'            => 1,
			),
			array( 'id' => $hotel_id ),
			array( '%s', '%s', '%s', '%s', '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'hrm_subscription_update_failed', __( 'The hotel subscription could not be updated.', 'hrm-pro' ) );
		}

		$wpdb->insert(
			HRM_Database::table( 'subscriptions' ),
			array(
				'hotel_id'           => $hotel_id,
				'plan'               => $plan,
				'billing_cycle'      => $billing_cycle,
				'amount_paid'        => (float) $amount_paid,
				'paystack_reference' => sanitize_text_field( $reference ),
				'paystack_sub_code'  => sanitize_text_field( $sub_code ),
				'starts_at'          => current_time( 'mysql' ),
				'ends_at'            => $ends_at,
				'status'             => 'active',
				'is_test'            => $this->is_test_mode() ? 1 : 0,
				'created_at'         => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		HRM_Activity_Log::log(
			$hotel_id,
			get_current_user_id(),
			'subscription_activated',
			'subscription',
			(int) $wpdb->insert_id,
			wp_json_encode(
				array(
					'plan'      => $plan,
					'cycle'     => $billing_cycle,
					'reference' => $reference,
					'amount'    => $amount_paid,
				)
			)
		);

		return true;
	}

	/**
	 * Return first-year or renewal yearly subscription amount.
	 *
	 * @param int    $hotel_id Hotel ID.
	 * @param string $plan     Plan slug.
	 * @param array  $config   Plan config.
	 * @return float
	 */
	private function subscription_yearly_amount( $hotel_id, $plan, $config ) {
		global $wpdb;

		$hotel_id = absint( $hotel_id );
		$plan     = sanitize_key( $plan );
		$first    = isset( $config['price_yearly'] ) ? (float) $config['price_yearly'] : 0;
		$renewal  = isset( $config['renewal_yearly'] ) ? (float) $config['renewal_yearly'] : $first;
		$hotel    = HRM_License::get_hotel( $hotel_id );

		if ( $hotel && $hotel->plan === $plan && $hotel->subscription_ends_at ) {
			return $renewal;
		}

		$prior = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}hrm_subscriptions WHERE hotel_id = %d AND plan = %s",
				$hotel_id,
				$plan
			)
		);

		return $prior > 0 ? $renewal : $first;
	}

	/**
	 * Send a request to Paystack.
	 *
	 * @param string     $method  HTTP method.
	 * @param string     $path    API path.
	 * @param array|null $payload Request payload.
	 * @return array|WP_Error
	 */
	private function request( $method, $path, $payload = null ) {
		$secret_key = $this->get_secret_key();

		if ( '' === $secret_key ) {
			return new WP_Error( 'hrm_paystack_missing_key', __( 'Paystack secret key has not been configured.', 'hrm-pro' ) );
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $secret_key,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 20,
		);

		if ( null !== $payload ) {
			$args['body'] = wp_json_encode( $payload );
		}

		$url = HRM_PAYSTACK_BASE_URL . $path;

		if ( 'GET' === strtoupper( $method ) ) {
			$response = wp_remote_get( $url, $args );
		} else {
			$response = wp_remote_post( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$message = isset( $body['message'] ) ? sanitize_text_field( $body['message'] ) : __( 'Paystack request failed.', 'hrm-pro' );
			return new WP_Error( 'hrm_paystack_request_failed', $message, array( 'status' => $code ) );
		}

		if ( ! is_array( $body ) ) {
			return new WP_Error( 'hrm_paystack_bad_response', __( 'Paystack returned an invalid response.', 'hrm-pro' ) );
		}

		return $body;
	}

	/**
	 * Normalize Paystack metadata into an array.
	 *
	 * @param mixed $metadata Metadata.
	 * @return array
	 */
	private function normalize_metadata( $metadata ) {
		if ( is_string( $metadata ) ) {
			$decoded = json_decode( $metadata, true );
			return is_array( $decoded ) ? $decoded : array();
		}

		if ( is_object( $metadata ) ) {
			return (array) $metadata;
		}

		return is_array( $metadata ) ? $metadata : array();
	}

	/**
	 * Process a verified webhook event.
	 *
	 * @param array $event Webhook event.
	 * @return void
	 */
	private function process_webhook_event( $event ) {
		global $wpdb;

		$event_name = sanitize_text_field( $event['event'] );
		$data       = isset( $event['data'] ) && is_array( $event['data'] ) ? $event['data'] : array();
		$metadata   = $this->normalize_metadata( isset( $data['metadata'] ) ? $data['metadata'] : array() );
		$hotel_id   = ! empty( $metadata['hotel_id'] ) ? absint( $metadata['hotel_id'] ) : 0;

		switch ( $event_name ) {
			case 'subscription.disable':
				$sub_code = isset( $data['subscription_code'] ) ? sanitize_text_field( $data['subscription_code'] ) : '';
				if ( $sub_code ) {
					$wpdb->update(
						HRM_Database::table( 'hotels' ),
						array( 'subscription_status' => 'expired' ),
						array( 'paystack_sub_code' => $sub_code ),
						array( '%s' ),
						array( '%s' )
					);
				}
				break;

			case 'invoice.payment_failed':
				if ( $hotel_id ) {
					$wpdb->update(
						HRM_Database::table( 'hotels' ),
						array( 'subscription_status' => 'past_due' ),
						array( 'id' => $hotel_id ),
						array( '%s' ),
						array( '%d' )
					);
				}
				break;

			case 'charge.success':
				if ( ! empty( $data['reference'] ) && ! empty( $metadata['type'] ) ) {
					if ( 'hms_subscription' === $metadata['type'] ) {
						$this->verify_subscription( sanitize_text_field( $data['reference'] ) );
					}

					if ( in_array( $metadata['type'], array( 'room_booking', 'room_booking_intent' ), true ) ) {
						$this->verify_booking_payment( sanitize_text_field( $data['reference'] ) );
					}
				}
				break;
		}

		if ( $hotel_id ) {
			HRM_Activity_Log::log(
				$hotel_id,
				0,
				'paystack_webhook_' . $event_name,
				'paystack',
				null,
				wp_json_encode( array( 'event' => $event_name ) )
			);
		}
	}
}
