<?php
/**
 * WhatsApp notification integration.
 *
 * @package HotelRoomManagerPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends hotel-scoped WhatsApp booking notifications when enabled.
 */
class HRM_WhatsApp {

	/**
	 * Send a booking WhatsApp message.
	 *
	 * @param string $phone        Recipient phone. Booking guest phone is used when empty.
	 * @param string $message_type Message type.
	 * @param int    $booking_id   Booking ID.
	 * @return bool
	 */
	public function send( $phone, $message_type, $booking_id ) {
		return true === $this->send_with_result( $phone, $message_type, $booking_id );
	}

	/**
	 * Send a booking WhatsApp message and return detailed errors for admin AJAX.
	 *
	 * @param string $phone        Recipient phone. Booking guest phone is used when empty.
	 * @param string $message_type Message type.
	 * @param int    $booking_id   Booking ID.
	 * @return true|WP_Error
	 */
	public function send_with_result( $phone, $message_type, $booking_id ) {
		$booking_id = absint( $booking_id );
		$booking    = ( new HRM_Bookings() )->get( $booking_id, 0 );

		if ( ! $booking ) {
			return new WP_Error( 'hrm_whatsapp_missing_booking', __( 'The booking could not be found.', 'hrm-pro' ) );
		}

		$hotel_id = (int) $booking->hotel_id;
		if ( ! HRM_License::hotel_can( $hotel_id, 'whatsapp_notifications' ) ) {
			return new WP_Error( 'hrm_whatsapp_plan_locked', __( 'WhatsApp notifications are not available on this plan.', 'hrm-pro' ) );
		}

		if ( 'yes' !== HRM_Settings::get( 'whatsapp_enabled', 'no', $hotel_id ) ) {
			return new WP_Error( 'hrm_whatsapp_disabled', __( 'WhatsApp notifications are disabled for this hotel.', 'hrm-pro' ) );
		}

		$guest = ( new HRM_Guests() )->get( (int) $booking->guest_id, $hotel_id );
		$room  = ( new HRM_Rooms() )->get( (int) $booking->room_id, $hotel_id );
		$hotel = HRM_License::get_hotel( $hotel_id );

		if ( ! $guest || ! $room || ! $hotel ) {
			return new WP_Error( 'hrm_whatsapp_missing_context', __( 'Guest, room, or hotel details are missing.', 'hrm-pro' ) );
		}

		$messages = array(
			'confirmation' => "Hello {$guest->full_name}! Your booking at {$hotel->hotel_name} is confirmed.\n\nRoom: {$room->room_number} ({$room->room_type})\nCheck-In: {$booking->check_in}\nCheck-Out: {$booking->check_out}\nTotal: " . HRM_Settings::money( $booking->total_amount, $hotel_id ) . "\n\nBooking Ref: #HRM{$booking->id}\n\nWe look forward to hosting you!",
			'checkin'      => "Welcome to {$hotel->hotel_name}, {$guest->full_name}! Your room {$room->room_number} is ready. Enjoy your stay!",
			'checkout'     => "Thank you for staying at {$hotel->hotel_name}, {$guest->full_name}! We hope you had a wonderful experience. Safe travels and we hope to see you again!",
			'invoice'      => "Invoice from {$hotel->hotel_name}:\n\nGuest: {$guest->full_name}\nRoom: {$room->room_number} ({$room->room_type})\nDates: {$booking->check_in} to {$booking->check_out}\nNights: {$booking->total_nights}\nTotal: " . HRM_Settings::money( $booking->total_amount, $hotel_id ) . "\nPaid: " . HRM_Settings::money( $booking->amount_paid, $hotel_id ) . "\nBalance: " . HRM_Settings::money( $booking->balance, $hotel_id ) . "\nInvoice Ref: #HRM{$booking->id}",
			'receipt'      => "Receipt for your stay at {$hotel->hotel_name}:\n\nRoom: {$room->room_number}\nDates: {$booking->check_in} to {$booking->check_out}\nNights: {$booking->total_nights}\nTotal Paid: " . HRM_Settings::money( $booking->total_amount, $hotel_id ) . "\nRef: #HRM{$booking->id}\n\nThank you!",
		);

		$message_type = sanitize_key( $message_type );
		$message      = isset( $messages[ $message_type ] ) ? $messages[ $message_type ] : '';
		if ( '' === $message ) {
			return new WP_Error( 'hrm_whatsapp_unknown_message', __( 'Unknown WhatsApp message type.', 'hrm-pro' ) );
		}

		$server_url = esc_url_raw( HRM_Settings::get( 'whatsapp_server_url', '', $hotel_id ) );
		$api_key    = sanitize_text_field( HRM_Settings::get( 'whatsapp_api_key', '', $hotel_id ) );
		if ( '' === $server_url || '' === $api_key ) {
			return new WP_Error( 'hrm_whatsapp_missing_settings', __( 'WhatsApp server URL and API key are required before messages can be sent.', 'hrm-pro' ) );
		}

		$response = wp_remote_post(
			rtrim( $server_url, '/' ) . '/send',
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'phone'   => sanitize_text_field( $phone ? $phone : $guest->phone ),
						'message' => $message,
						'api_key' => $api_key,
					)
				),
				'timeout' => 15,
			)
		);

		$success = ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response );
		$error   = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_body( $response );
		HRM_Activity_Log::log(
			$hotel_id,
			get_current_user_id(),
			$success ? 'whatsapp_sent' : 'whatsapp_failed',
			'booking',
			$booking_id,
			wp_json_encode(
				array(
					'type'  => $message_type,
					'phone' => $guest->phone,
					'error' => $success ? '' : $error,
				)
			)
		);

		if ( ! $success ) {
			return new WP_Error( 'hrm_whatsapp_send_failed', __( 'WhatsApp message could not be sent. Check the server URL and API key.', 'hrm-pro' ) );
		}

		return true;
	}

	/**
	 * Return whether WhatsApp can attempt sends for a hotel.
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return bool
	 */
	public function is_enabled( $hotel_id = 0 ) {
		$hotel_id = absint( $hotel_id ? $hotel_id : HRM_License::get_current_hotel_id() );

		return $hotel_id && HRM_License::hotel_can( $hotel_id, 'whatsapp_notifications' ) && 'yes' === HRM_Settings::get( 'whatsapp_enabled', 'no', $hotel_id );
	}
}
